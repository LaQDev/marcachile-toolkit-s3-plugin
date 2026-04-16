<?php
// var_dump(current_user_can ('manage_options')); die;
defined('ABSPATH') or die( "Bye bye" );
//Comprueba que tienes permisos para acceder a esta pagina
if (! current_user_can ('manage_options')) wp_die (__ ('No tienes suficientes permisos para acceder a esta página.'));

if(empty($_REQUEST["action"]) && empty($_REQUEST["post_id"])){
    require_once(dirname(__FILE__) . '/../includes/tabla-toolkits.php');
    
    $myListTable = new Tabla_Toolkits();
    $myListTable->per_page = 15;
    $myListTable->columns_data = array (
        "id" => "ID",
        "post_title" => "Título",
        "categoria" => "Categoría",
        "post_date" => "Fecha"); 
    $myListTable->sortable_columns = array(
        "id" => array("id", false),
        "post_title" => array("post_title", false),
        "categoria" => array("categoria", false),
        "post_date" => array("post_date", false));
    
    echo '<div class="wrap"><h2>Toolkit</h2>'; 
    $myListTable->prepare_items(); 

    echo '<form action="" method="GET">
        <input type="hidden" name="post_type" class="post_type_page" value="toolkit">
        <input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '"/>';
    $myListTable->search_box( 'Buscar toolkits' , 'search_id' );
    echo '</form>';

    $myListTable->display(); 
    echo '</div>';

} else {
    $post_id = $_REQUEST["post_id"];
    $carpeta_slug = '';
    $terms = get_the_terms( $post_id, 'categorias' );
    if ( $terms && ! is_wp_error( $terms ) ) {
        $carpeta_slug = $terms[0]->slug;
    }
    if ( empty( $carpeta_slug ) ) {
        echo '<div class="notice notice-error"><p>Este toolkit no tiene categoría asignada. Asigna una categoría antes de subir archivos.</p></div>';
    }
    $post = get_post( $post_id );
    $datos_archivos = get_field("datos_archivos", $post_id);
    // $delete = delete_row("datos_archivos", 2, $post_id);
    // $delete_field = delete_field("datos_archivos", $post_id);
    // echo '<pre>';
    // var_dump($datos_archivos);
    // echo '</pre>';
    // var_dump($delete_field);
    // die;
    ?>
    <div class="wrap">
        <h1>Administrar Archivos de <?= get_the_title($post) ?></h1>
        <div id="atk-s3-status" style="display:none;padding:8px 12px;margin-bottom:12px;border-left:4px solid #46b450;background:#f0fff0;font-weight:bold;">
            ✓ Upload S3 activo — carpeta: <code><?= esc_html($carpeta_slug) ?></code>
        </div>

        <form id="atk-files-form" method="post">
            <input type="hidden" name="post_id" value="<?= $post_id ?>">
            <table id="files" class="widefat">
                <thead>
                    <tr>
                        <th>Archivo</th>
                        <th>Idioma</th>
                        <th>Medidas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <body>
                    <?php if(!empty($datos_archivos)) {
                        foreach ($datos_archivos as $archivo) { ?>
                    <tr>
                        <td><input type="hidden" name="old[ruta_de_archivo][]" value="<?= $archivo["ruta_de_archivo"] ?>"><?= explode("/", $archivo["ruta_de_archivo"])[2] ?></td>
                        <td><input type="hidden" name="old[idioma][]" value="<?= $archivo["idioma"] ?>"><?= $archivo["idioma"] ?></td>
                        <input type="hidden" name="old[formato][]" value="<?= $archivo["formato"] ?>">
                        <input type="hidden" name="old[peso][]" value="<?= $archivo["peso"] ?>">
                        <input type="hidden" name="old[nombre_archivo][]" value="<?= $archivo["nombre_archivo"] ?>">
                        <td><input type="hidden" name="old[medidas][]" value="<?= $archivo["medidas"] ?>"><?= $archivo["medidas"] ?></td>
                        <td><a href="#"><span id="borrarFila" class="dashicons dashicons-trash"></span></a></td>
                    </tr>
                    <?php }
                    } ?>
                    <tr class="atk-new-row">
                        <td><input type="file" name="new_file[]"></td>
                        <td>
                            <select name="new[idioma][]" id="">
                                <option value="ES">Español</option>
                                <option value="EN">Inglés</option>
                                <option value="PT">Portugués</option>
                            </select>
                        </td>
                        <td><input type="text" name="new[medidas][]"></td>
                        <td></td>
                    </tr>
                </body>
            </table>
            <p class="submit">
                <input type="button" value="Añadir fila" class="button-primary" id="add-row">
                <input type="submit" value="Guardar" class="button-primary">
            </p>
        </form>

    </div>
    <script>
    var adminToolkitS3 = {
        nonce:   '<?= wp_create_nonce( 'admin_toolkit_s3_nonce' ) ?>',
        carpeta: '<?= esc_js( $carpeta_slug ) ?>',
        backUrl: '<?= esc_js( admin_url( "edit.php?post_type=toolkit&page=" . urlencode( WP_PLUGIN_DIR . "/admin-toolkit/admin/add-files.php" ) ) ) ?>'
    };
    console.log('[ATK] adminToolkitS3 definido:', adminToolkitS3);
    </script>
    <script src="<?= esc_url( plugins_url( 'js/s3-upload.js', __FILE__ ) ) ?>?v=1.0.3"></script>
    <?php
}
?>
<script>
    jQuery(document).ready(function($){
        $('#add-row').click(function() {
            let html = '<tr class="atk-new-row">\
                        <td><input type="file" name="new_file[]"></td>\
                        <td>\
                            <select name="new[idioma][]" id="">\
                                <option value="ES">Español</option>\
                                <option value="EN">Inglés</option>\
                                <option value="PT">Portugués</option>\
                            </select>\
                        </td>\
                        <td><input type="text" name="new[medidas][]"></td>\
                        <td></td>\
                    </tr>';
            $('#files > tbody:last-child').append(html);
        });

        $("#files").on("click", "#borrarFila", function(e) {
            e.preventDefault();
            $(this).closest("tr").remove();
        });
    });
</script>