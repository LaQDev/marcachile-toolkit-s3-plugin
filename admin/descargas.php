<?php

defined('ABSPATH') or die( "Bye bye" );
//Comprueba que tienes permisos para acceder a esta pagina
if (! current_user_can ('manage_options')) wp_die (__ ('No tienes suficientes permisos para acceder a esta página.'));

require_once(dirname(__FILE__) . '/../includes/my-table.php');

global $wpdb; 
$result = $wpdb->get_results("SELECT toolkit_descargas.archivo_zip, toolkit_descargas.id_descarga, wp_users.display_name, toolkit_descargas.fecha_registro FROM toolkit_descargas join wp_users on wp_users.ID = toolkit_descargas.id_usuario", 'ARRAY_A'); 

$myListTable = new My_Table();
$myListTable->per_page = 15;
$myListTable->data = $result;
$myListTable->columns_data = array (
    "id_descarga" => "ID",
    "display_name" => "Nombre",
	"archivo_zip" => "Archivo descargado",
    "fecha_registro" => "Fecha de Registro" ); 
$myListTable->sortable_columns = array(
    "id_descarga" => array("id_descarga", false),
    "nombre" => array("nombre", false));

echo '<div class="wrap"><h2>Descargas Toolkit</h2>'; 
$myListTable->prepare_items(); 
$myListTable->display(); 
echo '</div>'; 