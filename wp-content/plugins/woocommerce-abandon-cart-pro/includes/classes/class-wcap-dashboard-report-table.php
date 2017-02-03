<?php
/**
 * WCAP Dashboard report
 *
 * @package     WooCommerce Abandon Cart Plugin
 * @subpackage  Dashboard
 * @copyright   Copyright (c) 2015, Tyche Softwares
 * @since       3.5
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class Wcap_Dashboard_Report_Action {
    
    function wcap_get_all_reports( $selected_data_range, $start_date, $end_date ){
        
        global $wpdb;
        
        include_once( 'class-wcap-dashboard-report.php' );
        
        $wcap_month_total_orders_amount   = $wcap_month_recovered_cart_amount = 0;
        $wcap_month_recovered_cart_count  = $wcap_month_abandoned_cart_count  = 0;
        $ratio_of_recovered_number        = 0;
        $wcap_month_wc_orders             = 0;
        $ratio_of_recovered               = 0;
        $ratio_of_total_vs_abandoned      = 0;
        
        $orders = new Wcap_Dashboard_Report();
        
        $wcap_month_total_orders_amount   = $orders->get_this_month_amount_reports( 'wc_total_sales' , $selected_data_range, $start_date, $end_date );
        $wcap_month_recovered_cart_amount = $orders->get_this_month_amount_reports( 'recover'        , $selected_data_range, $start_date, $end_date );
        
        // if total order amount goes less than zero, then set it to 0.
        if ( $wcap_month_total_orders_amount < 0 ){
            
            $wcap_month_total_orders_amount = 0 ;
        }
        if ( $wcap_month_recovered_cart_amount > 0 && $wcap_month_total_orders_amount > 0 ){
            $ratio_of_recovered            = ( $wcap_month_recovered_cart_amount / $wcap_month_total_orders_amount ) * 100;
            $ratio_of_recovered            = round( $ratio_of_recovered, wc_get_price_decimals() );
        }
        
        $wcap_month_abandoned_cart_count   = $orders->get_this_month_number_reports( 'abandoned', $selected_data_range, $start_date, $end_date  );
        $wcap_month_recovered_cart_count   = $orders->get_this_month_number_reports( 'recover'  , $selected_data_range, $start_date, $end_date );
        
        if ( $wcap_month_recovered_cart_count > 0 && $wcap_month_abandoned_cart_count > 0 ){
            $ratio_of_recovered_number     = ( $wcap_month_recovered_cart_count / $wcap_month_abandoned_cart_count ) * 100;
            $ratio_of_recovered_number     = round( $ratio_of_recovered_number, wc_get_price_decimals() );
        }
        
        $wcap_month_wc_orders              = $orders->get_this_month_total_vs_abandoned_order( 'wc_total_orders', $selected_data_range, $start_date, $end_date );
        
        if ( $wcap_month_abandoned_cart_count > 0 && $wcap_month_wc_orders > 0 ){
            $ratio_of_total_vs_abandoned   = ( $wcap_month_abandoned_cart_count / $wcap_month_wc_orders  ) * 100;
            $ratio_of_total_vs_abandoned   = round( $ratio_of_total_vs_abandoned, wc_get_price_decimals() );
        }
        
        $wcap_email_sent_count             = $orders->wcap_get_email_report( "total_sent", $selected_data_range, $start_date, $end_date );
        
        $wcap_email_opened_count           = $orders->wcap_get_email_report( "total_opened", $selected_data_range, $start_date, $end_date );
        
        $wcap_email_clicked_count          = $orders->wcap_get_email_report( "total_clicked", $selected_data_range, $start_date, $end_date );
        
        ?>
        <div class = "wrap woocommerce" id="main-div" >
            <div id = "poststuff" >
                <div class = "postbox" >
                    <div class = "inside">
                        <div class = "wcap_dashboard_report_filter">
                            <form id="wcap_report_search" method="get" >
                            <input type="hidden" name="page" value="woocommerce_ac_page" />
                                <?php 
                                $this->search_by_date();
                                ?>
                            </form>
                        </div>
                        <div>
                            <?php 
                            $this->wcap_get_total_vs_recovered_revenue   ( $wcap_month_recovered_cart_amount );
                            $this->wcap_get_abandoned_vs_recovered_number( $wcap_month_recovered_cart_count );
                            $this->wcap_get_total_vs_abandoned_number    ( $wcap_month_abandoned_cart_count );
                            ?>
                       </div>
                       
                        <div class="chart-container">
                            <div id = "abandoned_vs_recovered_amount" class="chart-placeholder abandoned_vs_recovered_amount pie-chart"> </div>
                            <div id = "abandoned_vs_recovered_cart_number" class="chart-placeholder abandoned_vs_recovered_cart_number pie-chart" > </div>
                            <div id = "total_orders_vs_abandoned_orders_number" class="chart-placeholder total_orders_vs_abandoned_orders_number pie-chart" > </div> 
                       </div>
                       
                       <div>
                            <?php 
                            $this->wcap_get_total_vs_recovered_revenue_ratio    ( $ratio_of_recovered );
                            $this->wcap_get_abandoned_vs_recovered_number_ratio ( $ratio_of_recovered_number );
                            $this->wcap_get_total_vs_abandoned_number_ratio     ( $ratio_of_total_vs_abandoned );
                            
                            wp_register_script( 'wcap-dashboard-create-report', plugins_url()  . '/woocommerce-abandon-cart-pro/assets/js/wcap_create_reports.js', array( 'jquery' ) );
                            wp_enqueue_script( 'wcap-dashboard-create-report' );
                            
                            wp_localize_script( 'wcap-dashboard-create-report', 'wcap_dashboard_create_report_params', array(
                                'this_month_total_orders_amount'   => $wcap_month_total_orders_amount,
                                'this_month_recovered_cart_amount' => $wcap_month_recovered_cart_amount,
                                'this_month_abandoned_cart_count'  => $wcap_month_abandoned_cart_count,
                                'this_month_recovered_cart_count'  => $wcap_month_recovered_cart_count,
                                'this_month_wc_orders'             => $wcap_month_wc_orders,
                                'wcap_email_sent_count'            => $wcap_email_sent_count,
                                'wcap_email_opened'                => $wcap_email_opened_count,
                                'wcap_email_clicked'               => $wcap_email_clicked_count,
                                'recovered'                        => __( 'recovered',         'woocommerce-ac' ),
                                'total_revenue'                    => __( 'Total Revenue',     'woocommerce-ac' ),
                                'recovered_revenue'                => __( 'Recovered Revenue', 'woocommerce-ac' ),
                                'abandoned_carts'                  => __( 'Abandoned Carts',   'woocommerce-ac' ),
                                'recovered_carts'                  => __( 'Recovered Carts',   'woocommerce-ac' ),
                                'total_orders'                     => __( 'Total Carts',       'woocommerce-ac' ),
                                'abandoned_orders'                 => __( 'Abandoned Carts',   'woocommerce-ac' ),
                                'email_sent'                       => __( 'Emails Sent',       'woocommerce-ac' ),
                                'email_opened'                     => __( 'Emails Opened',     'woocommerce-ac' ),
                                'email_not_opened'                 => __( 'Emails Not Opened', 'woocommerce-ac' ),
                                'click_rate'                       => __( 'Click Rate',        'woocommerce-ac' ),
                            ) );
                            ?>
                       </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class = "wrap woocommerce wcap_email_report_parent_div" id="main-div wcap_email_report_parent_div">
            <div id = "poststuff" >
                <div class = "postbox"  >
                    <div class = "inside" >
                        <div>
                            <?php 
                            $wcap_emails_clicked     = 0;
                            $wcap_email_opened_ratio = 0;
                            $this->wcap_get_total_email_sent ( $wcap_email_sent_count );
                            
                            if ( $wcap_email_opened_count > 0 && $wcap_email_sent_count > 0 ){
                                $wcap_email_opened_ratio =       ( $wcap_email_opened_count / $wcap_email_sent_count ) * 100 ;
                                $wcap_email_opened_ratio =  round( $wcap_email_opened_ratio, wc_get_price_decimals() );
                            }
                            $this->wcap_get_email_opened     ( $wcap_email_opened_ratio );
                            
                            if ( $wcap_email_clicked_count > 0 && $wcap_email_opened_count > 0 ){
                                $wcap_emails_clicked    =        ( $wcap_email_clicked_count / $wcap_email_opened_count ) * 100 ;
                                $wcap_emails_clicked    =   round( $wcap_emails_clicked, wc_get_price_decimals() );
                            }
                            $this->wcap_abandoned_email_clicked    ( $wcap_emails_clicked );
                            ?>
                       </div>
                       
                        <div class="chart-container">
                            <div id = "wcap_abandoned_email_sent" class="chart-placeholder-email wcap_abandoned_email_sent pie-chart"> </div>
                            <div id = "wcap_abandoned_email_opened" class="chart-placeholder-email wcap_abandoned_email_opened pie-chart"> </div>
                            <div id = "wcap_abandoned_email_clicked" class="chart-placeholder-email wcap_abandoned_email_clicked pie-chart"> </div>
                       </div>
                       
                       <div>
                            <?php 
                            $this->wcap_get_total_email_sent_ratio  ( $wcap_email_sent_count );
                            $this->wcap_abandoned_email_opened_ratio( $wcap_email_opened_count,  $wcap_email_sent_count );
                            $this->wcap_emails_clicked_ratio        ( $wcap_email_clicked_count, $wcap_email_opened_count );
                            ?>
                       </div>
                    </div>
                </div>
            </div>
        </div>
        <br>
       
       <div id="wcap_template_report_dashboard" class="wcap_template_report_dashboard">
        
            <h1 style = "margin-bottom: 2%;"> Campaign Stats</h1> 
            <?php
            
                $wcap_get_all_template         = "SELECT wpet . * FROM `" . $wpdb->prefix . "ac_email_templates` AS wpet ORDER BY day_or_hour desc , frequency asc";
                $wcap_get_all_template_results = $wpdb->get_results( $wcap_get_all_template );
            
                $counter = 1;
                $serial_number = 1;
                foreach ( $wcap_get_all_template_results as $wcap_get_all_template_results_key => $wcap_get_all_template_results_value ){ 
                    if ( ($counter % 2) == 0 ){
                        ?>
                        <div id = "wcap_template_dashboard_second" >
                            <?php
                            $template_id = $wcap_get_all_template_results_value->id;
                            $this->wcap_create_template_section ( $template_id, $serial_number, $selected_data_range, $start_date, $end_date );
                            ?>
                        </div>
                        <?php 
                        $counter++;
                        $serial_number++;
                    }else if ( ($counter % 3) == 0 ){
                        ?>
                        <div id = "wcap_template_dashboard_third">
                            <?php 
                            $template_id = $wcap_get_all_template_results_value->id;
                            $this->wcap_create_template_section ( $template_id, $serial_number, $selected_data_range, $start_date, $end_date );
                            
                            ?>
                        </div>
                        <?php 
                        $counter++;
                        $serial_number++;
                    }else {
                    
                        ?>
                        <div id = "wcap_template_dashboard_first">
                            <?php 
                            $template_id = $wcap_get_all_template_results_value->id;
                            $this->wcap_create_template_section ( $template_id, $serial_number, $selected_data_range, $start_date, $end_date );
                            ?>
                        </div>
                        <?php 
                        $counter++;
                        $serial_number++;
                    }
                    if ( $counter > 3 ){
                        $counter = 1;
                    }
                }
            ?>
        </div>
        <br>
        <?php 
    }
    
    /*
     * All middle value of Pie
     */
    function wcap_get_total_vs_recovered_revenue ( $wcap_month_recovered_cart_amount ){
        ?>
        <div id ="abandoned_vs_recovered_amount_price" class ="abandoned_vs_recovered_amount_price" >
            <span id ="abandoned_vs_recovered_amount_span" class ="abandoned_vs_recovered_amount_span" > 
                <?php echo wc_price( $wcap_month_recovered_cart_amount ) ; ?>  
            </span>
        </div>
        <div id ="abandoned_vs_recovered_amount_price_text" class ="abandoned_vs_recovered_amount_price_text" >
            <span id ="abandoned_vs_recovered_amount_price_text_span" class ="abandoned_vs_recovered_amount_price_text_span" > 
                <?php echo "Recovered <br>Revenue"; ?>  
            </span>
        </div>
        <?php 
    }
    
    function wcap_get_abandoned_vs_recovered_number( $wcap_month_recovered_cart_count ){
        ?>
        <div id ="abandoned_vs_recovered_cart_number_div" class ="abandoned_vs_recovered_cart_number_div" >
            <span id ="abandoned_vs_recovered_cart_number_div_span" class ="abandoned_vs_recovered_cart_number_div_span" > 
                <?php echo $wcap_month_recovered_cart_count ; ?>  
            </span>
        </div>
        <div id ="abandoned_vs_recovered_cart_number_div_text" class ="abandoned_vs_recovered_cart_number_div_text" >
            <span id ="abandoned_vs_recovered_cart_number_div_text_span" class ="abandoned_vs_recovered_cart_number_div_text_span" > 
                <?php echo "Recovered <br>Carts"; ?>  
            </span>
        </div>
        <?php 
    }
    
    function wcap_get_total_vs_abandoned_number ( $wcap_month_abandoned_cart_count ) {
        ?>
        <div id ="total_orders_vs_abandoned_orders_number_div" class ="total_orders_vs_abandoned_orders_number_div" >
            <span id ="total_orders_vs_abandoned_orders_number_div_span" class ="total_orders_vs_abandoned_orders_number_div_span" > 
                <?php echo $wcap_month_abandoned_cart_count ; ?>  
            </span>
        </div>
        <div id ="total_orders_vs_abandoned_orders_number_div_text" class ="total_orders_vs_abandoned_orders_number_div_text" >
            <span id ="total_orders_vs_abandoned_orders_number_div_text_span" class ="total_orders_vs_abandoned_orders_number_div_text_span" > 
                <?php echo "Abandoned <br>Orders"; ?>
                
            </span>
        </div>
        <?php 
    }
    /*
     * All Ratio here
     */

    function wcap_get_total_vs_recovered_revenue_ratio ( $ratio_of_recovered ){
        ?>
        <div id ="abandoned_vs_recovered_amount_price_ratio" class ="abandoned_vs_recovered_amount_price_ratio">
            <span id ="abandoned_vs_recovered_amount_span_ratio" class ="abandoned_vs_recovered_amount_span_ratio"> 
                <?php echo $ratio_of_recovered. "%";  ?> 
            </span>
        </div>
        <div id ="abandoned_vs_recovered_amount_price_text_ratio" class ="abandoned_vs_recovered_amount_price_text_ratio" > 
            <span id ="abandoned_vs_recovered_amount_price_text_span_ratio" class ="abandoned_vs_recovered_amount_price_text_span_ratio"> 
                <?php echo "of Total Revenue";  ?> 
            </span>
        </div>
        <?php 
    }
    
    function wcap_get_abandoned_vs_recovered_number_ratio ( $ratio_of_recovered_number ) {
        ?>
        <div id ="abandoned_vs_recovered_cart_number_div_ratio" class ="abandoned_vs_recovered_cart_number_div_ratio">
            <span id ="abandoned_vs_recovered_cart_number_div_span_ratio" class ="abandoned_vs_recovered_cart_number_div_span_ratio"> 
                <?php echo $ratio_of_recovered_number. "%";  ?> 
            </span>
        </div>
        <div id ="abandoned_vs_recovered_cart_number_div_text_ratio" class ="abandoned_vs_recovered_cart_number_div_text_ratio" > 
            <span id ="abandoned_vs_recovered_cart_number_div_text_span_ratio" class ="abandoned_vs_recovered_cart_number_div_text_span_ratio"> 
                <?php echo "of Abandoned Carts";  ?> 
            </span>
        </div>
        <?php 
    }
    
    function wcap_get_total_vs_abandoned_number_ratio ( $ratio_of_total_vs_abandoned ){
        ?>
        <div id ="total_orders_vs_abandoned_orders_number_div_ratio" class ="total_orders_vs_abandoned_orders_number_div_ratio">
            <span id ="total_orders_vs_abandoned_orders_number_div_span_ratio" class ="total_orders_vs_abandoned_orders_number_div_span_ratio"> 
                <?php echo $ratio_of_total_vs_abandoned. "%";  ?> 
            </span>
        </div>
        <div id ="total_orders_vs_abandoned_orders_number_div_text_ratio" class ="total_orders_vs_abandoned_orders_number_div_text_ratio" > 
            <span id ="total_orders_vs_abandoned_orders_number_div_text_span_ratio" class ="total_orders_vs_abandoned_orders_number_div_text_span_ratio"> 
                <?php echo "of Total Carts";  ?> 
            </span>
        </div>
        <?php 
    }
    /*
     * Search data filter
     * 
     */
    public function search_by_date(  ) {
    
        $this->duration_range_select = array(
            
            'this_month'   => __( 'This Month'   , 'woocommerce-ac' ),
            'last_month'   => __( 'Last Month'   , 'woocommerce-ac' ),
            'this_quarter' => __( 'This Quarter' , 'woocommerce-ac' ),
            'last_quarter' => __( 'Last Quarter' , 'woocommerce-ac' ),
            'this_year'    => __( 'This Year'    , 'woocommerce-ac' ),
            'last_year'    => __( 'Last Year'    , 'woocommerce-ac' ),
            'other'        => __( 'Custom'       , 'woocommerce-ac' ),
        );
        if ( isset( $_GET['duration_select'] ) ) {
            $duration_range = $_GET['duration_select'];
        }else{
            $duration_range = "this_month";
        }
        ?>
        <div class = "main_start_end_date" id = "main_start_end_date" >
            <div class = "filter_date_drop_down" id = "filter_date_drop_down" >
                <label class="date_time_filter_label" for="date_time_filter_label" > 
                    <strong>
                        <?php _e( "Select date range:", "woocommerce-ac"); ?>
                    </strong>
                </label>
                    
                <select id=duration_select name="duration_select" >
                    <?php
                    foreach ( $this->duration_range_select as $key => $value ) {
                        $sel = "";
                        if ( $key == $duration_range ) {
                            $sel = __( "selected ", "woocommerce-ac" );
                        } 
                        echo"<option value='" . $key . "' $sel> " . __( $value,'woocommerce-ac' ) . " </option>";
                    }
                    ?>
                </select>
                <?php
                 
                $start_date_range = "";
                if ( isset( $_GET['wcap_start_date'] ) ) {
                    $start_date_range = $_GET['wcap_start_date'];
                }
                
                $end_date_range = "";
                if ( isset( $_GET['wcap_end_date'] ) ){
                    $end_date_range = $_GET['wcap_end_date'];
                }
                $start_end_date_div_show = 'block';
                if ( !isset($_GET['duration_select']) || $_GET['duration_select'] != 'other' ) {
                    $start_end_date_div_show = 'none';
                }
                ?>
                
                <div class = "wcap_start_end_date_div" id = "wcap_start_end_date_div" style="display: <?php echo $start_end_date_div_show; ?>;"  >
                    <input type="text" id="wcap_start_date" name="wcap_start_date" readonly="readonly" value="<?php echo $start_date_range; ?>" placeholder="yyyy-mm-dd"/>     
                    <input type="text" id="wcap_end_date" name="wcap_end_date" readonly="readonly" value="<?php echo $end_date_range; ?>" placeholder="yyyy-mm-dd"/>
                </div>
                <div id="wcap_submit_button" class="wcap_submit_button">
                    <?php submit_button( __( 'Go', 'woocommerce-ac' ), 'button', false, false, array('ID' => 'wcap-search-by-date-submit' ) ); ?>
                </div>
            </div>
        </div>
        
       <?php
    }
    
    function wcap_get_total_email_sent ( $wcap_sent_email_count ){
        ?>
        <div id ="wcap_sent_email_count_div" class ="wcap_sent_email_count_div" >
            <span id ="wcap_sent_email_count_div_span" class ="wcap_sent_email_count_div_span" > 
                <?php echo  $wcap_sent_email_count ; ?>  
            </span>
        </div>
        <div id ="wcap_sent_email_count_div_text" class ="wcap_sent_email_count_div_text" >
            <span id ="wcap_sent_email_count_div_text_span" class ="wcap_sent_email_count_div_text_span" > 
                <?php echo "Emails <br>Sent"; ?>  
            </span>
        </div>
        <?php 
    }
    
    function wcap_get_total_email_sent_ratio ( $wcap_sent_email_count ){
        ?>
        <div id ="wcap_sent_email_count_div_ratio" class ="wcap_sent_email_count_div_ratio" >
            <span id ="wcap_sent_email_count_div_ratio_span" class ="wcap_sent_email_count_div_ratio_span" > 
                <?php echo  $wcap_sent_email_count ; ?>  
            </span>
        </div>
        <div id ="wcap_sent_email_count_div_text_ratio" class ="wcap_sent_email_count_div_text_ratio" >
            <span id ="wcap_sent_email_count_div_text_ratio_span" class ="wcap_sent_email_count_div_text_ratio_span" > 
                <?php echo "Emails <br>Sent"; ?>  
            </span>
        </div>
        <?php 
    }
        
    function wcap_get_email_opened ( $wcap_email_opened ){
        ?>
        <div id ="wcap_email_opened_count_div" class ="wcap_email_opened_count_div" >
            <span id ="wcap_email_opened_count_div_span" class ="wcap_email_opened_count_div_span" > 
                <?php echo  $wcap_email_opened . '%' ; ?>  
            </span>
        </div>
        <div id ="wcap_email_opened_count_div_text" class ="wcap_email_opened_count_div_text" >
            <span id ="wcap_email_opened_count_div_text_span" class ="wcap_email_opened_count_div_text_span" > 
                <?php echo "Open <br>Rate"; ?>  
            </span>
        </div>
        <?php 
    }
    function wcap_abandoned_email_opened_ratio ( $wcap_email_opened_count,  $wcap_email_sent_count ) {
        ?>
        <div id ="wcap_abandoned_email_opened_div_ratio" class ="wcap_abandoned_email_opened_div_ratio">
            <span id ="wcap_abandoned_email_opened_div_span_ratio" class ="wcap_abandoned_email_opened_div_span_ratio"> 
                <?php echo $wcap_email_opened_count. " / ". $wcap_email_sent_count;  ?> 
            </span>
        </div>
        <div id ="wcap_abandoned_email_opened_div_text_ratio" class ="wcap_abandoned_email_opened_div_text_ratio" > 
            <span id ="wcap_abandoned_email_opened_div_text_span_ratio" class ="wcap_abandoned_email_opened_div_text_span_ratio"> 
                <?php echo "Emails <br>Opened";  ?> 
            </span>
        </div>
        <?php 
    }
    
    function wcap_abandoned_email_clicked ( $wcap_email_clicked ) {
        ?>
        <div id ="wcap_abandoned_email_clicked_div" class ="wcap_abandoned_email_clicked_div" >
            <span id ="wcap_abandoned_email_clicked_div_span" class ="wcap_abandoned_email_clicked_div_span" > 
                <?php echo $wcap_email_clicked . "%" ; ?>  
            </span>
        </div>
        <div id ="wcap_abandoned_email_clicked_div_text" class ="wcap_abandoned_email_clicked_div_text" >
            <span id ="wcap_abandoned_email_clicked_div_text_span" class ="wcap_abandoned_email_clicked_div_text_span" > 
                <?php echo "Click <br>Rate"; ?>  
            </span>
        </div>
        <?php 
    }
    
    function wcap_emails_clicked_ratio ( $wcap_emails_clicked, $wcap_email_opened_count ) {
        ?>
        <div id ="wcap_emails_clicked_ratio_div" class ="wcap_emails_clicked_ratio_div" >
            <span id ="wcap_emails_clicked_ratio_div_span" class ="wcap_emails_clicked_ratio_div_span" > 
                <?php echo $wcap_emails_clicked . " / " . $wcap_email_opened_count; ?>  
            </span>
        </div>
        <div id ="wcap_emails_clicked_ratio_div_text" class ="wcap_emails_clicked_ratio_div_text" >
            <span id ="wcap_emails_clicked_ratio_div_text_span" class ="wcap_emails_clicked_ratio_div_text_span" > 
                <?php echo "Emails <br>Clicked"; ?>  
            </span>
        </div>
        <?php 
    }
    
    function wcap_create_template_section ( $wcap_template_id, $serial_number, $selected_data_range, $start_date, $end_date ) {
        global $wpdb;
        
        $orders = new Wcap_Dashboard_Report();
        
        $get_all_template_information = "SELECT * FROM `" . $wpdb->prefix . "ac_email_templates` WHERE id = $wcap_template_id ";
        $results                      = $wpdb->get_results( $get_all_template_information );
        
        $is_active     = '';
        $active        = '';
        $frequency     = '';
        $day_or_hour   = '';
        $acticve_state = '';
        
        if (isset( $results[0]->is_active )){
            $is_active = $results[0]->is_active;
        }
        
        if ( '1' == $is_active ) {
            $active = "Active";
            $acticve_state = "wcap_active_template";
        } else if ( '0' == $is_active ){
            $active = "Not Active";
            $acticve_state = "wcap_deactive_template";
        }
        
        if ( isset( $results[0]->frequency ) ){
            $frequency   = $results[0]->frequency;
        }
        if ( isset( $results[0]->day_or_hour ) ){
            $day_or_hour = $results[0]->day_or_hour;
        }
        $sends_text = $frequency . " " . $day_or_hour ;
        
        $ratio_of_email_opened       =  $raio_of_total_email_clicked = $ration_recovered_email = 'NaN';
        
        $get_total_email_sent        = $orders->wcap_get_total_email_sent_for_template  ( $wcap_template_id, $selected_data_range, $start_date, $end_date ) ;
        $get_total_email_opened      = $orders->wcap_get_total_email_open_for_template  ( $wcap_template_id, $selected_data_range, $start_date, $end_date ) ;
        
        $ratio_of_email_opened = 0;
        if ( isset ( $get_total_email_opened ) && $get_total_email_opened > 0 && isset ( $get_total_email_sent ) && $get_total_email_sent > 0 ){
            $ratio_of_email_opened   = ( $get_total_email_opened / $get_total_email_sent ) * 100 ;
            $ratio_of_email_opened   = round( $ratio_of_email_opened, wc_get_price_decimals() )  ;
        }
        
        $get_total_email_clicked     = $orders->wcap_get_total_email_click_for_template ( $wcap_template_id, $selected_data_range, $start_date, $end_date ) ;
        
        $raio_of_total_email_clicked = 0;
        if ( isset ( $get_total_email_clicked ) && $get_total_email_clicked > 0 && isset ( $get_total_email_opened ) && $get_total_email_opened > 0 ){
            $raio_of_total_email_clicked = ( $get_total_email_clicked / $get_total_email_opened  ) * 100 ;
            $raio_of_total_email_clicked = round( $raio_of_total_email_clicked, wc_get_price_decimals() )  ;
        }
        
        $get_total_email_recovered       = $orders->wcap_get_total_email_recover_for_template ( $wcap_template_id, $selected_data_range, $start_date, $end_date ) ;
        
        $ration_recovered_email = 0;
        if ( isset ( $get_total_email_recovered ) && $get_total_email_recovered > 0 && isset ( $get_total_email_sent ) && $get_total_email_sent > 0 ){
            $ration_recovered_email      = ( $get_total_email_recovered / $get_total_email_sent ) * 100 ;
            $ration_recovered_email      = round( $ration_recovered_email, wc_get_price_decimals() )  ;
        }
    ?>
    <section class = "wcap_template_main_section">
        <!-- Title -->
        <span>
            <span class="<?php echo $acticve_state; ?>" id = "<?php echo $acticve_state; ?>">Email template&nbsp;<?php echo $serial_number; ?>:</span>
            <span> <strong class="<?php echo $acticve_state; ?>" id = "<?php echo $acticve_state; ?>" ><?php echo $active; ?> </strong> </span>
        </span>
        <hr>
        <div>
            <span>Sends</span>:&nbsp;
            <span >
            <?php echo $sends_text; ?> <span >after cart abandonment</span>
            </span>
        </div>
    
        <div class = "wcap_click_open_div" id = "wcap_click_open_div" >
            <strong>
                <span translate="" class="ng-scope">Open Rate</span>:&nbsp;
            </strong>
            <span>
            <?php echo $ratio_of_email_opened; ?>%</span>&nbsp;
            <strong>
                <span>Click Rate</span>:&nbsp;
            </strong>
            <span><?php echo $raio_of_total_email_clicked; ?>%</span>
            <br>
              
        </div>
        <div class = "wcap_recovery_div" id = "wcap_recovery_div" >
            <strong>
                <span>Recovery Rate</span>:&nbsp;
            </strong>
            <span><?php echo $ration_recovered_email;?>%</span>  
        </div>
    
    </section>
    <?php 
    }
}