<?php
/**
 * Descarga un CSV con los usuarios registrados en WordPress.
 * Parámetros GET: begin_date, end_date, s (búsqueda opcional).
 *
 * Este archivo vive fuera de la instalación de WordPress, por lo que
 * debe cargar wp-load.php primero para acceder a $wpdb y a las
 * funciones de autenticación.
 */

// Ruta absoluta a wp-load.php.
// Si el servidor cambia la ubicación de WordPress, ajustar este path.
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

// Verificar autenticación y permisos.
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    http_response_code( 403 );
    die( 'No tienes suficientes permisos para acceder a esta página.' );
}

global $wpdb;

// Filtro por rango de fechas.
$begin_date = isset( $_GET['begin_date'] ) ? sanitize_text_field( $_GET['begin_date'] ) : '';
$end_date   = isset( $_GET['end_date'] )   ? sanitize_text_field( $_GET['end_date'] )   : '';
$search     = isset( $_GET['s'] )          ? sanitize_text_field( $_GET['s'] )          : '';

$where = 'WHERE TRUE';

if ( $begin_date && $end_date ) {
    $where .= $wpdb->prepare(
        " AND user_registered BETWEEN %s AND %s",
        $begin_date . ' 00:00:00',
        $end_date   . ' 23:59:59'
    );
}

if ( $search ) {
    $like   = '%' . $wpdb->esc_like( $search ) . '%';
    $where .= $wpdb->prepare(
        " AND (display_name LIKE %s OR user_email LIKE %s)",
        $like,
        $like
    );
}

$usuarios = $wpdb->get_results(
    "SELECT ID, display_name, user_email, user_registered FROM {$wpdb->users} {$where}",
    ARRAY_A
);

// Nombre del archivo de descarga.
$filename = 'usuarios-' . date( 'Y-m-d' ) . '.csv';

header( 'Content-Type: text/csv; charset=utf-8' );
header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );

$output = fopen( 'php://output', 'w' );

// BOM para que Excel abra el CSV con UTF-8 correctamente.
fputs( $output, "\xEF\xBB\xBF" );

// Cabecera del CSV.
fputcsv( $output, [ 'ID', 'Nombre', 'Email', 'Fecha de Registro' ] );

foreach ( $usuarios as $usuario ) {
    fputcsv( $output, [
        $usuario['ID'],
        $usuario['display_name'],
        $usuario['user_email'],
        $usuario['user_registered'],
    ] );
}

fclose( $output );
exit;
