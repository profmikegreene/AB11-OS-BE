<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/www.wp.dev/wp-load.php' );
// require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php' );
global $wpdb;
global $ab11_os_admin_table;
global $ab11_os_table;

$output = '<div id="ab11-os-notice" class="updated fade">';

$ab11_os_admin_table = $wpdb->base_prefix . 'ab11_os_admin';

$file = isset( $_POST['attachment'] ) ? $_POST['attachment'] : NULL;
$semester_id = $file['semester_id'];

$ab11_os_table = $wpdb->base_prefix . 'ab11_os_' . $semester_id;

$serialized = is_null( $file ) ? NULL: serialize( $file );

if( is_null( $file ) ) {
	$output .= 'No file found';
}else {
	$rows_affected = $wpdb->insert( $ab11_os_admin_table, array(
		'semester_id' => $file['semester_id'],
		'data' => $serialized,
		'status' => 'draft',
		'created' => current_time( 'mysql' )
		));

	$import_id = $wpdb->insert_id;

	if( $wpdb->get_var( "SHOW TABLES LIKE '$ab11_os_table'" ) == $ab11_os_table) {
			$query = $wpdb->query("DROP TABLE $ab11_os_table");
			$output .= '<p>Dropped old ' . $ab11_os_table . '</p>';
	}

	$sql = $wpdb->prepare( "CREATE TABLE $ab11_os_table (
		subject varchar(4) NOT NULL,
		catalog int(4) unsigned NOT NULL,
		long_title varchar(100) NOT NULL,
		status varchar(10) NOT NULL,
		course_number int(5) unsigned NOT NULL,
		days varchar(7) NOT NULL,
		first_name varchar(30) NOT NULL,
		last_name varchar(30) NOT NULL,
		session varchar(3) NOT NULL,
		start_time varchar(10),
		end_time varchar(10),
		section varchar(4) NOT NULL,
		room varchar(4),
		credits varchar(3) NOT NULL,
		location varchar(30) NOT NULL,
		notes varchar(512),
		course_description varchar(1024) NOT NULL,
		mode_description varchar(50) NOT NULL,
		cap_enrl int(2) unsigned NOT NULL,
		tot_enrl int(2) unsigned NOT NULL,
		start_date varchar(10) NOT NULL,
		end_date  varchar(10) NOT NULL,
		census_date  varchar(10) NOT NULL,
		withdrawal_date  varchar(10) NOT NULL,
		career varchar(4) NOT NULL,
		semester_id int(4) unsigned NOT NULL,
		import_id int(9) unsigned NOT NULL
	);");

	$output .= '<p>Created new ' . $ab11_os_table . '</p>';

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	ini_set('auto_detect_line_endings',TRUE);

  if ( ($handle = fopen($file['url'], "r")) !== FALSE ){
	  while ( ($tmp = fgetcsv($handle, 0, ",")) !== FALSE ){
	  	if (is_numeric( $tmp[4] )){
	  		$course['subject'] = $tmp[0];
	  		$course['catalog'] = $tmp[1];
	  		$course['long_title'] = $tmp[2];
	  		$course['status'] = $tmp[3];
	  		$course['course_number'] = $tmp[4];
	  		$course['days'] = $tmp[5];
	  		$course['first_name'] = $tmp[6];
	  		$course['last_name'] = $tmp[7];
	  		$course['session'] = $tmp[8];
	  		$course['start_time'] = $tmp[9];
	  		$course['end_time'] = $tmp[10];
	  		$course['section'] = $tmp[11];
	  		$course['room'] = $tmp[12];
	  		$course['credits'] = $tmp[13];
	  		$course['location'] = $tmp[14];
	  		$course['notes'] = $tmp[15];
	  		$course['course_description'] = $tmp[16];
	  		$course['mode_description'] = $tmp[17];
	  		$course['cap_enrl'] = $tmp[18];
	  		$course['tot_enrl'] = $tmp[19];
	  		$course['start_date'] = $tmp[20];
	  		$course['end_date'] = $tmp[21];
	  		$course['census_date'] = $tmp[22];
	  		$course['withdrawal_date'] = $tmp[23];
	  		$course['career'] = $tmp[24];
	  		$course['semester_id'] = $semester_id;
	  		$course['import_id'] = $import_id;

	  		$filtered_course = filter_course( $course );


	  		if ( is_array( $filtered_course ) ){
	  			$keys = implode( ", ", array_keys($filtered_course) );
	  			$sql = "INSERT INTO $ab11_os_table ( $keys ) " .
	  			"VALUES ( %s, %d, %s, %s, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s, %s, %s, %s, %d, %d)";

					$query = $wpdb->query( $wpdb->prepare( $sql, $filtered_course ) );
	  		}
	  	}
	  }
	} else {
		$output .= "<p>Cannot open file</p>";
	}
	ini_set('auto_detect_line_endings',FALSE);

	$count = $wpdb->get_var( "SELECT COUNT(*) FROM $ab11_os_table" );

	$query = $wpdb->query( $wpdb->prepare(
		"UPDATE $ab11_os_admin_table SET status = 'published', courses = %d WHERE (semester_id) = %d AND (import_id = %d)",
		array( $count, $semester_id, $import_id )
	) );
	$query = $wpdb->query( $wpdb->prepare(
		"UPDATE $ab11_os_admin_table SET status = 'draft' WHERE (semester_id) = %d AND NOT (import_id = %d)",
		array( $semester_id, $import_id )
	) );

	if ($count > 0 ){
   	$output .= '<p>Successfully imported ' . $count;
   	$output .= ' courses for semester ' . $semester_id . '.</p>' . "\n\t";

	} else {
		$output .= '<p>Error Importing Courses.</p>' . "\n\t";

	}
	$output .= '</div>';
	echo $output;
}

function filter_course( $course ) {

	//Test for lab sections, if true change credits to LAB if false return whole numbe of credits
	$course['credits'] = ( strpbrk( $course['section'], 'L' ) !== FALSE ) ?
		'LAB' : strstr( $course['credits'], '.', TRUE );

	//Test for sections that need removal
	$remove_sections = ['54HC', '54CB', '94NH', '279'];
	$course = in_array( $course['section'], $remove_sections ) ? NULL : $course;

	//Test for campuses that need removal
	$remove_campuses = ['HCC'];
	$course = in_array( $course['location'], $remove_campuses ) ? NULL : $course;

	//Test for session that need removal
	$remove_sessions = ['YR1', 'YR2', 'YR3'];
	$course = in_array( $course['session'], $remove_sessions ) ? NULL : $course;

	return $course;

}

?>