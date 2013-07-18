<?php
/*
Plugin Name: Awaken Benehime Online Schedule Back End
Plugin URI: http://www.rappahannock.edu
Description: Online Schedule for RCC website.
Version: 11.0.0
Author: Michael Greene
Author URI: http://profmikegreene.com
License: This plugin is for RCC
*/

global $wpdb;
global $ab11_os_table;
global $ab11_os_admin_table;


$ab11_os_table = $wpdb->get_results( "SHOW TABLES LIKE '" . $wpdb->base_prefix . "ab11_os_2%'");
define("AB11_OS_DB_TABLES", serialize( $ab11_os_table ) );

$ab11_os_admin_table = $wpdb->base_prefix . "ab11_os_admin";
define("AB11_OS_DB_ADMIN_TABLE", $ab11_os_admin_table );









if ( ! function_exists( 'ab11_os_install' ) ) :
function ab11_os_install () {

	global $wpdb;
	global $ab11_os_admin_table;

	$ab11_os_admin_table = $wpdb->base_prefix . 'ab11_os_admin';

	if( $wpdb->get_var("SHOW TABLES LIKE '$ab11_os_admin_table'") != $ab11_os_admin_table ){
		$sql = $wpdb->prepare( "CREATE TABLE $ab11_os_admin_table (
			import_id int(9) UNSIGNED NOT NULL AUTO_INCREMENT,
			semester_id int(4) UNSIGNED NOT NULL,
			courses int(4) UNSIGNED,
			data varchar(5000),
			status varchar(10),
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      PRIMARY KEY (import_id)
			);");

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
endif;
register_activation_hook( __FILE__, 'ab11_os_install' );

if ( ! function_exists( 'ab11_os_admin_init' ) ) :
function ab11_os_admin_init() {
	wp_register_style( 'ab11_os_styles', plugins_url( 'style.css', __FILE__ ) );
	wp_enqueue_media();
	wp_register_script( 'ab11_os_scripts', plugins_url( 'script.js', __FILE__ ), array( 'jquery' ), false, true );
}
endif;
add_action( 'admin_init', 'ab11_os_admin_init' );

if ( ! function_exists( 'ab11_os_menu' ) ) :
function ab11_os_menu() {
	add_menu_page(
		'RCC Online Schedule',
		'Schedules',
		'edit_published_pages',
		'ab11_os_view_all',
		'ab11_os_view_all',
		'',
		3
		);
	add_submenu_page(
		'ab11_os_view_all',
		'Import',
		'Import',
		'edit_published_pages',
		'ab11_os_manage',
		'ab11_os_manage'
		);
}
endif;
add_action( 'admin_menu', 'ab11_os_menu' );

if ( ! function_exists( 'ab11_os_view_all' ) ) :
function ab11_os_view_all() {
	global $wpdb;
	global $ab11_os_table;
	global $ab11_os_admin_table;
	require_once ( 'class-ab11-os-admin-table.php' );

	if (!current_user_can( 'edit_published_pages' ) ) {
		wp_die( __('You do not have sufficient permissions to access this page.' ) );
	}

	wp_enqueue_style( 'ab11_os_styles' );
	wp_enqueue_script( 'ab11_os_scripts' );

	$ab11_os_list_table = new AB11_OS_WP_List_Table();
  $ab11_os_list_table->prepare_items();

	$output = '';

	$output .= '<div class="wrap">' . "\n";
  $output .= '<div id="icon-view-schedules" class="icon32 icon-generic"><br></div>' . "\n\t";
	$output .= '<h2>All Semesters' . "\n\t";
  $output .= '<a href="?page=ab11_os_manage&action=create" class="add-new-h2">Import</a>' . "\n\t";
  $output .= '</h2>' . "\n\t";
  echo $output;

  $output .= $ab11_os_list_table->display();
  $output = '</div>' . "\n";
  echo $output;
  echo get_option('plugin_error');
}
endif;





if ( ! function_exists( 'ab11_os_manage' ) ) :
function ab11_os_manage() {

	wp_enqueue_style( 'ab11_os_styles' );
	wp_enqueue_script( 'ab11_os_scripts' );

  global $wpdb;
	global $ab11_os_table;
	global $ab11_os_admin_table;

	$output = '';

  $output .= '<div class="wrap" id="ab11-os-import-wrap">' . "\n\t";
  $output .= '<div id="icon-edit-schedules" class="icon32 icon-generic"><br></div>' . "\n\t";

    if(isset($_GET['action'])){
      switch ($_GET['action']) {
        case 'create':
          $output .= '<h2>Import</h2>' . "\n\t";
          break;
        default:
          $output .= '<h2>Import</h2>';
          break;
      }
  }else {
  	$output .= '<h2>Import</h2>';
  }
  $output .= '<form id="ab11-os-import-form" method="post" action="' . plugins_url('import.php',__FILE__);
  $output .= '" enctype="multipart/form-data">' . "\n\t";
	$output .= '<div class="uploader">' . "\n\t";
	$output .= '<input type ="text" name="ab11_os_semester_id" id="ab11_os_semester_id" placeholder="2134">' . "\n\t";
	$output .= '<label for="ab11_os_semester_id">Semester ID</label>' . "\n\t";
	$output .= '<br /><br />' . "\n\t";
	$output .= '<input type="text" name="ab11_os_import" id="ab11_os_import" />' . "\n\t";
	$output .= '<input type="button" class="button button-primary" name="ab11_os_import_button" ';
	$output .= 'id="ab11_os_import_button" value="Upload" />' . "\n\t";
	$output .= '</div>' . "\n\t";
	$output .= '</form>' . "\n\t";
  $output .= '</div>' . "\n\t";

  echo $output;
}
endif;

if ( ! function_exists( 'ab11_os_import' ) ) :
function ab11_os_import() {
	wp_enqueue_style( 'ab11_os_styles' );
	wp_enqueue_script( 'ab11_os_scripts' );

	$outpu = '';

	$output .= '<div class="wrap" id="ab11-os-import-wrap">' . "\n\t";
  $output .= '<div id="icon-edit-schedules" class="icon32 icon32-posts-post"><br></div>' . "\n\t";
  $output .= '<h2>Import</h2>' . "\n\t";
	$output .= '</div>' . "\n\t";

	echo $output;
}
endif;

if ( ! function_exists( 'ab11_os_save_error' ) ) :
function ab11_os_save_error(){
    update_option('plugin_error',  ob_get_contents());
}
endif;
add_action('activated_plugin','ab11_os_save_error');

if ( ! function_exists( 'ab11_os_admin_bar' ) ) :
function ab11_os_admin_bar() {
    global $wp_admin_bar;

    //Add a link called 'My Link'...
    $wp_admin_bar->add_menu( array(
        'id'    => 'ab11-os-import',
        'title' => 'Schedule',
        'href'  => admin_url() . 'admin.php?page=ab11_os_manage&action=create',
        'parent' => 'new-content'
    ));

}
endif;
add_action( 'wp_before_admin_bar_render', 'ab11_os_admin_bar' );


// if ( ! function_exists( '' ) ) :
// function () {

// }
// endif;

// if ( ! function_exists( '' ) ) :
// function () {

// }
// endif;

?>