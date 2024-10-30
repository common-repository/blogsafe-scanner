<?php

class BlogSafe_Scanner_GetPluginChecksums {

    public function __construct($silent) {
        global $wpdb;
        $this->silent = $silent;
        $this->OS = 'l';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->OS = 'w';
        }         
    }

    private function ShowOutput($which, $value = '') {
        if ($this->silent) {
            return;
        }
        switch ($which) {
            case 1:
                echo __("Retrieving Wordpress plugin list", 'BSScanner') . "<br>";
                echo '<div id="plugins"></div>';
                echo "<script>
                    const element2 = document.querySelector('#plugins');
                    </script>";
                @ob_flush();
                @flush();
                break;
            case 2:
                echo "<script>
                    element2.innerHTML = `<div>" . __("Found plugin: ", 'BSScanner') . $value . "</div>`;
                    </script>";
                @ob_flush();
                @flush();
                break;
            case 3:
                echo "<script>
            element2.innerHTML = `<div>" . __("Plugins found: ", 'BSScanner') . " $value</div>`;
            </script>";
                @ob_flush();
                @flush();
                break;
        }
    }

    public function GetPluginChecksums() {
        global $wpdb;

        $BSUtils = new BlogSafe_Scanner_Utils();                
        $plugincount = 0;
        $this->ShowOutput(1);
        $checksums = array();
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        $sql = "UPDATE $table_name SET found = 0 where Type ='P'";
        $wpdb->query($sql);
        $plugins = get_plugins();
        foreach ($plugins as $key => $value) {
            //fixes issue where plugin path dir != text domain            
            $possibledomain = substr($key, 0, strpos($key, '/'));
            $origdomain = $value["TextDomain"];
            $name = $value["Name"];
            if ($value["TextDomain"] != $possibledomain) {
                $value["TextDomain"] = $possibledomain;
            }
            $SQL = $wpdb->prepare("SELECT COUNT(*) FROM $table_name where Type='P' and Name = %s and Domain = %s", $name, $origdomain);
            $rowcount = $wpdb->get_var($SQL);            
            if ($rowcount === NULL) {
                echo $wpdb->last_error . '<br>';
                return false;
            }
            if ($rowcount > 0) {
                $SQL = $wpdb->prepare("Update $table_name SET found = '1', Version = %s where Type='P' and Name = %s and Domain = %s", $value["Version"], $name, $origdomain);
            } else {
                $SQL = $wpdb->prepare("
                    INSERT INTO " . $table_name . "(Name,Type,Domain,Version,found)
                    VALUES ( %s, %s, %s, %s, '1')
                    ", $name, 'P', $origdomain, $value["Version"]);
            }
            if ($wpdb->query($SQL) === false) {
                echo $wpdb->last_error . '<br>';
                return false;
            }
            $plugincount++;
            $this->ShowOutput(2, $value["Name"]);
            $thisurl = BLOGSAFE_WP_OFFICIAL_PLUGIN_URL . $value["TextDomain"] . '/' . $value["Version"] . '.json';
            $response = wp_remote_post($thisurl,array('timeout' => 20));            
            if (is_wp_error($response)) {
                echo '<font color="red">' . $response->get_error_message() . "</font><br>";
                return false;
            }            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code == 200) {
                if ($response['body'] instanceof stdClass) {
                    $json_data = $response['body'];
                } else {
                    $json_data = @json_decode($response['body'], true);
                }
                if (!json_last_error()) {
                    foreach ($json_data['files'] as $key2 => $value2) {
                        $pluginpath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . plugin_dir_path($key) . $key2;
                        $name = str_replace(get_home_path(), '', $pluginpath);
                        $name = $BSUtils->revoutname($name, $this->OS);                        
                        if (is_array($value2['md5'])) {
                            for ($x = 0; $x <= count($value2['md5']) - 1; $x++) {
                                array_push($checksums, array('name' => $name, 'md5' => $value2['md5'][$x], 'dupe' => true, 'slug' => $value["TextDomain"], 'version' => $value["Version"], 'type' => 'P'));
                            }
                        } else {                         
                            array_push($checksums, array('name' => $name, 'md5' => $value2['md5'], 'dupe' => false, 'slug' => $value["TextDomain"], 'version' => $value["Version"], 'type' => 'P'));
                        }
                    }
                } else {
                    echo '<font color="red">' . __('Error reading official JSON file.', 'BSScanner') . "</font>";
                    return false;
                }
            } else {
                // TODO Plugin not found on officials
            }
            $this->ShowOutput(3, $plugincount);
        }
        $SQL = "Delete from $table_name where found=0 and Type='P'";
        if ($wpdb->query($SQL) === false) {
            echo $wpdb->last_error . '<br>';
            return false;
        }       
        return (array) $checksums;
    }
}