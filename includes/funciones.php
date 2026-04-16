<?php 
date_default_timezone_set("America/Santiago"); 

/* 
function ietk_menu_administrador() {  
    $ruta_ietk_menu = plugin_dir_path( __FILE__ );  
    $ruta_ietk_menu = str_replace("includes","admin",$ruta_ietk_menu);
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    '01 - Data CSV',
    'manage_options',
    $ruta_ietk_menu . 'data-csv.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    '02 - Agregar archivos',
    'manage_options',
    $ruta_ietk_menu. 'agregar-archivos.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    '03 - Publicar',
    'manage_options',
    $ruta_ietk_menu. 'publicar.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    'Usuarios Toolkit',
    'manage_options',
    $ruta_ietk_menu. 'usuarios.php','' );
} 
*/ 
function ietk_menu_administrador() {  
    $ruta_ietk_menu = plugin_dir_path( __FILE__ );  
    $ruta_ietk_menu = str_replace("includes","admin",$ruta_ietk_menu);
	// add_submenu_page(
    // 'edit.php?post_type=toolkit',
    // 'toolkit',
    // 'Archivos Toolkit',
    // 'manage_options',
    // $ruta_ietk_menu . 'archivos-toolkit.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    'Archivos Toolkit',
    'manage_options',
    $ruta_ietk_menu . 'add-files.php','' );
	add_submenu_page(
    null,
    'toolkit',
    'Añadir archivos save',
    'manage_options',
    $ruta_ietk_menu . 'add-files-save.php','' );
	// add_submenu_page(
    // 'edit.php?post_type=toolkit',
    // 'toolkit',
    // 'Usuarios Toolkit - old',
    // 'manage_options',
    // $ruta_ietk_menu. 'usuarios_old.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    'Usuarios Toolkit',
    'manage_options',
    $ruta_ietk_menu. 'usuarios.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    'Descargas Toolkit',
    'manage_options',
    $ruta_ietk_menu. 'descargas.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    //'Data CSV',
    'manage_options',
    $ruta_ietk_menu. 'data-csv.php','' );
	// add_submenu_page(
    // 'edit.php?post_type=toolkit',
    // 'toolkit',
    // 'Agregar Archivos',
    // 'manage_options',
    // $ruta_ietk_menu. 'agregar-archivos.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    //'Publicar',
    'manage_options',
    $ruta_ietk_menu. 'publicar.php','' );
	add_submenu_page(
    'edit.php?post_type=toolkit',
    'toolkit',
    //'Dashboards',
    'manage_options',
    $ruta_ietk_menu. 'dashboards.php','' );
	add_submenu_page(
	'edit.php?post_type=toolkit',
	'Admin Toolkit Configuración',
	'Configuración AWS',
	'manage_options',
	'admin-toolkit-settings',
	'ietk_render_settings_page'
	);
}
add_action( 'admin_menu', 'ietk_menu_administrador' ); 


/***/ 
/*** VALIDA CAMPOS ***/ 
/***/  
function ietk_rgp2($objeto){  
    /*
    Request: Get And Post 
    By: Isaac Espinoza 
    */
    $temp='';
    if(isset($_REQUEST[$objeto])){ 
    $temp = trim($_REQUEST[$objeto]);	
    $temp = stripslashes($temp);		
    }	
    $temp = str_replace("'", "´", $temp);	
    return $temp;
}   

  
function ietk_clean_var2($var){ 
    $re_var = str_replace(" ", "_", $var);	
    $re_var = str_replace("-", "_", $re_var); 	
    $vowels = array("á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú");
    $onlyconsonants = str_replace($vowels, "", $re_var);	
    return $re_var; 
} 

