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

class Wcap_Dashboard_Report {
    
    function get_begin_of_month ( $selected_data_range, $user_start_date ){
        
        $current_time = current_time ('timestamp');
        $begin_date   = '';
        switch ( $selected_data_range ){
            case 'this_month':
                $begin_date = mktime(00, 01, 01, date("n"), 1);
                break;
        
            case 'last_month':
                $begin_date = mktime(00, 01, 01, date("n") - 1, 1);
                break;
                
            case 'this_quarter':
                
                $current_month = date('m');
                $current_year = date('Y');
                
                if($current_month>=1 && $current_month<=3) {
                    $begin_date = strtotime('1-January-'.$current_year. '00:01:01' );  // timestamp or 1-Januray 12:00:00 AM
                }
                else  if($current_month>=4 && $current_month<=6) {
                    $begin_date = strtotime('1-April-'.$current_year. '00:01:01' );  // timestamp or 1-April 12:00:00 AM
                }
                else  if($current_month>=7 && $current_month<=9) {
                    $begin_date = strtotime('1-July-'.$current_year. '00:01:01' );  // timestamp or 1-July 12:00:00 AM
                }
                else  if($current_month>=10 && $current_month<=12) {
                    $begin_date = strtotime('1-October-'.$current_year. '00:01:01' );  // timestamp or 1-October 12:00:00 AM
                }
                
                break;
                
            case 'last_quarter':
                
                $current_month = date('m');
                $current_year = date('Y');
                
                if( $current_month >= 1 && $current_month <= 3 ) {
                    $begin_date = strtotime( '1-October-'.($current_year-1). '00:01:01' );  // timestamp or 1-October Last Year 12:00:00 AM
                }
                else if( $current_month >= 4 && $current_month <= 6 ) {
                    $begin_date = strtotime( '1-January-'.$current_year . '00:01:01');  // timestamp or 1-Januray 12:00:00 AM
                }
                else if( $current_month >= 7 && $current_month <= 9 ) {
                    $begin_date = strtotime( '1-April-'.$current_year. '00:01:01');  // timestamp or 1-April 12:00:00 AM
                }
                else if( $current_month >= 10 && $current_month <= 12 ) {
                    $begin_date = strtotime( '1-July-'.$current_year. '00:01:01');  // timestamp or 1-July 12:00:00 AM
                }
                break;

            case 'this_year':
                $begin_date =  mktime( 00, 01, 01, 1, 1, date('Y') );;
                break;
                
            case 'last_year':
                $begin_date = mktime( 00, 01, 01, 1, 1, date('Y')-1 );
                break;
            
            case 'other':
                $explode_start_date = explode ( "-", $user_start_date );
                $month              = $explode_start_date[1];
                $date               = $explode_start_date[2];
                $year               = $explode_start_date[0];
                $begin_date         = mktime( 00, 01, 01, $month, $date, $year );
                break;
        }
        return $begin_date;
    }
    
    function get_end_of_month ( $selected_data_range, $user_end_date ){
    
        $current_time = current_time ('timestamp');
        $end_date   = '';
        switch ( $selected_data_range ){
            case 'this_month':
                $end_date = mktime( 23, 59, 59, date("n"), date("t") );
                break;
    
            case 'last_month':
                $end_date = mktime( 23, 59, 59, date("n") - 1, date("t") - 1 );
                break;
    
            case 'this_quarter':
                $current_month = date('m');
                $current_year = date('Y');
                
                if( $current_month >= 1 && $current_month <= 3) {
                    $end_date = strtotime( '31-March-'.$current_year.'23:59:59' );  // timestamp or 1-April 12:00:00 AM means end of 31 March
                }
                else if( $current_month >= 4 && $current_month <=6 ) {
                    $end_date = strtotime( '30-July-'.$current_year.'23:59:59' );  // timestamp or 1-July 12:00:00 AM means end of 30 June
                }
                else if( $current_month >= 7 && $current_month <= 9 ) {
                    $end_date = strtotime( '30-September-'.$current_year.'23:59:59' );  // timestamp or 1-October 12:00:00 AM means end of 30 September
                }
                else if( $current_month >= 10 && $current_month <= 12) {
                    $end_date = strtotime( '1-January-'.( $current_year + 1 ).'23:59:59');  // timestamp or 1-January Next year 12:00:00 AM means end of 31 December this year
                }
                
                break;
    
            case 'last_quarter':
                
                $current_month = date('m');
                $current_year = date('Y');
                
                if( $current_month >= 1 && $current_month <= 3 ) {
                    $end_date = strtotime( '1-January-'.$current_year.'23:59:59' );  // // timestamp or 1-January  12:00:00 AM means end of 31 December Last year
                }
                else if( $current_month >= 4 && $current_month <= 6 ) {
                    $end_date = strtotime( '31-March-'.$current_year.'23:59:59' );  // timestamp or 1-April 12:00:00 AM means end of 31 March
                }
                else if( $current_month >= 7 && $current_month <= 9 ) {
                    $end_date = strtotime( '30-June-'.$current_year.'23:59:59' );  // timestamp or 1-July 12:00:00 AM means end of 30 June
                }
                else if( $current_month >= 10 && $current_month <= 12 ) {
                    $end_date = strtotime( '30-September-'.$current_year.'23:59:59' );  // timestamp or 1-October 12:00:00 AM means end of 30 September
                }
                break;
    
            case 'this_year':
                
                $end_date =  mktime( 23, 59, 59, date('m'), date('d'), date('y') ); // it will restrict date from todays date. so Jan 1st to the current date
                break;
    
            case 'last_year':
                $end_date = mktime( 23, 59, 59, 12, 31, date('Y')-1 );
                break;
                
            case 'other':
                $explode_end_date = explode ( "-", $user_end_date );
                $month            = $explode_end_date[1];
                $date             = $explode_end_date[2];
                $year             = $explode_end_date[0];
                $end_date         = mktime( 23, 59, 59, $month, $date, $year );
                break;
        }
        return $end_date;
    }
    
