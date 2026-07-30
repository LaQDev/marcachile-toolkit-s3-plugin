<?php
defined('ABSPATH') or die('Bye bye');
if ( ! current_user_can('manage_options') ) {
    wp_die( __('No tienes suficientes permisos para acceder a esta página.') );
}

echo '<div class="wrap"><h1>Reparar nombres de archivo en S3</h1>';
echo '<p>Detecta archivos del toolkit cuyo nombre en S3 tiene tildes, ñ u otros caracteres que provocan el error <code>Header value cannot be represented using ISO-8859-1</code> al descargar, y permite corregirlos.</p>';

$s3 = ietk_get_s3_client();
if ( is_wp_error( $s3 ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html( $s3->get_error_message() ) . '</p></div></div>';
    return;
}
$bucket = ietk_get_s3_bucket();

$mensajes = array();

// --- Procesar reparación (POST) ---
if ( isset( $_POST['ietk_reparar_accion'] ) && 'reparar' === $_POST['ietk_reparar_accion'] ) {
    check_admin_referer( 'ietk_reparar_nombres_s3' );

    $seleccionados = isset( $_POST['seleccionados'] ) && is_array( $_POST['seleccionados'] ) ? $_POST['seleccionados'] : array();
    $items         = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? $_POST['items'] : array();

    foreach ( $seleccionados as $indice => $marcado ) {
        if ( empty( $items[ $indice ] ) ) {
            continue;
        }

        $item      = $items[ $indice ];
        $post_id   = intval( $item['post_id'] ?? 0 );
        $old_key   = sanitize_text_field( wp_unslash( $item['old_key'] ?? '' ) );
        $source    = sanitize_text_field( $item['source'] ?? '' );
        $row_index = isset( $item['row_index'] ) && '' !== $item['row_index'] ? intval( $item['row_index'] ) : null;

        if ( ! $post_id || '' === $old_key ) {
            continue;
        }

        try {
            $new_key = ietk_construir_key_saneada( $old_key );
            $new_key = ietk_evitar_colision_s3( $s3, $bucket, $new_key );

            $s3->copyObject( array(
                'Bucket'     => $bucket,
                'Key'        => $new_key,
                'CopySource' => $bucket . '/' . Aws\S3\S3Client::encodeKey( $old_key ),
            ) );
            // Verifica que la copia realmente quedó antes de borrar el original.
            $s3->headObject( array( 'Bucket' => $bucket, 'Key' => $new_key ) );
            $s3->deleteObject( array( 'Bucket' => $bucket, 'Key' => $old_key ) );

            if ( 'acf' === $source && null !== $row_index && function_exists( 'update_sub_field' ) ) {
                update_sub_field( array( 'datos_archivos', $row_index + 1, 'ruta_de_archivo' ), $new_key, $post_id );
            } else {
                update_post_meta( $post_id, 'ruta_de_archivo', $new_key );
                update_post_meta( $post_id, 'ruta_s3', $new_key );
            }

            $mensajes[] = array(
                'tipo'  => 'success',
                'texto' => sprintf( 'Post #%d: "%s" → "%s" reparado.', $post_id, $old_key, $new_key ),
            );
        } catch ( \Exception $e ) {
            $mensajes[] = array(
                'tipo'  => 'error',
                'texto' => sprintf( 'Post #%d: error al reparar "%s": %s', $post_id, $old_key, $e->getMessage() ),
            );
        }
    }
}

foreach ( $mensajes as $m ) {
    $clase = 'error' === $m['tipo'] ? 'notice-error' : 'notice-success';
    echo '<div class="notice ' . esc_attr( $clase ) . '"><p>' . esc_html( $m['texto'] ) . '</p></div>';
}

// --- Escanear candidatos ---
$candidatos = array();
$post_ids   = get_posts( array(
    'post_type'      => 'toolkit',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
) );

foreach ( $post_ids as $post_id ) {
    if ( function_exists( 'have_rows' ) && have_rows( 'datos_archivos', $post_id ) ) {
        $i = 0;
        while ( have_rows( 'datos_archivos', $post_id ) ) {
            the_row();
            $key = get_sub_field( 'ruta_de_archivo' );
            if ( $key ) {
                $key_limpia = ietk_construir_key_saneada( $key );
                if ( $key_limpia !== $key ) {
                    $candidatos[] = array(
                        'post_id'   => $post_id,
                        'titulo'    => get_the_title( $post_id ),
                        'old_key'   => $key,
                        'new_key'   => $key_limpia,
                        'source'    => 'acf',
                        'row_index' => $i,
                    );
                }
            }
            $i++;
        }
    }

    // Ruta del formulario antiguo, por si quedara algún registro suelto que no
    // pasó por el repeater ACF (ver includes/operaciones.php).
    $vistos = array();
    foreach ( array( 'ruta_de_archivo', 'ruta_s3' ) as $meta_key ) {
        $legacy_key = get_post_meta( $post_id, $meta_key, true );
        if ( $legacy_key && ! in_array( $legacy_key, $vistos, true ) ) {
            $vistos[]   = $legacy_key;
            $key_limpia = ietk_construir_key_saneada( $legacy_key );
            if ( $key_limpia !== $legacy_key ) {
                $candidatos[] = array(
                    'post_id'   => $post_id,
                    'titulo'    => get_the_title( $post_id ),
                    'old_key'   => $legacy_key,
                    'new_key'   => $key_limpia,
                    'source'    => 'legacy',
                    'row_index' => null,
                );
            }
        }
    }
}

if ( empty( $candidatos ) ) {
    echo '<p>No se encontraron archivos con nombres problemáticos. ✅</p></div>';
    return;
}
?>
<form method="post">
    <?php wp_nonce_field( 'ietk_reparar_nombres_s3' ); ?>
    <input type="hidden" name="ietk_reparar_accion" value="reparar">
    <p>
        <label><input type="checkbox" id="ietk-seleccionar-todos"> Seleccionar todos</label>
    </p>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:40px;"></th>
                <th>Toolkit</th>
                <th>Nombre actual en S3</th>
                <th>Nombre propuesto</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ( $candidatos as $indice => $c ) : ?>
            <tr>
                <td>
                    <input type="checkbox" class="ietk-item-checkbox" name="seleccionados[<?php echo esc_attr( $indice ); ?>]" value="1">
                    <input type="hidden" name="items[<?php echo esc_attr( $indice ); ?>][post_id]" value="<?php echo esc_attr( $c['post_id'] ); ?>">
                    <input type="hidden" name="items[<?php echo esc_attr( $indice ); ?>][old_key]" value="<?php echo esc_attr( $c['old_key'] ); ?>">
                    <input type="hidden" name="items[<?php echo esc_attr( $indice ); ?>][source]" value="<?php echo esc_attr( $c['source'] ); ?>">
                    <input type="hidden" name="items[<?php echo esc_attr( $indice ); ?>][row_index]" value="<?php echo esc_attr( $c['row_index'] ?? '' ); ?>">
                </td>
                <td><a href="<?php echo esc_url( get_edit_post_link( $c['post_id'] ) ); ?>" target="_blank"><?php echo esc_html( $c['titulo'] ); ?></a> (#<?php echo esc_html( $c['post_id'] ); ?>)</td>
                <td><code><?php echo esc_html( $c['old_key'] ); ?></code></td>
                <td><code><?php echo esc_html( $c['new_key'] ); ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php submit_button( 'Reparar seleccionados' ); ?>
</form>
<script>
document.getElementById('ietk-seleccionar-todos').addEventListener('change', function () {
    document.querySelectorAll('.ietk-item-checkbox').forEach(function (cb) {
        cb.checked = this.checked;
    }.bind(this));
});
</script>
</div>
