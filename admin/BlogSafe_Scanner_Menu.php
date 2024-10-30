<?php

include_once "BlogSafe_Scanner_Utils.php";
include_once "BlogSafe_Scanner.php";
class BSScanner_Main_Menu
{
    public function BSScanner_Load_Menu_Buttons( $args )
    {
        foreach ( $args as $arg ) {
            echo  '<button class="button-primary" type="submit" name="action" value="' . $arg['value'] . '">' . $arg['text'] . '</button>&nbsp;' ;
        }
    }
    
    public function BSScanner_Load_Sub_Menu_Buttons( $args )
    {
        foreach ( $args as $arg ) {
            echo  '<button class="button" type="submit" name="action" value="' . $arg['value'] . '">' . $arg['text'] . '</button>&nbsp;' ;
        }
    }
    
    //assign the menu actions
    public function BSScanner_Set_Menu_Actions( $args )
    {
        foreach ( $args as $arg ) {
            add_action( 'BSScanner_Action_' . $arg['action'], array( $this, $arg["callback"] ) );
        }
    }
    
    public function BSScanner_Show_Menu( $submenu = NULL )
    {
        echo  '<div class="wrap">' ;
        echo  '<table width="100%">' ;
        echo  '<tr><td width="100px"><a href="http://www.blogsafe.org" target="_blank"><img src="' . plugin_dir_url( __FILE__ ) . 'images/BSScanner772x250.png" width="300px" align="bottom" hspace="3"/></a></td>' ;
        echo  '<td><h1>' . "BlogSafe Scanner </h1>" . 'Version ' . BLOGSAFE_SCANNER_VERSION ;
        echo  '<p>' . __( 'For more information and instructions please visit our website at: ', 'BSScanner' ) . '<a href="http://www.blogsafe.org" target="_blank">http://www.blogsafe.org</a></td>' ;
        echo  '<td width="100px" align="right"><a href="' . admin_url() . 'admin.php?page=BlogSafeScanner-pricing"><img src="' . plugin_dir_url( __FILE__ ) . 'images/BSS_Subscribe_Button.png" width="300px" align="bottom" hspace="3"/></a>' ;
        echo  '</tr></table><hr />' ;
        echo  '<form action="" method="get">' ;
        do_action( 'BSScanner_Load_Menu_Buttons', $this->main_menu_buttons );
        echo  '<button class="button-primary" onclick=" window.open(\'' . BLOGSAFE_HELP_URL . '\',\'_blank\')" value="Help">' . __( 'Help', 'BSScanner' ) . '</button>&nbsp;' ;
        
        if ( $submenu != NULL ) {
            echo  '<hr>' ;
            do_action( 'BSScanner_Load_Sub_Menu_Buttons', $submenu );
        }
        
        echo  '<input name="page" type="hidden" value="BlogSafeScanner" />' ;
        $nonce = wp_create_nonce( 'BSSnonce' );
        echo  '<input name="BSSnonce" type="hidden" value="' . $nonce . '" />' ;
        echo  '</form>' ;
        if ( $submenu != -1 ) {
            echo  '<hr>' ;
        }
        $BSScanner_errormsg = get_option( 'BSScanner_error_message', 'none' );
        if ( $BSScanner_errormsg != 'none' ) {
            $this->BSScanner_Show_Message( $BSScanner_errormsg );
        }
        @ob_flush();
        @flush();
    }
    
    private function BSScanner_Show_Post_Form()
    {
        echo  '<div id="col-container" class="wp-clearfix">
                    <div id="col-left">
                    <div class="col-wrap">' . '<h1>' . __( 'System Scan', 'BSScanner' ) . '</h1><p>' . __( 'BlogSafe\'s Scanner records the checksums of all of the files on your web server.', 'BSScanner' ) ;
        echo  '<br>' ;
        echo  __( "It then compares those checksums to official checksums as well as checksums", 'BSScanner' ) ;
        echo  '<br>' ;
        echo  __( "from previous scans and looks for discrepancies.", 'BSScanner' ) . '</p>' ;
        echo  '<hr>' ;
        
