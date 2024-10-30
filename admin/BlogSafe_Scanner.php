<?php

include_once "BlogSafe_Scanner_Official_Handler.php";
include_once "BlogSafe_Scanner_Theme_Handler.php";
include_once "BlogSafe_Scanner_Plugin_Handler.php";
class BlogSafe_Scanner_Official_Filter
{
    private  $name ;
    function __construct( $name )
    {
        $this->name = $name;
    }
    
    function findDupe( $i )
    {
        return $i == $this->name;
    }

}
class BlogSafe_Scanner
{
    public function __construct( $silent, $scheduled = NULL )
    {
        global  $wpdb ;
        if ( !function_exists( 'get_home_path' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $this->OS = 'l';
        if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
            $this->OS = 'w';
        }
        $this->scheduled = $scheduled;
        $this->silent = $silent;
        $this->path = get_home_path();
        $this->dt = microtime( true );
        $BSUtils = new BlogSafe_Scanner_Utils();
        $this->firstScan = $BSUtils->CheckFirst();
        $this->newVersion = $BSUtils->getVersion();
        $this->plugincount = $BSUtils->getPlugins();
        $this->themecount = $BSUtils->getThemes();
        $this->multisite = $BSUtils->getMultiSite();
        $this->sums = array();
        $this->report = '';
        $this->found_files = array();
        $this->vulnerabilities = array();
        $this->abandoned_themes = array();
        $this->abandoned_plugins = array();
        $this->response_error = false;
    }
    
    private function Show_Notice( $type )
    {
        return __( '<h3>NOTICE: Possible abandoned ' . $type . '</h3>', 'BSScanner' ) . __( 'BlogSafe Scanner has detected a ' . $type . ' that has not been updated in more than 12 months.  This ' . $type . ' may have been abandoned by it\'s original author.', 'BSScanner' ) . '<br><br><div><table cellpadding="0" width="100%" id="bssreport" cell style="border-spacing: 0px; border-color: grey; border-collapse: collapse;">' . '<tr><td>' . __( '<strong>Name</strong>', 'BSScanner' ) . '</td><td>' . __( '<strong>Last Updated</strong>', 'BSScanner' ) . '</td></tr>';
    }
    
    public function Build_Report_Nonce()
    {
        $nonce = wp_create_nonce( 'BSSnonce' );
        $report = '<input name="BSSnonce" type="hidden" value="' . $nonce . '" />' . '</form>' . '</div><!-- /col-right --></div><!-- /col-container --></div><!-- /wrap -->';
        return $report;
    }
    
    public function Build_Report( $rebuild = false )
    {
        $nf = get_option( "BlogSafe_Scanner_Report_NF", false );
        $mo = get_option( "BlogSafe_Scanner_Report_MO", false );
        
        if ( $rebuild ) {
            $this->vulnerabilities = unserialize( get_option( "BlogSafe_Scanner_Report_Vulnerabilities" ) );
            $this->abandoned_themes = unserialize( get_option( "BlogSafe_Scanner_Report_Abandoned_Themes" ) );
            $this->abandoned_plugins = unserialize( get_option( "BlogSafe_Scanner_Report_Abandoned_Plugins" ) );
        }
        
        $this->report = '';
        $this->report .= '</div>
                </div><!-- /col-left -->
                <div id="col-right">
                <div class="col-wrap" style="background: white; margin: 5px; padding: 5px;">' . '<h1>' . __( 'BlogSafe Scanner Report', 'BSScanner' ) . '</h1>' . 'Scan started: ' . gmdate( "Y-m-d H:i:s" ) . ' UTC';
        
        if ( $this->response_error ) {
            $this->report .= '<h3>' . __( 'Unable to retrievie data from the BlogSafe.org server.', 'BSScanner' ) . '</h3>';
        } else {
            
            if ( count( $this->vulnerabilities ) == 0 ) {
                $this->report .= '<h4>No vulnerabilities found.</h4><hr>';
            } else {
                $this->report .= __( '<h1 style="color:red;">WARNING: Vulnerability found!</h1>', 'BSScanner' ) . '<table cellpadding="10" width="100%" id="bssreport" cell style="border-spacing: 0px; border-color: grey; border-collapse: collapse;">';
                $this->report .= '<tr><td>' . __( '<strong>Name</strong>', 'BSScanner' ) . '</td><td>' . __( '<strong>Severity</strong>', 'BSScanner' ) . '</td><td>' . __( '<strong>Description</strong>', 'BSScanner' ) . '</td></tr>';
                foreach ( $this->vulnerabilities as $vuln ) {
                    $this->report .= '<tr><td>' . __( $vuln[0], 'BSScanner' ) . '</td><td style="color:red;">' . __( $vuln[1], 'BSScanner' ) . '</td><td>' . __( $vuln[2], 'BSScanner' ) . '</td></tr>';
                }
                $this->report .= '</table><br>';
            }
            
            
            if ( count( $this->abandoned_themes ) == 0 ) {
                $this->report .= '<h4>No abandoned themes.</h4><hr>';
            } else {
                $this->report .= $this->Show_Notice( 'theme' );
                foreach ( $this->abandoned_themes as $theme ) {
                    $this->report .= '<tr><td style="width:30%">' . $theme[0] . '</td><td style="width:70%">' . $theme[1] . '</td></tr>';
                }
                $this->report .= '</table><br>';
            }
            
            
            if ( count( $this->abandoned_plugins ) == 0 ) {
                $this->report .= '<h4>No abandoned plugins.</h4><hr>';
            } else {
                $this->report .= $this->Show_Notice( 'plugin' );
                foreach ( $this->abandoned_plugins as $plugin ) {
                    $this->report .= '<tr><td style="width:30%">' . $plugin[0] . '</td><td style="width:70%">' . $plugin[1] . '</td></tr>';
                }
                $this->report .= '</table><br>';
            }
        
        }
        
        $this->report .= '<h3>' . __( 'File Scan Results', 'BSScanner' ) . '</h3>' . '<form action="" method="get">' . '<table id="bssreport">';
        
        if ( count( $this->found_files ) <= 1 ) {
            $this->report .= '<tr style="background: none; border-bottom: none"><td colspan=4><font color="green">' . __( "No new, modified or missing files found", 'BSScanner' ) . '</font></td></tr>';
        } else {
            foreach ( $this->found_files as $file ) {
                $this->report .= '<tr><td>' . $file[0] . '</td>' . "<td>{$file[1]}</td><td>{$file[3]}</td>";
                
                if ( !empty($file[2]) ) {
                    $this->report .= '<td><input type="checkbox" name="update[' . $file[2] . ']" value="' . $file[2] . '">' . __( 'Update', 'BSScanner' ) . '</td>
                          <td><input type="checkbox" name="ignore[' . $file[2] . ']" value="' . $file[2] . '">' . __( 'Ignore', 'BSScanner' ) . '</td>';
                } else {
                    $this->report .= '<td><strong>' . __( 'Update', 'BSScanner' ) . '</strong></td><td><strong>' . __( 'Ignore', 'BSScanner' ) . '</strong></td>';
                }
                
                echo  '</tr>' ;
            }
            $this->report .= '<tr style="background: none; border-bottom: none"><td colspan = 4>&nbsp;</td></tr><tr style="background: none; border-bottom: none"><td colspan=3>' . '<input name="page" type="hidden" value="BlogSafeScanner" />' . '<button class="button-primary" type="submit" name="action" value="UpdateScan">' . __( 'Submit Manual Update', 'BSScanner' ) . '</button>&nbsp;';
            if ( $nf ) {
                $this->report .= '<button class="button-primary" type="submit" name="action" value="UpdateNewfiles">' . __( 'Update All New Files', 'BSScanner' ) . '</button>&nbsp;';
            }
            if ( $mo ) {
                $this->report .= "<tr style='background: none; border-bottom: none'><td colspan=4><h1><font color='red'>" . __( "Warning!", 'BSScanner' ) . "</font></h1>" . __( "Modified official files have been detected!", 'BSScanner' ) . "<br><br>" . __( "If you are aware of this modification, you can safely click the update or ignore button.", 'BSScanner' ) . '<br>' . __( "If you are unaware of this modification, make sure that the plugin or theme is the latest version. If not, update it and run a full scan again.", 'BSScanner' ) . '<br>' . __( "If it still shows up as modified, try deleting the theme or plugin and reinstalling it again from the WordPress website.", 'BSScanner' ) . "<br><br>" . __( "Warning: by deleting and reinstalling a plugin or theme, any custom settings will likely be removed.", 'BSScanner' ) . "</td></tr>";
            }
            $this->report .= '</table>';
        }
    
    }
    
    private function ShowOutput(
        $which,
        $value = '',
        $value2 = '',
        $value3 = '',
        $value4 = ''
    )
    {
        if ( $this->silent ) {
            return;
        }
        switch ( $which ) {
            case 1:
                echo  __( "Updates detected since the last scan. Performing full scan.", 'BSScanner' ) . '<br>' ;
            case 2:
                echo  __( 'Retrieving official checksums...', 'BSScanner' ) . "<br>" . '<div id="finder"></div>' . "<script>\r\n                    const fileelement = document.querySelector('#finder');\r\n                    </script>" ;
                @ob_flush();
                @flush();
                break;
            case 3:
                echo  '<script>fileelement.innerHTML = "<div>' . $value . '</div>";</script>' ;
                @ob_flush();
                @flush();
                break;
            case 4:
                echo  __( 'Performing Scan', 'BSScanner' ) . "<br>" . '<div id="scanner"></div>' . "<script>\r\n                    const element = document.querySelector('#scanner');\r\n                    </script>" ;
                @ob_flush();
                @flush();
                break;
            case 5:
                echo  '<script>element.innerHTML = "<div>' . $value . '</div>";</script>' ;
                @ob_flush();
                @flush();
                break;
            case 6:
                echo  "<script>\r\n                element.innerHTML = `<div>" . __( 'Scan Complete. Files Scanned: ' . $value2 . '  Total Time: ', 'BSScanner' ) . $value . " sec.</div>`;\r\n                </script>" ;
                break;
            case 15:
                echo  '<font color="red">' . __( "There was an error " . $value . ".", 'BSScanner' ) . '</font>' ;
                break;
            case 16:
                echo  $value ;
                return;
            case 25:
                echo  '<tr><td>' . __( 'No files selected to update.', 'BSScanner' ) . '</td></tr>' ;
                break;
        }
    }
    
    private function Update_Newfiles()
    {
        global  $wpdb ;
        echo  '<h4>' . __( 'Updating... ', 'BSScanner' ) . '</h4>' ;
        echo  '<table cellpadding="0" width="100%" id="bssreport" cell style="border-spacing: 0px; border-color: grey; border-collapse: collapse;">' ;
        $this->found_files = unserialize( get_option( "BlogSafe_Scanner_Report_Files" ) );
        $this->found_files = array_values( $this->found_files );
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $sql = "Select ID, fileName from {$table_name} where newFile = 1";
        
        if ( $results = $wpdb->get_results( $sql ) ) {
            if ( count( $results ) == 0 ) {
                $this->ShowOutput( 25 );
            }
            foreach ( $results as $result ) {
                $key = array_search( $result->fileName, array_column( $this->found_files, 1 ) );
                if ( $key !== false ) {
                    unset( $this->found_files[$key] );
                }
                $this->found_files = array_values( $this->found_files );
                $sql = "Update {$table_name} set newFile = 0 where ID = {$result->ID}";
                echo  '<tr><td>' ;
                $wpdb->query( $sql );
                $this->ShowOutput( 16, __( 'Updated ', 'BSScanner' ) . $result->fileName . '<br>' );
                echo  '</td></tr>' ;
            }
        }
        
        echo  '</table>' ;
        $this->Build_Report( true );
        update_option( "BlogSafe_Scanner_Report_Files", serialize( $this->found_files ) );
        update_option( "BlogSafe_Scanner_Report", $this->report );
    }
    
    private function Update_Scan()
    {
        global  $wpdb ;
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $this->found_files = unserialize( get_option( "BlogSafe_Scanner_Report_Files" ) );
        $this->found_files = array_values( $this->found_files );
        echo  '<h4>' . __( 'Updating... ', 'BSScanner' ) . '</h4>' ;
        echo  '<table cellpadding="0" width="100%" id="bssreport" cell style="border-spacing: 0px; border-color: grey; border-collapse: collapse;">' ;
        $count = 0;
        if ( isset( $_GET['ignore'] ) ) {
            foreach ( $_GET['ignore'] as $ignore ) {
                $count++;
                $ID = sanitize_text_field( $_GET['ignore'][$ignore] );
                $sql = "Select fileName from {$table_name} where ID = %s";
                $results = $wpdb->get_row( $wpdb->prepare( $sql, $ID ) );
                $key = array_search( $results->fileName, array_column( $this->found_files, 1 ) );
                if ( $key !== false ) {
                    unset( $this->found_files[$key] );
                }
                $this->found_files = array_values( $this->found_files );
                $file_path = ABSPATH . $results->fileName;
                
                if ( !file_exists( $file_path ) ) {
                    $sql = "Delete from {$table_name} where ID={$ID}";
                    $wpdb->query( $sql );
                    echo  '<tr><td>' ;
                    $this->ShowOutput( 16, __( 'Removing ', 'BSScanner' ) . $file_path );
                    echo  '</td></tr>' ;
                } else {
                    $sql = "UPDATE {$table_name} SET ignoredFile=1 WHERE ID={$ID}";
                    $wpdb->query( $sql );
                    echo  '<tr><td>' ;
                    $this->ShowOutput( 16, __( 'Ignoring: ', 'BSScanner' ) . $file_path );
                    echo  '</td></tr>' ;
                }
            
            }
        }
        if ( isset( $_GET['update'] ) ) {
            foreach ( $_GET['update'] as $update ) {
                $count++;
                $ID = sanitize_text_field( $_GET['update'][$update] );
                $sql = "Select fileName,type,ignoredFile from {$table_name} where ID = %s";
                $results = $wpdb->get_row( $wpdb->prepare( $sql, $ID ) );
                
                if ( $results->ignoredFile == 0 ) {
                    $file_path = ABSPATH . $results->fileName;
                    
                    if ( !file_exists( $file_path ) ) {
                        echo  '<tr><td>' ;
                        $this->ShowOutput( 16, __( 'Not found: ', 'BSScanner' ) . $file_path );
                        echo  '</td></tr>' ;
                    } else {
                        $key = array_search( $results->fileName, array_column( $this->found_files, 1 ) );
                        if ( $key !== false ) {
                            unset( $this->found_files[$key] );
                        }
                        $this->found_files = array_values( $this->found_files );
                        $checksum = md5_file( $file_path );
                        $sql = "Update {$table_name} set scanMD5 = '{$checksum}', updatedMD5 = '{$checksum}', newFile = 0, modifiedFile = 1 where ID = {$ID}";
                        $wpdb->query( $sql );
                        echo  '<tr><td>' ;
                        $this->ShowOutput( 16, __( 'Updated ', 'BSScanner' ) . $file_path );
                        echo  '</td></tr>' ;
                    }
                
                }
            
            }
        }
        if ( $count == 0 ) {
            $this->ShowOutput( 25 );
        }
        echo  '</table>' ;
        $this->Build_Report( true );
        update_option( "BlogSafe_Scanner_Report_Files", serialize( $this->found_files ) );
        update_option( "BlogSafe_Scanner_Report", $this->report );
    }
    
    public function PrepareScan()
    {
        // update with user selected
        
        if ( @$_GET['action'] == 'UpdateScan' ) {
            $this->Update_Scan();
            return;
        }
        
        // update all new files
        
        if ( @$_GET['action'] == 'UpdateNewfiles' ) {
            $this->Update_Newfiles();
            return;
        }
        
        // not updating, run the scan
        return $this->Do_Scan();
    }
    
    private function search_subkey(
        $array,
        $key,
        $returnkey,
        $value
    )
    {
        foreach ( $array as $ar ) {
            if ( $domain = array_search( $value, $ar ) ) {
                return $ar[$returnkey];
            }
        }
    }
    
    private function Check_Threat()
    {
        global  $wpdb ;
        $msg = '';
        $table_name = $wpdb->base_prefix . "BS_Scanner_Data";
        $SQL = "Select Domain, Version from {$table_name}";
        $threatlist = $wpdb->get_results( $SQL );
        $threatlist = base64_encode( json_encode( $threatlist ) );
        $response = wp_remote_post( BLOGSAFE_API_URL, array(
            'method'      => 'POST',
            'timeout'     => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => array(
            'data' => $threatlist,
        ),
            'cookies'     => array(),
        ) );
        
        if ( is_wp_error( $response ) ) {
            $this->response_error = true;
            return;
        }
        
        $response = json_decode( $response['body'] );
        $vulnfound = false;
        
        if ( $response->status == 'ok' ) {
            $pluginlist = get_plugins();
            $themelist = wp_get_themes();
            
            if ( count( $response->threat ) == 0 ) {
                $this->vulnerabilities = array();
            } else {
                foreach ( $response->threat as $threat ) {
                    $name = $threat->name;
                    foreach ( $themelist as $thistheme ) {
                        if ( $thistheme->get( 'TextDomain' ) == $threat->name ) {
                            $name = $thistheme->get( 'Name' );
                        }
                    }
                    foreach ( $pluginlist as $key => $value ) {
                        if ( $value["TextDomain"] == $threat->name ) {
                            $name = $value['Name'];
                        }
                    }
                    
                    if ( !$this->silent ) {
                        $description = $threat->description;
                        $description .= '<br><a href=' . BLOGSAFE_THREAT_URL . '?ID=' . $threat->ID . '  target=”_blank”>' . __( 'More Info', 'BSScanner' ) . '</a>';
                        $thisvuln = array( $name, $threat->severity, $description );
                        array_push( $this->vulnerabilities, $thisvuln );
                    } else {
                        
                        if ( !$vulnfound ) {
                            $msg .= __( 'VULNERABILITY FOUND! BlogSafe Scanner has found a vunlerability on your site. Please log in and perform a full scan for more details.', 'BSScanner' ) . "\n\n";
                            $vulnfound = true;
                        }
                    
                    }
                
                }
            }
            
            
            if ( count( $response->abandoned_themes ) == 0 ) {
                $this->abandoned_themes = array();
            } else {
                foreach ( $response->abandoned_themes as $theme ) {
                    $name = $theme[0]->name;
                    foreach ( $themelist as $thistheme ) {
                        if ( $thistheme->get( 'TextDomain' ) == $theme[0]->name ) {
                            $name = $thistheme->get( 'Name' );
                        }
                    }
                    $thistheme = array( $name, $theme[0]->last_updated );
                    array_push( $this->abandoned_themes, $thistheme );
                }
            }
            
            
            if ( count( $response->abandoned_plugins ) == 0 ) {
                $this->abandoned_plugins = array();
            } else {
                foreach ( $response->abandoned_plugins as $plugin ) {
                    $thisplugin = array( $plugin[0]->name, $plugin[0]->last_updated );
                    array_push( $this->abandoned_plugins, $thisplugin );
                }
            }
        
        } else {
            $this->response_error = true;
        }
        
        if ( $this->silent ) {
            return $msg;
        }
    }
    
    private function Check_Incon()
    {
        global  $wpdb ;
        $resultarray = array();
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $SQL = "SELECT ID,fileName, newFile, scanMD5, officialMD5, type from {$table_name} where newFile = '1' and ignoredFile = 0";
        $newlist = $wpdb->get_results( $SQL, ARRAY_A );
        $SQL = "SELECT ID, fileName, newFile, scanMD5, officialMD5, type from {$table_name} where (scanMD5 <> updatedMD5)  and type <> 'N' and ignoredFile = 0 and newFile = '0'";
        $offmodlist = $wpdb->get_results( $SQL, ARRAY_A );
        $SQL = "SELECT ID, fileName, newFile, scanMD5, officialMD5, type from {$table_name} where (scanMD5 <> officialMD5) and (scanMD5 = updatedMD5) and type <> 'N' and ignoredFile = 0 and modifiedFile = 0 and newFile = '0'";
        $offmodlist2 = $wpdb->get_results( $SQL, ARRAY_A );
        $offmodlist = array_merge( $offmodlist, $offmodlist2 );
        $SQL = "SELECT ID, fileName, newFile, scanMD5, officialMD5, type from {$table_name} where scanMD5 <> updatedMD5 and type = 'N' and modifiedFile = 0 and ignoredFile = 0 and newFile = '0'";
        $modlist = $wpdb->get_results( $SQL, ARRAY_A );
        $resultarray = array_merge( $newlist, $offmodlist );
        $resultarray = array_merge( $resultarray, $modlist );
        return $resultarray;
    }
    
    private function Array_Comp( $array1, $array2 )
    {
        $combined = $array1;
        $column = array_column( $array1, 'name' );
        foreach ( $array2 as $item ) {
            $key = array_search( $item['name'], $column );
            
            if ( $key !== false ) {
                $combined[$key]['dupe'] = true;
                $item['dupe'] = true;
                array_push( $combined, $item );
            } else {
                array_push( $combined, $item );
            }
        
        }
        return $combined;
    }
    
    public function Do_Scan()
    {
        global  $wpdb ;
        $isQuick = false;
        $success = true;
        // is this a quick or full scan?
        if ( @$_GET['action'] == 'ScanQuick' || $this->scheduled == 'Quick' ) {
            $isQuick = true;
        }
        // force a full scan if there are chnges.
        
        if ( $this->plugincount || $this->themecount || $this->newVersion ) {
            $isQuick = false;
            $this->ShowOutput( 1 );
        }
        
        // get the official checksums and stuff them into an array
        
        if ( !$isQuick || !$this->firstScan ) {
            update_option( "BlogSafe_Scanner_Ignore_Changed", false );
            $official = new BlogSafe_Scanner_GetOfficialChecksums( $this->silent );
            $sums1 = $official->GetChecksums();
            
            if ( !$sums1 ) {
                $this->ShowOutput( 15, __( 'retrieving official checksums', 'BSScanner' ) );
                $successreason = __( 'retrieving official checksums', 'BSScanner' );
                $success = false;
            }
            
            
            if ( $success ) {
                $plugins = new BlogSafe_Scanner_GetPluginChecksums( $this->silent );
                $sums2 = $plugins->GetPluginChecksums();
                
                if ( !$sums2 ) {
                    $this->ShowOutput( 15, __( 'retrieving plugin checksums', 'BSScanner' ) );
                    $successreason = __( 'retrieving plugin checksums', 'BSScanner' );
                    $success = false;
                }
            
            }
            
            
            if ( $success ) {
                $themes = new BlogSafe_Scanner_GetThemeChecksums( $this->silent );
                $sums3 = $themes->GetThemeChecksums();
                
                if ( !$sums3 ) {
                    $this->ShowOutput( 15, __( 'retrieving theme checksums', 'BSScanner' ) );
                    $successreason = __( 'retrieving theme checksums', 'BSScanner' );
                    $success = false;
                }
            
            }
            
            // all checksums downloaded.
            
            if ( $success ) {
                $this->sums = $this->Array_Comp( $sums1, $sums2 );
                $this->sums = $this->Array_Comp( $this->sums, $sums3 );
                $this->ShowOutput( 4 );
                $this->Begin_Full_Scan( $this->sums );
            }
        
        } else {
            //quick scan
            $this->Begin_Quick_Scan();
        }
        
        
        if ( $success ) {
            update_option( 'BSScanner_Version', get_bloginfo( 'version' ) );
            $table_name = $wpdb->base_prefix . "BS_Scanner";
            $rowcount = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
            $files = $this->Finalize_Scan();
            
            if ( $files === false ) {
                $success = false;
                $successreason = __( "accessing the database", 'BSScanner' );
            }
        
        }
        
        
        if ( $success ) {
            $time_end = microtime( true );
            $execution_time = $time_end - $this->dt;
            $locale_info = localeconv();
            $decimal = $locale_info['decimal_point'];
            $thousands = $locale_info['thousands_sep'];
            $execution_time = number_format(
                $execution_time,
                2,
                $decimal,
                $thousands
            );
            $rowcount = (string) number_format(
                $rowcount,
                0,
                $decimal,
                $thousands
            );
            $this->ShowOutput( 6, $execution_time, $rowcount );
            if ( $this->newVersion ) {
                update_option( 'BSScanner_Version', get_bloginfo( 'version' ), 'no' );
            }
        }
    
    }
    
    private function Finalize_Scan()
    {
        $files = '';
        $mlist = $this->Check_Incon();
        $mstatus = '<font color="#b3b300"><strong>' . __( 'Modified', 'BSScanner' ) . '</strong></font>';
        $ostatus = '<font color="#990000"><strong>' . __( 'Modified Official File!', 'BSScanner' ) . '</strong></font>';
        $nstatus = '<font color="#b3b300"><strong>New File</strong></font>';
        if ( get_option( 'BSScanner_Opt_In' ) == true ) {
            
            if ( $this->silent ) {
                $files .= $this->Check_Threat();
            } else {
                $this->Check_Threat();
            }
        
        }
        $mo = false;
        $nf = false;
        $mf = false;
        
        if ( count( $mlist ) > 0 ) {
            $found = array(
                '<strong>' . __( 'Status', 'BSScanner' ) . '</strong>',
                '<strong>' . __( 'File Name', 'BSScanner' ) . '</strong>',
                '',
                '<strong>' . __( 'Type', 'BSScanner' ) . '</strong>'
            );
            array_push( $this->found_files, $found );
            foreach ( $mlist as $item ) {
                switch ( $item['type'] ) {
                    case "O":
                        $itemtype = __( 'Official', 'BSScanner' );
                        break;
                    case "P":
                        $itemtype = __( 'Plugin', 'BSScanner' );
                        break;
                    case "T":
                        $itemtype = __( 'Theme', 'BSScanner' );
                        break;
                    case "N":
                        $itemtype = __( 'Non-Official', 'BSScanner' );
                        break;
                }
                
                if ( $item['newFile'] == '1' ) {
                    $nf = true;
                    $found = array(
                        $nstatus,
                        $item['fileName'],
                        $item['ID'],
                        $itemtype
                    );
                    array_push( $this->found_files, $found );
                } else {
                    
                    if ( $item['type'] != 'N' ) {
                        $mo = true;
                        $found = array(
                            $ostatus,
                            $item['fileName'],
                            $item['ID'],
                            $itemtype
                        );
                        array_push( $this->found_files, $found );
                    } else {
                        $mf = true;
                        $found = array(
                            $mstatus,
                            $item['fileName'],
                            $item['ID'],
                            $itemtype
                        );
                        array_push( $this->found_files, $found );
                    }
                
                }
            
            }
            update_option( "BlogSafe_Scanner_Report_NF", false );
            update_option( "BlogSafe_Scanner_Report_MO", false );
            if ( $nf ) {
                update_option( "BlogSafe_Scanner_Report_NF", true );
            }
            if ( $mo ) {
                update_option( "BlogSafe_Scanner_Report_MO", true );
            }
        }
        
        $this->Build_Report();
        update_option( "BlogSafe_Scanner_Report_Vulnerabilities", serialize( $this->vulnerabilities ) );
        update_option( "BlogSafe_Scanner_Report_Abandoned_Themes", serialize( $this->abandoned_themes ) );
        update_option( "BlogSafe_Scanner_Report_Abandoned_Plugins", serialize( $this->abandoned_plugins ) );
        update_option( "BlogSafe_Scanner_Report_Files", serialize( $this->found_files ) );
        update_option( "BlogSafe_Scanner_Report", $this->report );
        
        if ( $this->silent ) {
            foreach ( $mlist as $item ) {
                
                if ( $item['newFile'] == '1' ) {
                    $files .= __( "New file: ", 'BSScanner' );
                } else {
                    
                    if ( $item['type'] != 'N' ) {
                        $files .= __( "Modified official file: ", 'BSScanner' );
                    } else {
                        $files .= __( "Modified file: ", 'BSScanner' );
                    }
                
                }
                
                $files .= $item['fileName'] . "\n";
            }
            return $files;
        } else {
            echo  $this->report ;
            echo  $this->Build_Report_Nonce() ;
        }
    
    }
    
    private function Multi_Site_Check()
    {
        foreach ( $this->multisite as $sitepath ) {
            $checkpath = rtrim( $sitepath, '/' );
            $checkpath = ltrim( $checkpath, '/' );
            if ( strpos( $this->path, $checkpath ) !== false ) {
                return true;
            }
        }
        return false;
    }
    
    private function Process_Scan_Results( $ScanResults )
    {
        global  $wpdb ;
        // initialize the table
        $BSUtils = new BlogSafe_Scanner_Utils();
        $date = date( "Y-m-d H:i:s" );
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        
        if ( !$this->firstScan ) {
            foreach ( $ScanResults as $result ) {
                $outname = $BSUtils->outname( $result['name'], $this->OS );
                $this->ShowOutput( 5, "Updating: " . $outname );
                $SQL = $wpdb->prepare(
                    "\r\n                            INSERT IGNORE INTO " . $table_name . "(fileName, scanMD5, officialMD5, updatedMD5, dateFound, newFile, fileFound, type, slug, ver)\r\n                            VALUES ( %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)\r\n                            ",
                    $result['name'],
                    $result['md5'],
                    $result['officialMD5'],
                    $result['md5'],
                    $date,
                    $this->firstScan,
                    '1',
                    $result['type'],
                    $result['slug'],
                    $result['ver']
                );
                
                if ( $wpdb->query( $SQL ) === false ) {
                    echo  $wpdb->last_error . '<br>' ;
                    return false;
                }
            
            }
            update_option( 'BSScanner_FirstScan', 'no' );
        } else {
            $SQL = "Update {$table_name} set fileFound = 0";
            
            if ( $wpdb->query( $SQL ) === false ) {
                echo  $wpdb->last_error . '<br>' ;
                return false;
            }
            
            $sql = "Select * from {$table_name}";
            $dbresult = $wpdb->get_results( $sql, ARRAY_A );
            $dbcolumn = array_column( $dbresult, 'fileName' );
            foreach ( $ScanResults as $result ) {
                $key = $this->Search_DB_Array( $result['name'], $dbcolumn );
                // not found in db
                
                if ( $key === false ) {
                    // new file with official checksum
                    
                    if ( !empty($result['officialMD5']) ) {
                        //insert official
                        $SQL = $wpdb->prepare(
                            "INSERT IGNORE INTO " . $table_name . " " . "(fileName, scanMD5, officialMD5, updatedMD5, fileFound, dateFound, type, slug, ver) " . "VALUES " . "(%s, %s, %s, %s, %s, %s, %s, %s, %s)",
                            $result['name'],
                            $result['md5'],
                            $result['officialMD5'],
                            $result['md5'],
                            '1',
                            $date,
                            $result['type'],
                            $result['slug'],
                            $result['ver']
                        );
                        //insert non official
                    } else {
                        $SQL = $wpdb->prepare(
                            "INSERT IGNORE INTO " . $table_name . " " . "(fileName, scanMD5, updatedMD5, fileFound, newFile, dateFound, type, slug, ver) " . "VALUES " . "(%s, %s, %s, %s, %s, %s, %s, %s, %s)",
                            $result['name'],
                            $result['md5'],
                            $result['md5'],
                            '1',
                            '1',
                            $date,
                            $result['type'],
                            $result['slug'],
                            $result['ver']
                        );
                    }
                
                } else {
                    $filetype = $dbresult[$key]['type'];
                    $fileslug = $dbresult[$key]['slug'];
                    $fileversion = $dbresult[$key]['ver'];
                    $officialmd5 = $dbresult[$key]['officialMD5'];
                    $scanmd5 = $dbresult[$key]['scanMD5'];
                    $updatedmd5 = $dbresult[$key]['updatedMD5'];
                    $modifiedfile = $dbresult[$key]['modifiedFile'];
                    $newfile = $dbresult[$key]['newFile'];
                    $changed = false;
                    
                    if ( !empty($officialmd5) ) {
                        //new official md5 - update version info
                        
                        if ( $officialmd5 != $result['officialMD5'] ) {
                            $officialmd5 = $result['officialMD5'];
                            $filetype = $result['type'];
                            $fileslug = $result['slug'];
                            $fileversion = $result['ver'];
                            $changed = true;
                        }
                        
                        //this file was modified then reverted back to matching officials
                        
                        if ( $officialmd5 == $result['md5'] && $scanmd5 != $result['md5'] ) {
                            $scanmd5 = $result['md5'];
                            $updatedmd5 = $result['md5'];
                            $modifiedfile = '0';
                            $changed = true;
                        }
                    
                    }
                    
                    
                    if ( $updatedmd5 != $result['md5'] ) {
                        $updatedmd5 = $result['md5'];
                        $modifiedfile = '0';
                        $changed = true;
                    }
                    
                    
                    if ( $changed ) {
                        $SQL = $wpdb->prepare(
                            "UPDATE {$table_name} " . "SET" . " scanMD5 = %s," . " officialMD5 = %s," . " updatedMD5 = %s," . " fileFound = 1," . " modifiedFile = %s," . " type = %s," . " slug = %s," . " ver = %s" . " where fileName = %s",
                            $scanmd5,
                            $officialmd5,
                            $updatedmd5,
                            $modifiedfile,
                            $filetype,
                            $fileslug,
                            $fileversion,
                            $result['name']
                        );
                    } else {
                        $SQL = $wpdb->prepare( "UPDATE {$table_name} " . "SET fileFound = 1 where fileName = %s", $result['name'] );
                    }
                
                }
                
                $outname = $BSUtils->outname( $result['name'], $this->OS );
                $this->ShowOutput( 5, "Updating: " . $outname );
                
                if ( $wpdb->query( $SQL ) === false ) {
                    echo  $wpdb->last_error . '<br>' ;
                    return false;
                }
            
            }
            $SQL = "Delete from {$table_name} where fileFound = '0'";
            
            if ( $wpdb->query( $SQL ) === false ) {
                echo  $wpdb->last_error . '<br>' ;
                return false;
            }
        
        }
    
    }
    
    private function Begin_Quick_Scan()
    {
        global  $wpdb ;
        $BSUtils = new BlogSafe_Scanner_Utils();
        $this->ShowOutput( 4 );
        $ScanResults = array();
        $date = date( "Y-m-d H:i:s" );
        //included for later dir blacklist
        $directorylist = array( WP_CONTENT_DIR . 'cache' );
        $extensionlist = array(
            '7z',
            'bmp',
            'bz2',
            'css',
            'doc',
            'docx',
            'fla',
            'flv',
            'gif',
            'gz',
            'ico',
            'jpeg',
            'jpg',
            'less',
            'mo',
            'mov',
            'mp3',
            'mp4',
            'pdf',
            'png',
            'po',
            'pot',
            'ppt',
            'pptx',
            'psd',
            'rar',
            'scss',
            'so',
            'svg',
            'tar',
            'tgz',
            'tif',
            'tiff',
            'ttf',
            'txt',
            'webp',
            'wmv',
            'z',
            'zip'
        );
        $multi = $this->Multi_Site_Check();
        $table_name = $wpdb->base_prefix . "BS_Scanner";
        $SQL = "Select * from {$table_name}";
        
        if ( !($dbresult = $wpdb->get_results( $SQL, ARRAY_A )) ) {
            echo  $wpdb->last_error . '<br>' ;
            return false;
        }
        
        $officialmd5 = '';
        $dbcolumn = array_column( $dbresult, 'fileName' );
        if ( $this->OS == 'w' ) {
            $this->path = str_replace( '/', '\\', $this->path );
        }
        $objects = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->path ), RecursiveIteratorIterator::SELF_FIRST );
        $type = 'N';
        $ver = '';
        $slug = '';
        foreach ( $objects as $name => $object ) {
            $thispath = pathinfo( $name, PATHINFO_DIRNAME );
            $excludedirfound = false;
            foreach ( $directorylist as $dir ) {
                if ( strpos( $thispath, $dir ) != false ) {
                    $excludedirfound = true;
                }
            }
            
            if ( is_file( $name ) && !$excludedirfound ) {
                $bname = str_replace( $this->path, '', $name );
                $key = $this->Search_DB_Array( $bname, $dbcolumn );
                $outname = $BSUtils->outname( $bname, $this->OS );
                $this->ShowOutput( 5, "Scanning: " . $outname );
                
                if ( $key === false ) {
                    // new file
                    $md5 = md5_file( $name );
                    array_push( $ScanResults, array(
                        'name'        => $bname,
                        'md5'         => $md5,
                        'officialMD5' => $officialmd5,
                        'ver'         => $ver,
                        'slug'        => $slug,
                        'type'        => $type,
                    ) );
                } else {
                    //scan this file
                    $ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
                    
                    if ( !in_array( $ext, $extensionlist ) && !$multi ) {
                        $md5 = md5_file( $name );
                        array_push( $ScanResults, array(
                            'name'        => $bname,
                            'md5'         => $md5,
                            'officialMD5' => $dbresult[$key]['officialMD5'],
                            'ver'         => $dbresult[$key]['ver'],
                            'slug'        => $dbresult[$key]['slug'],
                            'type'        => $dbresult[$key]['type'],
                        ) );
                    } else {
                        array_push( $ScanResults, array(
                            'name'        => $bname,
                            'md5'         => $dbresult[$key]['scanMD5'],
                            'officialMD5' => $dbresult[$key]['officialMD5'],
                            'ver'         => $dbresult[$key]['ver'],
                            'slug'        => $dbresult[$key]['slug'],
                            'type'        => $dbresult[$key]['type'],
                        ) );
                    }
                
                }
            
            }
        
        }
        $this->Process_Scan_Results( $ScanResults );
        return;
    }
    
    private function Search_DB_Array( $name, $column )
    {
        $key = array_search( $name, $column );
        if ( $key === false ) {
            return false;
        }
        return $key;
    }
    
    private function Search_Official_Checksums( $name, $column )
    {
        $key = array_search( $name, $column );
        if ( $key === false ) {
            return false;
        }
        
        if ( $this->sums[$key]['dupe'] == true ) {
            
            if ( !($dupes = array_filter( $column, array( new BlogSafe_Scanner_Official_Filter( $this->sums[$key]['name'] ), 'findDupe' ) )) ) {
                return false;
            } else {
                $tempmd5 = array();
                foreach ( $dupes as $key => $value ) {
                    array_push( $tempmd5, $key );
                }
                return $tempmd5;
            }
        
        } else {
            return $key;
        }
    
    }
    
    private function Begin_Full_Scan( $sums )
    {
        global  $wpdb ;
        $BSUtils = new BlogSafe_Scanner_Utils();
        $ScanResults = array();
        if ( $this->OS == 'w' ) {
            $this->path = str_replace( '/', '\\', $this->path );
        }
        $objects = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->path ), RecursiveIteratorIterator::SELF_FIRST );
        $column = array_column( $this->sums, 'name' );
        $multi = $this->Multi_Site_Check();
        //included for later directory blacklisting
        $directorylist = array( WP_CONTENT_DIR . 'cache' );
        foreach ( $objects as $name => $object ) {
            $thispath = pathinfo( $name, PATHINFO_DIRNAME );
            $excludedirfound = false;
            foreach ( $directorylist as $dir ) {
                if ( strpos( $thispath, $dir ) != false ) {
                    $excludedirfound = true;
                }
            }
            if ( is_file( $name ) && !$excludedirfound ) {
                // if not on a multisite path, process it.
                
                if ( !$multi ) {
                    $officialmd5 = '';
                    $md5 = md5_file( $name );
                    $name = str_replace( $this->path, '', $name );
                    $outname = $BSUtils->outname( $name, $this->OS );
                    $this->ShowOutput( 5, "Scanning: " . $outname );
                    $type = 'N';
                    $ver = '';
                    $slug = '';
                    //check file in officials
                    $comparison = $this->Search_Official_Checksums( $name, $column );
                    
                    if ( $comparison !== false ) {
                        // set official md5 with dupes
                        
                        if ( is_array( $comparison ) ) {
                            foreach ( $comparison as $thiskey ) {
                                
                                if ( $officialmd5 == NULL ) {
                                    $officialmd5 = $this->sums[$thiskey]['md5'];
                                    $ver = $this->sums[$thiskey]['version'];
                                    $slug = $this->sums[$thiskey]['slug'];
                                    $type = $this->sums[$thiskey]['type'];
                                } else {
                                    
                                    if ( $md5 == $this->sums[$thiskey]['md5'] ) {
                                        $officialmd5 = $this->sums[$thiskey]['md5'];
                                        $ver = $this->sums[$thiskey]['version'];
                                        $slug = $this->sums[$thiskey]['slug'];
                                        $type = $this->sums[$thiskey]['type'];
                                    }
                                
                                }
                            
                            }
                        } else {
                            $officialmd5 = $this->sums[$comparison]['md5'];
                            $ver = $this->sums[$comparison]['version'];
                            $slug = $this->sums[$comparison]['slug'];
                            $type = $this->sums[$comparison]['type'];
                        }
                    
                    } else {
                        // official file not found in scan results
                    }
                    
                    $found = false;
                    foreach ( $directorylist as $dir ) {
                        if ( strpos( $name, $dir ) != false ) {
                            $found = true;
                        }
                    }
                    if ( !$found ) {
                        array_push( $ScanResults, array(
                            'name'        => $name,
                            'md5'         => $md5,
                            'officialMD5' => $officialmd5,
                            'ver'         => $ver,
                            'slug'        => $slug,
                            'type'        => $type,
                        ) );
                    }
                }
            
            }
        }
        $this->Process_Scan_Results( $ScanResults );
    }

}