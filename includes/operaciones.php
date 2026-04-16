<?php  
use Aws\S3\S3Client;  
use Aws\Exception\AwsException; 
use Aws\S3\Exception\S3Exception;  


/**********************************************************************************************************/ 
/*********************** VERIFICAR SI YA SE CARGO AWS      ************************************************/ 
function loadAwsSdk() {
    if (function_exists('\Aws\is_valid_epoch')) {
        return true;
    } else {
        include_once plugin_dir_path( __FILE__ ) .'../vendor/autoload.php';  
    }
}

/**********************************************************************************************************/

/**
 * Retorna una instancia de S3Client configurada con credenciales desde wp_options.
 */
function ietk_get_s3_client() {
    static $client = null;

    if ( $client !== null ) {
        return $client;
    }

    $key    = get_option( 'admin_toolkit_aws_access_key', '' );
    $secret = get_option( 'admin_toolkit_aws_secret_key', '' );

    if ( empty( $key ) || empty( $secret ) ) {
        return new WP_Error(
            'missing_aws_credentials',
            'Las credenciales AWS no están configuradas. Ve a Admin Toolkit → Configuración AWS.'
        );
    }

    loadAwsSdk();
    try {
        $client = new Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => get_option( 'admin_toolkit_aws_region', 'us-east-1' ),
            'credentials' => new Aws\Credentials\Credentials( $key, $secret ),
        ]);
    } catch ( \Exception $e ) {
        return new WP_Error( 'aws_client_error', 'Error al conectar con AWS: ' . $e->getMessage() );
    }

    return $client;
}

/**
 * Retorna el nombre del bucket S3 desde wp_options.
 */
function ietk_get_s3_bucket() {
    return get_option( 'admin_toolkit_s3_bucket', 'cdn.marcachile2.redon.cl' );
}

/**********************************************************************************************************/
/******************************************************************************** FORM UPLOAD CSV ***********/
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_form_upload_csv', 'ietk_form_upload_csv' );
function ietk_form_upload_csv() {  
    if (  ! isset( $_POST['name_of_nonce_field'] )  || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'ietk_form_upload_csv_nonce') ) { 
        exit('The form is not valid'); 
    }  
    $action = sanitize_text_field( $_POST['action'] ?? '' );
    $errores_val =0;
    $msj_error  ="";
    if(ietk_val_vacio2($action)==false){
        $errores_val++;
        $msj_error.="<li>Formulario no válido.</li>";
    }
    if( (!isset($_FILES['archivo_csv']))||(empty($_FILES['archivo_csv']))||(empty($_FILES['archivo_csv']['name'])) ){
        $errores_val++;
        $msj_error.="<li>No has seleccionado ningún archivo.</li>";
    }
    if($errores_val==0){
        $dir_ruta = rtrim( ABSPATH, '/' );
        $dir_subida= $dir_ruta.'/_tk_csv/';

        $new_name=str_replace(" ","",$_FILES['archivo_csv']['name']);
        $new_name=str_replace("(","",$new_name);
        $new_name=str_replace(")","",$new_name);
        $new_name=str_replace("/","",$new_name);
        $new_name=str_replace("'","",$new_name);
        $new_name=str_replace("´","",$new_name);
        $new_name=ietk_clean_var2($new_name);
        $fichero_subido = $dir_subida . basename($new_name); 
      
        $path = $_FILES['archivo_csv']['name']; 
        $ext = pathinfo($path, PATHINFO_EXTENSION); 
        if($ext=="csv"){ 
            if (move_uploaded_file($_FILES['archivo_csv']['tmp_name'], $fichero_subido)) { 
                echo "<ul><li class='ok'>El archivo es válido y se subió con éxito.</li></ul>"; 
            } else {  
                echo "<ul><li>Error al subir archivo.</li></ul>"; 
            }
        }else{
            echo "<ul><li>El archivo no es válido. Sólo se permite formato .csv</li></ul>"; 
        } 

    }else{  
        ?>
        <ul><?php echo $msj_error; ?></ul>
        <?php 
    } 
    ?> 
    <script>
   //actualiza_lista_files2('ietk_after_form_upload_csv'); 
    </script>     
    <?php 
    exit;
}