    function get_this_month_amount_reports ( $type, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
    
        $count_month = 0;
        $begin_of_month = $this->get_begin_of_month ( $selected_data_range, $start_date ); //mktime(0, 0, 0, date("n"), 1);
        $end_of_month   = $this->get_end_of_month   ( $selected_data_range, $end_date ); //mktime(23, 59, 0, date("n"), date("t"));
    
        switch ( $type ){
            case 'recover':
                $count_month = $this->get_current_month_recovered_amount ( $begin_of_month, $end_of_month);
                break;
    
            case 'wc_total_sales':
                $count_month = $this->get_wc_total_sales ( $begin_of_month, $end_of_month);
                break;
        }
        return $count_month;
    }
    
    function get_this_month_number_reports ( $type, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
    
        $count = 0;
        $current_time = current_time ('timestamp');
    
        $begin_of_month = $this->get_begin_of_month ( $selected_data_range, $start_date ); //mktime(0, 0, 0, date("n"), 1);
        $end_of_month   = $this->get_end_of_month   ( $selected_data_range, $end_date ); //mktime(23, 59, 0, date("n"), date("t"));
    
        switch ( $type ){
    
            case 'abandoned':
                $count = $this->get_current_month_abandoned_count ( $begin_of_month, $end_of_month);
                break;
    
            case 'recover':
                $count = $this->get_current_month_recovered_count ( $begin_of_month, $end_of_month);
                break;
        }
        return $count;
    }
    
    function get_this_month_total_vs_abandoned_order ( $type, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
    
        $count = 0;
        $current_time = current_time ('timestamp');
    
        $begin_of_month = $this->get_begin_of_month ( $selected_data_range, $start_date );
        $end_of_month   = $this->get_end_of_month   ( $selected_data_range, $end_date );
    
        switch ( $type ){
    
            case 'abandoned':
                $count = $this->get_current_month_abandoned_count ( $begin_of_month, $end_of_month);
                break;
    
            case 'wc_total_orders':
                $count = $this->get_this_month_wc_total_order_count ( $begin_of_month, $end_of_month);
                break;
        }
        return $count;
    }
    
    function wcap_get_email_report ( $type, $selected_data_range, $user_start_date, $user_end_date ){
    
        $total_sent_email_count = 0;
        
        $begin_date             = $this->get_begin_of_month ( $selected_data_range, $user_start_date );
        $end_date               = $this->get_end_of_month   ( $selected_data_range, $user_end_date );
        
        $start_date_db          = date( 'Y-m-d H:i:s', $begin_date );
        $end_date_db            = date( 'Y-m-d H:i:s', $end_date );
        switch ( $type ){
        
            case 'total_sent':
                $count = $this->wcap_get_total_email_sent_count ( $start_date_db, $end_date_db);
                break;
        
            case 'total_opened':
                $count = $this->wcap_get_total_emails_opened ( $start_date_db, $end_date_db);
                break;
                
            case 'total_clicked':
                $count = $this->wcap_get_total_emails_clicked ( $start_date_db, $end_date_db);
                break;
        }
        return $count;
    }
    
