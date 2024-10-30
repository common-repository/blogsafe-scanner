<?php

/**
 * The plugin bootstrap file
 *
 * BlogSafe Scanner is licensed at GPL-2.0+
 * BlogSafe Scanner Plus is copyright 2021 BlogSafe.org, all rights reserved.
 *
 * @link              https://www.blogsafe.org
 * @since             1.0.0
 * @package           Blogsafe_Scanner
 *
 * @wordpress-plugin
 * Plugin Name:       BlogSafe Scanner
 * Plugin URI:        https://www.blogsafe.org
 * Description:       BlogSafe Scanner is a lightweight file scanner designed to notify you when any files are modified or uploaded to your server.
 * Version:           1.1.5
 * Author:            BlogSafe.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       blogsafe-scanner
 * Domain Path:       /languages
 *
 * 
 * 
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'blogafe_scanner_fs' ) ) {
    blogafe_scanner_fs()->set_basename( false, __FILE__ );
} else {
    function blogafe_scanner_fs()
    {
        global  $blogafe_scanner_fs ;
        
        if ( !isset( $blogafe_scanner_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __FILE__ ) . '/freemius/start.php';
            $blogafe_scanner_fs = fs_dynamic_init( array(
                'id'             => '7167',
                'slug'           => 'blogsafe-scanner',
                'premium_slug'   => 'blogsafe-scanner-plus',
                'type'           => 'plugin',
                'public_key'     => 'pk_3668b19021c2af2b0b73ac6b4ce76',
                'is_premium'     => false,
                'premium_suffix' => 'Plus',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'    => 'BlogSafeScanner',
                'support' => false,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $blogafe_scanner_fs;
    }
    
    // Init Freemius.
    blogafe_scanner_fs();
    // Signal that SDK was initiated.
    do_action( 'blogafe_scanner_fs_loaded' );
}

@define( "BLOGSAFE_WP_OFFICIAL_URL", 'https://api.wordpress.org/core/checksums/1.0/' );
@define( "BLOGSAFE_WP_OFFICIAL_PLUGIN_URL", 'https://downloads.wordpress.org/plugin-checksums/' );
@define( "BLOGSAFE_WP_OFFICIAL_THEME_URL", 'https://downloads.wordpress.org/theme/' );
@define( "BLOGSAFE_WP_BLOGSAFE_REPAIR_URL", 'http://core.svn.wordpress.org/tags/' );
@define( 'BLOGSAFE_API_URL', 'https://blogsafe.org/api/scannerapi/scan_check/' );
@define( 'BLOGSAFE_THREAT_URL', 'https://blogsafe.org/threat/' );
@define( 'BLOGSAFE_HELP_URL', 'https://blogsafe.org/blogscanner-help/' );
@define( 'BLOGSAFESCAN_DBVER', '1.1' );
@define( 'BLOGSAFE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
@define( 'BLOGSAFE_PLUGIN_FILE', __FILE__ );
@define( 'BLOGSAFE_SCANNER_VERSION', '1.1.5' );

if ( !function_exists( 'activate_blogsafe_scanner_plus' ) ) {
    @define( 'BLOGSAFE_SCANNER_NAME', 'BlogSafe Scanner' );
    function activate_blogsafe_scanner_plus( $network_wide )
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-blogsafe-scanner-plus-activator.php';
        Blogsafe_Scanner_Plus_Activator::activate( $network_wide );
    }
    
    function deactivate_blogsafe_scanner_plus()
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-blogsafe-scanner-plus-deactivator.php';
        Blogsafe_Scanner_Plus_Deactivator::deactivate();
    }
    
    blogafe_scanner_fs()->add_action( 'after_uninstall', 'blogafe_scanner_fs_uninstall_cleanup' );
    register_activation_hook( __FILE__, 'activate_blogsafe_scanner_plus' );
    register_deactivation_hook( __FILE__, 'deactivate_blogsafe_scanner_plus' );
    require plugin_dir_path( __FILE__ ) . 'includes/class-blogsafe-scanner-plus.php';
    function run_blogsafe_scanner_plus()
    {
        $plugin = new Blogsafe_Scanner_Plus();
        $plugin->run();
    }
    
    run_blogsafe_scanner_plus();
} else {
    wp_die( __( 'You need to deactivate the other version of BlogSafe Scanner before activating this one.', 'BSScanner' ) );
}
