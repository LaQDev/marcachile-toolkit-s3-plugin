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
		<h2>Publicar Archivos</h2>
		<form name="ietk_form_ejecuta_csv" id="ietk_form_ejecuta_csv" method="post"  action="<?php echo admin_url('admin-ajax.php'); ?>" class="formulario"  > 
			<div class="cols">
				<h3>Data CSV</h3>
				<div class="radios">
					<?php 
					$path="../_tk_csv/";
					$dir = opendir($path);
					$cont=0; 
					?>
					<ul>
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
									<input type="radio" name="archivo" value="<?php echo $elemento;?>" id="btn_<?php echo $id_elemento;?>" />
									<label for="btn_<?php echo $id_elemento;?>"  >
										<?php echo $elemento; ?>
									</label> 
								</li>
								<?php    
							}
						} 
					} 
					?>
				</div> 
				<h3>Carpeta de destino</h3>
				<div class="radios">
					<ul>
						<?php
							$args = array(
								'type'                     => 'toolkit',
								'child_of'                 => 0,
								'parent'                   => 0,
								'orderby'                  => 'name',
								'order'                    => 'ASC',
								'hide_empty'               => 0,
								'hierarchical'             => 1,
								'taxonomy'                 => 'categorias',
								'pad_counts'               => false
							);
							$categories = get_categories($args);

							foreach ($categories as $category) {
								$url = get_term_link($category);?>								
									<li id="">
										<input type="radio" name="carpeta" value="<?php echo $category->slug; ?>" id="btn_carpeta_<?php echo $category->slug; ?>">
										<label for="btn_carpeta_<?php echo $category->slug; ?>"><?php echo $category->slug; ?></label> 
									</li>
								<?php
							}
						?>
					</ul>
				</div>  

				<h3>Acción</h3>
				<div class="radios linea">
					<ul>
						<li id="">
							<input type="radio" name="accion" value="agregar" id="btn_agregar">
							<label for="btn_agregar">agregar</label> 
						</li>
						<li id="">
							<input type="radio" name="accion" value="previsualizar" id="btn_previsualizar" checked="checked">
							<label for="btn_previsualizar">previsualizar</label> 
						</li>
					</ul>
				</div> 

				<h3>Cantidad</h3>
				<div class="radios linea">
					<div class="grupo_inputs">
						<input type="number" name="cantidad" value="1" id="cantidad" class="cantidad">
						<label for="btn_subir">-1 para todos</label> 
					</div>
				</div> 
			</div>
			<div class="group_block">
				<input type="hidden" name="action" value="ietk_form_ejecuta_csv">
				<?php wp_nonce_field( 'ietk_form_ejecuta_csv_nonce', 'name_of_nonce_field' ); ?>
				<input type="submit" name="enviar" id="enviar" />
			</div> 
			<div id="ajax_results_ejecuta" class="ajax_results"></div> 
		</form> 
	</div>


	<script type="text/javascript" >  	
	var products_send=0; 
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
</div>
<!-- body_plugin -->