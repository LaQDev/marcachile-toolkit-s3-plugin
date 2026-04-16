<?php
/* 
Plugin Name: Admin Toolkit
Plugin URI: https://isaacespinoza.com
Description: Admin Toolkit - de Marca Chile
Version: 1.0
Author: Isaac Espinoza
Author URI: https://isaacespinoza.com
*/ 

//Evita que un usuario malintencionado ejecute codigo php desde la barra del navegador

defined('ABSPATH') or die( "Bye bye" );

//Aqui se definen las constantes
//define('IE_RUTA',plugin_dir_path(__FILE__));
//define('IE_NOMBRE','Admin Toolkit');

//$dir = plugin_dir_url( __FILE__ );


//Archivos externos


function ietk_load_functions() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/funciones.php';
    require_once plugin_dir_path( __FILE__ ) . 'includes/operaciones.php';
}
add_action( 'init', 'ietk_load_functions' );


function ietk_render_settings_page() {
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings.php';
}

function ietk_sanitize_secret_key( $new ) {
    if ( '' === trim( $new ) ) {
        return get_option( 'admin_toolkit_aws_secret_key', '' );
    }
    return sanitize_text_field( $new );
}

function ietk_register_settings() {
    register_setting( 'admin_toolkit_settings', 'admin_toolkit_aws_access_key', 'sanitize_text_field' );
    register_setting( 'admin_toolkit_settings', 'admin_toolkit_aws_secret_key', 'ietk_sanitize_secret_key' );
    register_setting( 'admin_toolkit_settings', 'admin_toolkit_aws_region',     'sanitize_text_field' );
    register_setting( 'admin_toolkit_settings', 'admin_toolkit_s3_bucket',      'sanitize_text_field' );

    add_settings_section(
        'admin_toolkit_aws_section',
        'Credenciales y configuración AWS S3',
        null,
        'admin-toolkit-settings'
    );

    add_settings_field( 'aws_access_key', 'Access Key ID',    'ietk_field_access_key', 'admin-toolkit-settings', 'admin_toolkit_aws_section' );
    add_settings_field( 'aws_secret_key', 'Secret Access Key','ietk_field_secret_key', 'admin-toolkit-settings', 'admin_toolkit_aws_section' );
    add_settings_field( 'aws_region',     'Región AWS',       'ietk_field_region',     'admin-toolkit-settings', 'admin_toolkit_aws_section' );
    add_settings_field( 's3_bucket',      'Bucket S3',        'ietk_field_bucket',     'admin-toolkit-settings', 'admin_toolkit_aws_section' );
}
add_action( 'admin_init', 'ietk_register_settings' );

function ietk_field_access_key() {
    $val = esc_attr( get_option( 'admin_toolkit_aws_access_key', '' ) );
    echo "<input type='text' name='admin_toolkit_aws_access_key' value='{$val}' class='regular-text'>";
}
function ietk_field_secret_key() {
    $stored = get_option( 'admin_toolkit_aws_secret_key', '' );
    $placeholder = $stored ? '••••••••••••••••' : '';
    printf(
        '<input type="password" name="admin_toolkit_aws_secret_key" value="" placeholder="%s" class="regular-text">',
        esc_attr( $placeholder )
    );
}
function ietk_field_region() {
    $val = esc_attr( get_option( 'admin_toolkit_aws_region', 'us-east-1' ) );
    echo "<input type='text' name='admin_toolkit_aws_region' value='{$val}' class='regular-text'>";
}
function ietk_field_bucket() {
    $val = esc_attr( get_option( 'admin_toolkit_s3_bucket', 'cdn.marcachile2.redon.cl' ) );
    echo "<input type='text' name='admin_toolkit_s3_bucket' value='{$val}' class='regular-text'>";
}