/**********************************************************************************************************/ 
/******************************************************************************** FORM UPLOAD ARCHIVOS ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_form_upload_files', 'ietk_form_upload_files' );
function ietk_form_upload_files() {  

    //print "_POST <pre>"; print_r($_POST); print "</pre>";  
    //print "_FILES <pre>"; print_r($_FILES); print "</pre>";  

    if (  ! isset( $_POST['name_of_nonce_field'] )  || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'ietk_form_upload_files_nonce') ) { 
        exit('The form is not valid'); 
    }  

    $action = sanitize_text_field( $_POST['action'] ?? '' );
    $errores_val =0;
    $msj_error  ="";

    if(ietk_val_vacio2($action)==false){
        $errores_val++;
        $msj_error.="<li>Formulario no válido.</li>";
    }

    if( (!isset($_FILES['archivos']))||(empty($_FILES['archivos']))||(empty($_FILES['archivos']['name'][0])) ){
        $errores_val++;
        $msj_error.="<li>No has seleccionado ningún archivo.</li>";
    }

    if($errores_val==0){
        $file_ary = ietk_reArrayFiles($_FILES['archivos']);
        foreach ($file_ary as $file) {
            $dir_ruta = rtrim( ABSPATH, '/' );
            $dir_subida= $dir_ruta.'/_tk_para_subir/';
            $fichero_subido = $dir_subida . basename($file['name']);

            $path = $file['name'];
            $ext = pathinfo($path, PATHINFO_EXTENSION);

                if(file_exists($fichero_subido)) {
                    //chmod($fichero_subido,0755); //Change the file permissions if allowed
                    unlink($fichero_subido); //remove the file
                }

                if (move_uploaded_file($file['tmp_name'], $fichero_subido)) { 
                    echo "<h2>El archivo es válido y se subió con éxito.</h2>"; 
                    echo "archivo: ".basename($file['name'])."<br /><br />"; 
                } else {  
                    echo "<h2>Error al subir archivo!</h2>"; 
                    echo "archivo: ".basename($file['name'])."<br /><br />"; 
                }  

        }

    }else{  ?>
        <ul>
        <?php echo $msj_error;?>
        </ul>
        <?php 
    } 
    ?> 
    <script>
    //actualiza_lista_files2('ietk_after_form_upload_files'); 
    </script>     
    <?php 
    return true; 
}


/**********************************************************************************************************/ 
/******************************************************************************** AFTER FORM UPLOAD CSV ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_after_form_upload_csv', 'ietk_after_form_upload_csv' );
function ietk_after_form_upload_csv() {  
    ietk_listarArchivos("../_tk_csv/");
}

/**********************************************************************************************************/ 
/******************************************************************************** AFTER FORM UPLOAD CSV ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_after_form_upload_files', 'ietk_after_form_upload_files' );
function ietk_after_form_upload_files() {  
    ietk_listarArchivos("../_tk_para_subir/","img");
}




/**********************************************************************************************************/ 
/******************************************************************************** DELETE UPLOADED FILES ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_delete_files', 'ietk_delete_files' );
function ietk_delete_files() {  
    if (  ! isset( $_POST['name_of_nonce_field'] )  || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'ietk_delete_files_nonce') ) { 
        exit('The form is not valid'); 
    }  

    $carpeta_raw = sanitize_text_field( $_POST['carpeta'] ?? '' );
    // Whitelist de carpetas permitidas
    $carpetas_permitidas = [ '_tk_para_subir', '_tk_csv' ];
    $carpeta = in_array( $carpeta_raw, $carpetas_permitidas, true ) ? $carpeta_raw : '';

    $errores_val =0;
    $msj_error  ="";

    if ( empty( $carpeta ) ) {
        $errores_val++;
        $msj_error.="<li>Carpeta no válida.</li>";
    }

    if(isset($_POST["archivos"])) {
        if(empty($_POST["archivos"])){
            $errores_val++;
            $msj_error.="<li>No has seleccionado ningún archivo.</li>";
        }
    }
    else{
        $errores_val++;
        $msj_error.="<li>- No has seleccionado ningún archivo.</li>";
    }

    if($errores_val==0){
        $base_dir = rtrim( ABSPATH, '/' );
        foreach($_POST["archivos"] AS $archivo){
            $archivo = basename( sanitize_text_field( $archivo ) );
            if ( empty( $archivo ) ) continue;
            $partes_ruta = pathinfo($carpeta.$archivo);
            $id_elemento=str_replace(" ","",$partes_ruta['filename']);
            unlink( $base_dir . '/' . $carpeta . '/' . $archivo );
            ?>
            <script>
            $("#<?php echo $id_elemento;?>").remove();
            </script>
            <?php 
        }
    }else{ ?>
        <ul>
            <?php echo $msj_error;?>
        </ul>
        <?php 
    } 
}



/**********************************************************************************************************/ 
/******************************************************************************** FORM EJECUTA CSV ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_form_ejecuta_csv', 'ietk_form_ejecuta_csv' );
function ietk_form_ejecuta_csv() {  
    if (  ! isset( $_POST['name_of_nonce_field'] )  || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'ietk_form_ejecuta_csv_nonce') ) { 
        exit('The form is not valid'); 
    }  

    $archivo = sanitize_text_field( $_POST['archivo'] ?? '' );
    $carpeta = sanitize_text_field( $_POST['carpeta'] ?? '' );
    $accion  = sanitize_text_field( $_POST['accion']  ?? '' );
    $cantidad = intval( $_POST['cantidad'] ?? 0 );

    global $wpdb;
    $errores_val =0;
    $msj_error  ="";
    $prefijo="_tk_para_subir/";
    if(ietk_val_vacio2($archivo)==false){
        $errores_val++;
        $msj_error.="<li>No has seleccionado ningún archivo.</li>";
    }

    if(ietk_val_vacio2($carpeta)==false){
        $errores_val++;
        $msj_error.="<li>Debes seleccionar una categoría.</li>";
    }

    if(ietk_val_vacio2($accion)==false){
        $errores_val++;
        $msj_error.="<li>Selecciona si deseas Agregar nuevos elementos o sólo previsualizar.</li>";
    }
    /*
    if(isset($s3_accion)){
        if(ietk_val_vacio2($s3_accion)==false){ 
            $errores_val++;  
            $msj_error.="<li>Selecciona si las imagenes se cargan desde el servidor o ya están en el s3.</li>";
        }
    }else{ 
        $errores_val++;  
        $msj_error.="<li>- Selecciona si las imagenes se cargan desde el servidor o ya están en el s3.</li>";
    } 
    */

    if( $cantidad <= 0 ){
        $errores_val++;
        $msj_error.="<li>Debes indicar el número de archivos.</li>";
    }

    if($errores_val==0){

        $archivo_csv=$archivo;
        $prefijo.=$carpeta; 

        $ingresados=0;
        $rechazados=0;
        $repetidos=0;
        $adjuntos=0;
        $array_no_encontrados=array(); 
        $array_repetidos=array(); 
        $array_error_s3=array(); 
        $array_repetidos_s3=array(); 
        $archivos_faltantes=0;
        $array_csv=array();
        $nueva_cantidad=($cantidad)-1;

        if (($gestor = fopen("../_tk_csv/".$archivo_csv."", "r")) !== FALSE) {
            $cont=0; 
            while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {   
                $total_datos = count($datos);
                if($cont>0){
                    for ($c=0; $c < $total_datos; $c++) { 
                        if((isset($datos[0]))&&(isset($datos[8]))){ 
                            if($c==0){  $array_csv[$cont]["id"]=$cont; $array_csv[$cont]["categorias"]=$datos[$c]; }    
                            if($c==1){  $array_csv[$cont]["titulo"]=$datos[$c]; }    
                            if($c==2){  $array_csv[$cont]["descripcion"]=$datos[$c]; }    
                            if($c==3){  $array_csv[$cont]["temas"]=$datos[$c]; }    
                            if($c==4){  $array_csv[$cont]["autor"]=$datos[$c]; }  
                            if($c==5){  $array_csv[$cont]["idiomas"]=$datos[$c]; }  
                            if($c==6){  $array_csv[$cont]["licencia"]=$datos[$c]; } 
                            if($c==7){  $array_csv[$cont]["palabras_claves"]=$datos[$c]; }
                            //if($c==8){  $array_csv[$cont]["archivo_descarga"]=strtolower($datos[$c]); }
                            if($c==8){  $array_csv[$cont]["archivo_descarga"]=$datos[$c]; }
                            //if($c==9){  $array_csv[$cont]["achivo_preview"]=strtolower($datos[$c]); }
                            if($c==9){  $array_csv[$cont]["achivo_preview"]=$datos[$c]; }
                            if($c==11){ $array_csv[$cont]["medidas"]=$datos[$c]; } 
                            //formato 
                            if($c==10){ $array_csv[$cont]["fb_archivo"]=$datos[$c]; } 
                            if($c==13){ $array_csv[$cont]["adjunto"]=$datos[$c]; } 
                            if($c==14){ $array_csv[$cont]["nombre_archivo"]=$datos[$c]; } 
                        }
                    } 
                }
                $cont++; 
            } 
        }else{ 
            echo "error al cargar archivo <b>".$archivo_csv."<br />";  
        }
        fclose($gestor); 
      
        //print "array_csv <pre>"; print_r($array_csv); print "</pre>";  

        /*   
        global $s3; 
        echo "s3_credentials <b>".$s3_credentials."<br />";  
        echo "s3_bucket <b>".$s3_bucket."<br />";  
        
        global $s3_credentials; 
        global $s3_bucket;
        */ 
        $s3_bucket = isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] == "prod" ? "toolkitnew" : 'toolkitnew';
        $s3 = ietk_get_s3_client();
        if ( is_wp_error( $s3 ) ) {
            echo 'Error de credenciales S3: ' . esc_html( $s3->get_error_message() );
            return;
        }

 
                            
        ?>

        <br /><br /> 

        <table cellpadding="0" cellspacing="0" width="100%"> 
            <tr>
                <td>id</td>
                <td>1-categoria</td>
                <td>2-título de imagen</td>
                <td>3-Descripción (oculto)</td>
                <td>4-Temas</td>
                <td>5-autor</td>
                <td>6-idiomas</td>
                <td>7-Licencia</td>
                <td>8-palabras_claves</td>
                <td>9-nombre de archivo para descarga</td>
                <td>10-nombre de archivo indexacion</td>
                <td>11-medidas</td>
                <td>12-facebook</td>
                <td>POST</td>
            </tr>
            
            <?php 
            $contabilizados=0;
            $error_s3=0;
            $repetidos_s3=0;
            foreach ($array_csv as $name_campo ) {  
                if((ietk_val_vacio2($name_campo["titulo"])==true)&&(ietk_val_vacio2($name_campo["archivo_descarga"])==true)){ 
                    $contabilizados++; 
                    $error_img=0;
                    $post_id_old = 0;
                    $categorias=$name_campo["categorias"];
                    $nombre_de_categoria=ietk_nombre_de_categoria($categorias); 
                    //$nombre_de_categoria=$categorias; 
                    $titulo=$name_campo["titulo"];
                    $descripcion=$name_campo["descripcion"];
                    $temas=$name_campo["temas"];
                    $autor=$name_campo["autor"];
                    $idiomas=$name_campo["idiomas"];
                    // if (strpos($name_campo["idiomas"], ',') !== false) {
                    //     $idiomas = array_map('trim', explode(',', $name_campo["idiomas"])); 
                    // }
                    $licencia=$name_campo["licencia"];
                    $medidas=$name_campo["medidas"];
                    $palabras_claves=$name_campo["palabras_claves"];
                    $archivo_descarga=$name_campo["archivo_descarga"];
                    $formato = "";
                    if($archivo_descarga){
                        $ext_archivo=substr($archivo_descarga, -6); 
                        $ext_name=explode(".",$ext_archivo);
                        $formato = $ext_name[1];
                        if($formato=="jpe"){
                            $formato="jpg";
                        }
                    }
                    $achivo_preview=$name_campo["achivo_preview"];
                    $fb_archivo=$name_campo["fb_archivo"];
                    $adjunto=$name_campo["adjunto"];
                    $nombre_archivo=$name_campo["nombre_archivo"];
                    //$fb_archivo=str_replace("0_","fb_",$achivo_preview);
                    ?> 
                    <tr>
                    <td><?php echo $name_campo["id"];?></td>
                    <td><?php echo $categorias;?></td>
                    <td><?php echo $titulo;?></td>
                    <td><?php echo $descripcion;?></td>
                    <td><?php echo $temas;?></td>
                    <td><?php echo $autor;?></td>
                    <td><?php echo $idiomas;?></td>
                    <td><?php echo $licencia;?></td>
                    <td><?php echo $palabras_claves;?></td>
                    <?php 
                    //$nombre_fichero="../".$prefijo."/".$archivo_descarga."";
                    $nombre_fichero="../".$prefijo."/"."original/".$archivo_descarga."";  
                    if (!file_exists($nombre_fichero)) {  
                        $error_img++;
                        $archivos_faltantes++;
                        $array_no_encontrados[]=$archivo_descarga; 
                        ?>
                        <td style="background-color:#ee5b5b; color:white;">Archivo no encontrado: <?php echo $nombre_fichero;?></td>
                        <?php 
                    }else{ 
                        ?>
                        <td align="center"><?php echo $archivo_descarga;?><br /><img src="<?php echo $nombre_fichero;?>" alt="" height="64" /></td>
                        <?php 
                    } 
                    //$nombre_fichero="../".$prefijo."/".$achivo_preview."";
                    $nombre_fichero="../".$prefijo."/"."previews/".$achivo_preview."";  
                    //$achivo_preview=$achivo_preview;
                    if (!file_exists($nombre_fichero)) {  
                        $error_img++;
                        $archivos_faltantes++;
                        $array_no_encontrados[]=$achivo_preview; 
                        ?>
                        <td style="background-color:#ee5b5b; color:white;">Imagen no encontrada: <?php echo $nombre_fichero;?></td>
                        <?php 
                    }else{ 
                        ?>
                        <td align="center"><?php echo $achivo_preview;?><br /><img src="<?php echo $nombre_fichero;?>" alt="" height="64" /></td>
                        <?php 
                    } 
                    ?>
                    <td><?php echo $medidas;?></td>
                    <!-- <td><?php echo $nombre_de_categoria;?></td> -->
                    <!-- <td><?php echo $formato;?></td> -->
                    <?php 
                    //$nombre_fichero="../".$prefijo."/".$fb_archivo."";
                    $nombre_fichero="../".$prefijo."/"."fb/".$fb_archivo."";  
                    if (!file_exists($nombre_fichero)) {  
                        $error_img++;
                        $archivos_faltantes++;
                        $array_no_encontrados[]=$fb_archivo; 
                        ?>
                        <td style="background-color:#ee5b5b; color:white;">Imagen no encontrada: <?php echo $nombre_fichero;?></td>
                        <?php 
                    }else{  ?>
                        <td align="center"><?php echo $fb_archivo;?><br /><img src="<?php echo $nombre_fichero;?>" alt="" height="64" /></td>
                        <?php 
                    }  
                
                    $permiso=0;
                    if($accion=="agregar"){ 
                        if($error_img==0){ 
                            $permiso=1;
                        }
                    }else{ 
                        //solo previsualiza
                    }

                    $get_posts_id = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_title = '%s' AND post_type = '%s' AND post_status = '%s'", $titulo, "toolkit", "publish" ) );
                    $categories_array = array();
                    $autor_array = array();
                    $pair_array = array();
                    // $found_post = post_exists($titulo,'','','toolkit');
                    if(count($get_posts_id)>0){
                        foreach ($get_posts_id as $post_id_tmp) {
                            $found_cat_obj = wp_get_object_terms( $post_id_tmp, 'categorias' );
                            $found_cat_name = $found_cat_obj[0]->name;
                            array_push($categories_array, $found_cat_name);
                            $autor_tmp = get_post_meta($post_id_tmp, 'autor', true);
                            array_push($autor_array, $autor_tmp);
                            $pair_tmp = $titulo . '_' . $found_cat_name . '_' . $autor_tmp;
                            array_push($pair_array, $pair_tmp);
                        }
                    }
                    if($adjunto == 'SI' && count($get_posts_id) > 0){
                        $pair_string = $titulo . '_' . $categorias . '_' . $autor;
                        $pos = in_array($pair_string, $pair_array);
                        $post_id_old = $get_posts_id[$pos];
                    } else {
                        if ( count($get_posts_id)>0 && in_array($nombre_de_categoria, $categories_array) && in_array($autor, $autor_array)) { 
                            // $get_post_status= get_post_status ( $found_post ); 
                            // if($get_post_status=="publish"){
                                $permiso=0;
                                $repetidos++; 
                                $array_repetidos[]=$titulo; 
                            // }
                        }
                    }

                    if($permiso==1){
                        //if( (isset($s3_accion))&&($s3_accion=="subir") ){  
                        if($permiso==$permiso){  
                          
                            $keyname = "descargas/".$carpeta."/"; 
                            $ruta_file_s3=$keyname.$archivo_descarga;                            

                            try {
                                $s3->headObject(array(
                                    'Bucket' => $s3_bucket,
                                    'Key'    => $ruta_file_s3,
                                ));
                                $existe = true;
                                //echo "true "; 
                            } catch (Aws\S3\Exception\S3Exception $e) { 
                                //echo "ERROR"; 
                                $existe = false;
                                //echo $e->getMessage() . PHP_EOL;
                            } 

                            $agrego_img_s3=0;
                            if($existe){ 
                                //no la sube  
                                $repetidos_s3++; 
                                $array_repetidos_s3[]=$archivo_descarga; 
                            }else{ 
                                try {
                                    $putObject=$s3->putObject([
                                        'Bucket' => $s3_bucket,
                                        'Key'    => $keyname.$archivo_descarga,
                                        'Body'   => fopen('../'.$prefijo.'/original/'.$archivo_descarga.'', 'r'),
                                        'ACL'    => 'public-read',
                                    ]);
                                } catch (Aws\S3\Exception\S3Exception $e) { 
                                    //echo "ERROR"; 
                                } 
                                if($putObject){ 
                                    $agrego_img_s3=1;
                                    //Agregó imagen 
                                }else{ 
                                    //error al subir 
                                    $permiso=0;
                                    $error_s3++; 
                                    $array_error_s3[]=$archivo_descarga; 
                                } 
                            } 

                        }
                    }


                    if($permiso==1 && $adjunto != 'SI'){  
                        $my_post = array(
                            'post_title' => $titulo,
                            'post_content' => $descripcion,
                            'post_status' => 'publish', 
                            'post_type' => 'toolkit',
                        ); 

                        $post_id_new = wp_insert_post( $my_post ); 

                        $ext = pathinfo('descargas/'.$carpeta.'/'.$archivo_descarga.'', PATHINFO_EXTENSION); 
                        $filesize = filesize('../'.$prefijo.'/original/'.$archivo_descarga.''); 
                        $peso=($filesize/1000);
                        $peso=round($peso);

                        ?> 
                        <td><?php echo $post_id_new;?> - <?php echo $filesize;?></td>
                        <?php 

                        // if( (empty($idiomas))||($idiomas="")){ 
                        //     $idiomas="ES";
                        // } 
                        // if( (!empty($idiomas))&&($idiomas!="")){ 
                        //     add_post_meta($post_id_new, 'idiomas', $idiomas ); 
                        // } 
                        if( (!empty($autor))&&($autor!="")){
                            add_post_meta($post_id_new, 'autor', $autor ); 
                        } 
                        if( (!empty($licencia))&&($licencia!="")){
                            add_post_meta($post_id_new, 'licencia', $licencia ); 
                        } 
                        // if( (!empty($medidas))&&($medidas!="")){
                        //     add_post_meta($post_id_new, 'medidas', $medidas ); 
                        // } 
                        // if( (!empty($formato))&&($formato!="")){ 
                        //     if($formato=="jpeg"){ $formato="jpg"; }
                        //     add_post_meta($post_id_new, 'formatos', $formato ); 
                        // }else{
                        //     if($ext=="jpeg"){ $ext="jpg"; }
                        //     add_post_meta($post_id_new, 'formatos', $ext ); 
                        // }
                        if( (!empty($temas))&&($temas!="")){
                            $temas_=explode(",",$temas);
                            wp_set_object_terms( $post_id_new, $temas_, 'temas' ); 
                        } 
                        if( (!empty($palabras_claves))&&($palabras_claves!="")){
                            $palabras_=explode(",",$palabras_claves);
                            wp_set_object_terms( $post_id_new, $palabras_, 'palabras_relacionadas' );  
                        }
                        if( (!empty($nombre_de_categoria))&&($nombre_de_categoria!="")){ 
                            wp_set_object_terms( $post_id_new, $nombre_de_categoria, 'categorias' );                    
                        }

                        // add_post_meta($post_id_new, 'peso', $peso ); 
                        ietk_guarda_imagen_destacada('../'.$prefijo.'/previews/'.$achivo_preview.'', $post_id_new ); 
                        ietk_guarda_imagen_facebook( '../'.$prefijo.'/fb/'.$fb_archivo.'', $post_id_new ); 
                        //if($agrego_img_s3==1){ 
                            // add_post_meta($post_id_new, 'ruta_s3', 'descargas/'.$carpeta.'/'.$archivo_descarga.'');       
                            if($ext=="jpeg"){ $ext="jpg"; }                      
                            $row_add_s3 = array(
                                'nombre_archivo' => $nombre_archivo,
                                'ruta_de_archivo' => 'descargas/'.$carpeta.'/'.$archivo_descarga.'',
                                'idioma'   => $idiomas,
                                'formato'   => $ext,
                                'peso'  => ''.$peso.' KB',
                                // 'licencia'   => $licencia,
                                'medidas'   => $medidas
                            );
                            $res3=add_row('datos_archivos', $row_add_s3, $post_id_new); 
                        //}

                        /* 
                        //fopen('../'.$prefijo.'/'.$archivo_descarga.'', 'r'),
                        */
                        $ingresados++; 
                    } 
                    if ($adjunto == 'SI' && !empty($post_id_old)){
                        $ext = pathinfo('descargas/'.$carpeta.'/'.$archivo_descarga.'', PATHINFO_EXTENSION); 
                        $filesize = filesize('../'.$prefijo.'/original/'.$archivo_descarga.''); 
                        $peso=($filesize/1000);
                        $peso=round($peso);

                        // if( (empty($idiomas))||($idiomas="")){ 
                        //     $idiomas="ES";
                        // } 

                        if($ext=="jpeg"){ $ext="jpg"; } 
                        $row_add_s3 = array(
                            'nombre_archivo' => $nombre_archivo,
                            'ruta_de_archivo' => 'descargas/'.$carpeta.'/'.$archivo_descarga.'',
                            'idioma'   => $idiomas,
                            'formato'   => $ext,
                            'peso'  => ''.$peso.' KB',
                            // 'licencia'   => $licencia,
                            'medidas'   => $medidas
                        );
                        $res3=add_row('datos_archivos', $row_add_s3, $post_id_old); 
                        $adjuntos++;
                    }
                    ?>
                    </tr>
                    <?php 

                    if($error_img>0){  
                        //falta_img 
                        $rechazados++; 
                        ?>
                        <!-- 
                        <tr> 
                            <td colspan='15' style='background:cyan; height:16px;'>falta_img - <?php echo $error_img;?></td>
                        </tr> 
                        <tr> 
                            <td colspan='15' style='background:yellow; height:16px;'>rechazados - <?php echo $rechazados;?></td>
                        </tr> 

                        <tr> 
                            <td colspan='15' style='background:green; height:16px;'>archivos_faltantes - <?php echo $archivos_faltantes;?></td>
                        </tr> 
                        -->
                        <?php 
                    }else{
                        if( count($get_posts_id)>0 && in_array($nombre_de_categoria, $categories_array) && in_array($autor, $autor_array)  && $adjunto != 'SI') {    
                            ?> 
                            <tr> 
                                <td colspan='15' style='background:red; height:16px;'>repetidos  <?php echo $repetidos;?></td>
                            </tr> 
                            <?php 
                        } else if ($adjunto == 'SI') {    
                            ?> 
                            <tr> 
                                <td colspan='15' style='background:orange; height:16px;'>adjunto de  <?php echo $post_id_old;?></td>
                            </tr> 
                            <?php 
                        }else{
                            // aprobado   
                            if( (isset($s3_accion))||($s3_accion=="subir") ){  
                                // borra imagen 
                                /* 
                                unlink('/var/www/html/'.$prefijo.'/previews/'.$achivo_preview.''); 
                                unlink( '/var/www/html/'.$prefijo.'/fb/'.$fb_archivo.'' ); 
                                unlink( '/var/www/html/'.$prefijo.'/original/'.$archivo_descarga.'' ); 
                                */ 
                            } 

                        }
                    } 
                    if($contabilizados>$nueva_cantidad){
                        break; 
                    }  
                } 
            }
            //End Foreach 
     
        ?>
        </table> 

        <br /><hr /><br /> 

        <?php 
        if(!empty($array_no_encontrados)){
            $array_log=implode(', ',$array_no_encontrados);
        }else{
            $array_log="";
        }
        if(!empty($array_repetidos)){
            $array_repetidos=implode(', ',$array_repetidos);
        }else{
            $array_repetidos="";
        }
        if(!empty($array_error_s3)){
            $array_error_s3=implode(', ',$array_error_s3);
        }else{
            $array_error_s3="";
        }
        if(!empty($array_repetidos_s3)){
            $array_repetidos_s3=implode(', ',$array_repetidos_s3);
        }else{
            $array_repetidos_s3="";
        }
        
        //print "array_no_encontrados <pre>";    print_r($array_no_encontrados);     print "</pre>"; 

        $fecha = date("Y-m-d H:i:s"); 
        /*
        $wpdb->insert("tb_log_cargas", array(
            "archivo" => $archivo_csv,
            "categoria" => $carpeta,
            "accion" => $accion,
            "contabilizados" => $contabilizados,
            "ingresados" => $ingresados,
            "rechazados" => $rechazados,
            "repetidos" => $repetidos,
            "repetidos_s3" => $repetidos_s3,
            "array_log" => $array_log,
            "array_repetidos" => $array_repetidos,
            "array_repetidos_s3" => $array_repetidos_s3,
            "array_error_s3" => $array_error_s3,
            "archivos_faltantes" => $archivos_faltantes,
            "fecha_registro" => $fecha,
        )); 
        //$lastid = $wpdb->insert_id;   
        */
       
        $ruta_a_buscar3="wp-content/uploads/"; 
        $ruta_archivos3=get_home_path().$ruta_a_buscar3; 
        // ies3_lectura_rutas($ruta_archivos3); 

        ?> 
        <div class="content_plugin">
            <ul>
                <li>contabilizados <?php echo $contabilizados;?></li>
                <li>aprobados <?php echo $ingresados;?></li>
                <li>rechazados <?php echo $rechazados;?></li>
                <li>repetidos <?php echo $repetidos;?></li>
                <li>adjuntos <?php echo $adjuntos;?></li>
                <li>errores al subir imagenes con amazon <?php echo $error_s3;?></li>
                <li>repetidos amazon <?php echo $repetidos_s3;?></li>
            </ul>
            <?php 
            //print "<pre>"; print_r($array_no_encontrados); print "</pre>";
            ?>
        </div>

        <br /><hr /><br /> 

        <?php  
    }
    else{   
        ?> 
        <div class="content_plugin">
            <ul>
                <?php echo $msj_error;?>
                <li><a href="admin.php?page=admin-toolkit%2Fadmin%2Fformulario.php">volver</a></li>
            </ul>
        </div>
        <?php 		
    } 
}






