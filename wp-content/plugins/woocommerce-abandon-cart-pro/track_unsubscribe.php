<?php

if( defined( 'WP_CONTENT_FOLDERNAME' ) ) {
    $wp_content_dir_name = WP_CONTENT_FOLDERNAME;
} else {
    $wp_content_dir_name = "wp-content";
}

$url    = dirname( __FILE__ );
$my_url = explode( $wp_content_dir_name , $url );
$path   = $my_url[0];

include_once $path . 'wp-load.php';
global $wpdb;

/******
*  This function is used to decode the validate string.
******/
function decrypt_validate( $validate ) {
    $cryptKey         = 'qJB0rGtIn5UB1xG03efyCp';
    $validate_decoded = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $validate ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
    return( $validate_decoded );
}

$validate_server_string  = $_SERVER[ "QUERY_STRING" ];
$validate_server_arr     = explode( "validate=", $validate_server_string );
$validate_encoded_string = end( $validate_server_arr );

$validate_email_address_string = '';
$validate_email_id_decode = 0;
if( preg_match( '/&track_email_id=/', $validate_encoded_string ) ) {
    
    $validate_email_address_arr = explode( "&track_email_id=", $validate_encoded_string );
    if( isset( $validate_email_address_arr[0] ) ) {
        $validate_email_id_decode = decrypt_validate( $validate_email_address_arr[0] );
    }
    $validate_email_address_string = end( $validate_email_address_arr );
}

$query_id      = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history` WHERE id = %d ";
$results_sent  = $wpdb->get_results ( $wpdb->prepare( $query_id, $validate_email_id_decode ) );
$email_address = '';

if( isset( $results_sent[0] ) ) {
    $email_address =  $results_sent[0]->sent_email_id;
}

if( $validate_email_address_string == hash( 'sha256', $email_address ) ) {
   
   $email_sent_id     = $validate_email_id_decode;
   $get_ac_id_query	  = "SELECT abandoned_order_id FROM `" . $wpdb->prefix . "ac_sent_history`
					    WHERE id = %d";
   $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query , $email_sent_id ) );
   $user_id           = 0;
   if( isset( $get_ac_id_results[0] ) ) {
       $get_user_id_query = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history`
        				       WHERE id = %d";
       $get_user_results  = $wpdb->get_results( $wpdb->prepare( $get_user_id_query , $get_ac_id_results[0]->abandoned_order_id ) );
   }
   if( isset( $get_user_results[0] ) ) {
       $user_id	= $get_user_results[0]->user_id;
   }
   
   $unsubscribe_query = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history`
						SET unsubscribe_link = '1'
						WHERE user_id= %d AND cart_ignored='0' ";
   $wpdb->query( $wpdb->prepare( $unsubscribe_query , $user_id ) );
   
   echo "Unsubscribed Successfully";
   
   sleep( 2 );
   
   $url = get_option( 'siteurl' );
   ?>
   <script>
   location.href = "<?php echo $url; ?>";
   </script>
   <?php 
     }
   ?>
   