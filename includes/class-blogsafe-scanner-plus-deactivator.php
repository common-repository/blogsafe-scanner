<?php

class Blogsafe_Scanner_Plus_Deactivator
{
    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        global  $wpdb ;
        $to = get_option( 'admin_email' );
        $subject = __( "BlogSafe Scanner deactivated!", 'BSScanner' );
        $body = __( "BlogSafe Scanner at " . get_option( 'blogname' ) . " has been deactivated!", 'BSScanner' );
        $headers = array();
        $headers[] = 'From: ' . $to;
        wp_mail(
            $to,
            $subject,
            $body,
            $headers
        );
        wp_clear_scheduled_hook( 'BSScanner_quick_scan' );
        wp_clear_scheduled_hook( 'BSScanner_full_scan' );
        delete_option( "BSScanner_Found_Email" );
        delete_option( 'BSScanner_Version' );
        delete_option( 'BSScanner_Receive_Email' );
        delete_option( 'BSScanner_FirstScan' );
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query( $sql );
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        $sql = "DROP TABLE IF EXISTS {$table_name}";
        $wpdb->query( $sql );
    }

}