/**********************************************************************************************************/ 
/******************************************************************************** MUESTRA FORM PARA AGREGAR FORMULARIO ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_form_agrega', 'ietk_form_agrega' );
function ietk_form_agrega() {  

    //echo "_POST <br/>"; 

    //print_r($_POST); 
    $posts_ = get_post( $_POST['id'] );

    ?>

    <section>
        <h1><?php echo $posts_->post_title;?></h1>
        <h2>Subir archivos</h2>
        <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" name="ietk_form_upload_archivos_toolkit" id="ietk_form_uploaietk_form_upload_archivos_toolkitd_files" class="formulario" enctype="multipart/form-data"> 
            <div>
                <label for="">Archivo:</label> 
                <input class="fileUpload_avatar" type="file"  name="archivos[]" multiple /> 
            </div>
            <div>
                <label for="">Categoría:</label> 
                <select name="categoria">
                    <option value="footage">Footage</option>
                    <option value="imagenes">Imágenes</option>
                    <option value="infografias">Infografías</option>
                    <option value="presentaciones">Presentaciones</option>
                    <option value="videos">Videos</option>
                    <option value="test">test</option>
                </select> 	
            </div>
            <div>
                <input type="submit" name="enviar" id="enviar" />
            </div> 
            <input type="hidden" name="id" value="<?php echo $_POST['id'];?>">
            <input type="hidden" name="action" value="ietk_form_upload_archivos_toolkit">
            <?php wp_nonce_field( 'ietk_form_upload_archivos_toolkit_nonce', 'name_of_nonce_field' ); ?>
            <div id="ajax_results" class="ajax_results"></div> 
        </form> 
    </section>

    <script>
    jQuery(document).ready(function($) { 	
        $(".formulario").submit(function() {  	    
            var formu=$(this); 
            formu.find('.ajax_results').html("<h3>enviando...</h3>");  
            var id_form=formu.attr("id"); 
            var formData  = new FormData(document.getElementById(id_form));       
            $.ajax({ 
            type: 'POST',
                url: formu.attr("action"),
                data: formData,
                cache: false,
                contentType: false,
                processData: false, 
                success: function(data) {	 
                    formu.find('.ajax_results').html(data); 
                }
            }); 
            return false; 			
        }); 	
    }); 
    </script>
    <?php 
}

/**********************************************************************************************************/ 
/******************************************************************************** FORM UPLOAD ARCHIVOS ***********/ 
/**********************************************************************************************************/  
add_action( 'wp_ajax_ietk_form_upload_archivos_toolkit', 'ietk_form_upload_archivos_toolkit' );
function ietk_form_upload_archivos_toolkit() {  

    //print "_POST <pre>"; print_r($_POST); print "</pre>";  
    //print "_FILES <pre>"; print_r($_FILES); print "</pre>";  

    if (  ! isset( $_POST['name_of_nonce_field'] )  || ! wp_verify_nonce( $_POST['name_of_nonce_field'], 'ietk_form_upload_archivos_toolkit_nonce') ) { 
        exit('The form is not valid'); 
    }  

    $action    = sanitize_text_field( $_POST['action']    ?? '' );
    $categoria = sanitize_text_field( $_POST['categoria'] ?? '' );
    $id        = intval( $_POST['id'] ?? 0 );

    $errores_val =0;
    $msj_error  ="";

    if(ietk_val_vacio2($action)==false){
        $errores_val++;
        $msj_error.="<li>Formulario no válido.</li>";
    }

    if( (!isset($_FILES['archivos']))||(empty($_FILES['archivos']))||(empty($_FILES['archivos']['name'][0])) ){    
        $errores_val++;  
        $msj_error.="<li>No has seleccionado ningún archivo.</li>";
    }

    if($errores_val==0){
        $s3 = ietk_get_s3_client();
        if ( is_wp_error( $s3 ) ) {
            echo 'Error de credenciales S3: ' . esc_html( $s3->get_error_message() );
            return;
        }
        $count_files=count($_FILES['archivos']['name']);
        //echo "count ".count($_FILES['archivos']['name'])." <br />"; 
        $file_ary = ietk_reArrayFiles($_FILES['archivos']);                  
        foreach ($file_ary as $file) {   

            //echo "file_ary - ".$file['name']."<br />"; 
            $dir_subida = rtrim( ABSPATH, '/' ) . '/_tk_para_subir/';
            $fichero_subido = $dir_subida . basename($file['name']);

            $path = $file['name'];
            $ext = pathinfo($path, PATHINFO_EXTENSION); 

            if(file_exists($fichero_subido)) {
                //chmod($fichero_subido,0755); //Change the file permissions if allowed
                unlink($fichero_subido); //remove the file
            }

            if (move_uploaded_file($file['tmp_name'], $fichero_subido)) { 
                echo "<h2>El archivo es válido y se subió con éxito.</h2>"; 
                echo "archivo: ".basename($file['name'])."<br /><br />"; 
            } else {  
                echo "<h2>Error al subir archivo!</h2>"; 
                echo "archivo: ".basename($file['name'])."<br /><br />"; 
            }  

            $keyname = "descargas/".$categoria."/"; 
            $ruta_file_s3=$keyname.basename($file['name']);      

            //echo "<h2>ruta_file_s3 ".$ruta_file_s3."</h2>";
            $s3_bucket = isset($_ENV["APP_ENV"]) && $_ENV["APP_ENV"] == "prod" ? "cdn.toolkit.cl" : 'cdn.toolkit.cl';

            $existe_archivo=0; 
            try {
                $s3->getObject(array(
                    'Bucket' => $s3_bucket,
                    'Key'    => $ruta_file_s3,
                ));
                $existe_archivo = 1;
            } catch (Aws\S3\Exception\S3Exception $e) { 
                //echo $e->getMessage() . PHP_EOL;
            } 

            $ruta_local="../_tk_para_subir/".basename($file['name']).""; 
            //echo "<h2>ruta_local ".$ruta_local."</h2>"; 
            $ext = pathinfo($ruta_local, PATHINFO_EXTENSION); 
            //echo "<h2>ext ".$ext."</h2>"; 

            if($existe_archivo==1){
                $nombre_reemplaza=str_replace(".".$ext,"-".time().".".$ext,basename($file['name']));
                $ruta_file_s3=$keyname.$nombre_reemplaza; 
            }
            //echo "<h2>ruta_file_s3 ".$ruta_file_s3."</h2>"; 

            try {
                $putObject=$s3->putObject([
                    'Bucket' => $s3_bucket,
                    'Key'    => $ruta_file_s3,
                    'Body'   => fopen($ruta_local, 'r'),
                ]);
            } catch (Aws\S3\Exception\S3Exception $e) { 
                echo "ERROR <br />"; 
            } 
            if($putObject){ 
                //Agregó imagen 
                echo "Agregó imagen  <br />"; 
            }else{ 
                //error al subir 
                echo "error al subir  <br />";
            }        
            $filesize = filesize($ruta_local); 
            $peso=($filesize/1000);
            $peso=round($peso);

            global $wpdb; 
            update_post_meta($id, 'peso', $peso ); 
            if($ext=="jpeg"){ $ext="jpg"; }
            update_post_meta($id, 'formatos', $ext ); 
            update_post_meta($id, 'ruta_s3', $ruta_file_s3 ); 
            update_post_meta($id, 'ruta_de_archivo', $ruta_file_s3 ); 
            add_post_meta($id, 'ruta_de_archivo', $ruta_file_s3 ); 
        }

    }else{  ?>
        <ul>
            <?php echo $msj_error;?>
        </ul>
        <?php 
    }
    ?>
    <script>
    //actualiza_lista_files2('ietk_after_form_upload_files');
    </script>
    <?php
    //return true;
}

