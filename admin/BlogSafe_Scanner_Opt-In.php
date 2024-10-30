<?php

class BlogSafe_Scanner_Opt_In {

    public function Set_Opt_In() {
        if (isset($_GET['optin'])) {
            update_option('BSScanner_Opt_In', true, 'no');
        } else {
            update_option('BSScanner_Opt_In', false, 'no');
        }
        update_option('BSScanner_error_message', serialize(array('message' => __('Settings Updated.', 'BSScanner'), 'type' => 1)));
    }

    public function Show_Opt_In() {
        $nonce = wp_create_nonce('BSSnonce');
        if (get_option('BSScanner_Opt_In') == true) {
            $optchecked = 'checked';
        } else {
            $optchecked = '';
        }
        echo '<h1>' . __('Opt-In', 'BSScanner') . '</h1>';
        echo '<br><br><strong>' . __('BlogSafe.org API', 'BSScanner') . '</strong>';
        echo '<br>' . __('In order to check for abandoned themes and plugins as well as potentially vulnerable files, BlogSafe Scanner will upload a complete list of plugins and themes found on your site to the BlogSafe.org API.  Various other pieces of non-personal information such as IP address may be obtained during this transfer.  BlogSafe.org may retain this information in order to provide statistics which will help improve our products.', 'BSScanner') . '</strong>';
        echo '<br><br><strong>' . __('Privacy Policy', 'BSScanner') . '</strong>';
        echo '<br>' . __('Our privacy policy is available online at <a href="https://blogsafe.org/privacy-policy/" target="blank">https://blogsafe.org/privacy-policy/</a>', 'BSScanner') . '</strong>';
        echo '<form action="" method="get">';
        echo '<br><br><input type="checkbox" name="optin" value="optin" ' . $optchecked . '>' . __('Check to opt-in', 'BSScanner');

        echo '<input name="BSSnonce" type="hidden" value="' . $nonce . '" />';
        echo '<input name="page" type="hidden" value="BlogSafeScanner" />';
        echo '<br><br><button class="button-primary" type="submit" name="action" value="UpdateOptIn">' . __('Update', 'BSScanner') . '</button>&nbsp;';
        echo '</form></div></div></div>';
    }
}