    function wcap_get_total_email_sent_count ( $start_date_db, $end_date_db ){
        global $wpdb;
        
        $query_ac_sent          = "SELECT * FROM " . $wpdb->prefix . "ac_sent_history WHERE sent_time >= %s AND sent_time <= %s ORDER BY `id` DESC";
        $ac_results_sent        = $wpdb->get_results( $wpdb->prepare( $query_ac_sent, $start_date_db, $end_date_db ) );
        
        $total_sent_email_count = count ( $ac_results_sent );
        
        return $total_sent_email_count;
    }
    
    function wcap_get_total_emails_opened ( $start_date_db, $end_date_db ){
        global $wpdb;
        
        $query_ac_opened        = "SELECT DISTINCT wpoe.email_sent_id, wpsh . id FROM " . $wpdb->prefix . "ac_opened_emails as wpoe LEFT JOIN ".$wpdb->prefix."ac_sent_history AS wpsh ON wpsh.id = wpoe.email_sent_id WHERE time_opened >= '" . $start_date_db . "' AND time_opened <= '" . $end_date_db . "' AND wpsh.id = wpoe.email_sent_id ORDER BY id DESC ";
		$ac_results_opened      = $wpdb->get_results( $query_ac_opened, ARRAY_A );
    
        $wcap_opened_emails     = count ( $ac_results_opened );
    
        return $wcap_opened_emails;
    }
    
    function wcap_get_total_emails_clicked ( $start_date_db, $end_date_db ){
        global $wpdb;
    
        $query_ac_clicked       = "SELECT DISTINCT email_sent_id FROM " . $wpdb->prefix . "ac_link_clicked_email WHERE time_clicked >= '" . $start_date_db . "' AND time_clicked <= '" . $end_date_db . "' ORDER BY id DESC ";
		$ac_results_clicked     = $wpdb->get_results( $query_ac_clicked, ARRAY_A );
    
        $wcap_opened_clicked     = count ( $ac_results_clicked );
    
        return $wcap_opened_clicked;
    }
    
    function get_wc_total_sales ( $begin_of_month, $end_of_month ){
        
        global $wpdb;
        $count_month         = 0;
        $begin_date_of_month = date( 'Y-m-d H:i:s', $begin_of_month );
        $end_date_of_month   = date( 'Y-m-d H:i:s', $end_of_month );
        
        $order_totals = $wpdb->get_row( "
        
            SELECT SUM(meta.meta_value) AS total_sales, COUNT(posts.ID) AS total_orders FROM {$wpdb->posts} AS posts
        
            LEFT JOIN {$wpdb->postmeta} AS meta ON posts.ID = meta.post_id
        
            WHERE meta.meta_key = '_order_total'
        
            AND posts.post_type = 'shop_order'
        
            AND posts.post_date >= '$begin_date_of_month'
        
            AND posts.post_date <= '$end_date_of_month'
        
            AND posts.post_status IN ( '" . implode( "','", array( 'wc-completed', 'wc-processing', 'wc-on-hold' ) ) . "' )
        
            " ) ;
        
            $count_month = $order_totals->total_sales == null ? 0 : $order_totals->total_sales ;
            
        return $count_month;
    }
    
    function get_current_month_recovered_amount ( $begin_of_month, $end_of_month ){
        global $wpdb;
        $count_month = 0;
        $start_date                 = $begin_of_month;
        $end_date                   = $end_of_month;
        $blank_cart_info            =  '{"cart":[]}';
        $blank_cart_info_guest      =  '[]';
        $query_ac                   = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d AND recovered_cart > 0 ORDER BY recovered_cart desc";
        $ac_results                 = $wpdb->get_results( $wpdb->prepare( $query_ac, $blank_cart_info, $blank_cart_info_guest, $start_date, $end_date ) );
        $recovered_item             = $recovered_total = $count_carts = $total_value = $order_total = 0;
        $i                          = 1;
        $recovered_order_total      = 0;
        $this->total_recover_amount = round( $recovered_order_total, wc_get_price_decimals() )  ;
        $count_month                = 0;
        $table_data                 = "";
        foreach ( $ac_results as $key => $value ) {
            if( 0 != $value->recovered_cart ) {
                $recovered_id       = $value->recovered_cart;
                $rec_order          = get_post_meta( $recovered_id );
                if ( isset( $rec_order ) && $rec_order != false ) {
                    $recovered_total += $rec_order['_order_total'][0];
                }
                $recovered_order_total        = 0;
                if ( isset( $rec_order['_order_total'][0] ) ) {
                    $recovered_order_total = $rec_order['_order_total'][0];
                }
                $count_month = round( ( $recovered_order_total + $count_month ) , wc_get_price_decimals() )  ;
                $i++;
            }
        }
        return $count_month;
    }
    
    function get_current_month_abandoned_amount ( $begin_of_month, $end_of_month ){
    
        global $wpdb;
        $count_month             = 0;
        $start_date              = $begin_of_month;
        $end_date                = $end_of_month;
        $blank_cart_info         =  '{"cart":[]}';
        $blank_cart_info_guest   =  '[]';
        
        $query_ac_carts          = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time >= %d AND abandoned_cart_time <= %d";
        $ac_carts_results        = $wpdb->get_results( $wpdb->prepare( $query_ac_carts, $blank_cart_info, $blank_cart_info_guest, $start_date, $end_date ) );
        
        $recovered_item          = $recovered_total = $count_carts = $total_value = $order_total = 0;
        
        foreach ( $ac_carts_results as $key => $value ) {
        
            $count_carts += 1;
            $cart_detail = json_decode( $value->abandoned_cart_info );
            $product_details = array();
            if( isset( $cart_detail->cart ) ){
                $product_details = $cart_detail->cart;
            }
            $line_total = 0;
            if ( isset( $product_details ) && count( $product_details ) > 0 && $product_details != false ) {
        
                foreach ( $product_details as $k => $v ) {
                    if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                        $line_total = $line_total + $v->line_total + $v->line_subtotal_tax;
                    } else {
                        $line_total = $line_total + $v->line_total;
                    }
                }
            }
            $total_value += $line_total;
        }
        $count_month = round( $total_value, wc_get_price_decimals() );
        return $count_month;
    }
    