function ietk_val_vacio2($valor){
    $re_valor = $valor;		
    $re_valor = strip_tags($re_valor);
    $re_valor = str_replace("%20", "", $re_valor);	
    $re_valor = str_replace("&nbsp;", "", $re_valor);	
    $re_valor = str_replace("<br>", "", $re_valor);	
    $re_valor = str_replace("<br />", "", $re_valor);	
    $re_valor = str_replace("   ", "", $re_valor);	
    $re_valor = str_replace("  ", "", $re_valor);
    $re_valor = str_replace(" ", "", $re_valor);
    $re_valor = htmlspecialchars($re_valor, ENT_QUOTES);
    $re_valor = str_replace("   ", "", $re_valor);	
    $re_valor = str_replace("  ", "", $re_valor);
    $re_valor = str_replace(" ", "", $re_valor);
    if(($re_valor=="")||($re_valor==" ")||($re_valor=="  ")||($re_valor=="&nbsp;")||($re_valor=="&nbsp;<br>")||($re_valor=="&nbsp;<br />")){return false;}
    else{if(strlen($re_valor)>0){ 	return true;  }
    else{ return false;	 }}
} 


/***/ 
/*** GENERA Y GUARDA IMAGENES Thumbail y Facebook - TOOLKIT ***/ 
/***/  
function ietk_guarda_imagen_destacada( $image_url, $post_id  ){
    $upload_dir = wp_upload_dir(); 
    $name_date=date('Y')."/".date('m')."";
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if(wp_mkdir_p($upload_dir['basedir']."/toolkit/previews/".$name_date."/"))
    $file = $upload_dir['basedir']."/toolkit/previews/".$name_date.'/' . $filename;
    else
    $file = $upload_dir['path'] . '/' . $filename;
    file_put_contents($file, $image_data); 
    $wp_filetype = wp_check_filetype($filename, null );
    $attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => sanitize_file_name($filename),
    'post_content' => '',
    'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    $res2= set_post_thumbnail( $post_id, $attach_id );
    $res1=update_post_meta($post_id, 'imagen_principal', $attach_id);
}
    
function ietk_guarda_imagen_facebook( $image_url, $post_id  ){
    $upload_dir = wp_upload_dir();
    $name_date=date('Y')."/".date('m')."";
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if(wp_mkdir_p($upload_dir['basedir']."/toolkit/fb/".$name_date."/"))
    $file = $upload_dir['basedir']."/toolkit/fb/".$name_date.'/' . $filename;
    else
    $file = $upload_dir['path'] . '/' . $filename;
    file_put_contents($file, $image_data);
    $wp_filetype = wp_check_filetype($filename, null );
    $attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => sanitize_file_name($filename),
    'post_content' => '',
    'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1=update_post_meta($post_id, 'imagen_facebook', $attach_id);
}
    

/***/
/*** UPLOADS TOOLKIT  ****/
/***/
// SVG deshabilitado: permite XSS almacenado sin sanitización de contenido.

if(!function_exists('ietk_bytesToSize1024')){ 
    function ietk_bytesToSize1024($bytes, $precision = 2) {
    $unit = array('B','KB','MB');
    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
    }	 
}

if(!function_exists('ietk_reArrayFiles')){ 
    function ietk_reArrayFiles(&$file_post) {
    $file_ary = array();
    $file_count = count($file_post['name']);
    $file_keys = array_keys($file_post); 
    for ($i=0; $i<$file_count; $i++) {
        foreach ($file_keys as $key) {
        $file_ary[$i][$key] = $file_post[$key][$i];
        }
    }
    return $file_ary;
    } 
} 



