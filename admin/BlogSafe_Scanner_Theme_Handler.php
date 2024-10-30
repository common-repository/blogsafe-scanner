<?php

class BlogSafe_Scanner_GetThemeChecksums {

    public function __construct($silent) {
        global $wpdb;
        $this->silent = $silent;
    }

    private function array_key_first(array $arr) {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }

    private function ShowOutput($which, $value = '') {
        if ($this->silent) {
            return;
        }
        switch ($which) {
            case 1:
                echo __("Retrieving Themes", 'BSScanner') . "<br>";
                echo '<div id="themes"></div>';
                echo "<script>
                const element3 = document.querySelector('#themes');
                </script>";
                @ob_flush();
                @flush();
                break;
            case 2:
                echo "<script>
                element3.innerHTML = `<div>" . __("Checking Theme: ", 'BSScanner') . $value . "</div>`;
                </script>";
                @ob_flush();
                @flush();
                break;
            case 3:
                echo "<script>
                element3.innerHTML = `<div>Scanning Theme: " . $value . "</div>`;
                </script>";
                @ob_flush();
                @flush();
                break;
            case 4:
                echo "<script>
                element3.innerHTML = `<div>" . __("Themes Found: ", 'BSScanner') . $value . "</div>`;
                </script>";
                @ob_flush();
                @flush();
        }
    }

    private function DownloadZip($url, $target) {
        $response = wp_remote_get($url, array(
            'timeout' => 20
        ));
        if (is_wp_error($response)) {
            return false;
        }
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code != 200) {
            return false;
        }
        file_put_contents($target, $response['body']);
        return true;
    }

    private function ExtractZip($zipFile) {
        $extractFolder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'chksm_' . md5(microtime(true)) . '.extracted';
        @mkdir($extractFolder, 0777, true);
        $zip = new ZipArchive();
        $zip->open($zipFile);
        $zip->extractTo($extractFolder);
        $zip->close();
        return $extractFolder;
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }

    private function ScanDir($target, $domain, $orig, $version) {
        global $wpdb;

        $checksums = array();        
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        if (is_dir($target)) {
            $files = glob($target . '*', GLOB_MARK); // GLOB_MARK adds a slash to directories returned
            foreach ($files as $file) {
                $checksums = array_merge($checksums,$this->ScanDir($file, $domain, $orig, $version));
                if (!is_dir($file)) {
                    $dir = str_replace($orig, '', $file);
                    $dir = get_theme_root() . DIRECTORY_SEPARATOR . $domain . DIRECTORY_SEPARATOR . $dir;
                    $thisthemepath = str_replace(get_home_path(), '', $dir);
                    $checksum = md5_file($file);
                    array_push($checksums, array('name' => $thisthemepath, 'md5' => $checksum, 'dupe' => false, 'slug' => $domain, 'version' => $version, 'type' => 'T'));
                }
            }
        }
        return $checksums;
    }

    public function GetThemeChecksums() {
        global $wpdb;

        $themesums = array();
        $themearray = wp_get_themes();

        $themecount = 0;
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        $sql = "UPDATE $table_name SET found = 0 where Type ='T'";
        $wpdb->query($sql);
        $this->ShowOutput(1);
        foreach ($themearray as $theme) {
            $domain = $theme->get('TextDomain');
            if (empty($domain)) {
                $domain = $keys[$themecount];
            }
            $themecount ++;
            $name = $theme->get('Name');
            $version = $theme->get('Version');
            $rowcount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name where Type='T' and Name = %s and Domain = %s", $name, $domain));
            if ($rowcount === NULL) {
                echo $wpdb->last_error . '<br>';
                return false;
            }
            if ($rowcount > 0) {
                $SQL = $wpdb->prepare("Update $table_name SET found = 1, Version = %s where Type='T' and Name = %s and Domain = %s", $version, $name, $domain);
            } else {
                $SQL = $wpdb->prepare("
                    INSERT INTO " . $table_name . "(Name, Type,Domain,Version,found)
                    VALUES ( %s, %s, %s, %s,'1')
                    ", $name, 'T', $domain, $version);
            }
            if ($wpdb->query($SQL) === false) {
                echo $wpdb->last_error . '<br>';
                return false;
            }
            $this->ShowOutput(2, $name);
            $url = BLOGSAFE_WP_OFFICIAL_THEME_URL . $domain . '.' . $version . '.zip';
            $target = wp_tempnam();
            if ($this->DownloadZip($url, $target)) {
                $this->ShowOutput(3, $name);
                $ret = $this->ExtractZip($target);
                $targetdir = $ret . DIRECTORY_SEPARATOR . $domain . DIRECTORY_SEPARATOR;
                $thissums = $this->ScanDir($targetdir, $domain, $targetdir, $version);
                $themesums = array_merge($themesums, $thissums);
                if (file_exists($ret)) {
                    $this->rrmdir($ret);
                }
                if (file_exists($target)) {
                    unlink($target);
                }
            } else {
                //theme not found on officials
            }
        }
        $SQL = "Delete from $table_name where found=0 and Type='T'";
        if ($wpdb->query($SQL) === false) {
                echo $wpdb->last_error . '<br>';
                return false;
        }
        $this->ShowOutput(4, $themecount);
        return $themesums;
    }
}
?>