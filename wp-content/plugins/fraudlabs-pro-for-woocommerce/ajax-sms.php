<?php
include_once( '../../../wp-config.php' );

function isvalidip( $ip ) {
	if ( ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) || ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) ) {
		return true;
	}
	else {
		return false;
	}
}

function getip() {
	if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && isvalidip( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
		$ipAddress = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
	}
	else {
		$ipAddress = $_SERVER[ 'REMOTE_ADDR' ];
	}

	if ( preg_match( '/,/', $ipAddress ) ) {
		$tmp = explode( ',', $ipAddress );
		$ipAddress = trim( $tmp[ 0 ] );
	}
	return $ipAddress;
}

function query_sms_table( $table_name, $ip ) {
	global $wpdb;
	
	return $wpdb->get_row( "SELECT * FROM $table_name WHERE ip = '" . $ip . "' LIMIT 1" );
}

function update_sms_table( $table_name, $ip, $counter, $last, $ajax ) {
	global $wpdb;
	
	$wpdb->replace( $table_name, array( 'ip' => $ip, 'counter' => $counter, 'last' => $last, 'ajax' => $ajax ), array( '%s', '%d', '%d', '%s' ) );
}

function delete_sms_table( $table_name, $ip ) {
	global $wpdb;
	
	$wpdb->delete( $table_name, array( 'ip' => $ip ), array( '%s' ) );
}

$table_name = $wpdb->prefix . 'flp_sms_counter';
$ip = getip();
$smsdata = query_sms_table( $table_name, $ip );

$retries = $smsdata->counter;
$flp_ajax_field = $smsdata->ajax;

// using dynamic form field name for authentication
if ( ( !isset( $flp_ajax_field ) ) ||  ( trim( $flp_ajax_field ) == '' ) || ( !isset( $_POST[ $flp_ajax_field ] ) ) )
	die( 'ERROR 100' );

// only AJAX can run call this page
if ( ( !isset( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) ) || ( strtolower( $_SERVER[ 'HTTP_X_REQUESTED_WITH' ] ) != 'xmlhttprequest' ) )
	die( 'ERROR 200' );

if ( ( !isset( $_POST[ 'action' ] )) || ( preg_match( '/^(send|verify)$/', $_POST[ 'action' ] ) === 0 ) )
	die( 'ERROR 300' );

$action = $_POST[ 'action' ];

$apiKey = get_option( 'wc_settings_woocommerce-fraudlabs-pro_api_key' );
$params[ 'format' ] = 'json';

if ( $action == 'send' ) {
	if ( ( !isset( $_POST[ 'tel' ] ) ) || ( !isset( $_POST[ 'country_code' ] ) ) )
		die( 'ERROR 400' );
	$params[ 'tel' ] = trim( $_POST[ 'tel' ] );
	if ( strpos( $params[ 'tel' ], '+' ) !== 0 )
		$params[ 'tel' ] = '+' . $params[ 'tel' ];
	$params[ 'country_code' ] = $_POST[ 'country_code' ];
	$params[ 'mesg' ] = get_option( 'wc_settings_woocommerce-fraudlabs-pro_sms_template' );
	$params[ 'mesg' ] = str_replace( [ '{', '}' ], [ '<', '>' ], $params[ 'mesg' ] );
	$url = 'https://api.fraudlabspro.com/v1/verification/send';
	update_sms_table( $table_name, $ip, --$smsdata->counter, $smsdata->last, $smsdata->ajax );
}
else if ( $action == 'verify' ) {
	if ( ( !isset( $_POST[ 'otp' ] ) ) || ( !isset( $_POST[ 'tran_id' ] ) ) )
		die( 'ERROR 400' );
	$params[ 'otp' ] = $_POST[ 'otp' ];
	$params[ 'tran_id' ] = $_POST[ 'tran_id' ];
	$url = 'https://api.fraudlabspro.com/v1/verification/result';
}

$query = '';

foreach( $params as $key=>$value ) {
	$query .= '&' . $key . '=' . rawurlencode( $value );
}

$url = $url . '?key=' . $apiKey . $query;

$request = wp_remote_get( $url );

// network error, wait 2 seconds for next retry
if ( is_wp_error( $request ) ) {
	for ( $i = 0; $i < 3; ++$i ) {
		sleep( 2 );
		$request = wp_remote_get( $url );
		
		if ( !is_wp_error( $request ) ) {
			break;
		}
	}
}

// still having network issue after 3 retries, give up
if ( is_wp_error( $request ) )
	die ( 'ERROR 500' );

// Get the HTTP response
$data = json_decode( wp_remote_retrieve_body( $request ) );

if ( trim( $data->error ) != '' ) {
	die( 'ERROR 600-' . $data->error );
}
else {
	if ( $action == 'send' ) {
		die ( 'OK' . $data->tran_id );
	}
	delete_sms_table( $table_name, $ip );
	die ( 'OK' );
}
?>