/**********************************************************************************************************/
/************************* S3 DIRECT UPLOAD — SIMPLE PRESIGNED URL (< 5MB) *****************************/
/**********************************************************************************************************/
add_action( 'wp_ajax_s3_get_simple_presigned_url', 'ietk_s3_get_simple_presigned_url' );
function ietk_s3_get_simple_presigned_url() {
    check_ajax_referer( 'admin_toolkit_s3_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Sin permisos'], 403 );

    $filename     = sanitize_file_name( $_POST['filename'] ?? '' );
    $carpeta      = sanitize_text_field( $_POST['carpeta'] ?? '' );
    $content_type = sanitize_text_field( $_POST['content_type'] ?? 'application/octet-stream' );

    if ( empty( $filename ) || empty( $carpeta ) ) {
        wp_send_json_error( ['message' => 'Parámetros inválidos'] );
    }

    $carpeta = trim( $carpeta, '/' );
    if ( strpos( $carpeta, '..' ) !== false || strpos( $carpeta, '/' ) !== false ) {
        wp_send_json_error( ['message' => 'Carpeta inválida'] );
    }

    $s3 = ietk_get_s3_client();
    if ( is_wp_error( $s3 ) ) {
        wp_send_json_error( ['message' => $s3->get_error_message()] );
    }

    $bucket = ietk_get_s3_bucket();
    $key    = "descargas/{$carpeta}/{$filename}";

    // Verificar si ya existe en S3
    try {
        $s3->headObject( ['Bucket' => $bucket, 'Key' => $key] );
        wp_send_json_success( ['exists' => true, 'key' => $key] );
        return;
    } catch ( Aws\S3\Exception\S3Exception $e ) {
        if ( $e->getStatusCode() !== 404 ) {
            wp_send_json_error( ['message' => 'Error al verificar en S3: ' . $e->getMessage()] );
        }
    }

    // Generar presigned PUT URL (válida 1 hora)
    $cmd = $s3->getCommand( 'PutObject', [
        'Bucket'      => $bucket,
        'Key'         => $key,
        'ContentType' => $content_type,
    ]);
    $presigned = $s3->createPresignedRequest( $cmd, '+1 hour' );

    wp_send_json_success( [
        'exists' => false,
        'url'    => (string) $presigned->getUri(),
        'key'    => $key,
    ]);
}

