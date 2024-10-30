<?php

class BlogSafe_Scanner_GetOfficialChecksums {

    public function __construct($silent) {
        global $wpdb;
        $this->silent = $silent;
        $this->OS = 'l';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->OS = 'w';
        }        
    }

    private function ShowOutput($which) {
        if ($this->silent) {
            return;
        }
        switch ($which) {
            case 1:
                echo "Retrieving Wordpress file list<br>";
                @ob_flush();
                @flush();
                break;
        }
    }

    public function GetChecksums() {

        $BSUtils = new BlogSafe_Scanner_Utils();        
        $checksums = array();
        $this->ShowOutput(1);
        $language = get_locale();
        $version = get_bloginfo('version');
        $response = wp_remote_post(BLOGSAFE_WP_OFFICIAL_URL, array(
            'method' => 'GET',
            'timeout' => 20,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
                'version' => $version,
                'locale' => $language
            ),
            'cookies' => array()
        ));

        if (is_wp_error($response)) {
            echo '<font color="red">' . $response->get_error_message() . "</font>";
            return false;
        } elseif ($response) {
            if ($response['body'] instanceof stdClass) {
                $json_data = $response['body'];
            } else {
                $json_data = @json_decode($response['body'], true);
            }

            if (!json_last_error()) {
                foreach ($json_data['checksums'] as $key => $value) {
                    $key = $BSUtils->revoutname($key, $this->OS);
                    if (is_array($value) && count($value) > 1) {
                        for ($x = 0; $x <= count($value) - 1; $x++) {
                            array_push($checksums, array('name' => $key, 'md5' => $value[$x], 'dupe' => true, 'slug' => $language, 'version' => $version, 'type' => 'O'));
                        }
                    } else {
                        array_push($checksums, array('name' => $key, 'md5' => $value, 'dupe' => false, 'slug' => $language, 'version' => $version, 'type' => 'O'));
                    }
                }
            } else {
                echo '<font color="red">' . __('Error reading official JSON file.', 'BSScanner') . "</font>";
                return false;
            }
            return (array) $checksums;
        }
    }
}
?>