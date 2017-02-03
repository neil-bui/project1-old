<?php

class wcap_common{
    
    public  static function wcap_get_client_ip() {
        
        $ipaddress = '';
        if ( getenv( 'HTTP_CLIENT_IP' ) ){
            $ipaddress = getenv( 'HTTP_CLIENT_IP' );
        } else if( getenv( 'HTTP_X_FORWARDED_FOR' ) ){
            $ipaddress = getenv( 'HTTP_X_FORWARDED_FOR' );
        } else if( getenv( 'HTTP_X_FORWARDED' ) ){
            $ipaddress = getenv( 'HTTP_X_FORWARDED' );
        } else if( getenv( 'HTTP_FORWARDED_FOR' ) ){
            $ipaddress = getenv( 'HTTP_FORWARDED_FOR' );
        } else if( getenv( 'HTTP_FORWARDED' ) ){
            $ipaddress = getenv( 'HTTP_FORWARDED' );
        } else if( getenv( 'REMOTE_ADDR' ) ){
            $ipaddress = getenv( 'REMOTE_ADDR' );
        } else{
            $ipaddress = 'UNKNOWN';
        }
        return $ipaddress;
    }
    
    public  static function wcap_get_user_role( $uid ) {
        global $wpdb;
        $role = $wpdb->get_var("SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = 'wp_capabilities' AND user_id = {$uid}");
        if(!$role) return 'non-user';
        $rarr = unserialize($role);
        $roles = is_array($rarr) ? array_keys($rarr) : array('non-user');
        return ucfirst ( $roles[0] );
    }
}
?>