/**********************************************************************************************************/
/************************* S3 DIRECT UPLOAD — MULTIPART (≥ 5MB) ****************************************/
/**********************************************************************************************************/

// --- Iniciar multipart upload ---
add_action( 'wp_ajax_s3_initiate_multipart', 'ietk_s3_initiate_multipart' );
function ietk_s3_initiate_multipart() {
    check_ajax_referer( 'admin_toolkit_s3_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Sin permisos'], 403 );

    $filename     = sanitize_file_name( $_POST['filename'] ?? '' );
    $carpeta      = sanitize_text_field( $_POST['carpeta'] ?? '' );
    $content_type = sanitize_text_field( $_POST['content_type'] ?? 'application/octet-stream' );

    if ( empty( $filename ) || empty( $carpeta ) ) {
        wp_send_json_error( ['message' => 'Parámetros inválidos'] );
    }

    $carpeta = trim( $carpeta, '/' );
    if ( strpos( $carpeta, '..' ) !== false || strpos( $carpeta, '/' ) !== false ) {
        wp_send_json_error( ['message' => 'Carpeta inválida'] );
    }

    $s3 = ietk_get_s3_client();
    if ( is_wp_error( $s3 ) ) {
        wp_send_json_error( ['message' => $s3->get_error_message()] );
    }

    $bucket = ietk_get_s3_bucket();
    $key    = "descargas/{$carpeta}/{$filename}";

    // Verificar si ya existe en S3
    try {
        $s3->headObject( ['Bucket' => $bucket, 'Key' => $key] );
        wp_send_json_success( ['exists' => true, 'key' => $key] );
        return;
    } catch ( Aws\S3\Exception\S3Exception $e ) {
        if ( $e->getStatusCode() !== 404 ) {
            wp_send_json_error( ['message' => 'Error al verificar en S3: ' . $e->getMessage()] );
        }
    }

    try {
        $result = $s3->createMultipartUpload([
            'Bucket'      => $bucket,
            'Key'         => $key,
            'ContentType' => $content_type,
        ]);
    } catch ( Aws\S3\Exception\S3Exception $e ) {
        wp_send_json_error( ['message' => 'Error al iniciar multipart: ' . $e->getMessage()] );
    }
    wp_send_json_success([
        'exists'    => false,
        'upload_id' => $result['UploadId'],
        'key'       => $key,
    ]);
}