    function get_current_month_abandoned_count ( $begin_of_month, $end_of_month ){
    
        global $wpdb;
        $count_month             = 0;
        $blank_cart_info         =  '{"cart":[]}';
        $blank_cart_info_guest   =  '[]';
        
        $ac_cutoff_time = get_option( 'ac_cart_abandoned_time' );
        $cut_off_time   = $ac_cutoff_time * 60;
        
        $current_time   = current_time( 'timestamp' );
        $compare_time   = $current_time - $cut_off_time;
    
        $query_abandoned  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $begin_of_month AND abandoned_cart_time <= $end_of_month AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_time <= '$compare_time'";
        $results_abandoned = $wpdb->get_results($query_abandoned);

        if ( count ( $results_abandoned ) > 0 ){
            $count_month = count ( $results_abandoned );
        }
        return $count_month;
    }
    
    function get_current_month_recovered_count ( $begin_of_month, $end_of_month ){
    
        global $wpdb;
        $count_month             = 0;
        $blank_cart_info         =  '{"cart":[]}';
        $blank_cart_info_guest   =  '[]';
    
        $query_recover  = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE abandoned_cart_time >=  $begin_of_month AND abandoned_cart_time <= $end_of_month AND recovered_cart != 0 AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ";
        $results_recover = $wpdb->get_results( $query_recover );

        if ( count ( $results_recover ) > 0 ){
            $count_month = count ( $results_recover );
        }
        return $count_month;
    }
    
    function get_this_month_wc_total_order_count ( $begin_of_month, $end_of_month ){
    
        global $wpdb;
        $count_month             = 0;
    
        $begin_date_of_month = date( 'Y-m-d H:i:s', $begin_of_month );
        $end_date_of_month   = date( 'Y-m-d H:i:s', $end_of_month );
        
        $sales =  "SELECT ID FROM {$wpdb->posts} as posts
        WHERE posts.post_type     = 'shop_order'
        AND   posts.post_date >= '$begin_date_of_month'
        AND   posts.post_date <= '$end_date_of_month' 
        AND   posts.post_status   IN ( '" . implode( "','", array( 'wc-completed', 'wc-processing', 'wc-on-hold' ) ) . "' ) ";
       
        $get_orders =   $wpdb->get_results( $sales );
    
        if ( count ( $get_orders ) > 0 ){
            $count_month = count ( $get_orders );
        }
        return $count_month;
    }
    
    /*
     * Templates stats on dashboard 
     */
    
