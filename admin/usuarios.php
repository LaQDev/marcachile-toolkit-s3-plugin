<?php

defined('ABSPATH') or die( "Bye bye" );
//Comprueba que tienes permisos para acceder a esta pagina
if (! current_user_can ('manage_options')) wp_die (__ ('No tienes suficientes permisos para acceder a esta página.'));

require_once(dirname(__FILE__) . '/../includes/tabla-usuarios.php');

$myListTable = new Tabla_Usuarios();
$myListTable->per_page = 15;
$myListTable->columns_data = array (
    "ID" => "ID",
    "display_name" => "Nombre",
    "user_email" => "Email",

    "user_registered" => "Fecha de Registro" ); 
$myListTable->sortable_columns = array(
    "ID" => array("ID", false),
    "display_name" => array("nombre", false),
    "user_email" => array("email", false));
    //"institucion" => array("institucion", false);
    //"pais" => array("pais", false));

echo '<div class="wrap"><h2>Usuarios Toolkit</h2>'; 
$myListTable->prepare_items();

echo '<form action="" method="GET">
        <input type="hidden" name="post_type" class="post_type_page" value="toolkit">
        <input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '"/>';
$myListTable->search_box( 'Buscar usuarios' , 'search_id' );
echo '</form>';

$myListTable->display(); 
echo '</div>';
?>

<script>
    jQuery(document).ready(function($){
        $("#btnDownload").click(function(e) {
            var begin_date = $("#begin_date").val();
            var end_date = $("#end_date").val();
            var s = $("#search_id-search-input").val();
            let url = "/admin-toolkit-downloads/download-usuarios.php?begin_date=" + begin_date + "&end_date=" + end_date + "&s=" + s;
            window.location.href = (url);
        });
    });
</script>