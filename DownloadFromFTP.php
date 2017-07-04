<?php
/*
Plugin Name: Download CSV
Description: Download a CSV file with the catalog of articles from a ftp server
*/
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
        /**
         * You DO NOT want to hard-code this, these values are here specifically for
         * testing purposes.
         */
        $connection_arguments = array(
            'port' => 21,
            'hostname' => '66.220.9.50',
            'username' => 'ivan31',
            'password' => '123456',
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
        // Will return a 0 if for some reason insertion fails.
        $attachment_id = wp_insert_attachment( array(
            'guid'           => $sideloaded['url'], // wp_handle_sideload() will have a URL array key which is the absolute URL including HTTP
            'post_mime_type' => $sideloaded['type'], // wp_handle_sideload() will have a TYPE array key, so we use this in case it was filtered somewhere
            'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $remote_file ) ), // Again copy/paste from codex
            'post_content'   => '',
            'post_status'    => 'inherit',
        ), $sideloaded['file'] ); // wp_handle_sideload() will have a file array key, so we use this in case it was filtered
//        unlink( $temp_file ); // Final cleanup
    }

}
function DownloadFromFTP() {
    return DownloadFromFTP::init();
}

function cron_define() {
    if(!wp_next_scheduled('update_store')) {
        wp_schedule_event(current_time('timestamp'),'hourly','update_store');
    }
}

function unset_cron() {
    wp_clear_scheduled_hook('update_store');
}

register_activation_hook( __FILE__, 'cron_define' );
add_action( 'update_store' , array(DownloadFromFTP(), 'main'));
register_deactivation_hook( __FILE__, 'unset_cron' );

/* STOP */
?>