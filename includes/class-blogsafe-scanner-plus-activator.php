<?php

class Blogsafe_Scanner_Plus_Activator
{
    /**
     * Short Description.
     * (use period)
     *
     * Long Description.
     *
     * @since 1.0.0
     */
    public static function activate( $network_wide )
    {
        global  $wpdb ;
        if ( is_multisite() && $network_wide ) {
            wp_die( __( 'BlogSafe Scanner should not be activated network wide. It should only be activated on the parent site.', 'BSScanner' ) );
        }
        
        if ( get_option( 'BSScanner_DB_Version' ) != BLOGSAFESCAN_DBVER ) {
            $table_name = $wpdb->base_prefix . "BS_Scanner";
            $sql = "DROP TABLE IF EXISTS {$table_name}";
            $wpdb->query( $sql );
            $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
            $sql = "DROP TABLE IF EXISTS {$table_name}";
            $wpdb->query( $sql );
            update_option( 'BSScanner_DB_Version', BLOGSAFESCAN_DBVER, 'no' );
        }
        
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        if ( !get_option( 'BSScanner_Opt_In' ) ) {
            update_option( 'BSScanner_Opt_In', false, 'no' );
        }
        update_option( "BlogSafe_Scanner_Report_Vulnerabilities", '', 'no' );
        update_option( "BlogSafe_Scanner_Report_Abandoned_Themes", '', 'no' );
        update_option( "BlogSafe_Scanner_Report_Abandoned_Plugins", '', 'no' );
        update_option( "BlogSafe_Scanner_Report_Files", '', 'no' );
        update_option( "BlogSafe_Scanner_Report", '', 'no' );
        update_option( "BlogSafe_Scanner_Ignore_Changed", false, 'no' );
        update_option( 'BSScanner_Version', get_bloginfo( 'version' ), 'no' );
        update_option( 'BSScanner_FirstScan', 'yes', 'no' );
        update_option( 'BSScanner_QuickScan', 'hourly', 'no' );
        update_option( 'BSScanner_FullScan', 'daily', 'no' );
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (\n            `ID` int(11) NOT NULL AUTO_INCREMENT,\n            `fileName` varchar(255) NOT NULL,\n            `scanMD5` varchar(50) NOT NULL,\n            `officialMD5` varchar(50) DEFAULT NULL,\n            `updatedMD5` varchar(50) DEFAULT NULL,\n            `fileFound` tinyint(1) NOT NULL DEFAULT 1,\n            `ignoredFile` tinyint(1) NOT NULL DEFAULT 0,\n            `newFile` tinyint(1) NOT NULL DEFAULT 0,\n            `modifiedFile` tinyint(1) NOT NULL DEFAULT 0,\n            `dateFound` datetime NOT NULL,\n            `type` varchar(1) NOT NULL DEFAULT 'N',\n            `slug` varchar(100) NOT NULL,\n            `ver` varchar(20) NOT NULL,\n            PRIMARY KEY (`fileName`),\n            UNIQUE KEY `fileName` (`fileName`),\n            KEY `ID` (`ID`)\n          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $wpdb->query( $sql );
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (\n           `ID` int(11) NOT NULL AUTO_INCREMENT,\n           `Name` varchar(50) NOT NULL,            \n           `Type` varchar(1) NOT NULL,\n           `Domain` varchar(50) NOT NULL,\n           `Version` varchar(25) NOT NULL,\n           `found` tinyint(1) NOT NULL DEFAULT 0,\n           PRIMARY KEY (`ID`),\n           UNIQUE KEY `ID` (`ID`)\n         ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $wpdb->query( $sql );
    }

}