// --- Presigned URL para una parte ---
add_action( 'wp_ajax_s3_get_presigned_url', 'ietk_s3_get_presigned_url' );
function ietk_s3_get_presigned_url() {
    check_ajax_referer( 'admin_toolkit_s3_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Sin permisos'], 403 );

    $upload_id   = sanitize_text_field( $_POST['upload_id'] ?? '' );
    $key         = sanitize_text_field( $_POST['key'] ?? '' );
    $part_number = intval( $_POST['part_number'] ?? 0 );

    if ( empty( $upload_id ) || empty( $key ) || $part_number < 1 || $part_number > 10000 ) {
        wp_send_json_error( ['message' => 'Parámetros inválidos'] );
    }

    if ( strpos( $key, 'descargas/' ) !== 0 ) {
        wp_send_json_error( ['message' => 'Clave S3 inválida'] );
    }

    $s3 = ietk_get_s3_client();
    if ( is_wp_error( $s3 ) ) {
        wp_send_json_error( ['message' => $s3->get_error_message()] );
    }

    try {
        $cmd = $s3->getCommand( 'UploadPart', [
            'Bucket'     => ietk_get_s3_bucket(),
            'Key'        => $key,
            'UploadId'   => $upload_id,
            'PartNumber' => $part_number,
        ]);
        $presigned = $s3->createPresignedRequest( $cmd, '+1 hour' );
    } catch ( Aws\S3\Exception\S3Exception $e ) {
        wp_send_json_error( ['message' => 'Error al generar URL prefirmada: ' . $e->getMessage()] );
    }
    wp_send_json_success( ['url' => (string) $presigned->getUri()] );
}

