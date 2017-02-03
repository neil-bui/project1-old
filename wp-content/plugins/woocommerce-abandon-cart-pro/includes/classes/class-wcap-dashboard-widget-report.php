<?php
/**
 * WCAP Dashboard Widgets report
 *
 * @package     WooCommerce Abandon Cart Plugin
 * @subpackage  Admin/Dashboard
 * @copyright   Copyright (c) 2015, Tyche Softwares
 * @since       2.7
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard_Widget_Report {
    
    function get_today_reports ( $type ){
        global $wpdb;
        
        $count = 0;
        
        $blank_cart_info       =  '{"cart":[]}';
        $blank_cart_info_guest =  '[]';
        
        $ac_cutoff_time        = get_option( 'ac_cart_abandoned_time' );
        $cut_off_time          = $ac_cutoff_time * 60;
        $current_time          = current_time ('timestamp');
        $compare_time          = $current_time - $cut_off_time;
        
        $beginOfDay = strtotime("midnight", $current_time);
        $endOfDay   = strtotime("tomorrow", $current_time) - 1;
        
        switch ( $type ){
            
            case 'abandoned':
                
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $beginOfDay AND abandoned_cart_time <= $endOfDay AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_time <= '$compare_time' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
                
                if ( count ( $results_abandoned ) > 0 ){
                    $count = count ( $results_abandoned );
                }
                 
                break;
            
            case 'recover':

                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $beginOfDay AND abandoned_cart_time <= $endOfDay AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
                
                if ( count ( $results_recover ) > 0 ){
                    $count = count ( $results_recover );
                }
                
                break;
            
            case 'ratio':
                
                $count_recover = $count_abandoned = '0';
                
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $beginOfDay AND abandoned_cart_time <= $endOfDay AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
                
                if ( count ( $results_recover ) > 0 ){
                    $count_recover = count ( $results_recover );
                }
                
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $beginOfDay AND abandoned_cart_time <= $endOfDay AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
                
                if ( count ( $results_abandoned ) > 0 ){
                    $count_abandoned = count ( $results_abandoned );
                }
                
                if ( $count_recover > 0 ){ 
                    $count =  ( $count_recover /  $count_abandoned ) * 100 ;
                }
                
                break;
        }
        return $count;
    }
    
    function get_this_month_reports ( $type ){
        global $wpdb;
    
        $count_month = 0;
    
        $blank_cart_info       =  '{"cart":[]}';
        $blank_cart_info_guest =  '[]';
    
        $current_time = current_time ('timestamp');
    
        $begin_of_month = mktime(0, 0, 0, date("n"), 1);
        $end_of_month   = mktime(23, 59, 0, date("n"), date("t"));
    
        switch ( $type ){
    
            case 'abandoned':
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $begin_of_month AND abandoned_cart_time <= $end_of_month AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
    
                if ( count ( $results_abandoned ) > 0 ){
                    $count_month = count ( $results_abandoned );
                }
                 
                break;
    
            case 'recover':
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $begin_of_month AND abandoned_cart_time <= $end_of_month AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                if ( count ( $results_recover ) > 0 ){
                    $count_month = count ( $results_recover );
                }
    
                break;
    
            case 'ratio':
    
                $count_recover = $count_abandoned = '0';
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $begin_of_month AND abandoned_cart_time <= $end_of_month AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                if ( count ( $results_recover ) > 0 ){
                    $count_recover = count ( $results_recover );
                }
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $begin_of_month AND abandoned_cart_time <= $end_of_month AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
    
                if ( count ( $results_abandoned ) > 0 ){
                    $count_abandoned = count ( $results_abandoned );
                }
    
                if ( $count_recover > 0 ){
                    $count_month =  ( $count_recover /  $count_abandoned ) * 100 ;
                }
    
                break;
        }
        return $count_month;
    }
    
    function get_last_month_reports ( $type ){
        global $wpdb;
    
        $count_last_month = 0;
    
        $blank_cart_info       =  '{"cart":[]}';
        $blank_cart_info_guest =  '[]';
    
        $current_time = current_time ('timestamp');
    
        $last_month_of_begin = mktime(0, 0, 0, date("n")- 1, 1);
        $last_month_of_end   = mktime(23, 59, 0, date("n") - 1 , date("t") - 1 );
    
        switch ( $type ){
    
            case 'abandoned':
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $last_month_of_begin AND abandoned_cart_time <= $last_month_of_end AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
    
                if ( count ( $results_abandoned ) > 0 ){
                    $count_last_month = count ( $results_abandoned );
                }
                 
                break;
    
            case 'recover':
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $last_month_of_begin AND abandoned_cart_time <= $last_month_of_end AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                if ( count ( $results_recover ) > 0 ){
                    $count_last_month = count ( $results_recover );
                }
    
                break;
    
            case 'ratio':
    
                $count_recover = $count_abandoned = '0';
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $last_month_of_begin AND abandoned_cart_time <= $last_month_of_end AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                if ( count ( $results_recover ) > 0 ){
                    $count_recover = count ( $results_recover );
                }
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $last_month_of_begin AND abandoned_cart_time <= $last_month_of_end AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
    
                if ( count ( $results_abandoned ) > 0 ){
                    $count_abandoned = count ( $results_abandoned );
                }
    
                if ( $count_recover > 0 ){
                    $count_last_month =  ( $count_recover /  $count_abandoned ) * 100 ;
                }
    
                break;
    
        }
    
        return $count_last_month;
    
    }
    
    function get_total_reports ( $type ){
        global $wpdb;
    
        $count_last_month = 0;
    
        $blank_cart_info       =  '{"cart":[]}';
        $blank_cart_info_guest =  '[]';
    
        switch ( $type ){
    
            case 'abandoned':
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
    
                if ( count ( $results_abandoned ) > 0 ){
                    $count_last_month = count ( $results_abandoned );
                }
                 
                break;
    
            case 'recover':
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                if ( count ( $results_recover ) > 0 ){
                    $count_last_month = count ( $results_recover );
                }
    
                break;
    
            case 'ratio':
    
                $count_recover = $count_abandoned = '0';
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                if ( count ( $results_recover ) > 0 ){
                    $count_recover = count ( $results_recover );
                }
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
    
                if ( count ( $results_abandoned ) > 0 ){
                    $count_abandoned = count ( $results_abandoned );
                }
    
                if ( $count_recover > 0 ){
                    $count_last_month =  ( $count_recover /  $count_abandoned ) * 100 ;
                }
    
                break;
    
        }
    
        return $count_last_month;
    
    }
    
    function get_product ( $type ){
        global $wpdb;
    
        $product_id = 0;
    
        $blank_cart_info       =  '{"cart":[]}';
        $blank_cart_info_guest =  '[]';
    
        switch ( $type ){
    
            case 'abandoned':
    
                $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_abandoned = $wpdb->get_results($query_abandoned);
                $products_id_array = array();
                
                if ( count ( $results_abandoned ) > 0 ){
                    foreach ($results_abandoned as $results_abandoned_key => $results_abandoned_value) {
                        $cart_info =  json_decode ($results_abandoned_value->abandoned_cart_info) ;
                        if ( count( $cart_info ) > 0 && ( is_array( $cart_info ) || is_object( $cart_info ) ) ) {
                            foreach ( $cart_info as $cart_info_key => $cart_info_value){
                                
                                foreach ($cart_info_value as $cart_info_value_key => $cart_info_value_of_value ){
                                    $products_id_array [] = $cart_info_value_of_value->product_id;
                                }
                            }
                        }
                    }   
                }
                if ( count ($products_id_array) > 0 ){
                    $products_id_values = array_count_values ( $products_id_array );
                    /*
                     * It will search for the highest value of the recover product
                     * Then in array it will search for that Value and return the Key ( product id ) fo that.
                     */
                    $highest_abandoned_value = max($products_id_values);
                    
                    $product_id   = array_search ( $highest_abandoned_value, $products_id_values );
                }
                
                break;
    
            case 'recover':
    
                $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
                $results_recover = $wpdb->get_results( $query_recover );
    
                $products_id_array = array();
    
                if ( count ( $results_recover ) > 0 ){
                    foreach ($results_recover as $results_recover_key => $results_recover_value) {
                        $cart_info =  json_decode ($results_recover_value->abandoned_cart_info) ;

                        foreach ( $cart_info as $cart_info_key => $cart_info_value){
                            
                            foreach ($cart_info_value as $cart_info_value_key => $cart_info_value_of_value ){
                                $products_id_array [] = $cart_info_value_of_value->product_id;
                            }
                        }
                    }   
                }
                if ( count ($products_id_array) > 0 ){
                    $products_id_values = array_count_values ( $products_id_array );
                    /*
                     * It will search for the highest value of the recover product
                     * Then in array it will search for that Value and return the Key ( product id ) fo that.
                     */
                    $highest_recover_value = max($products_id_values);
                    $product_id   = array_search ( $highest_recover_value, $products_id_values );
                }
    
                break;
        }
        return $product_id;
    }

}