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

    <h1>Archivos Toolkit</h1> 
    <table width="100%" border="1">
        <tr>
            <td><b>#</b></td>
            <td><b>id post</b></td>
            <td><b>título</b></td>
            <td><b>fecha</b></td>
            <td><b>enlace publicación</b></td>
        </tr>
        <?php 
        global $wpdb; 
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'toolkit',
            'post_status'      => 'publish',
            'orderby'   => 'title',
            'order' => 'ASC',
        ); 
        $posts_ = get_posts( $args );   

        //print "<pre>"; print_r($posts_); print "</pre>"; 

        if(count($posts_)>0){ 
            $cont=0;
            foreach ($posts_ AS $post_){  
                $cont++; 
                ?>
                <tr>
                    <td><?php echo $cont;?></td>
                    <td><?php echo $post_->ID;?></td>
                    <td><a href="javascript:void(0);" onclick="admin_archivo('<?php echo $post_->ID;?>');" ><?php echo $post_->post_title;?></a></td>
                    <td><?php echo $post_->post_date;?></td>
                    <td><a href="<?php echo $post_->guid;?>" target="_blank">Ver publicación</a></td>
                </tr>
                <?php 
            }
        }else{
            ?>
            <p>
            0 resultados 
            </p>
            <?php 
        }
        ?>
    </table>
    

    <script>
    function admin_archivo(id){ 
        var accion = "ietk_form_agrega";
        $.post("<?php echo admin_url('admin-ajax.php'); ?>", {action: accion, id: id}, function(result){
            $(".body_plugin").html(result);
        });
    }
    </script>
</div>

