<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class My_Table extends WP_List_Table {

    var $data;
    var $columns_data;
    var $sortable_columns;
    var $per_page;

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
          
    function prepare_items () { 
        $columns = $this -> get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $this->data;
        usort( $this->data, array(&$this, 'usort_reorder' ));
        $this->items = $this->data;
        $current_page = $this->get_pagenum();
        $total_items = count( $this->data );
        
        $found = array_slice( $this->data, ( ( $current_page - 1 ) * $this->per_page ), $this->per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $this->per_page
        ));

        $this->items = $found;
    }

    function column_post_title($item) {
        $actions = array(
                    'edit' => sprintf('<a href="?post_type=toolkit&page=%s&action=%s&post_id=%s">Editar</a>',$_REQUEST['page'],'addFiles',$item['id'])
                );
        
        return sprintf('%1$s %2$s', $item['post_title'], $this->row_actions($actions) );
    }
}  