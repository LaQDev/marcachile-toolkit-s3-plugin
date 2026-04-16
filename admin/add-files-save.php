<?php
defined('ABSPATH') or die('Bye bye');
if ( ! current_user_can('manage_options') ) {
    wp_die( __('No tienes suficientes permisos para acceder a esta página.') );
}
// La subida de archivos ahora se realiza directamente al bucket S3
// desde el browser via presigned URLs. Ver admin/add-files.php y
// los endpoints AJAX en includes/operaciones.php.
wp_redirect( admin_url('edit.php?post_type=toolkit&page=' . plugin_dir_path(__FILE__) . 'add-files.php') );
exit;