/**********************************************************************************************************/ 
/******************************************************************************** ietk_listarArchivos ***********/ 
/**********************************************************************************************************/  
function ietk_listarArchivos( $path, $tipo=null ){
    $dir = opendir($path);
    $cont=0; 
    if($tipo==null){  ?> 
        <ul class="lista_archivos"  >
        <?php 
        while ($elemento = readdir($dir)){ 
            $cont++; 
            if( $elemento != "." && $elemento != ".."){ 
                if( is_dir($path.$elemento) ){
                //echo "<p><strong>CARPETA: ". $elemento ."</strong></p>";
                }else{ 
                    $partes_ruta = pathinfo($path.$elemento);
                    $id_elemento=str_replace(" ","",$partes_ruta['filename']);
                    ?>
                    <li id="<?php echo $id_elemento;?>">
                        <input type="checkbox" name="archivos[]" value="<?php echo $elemento;?>" id="btn_<?php echo $id_elemento;?>" />
                        <label for="btn_<?php echo $id_elemento;?>"  >
                            <?php echo $elemento; ?>
                        </label> 
                    </li>
                    <?php    
                }
            } 
        } 
        ?>
        </ul>
        <?php 
    }else{ 
        if($tipo=="img"){   
            ?>
            <!-- <div class="row row-xsmall"> -->
            <table border="1">
            <?php 
            $cont_tr=0;
            while ($elemento = readdir($dir)){ 
                $cont++; 
                if( $elemento != "." && $elemento != ".."){ 
                    if( is_dir($path.$elemento) ){
                    //echo "<p><strong>CARPETA: ". $elemento ."</strong></p>";
                    }else{  
                        $cont_tr++; 
                        if($cont_tr==1){ ?> <tr> <?php }
                        $partes_ruta = pathinfo($path.$elemento);
                        $id_elemento=str_replace(" ","",$partes_ruta['filename']);
                        ?>
                        <!-- <div class="col-md-2 col-sm-6 li_file" id="<?php echo $id_elemento;?>"> -->
                        <td width="220" align="center" valign="top">
                            <label for="btn_<?php echo $id_elemento;?>"  >
                            <?php 
                            if( ($partes_ruta['extension']=="jpg") || ($partes_ruta['extension']=="png") || ($partes_ruta['extension']=="gif") || ($partes_ruta['extension']=="jpeg")){
                                ?> <img src="/_tk_para_subir/<?php echo $elemento;?>" alt="" height="100" /><?php 
                            }else{
                                ?><img src="/ico_file.png" alt="" height="100" /><?php 
                            }
                            ?>
                            <br /> 
                            <?php echo $elemento; ?>
                            </label> 
                            <br />
                            <input type="checkbox" name="archivos[]" value="<?php echo $elemento;?>" id="btn_<?php echo $id_elemento;?>" />
                        </td>
                        <!-- </div> -->
                        <?php    
                        if($cont_tr==6){ ?> 
                            </tr> 
                            <tr><td colspan="6"></td></tr>
                            <?php  
                            $cont_tr=0; 
                        }
                    }
                } 
            } 
            ?>
            <!-- </div>  --> 
            </table>
            <?php 
        } 
    } 
} 


function ietk_nombre_de_categoria($categoria){  
    $nombre_cat="";
    if(($categoria=="imagen")||($categoria=="imagenes")){ 
      $nombre_cat="Imágenes";
    }
    if($categoria=="videos"){
      $nombre_cat="Videos";
    }
    if($categoria=="presentaciones"){
      $nombre_cat="Presentaciones";
    }
    if($categoria=="footage"){
      $nombre_cat="Footage";
    }
    if($categoria=="infografias"){
      $nombre_cat="Infografías";
    } 
    if($categoria=="fondos de reuniones"){
      $nombre_cat="Fondos de reuniones";
    } 
    if($categoria=="lineamientos graficos"){
      $nombre_cat="Lineamientos gráficos";
    } 
    if($categoria=="campanas"){
      $nombre_cat="Campañas";
    } 
    if($categoria=="estudios"){
      $nombre_cat="Estudios";
    } 
    if($categoria=="Chilenas Creando Futuro"){
      $nombre_cat="Chilenas Creando Futuro";
    } 
    if($categoria=="Creando Futuro I"){
      $nombre_cat="Creando Futuro I";
    } 
    if($categoria=="Creando Futuro II"){
      $nombre_cat="Creando Futuro II";
    } 
    if($categoria=="Chile Inspira"){
      $nombre_cat="Chile Inspira";
    } 
    if($categoria=="COP"){
      $nombre_cat="COP";
    } 
    if($categoria=="Eclipse Antártica"){
      $nombre_cat="Eclipse Antártica";
    } 
    if($categoria=="Red Creadores de Futuro"){
      $nombre_cat="Red Creadores de Futuro";
    } 
    return $nombre_cat;  
  } 
  add_action( 'wp_ajax_ietk_nombre_de_categoria', 'ietk_nombre_de_categoria' );
