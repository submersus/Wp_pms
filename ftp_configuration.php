<?php
/*
Plugin Name: CSV Downloader
Description: Download a CSV file with the catalog of articles from a ftp server.
Version: 1.0
Author: pentagonmediasolutions by: Ivan de Menezes,Alirio Angel.
*/

include('DownloadFromFTP.php');

add_action('admin_menu', 'option_menu');
function option_menu() {
    add_submenu_page(
    'tools.php',
    'Download CSV Settings',
    'Download CSV Settings',
    'manage_options',
    'ftp_settings',
    'dcsv_options_page_html'
    );
}

function dcsv_options_page_html() {
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "wporg_options"
            settings_fields('dcsv');
            // output setting sections and their fields
            // (sections are registered for "wporg", each field is registered to a specific section)
            do_settings_sections('dcsv');
            // output save settings button
            submit_button('Guardar Ajustes');
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init','dcsv_settings_init');

function dcsv_settings_init() {
    register_setting('dcsv', 'dcsv_options');
    
    add_settings_section(
    'server_section',
    __( 'Configuración del servidor FTP', 'dcsv' ),
     'wporg_section_developers_cb',
     'dcsv'
     );
    
    add_settings_field(
    'host',
    'Dirección IP del Servidor',
    'fields_render',
    'dcsv',
    'server_section',
    $id = 'host'
    );
    
    add_settings_field(
    'username',
    'Nombre de Usuario',
    'fields_render',
    'dcsv',
    'server_section',
    $id = 'username'
    );
    
    add_settings_field(
    'password',
    'Contraseña',
    'fields_render',
    'dcsv',
    'server_section',
    $id = 'password'
    );
    
    add_settings_field(
    'port',
    'Puerto de Conexión',
    'fields_render',
    'dcsv',
    'server_section',
    $id = 'port'
    );
}

function fields_render($id) {
    $options = get_option('dcsv_options');
    $value = $options[$id];
    ?>
    <input id="<?php echo $id ?>" name="dcsv_options[<?php echo $id; ?>]" type="text" value="<?php echo (!isset($value) && $id=='port' ? "21" : esc_attr( $value )); ?>" >
    <?php
}

register_activation_hook( __FILE__, 'cron_define' );
add_action('admin_init', 'my_plugin_redirect');
add_action( 'update_store' , array(DownloadFromFTP(), 'main'));
register_deactivation_hook( __FILE__, 'unset_cron' );

