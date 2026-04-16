<?php 
defined('ABSPATH') or die( "Bye bye" ); 
//Comprueba que tienes permisos para acceder a esta pagina 
if (! current_user_can ('manage_options')) wp_die (__ ('No tienes suficientes permisos para acceder a esta página.')); 
$dir = plugin_dir_url( __FILE__ );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Toolkit</title>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="<?php echo $dir;?>estilos.css" type="text/css">
</head>
<div class="body_plugin">
	<div class="content_plugin">

		<section>
			<h2>Subir archivos</h2>
			<form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" name="ietk_form_upload_files" id="ietk_form_upload_files" class="formulario" enctype="multipart/form-data"> 
				<div>
					<label for="">archivo:</label> 
					<input class="fileUpload_avatar" type="file"  name="archivos[]" multiple /> 
				</div>
				<div>
					<input type="submit" name="enviar" id="enviar" />
				</div> 
				<input type="hidden" name="action" value="ietk_form_upload_files">
				<?php wp_nonce_field( 'ietk_form_upload_files_nonce', 'name_of_nonce_field' ); ?>
				<div id="ajax_results" class="ajax_results"></div> 
			</form> 
		</section>

		<hr />	

		<section>
			<h3>Archivos subidos</h3>
			<div class="cols">
				<form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" name="ietk_after_form_upload_files" id="ietk_after_form_upload_files" class="formulario"> 
					<input type="hidden" name="action" value="ietk_after_form_upload_files">
					<?php wp_nonce_field( 'ietk_after_form_upload_files_nonce', 'name_of_nonce_field' ); ?>
				</form> 
				<form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" name="ietk_delete_files" id="ietk_delete_files" class="formulario"> 
					<div id="ajax_results_listado" class="">
						<?php ietk_listarArchivos("../_tk_para_subir/","img"); ?>
					</div> 
					<input type="hidden" name="action" value="ietk_delete_files">
					<?php wp_nonce_field( 'ietk_delete_files_nonce', 'name_of_nonce_field' ); ?>
					<input type="submit" name="eliminar" value="eliminar" />
					<input type="hidden" name="carpeta" value="/_tk_para_subir/" />
					<div id="ajax_results3" class="ajax_results"></div> 
				</form> 
			</div>
		</section>

    </div>
	
</div>

<!-- body_plugin -->