        if ( get_option( 'BSScanner_FirstScan' ) == 'yes' ) {
            echo  '<p style="color:red;">' . __( 'A full system scan has not been run.<br>Prior to being able to perform quick scans, a full scan must be performed.', 'BSScanner' ) . '</p>' ;
        } else {
            
            if ( $this->versioning ) {
                echo  '<p style="color:red;">' . __( 'BlogSafe Scanner has detected a new version of WordPress has been installed.<br><br>Please run a full scan now.', 'BSScanner' ) . '</p>' ;
            } elseif ( $this->plugincount || $this->themecount ) {
                echo  '<p style="color:red;">' . __( 'BlogSafe Scanner has detected that new theme or plugin files have been installed or a theme or plugin version has changed.<br><br>Please run a full scan now.', 'BSScanner' ) . '</p>' ;
            } elseif ( $this->ignorechanged ) {
                echo  '<p style="color:red;">' . __( 'The list of ignored files has changed.<br><br>Please run a full scan now.', 'BSScanner' ) . '</p>' ;
            }
        
        }
        
        @ob_flush();
        @flush();
    }
    
    public function BSScanner_Show_Message( $message )
    {
        $msg = unserialize( $message );
        switch ( $msg['type'] ) {
            case 0:
                echo  '<div class="notice notice-error is-dismissable"><p>' . $msg['message'] . '</p> </div>' ;
                break;
            case 1:
                echo  '<div class="notice notice-success is-dismissible"><p>' . $msg['message'] . '</p></div>' ;
                break;
            case 2:
                echo  '<div class="notice notice-warning is-dismissible"><p>' . $msg['message'] . '</p></div>' ;
                break;
        }
        update_option( 'BSScanner_error_message', 'none' );
    }
    
    private function BSScanner_Show_Default_Menu()
    {
        $this->BSScanner_Show_Menu( $this->scanner_submenu );
        $this->BSScanner_Show_Post_Form();
        if ( !$this->ignorechanged ) {
            $this->BSScanner_Echo_Report();
        }
    }
    
    public function BSScanner_Action_Scanner()
    {
        $this->BSScanner_Show_Menu( $this->scanner_submenu );
        $this->BSScanner_Show_Post_Form();
        if ( !$this->ignorechanged ) {
            $this->BSScanner_Echo_Report();
        }
    }
    
    public function BSScanner_Action_Ignored_Files()
    {
        $this->BSScanner_Show_Menu();
        include_once 'BlogSafe_Scanner_Ignores.php';
        BSScanner_Render_Ignores_Page();
    }
    
    public function BSScanner_Action_removeignore()
    {
        include_once 'BlogSafe_Scanner_Ignores.php';
        
        if ( isset( $_GET['ID'] ) ) {
            $toggle = new BlogSafe_Scanner_Utils();
            
            if ( is_array( $_GET['ID'] ) ) {
                foreach ( $_GET['ID'] as $id ) {
                    $toggle->toggleIgnore( $id );
                }
            } else {
                $toggle->toggleIgnore( $_GET['ID'] );
            }
            
            update_option( "BlogSafe_Scanner_Ignore_Changed", true );
        }
        
        $this->BSScanner_Show_Menu();
        BSScanner_Render_Ignores_Page();
    }
    
    public function BSScanner_Action_Full_Scan()
    {
        $this->BSScanner_Show_Menu( $this->scanner_submenu );
        $this->BSScanner_Show_Post_Form();
        $scanner = new BlogSafe_Scanner( FALSE );
        $scanner->PrepareScan();
    }
    
    public function BSScanner_Action_Quick_Scan()
    {
        $this->BSScanner_Show_Menu( $this->scanner_submenu );
        $this->BSScanner_Show_Post_Form();
        $scanner = new BlogSafe_Scanner( FALSE );
        $scanner->PrepareScan();
    }
    
    public function BSScanner_Action_Settings()
    {
    }
    
    public function BSScanner_Action_UpdateSettings()
    {
    }
    
    public function BSScanner_Action_OptIn()
    {
        $this->BSScanner_Show_Menu();
        include_once 'BlogSafe_Scanner_Opt-In.php';
        $optin = new BlogSafe_Scanner_Opt_In();
        $optin->Show_Opt_In();
    }
    
    public function BSScanner_Action_UpdateOptIn()
    {
        include_once 'BlogSafe_Scanner_Opt-In.php';
        $optin = new BlogSafe_Scanner_Opt_In();
        $optin->Set_Opt_In();
        $this->BSScanner_Show_Menu();
        $optin->Show_Opt_In();
    }
    
    public function BSScanner_Action_UpdateNewfiles()
    {
        $this->BSScanner_Show_Menu();
        $this->BSScanner_Show_Post_Form();
        $scanner = new BlogSafe_Scanner( FALSE );
        $scanner->PrepareScan();
        $this->BSScanner_Echo_Report();
    }
    
    public function BSScanner_Action_UpdateScan()
    {
        $this->BSScanner_Show_Menu();
        $this->BSScanner_Show_Post_Form();
        $scanner = new BlogSafe_Scanner( FALSE );
        $scanner->PrepareScan();
        $this->BSScanner_Echo_Report();
    }
    
    private function BSScanner_Echo_Report()
    {
        echo  get_option( "BlogSafe_Scanner_Report" ) ;
        $scanner = new BlogSafe_Scanner( FALSE );
        $closing = $scanner->Build_Report_Nonce();
        echo  $closing ;
    }
    
    public function __construct()
    {
        $BSUtils = new BlogSafe_Scanner_Utils();
        $this->plugincount = $BSUtils->getPlugins();
        $this->themecount = $BSUtils->getThemes();
        $this->versioning = $BSUtils->getVersion();
        $this->ignorechanged = get_option( "BlogSafe_Scanner_Ignore_Changed", false );
        //set up the menu actions
        $this->menu_actions = array(
            array(
            'action'   => 'Scanner',
            'callback' => 'BSScanner_Action_Scanner',
            10,
            1,
        ),
            array(
            'action'   => 'Ignored_Files',
            'callback' => 'BSScanner_Action_Ignored_Files',
            10,
            1,
        ),
            array(
            'action'   => 'Full_Scan',
            'callback' => 'BSScanner_Action_Full_Scan',
            10,
            1,
        ),
            array(
            'action'   => 'Quick_Scan',
            'callback' => 'BSScanner_Action_Quick_Scan',
            10,
            1,
        ),
            array(
            'action'   => 'OptIn',
            'callback' => 'BSScanner_Action_OptIn',
            10,
            1,
        ),
            array(
            'action'   => 'UpdateOptIn',
            'callback' => 'BSScanner_Action_UpdateOptIn',
            10,
            1,
        ),
            array(
            'action'   => 'removeignore',
            'callback' => 'BSScanner_Action_removeignore',
            10,
            1,
        ),
            array(
            'action'   => 'UpdateNewfiles',
            'callback' => 'BSScanner_Action_UpdateNewfiles',
            10,
            1,
        ),
            array(
            'action'   => 'UpdateScan',
            'callback' => 'BSScanner_Action_UpdateScan',
            10,
            1,
        ),
            array(
            'action'   => 'UpdateSettings',
            'callback' => 'BSScanner_Action_UpdateSettings',
            10,
            1,
        )
        );
        $this->main_menu_buttons = array( array(
            'value' => 'Scanner',
            'text'  => __( 'Scanner', 'BSScanner' ),
        ), array(
            'value' => 'Ignored_Files',
            'text'  => __( 'Ignored Files', 'BSScanner' ),
        ) );
        $this->main_menu_buttons[] = array(
            'value' => 'OptIn',
            'text'  => __( 'Opt-In', 'BSScanner' ),
        );
        $this->scanner_submenu = array( array(
            'value' => 'Full_Scan',
            'text'  => __( 'Full Scan', 'BSScanner' ),
        ) );
        if ( get_option( 'BSScanner_FirstScan' ) == 'no' ) {
            if ( !$this->themecount && !$this->plugincount && !$this->versioning && !$this->ignorechanged ) {
                $this->scanner_submenu[] = array(
                    'value' => 'Quick_Scan',
                    'text'  => __( 'Quick Scan', 'BSScanner' ),
                );
            }
        }
        add_action( 'BSScanner_Load_Menu_Buttons', array( $this, 'BSScanner_Load_Menu_Buttons' ) );
        add_action( 'BSScanner_Load_Sub_Menu_Buttons', array( $this, 'BSScanner_Load_Sub_Menu_Buttons' ) );
        add_action( 'BSScanner_Set_Menu_Actions', array( $this, 'BSScanner_Set_Menu_Actions' ) );
        do_action( 'BSScanner_Set_Menu_Actions', $this->menu_actions );
        //process the action request
        
        if ( !empty($_GET['action']) ) {
            $nonce = sanitize_text_field( $_REQUEST['BSSnonce'] );
            if ( !wp_verify_nonce( $nonce, 'BSSnonce' ) ) {
                die( __( 'Security Check', 'BSSnonce' ) );
            }
            
            if ( method_exists( $this, 'BSScanner_Action_' . $_GET['action'] ) ) {
                do_action( 'BSScanner_Action_' . $_GET['action'] );
            } else {
                $this->BSScanner_Show_Default_Menu();
            }
        
        } else {
            $this->BSScanner_Show_Default_Menu();
        }
    
    }

}