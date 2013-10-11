<?php
if(!class_exists('WP_List_Table')){
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class AB11_OS_WP_List_Table extends WP_List_Table {
	function __construct() {
		global $status,
					 $page;

		parent::__construct( array(
			'singular'		=> 'ab11_os_semester',
			'plural'			=> 'ab11_os_semesters',
			'ajax'				=> false
		) );
	}

	function column_default( $item, $column_name ){
		switch($column_name){
			default:
				return print_r($item,true); //Show the whole array for troubleshooting purposes
		}
	}

 	function column_semester_id( $item ){
        //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s">Import</a>','ab11_os_manage'),
            'delete'    => sprintf('<a href="?page=%s&action=%s&_id=%s">Delete</a>','ab11_os_manage','delete',$item['semester_id']),
        );

        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ stripslashes($item['semester_id']),
            /*$2%s*/ $this->row_actions($actions)
        );
  }

  function column_courses( $item ) {
  	return sprintf('%1$s', $item['courses']);

  }

  function column_import_id( $item ) {
  	return sprintf('%1$s', $item['import_id']);

  }

  function column_created( $item ) {
  	$timezone = new DateTimeZone('America/New_York');
  	$created = new DateTime( $item['created' ]);
  	$created->setTimezone($timezone);
  	$formatted = $created->format( 'Y/m/d g:i:s A' );
  	return sprintf('%1$s', $formatted);
  }

  function get_columns() {
  	$columns = array(
  		'semester_id' => 'Semester',
  		'courses' => 'Courses',
  		'import_id' => 'Import ID',
  		'created' => 'Created'
  		);
  	return $columns;
  }

  function get_sortable_columns() {
    $sortable_columns = array( );
    return $sortable_columns;
	}

	function prepare_items() {
		global $wpdb,
					 $ab11_os_admin_table,
					 $ab11_os_table;

		$ab11_os_admin_table = $wpdb->base_prefix . 'ab11_os_admin';
		$sql                   = "SELECT * FROM $ab11_os_admin_table WHERE (status = 'published') ";
		$sql                   .= "ORDER BY import_id DESC";
		$per_page              = 50;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$data                  = $wpdb->get_results($sql, ARRAY_A);
		$current_page = $this->get_pagenum();
		$total_items           = count($data);
		$data                  =		array_slice($data, (($current_page-1)*$per_page),$per_page);
		$this->items           =		$data;
		$this->set_pagination_args( array(
		'total_items'          => $total_items,
		'per_page'             => $per_page,
		'total_pages'          => ceil($total_items/$per_page)
		) );
	}
}
?>