// --- Completar multipart upload ---
add_action( 'wp_ajax_s3_complete_multipart', 'ietk_s3_complete_multipart' );
function ietk_s3_complete_multipart() {
    check_ajax_referer( 'admin_toolkit_s3_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Sin permisos'], 403 );

    $upload_id = sanitize_text_field( $_POST['upload_id'] ?? '' );
    $key       = sanitize_text_field( $_POST['key'] ?? '' );
    $parts     = json_decode( stripslashes( $_POST['parts'] ?? '[]' ), true );

    if ( empty( $upload_id ) || empty( $key ) || ! is_array( $parts ) || empty( $parts ) ) {
        wp_send_json_error( ['message' => 'Parámetros inválidos'] );
    }
    foreach ( $parts as $part ) {
        if ( empty( $part['PartNumber'] ) || empty( $part['ETag'] ) ) {
            wp_send_json_error( ['message' => 'Estructura de partes inválida'] );
        }
    }

    if ( strpos( $key, 'descargas/' ) !== 0 ) {
        wp_send_json_error( ['message' => 'Clave S3 inválida'] );
    }

    $s3 = ietk_get_s3_client();
    if ( is_wp_error( $s3 ) ) {
        wp_send_json_error( ['message' => $s3->get_error_message()] );
    }

    try {
        $s3->completeMultipartUpload([
            'Bucket'          => ietk_get_s3_bucket(),
            'Key'             => $key,
            'UploadId'        => $upload_id,
            'MultipartUpload' => ['Parts' => $parts],
        ]);
    } catch ( Aws\S3\Exception\S3Exception $e ) {
        wp_send_json_error( ['message' => 'Error al completar multipart: ' . $e->getMessage()] );
    }
    wp_send_json_success( ['key' => $key] );
}

