<?php
/*
Plugin Name: Natural Is Smarter Functions Plugin
Description: Site specific code changes for NaturalIsSmarter.com
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
    public function hooks() {
        // All hooks here.
        add_action( 'init', array( $this, 'main' ) );
    }
    public function main() {
        if ( ! isset( $_GET['download'] ) ) {
            return;
        }
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
            'hostname' => '127.0.0.1',
            'username' => '',
            'password' => '',
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
        unlink( $temp_file ); // Final cleanup
    }
}
function DownloadFromFTP() {
    return DownloadFromFTP::init();
}
add_action( 'plugins_loaded', array( DownloadFromFTP(), 'hooks' ) );


/* STOP */
?>