<?php
/**
 * Archivo de compatibilidad — redirige al endpoint AJAX del plugin.
 *
 * La lógica de descarga fue migrada al plugin de WordPress como una
 * acción AJAX (ietk_download_usuarios). Este archivo se mantiene para
 * no romper accesos directos existentes al servidor.
 *
 * Para eliminar la dependencia de este archivo externo, actualizar el
 * plugin desde el repositorio y ya no se necesita deployar esta carpeta.
 */

$wp_load_candidates = [
    __DIR__ . '/../toolkit/wp-load.php',
    __DIR__ . '/../wp-load.php',
    __DIR__ . '/../../wp-load.php',
];

$wp_loaded = false;
foreach ( $wp_load_candidates as $candidate ) {
    if ( file_exists( $candidate ) ) {
        require_once $candidate;
        $wp_loaded = true;
        break;
    }
}

if ( ! $wp_loaded ) {
    http_response_code( 500 );
    die( 'No se pudo encontrar la instalación de WordPress.' );
}

if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    http_response_code( 403 );
    die( 'No tienes suficientes permisos para acceder a esta página.' );
}

$params = http_build_query( [
    'action'     => 'ietk_download_usuarios',
    'nonce'      => wp_create_nonce( 'ietk_download_usuarios_nonce' ),
    'begin_date' => isset( $_GET['begin_date'] ) ? $_GET['begin_date'] : '',
    'end_date'   => isset( $_GET['end_date'] )   ? $_GET['end_date']   : '',
    's'          => isset( $_GET['s'] )           ? $_GET['s']          : '',
] );

wp_redirect( admin_url( 'admin-ajax.php' ) . '?' . $params );
exit;
