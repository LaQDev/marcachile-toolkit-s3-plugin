<?php
defined('ABSPATH') or die('Bye bye');
if ( ! current_user_can('manage_options') ) {
    wp_die( __('No tienes suficientes permisos para acceder a esta página.') );
}
?>
<div class="wrap">
    <h1>Admin Toolkit — Configuración AWS S3</h1>
    <?php settings_errors(); ?>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'admin_toolkit_settings' );
        do_settings_sections( 'admin-toolkit-settings' );
        submit_button( 'Guardar configuración' );
        ?>
    </form>
</div>
