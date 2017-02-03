<?php
if( defined( 'WP_CONTENT_FOLDERNAME' ) ) {
    $wp_content_dir_name = WP_CONTENT_FOLDERNAME;
} else {
    $wp_content_dir_name = "wp-content";
}

$url    = dirname( __FILE__ );
$my_url = explode( $wp_content_dir_name , $url );
$path   = $my_url[0];

require_once $path . 'wp-load.php';
global $wpdb;

$email_sent_id = $_GET['email_sent_id'];
if ( $email_sent_id > 0 && is_numeric( $email_sent_id ) ) {
	$query = "INSERT INTO `" . $wpdb->prefix . "ac_opened_emails` ( email_sent_id , time_opened )
			  VALUES ( '" . $email_sent_id . "' , '" . current_time( 'mysql' ) . "' )";
	$wpdb->query( $query );	
	//mysql_query( $query );
	//echo "stored";
}
?>