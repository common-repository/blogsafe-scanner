<?php

class BlogSafe_Scanner_Utils {

    public function __construct() {
//
    }

    public function outname($name, $os) {
        if ($os == 'w') {
            return str_replace('\\', '/', $name);
        }
        return $name;
    }    

    public function revoutname($name, $os) {
        if ($os == 'w') {
            return str_replace('/', '\\', $name);
        }
        return $name;
    } 
    
    public function get_ext($file) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $extensionlist = array("sh", "asp", "aspx", "cgi", "cshtml", "ipl", "js", "jsp", "php", "php3", "ph3", "php4", "ph4", "php5", "ph5", "phtm", "phtml", "pl", "py", "rb", "rbw", "ssjs");
        if (@in_array($ext, $extensions)) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function toggleIgnore($id) {
        global $wpdb;

        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $wpdb->query($wpdb->prepare("Update $table_name set ignoredFile = 0 where ID = %s", $id));
    }

    public function CheckFirst() {
        if (get_option('BSScanner_FirstScan') == 'yes') {
            return 0;
        } else {
            //just in case the option got removed
            update_option('BSScanner_FirstScan', 'no', 'no');
            return 1;
        }
    }

    public function getVersion() {
        if (get_option('BSScanner_Version') != get_bloginfo('version')) {
            return true;
        }
        return false;
    }

    public function getMultiSite() {
        $pathlist = array();
        if (is_multisite()) {
            $sites = get_sites();
            foreach ($sites as $site) {
                foreach ($site as $key => $value) {
                    if ($key == 'path' && $value != '/') {
                        array_push($pathlist, $value);
                    }
                }
            }
        }
        return $pathlist;
    }

    public function getPlugins() {
        global $wpdb;

        if (!function_exists('get_plugins')) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        if (!function_exists('get_home_path')) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        $plugins = get_plugins();
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM $table_name where type='P'");
        if (count($plugins) > $rowcount) {
            return true;
        } else {
            $SQL = "Select * from $table_name where type='P'";
            $results = $wpdb->get_results($SQL);
            foreach ($results as $result) {
                foreach ($plugins as $plugin) {
                    if ($result->Name == $plugin["Name"]) {
                        if ($result->Version != $plugin["Version"]) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
    }

    public function getThemes() {
        global $wpdb;

        if (!function_exists('wp_get_themes')) {
            require_once( ABSPATH . 'wp-admin/includes/theme.php' );
        }
        $themearray = wp_get_themes();
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        foreach ($themearray as $theme) {
            $rowcount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name where type='T' and Domain = %s", $theme->get('TextDomain')));
            if ($rowcount == 0) {
                return true;
            }
        }
        $SQL = "Select * from $table_name where type='T'";
        $results = $wpdb->get_results($SQL);
        foreach ($results as $result) {
            foreach ($themearray as $theme) {
                if ($result->Name == $theme->get('Name')) {
                    if ($result->Version != $theme->get('Version')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
?>