// --- Abortar multipart upload ---
add_action( 'wp_ajax_s3_abort_multipart', 'ietk_s3_abort_multipart' );
function ietk_s3_abort_multipart() {
    check_ajax_referer( 'admin_toolkit_s3_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Sin permisos'], 403 );

    $upload_id = sanitize_text_field( $_POST['upload_id'] ?? '' );
    $key       = sanitize_text_field( $_POST['key'] ?? '' );

    if ( empty( $upload_id ) || empty( $key ) ) {
        wp_send_json_error( ['message' => 'Parámetros inválidos'] );
    }

    if ( strpos( $key, 'descargas/' ) !== 0 ) {
        wp_send_json_error( ['message' => 'Clave S3 inválida'] );
    }

    $s3 = ietk_get_s3_client();
    if ( is_wp_error( $s3 ) ) {
        wp_send_json_error( ['message' => $s3->get_error_message()] );
    }

    try {
        $s3->abortMultipartUpload([
            'Bucket'   => ietk_get_s3_bucket(),
            'Key'      => $key,
            'UploadId' => $upload_id,
        ]);
    } catch ( Aws\S3\Exception\S3Exception $e ) {
        wp_send_json_error( ['message' => 'Error al abortar multipart: ' . $e->getMessage()] );
    }
    wp_send_json_success();
}

/**********************************************************************************************************/
/************************* S3 DIRECT UPLOAD — REGISTRAR EN ACF *****************************************/
/**********************************************************************************************************/
add_action( 'wp_ajax_s3_register_file', 'ietk_s3_register_file' );
function ietk_s3_register_file() {
    check_ajax_referer( 'admin_toolkit_s3_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Sin permisos'], 403 );

    $post_id = intval( $_POST['post_id'] ?? 0 );
    $key     = sanitize_text_field( $_POST['key'] ?? '' );
    $nombre  = sanitize_file_name( $_POST['nombre'] ?? '' );
    $idioma  = sanitize_text_field( $_POST['idioma'] ?? 'ES' );
    $medidas = sanitize_text_field( $_POST['medidas'] ?? '' );
    $peso_kb = intval( $_POST['peso_kb'] ?? 0 );
    $formato = sanitize_text_field( $_POST['formato'] ?? '' );

    if ( ! $post_id || empty( $key ) || empty( $nombre ) ) {
        wp_send_json_error( ['message' => 'Parámetros inválidos'] );
    }

    // Validar que el post existe y es de tipo toolkit
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'toolkit' ) {
        wp_send_json_error( ['message' => 'Post inválido'] );
    }

    // Validar que la clave S3 pertenece al prefijo correcto
    if ( strpos( $key, 'descargas/' ) !== 0 ) {
        wp_send_json_error( ['message' => 'Clave S3 inválida'] );
    }

    $row = [
        'nombre_archivo'  => $nombre,
        'ruta_de_archivo' => $key,
        'idioma'          => $idioma,
        'formato'         => $formato,
        'peso'            => $peso_kb . ' KB',
        'medidas'         => $medidas,
    ];

    $result = add_row( 'datos_archivos', $row, $post_id );

    if ( $result ) {
        wp_send_json_success( ['message' => 'Archivo registrado correctamente en el post ' . $post_id] );
    } else {
        wp_send_json_error( ['message' => 'Error al registrar el archivo en ACF para el post ' . $post_id] );
    }
}