    function wcap_get_total_email_sent_for_template ( $wcap_template_id, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
    
        $begin_of_month      = $this->get_begin_of_month ( $selected_data_range, $start_date ); //mktime(0, 0, 0, date("n"), 1);
        $end_of_month        = $this->get_end_of_month   ( $selected_data_range, $end_date );
        
        $begin_date_of_month = date( 'Y-m-d H:i:s', $begin_of_month );
        $end_date_of_month   = date( 'Y-m-d H:i:s', $end_of_month );
    
        $query_no_emails        = "SELECT * FROM " . $wpdb->prefix . "ac_sent_history WHERE template_id= %d AND sent_time >=  %s AND sent_time <= %s ";
        $number_emails          = $wpdb->get_results( $wpdb->prepare( $query_no_emails, $wcap_template_id, $begin_date_of_month, $end_date_of_month ) );
    
        $wcap_emails_sent_count	= count( $number_emails );
    
        return $wcap_emails_sent_count;
    }
    
    function wcap_get_total_email_open_for_template ( $wcap_template_id, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
        
        $begin_of_month      = $this->get_begin_of_month ( $selected_data_range, $start_date ); //mktime(0, 0, 0, date("n"), 1);
        $end_of_month        = $this->get_end_of_month   ( $selected_data_range, $end_date );
        
        $begin_date_of_month = date( 'Y-m-d H:i:s', $begin_of_month );
        $end_date_of_month   = date( 'Y-m-d H:i:s', $end_of_month );
    
        $query_ac_opened     = "SELECT DISTINCT wpoe.email_sent_id, wpsh.id
                                FROM " . $wpdb->prefix . "ac_opened_emails AS wpoe
                                LEFT JOIN ".$wpdb->prefix."ac_sent_history AS wpsh ON wpsh.id = wpoe.email_sent_id
                                WHERE time_opened >=  '$begin_date_of_month'
                                AND time_opened <=  '$end_date_of_month'
                                AND wpsh.id = wpoe.email_sent_id
                                AND wpsh.template_id = '$wcap_template_id'
                                ORDER BY wpoe.id DESC";
        
        $ac_results_opened   = $wpdb->get_results( $query_ac_opened, ARRAY_A );
        $wcap_emails_opened_count = count( $ac_results_opened );
    
        return $wcap_emails_opened_count;
    }
    
    function wcap_get_total_email_click_for_template ( $wcap_template_id, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
    
        $begin_of_month      = $this->get_begin_of_month ( $selected_data_range, $start_date ); //mktime(0, 0, 0, date("n"), 1);
        $end_of_month        = $this->get_end_of_month   ( $selected_data_range, $end_date );
    
        $begin_date_of_month = date( 'Y-m-d H:i:s', $begin_of_month );
        $end_date_of_month   = date( 'Y-m-d H:i:s', $end_of_month );
    
        $query_ac_clicked    = "SELECT wplc.email_sent_id, wpsh . id FROM " . $wpdb->prefix . "ac_link_clicked_email as wplc LEFT JOIN ".$wpdb->prefix."ac_sent_history AS wpsh ON wpsh.id = wplc.email_sent_id WHERE wpsh.template_id = $wcap_template_id AND wpsh.sent_time >= '$begin_date_of_month' AND wpsh.sent_time <= '$end_date_of_month' ";
        $ac_results_clicked  = $wpdb->get_results( $query_ac_clicked, ARRAY_A );
    
        $wcap_emails_clikced_count = count( $ac_results_clicked );
    
        return $wcap_emails_clikced_count;
    }
    
    function wcap_get_total_email_recover_for_template ( $wcap_template_id, $selected_data_range, $start_date, $end_date ){
        global $wpdb;
    
        $begin_of_month      = $this->get_begin_of_month ( $selected_data_range, $start_date ); //mktime(0, 0, 0, date("n"), 1);
        $end_of_month        = $this->get_end_of_month   ( $selected_data_range, $end_date );
    
        $begin_date_of_month = date( 'Y-m-d H:i:s', $begin_of_month );
        $end_date_of_month   = date( 'Y-m-d H:i:s', $end_of_month );
    
        $query_recovered_orders = "SELECT * FROM " . $wpdb->prefix . "ac_sent_history WHERE template_id= %d AND recovered_order != '0' AND sent_time >=  %s AND sent_time <= %s";
        $number_order_recovered = $wpdb->get_results( $wpdb->prepare( $query_recovered_orders, $wcap_template_id, $begin_date_of_month, $end_date_of_month ) );
    
        $number_order_recovered_count	= count( $number_order_recovered );
    
        return $number_order_recovered_count;
    }
}