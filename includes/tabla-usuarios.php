<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Tabla_Usuarios extends WP_List_Table {

    var $data;
    var $columns_data;
    var $sortable_columns;
    var $per_page;

    function __construct(){
        global $status, $page;                
    
        parent::__construct( array(
            'singular'  => 'Usuario',  
            'plural'    => 'Usuarios',   
            'ajax'      => false      
        ) );        
    }

    function get_columns () { 
        return $this->columns_data;
    }

    function column_default( $item, $column_name ) {
        if(array_key_exists($column_name, $this->columns_data)) {
            return $item[ $column_name ];
        } else {
            return print_r( $item, true ) ;
        }
    }

    function get_sortable_columns() {
        return $this->sortable_columns;
    }

    function usort_reorder( $a, $b ) {
        // Si no se especifica columna, por defecto la primera
        $orderby = ( !empty($_GET['orderby']) ) ? $_GET['orderby'] : array_key_first($this->columns_data);
        // Si no hay orden, por defecto asendente
        $order = ( !empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
        $result = strnatcmp( $a[$orderby], $b[$orderby] );

        return ( $order === 'asc' ) ? $result : -$result;
    }

    function extra_tablenav( $which ) {
        if ( $which == "top" ){
            ?>
            <div class="alignleft actions bulkactions">
                <form action="" method="GET">
                    <?php
                    $begin_date = ( isset($_REQUEST['begin_date']) ) ? $_REQUEST['begin_date'] : "";
                    $end_date = ( isset($_REQUEST['end_date']) ) ? $_REQUEST['end_date'] : "";
                    ?>
                    <input type="hidden" name="post_type" class="post_type_page" value="toolkit">
                    <input type="hidden" name="page" value="<?= esc_attr($_REQUEST['page']) ?>"/>
                    <label for="begin_date">Desde:</label>
                    <input type="date" id="begin_date" name="begin_date" value="<?= $begin_date ?>" required />
                    <label for="end_date">Hasta:</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" required />
                    <input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filtrar">
                    <input type="button" id="btnDownload" class="button" value="Descargar">
                </form>
            </div>
            <?php
        }
    }
          
    function prepare_items () {
        global $wpdb;

        $date_filter = ( isset($_REQUEST['begin_date']) && isset($_REQUEST['end_date']) ) ? true : false;
        $do_date_filter = ( $date_filter ) ? " AND user_registered BETWEEN '" . $_REQUEST['begin_date'] . "' AND '" . $_REQUEST['end_date'] . " 23:59:59' " : '';

        $search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : false;
        $do_search = ( $search ) ? " AND (nombre LIKE '%$search%' OR email LIKE '%$search%' OR institucion LIKE '%$search%' OR pais LIKE '%$search%')" : ''; 

        $this->data = $wpdb->get_results("SELECT ID, display_name, user_email, user_registered FROM wp_users WHERE TRUE $do_date_filter $do_search", 'ARRAY_A');

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        usort( $this->data, array(&$this, 'usort_reorder' ));

        $current_page = $this->get_pagenum();
        $total_items = count( $this->data );
        
        $found = array_slice( $this->data, ( ( $current_page - 1 ) * $this->per_page ), $this->per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $this->per_page,
            'total_pages' => ceil($total_items/$this->per_page)
        ));

        $this->items = $found;
    }
}