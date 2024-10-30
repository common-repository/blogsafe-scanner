<?php

class Blogsafe_Scanner_Plus_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private  $plugin_name ;
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private  $version ;
    function blogafe_scanner_fs_custom_connect_message_on_update(
        $message,
        $user_first_name,
        $plugin_title,
        $user_login,
        $site_link,
        $freemius_link
    )
    {
        return sprintf(
            __( 'Hey %1$s' ) . ',<br>' . __( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'blogsafe-scanner' ),
            $user_first_name,
            '<b>' . $plugin_title . '</b>',
            '<b>' . $user_login . '</b>',
            $site_link,
            $freemius_link
        );
    }
    
    public function BSScanner_plugin_main_menu()
    {
        $icon_url = plugin_dir_url( __FILE__ ) . 'images/BSScannerIcon.png';
        add_menu_page(
            'BlogSafe',
            "BlogSafe Scanner",
            'edit_posts',
            'BlogSafeScanner',
            array( $this, 'BSScanner_main_menu' ),
            $icon_url
        );
    }
    
    public function BSScanner_main_menu()
    {
        include_once 'BlogSafe_Scanner_Menu.php';
        new BSScanner_Main_Menu();
    }
    
    function BSScan_do_output_buffer()
    {
        global  $wpdb ;
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $count = $wpdb->get_var( "SELECT COUNT(*) from {$table_name}" );
        if ( $count == 0 ) {
            update_option( 'BSScanner_FirstScan', 'yes', 'no' );
        }
        //        $buffers = ob_get_status(true);
        //        if (count($buffers) > 1) {
        //            for ($i = 0; $i < count($buffers) - 1; $i++) {
        //                ob_end_flush();
        //            }
        //        }
        //        ob_start();
        //        ob_implicit_flush(true);
    }
    
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        blogafe_scanner_fs()->add_filter(
            'connect_message_on_update',
            'blogafe_scanner_fs_custom_connect_message_on_update',
            10,
            6
        );
        add_action( 'admin_menu', array( $this, 'BSScanner_plugin_main_menu' ) );
        add_action( 'init', array( $this, 'BSScan_do_output_buffer' ) );
        blogafe_scanner_fs()->override_i18n( array(
            'yee-haw'  => __( "", 'BSScanner' ),
            'woot'     => __( '', 'BSScanner' ),
            'right-on' => __( "", 'BSScanner' ),
            'hey'      => __( "", 'BSScanner' ),
        ) );
        //fix for ob_end_flush bug
        remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
        add_action( 'shutdown', function () {
            while ( @ob_end_flush() ) {
            }
        } );
    }
    
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Blogsafe_Scanner_Plus_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Blogsafe_Scanner_Plus_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/blogsafe-scanner-plus-admin.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Blogsafe_Scanner_Plus_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Blogsafe_Scanner_Plus_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/blogsafe-scanner-plus-admin.js',
            array( 'jquery' ),
            $this->version,
            false
        );
    }

}