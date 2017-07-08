<?php

/* START */

//Download CSV from FTP to WP

class DownloadFromFTP {
    /**
     * Instance of DownloadFromFTP
     * @var DownloadFromFTP
     */
    public static $instance = null;
    public static function init() {
        if ( null == self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function main() {
//        if ( ! isset( $_GET['download'] ) ) {
//            return;
//        }
        // First load the necessary classes
        if ( ! function_exists( 'wp_tempnam' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
        }
        if ( ! class_exists( 'WP_Filesystem_FTPext' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpext.php' );
        }
        // Typically this is not defined, so we set it up just in case
        if ( ! defined( 'FS_CONNECT_TIMEOUT' ) ) {
            define( 'FS_CONNECT_TIMEOUT', 30 );
        }
        
        // Check if the file CSV already exist in the file system
//        $path_temp = "../wp-content/uploads/" . date("Y");
//        if (is_dir($path_temp) && file_exists($path_temp)) {
//            $path_temp .= "/" . date("m");
//            if(is_dir($path_temp) && file_exists($path_temp)) {
//                $path_temp .= "/inventario.csv";
//                if(file_exists($path_temp)) {
//                    unlink($path_temp);
//                }
//            }
//        }
        /**
         * You DO NOT want to hard-code this, these values are here specifically for
         * testing purposes.
         */
        $option = get_option('dcsv_options');
        $connection_arguments = array(
            'port' => "{$option['port']}",
            'hostname' => "{$option['host']}",
            'username' => "{$option['username']}",
            'password' => "{$option['password']}",
        );
        $connection = new WP_Filesystem_FTPext( $connection_arguments );
        $connected = $connection->connect();
        if ( ! $connected ) {
            return;
        }
        
        $remote_file = "inventario.csv";
        // Yep, you can use paths as well.
        // $remote_file = "some/remote-file.txt";
        if ( ! $connection->is_file( $remote_file ) ) {
            return;
        }
        // Get the contents of the file into memory.
        $remote_contents = $connection->get_contents( $remote_file );
        if ( empty( $remote_contents ) ) {
            return;
        }
        // Create a temporary file to store our data.
        $temp_file = wp_tempnam( $remote_file );
        if ( ! is_writable( $temp_file ) || false === file_put_contents( $temp_file, $remote_contents ) ) {
            unlink( $temp_file );
            return;
        }
        // Optimally you want to check the filetype against a WordPress method, or you can hard-code it.
        $mime_data = wp_check_filetype( $remote_file );
        if ( ! isset( $mime_data['type'] ) ) {
            // WE just don't have a type registered for this attachment
            unlink( $temp_file ); // Cleanup
            return;
        }
        /**
         * The following arrays are pretty much a copy/paste from the Codex, no need
         * to re-invent the wheel.
         * @link https://codex.wordpress.org/Function_Reference/wp_handle_sideload#Examples
         */
        $file_array = array(
            'name'     => basename( $remote_file ),
            'type'     => $mime_data['type'],
            'tmp_name' => $temp_file,
            'error'    => 0,
            'size'     => filesize( $temp_file ),
        );
        $overrides = array(
            'test_form'   => false,
            'test_size'   => true,
            'test_upload' => true,
        );
        // Side loads the content into the wp-content/uploads directory.
        $sideloaded = wp_handle_sideload( $file_array, $overrides );
        if ( ! empty( $sideloaded['error'] ) ) {
            return;
        }
        
    }
    
    

}
function DownloadFromFTP() {
    check_file_exist();
    return DownloadFromFTP::init();
}

function cron_define() {
    if(!wp_next_scheduled('update_store')) {
        wp_schedule_event(current_time('timestamp'),'hourly','update_store');
        add_option('my_plugin_do_activation_redirect', true);
    }
}

function unset_cron() {
    wp_clear_scheduled_hook('update_store');
}

function my_plugin_redirect() {
    if (get_option('my_plugin_do_activation_redirect', false)) {
        delete_option('my_plugin_do_activation_redirect');
        exit(wp_redirect('tools.php?page=ftp_settings'));
    }
}

function check_file_exist()
{
    $path_temp = "../wp-content/uploads/" . date("Y");
    if (is_dir($path_temp) && file_exists($path_temp)) {
        $path_temp .= "/" . date("m");
        if (is_dir($path_temp) && file_exists($path_temp)) {
            $path_temp .= "/inventario.csv";
            if (file_exists($path_temp)) {
                unlink($path_temp);
            }
        } else {
            $month = intval(date("m")) - 1;
            $month = $month < 10 ? "0" . $month : $month;
            $path_temp = "../wp-content/uploads/" . date("Y") . "/" . $month;
            if (is_dir($path_temp) && file_exists($path_temp)) {

                $path_temp .= "/inventario.csv";
                if (file_exists($path_temp)) {
                    unlink($path_temp);
                    $month = intval(date("m")) - 1;
                    $month = $month < 10 ? "0" . $month : $month;
                    $path_temp = "../wp-content/uploads/" . date("Y") . "/" . $month;
                    rmdir($path_temp);
                } else {
                    $month = intval(date("m")) - 1;
                    $month = $month < 10 ? "0" . $month : $month;
                    $path_temp = "../wp-content/uploads/" . date("Y") . "/" . $month;
                    rmdir($path_temp);
                }
            }
        }
    }
    else {
        $path_temp = "../wp-content/uploads/" . date("Y", strtotime("-1 year"));
        if (is_dir($path_temp) && file_exists($path_temp)) {
                $path_temp .= "/12";
                if (is_dir($path_temp) && file_exists($path_temp)) {
                    $path_temp .= "/inventario.csv";
                    if (file_exists($path_temp)) {
                        unlink($path_temp);
                        rmdir("../wp-content/uploads/" . date("Y", strtotime("-1 year")) . "/12");
                        rmdir("../wp-content/uploads/" . date("Y", strtotime("-1 year")));
                    } else {
                        rmdir("../wp-content/uploads/" . date("Y", strtotime("-1 year")) . "/12");
                        rmdir("../wp-content/uploads/" . date("Y", strtotime("-1 year")));
                    }
                } else {
                    rmdir("../wp-content/uploads/" . date("Y", strtotime("-1 year")));
                }
            }

        }
        return;
}
/* STOP */
?>
