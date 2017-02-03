<?php 
/*
Plugin Name: Abandoned Cart Pro for WooCommerce
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
Description: This plugin captures abandoned carts by logged-in users and guest users. It allows to create multiple email templates to be sent at fixed intervals. Thereby reminding customers about their abandoned orders & resulting in increased sales by completing those orders. Go to <strong>WooCommerce -> <a href="admin.php?page=woocommerce_ac_page">Abandoned Carts</a> </strong>to get started.
Version: 3.9
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
*/

/*require 'plugin-updates/plugin-update-checker.php';
$ACUpdateChecker = new PluginUpdateChecker(
    'http://www.tychesoftwares.com/plugin-updates/woocommerce-abandon-cart-pro/info.json',
    __FILE__
);*/

global $ACUpdateChecker;
$ACUpdateChecker = '3.9';

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'EDD_SL_STORE_URL_AC_WOO', 'http://www.tychesoftwares.com/' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

// the name of your product. This is the title of your product in EDD and should match the download title in EDD exactly
define( 'EDD_SL_ITEM_NAME_AC_WOO', 'Abandoned Cart Pro for WooCommerce' ); // IMPORTANT: change the name of this constant to something unique to prevent conflicts with other plugins using this system

if( ! class_exists( 'EDD_AC_WOO_Plugin_Updater' ) ) {
    // load our custom updater if it doesn't already exist
    include( dirname( __FILE__ ) . '/plugin-updates/EDD_AC_WOO_Plugin_Updater.php' );
}

// retrieve our license key from the DB
$license_key = trim( get_option( 'edd_sample_license_key_ac_woo' ) );

// setup the updater
$edd_updater = new EDD_AC_WOO_Plugin_Updater( EDD_SL_STORE_URL_AC_WOO, __FILE__, array(
        'version'   => '3.9',                     // current version number
        'license'   => $license_key,                // license key (used get_option above to retrieve from DB)
        'item_name' => EDD_SL_ITEM_NAME_AC_WOO,     // name of this plugin
        'author'    => 'Ashok Rane'                 // author of this plugin
        )
);

require_once( "cron/wcap_send_email.php" );
require_once( "includes/wcap_class-guest.php" );
require_once( "includes/wcap_default-settings.php" );
require_once( "includes/wcap_actions.php" );
require_once( "includes/wcap-dashboard-widget.php" );
require_once( "includes/classes/class-wcap-dashboard-widget-report.php" );
require_once( "includes/classes/class-wcap-dashboard-widget-heartbeat.php" );
require_once( "includes/classes/class-wcap-aes.php" );
require_once( "includes/classes/class-wcap-aes-counter.php" );
require_once( "includes/wcap-common.php" );
// Deletion Settings
register_uninstall_hook( __FILE__, 'woocommerce_ac_delete' );

// Add a new interval of 15 minutes
add_filter( 'cron_schedules', 'wcap_add_cron_schedule' );

function wcap_add_cron_schedule( $schedules ) {
    $schedules['15_minutes'] = array(
                'interval' => 900,  // 15 minutes in seconds
                'display'  => __( 'Once Every Fifteen Minutes' ),
    );
    return $schedules;
}
// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_ac_send_email_action' ) ) {
    wp_schedule_event( time(), '15_minutes', 'woocommerce_ac_send_email_action' );
}

function woocommerce_ac_delete() {
    
    global $wpdb;
    if ( is_multisite() ){
        // get main site's table prefix
    
        $blog_list = wp_get_sites(  );
                                
        foreach ($blog_list as $blog_list_key => $blog_list_value ){
            
             if( $blog_list_value['blog_id'] > 1 ){
                
                $sub_site_prefix = $wpdb->prefix.$blog_list_value['blog_id']."_";
                
                $table_name_ac_abandoned_cart_history = $sub_site_prefix . "ac_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );
                
                $table_name_ac_email_templates = $sub_site_prefix . "ac_email_templates";
                $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_email_templates );
                
                $table_name_ac_sent_history = $sub_site_prefix . "ac_sent_history";
                $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_sent_history );
                
                $table_name_ac_opened_emails = $sub_site_prefix . "ac_opened_emails";
                $sql_ac_opened_emails = "DROP TABLE " . $table_name_ac_opened_emails ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_opened_emails );
                
                $table_name_ac_link_clicked_email = $sub_site_prefix . "ac_link_clicked_email";
                $sql_ac_link_clicked_email = "DROP TABLE " . $table_name_ac_link_clicked_email ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_link_clicked_email );
                
                $table_name_ac_guest_abandoned_cart_history = $sub_site_prefix . "ac_guest_abandoned_cart_history";
                $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_ac_abandoned_cart_history );
                
                $sql_table_user_meta = "DELETE FROM `" . $sub_site_prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta );
                
                $sql_table_post_meta = "DELETE FROM `" . $sub_site_prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_post_meta );
                
                $sql_table_user_meta_cart = "DELETE FROM `" . $sub_site_prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_user_meta_cart );
                
                $option_name = $wpdb->prefix.$blog_list_value['blog_id']."_woocommerce_ac_default_templates_installed";
                
                $sql_table_option_data = "DELETE FROM `" . $sub_site_prefix . "options` WHERE option_name = '".$option_name."' ";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $wpdb->get_results( $sql_table_option_data );
             }else{ // this is for the primary site.
                 $option_name = $wpdb->prefix . "woocommerce_ac_default_templates_installed";
                 delete_option( $option_name );
                 
                 $table_name_ac_abandoned_cart_history = $wpdb->prefix . "ac_abandoned_cart_history";
                 $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_ac_abandoned_cart_history );
                 
                 $table_name_ac_email_templates = $wpdb->prefix . "ac_email_templates";
                 $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_ac_email_templates );
                 
                 $table_name_ac_sent_history = $wpdb->prefix . "ac_sent_history";
                 $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_ac_sent_history );
                 
                 $table_name_ac_opened_emails = $wpdb->prefix . "ac_opened_emails";
                 $sql_ac_opened_emails = "DROP TABLE " . $table_name_ac_opened_emails ;
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_ac_opened_emails );
                 
                 $table_name_ac_link_clicked_email = $wpdb->prefix . "ac_link_clicked_email";
                 $sql_ac_link_clicked_email = "DROP TABLE " . $table_name_ac_link_clicked_email ;
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_ac_link_clicked_email );
                 
                 $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history";
                 $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_ac_abandoned_cart_history );
                 
                 $sql_table_user_meta = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_table_user_meta );
                 
                 $sql_table_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_table_post_meta );
                 
                 $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
                 require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                 $wpdb->get_results( $sql_table_user_meta_cart );
             }
        }
    }else{
        // non-multisite - regular table name
        delete_option( 'woocommerce_ac_default_templates_installed' );
        
        $table_name_ac_abandoned_cart_history = $wpdb->prefix . "ac_abandoned_cart_history";
        $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_abandoned_cart_history );
        
        $table_name_ac_email_templates = $wpdb->prefix . "ac_email_templates";
        $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_email_templates );
        
        $table_name_ac_sent_history = $wpdb->prefix . "ac_sent_history";
        $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_sent_history );
        
        $table_name_ac_opened_emails = $wpdb->prefix . "ac_opened_emails";
        $sql_ac_opened_emails = "DROP TABLE " . $table_name_ac_opened_emails ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_opened_emails );
        
        $table_name_ac_link_clicked_email = $wpdb->prefix . "ac_link_clicked_email";
        $sql_ac_link_clicked_email = "DROP TABLE " . $table_name_ac_link_clicked_email ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_link_clicked_email );
        
        $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history";
        $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_ac_abandoned_cart_history );
        
        $sql_table_user_meta = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_coupon'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_user_meta );
        
        $sql_table_post_meta = "DELETE FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_woocommerce_ac_coupon'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_post_meta );
        
        $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->get_results( $sql_table_user_meta_cart );
    }
    // delete the settings option records when the plugin is deleted
    delete_option( 'ac_enable_cart_emails' );
    delete_option( 'ac_cart_abandoned_time' );
    delete_option( 'ac_delete_abandoned_order_days' );
    delete_option( 'ac_email_admin_on_recovery' );
    delete_option( 'ac_track_coupons' );
    delete_option( 'ac_disable_guest_cart_email' );
    delete_option( 'ac_disable_logged_in_cart_email' );
    delete_option( 'ac_track_guest_cart_from_cart_page' );
    delete_option( 'ac_settings_status' );
    delete_option( 'woocommerce_ac_db_version' );
}
    
    /**
    * woocommerce_abandon_cart class
    **/

if ( ! class_exists( 'woocommerce_abandon_cart' ) ) {

    class woocommerce_abandon_cart {
        var $one_hour;
        var $three_hours;
        var $six_hours;
        var $twelve_hours;
        var $one_day;
        var $one_week;
        var $duration_range_select = array();
        var $start_end_dates = array();
        
        public function __construct() {
            
            $this->one_hour     = 60 * 60;
            $this->three_hours  = 3 * $this->one_hour;
            $this->six_hours    = 6 * $this->one_hour;
            $this->twelve_hours = 12 * $this->one_hour;
            $this->one_day      = 24 * $this->one_hour;
            $this->one_week     = 7 * $this->one_day;
            $this->duration_range_select = array( 
                    'yesterday'         => __( 'Yesterday', 'woocommerce-ac' ),
                    'today'             => __( 'Today', 'woocommerce-ac' ),
                    'last_seven'        => __( 'Last 7 days', 'woocommerce-ac' ),
                    'last_fifteen'      => __( 'Last 15 days', 'woocommerce-ac' ),
                    'last_thirty'       => __( 'Last 30 days', 'woocommerce-ac' ),
                    'last_ninety'       => __( 'Last 90 days', 'woocommerce-ac' ),
                    'last_year_days'    => __( 'Last 365', 'woocommerce-ac' ) );
            
            $this->start_end_dates = array(     
                    'yesterday'     => array( 'start_date' => date( "d M Y", ( current_time('timestamp') - 24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ) ),

                    'today'         => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

                    'last_seven'    => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

                    'last_fifteen'  => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 15*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

                    'last_thirty'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 30*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

                    'last_ninety'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 90*24*60*60 ) ), 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),

                    'last_year_days'=> array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 365*24*60*60 ) ) , 'end_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ) ) );
                          
            // Initialize settings
            register_activation_hook( __FILE__,                               array( &$this, 'wcap_activate' ) );
            
			// Update the options as per settings API              
            add_action( 'admin_init',                                         array( &$this,'wcap_update_db_check' ) );
            
            // Wordpress settings API
            add_action( 'admin_init',                                         array( &$this, 'wcap_initialize_plugin_options' ) );
            
            // Add settings link on plugins page
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( &$this, 'wcap_plugin_action_links' ) );
            
            //Add plugin doc and forum link in description
            add_filter( 'plugin_row_meta',                                    array( &$this, 'wcap_plugin_row_meta' ), 10, 2 );
            
            // WordPress Administration Menu 
            add_action( 'admin_menu',                                         array( &$this, 'wcap_admin_menu' ) );
            
            // Actions to be done on cart update
            add_action( 'woocommerce_cart_updated',                           array( &$this, 'wcap_store_cart_timestamp' ) );
            
            // delete added temp fields after order is placed 
            add_filter( 'woocommerce_order_details_after_order_table',        array( &$this, 'wcap_action_after_delivery_session' ) );
            
            // tracking coupons
            $display_tracked_coupons =  get_option( 'ac_track_coupons' );
            
            if ( $display_tracked_coupons == 'on' ) {
                add_action( 'woocommerce_coupon_error',                       array( &$this, 'wcap_coupon_ac_test_new' ), 15, 2 );
                add_action( 'woocommerce_applied_coupon',                     array( &$this, 'wcap_coupon_ac_test' ), 15, 2 );
            }
            
            add_action( 'admin_init',                                         array( &$this, 'wcap_action_admin_init' ) );
            add_action( 'admin_enqueue_scripts',                              array( &$this, 'wcap_enqueue_scripts_js' ) );
            add_action( 'admin_enqueue_scripts',                              array( &$this, 'wcap_enqueue_scripts_css' ) );
            
            // track links
            add_filter( 'template_include',                                   array( &$this, 'wcap_email_track_links' ), 99, 1 );
            
            /*
             * @since: 2.9
             * @comment : It wil track the email open time.
             * It will also used to unsubcribe the emails.
             * It has been done to over come the Wp-Load.php file include issue.
             *
             */
            add_action( 'template_include',                                            array( &$this, 'wcap_email_track_open_and_unsubscribe') );
            
            if ( is_admin() ) {
                
                // Load "admin-only" scripts here
                add_action( 'admin_head',                                              array( &$this, 'wcap_action_javascript' ) );
                
                add_action( 'admin_head',                                              array( &$this, 'wcap_send_test_email' ) );
                add_action( 'wp_ajax_wcap_preview_email_sent',                         array( &$this, 'wcap_preview_email_sent' ) );
                
                add_action( 'wp_ajax_wcap_json_find_coupons',                          array( &$this, 'wcap_json_find_coupons' ) );
                
                add_action( 'wp_dashboard_setup',                                      array( 'wcap_dashboard_widget', 'wcap_register_dashboard_widget' ), 10 );
                add_action( 'wp_ajax_wcap_dashboard_widget_report',                    array( 'wcap_dashboard_widget', 'wcap_dashboard_widget_report' ), 10 );
                
            }
            // Send Email on order recovery
            add_action( 'woocommerce_order_status_pending_to_processing_notification', array( &$this, 'wcap_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_pending_to_completed_notification',  array( &$this, 'wcap_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_pending_to_on-hold_notification',    array( &$this, 'wcap_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_failed_to_processing_notification',  array( &$this, 'wcap_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_failed_to_completed_notification',   array( &$this, 'wcap_email_admin_recovery' ) );
            
            add_action( 'woocommerce_order_status_changed',                            array( &$this, 'wcap_email_admin_recovery_for_paypal' ), 10, 3);
            
            add_action( 'admin_init',                                                  array( &$this, 'wcap_edd_ac_register_option' ) );
            add_action( 'admin_init',                                                  array( &$this, 'wcap_edd_ac_deactivate_license' ) );
            add_action( 'admin_init',                                                  array( &$this, 'wcap_edd_ac_activate_license' ) );
            
            // Language translation
            add_action( 'init',                                                        array( &$this, 'wcap_update_po_file' ) );
            
            // Add coupon when user views cart page
            add_action( 'woocommerce_before_cart_table',                               array( &$this, 'wcap_apply_direct_coupon_code' ) );
            
            // Add coupon when user views checkout page (would not be added otherwise, unless user views cart first).
            add_action( 'woocommerce_before_checkout_form',                            array( &$this, 'wcap_apply_direct_coupon_code' ) );
            
            add_action( 'admin_init',                                                  array( &$this, 'wcap_preview_emails' ) );
            
            add_action( 'init',                                                        array( &$this, 'wcap_output_buffer') );
            
            add_action( 'admin_init',                                                  array( &$this, 'wcap_register_template_string_for_wpml') );
            add_action( 'wp_login',                                                    array( &$this, 'wcap_remove_action_hook' ), 1);
            
            add_filter( 'wc_session_expiring',                                         array( &$this, 'wcap_set_session_expiring' ),10,1 );
            
            add_filter( 'wc_session_expiration',                                       array( &$this, 'wcap_set_session_expired' ),10,1 );
            
            add_action( 'woocommerce_checkout_order_processed',                        array( &$this, 'wcap_order_placed' ), 10 , 1 );
            
            add_filter( 'woocommerce_payment_complete_order_status',                   array( &$this ,'wcap_order_complete_action'), 10 , 2 );
            
            /*
             * @since: 2.9
             * @comment :Cron Job call action, which will run the function based on standard wordpress way.
             * It has been done to over come the Wp-Load.php file include issue.
             * 
             */
             
            // Hook into that action that'll fire every 5 minutes
            add_action( 'woocommerce_ac_send_email_action',                             array( 'Wcap_Abandoned_Cart_Cron_Job_Class', 'wcap_abandoned_cart_send_email_notification' ) );
            
            //delete abandoned order after X number of days
            add_action( 'wp_head',                                                      array( 'Wcap_Abandoned_Cart_Cron_Job_Class','wcap_delete_abandoned_carts_after_x_days'));
            
            /*
             * @since : 3.8
             * It is used to print the abandoned cart data.
             */
            add_action( 'admin_init',                                                   array( &$this, 'wcap_print_data' ) );
        }
        
        /*
         * @since : 3.8
         * It is used to print the abandoned cart data.
         */
        public function wcap_print_data(  ) {
            if ( ( isset( $_GET['action'] ) && 'listcart' == $_GET['action'] ) && ( isset( $_GET['wcap_download'] ) && 'wcap.print' == $_GET['wcap_download'] ) ) {
                $this->wcap_generate_print_report();
            }
        }
        
        /**************************************************/
        public function wcap_set_session_expiring($seconds) {
            $hours_23 = 60 * 60 * 23 ;
            $days_7 = $hours_23 * 7 ;
            return $days_7; 
        }
        
        public function wcap_set_session_expired($seconds) {
            $hours_24 = 60 * 60 * 24 ;
            $days_7 = $hours_24 * 7 ;
            return $days_7; 
        }
        
        /**
         * Preview email template
         *
         * @return string
         */
        public function wcap_preview_emails() {
        
            global $woocommerce;
            
            if ( isset( $_GET['wcap_preview_woocommerce_mail'] ) ) {
                if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-ac') ) {
                    die( 'Security check' );
                }
        
                $message = '';
                if ( $woocommerce->version < '2.3' ) {                       
                    global $email_heading;
                   
                    ob_start();
                    
                   include( 'views/wcap-email-template-preview.php' );
                    
                    $mailer        = WC()->mailer();
                    $message       = ob_get_clean();
                    $email_heading = __( 'HTML Email Template', 'woocommerce' );
                    
                    $message =  $mailer->wrap_message( $email_heading, $message );
                }else{
                    
                    // load the mailer class
                    $mailer        = WC()->mailer();
                    
                    // get the preview email subject
                    $email_heading = __( 'Abandoned cart Email Template', 'woocommerce-ac' );
                    
                    // get the preview email content
                    ob_start();
                    include( 'views/wcap-wc-email-template-preview.php' );
                    $message       = ob_get_clean();
                    
                    // create a new email
                    $email         = new WC_Email();
                    
                    // wrap the content with the email template and then add styles
                    $message       = $email->style_inline( $mailer->wrap_message( $email_heading, $message ) );
                }
                
                echo $message;
                exit;
            }
            
            if ( isset( $_GET['wcap_preview_mail'] ) ) {
                if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-ac') ) {
                    die( 'Security check' );
                }
                
                // get the preview email content
                ob_start();
                include( 'views/wcap-email-template-preview.php' );
                $message  = ob_get_clean();

                // print the preview email
                echo $message;
                exit;
            }
        }
        /* 
         * Localization
         */
        function  wcap_update_po_file() {
            $domain = 'woocommerce-ac';
            $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
            if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
                return $loaded;
            } else {
                load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
            }
        }
        
        public static function wcap_order_placed( $order_id ) {
        
            if( session_id() === '' ){
                //session has not started
                session_start();
            }
        
            if ( isset( $_SESSION['email_sent_id'] ) && $_SESSION['email_sent_id'] !='' ) {
        
                global $woocommerce, $wpdb;
        
                $email_sent_id     = $_SESSION['email_sent_id'];
                $get_ac_id_query   = "SELECT abandoned_order_id FROM `" . $wpdb->prefix."ac_sent_history` WHERE id = %d";
                $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );
        
                $abandoned_order_id = $get_ac_id_results[0]->abandoned_order_id;
        
                update_post_meta( $order_id , 'wcap_recover_order_placed', $abandoned_order_id );
        
                update_post_meta( $order_id , 'wcap_recover_order_placed_sent_id', $email_sent_id );
        
            }else if ( isset( $_SESSION['abandoned_cart_id'] ) && $_SESSION['abandoned_cart_id'] !=''  ){
        
                global $woocommerce, $wpdb;
            
                $results_sent  = array();
            
                $abandoned_cart_id = $_SESSION['abandoned_cart_id'];
            
                $get_email_sent_for_abandoned_id = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history` WHERE abandoned_order_id = %d ";
            
                $results_sent  = $wpdb->get_results ( $wpdb->prepare( $get_email_sent_for_abandoned_id, $abandoned_cart_id ) );
            
                if ( empty ( $results_sent ) && count ($results_sent) == 0 ){
                    
                    /*
                     * If logeged in user place the order once it is displyed under the abandoned orders tab.
                     * But the email has been not sent to the user. And order is placed successfuly
                     * Then We are deleteing those order. But for those orders Recovered email has been set to the Admin.
                     * Below code ensure that admin recovery email wil not be sent for tose orders.
                     */
                    
                    $get_user_id_of_abandoned_cart = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id = %d ";
                    
                    $get_results_of_user_id        = $wpdb->get_results ( $wpdb->prepare( $get_user_id_of_abandoned_cart, $abandoned_cart_id ) );
                    $user_id                       = $get_results_of_user_id[0]->user_id;
                    
                    delete_user_meta( $user_id, '_woocommerce_ac_modified_cart' );
                    
                    /*
                     * It will delete the order from history table if the order is placed before any email sent to the user.
                     * 
                     */
                 
                    $table_name = $wpdb->prefix . 'ac_abandoned_cart_history';
                
                    $wpdb->delete( $table_name , array( 'id' => $abandoned_cart_id ) );
                }else{
            
                    $email_sent_id = $results_sent[0]->id;
                    update_post_meta( $order_id , 'wcap_recover_order_placed', $abandoned_cart_id );
                    update_post_meta( $order_id , 'wcap_recover_order_placed_sent_id', $email_sent_id );
                }
            }
        }
        
        public function wcap_order_complete_action( $order_status, $order_id ) {
        
            if ( 'failed' != $order_status  ) {
        
                global $woocommerce, $wpdb;
                $order = new WC_Order( $order_id );
                 
                $get_abandoned_id_of_order  = '';
                $get_sent_email_id_of_order = '';
                $get_abandoned_id_of_order  =   get_post_meta( $order_id, 'wcap_recover_order_placed', true );
                 
                if ( isset( $get_abandoned_id_of_order ) && $get_abandoned_id_of_order != '' ){
                     
                    $get_abandoned_id_of_order  =   get_post_meta( $order_id, 'wcap_recover_order_placed', true );
        
                    $get_sent_email_id_of_order = get_post_meta( $order_id, 'wcap_recover_order_placed_sent_id', true );
        
                    $query_order = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET recovered_cart= '" . $order_id . "', cart_ignored = '1'
                                    WHERE id = '".$get_abandoned_id_of_order."' ";
                    $wpdb->query( $query_order );
                    
                    $query_language_session = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET language = ''
                                               WHERE id = '".$get_abandoned_id_of_order."' ";
                    $wpdb->query( $query_language_session );
                    
                    $recover_order = "UPDATE `" . $wpdb->prefix . "ac_sent_history` SET recovered_order = '1'
									  WHERE id ='" . $get_sent_email_id_of_order . "' ";
                    $wpdb->query( $recover_order );
        
                    $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-ac' ) );
                     
                    delete_post_meta( $order_id, 'wcap_recover_order_placed', $get_abandoned_id_of_order );
                    delete_post_meta( $order_id , 'wcap_recover_order_placed_sent_id', $get_sent_email_id_of_order );
                }
            }
            return $order_status;
        }
        
        /**
         * Show action links on the plugin screen.
         *
         * @param	mixed $links Plugin Action links
         * @return	array
         */
        
        public static function wcap_plugin_action_links( $links ) {
            $action_links = array(
                'settings' => '<a href="' . admin_url( 'admin.php?page=woocommerce_ac_page&action=emailsettings' ) . '" title="' . esc_attr( __( 'View WooCommerce abandoned Cart Settings', 'woocommerce-ac' ) ) . '">' . __( 'Settings', 'woocommerce-ac' ) . '</a>',
            );
            return array_merge( $action_links, $links );
        }
        
        /**
         * Show row meta on the plugin screen.
         *
         * @param	mixed $links Plugin Row Meta
         * @param	mixed $file  Plugin Base file
         * @return	array
         */
        
        public static function wcap_plugin_row_meta( $links, $file ) {
            $plugin_base_name = plugin_basename( __FILE__ );
            if ( $file == $plugin_base_name ) {
                $row_meta = array(
                    'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_abandoned_cart_docs_url', 'https://www.tychesoftwares.com/woocommerce-abandon-cart-plugin-documentation/' ) ) . '" title="' . esc_attr( __( 'View WooCommerce abandoned Cart Documentation', 'woocommerce-ac' ) ) . '">' . __( 'Docs', 'woocommerce-ac' ) . '</a>',
                    'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_abandoned_cart_support_url', 'https://www.tychesoftwares.com/forums/forum/woocommerce-abandon-cart-pro/' ) ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'woocommerce-ac' ) ) . '">' . __( 'Support Forums', 'woocommerce-ac' ) . '</a>',
                );
                return array_merge( $links, $row_meta );
            }
            return (array) $links;
        }
            
        function wcap_edd_ac_activate_license() {
                
            // listen for our activate button to be clicked
            if ( isset( $_POST['edd_ac_license_activate'] ) ) {
        
                // run a quick security check
                if ( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
                    return; // get out if we didn't click the Activate button
        
                // retrieve the license from the database
                $license = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
                               
                // data to send in our API request
                $api_params = array(
                        'edd_action'=> 'activate_license',
                        'license'   => $license,
                        'item_name' => urlencode( EDD_SL_ITEM_NAME_AC_WOO ) // the name of our product in EDD
                ); 
        
                // Call the custom API.
                $response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );
        
                // make sure the response came back okay
                if ( is_wp_error( $response ) )
                    return false;
        
                // decode the license data
                $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        
                // $license_data->license will be either "active" or "inactive"
        
                update_option( 'edd_sample_license_status_ac_woo', $license_data->license );           
            }
        }
                    
        /***********************************************
         * Illustrates how to deactivate a license key.
         * This will descrease the site count
         ***********************************************/           
        function wcap_edd_ac_deactivate_license() {
                
            // listen for our activate button to be clicked
            if ( isset( $_POST['edd_ac_license_deactivate'] ) ) {
        
                // run a quick security check
                if ( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
                    return; // get out if we didn't click the Activate button
        
                // retrieve the license from the database
                $license = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
                              
                // data to send in our API request
                $api_params = array(
                        'edd_action'=> 'deactivate_license',
                        'license'   => $license,
                        'item_name' => urlencode( EDD_SL_ITEM_NAME_AC_WOO ) // the name of our product in EDD
                );
        
                // Call the custom API.
                $response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );
        
                // make sure the response came back okay
                if ( is_wp_error( $response ) )
                    return false;
        
                // decode the license data
                $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        
                // $license_data->license will be either "deactivated" or "failed"
                if ( $license_data->license == 'deactivated' )
                    delete_option( 'edd_sample_license_status_ac_woo' );
            }
        }
                  
        /************************************
         * this illustrates how to check if
         * a license key is still valid
         * the updater does this for you,
         * so this is only needed if you
         * want to do something custom
        *************************************/           
        function edd_sample_check_license() {
                
            global $wp_version;
            $license = trim( get_option( 'edd_sample_license_key_ac_woo' ) );
            $api_params = array(
                    'edd_action' => 'check_license',
                    'license'    => $license,
                    'item_name'  => urlencode( EDD_SL_ITEM_NAME_AC_WOO )
            );
                
            // Call the custom API.
            $response = wp_remote_get( add_query_arg( $api_params, EDD_SL_STORE_URL_AC_WOO ), array( 'timeout' => 15, 'sslverify' => false ) );
                
            if ( is_wp_error( $response ) )
                return false;               
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
                
            if ( $license_data->license == 'valid' ) {
                echo 'valid'; 
                exit;
                // this license is still valid
            } else {
                echo 'invalid'; 
                exit;
                // this license is no longer valid
            }
        }
        
        function wcap_edd_ac_register_option() {
            // creates our settings in the options table
            register_setting( 'edd_sample_license', 'edd_sample_license_key_ac_woo', array( &$this, 'wcap_edd_sanitize_license' ) );
        }
         
        function wcap_edd_sanitize_license( $new ) {
            $old = get_option( 'edd_sample_license_key_ac_woo' );
            if ( $old && $old != $new ) {
                delete_option( 'edd_sample_license_status_ac_woo' ); // new license has been entered, so must reactivate
            }
            return $new;
        }
             
        function wcap_format_tiny_MCE( $in ) {
            add_editor_style();
            $in['force_root_block']             = false;
            $in['valid_children']               = '+body[style]';
            $in['remove_linebreaks']            = false;
            $in['gecko_spellcheck']             = false;
            $in['keep_styles']                  = true;
            $in['accessibility_focus']          = true;
            $in['tabfocus_elements']            = 'major-publishing-actions';
            $in['media_strict']                 = false;
            $in['paste_remove_styles']          = false;
            $in['paste_remove_spans']           = false;
            $in['paste_strip_class_attributes'] = 'none';
            $in['paste_text_use_dialog']        = true;
            $in['wpeditimage_disable_captions'] = true;
            $in['wpautop']                      = false;
            $in['apply_source_formatting']      = true;     
            $in['cleanup']                      = true;
            $in['convert_newlines_to_brs']      = FALSE;
            $in['fullpage_default_xml_pi']      = false;
            $in['convert_urls']                 = false;
            // Do not remove redundant BR tags
            $in['remove_redundant_brs']         = false;
            return $in;
        }
        
        function wcap_decrypt_validate( $validate ) {
            
            if( function_exists( "mcrypt_encrypt" ) ) {
                $cryptKey         = get_option( 'ac_security_key' );
                $validate_decoded = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $validate ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
            } else {
                $validate_decoded = base64_decode ( $validate );
            }
            return( $validate_decoded );
        }
        
        function wcap_email_track_open_and_unsubscribe ( $args ){
            global $wpdb;
            if ( isset( $_GET['wcap_track_email_opens'] ) && $_GET['wcap_track_email_opens'] == 'wcap_email_open' ){
                
                $email_sent_id = $_GET['email_id'];
                echo $email_sent_id;
                if ( $email_sent_id > 0 && is_numeric( $email_sent_id ) ) {
                    $query = "INSERT INTO `" . $wpdb->prefix . "ac_opened_emails` ( email_sent_id , time_opened )
                VALUES ( '" . $email_sent_id . "' , '" . current_time( 'mysql' ) . "' )";
                    $wpdb->query( $query );
                }
                exit();
            }else if ( isset( $_GET['wcap_track_unsubscribe'] ) && $_GET['wcap_track_unsubscribe'] == 'wcap_unsubscribe' ){

                $encoded_email_id         = rawurldecode ( $_GET['validate'] );
                $validate_email_id_string = str_replace ( " " , "+", $encoded_email_id);
                
                $validate_email_address_string = '';
                $validate_email_id_decode = 0;
                
                if( isset( $_GET['track_email_id'] ) ) {
                
                    $encoded_email_address         = rawurldecode ( $_GET['track_email_id'] );
                    $validate_email_address_string = str_replace ( " " , "+", $encoded_email_address);
                    
                    if( isset( $validate_email_id_string ) ) {
                        $validate_email_id_decode  = $this->wcap_decrypt_validate( $validate_email_id_string );
                    }
                    $validate_email_address_string = $validate_email_address_string;
                }
                
                if ( !preg_match('/^[1-9][0-9]*$/', $validate_email_id_decode ) ) { // This will decrypt more security
                    $cryptKey  = get_option( 'ac_security_key' );
                    $validate_email_id_decode = Wcap_Aes_Ctr::decrypt( $validate_email_id_string, $cryptKey, 256 );
                }
                
                $query_id      = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history` WHERE id = %d ";
                $results_sent  = $wpdb->get_results ( $wpdb->prepare( $query_id, $validate_email_id_decode ) );
                $email_address = '';
                
                if( isset( $results_sent[0] ) ) {
                    $email_address =  $results_sent[0]->sent_email_id;
                }
                
                if( $validate_email_address_string == hash( 'sha256', $email_address ) ) {
                     
                    $email_sent_id     = $validate_email_id_decode;
                    $get_ac_id_query   = "SELECT abandoned_order_id FROM `" . $wpdb->prefix . "ac_sent_history` WHERE id = %d";
                    $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query , $email_sent_id ) );
                    $user_id           = 0;
                    if( isset( $get_ac_id_results[0] ) ) {
                        $get_user_id_query = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id = %d";
                        $get_user_results  = $wpdb->get_results( $wpdb->prepare( $get_user_id_query , $get_ac_id_results[0]->abandoned_order_id ) );
                    }
                    if( isset( $get_user_results[0] ) ) {
                        $user_id = $get_user_results[0]->user_id;
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
            }else {
                return $args; 
            }
        }
    
        function wcap_email_track_links( $template ) {
            global $woocommerce;
            $track_link = '';
    
            if ( isset( $_GET['wacp_action'] ) ) 
                $track_link = $_GET['wacp_action'];
            
            if ( $track_link == 'track_links' ) {
                
                if( session_id() === '' ){
                    //session has not started
                    session_start();
                }
                
                global $wpdb;
                
                $validate_server_string  = rawurldecode ( $_GET ['validate'] );
                $validate_server_string = str_replace ( " " , "+", $validate_server_string);
                $validate_encoded_string = $validate_server_string;
                
                if ( isset( $_GET ['c'] ) ) { // it will check if coupon code parameter exists or not 
             
                    $decrypt_coupon_code = rawurldecode ( $_GET ['c'] );
    				$decrypt_coupon_code = str_replace ( " " , "+", $decrypt_coupon_code);
                    
                    if( function_exists( "mcrypt_encrypt" ) ) {
                        $decode_coupon_code = $this->wcap_decrypt_validate( $decrypt_coupon_code );
                    }else{
                        $decode_coupon_code = base64_decode( $decrypt_coupon_code );
                    }
                    
    				if ( !preg_match("#^[a-zA-Z0-9]+$#", $decode_coupon_code ) ) { // This will decrypt more security
                        $cryptKey  = get_option( 'ac_security_key' );
    					$decode_coupon_code = Wcap_Aes_Ctr::decrypt( $decrypt_coupon_code, $cryptKey, 256 );
                    }
    
                    $_SESSION['acp_c'] = $decode_coupon_code ; // wee need to set in session coz we directly apply coupon
                } else {
                    $decode_coupon_code = '';
                }
                
    			$link_decode_test = base64_decode( $validate_encoded_string );
                
                if ( preg_match( '/&url=/', $link_decode_test ) ) { // it will check if any old email have open the link
                    $link_decode = $link_decode_test;
                } else {
                    $link_decode = $this->wcap_decrypt_validate( $validate_encoded_string );
                }
                
                if ( !preg_match( '/&url=/', $link_decode ) ) { // This will decrypt more security
                    $cryptKey    = get_option( 'ac_security_key' );
                    
                    $link_decode = Wcap_Aes_Ctr::decrypt( $validate_encoded_string, $cryptKey, 256 );
                }
                
                $sent_email_id_pos           = strpos( $link_decode, '&' );
                $email_sent_id               = substr( $link_decode , 0, $sent_email_id_pos );
    			$_SESSION['email_sent_id']   = $email_sent_id;
                $url_pos                     = strpos( $link_decode, '=' );
                $url_pos                     = $url_pos + 1;
                $url                         = substr( $link_decode, $url_pos );
                $get_ac_id_query             = "SELECT abandoned_order_id FROM `" . $wpdb->prefix."ac_sent_history` WHERE id = %d";
                $get_ac_id_results           = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );
                $get_user_id_query           = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id = %d";
                $get_user_results            = $wpdb->get_results( $wpdb->prepare( $get_user_id_query, $get_ac_id_results[0]->abandoned_order_id ) );                  
                $user_id                     = 0;
    
                if ( isset( $get_user_results ) && count( $get_user_results ) > 0 ) {
                $user_id = $get_user_results[0]->user_id;
                }
                
                if ( $user_id == 0 ) {
                    echo "Link expired";
                    exit;
                }
                $user = wp_set_current_user( $user_id );
                
                if ( $user_id >= "63000000" ) {
                    $query_guest   = "SELECT * from `". $wpdb->prefix."ac_guest_abandoned_cart_history` WHERE id = %d";
                    $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $user_id ) );                            
                    $query_cart    = "SELECT recovered_cart FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d";
                    $results       = $wpdb->get_results( $wpdb->prepare( $query_cart, $user_id ) );
                    
                    if ( $results_guest  && $results[0]->recovered_cart == '0' ) {                           
                        $_SESSION['guest_first_name'] = $results_guest[0]->billing_first_name;
                        $_SESSION['guest_last_name']  = $results_guest[0]->billing_last_name;
                        $_SESSION['guest_email']      = $results_guest[0]->email_id;
                        $_SESSION['guest_phone']      = $results_guest[0]->phone;
                        $_SESSION['user_id']          = $user_id;
                    } else {
                        wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
                    }
                }
    
                if ( $user_id < "63000000" ) {
                    
                    $query_guest   = "SELECT * from `". $wpdb->prefix."ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' ";
                    $results = $wpdb->get_results( $wpdb->prepare( $query_guest, $user_id ) );
                     
                    $user_login = $user->data->user_login;
                    wp_set_auth_cookie( $user_id );
                    
                    $my_temp = woocommerce_load_persistent_cart( $user_login, $user );
                    
                    if ( function_exists('icl_register_string') ) {
                        $_SESSION [ 'wcap_selected_language' ] = $results[0]->language;
                    }
                    do_action( 'wp_login', $user_login, $user );
    
                    if ( isset( $sign_in ) && is_wp_error( $sign_in ) ) {
                        echo $sign_in->get_error_message();
                        exit;
                    }
                } else
                    $my_temp = $this->wcap_load_guest_persistent_cart( $user_id );
                
                if ( $email_sent_id > 0 && is_numeric( $email_sent_id ) ) {
                    $query = "INSERT INTO `" . $wpdb->prefix . "ac_link_clicked_email` ( email_sent_id, link_clicked, time_clicked )
                        VALUES ( '" . $email_sent_id . "', '".$url."', '" . current_time( 'mysql' ) . "' )";
                    $wpdb->query( $query );
                    header( "Location: $url" );
                }   
            } else
                return $template;
        }
    
        function wcap_load_guest_persistent_cart() {
            
            global $woocommerce;
            if ( isset( $_SESSION['user_id'] ) && $_SESSION['user_id'] != ''  ){
                $saved_cart = json_decode( get_user_meta( $_SESSION['user_id'], '_woocommerce_persistent_cart', true ), true );
            }else {
                $saved_cart = array();
            }
            $c = array();
            $cart_contents_total = $cart_contents_weight = $cart_contents_count = $cart_contents_tax = $total = $subtotal = $subtotal_ex_tax = $tax_total = 0;
    
            foreach ( $saved_cart as $key => $value ) {
                
                foreach ( $value as $a => $b ) {
                    $c['product_id']        = $b['product_id'];
                    $c['variation_id']      = $b['variation_id'];
                    $c['variation']         = $b['variation'];
                    $c['quantity']          = $b['quantity'];
                    $product_id             = $b['product_id'];
                    $c['data']              = get_product($product_id);
                    $c['line_total']        = $b['line_total'];
                    $c['line_tax']          = $cart_contents_tax;
                    $c['line_subtotal']     = $b['line_subtotal'];
                    $c['line_subtotal_tax'] = $cart_contents_tax;
                    $value_new[$a]          = $c;
                    $cart_contents_total    = $b['line_subtotal'] + $cart_contents_total;
                    $cart_contents_count    = $cart_contents_count + $b['quantity'];
                    $total                  = $total + $b['line_total'];
                    $subtotal               = $subtotal + $b['line_subtotal'];
                    $subtotal_ex_tax        = $subtotal_ex_tax + $b['line_subtotal'];
                }
                $saved_cart_data[$key]      = $value_new;
                $woocommerce_cart_hash      = $a;
            }
                
            if ( $saved_cart ) {
                
                if ( empty( $woocommerce->session->cart ) || ! is_array( $woocommerce->session->cart ) || sizeof( $woocommerce->session->cart ) == 0 ) {
                    $woocommerce->session->cart                 = $saved_cart['cart'];
                    $woocommerce->session->cart_contents_total  = $cart_contents_total;
                    $woocommerce->session->cart_contents_weight = $cart_contents_weight;
                    $woocommerce->session->cart_contents_count  = $cart_contents_count;
                    $woocommerce->session->cart_contents_tax    = $cart_contents_tax;
                    $woocommerce->session->total                = $total;
                    $woocommerce->session->subtotal             = $subtotal;
                    $woocommerce->session->subtotal_ex_tax      = $subtotal_ex_tax;
                    $woocommerce->session->tax_total            = $tax_total;
                    $woocommerce->session->shipping_taxes       = array();
                    $woocommerce->session->taxes                = array();
                    $woocommerce->session->ac_customer          = array();
                    $woocommerce->cart->cart_contents           = $saved_cart_data['cart'];
                    $woocommerce->cart->cart_contents_total     = $cart_contents_total;
                    $woocommerce->cart->cart_contents_weight    = $cart_contents_weight;
                    $woocommerce->cart->cart_contents_count     = $cart_contents_count;
                    $woocommerce->cart->cart_contents_tax       = $cart_contents_tax;
                    $woocommerce->cart->total                   = $total;
                    $woocommerce->cart->subtotal                = $subtotal;
                    $woocommerce->cart->subtotal_ex_tax         = $subtotal_ex_tax;
                    $woocommerce->cart->tax_total               = $tax_total;
                }
            }                   
        }
    
        function wcap_apply_direct_coupon_code( $coupon_code ) {
            
            remove_action( 'woocommerce_cart_updated', array( 'woocommerce_abandon_cart', 'wcap_store_cart_timestamp' ) );
            
            if ( isset( $_SESSION [ 'wcap_selected_language' ] ) && $_SESSION [ 'wcap_selected_language' ] !='' && function_exists('icl_register_string')){
            
                global $sitepress;
                $sitepress->switch_lang( $_SESSION [ 'wcap_selected_language' ] );
            }
            
            if( isset( $_SESSION['acp_c'] ) && $_SESSION['acp_c']!= ''){
                
                global $woocommerce;
                $coupon_code = $_SESSION['acp_c'];
            
               // If coupon has been already been added remove it.
                if ($woocommerce->cart->has_discount( sanitize_text_field( $coupon_code ) ) ) {
                    
                    if (!$woocommerce->cart->remove_coupons( sanitize_text_field($coupon_code ) ) ) {
        
                        wc_print_notices( );
                    }
                }
            
                // Add coupon
                if ( !$woocommerce->cart->add_discount( sanitize_text_field( $coupon_code ) ) ) {
                    wc_print_notices( );
                } else {     
                    wc_print_notices( );
                }
                // Manually recalculate totals.  If you do not do this, a refresh is required before user will see updated totals when discount is removed.
                $woocommerce->cart->calculate_totals();
                // need to clear the coupon code from session
                $_SESSION['acp_c'] = '';
            }
       }    
   
       public static function wcap_email_admin_recovery_for_paypal ( $order_id, $old, $new_status ) {
           
           if ( 'pending' == $old && 'processing' == $new_status ){
               
               $user_id                 = get_current_user_id();
               $ac_email_admin_recovery = get_option( 'ac_email_admin_on_recovery' );
           
               $order   = new WC_Order( $order_id );
               $user_id = $order->user_id;
               
               if( $ac_email_admin_recovery == 'on' ) {
           
                   $recovered_email_sent = get_post_meta( $order_id, 'wcap_recovered_email_sent', true );
                   if ( 'yes' != $recovered_email_sent && ( get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "yes" ) || get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "no" ) ) ) { // indicates cart is abandoned
                       $order          = new WC_Order( $order_id );
                       $email_heading  = __( 'New Customer Order - Recovered', 'woocommerce' );
                       $blogname       = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                       $email_subject  = "New Customer Order - Recovered";
                       $user_email     = get_option( 'admin_email' );
                       $headers[]      = "From: Admin <".$user_email.">";
                       $headers[]      = "Content-Type: text/html";
           
                       // Buffer
                       ob_start();
           
                       // Get mail template
                       woocommerce_get_template( 'emails/admin-new-order.php', array(
                                                 'order'         => $order,
                                                 'email_heading' => $email_heading,
                                                 'sent_to_admin' => false,
                                                 'plain_text'    => false,
                                                 'email'         => true
                                                )
                        );
           
                       // Get contents
                       $email_body = ob_get_clean();
                       woocommerce_mail( $user_email, $email_subject, $email_body, $headers );
                   
                       update_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );
                   } 
               }
           }
       }

        function wcap_email_admin_recovery ( $order_id ) {              
            $user_id                 = get_current_user_id();
            $ac_email_admin_recovery = get_option( 'ac_email_admin_on_recovery' );
    
            if( $ac_email_admin_recovery == 'on' ) {
                $recovered_email_sent = get_post_meta( $order_id, 'wcap_recovered_email_sent', true );
                
                if ( 'yes' != $recovered_email_sent && ( get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "yes" ) || get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "no" ) ) ) { // indicates cart is abandoned 
                    $order          = new WC_Order( $order_id );
                    $email_heading  = __( 'New Customer Order - Recovered', 'woocommerce' );
                    $blogname       = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                    $email_subject  = "New Customer Order - Recovered";
                    $user_email     = get_option( 'admin_email' );
                    $headers[]      = "From: Admin <".$user_email.">";
                    $headers[]      = "Content-Type: text/html";
                    
                    // Buffer
                    ob_start();
            
                    // Get mail template
                    woocommerce_get_template( 'emails/admin-new-order.php', array(
                        'order'         => $order,
                        'email_heading' => $email_heading,
                        'sent_to_admin' => false,
                        'plain_text'    => false,
                        'email'         => true
                    ) );
            
                    // Get contents
                    $email_body = ob_get_clean();
                    woocommerce_mail( $user_email, $email_subject, $email_body, $headers );
                    
                    update_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );
                }
            }                
        }
    	/**************************************************
    	* This function is run when the plugin is upgraded
    	************************************************/
    	function wcap_update_db_check() {
        	global $woocommerce_ac_plugin_version, $ACUpdateChecker;
        	global $wpdb;
        	$woocommerce_ac_plugin_version = get_option( 'woocommerce_ac_db_version' );
        	
        	if ( $woocommerce_ac_plugin_version != $this->wcap_get_version() ) {
        		//get the option, if it is not set to individual then convert to individual records and delete the base record
        		$ac_settings = get_option( 'ac_settings_status' );
        		if ( $ac_settings != 'INDIVIDUAL' ) {
        			//fetch the existing settings and save them as inidividual to be used for the settings API
            		$woocommerce_ac_settings = json_decode( get_option( 'woocommerce_ac_settings' ) );
            		add_option( 'ac_enable_cart_emails',                 $woocommerce_ac_settings[0]->enable_cart_notification );
            		add_option( 'ac_cart_abandoned_time',                $woocommerce_ac_settings[0]->cart_time );
            		add_option( 'ac_delete_abandoned_order_days',        $woocommerce_ac_settings[0]->delete_order_days );
            		add_option( 'ac_email_admin_on_recovery',            $woocommerce_ac_settings[0]->email_admin );
            		add_option( 'ac_track_coupons',                      $woocommerce_ac_settings[0]->track_coupons );
            		add_option( 'ac_disable_guest_cart_email',           $woocommerce_ac_settings[0]->disable_guest_cart );
            		add_option( 'ac_disable_logged_in_cart_email',       $woocommerce_ac_settings[0]->disable_logged_in_cart );
            		add_option( 'ac_track_guest_cart_from_cart_page',  $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page );
            		update_option( 'ac_settings_status', 'INDIVIDUAL' );
            		//Delete the main settings record
            		delete_option( 'woocommerce_ac_settings' );
        		}
        		update_option( 'woocommerce_ac_db_version', '3.9' );
        		// the default templates have the shop name hard coded as Tyche Softwares. Change it to {{shop.name}} merge tag
        		$query_templates = "SELECT id, body FROM `" . $wpdb->prefix . "ac_email_templates`
        							WHERE default_template = '1'";
        		$template_results = $wpdb->get_results( $query_templates );
        		 
        		if ( isset( $template_results ) && is_array( $template_results ) && count( $template_results ) > 0 ) {
        			$table_name = $wpdb->prefix . 'ac_email_templates';
        			foreach ( $template_results as $key => $value ) {
        				$new_body = str_ireplace( 'Tyche Softwares', '{{shop.name}}', $value->body );
        				$wpdb->update( $table_name,
        						array( 'body' => $new_body ),
        						array( 'id' => $value->id )
        				);
        			}
        		}
        		
        		$clicked_link_table_name = $wpdb->prefix . "ac_link_clicked_email";
        		$check_table_query = "SHOW COLUMNS FROM $clicked_link_table_name LIKE 'link_clicked'";
        		$results = $wpdb->get_results ( $check_table_query );
        		if ( $results[0]-> Type == 'varchar(60)' ) {
        		    $alter_table_query = " ALTER TABLE $clicked_link_table_name MODIFY COLUMN link_clicked varchar (500) ";
        		    $wpdb->get_results ( $alter_table_query );
        		}
            	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_email_templates';" ) ) {
            	    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_email_templates` LIKE 'is_wc_template';" ) ) {
            	        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_email_templates ADD `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `generate_unique_coupon_code`;" );
            	    }
            	}
        	}
        	
        	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_email_templates';" ) ) {
        	    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_email_templates` LIKE 'wc_email_header';" ) ) {
        	        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_email_templates ADD `wc_email_header` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `is_wc_template`;" );
        	    }
        	}
        	
        	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history';" ) ) {
        	    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history` LIKE 'language';" ) ) {
        	        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history ADD `language` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `user_type`;" );
        	    }
        	}
        	
        	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history';" ) ) {
        	    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history` LIKE 'session_id';" ) ) {
        	        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history ADD `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `language`;" );
        	    }
        	}
        	
        	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history';" ) ) {
        	    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history` LIKE 'ip_address';" ) ) {
        	        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history ADD `ip_address` longtext COLLATE utf8_unicode_ci NOT NULL AFTER  `session_id`;" );
        	    }
        	}
        	
        	if ( !get_option( 'ac_security_key' ) ){
                update_option( 'ac_security_key', "qJB0rGtIn5UB1xG03efyCp" );
        	}
        }

        /***********************************************************
         * This function returns the AC plugin version number
        **********************************************************/
        function wcap_get_version() {
        	$plugin_data = get_plugin_data( __FILE__ );
        	$plugin_version = $plugin_data['Version'];
        	return $plugin_version;
        }
        
        function wcap_activate() {
            global $wpdb;
    
            $wcap_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $wcap_collate = $wpdb->get_charset_collate();
            }
            $table_name = $wpdb->prefix . "ac_email_templates";           
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `from_email` text COLLATE utf8_unicode_ci NOT NULL,
            `subject` text COLLATE utf8_unicode_ci NOT NULL,
            `body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
            `is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
            `frequency` int(11) NOT NULL,
            `day_or_hour` enum('Minutes','Days','Hours') COLLATE utf8_unicode_ci NOT NULL,
            `coupon_code` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
            `template_name` text COLLATE utf8_unicode_ci NOT NULL,
            `from_name` text COLLATE utf8_unicode_ci NOT NULL,
            `reply_email` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY (`id`)
            ) $wcap_collate AUTO_INCREMENT=1 ";           
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $sql );        
            
            $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'default_template' ";             
            $results = $wpdb->get_results( $check_template_table_query );
    
            if ( count( $results ) == 0 ) {
                $alter_template_table_query = "ALTER TABLE $table_name 
                        ADD COLUMN `default_template` int(11) NOT NULL AFTER `reply_email`, 
                        ADD COLUMN `discount` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `default_template`,
                        ADD COLUMN `generate_unique_coupon_code` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `discount`";
                $wpdb->get_results( $alter_template_table_query );
            }
            
            update_option( 'woocommerce_ac_db_version', '3.9' );
    		
            $check_table_query = "SHOW COLUMNS FROM $table_name WHERE Field = 'day_or_hour'";
            $result = $wpdb->get_results( $check_table_query );               
            $options = json_decode( str_replace(
                    array( "enum(", ")", "'" ),
                    array( "[", "]", '"' ),
                    $result[0]->Type
            ) );
        
            if ( ! in_array( "Minutes", $options ) ) {
                $alter_table_query = "ALTER TABLE $table_name MODIFY `day_or_hour` enum( 'Minutes', 'Days', 'Hours' ) COLLATE utf8_unicode_ci NOT NULL";
                $wpdb->get_results( $alter_table_query );
            }
            
            $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
            $results = $wpdb->get_results( $check_template_table_query );
             
            if ( count( $results ) == 0 ) {
                $alter_template_table_query = "ALTER TABLE $table_name
                ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `generate_unique_coupon_code`";
                $wpdb->get_results( $alter_template_table_query );
            }
            
            $sent_table_name = $wpdb->prefix . "ac_sent_history";
        
            $sql_query = "CREATE TABLE IF NOT EXISTS $sent_table_name (
            `id` int(11) NOT NULL auto_increment,
            `template_id` varchar(40) collate utf8_unicode_ci NOT NULL,
            `abandoned_order_id` int(11) NOT NULL,
            `sent_time` datetime NOT NULL,
            `sent_email_id` text COLLATE utf8_unicode_ci NOT NULL,
            PRIMARY KEY  (`id`)
            ) $wcap_collate AUTO_INCREMENT=1 ";                 
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $sql_query );  
    
    		$check_sent_history_table_query = "SHOW COLUMNS FROM $sent_table_name LIKE 'recovered_order' ";             
            $results = $wpdb->get_results( $check_sent_history_table_query );
    
            if ( count( $results ) == 0 ) {
                $alter_sent_history_table_query = "ALTER TABLE $sent_table_name 
                        ADD COLUMN `recovered_order` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `sent_email_id`";
                $wpdb->get_results( $alter_sent_history_table_query );
            }
    		
            $opened_table_name = $wpdb->prefix . "ac_opened_emails"; 				
            $opened_query = "CREATE TABLE IF NOT EXISTS $opened_table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email_sent_id` int(11) NOT NULL,
            `time_opened` datetime NOT NULL,
            PRIMARY KEY (`id`)
            ) $wcap_collate COMMENT='store the primary key id of opened email template' AUTO_INCREMENT=1 ";   
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $opened_query );  
    		
            $clicked_link_table_name = $wpdb->prefix . "ac_link_clicked_email";          
            $clicked_query = "CREATE TABLE IF NOT EXISTS $clicked_link_table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `email_sent_id` int(11) NOT NULL,
            `link_clicked` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
            `time_clicked` datetime NOT NULL,
            PRIMARY KEY (`id`)
            ) $wcap_collate COMMENT='store the link clicked in sent email template' AUTO_INCREMENT=1 ";           
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $clicked_query );
                     
            $ac_history_table_name = $wpdb->prefix . "ac_abandoned_cart_history";               
            $history_query = "CREATE TABLE IF NOT EXISTS $ac_history_table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
            `abandoned_cart_time` int(11) NOT NULL,
            `cart_ignored` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
            `recovered_cart` int(11) NOT NULL,
            `unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
            `user_type` text,
            PRIMARY KEY (`id`)
            ) $wcap_collate";                       
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $wpdb->query( $history_query );
        
            $check_table_query = "SHOW COLUMNS FROM $ac_history_table_name LIKE 'user_type'";             
            $results           = $wpdb->get_results( $check_table_query );
    
            if ( count( $results ) == 0 ) {
                $alter_table_query = "ALTER TABLE $ac_history_table_name ADD `user_type` text AFTER  `unsubscribe_link`";
                $wpdb->get_results( $alter_table_query );
            }
            
            $ac_guest_history_table_name = $wpdb->prefix . "ac_guest_abandoned_cart_history";                   
            $ac_guest_history_query      = "CREATE TABLE IF NOT EXISTS $ac_guest_history_table_name (
            `id` int(15) NOT NULL AUTO_INCREMENT,
            `billing_first_name` text, 
            `billing_last_name` text,
            `billing_company_name` text,
            `billing_address_1` text,
            `billing_address_2` text,
            `billing_city` text,
            `billing_county` text,
            `billing_zipcode` text,
            `email_id` text,
            `phone` text,
            `ship_to_billing` text,
            `order_notes` text,
            `shipping_first_name` text, 
            `shipping_last_name` text,
            `shipping_company_name` text,
            `shipping_address_1` text,
            `shipping_address_2` text,
            `shipping_city` text,
            `shipping_county` text,
            `shipping_zipcode` text,
            `shipping_charges` double,
            PRIMARY KEY (`id`)
            ) $wcap_collate AUTO_INCREMENT=63000000";                    
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
            $wpdb->query( $ac_guest_history_query );
            
            $check_table_query = "SHOW COLUMNS FROM $ac_guest_history_table_name WHERE Field = 'shipping_charges'";
            $result            = $wpdb->get_results( $check_table_query );
                
            if ( count( $result ) == 0 ) {
                $alter_table_query = "ALTER TABLE $ac_guest_history_table_name ADD `shipping_charges` double AFTER  `shipping_zipcode`";
                $wpdb->get_results( $alter_table_query );
            }
            $default_template = new default_template_settings;
            //Default settings, if option table do not have any entry.
            if( !get_option( 'ac_enable_cart_emails' ) ) {
                // function call to create default settings.
                $default_template->create_default_settings();
            } 
    
            // Default templates:  function call to create default templates.
            $check_table_empty  = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "ac_email_templates`" );
    
            if ( is_multisite() ){
                // get main site's table prefix
                
                if( !get_option( $wpdb->prefix."woocommerce_ac_default_templates_installed" ) ) {
                
                    if( 0 == $check_table_empty ) {
                        $default_template->create_default_templates();
                        update_option( $wpdb->prefix."woocommerce_ac_default_templates_installed", "yes" );
                    }
                }
                
            }else{
                // non-multisite - regular table name
                if( !get_option( 'woocommerce_ac_default_templates_installed' ) ) {
    
                    if( 0 == $check_table_empty ) {
                            $default_template->create_default_templates();
                             update_option( 'woocommerce_ac_default_templates_installed', "yes" );
                    }
                }
            }
            
            /**
             * This is add for thos user who Install the plguin first time.
             * So for them this option will be cheked.
             */
            if( !get_option( 'ac_track_guest_cart_from_cart_page' ) ) {
                add_option( 'ac_track_guest_cart_from_cart_page',  'on' );
            }
        }
        /***************************************************************
         * WP Settings API 
        **************************************************************/
        function wcap_initialize_plugin_options() {
        	// First, we register a section. This is necessary since all future options must belong to a
        	add_settings_section(
    			'ac_general_settings_section',         // ID used to identify this section and with which to register options
    			__( 'Settings', 'woocommerce-ac' ),                  // Title to be displayed on the administration page
    			array($this, 'wcap_general_options_callback' ), // Callback used to render the description of the section
    			'woocommerce_ac_page'     // Page on which to add this section of options
        	);
        	 
        	add_settings_field(
    			'ac_enable_cart_emails',
    			__( 'Enable abandoned cart emails', 'woocommerce-ac' ),
    			array( $this, 'wcap_enable_cart_emails_callback' ),
    			'woocommerce_ac_page',
    			'ac_general_settings_section',
    			array( __( 'Yes, enable the abandoned cart emails.', 'woocommerce-ac' ) )
        	);
        	add_settings_field(
    			'ac_cart_abandoned_time',
    			__( 'Cart abandoned cut-off time', 'woocommerce-ac' ),
    			array( $this, 'wcap_cart_abandoned_time_callback' ),
    			'woocommerce_ac_page',
    			'ac_general_settings_section',
    			array( __( 'Consider cart abandoned after X minutes of item being added to cart & order not placed.', 'woocommerce-ac' ) )
        	);
        	 
        	add_settings_field(
    			'ac_delete_abandoned_order_days',
    			__( 'Automatically Delete Abandoned Orders after X days', 'woocommerce-ac' ),
    			array( $this, 'wcap_delete_abandoned_orders_days_callback' ),
    			'woocommerce_ac_page',
    			'ac_general_settings_section',
    			array( __( 'Automatically delete abandoned cart orders after X days.', 'woocommerce-ac' ) )
        	);
        	 
        	add_settings_field(
    			'ac_email_admin_on_recovery',
    			__( 'Email admin On Order Recovery', 'woocommerce-ac' ),
    			array( $this, 'wcap_email_admin_on_recovery_callback' ),
    			'woocommerce_ac_page',
    			'ac_general_settings_section',
    			array( __( 'Sends email to Admin if an Abandoned Cart Order is recovered.', 'woocommerce-ac' ) )
        	);
        	
        	add_settings_field(
        	'ac_email_admin_on_abandoned',
        	__( 'Send the Abandoned cart emails to the admin' , 'woocommerce-ac' ),
        	array( $this, 'wcap_email_admin_on_abandoned_callback' ),
        	'woocommerce_ac_page',
        	'ac_general_settings_section',
        	array( __( 'Send a copy to admin for all Abandoned cart email notifications that are sent to Customers.', 'woocommerce-ac' ) )
        	);
        	 
        	add_settings_field(
    			'ac_track_coupons',
    			__( 'Track Coupons', 'woocommerce-ac' ),
    			array( $this, 'wcap_track_coupons_callback' ),
    			'woocommerce_ac_page',
    			'ac_general_settings_section',
    			array( __( 'Tracks all coupons that were applied to abandoned carts.', 'woocommerce-ac' ) )
        	);
        	 
        	add_settings_field(
    			'ac_disable_guest_cart_email',
    			__( 'Do not track carts of guest users', 'woocommerce-ac' ),
    			array( $this, 'wcap_disable_guest_cart_email_callback' ),
    			'woocommerce_ac_page',
    			'ac_general_settings_section',
    			array( __( 'Abandoned carts of guest users will not be tracked.', 'woocommerce-ac' ) )
        	);
        	
        	add_settings_field(
            	'ac_track_guest_cart_from_cart_page',
            	__( 'Enable tracking carts when customer doesn\'t enter details', 'woocommerce-ac' ),
            	array( $this, 'wcap_track_guest_cart_from_cart_page_callback' ),
            	'woocommerce_ac_page',
            	'ac_general_settings_section',
            	array( __( 'Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.', 'woocommerce-ac' ) )
        	);
        	
        	add_settings_field(
            	'ac_disable_logged_in_cart_email',
            	__( 'Do not track carts of logged-in users', 'woocommerce-ac' ),
            	array( $this, 'wcap_disable_logged_in_cart_email_callback' ),
            	'woocommerce_ac_page',
            	'ac_general_settings_section',
            	array( __( 'Abandoned carts of logged-in users will not be tracked.', 'woocommerce-ac' ) )
        	);
        	
        	//Setting section and field for license options
        	add_settings_section(
    	        'ac_general_license_key_section',
    	        __( 'Plugin License Options', 'woocommerce-ac' ),
    	        array( $this, 'wcap_general_license_key_section_callback' ),
    	        'woocommerce_ac_page'
        	);
        	
        	add_settings_field(
    	        'edd_sample_license_key_ac_woo',
    	        __( 'License Key', 'woocommerce-ac' ),
    	        array( $this, 'wcap_edd_sample_license_key_ac_woo_callback' ),
    	        'woocommerce_ac_page',
    	        'ac_general_license_key_section',
    	        array( __( 'Enter your license key.', 'woocommerce-ac' ) )
        	 );
        	 
        	 add_settings_field(
    	         'activate_license_key_ac_woo',
    	         __( 'Activate License', 'woocommerce-ac' ),
    	         array( $this, 'wcap_activate_license_key_ac_woo_callback' ),
    	         'woocommerce_ac_page',
    	         __( 'ac_general_license_key_section', 'woocommerce-ac' )
        	 );
        	     
        	// Finally, we register the fields with WordPress
        	register_setting(
    			'woocommerce_ac_settings',
    			'ac_enable_cart_emails'
        	);
        	register_setting(
    			'woocommerce_ac_settings',
    			'ac_cart_abandoned_time',
    			array ( $this, 'wcap_cart_time_validation' )
        	);
        	register_setting(
    			'woocommerce_ac_settings',
    			'ac_delete_abandoned_order_days',
    			array ( $this, 'wcap_delete_days_validation' )
        	);
        	register_setting(
    			'woocommerce_ac_settings',
    			'ac_email_admin_on_recovery'
        	);
        	register_setting(
            	'woocommerce_ac_settings',
            	'ac_email_admin_on_abandoned'
    	    );
        	
        	register_setting(
    			'woocommerce_ac_settings',
    			'ac_track_coupons'
        	);
        	register_setting(
    			'woocommerce_ac_settings',
    			'ac_disable_guest_cart_email'
        	);
        	register_setting(
    	       'woocommerce_ac_settings',
    	       'ac_disable_logged_in_cart_email'
        	);
        	register_setting(
        	   'woocommerce_ac_settings',
        	   'ac_track_guest_cart_from_cart_page'
        	);
        	register_setting(
    	        'woocommerce_ac_settings',
    	        'edd_sample_license_key_ac_woo'
            );
        }
    
        /***************************************************************
         * WP Settings API cart time field validation
        **************************************************************/
        function wcap_cart_time_validation( $input ) {
        	$output = '';
        	if ( $input != '' && ( is_numeric( $input) && $input > 0  ) ) {
        		$output = stripslashes( $input) ;
        	} else {
        		add_settings_error( 'ac_cart_abandoned_time', 'error found', __( 'Abandoned cart cut off time should be numeric and has to be greater than 0.', 'woocommerce-ac' ) );
        	}
        	return $output;
        }
        /***************************************************************
         * WP Settings API delete days field validation
        **************************************************************/
        function wcap_delete_days_validation($input) {
        	$output = '';
        	if ( $input == '' || ( is_numeric( $input) && $input > 0 ) ) {
        		$output = stripslashes( $input ) ;
        	} else {
        		add_settings_error( 'ac_delete_abandoned_order_days', 'error found', __( 'Automatically Delete Abandoned Orders after X days has to be greater than 0.', 'woocommerce-ac' ) );
        	}
        	return $output;
        }
        /***************************************************************
         * WP Settings API callback for section
        **************************************************************/
        function wcap_general_options_callback() {
        	 
        }
        /***************************************************************
         * WP Settings API callback for enable cart emails field
        **************************************************************/
        function wcap_enable_cart_emails_callback( $args ) {
        	// First, we read the option
        	$enable_cart_emails = get_option( 'ac_enable_cart_emails' );
        	// This condition added to avoid the notie displyed while Check box is unchecked.
        	if (isset( $enable_cart_emails ) &&  $enable_cart_emails == "") {
        		$enable_cart_emails = 'off';
        	}
        
        	// Next, we update the name attribute to access this element's ID in the context of the display options array
        	// We also access the show_header element of the options collection in the call to the checked() helper function 
        	$html = '<input type="checkbox" id="ac_enable_cart_emails" name="ac_enable_cart_emails" value="on" ' . checked( 'on', $enable_cart_emails, false ) . '/>';
        	
        	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
        	$html .= '<label for="ac_enable_cart_emails"> '  . $args[0] . '</label>';
        	echo $html;
        }
    
        /***************************************************************
         * WP Settings API callback for cart time field
        **************************************************************/
        function wcap_cart_abandoned_time_callback($args) {
            
        	// First, we read the option
        	$cart_abandoned_time = get_option( 'ac_cart_abandoned_time' );
        	
        	// Next, we update the name attribute to access this element's ID in the context of the display options array
        	// We also access the show_header element of the options collection in the call to the checked() helper function
        	printf(
    			'<input type="text" id="ac_cart_abandoned_time" name="ac_cart_abandoned_time" value="%s" />',
    			isset( $cart_abandoned_time ) ? esc_attr( $cart_abandoned_time ) : ''
        	);
        	
        	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
        	$html = '<label for="ac_cart_abandoned_time"> '  . $args[0] . '</label>';
        	echo $html;
        }   
    
        /***************************************************************
         * WP Settings API callback for delete order days field
        **************************************************************/
        function wcap_delete_abandoned_orders_days_callback( $args ) {
            
        	// First, we read the option
        	$delete_abandoned_order_days = get_option( 'ac_delete_abandoned_order_days' );
        	
        	// Next, we update the name attribute to access this element's ID in the context of the display options array
        	// We also access the show_header element of the options collection in the call to the checked() helper function
        	printf(
    			'<input type="text" id="ac_delete_abandoned_order_days" name="ac_delete_abandoned_order_days" value="%s" />',
    			isset( $delete_abandoned_order_days ) ? esc_attr( $delete_abandoned_order_days ) : ''
        	);
        	
        	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
        	$html = '<label for="ac_delete_abandoned_order_days"> '  . $args[0] . '</label>';
        	echo $html;
        }
    
        /***************************************************************
         * WP Settings API callback for email admin on cart recovery field
        **************************************************************/
        function wcap_email_admin_on_recovery_callback( $args ) {
            
        	// First, we read the option
        	$email_admin_on_recovery = get_option( 'ac_email_admin_on_recovery' );
        	
        	// This condition added to avoid the notie displyed while Check box is unchecked.
        	if ( isset( $email_admin_on_recovery ) && $email_admin_on_recovery == '' ) {
        		$email_admin_on_recovery = 'off';
        	}
        	
        	// Next, we update the name attribute to access this element's ID in the context of the display options array
        	// We also access the show_header element of the options collection in the call to the checked() helper function
        	$html='';
        	printf(
    			'<input type="checkbox" id="ac_email_admin_on_recovery" name="ac_email_admin_on_recovery" value="on"
    			' . checked('on', $email_admin_on_recovery, false).' />'
        	);
        	
        	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
        	$html .= '<label for="ac_email_admin_on_recovery"> '  . $args[0] . '</label>';
        	echo $html;
        }
        
        /***************************************************************
         * WP Settings API callback for email abandoned cart reminder email to admin
         **************************************************************/
        function wcap_email_admin_on_abandoned_callback( $args ) {
        
            // First, we read the option
            $email_admin_on_abandoned = get_option( 'ac_email_admin_on_abandoned' );
             
            // This condition added to avoid the notie displyed while Check box is unchecked.
            if ( isset( $email_admin_on_abandoned ) && $email_admin_on_abandoned == '' ) {
                $email_admin_on_recovery = 'off';
            }
             
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            $html='';
            printf(
            '<input type="checkbox" id="ac_email_admin_on_abandoned" name="ac_email_admin_on_abandoned" value="on"
    			' . checked('on', $email_admin_on_abandoned, false).' />'
		    );
             
            // Here, we'll take the first argument of the array and add it to a label next to the checkbox
            $html .= '<label for="ac_email_admin_on_abandoned"> '  . $args[0] . '</label>';
            echo $html;
        }
    
        /***************************************************************
         * WP Settings API callback for track coupons field
        **************************************************************/
        function wcap_track_coupons_callback($args) {
            
        	// First, we read the option
        	$track_coupons = get_option( 'ac_track_coupons' );
        	
        	// This condition added to avoid the notie displyed while Check box is unchecked.
        	if ( isset( $track_coupons ) && $track_coupons == '' ) {
        		$track_coupons = 'off';
        	}
        	
        	// Next, we update the name attribute to access this element's ID in the context of the display options array
        	// We also access the show_header element of the options collection in the call to the checked() helper function
        	$html='';
        	printf(
    			'<input type="checkbox" id="ac_track_coupons" name="ac_track_coupons" value="on"
    			'.checked('on', $track_coupons, false).' />'
        	);
        	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
        	$html .= '<label for="ac_track_coupons"> '  . $args[0] . '</label>';
        	echo $html;
        }
    
        /***************************************************************
         * WP Settings API callback for disable guest cart field
         **************************************************************/
        function wcap_disable_guest_cart_email_callback( $args ) {
            
        	// First, we read the option
        	$disable_guest_cart_email = get_option( 'ac_disable_guest_cart_email' );
        	
        	// This condition added to avoid the notie displyed while Check box is unchecked.
        	if ( isset( $disable_guest_cart_email ) && $disable_guest_cart_email == '' ) {
        		$disable_guest_cart_email = 'off';
        	}
        	// Next, we update the name attribute to access this element's ID in the context of the display options array
        	// We also access the show_header element of the options collection in the call to the checked() helper function
        	$html='';
        	printf(
    			'<input type="checkbox" id="ac_disable_guest_cart_email" name="ac_disable_guest_cart_email" value="on"
    			'.checked('on', $disable_guest_cart_email, false).' />'
        	);
        	// Here, we'll take the first argument of the array and add it to a label next to the checkbox
        	$html .= '<label for="ac_disable_guest_cart_email"> '  . $args[0] . '</label>';
        	echo $html;
        } 
    
        /***************************************************************
         * WP Settings API callback for disable logged-in cart email field
         **************************************************************/
        function wcap_disable_logged_in_cart_email_callback( $args ) {
        
            // First, we read the option
            $disable_logged_in_cart_email = get_option( 'ac_disable_logged_in_cart_email' );
             
            // This condition added to avoid the notice displyed while Check box is unchecked.
            if ( isset( $disable_logged_in_cart_email ) && $disable_logged_in_cart_email == '' ) {
                $disable_logged_in_cart_email = 'off';
            }
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            $html='';
            printf(
                '<input type="checkbox" id="ac_disable_logged_in_cart_email" name="ac_disable_logged_in_cart_email" value="on"
    			'.checked('on', $disable_logged_in_cart_email, false).' />'
    	    );
    	    // Here, we'll take the first argument of the array and add it to a label next to the checkbox
    	    $html .= '<label for="ac_disable_logged_in_cart_email"> '  . $args[0] . '</label>';
    	    echo $html;
        }
    
        /***************************************************************
         * @since : 2.7
         * WP Settings API callback for capturing guest cart which do not reach the checkout page.
         **************************************************************/
        function wcap_track_guest_cart_from_cart_page_callback( $args ) {
        
            // First, we read the option
            $disable_guest_cart_from_cart_page = get_option( 'ac_track_guest_cart_from_cart_page' );
            
            $disable_guest_cart_email      = get_option( 'ac_disable_guest_cart_email' );
            
            // This condition added to avoid the notice displyed while Check box is unchecked.
            if ( isset( $disable_guest_cart_from_cart_page ) && $disable_guest_cart_from_cart_page == '' ) {
                $disable_guest_cart_from_cart_page = 'off';
            }
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            $html='';
            
            $disabled = '';
            
            if (isset($disable_guest_cart_email) && $disable_guest_cart_email == 'on') {
                $disabled = 'disabled';
                $disable_guest_cart_from_cart_page = 'off';
            }
            
            printf(
            '<input type="checkbox" id="ac_track_guest_cart_from_cart_page" name="ac_track_guest_cart_from_cart_page" value="on"
    			'.checked('on', $disable_guest_cart_from_cart_page, false).'
    			'.$disabled.' />'
                    			    );
    	    // Here, we'll take the first argument of the array and add it to a label next to the checkbox
    	    $html .= '<label for="ac_track_guest_cart_from_cart_page"> '  . $args[0] . '</label>';
    	    echo $html;
        }
    
        /***************************************************************
         * WP Settings API callback for License plugin option
         **************************************************************/
        function wcap_general_license_key_section_callback(){
        
        }
    
        /***************************************************************
         * WP Settings API callback for License key
         **************************************************************/
        function wcap_edd_sample_license_key_ac_woo_callback( $args ){
            $edd_sample_license_key_ac_woo_field = get_option( 'edd_sample_license_key_ac_woo' );
            printf(
                '<input type="text" id="edd_sample_license_key_ac_woo" name="edd_sample_license_key_ac_woo" class="regular-text" value="%s" />',
                isset( $edd_sample_license_key_ac_woo_field ) ? esc_attr( $edd_sample_license_key_ac_woo_field ) : ''
            );
            // Here, we'll take the first argument of the array and add it to a label next to the checkbox
            $html = '<label for="edd_sample_license_key_ac_woo"> '  . $args[0] . '</label>';
            echo $html;
        }
        /***************************************************************
         * WP Settings API callback for to Activate License key
         **************************************************************/
        function wcap_activate_license_key_ac_woo_callback() {
            $license = get_option( 'edd_sample_license_key_ac_woo' );
            $status  = get_option( 'edd_sample_license_status_ac_woo' );
            ?>
                <form method="post" action="options.php">
                <?php if ( false !== $license ) { ?>
                    <?php if( $status !== false && $status == 'valid' ) { ?>
                        <span style="color:green;"><?php _e( 'active' ); ?></span>
                        <?php wp_nonce_field( 'edd_sample_nonce' , 'edd_sample_nonce' ); ?>
                        <input type="submit" class="button-secondary" name="edd_ac_license_deactivate" value="<?php _e( 'Deactivate License' ); ?>"/>
                     <?php } else {
                                wp_nonce_field( 'edd_sample_nonce', 'edd_sample_nonce' ); ?>
                                <input type="submit" class="button-secondary" name="edd_ac_license_activate" value="<?php _e( 'Activate License' ); ?>"/>
                            <?php } ?>
                <?php } ?>
                </form>
            <?php 
        }
                  
        function wcap_admin_menu() {   
            $page = add_submenu_page( 'woocommerce', __( 'Abandoned Carts', 'woocommerce-ac' ), __( 'Abandoned Carts', 'woocommerce-ac' ), 'manage_woocommerce', 'woocommerce_ac_page', array( &$this, 'wcap_menu_page' ) );
        }
        
        public static function wcap_remove_action_hook() {
            remove_action( 'woocommerce_cart_updated', array( 'woocommerce_abandon_cart', 'wcap_store_cart_timestamp' ) );
        }
    
        function wcap_store_cart_timestamp() {
            
            if( session_id() === '' ){
                //session has not started
                session_start();
            }
            
            global $wpdb, $woocommerce;
            
            $current_time                       = current_time( 'timestamp' );
            $disable_guest_cart                 = get_option( 'ac_disable_guest_cart_email' );
            $disable_logged_in_cart             = get_option( 'ac_disable_logged_in_cart_email' );
            $track_guest_cart_from_cart_page    = get_option( 'ac_track_guest_cart_from_cart_page' );
            $cut_off                            = get_option( 'ac_cart_abandoned_time' );
            
            $cart_cut_off_time                  = $cut_off * 60;
            $compare_time                       = $current_time - $cart_cut_off_time;
            $guest_cart                         = "";
            
            if ( isset( $disable_guest_cart ) ) {
            	$guest_cart = $disable_guest_cart;
            }
            
            $logged_in_cart    = "";
            if ( isset( $disable_logged_in_cart ) ) {
                $logged_in_cart = $disable_logged_in_cart;
            }
            
            $track_guest_user_cart_from_cart    = "";
            if ( isset( $track_guest_cart_from_cart_page ) ) {
                $track_guest_user_cart_from_cart = $track_guest_cart_from_cart_page;
            }
            
            if (function_exists('icl_register_string')) {
                $current_user_lang = ICL_LANGUAGE_CODE;
            } else {
                $current_user_lang = 'en';
            }
            
            if ( is_user_logged_in() ) {
        
                if ( $logged_in_cart != "on" ) {
                    $user_id = get_current_user_id();
                    
                    $loggedin_user_ip_address = wcap_common::wcap_get_client_ip ();
                    
                    $query   = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' ";
                    
                    $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
    
                    if ( count( $results ) == 0 ) {
                        
                        $cart_info        = json_encode(get_user_meta( $user_id, '_woocommerce_persistent_cart', true ) );
                        $cart_info        = addslashes ( $cart_info );
                        $blank_cart_info  =  '{"cart":[]}';
                        
                        if ( $blank_cart_info != $cart_info ){
                            $insert_query = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history`
                                            ( user_id , abandoned_cart_info , abandoned_cart_time , cart_ignored , recovered_cart, user_type, language, ip_address )
                                            VALUES ( '" . $user_id."' , '" . $cart_info."' , '" . $current_time . "' , '0' , '0' , 'REGISTERED', '". $current_user_lang ."', '". $loggedin_user_ip_address ."' )";
                            $wpdb->query( $insert_query );
                            
                            $abandoned_cart_id              = $wpdb->insert_id;
                            
                            $_SESSION['abandoned_cart_id'] = $abandoned_cart_id;
                        }
                        
                    } elseif ( $compare_time > $results[0]->abandoned_cart_time ) {
                        
                        $updated_cart_info = json_encode( get_user_meta( $user_id, '_woocommerce_persistent_cart', true ) );
                        $updated_cart_info = addslashes ( $updated_cart_info );
                        $blank_cart_info   =  '{"cart":[]}';
                        
                        if ( ($results[0]->language == $current_user_lang ||  $results[0]->language == '') && $blank_cart_info != $updated_cart_info ){
                            
                            if ( ! $this->wcap_compare_carts( $user_id, $results[0]->abandoned_cart_info ) ) {
                                
                                $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1' WHERE user_id ='" . $user_id . "'";
                                $wpdb->query( $query_ignored );
                                
                                $query_update = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history`
                                                ( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, language, ip_address  )
                                                VALUES ( '" . $user_id . "', '" . $updated_cart_info . "', '" . $current_time . "', '0', '0', 'REGISTERED', '". $current_user_lang ."', '". $loggedin_user_ip_address ."' )";
                                $wpdb->query( $query_update );
                                
                                $abandoned_cart_id              = $wpdb->insert_id;
                                
                                $_SESSION['abandoned_cart_id'] = $abandoned_cart_id;
                                
                                update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5( "yes" ) );
                            } else {
                                update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5( "no" ) );
                            }
                        }
                    } else {
                        
                        $blank_cart_info   =  '{"cart":[]}';
                        $updated_cart_info = json_encode( get_user_meta( $user_id, '_woocommerce_persistent_cart', true ) );
                        $updated_cart_info = addslashes ( $updated_cart_info );
                        if ( ( $results[0]->language == $current_user_lang ||  $results[0]->language == '' )  && $blank_cart_info != $updated_cart_info ){
                            
                            if ( ! $this->wcap_compare_carts( $user_id, $results[0]->abandoned_cart_info ) ) {
                                $query_update = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET abandoned_cart_info = '" . $updated_cart_info . "', abandoned_cart_time  = '" . $current_time . "', language = '". $current_user_lang ."', ip_address = '". $loggedin_user_ip_address ."'   WHERE user_id ='" . $user_id . "' AND cart_ignored='0' ";
                                $wpdb->query( $query_update );
                                
                                $query_update = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id ='" . $user_id . "' AND cart_ignored='0' ";
                                
                                $get_abandoned_record           = $wpdb->get_results( $query_update );
                                $abandoned_cart_id              = $get_abandoned_record[0]->id;
                                
                                $_SESSION['abandoned_cart_id'] = $abandoned_cart_id;
                            }
                        }
                    }
                }
            } else {
                
                if ( isset( $_SESSION['user_id'] ) ){
                    $user_id = $_SESSION['user_id'];
                }else{
                    $user_id = "";
                }
                
                $guest_user_ip_address = wcap_common::wcap_get_client_ip ();
                
                $query   = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_id != '0'";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );                 
                $cart    = array();
                
                $get_cookie = WC()->session->get_session_cookie();
                if ( function_exists('WC') ) {
                    $cart['cart'] = WC()->session->cart;
                } else {
                    $cart['cart'] = $woocommerce->session->cart;
                }
                $updated_cart_info = json_encode($cart);
                $updated_cart_info = addslashes ( $updated_cart_info );
                $guest_blank_cart_info  =  '[]';
                
                if ( count($results) > 0 ) {
                    
                    if ( $guest_cart != "on" ) {
                        if ( $compare_time > $results[0]->abandoned_cart_time ) { 
    					
                        if ( $updated_cart_info != $guest_blank_cart_info && ! $this->wcap_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
                                $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1' WHERE user_id ='" . $user_id . "'";
                                $wpdb->query( $query_ignored );  
                                                                  
                                $query_update = "INSERT INTO `" . $wpdb->prefix."ac_abandoned_cart_history` ( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, language, ip_address )
                                                VALUES ( '" . $user_id."', '" . $updated_cart_info . "', '".$current_time . "', '0', '0', 'GUEST', '". $current_user_lang ."', '". $guest_user_ip_address ."' )";
                                $wpdb->query( $query_update ); 
                                                                  
                                update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5("yes") );
                            } else {
                                update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5("no") );
                        }
                    } else {
                            if ( $updated_cart_info!= $guest_blank_cart_info ) {
                                $query_update = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET abandoned_cart_info = '" . $updated_cart_info . "', abandoned_cart_time = '" . $current_time . "',  ip_address = '" . $guest_user_ip_address . "' WHERE user_id='" . $user_id . "' AND cart_ignored='0' ";
                                $wpdb->query( $query_update );
                            }
                        }
                    }
                } else {
                    
                    /***
                     * @Since: 2.7
                     * Here we capture the guest cart from the cart page.
                     */
                    
                    if ( $track_guest_user_cart_from_cart == "on" &&  $get_cookie[0] != ''  ){
                    
                        $visitor_user_ip_address = wcap_common::wcap_get_client_ip ();
                        
                        $query   = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE session_id LIKE %s AND cart_ignored = '0' AND recovered_cart = '0' ";
                        $results = $wpdb->get_results( $wpdb->prepare( $query, $get_cookie[0] ) );
                        
                        if ( count( $results ) == 0 ) {
                        
                            $cart_info        = $updated_cart_info;
                            $blank_cart_info  =  '[]';
                        
                            if ( $blank_cart_info != $cart_info ){
                                $insert_query = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history`
                                                ( abandoned_cart_info , abandoned_cart_time , cart_ignored , recovered_cart, user_type, language, session_id, ip_address  )
                                                VALUES ( '" . $cart_info."' , '" . $current_time . "' , '0' , '0' , 'GUEST', '". $current_user_lang ."', '". $get_cookie[0] ."', '". $visitor_user_ip_address ."' )";
                                $wpdb->query( $insert_query );
                            }
                        
                        } elseif ( $compare_time > $results[0]->abandoned_cart_time ) {
                        
                            $blank_cart_info  =  '[]';
                        
                            if ( ($results[0]->language == $current_user_lang ||  $results[0]->language == '') && $blank_cart_info != $updated_cart_info ){
                        
                                if ( ! $this->wcap_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
                        
                                    $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1', ip_address = '". $visitor_user_ip_address ."' WHERE session_id ='" . $get_cookie[0] . "'";
                                    $wpdb->query( $query_ignored );
                        
                                    $query_update = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history`
                                                    ( abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, language, session_id, ip_address  )
                                                    VALUES ( '" . $updated_cart_info . "', '" . $current_time . "', '0', '0', 'GUEST', '". $current_user_lang ."', '". $get_cookie[0] ."', '". $visitor_user_ip_address ."' )";
                                    $wpdb->query( $query_update );
                                }
                            }
                        } else {
                        
                            $blank_cart_info   =  '[]';
                        
                            if ( ( $results[0]->language == $current_user_lang ||  $results[0]->language == '' )  && $blank_cart_info != $updated_cart_info ){
                        
                                if ( ! $this->wcap_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
                                    $query_update = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET abandoned_cart_info = '" . $updated_cart_info . "', abandoned_cart_time  = '" . $current_time . "', language = '". $current_user_lang ."', ip_address = '". $visitor_user_ip_address ."' WHERE session_id ='" . $get_cookie[0] . "' AND cart_ignored='0' ";
                                    $wpdb->query( $query_update );
                                }
                            }
                        }
                    }
                }
            }
        }
    
        function wcap_compare_only_guest_carts( $new_cart, $last_abandoned_cart ) {
    
            $current_woo_cart   = array();
            $current_woo_cart   = json_decode ( stripslashes($new_cart), true );
            
            $abandoned_cart_arr = array();
            $abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
            
            $temp_variable      = "";
            
            if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) {
                
            } else {
                $temp_variable      = $current_woo_cart;
                $current_woo_cart   = $abandoned_cart_arr;
                $abandoned_cart_arr = $temp_variable;
            }
            if ( is_array( $current_woo_cart ) || is_object( $current_woo_cart ) ){
                foreach ( $current_woo_cart as $key => $value ) {
            
                    foreach ( $value as $item_key => $item_value ) {
                        $current_cart_product_id   = $item_value['product_id'];
                        $current_cart_variation_id = $item_value['variation_id'];
                        $current_cart_quantity     = $item_value['quantity'];
            
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['product_id'] ) ) $abandoned_cart_product_id = $abandoned_cart_arr[$key][$item_key]['product_id'];
                        else $abandoned_cart_product_id = "";
            
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['variation_id'] ) ) $abandoned_cart_variation_id = $abandoned_cart_arr[$key][$item_key]['variation_id'];
                        else $abandoned_cart_variation_id = "";
            
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['quantity'] ) ) $abandoned_cart_quantity = $abandoned_cart_arr[$key][$item_key]['quantity'];
                        else $abandoned_cart_quantity = "";
            
                        if ( ( $current_cart_product_id   != $abandoned_cart_product_id ) ||
                            ( $current_cart_variation_id != $abandoned_cart_variation_id ) ||
                            ( $current_cart_quantity     != $abandoned_cart_quantity ) ) {
                                return false;
                        }
                    }
                }
            }
            return true;
        }
    
        function wcap_compare_carts( $user_id, $last_abandoned_cart ) {
            $current_woo_cart   = array();
            $current_woo_cart   = get_user_meta( $user_id, '_woocommerce_persistent_cart', true );
            $abandoned_cart_arr = array();
            $abandoned_cart_arr = json_decode( $last_abandoned_cart, true );          
            $temp_variable      = "";
    
            if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) {
                
            } else {
                $temp_variable      = $current_woo_cart;
                $current_woo_cart   = $abandoned_cart_arr;
                $abandoned_cart_arr = $temp_variable;
            }
            if ( is_array( $current_woo_cart ) || is_object( $current_woo_cart ) ){
                foreach ( $current_woo_cart as $key => $value ) {
                    
                    foreach ( $value as $item_key => $item_value ) {
                        $current_cart_product_id   = $item_value['product_id'];
                        $current_cart_variation_id = $item_value['variation_id'];
                        $current_cart_quantity     = $item_value['quantity'];
            
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['product_id'] ) ) $abandoned_cart_product_id = $abandoned_cart_arr[$key][$item_key]['product_id'];
                        else $abandoned_cart_product_id = "";
    
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['variation_id'] ) ) $abandoned_cart_variation_id = $abandoned_cart_arr[$key][$item_key]['variation_id'];
                        else $abandoned_cart_variation_id = "";
    
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['quantity'] ) ) $abandoned_cart_quantity = $abandoned_cart_arr[$key][$item_key]['quantity'];
                        else $abandoned_cart_quantity = "";
            
                        if ( ( $current_cart_product_id   != $abandoned_cart_product_id ) ||
                             ( $current_cart_variation_id != $abandoned_cart_variation_id ) ||
                             ( $current_cart_quantity     != $abandoned_cart_quantity ) ) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
    
        function wcap_action_after_delivery_session( $order ) {
            global $wpdb;
            
            if( session_id() === '' ){
                //session has not started
                session_start();
            }
            
            $order_id = $order->id;
            
            $get_abandoned_id_of_order  = '';
            $get_sent_email_id_of_order = '';
            $get_abandoned_id_of_order  =   get_post_meta( $order_id, 'wcap_recover_order_placed', true );
             
            if ( isset( $get_abandoned_id_of_order ) && $get_abandoned_id_of_order != '' ){
                 
                $get_abandoned_id_of_order  =   get_post_meta( $order_id, 'wcap_recover_order_placed', true );
                $get_sent_email_id_of_order = get_post_meta( $order_id, 'wcap_recover_order_placed_sent_id', true );
            
                $query_order = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET recovered_cart= '" . $order_id . "', cart_ignored = '1'
                                    WHERE id = '".$get_abandoned_id_of_order."' ";
                $wpdb->query( $query_order );
            
                $query_language_session = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET language = ''
                                               WHERE id = '".$get_abandoned_id_of_order."' ";
                $wpdb->query( $query_language_session );
            
                $recover_order = "UPDATE `" . $wpdb->prefix . "ac_sent_history` SET recovered_order = '1'
    								  WHERE id ='" . $get_sent_email_id_of_order . "' ";
                $wpdb->query( $recover_order );
            
                $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-ac' ) );
                 
                delete_post_meta( $order_id, 'wcap_recover_order_placed', $get_abandoned_id_of_order );
                delete_post_meta( $order_id , 'wcap_recover_order_placed_sent_id', $get_sent_email_id_of_order );
                delete_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );
            }
    
            $user_id = get_current_user_id();
            
            if (isset( $_SESSION [ 'wcap_selected_language' ] ) && $_SESSION [ 'wcap_selected_language' ] !='' && function_exists('icl_register_string')){
            
                global $sitepress;
            
                $sitepress->switch_lang( $_SESSION [ 'wcap_selected_language' ] );
            }
            
            if( isset( $_SESSION['email_sent_id'] ) ) {
    		    $sent_email = $_SESSION['email_sent_id'];
            } else {
                $sent_email = '';    
            }
              
            if ( isset( $_SESSION['user_id'] ) && $user_id == "" ) {
                $user_id = $_SESSION['user_id'];
                
                //  Set the session variables to blanks
                $_SESSION['guest_first_name'] = $_SESSION['guest_last_name'] = $_SESSION['guest_email'] = $_SESSION['user_id'] = $_SESSION['guest_phone'] = "";
            }
            delete_user_meta( $user_id, '_woocommerce_ac_persistent_cart_time' );
            delete_user_meta( $user_id, '_woocommerce_ac_persistent_cart_temp_time' );
            
            remove_action( 'woocommerce_cart_updated', array( 'woocommerce_abandon_cart', 'wcap_store_cart_timestamp' ) );
            
            // get all latest abandoned carts that were modified
            $sent_history_query = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history` WHERE id = %d ORDER BY id DESC LIMIT 1";
            $sent_history_results = $wpdb->get_results( $wpdb->prepare( $sent_history_query, $sent_email ) );
            if( isset( $sent_history_results[0] ) ) { 
                $abandoned_cart_id = $sent_history_results[0]->abandoned_order_id;
                // get all latest abandoned carts that were modified
                $query = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND id = %d ORDER BY id DESC LIMIT 1";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id, $abandoned_cart_id  ) );
            } else {
                $abandoned_cart_id = '';
                // get all latest abandoned carts that were modified
                $query = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' ORDER BY id DESC LIMIT 1";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
            }
            remove_action( 'woocommerce_cart_updated', array( 'woocommerce_abandon_cart', 'wcap_store_cart_timestamp' ) );
            
            $get_ac_id_results = array();
            if ( isset( $results[0]->id ) && '' != $results[0]->id ){
                $get_ac_id_query   = "SELECT abandoned_order_id FROM `" . $wpdb->prefix."ac_sent_history` WHERE abandoned_order_id = %d";
                $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $results[0]->id ) );
            }
            
            
           if ( $results && count ( $get_ac_id_results ) > 0 ) {
                
                if ( get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5("yes") || 
                     get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5("no") ) {
                    
                    $order_id = $order->id;
                    $query_order = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET recovered_cart = '" . $order_id . "', cart_ignored = '1' 
                    				WHERE id ='" . $results[0]->id . "' ";
                    $wpdb->query( $query_order );
                    
                    $query_order_language = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET language = '' WHERE recovered_cart ='".$order_id."' ";
                    $wpdb->query( $query_order_language );
                    
                    delete_user_meta( $user_id, '_woocommerce_ac_modified_cart' );
                    delete_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );
    
                     if( isset( $_SESSION['email_sent_id'] ) ) {
    				    $sent_email = $_SESSION['email_sent_id'];
    				} else {
    				    $sent_email = '';
    				}
    				$recover_order = "UPDATE `" . $wpdb->prefix . "ac_sent_history` SET recovered_order = '1' 
    									WHERE id ='" . $sent_email . "' ";
    				$wpdb->query( $recover_order );
                } else {
                    
                    if ( isset( $results[0]->user_type ) && $results[0]->user_type == "GUEST" ) {
                        $delete_guest = "DELETE FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = '" . $user_id . "'";
                        $wpdb->query( $delete_guest ); 
                    }
    
                    if ( isset( $results[0]->id ) ) {
                        $delete_query = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id ='" . $results[0]->id . "' ";
                        $wpdb->query( $delete_query );
                    }
                }
                $display_tracked_coupons = get_option( 'ac_track_coupons' );
                
                if ( isset( $display_tracked_coupons ) && $display_tracked_coupons == 'on' ) {
                    delete_user_meta( $user_id, '_woocommerce_ac_coupon' );
                }
            } else if (count ( $get_ac_id_results ) > 0 ){
                $email_id = $order->billing_email;
                $query = "SELECT * FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE email_id = %s";
                $results_id = $wpdb->get_results( $wpdb->prepare( $query, $email_id ) );
                
                if ( $results_id ) {
                    $record_status = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND recovered_cart = '0'";
                    $results_status = $wpdb->get_results( $wpdb->prepare( $record_status, $results_id[0]->id ) );
                        
                    if ( count ( $results_status ) > 0 ) {
                        
                        if ( get_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart', true ) == md5("yes") ||
                                get_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart', true ) == md5("no") ) {
                                
                            $order_id = $order->id;
                            $query_order = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET recovered_cart= '" . $order_id . "', cart_ignored = '1' WHERE id='".$results_status[0]->id."' ";
                            $wpdb->query( $query_order );
                            
                            $query_order_language = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET language = '' WHERE recovered_cart ='".$order_id."' ";
                            $wpdb->query( $query_order_language );
    
                            delete_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart' );
    
    						$sent_email = $_SESSION['email_sent_id'];
    						$recover_order = "UPDATE `" . $wpdb->prefix . "ac_sent_history` SET recovered_order = '1' 
    						WHERE id ='" . $sent_email ."' ";
    						$wpdb->query( $recover_order );
                        } else {
                            $delete_guest = "DELETE FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = '" . $results_id[0]->id . "'";
                            $wpdb->query( $delete_guest );
                            
                            $delete_query = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id='" . $results_id[0]->id . "' ";
                            $wpdb->query( $delete_query );
                        }
                    }       
                }
            }
            if (isset( $_SESSION [ 'wcap_selected_language' ] ) && $_SESSION [ 'wcap_selected_language' ] !='' && function_exists('icl_register_string')){
                $_SESSION [ 'wcap_selected_language' ] = '';
            }
        }
    
        function wcap_coupon_ac_test( $valid ) {
            global $wpdb;
            if ( isset( $_SESSION ['user_id'] ) && $_SESSION ['user_id'] > 0 ){
                $user_id = $_SESSION ['user_id'];
            }else{
                $user_id = get_current_user_id();
            }
            //`$coupon_code = $_POST['coupon_code'];
            if( isset( $_SESSION ['acp_c']) && $_SESSION ['acp_c'] != '') { // check here
                $coupon_code = $_SESSION ['acp_c'];
            }else{
                $coupon_code = $_POST['coupon_code'];
            }
    
            if ( $valid != '' ) {
                $abandoned_cart_id_query   = "SELECT id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0'";
                $abandoned_cart_id_results = $wpdb->get_results( $wpdb->prepare( $abandoned_cart_id_query, $user_id ) );
                $abandoned_cart_id         = '0';
                if( isset( $abandoned_cart_id_results ) && !empty( $abandoned_cart_id_results ) ) {
                    $abandoned_cart_id = $abandoned_cart_id_results[0]->id;
                }
                $existing_coupon = ( get_user_meta( $user_id, '_woocommerce_ac_coupon', true ) );
    
                    if ( count( $existing_coupon ) > 0 && gettype( $existing_coupon ) == "array" ) {
                    
                        foreach ( $existing_coupon as $key => $value ) {
                        
                            if ( $existing_coupon[$key]['coupon_code'] != $coupon_code ) {
                                
                                $existing_coupon[]      = array ( 'coupon_code' => $coupon_code, 'coupon_message' => __( 'Discount code applied successfully.', 'woocommerce-ac' ) );
                                $post_meta_coupon_array = array ( 'coupon_code' => $coupon_code, 'coupon_message' => __( 'Discount code applied successfully.', 'woocommerce-ac' ) );
                                update_user_meta( $user_id, '_woocommerce_ac_coupon', $existing_coupon );
                                
                                if( $user_id > 0){
                                    add_post_meta( $abandoned_cart_id, '_woocommerce_ac_coupon', $post_meta_coupon_array );
                                }
                            return $valid;
                        }
                    }
                } else {
                    $coupon_details[]       = array ( 'coupon_code' => $coupon_code, 'coupon_message' => __( 'Discount code applied successfully.', 'woocommerce-ac' ) );
                    $post_meta_coupon_array = array ( 'coupon_code' => $coupon_code, 'coupon_message' => __( 'Discount code applied successfully.', 'woocommerce-ac' ) );
                    update_user_meta( $user_id, '_woocommerce_ac_coupon', $coupon_details );
                    
                    if( $user_id > 0) {
                        add_post_meta( $abandoned_cart_id, '_woocommerce_ac_coupon', $post_meta_coupon_array );
                    }
                    return $valid;
                }
            }
            return $valid;
        }
    
        function wcap_coupon_ac_test_new( $valid, $new ) {
           
                global $wpdb;
                $coupon_code = '';
                if ( isset( $_SESSION ['user_id'] ) && $_SESSION ['user_id'] > 0 ){
                    $user_id = $_SESSION ['user_id'];
                }else{
                    $user_id = get_current_user_id();
                }
    
                $abandoned_cart_id_query   = "SELECT id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0'";
                $abandoned_cart_id_results = $wpdb->get_results( $wpdb->prepare( $abandoned_cart_id_query, $user_id ) );
                $abandoned_cart_id         = '0';
                if( isset( $abandoned_cart_id_results ) && !empty( $abandoned_cart_id_results ) ) {
                    $abandoned_cart_id = $abandoned_cart_id_results[0]->id;
                }
                if( isset( $_SESSION ['acp_c'] ) && $_SESSION ['acp_c'] != ''){ // check here
                    $coupon_code = $_SESSION ['acp_c'];
                }else{
                    if( isset( $_POST['coupon_code'] ) ){
                        $coupon_code = $_POST['coupon_code'];
                    }
                }
                
                if ( '' != $coupon_code ){
                    $existing_coupon        = get_user_meta( $user_id, '_woocommerce_ac_coupon', true );
                    $existing_coupon[]      = array ( 'coupon_code' => $coupon_code, 'coupon_message' => $valid );
                    $post_meta_coupon_array = array ( 'coupon_code' => $coupon_code, 'coupon_message' => $valid );
                    if( $user_id > 0 ) {
                    add_post_meta( $abandoned_cart_id, '_woocommerce_ac_coupon', $post_meta_coupon_array );
                    }
                    update_user_meta( $user_id, '_woocommerce_ac_coupon', $existing_coupon );
                }
            return $valid;
        }
    
        function wcap_action_admin_init() {
            
            global $typenow;
            /* Only hook up these filters if we're in the admin panel, and the current user has permission
             * to edit posts and pages.
             */
            if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
                return;
            }
             
            if ( !isset( $_GET['page'] ) || $_GET['page'] != "woocommerce_ac_page" ) {
                return;
            }
             
            if ( get_user_option( 'rich_editing' ) == 'true' ) {
                remove_filter( 'the_excerpt', 'wpautop' );
                
                add_filter('tiny_mce_before_init',  array( &$this, 'wcap_format_tiny_MCE'));
                add_filter( 'mce_buttons',          array( &$this, 'wcap_filter_mce_button' ) );
                add_filter( 'mce_external_plugins', array( &$this, 'wcap_filter_mce_plugin' ) );
            }
            
            if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' == $_GET['page'] ){
                if( session_id() === '' ){
                    //session has not started
                    session_start();
                }
            }
        }
    
        function wcap_filter_mce_button( $buttons ) {
            
            // add a separation before our button, here our button's id is &quot;mygallery_button&quot;
            array_push( $buttons, 'abandoncart_pro', '|' );
            array_push( $buttons, 'abandoncart_pro_css', '|' );
            
            return $buttons;
        }
        
        function wcap_filter_mce_plugin( $plugins ) {
            // this plugin file will work the magic of our button
            $plugins['abandoncart_pro']     = plugin_dir_url( __FILE__ ) . 'assets/js/abandoncart_plugin_button.js';
            $plugins['abandoncart_pro_css'] = plugin_dir_url( __FILE__ ) . 'assets/js/abandoncart_plugin_button_css.js';
            return $plugins;
        }
    
        function wcap_display_tabs() {
        
            $action           = "";
            if ( isset( $_GET['action'] ) ){
                $action = $_GET['action'];
            }
            
            $active_wcap_dashboard = "";
            $active_listcart       = "";
            $active_emailtemplates = "";
            $active_settings       = "";
            $active_stats          = "";
        
            
            if ( ( 'wcap_dashboard' == $action || 'orderdetails' == $action ) || '' == $action ) {
                $active_wcap_dashboard = "nav-tab-active";
            }
            
            if ( $action == 'listcart'  ) {
                $active_listcart = "nav-tab-active";
            }
            
            if ( $action == 'emailtemplates' ) {
                $active_emailtemplates = "nav-tab-active";
            }
            
            if ( $action == 'emailsettings' ) {
                $active_settings       = "nav-tab-active";
            }
            
            if ( $action == 'stats' ) {
                $active_stats          = "nav-tab-active";
            }
            
            if ( $action == 'emailstats' ) {
                $active_emailstats     = "nav-tab-active";
            }
    
    		if ( $action == 'report' ) {
                $active_report         = "nav-tab-active";
            }
            ?>
            
            <div style="background-image: url( '<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/ac_tab_icon.png' ) !important;" class="icon32">
            <br>
            </div>
            <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="admin.php?page=woocommerce_ac_page&action=wcap_dashboard" class="nav-tab <?php echo $active_wcap_dashboard; ?>"> <?php _e( 'Dashboard', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab <?php echo $active_listcart; ?>"> <?php _e( 'Abandoned Orders', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab <?php echo $active_emailtemplates; ?>"> <?php _e( 'Email Templates', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab <?php echo $active_settings; ?>"> <?php _e( 'Settings', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab <?php echo $active_stats; ?>"> <?php _e( 'Recovered Orders', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailstats" class="nav-tab <?php if( isset( $active_emailstats ) ) echo $active_emailstats; ?>"> <?php _e( 'Sent Emails', 'woocommerce-ac' );?> </a>
        		<a href="admin.php?page=woocommerce_ac_page&action=report" class="nav-tab <?php if( isset( $active_report ) ) echo $active_report; ?>"> <?php _e( 'Product Report', 'woocommerce-ac' );?> </a>
            </h2>
            <?php
        }
    
        function wcap_enqueue_scripts_js( $hook ) {
            
            if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
                return;
            } else {
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script(
                        'jquery-ui-min',
                        '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js',
                        '',
                        '',
                        false
                );
                wp_enqueue_script(
                        'jquery-tip',
                        plugins_url('/assets/js/jquery.tipTip.minified.js', __FILE__),
                        '',
                        '',
                        false
                );
                                        
                // scripts included for woocommerce auto-complete coupons
                wp_register_script( 'woocommerce_admin',     plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ) );
                wp_register_script( 'jquery-ui-datepicker',  plugins_url() . '/woocommerce/assets/js/admin/ui-datepicker.js' );
                wp_register_script( 'woocommerce_metaboxes', plugins_url() . '/woocommerce/assets/js/admin/meta-boxes.js',        array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable' ) );
                
                wp_register_script( 'enhanced' ,             plugins_url() . '/woocommerce/assets/js/admin/wc-enhanced-select.js',            array( 'jquery', 'select2' ) );
                wp_enqueue_script( 'woocommerce_metaboxes' );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                
                
                wp_register_script( 'flot', plugins_url()        . '/woocommerce-abandon-cart-pro/assets/js/jquery-flot/jquery.flot.min.js', array( 'jquery' ) );
                wp_register_script( 'flot-resize', plugins_url() . '/woocommerce-abandon-cart-pro/assets/js/jquery-flot/jquery.flot.resize.min.js', array( 'jquery', 'flot' ) );
                wp_register_script( 'flot-time', plugins_url()   . '/woocommerce-abandon-cart-pro/assets/js/jquery-flot/jquery.flot.time.min.js', array( 'jquery', 'flot' ) );
                wp_register_script( 'flot-pie', plugins_url()    . '/woocommerce-abandon-cart-pro/assets/js/jquery-flot/jquery.flot.pie.min.js', array( 'jquery', 'flot' ) );
                wp_register_script( 'flot-stack', plugins_url()  . '/woocommerce-abandon-cart-pro/assets/js/jquery-flot/jquery.flot.stack.min.js', array( 'jquery', 'flot' ) );
                wp_register_script( 'wcap-dashboard-report', plugins_url()  . '/woocommerce-abandon-cart-pro/assets/js/wcap_reports.js', array( 'jquery' ) );
                
                
                wp_enqueue_script( 'flot' );
                wp_enqueue_script( 'flot-resize' );
                wp_enqueue_script( 'flot-time' );
                wp_enqueue_script( 'flot-pie' );
                wp_enqueue_script( 'flot-stack' );
                wp_enqueue_script( 'wcap-dashboard-report' );
                
                /*
                 * It is used for the Search coupon new functionality.
                 * Since: 3.3
                 */
                wp_localize_script( 'enhanced', 'wc_enhanced_select_params', array(
                                    'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
                                    'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
                                    'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
                                    'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
                                    'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
                                    'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
                                    'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
                                    'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
                                    'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
                                    'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
                                    'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
                                    'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
                                    'ajax_url'                  => admin_url( 'admin-ajax.php' ),
                                    'search_products_nonce'     => wp_create_nonce( 'search-products' ),
                                    'search_customers_nonce'    => wp_create_nonce( 'search-customers' )
                ) );
                
                $wc_round_value = wc_get_price_decimals();
                wp_localize_script( 'wcap-dashboard-report', 'wcap_dashboard_report_params', array(
                                    'currency_symbol'           =>  get_woocommerce_currency_symbol(),
                                    'wc_round_value'            => $wc_round_value
                ) );
                
                wp_enqueue_script( 'enhanced' );
                wp_enqueue_script( 'woocommerce_admin' );
                wp_enqueue_script( 'jquery-ui-sortable' );
                
                $woocommerce_admin_meta_boxes = array(
                        'search_products_nonce' => wp_create_nonce( "search-products" ),
                        'plugin_url'            => plugins_url(),
                        'ajax_url'              => admin_url( 'admin-ajax.php' )
                );
                wp_localize_script( 'woocommerce_metaboxes', 'woocommerce_admin_meta_boxes', $woocommerce_admin_meta_boxes );
                // scripts ended for woocommerce auto-complete coupons                
                ////////////////////////////////////////////////////////////////                
                ?>
                <script type="text/javascript" >
    
                    function wcap_activate_email_template( template_id, active_state ) {
                        
                        location.href = 'admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=activate_template&id='+template_id+'&active_state='+active_state ;
                    }
                </script>                  
                <!-- /////////////////////////////////////////////////////////////// -->                  
                <?php
                $js_src = includes_url('js/tinymce/') . 'tinymce.min.js';
                wp_enqueue_script( 'tinyMCE_ac', $js_src );
                wp_enqueue_script( 'ac_email_variables', plugins_url() . '/woocommerce-abandon-cart-pro/assets/js/abandoncart_plugin_button.js' );
                wp_enqueue_script( 'ac_email_button_css', plugins_url() . '/woocommerce-abandon-cart-pro/assets/js/abandoncart_plugin_button_css.js' );
                ?>
            <?php
            }
        }
    
        function wcap_enqueue_scripts_css( $hook ) {
            
            if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
                wp_enqueue_style( 'wcap-dashboard',           plugins_url() . '/woocommerce-abandon-cart-pro/assets/css/style.css' );
                return;
            } else {
                wp_enqueue_style( 'jquery-ui',                "//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css", '', '', false );
                wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
                wp_enqueue_style( 'jquery-ui-style',          '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
                wp_enqueue_style( 'abandoned-orders-list',    plugins_url() . '/woocommerce-abandon-cart-pro/assets/css/view.abandoned.orders.style.css' );
                wp_enqueue_style( 'wcap-dashboard',           plugins_url() . '/woocommerce-abandon-cart-pro/assets/css/reports.css' );
            } 
        }
    
        //bulk action
        // to over come the wp redirect warning while deleting
        function wcap_output_buffer() {
            ob_start();
        }
    
        /**
         * Abandon Cart Settings Page
         */
        function wcap_menu_page() {
            
            if ( is_user_logged_in() ) {
                global $wpdb;
    
                // Check the user capabilities
                if ( ! current_user_can( 'manage_woocommerce' ) ) {
                    wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-ac' ) );
                }
                ?>
                <div class="wrap">
                <h2>
                    <?php _e( 'WooCommerce - Abandon Cart', 'woocommerce-ac' ); ?>
                </h2>
                <?php 
                
                $action = "";
                if ( isset( $_GET['action'] ) ) { 
                    $action = $_GET['action'];
                }
                
                $mode = "";
                if ( isset( $_GET['mode'] ) ){
                    $mode = $_GET['mode'];
                }
                
                
                $this->wcap_display_tabs();
                
                /**
                 * When we delete the item from the below drop down it is registred in action 2
                 */
                $action_two = "";
                if ( isset( $_GET['action2'] ) ) {
                    $action_two = $_GET['action2'];
                }
                 
                // Detect when a bulk action is being triggered on abandoned orders page.
                if( 'wcap_delete' === $action || 'wcap_delete' === $action_two  ){
                    
                    $ids       = isset( $_GET['abandoned_order_id'] ) ? $_GET['abandoned_order_id'] : false;
                    if ( ! is_array( $ids ) ){
                        $ids   = array( $ids );
                    }
                
                    foreach ( $ids as $id ) {
                        $class = new wcap_delete_bulk_action_handler();
                        $class->wcap_delete_bulk_action_handler_function( $id );
                    }
                }
                
                //Detect when a bulk action is being triggered on temnplates page.
                if( 'wcap_delete_template' === $action || 'wcap_delete_template' === $action_two  ){
                
                    $ids       = isset( $_GET['template_id'] ) ? $_GET['template_id'] : false;
                    
                    if ( ! is_array( $ids ) ){
                        $ids   = array( $ids );
                    }
                
                    foreach ( $ids as $id ) {
                        $class = new wcap_delete_bulk_action_handler();
                        $class->wcap_delete_template_bulk_action_handler_function( $id );
                    }
                }
                
                if ( isset($_GET ['wcap_deleted']) && 'YES' == $_GET['wcap_deleted'] ) { ?>
                    <div id="message" class="updated fade">
                        <p>
                            <strong>
                                <?php _e( 'The abandoned cart has been successfully deleted.', 'woocommerce-ac' ); ?>
                            </strong>
                        </p>
                    </div>
                <?php }
    
                if ( isset($_GET ['wcap_template_deleted']) && 'YES' == $_GET['wcap_template_deleted'] ) { ?>
                    <div id="message" class="updated fade">
                        <p>
                            <strong>
                                <?php _e( 'The Template has been successfully deleted.', 'woocommerce-ac' ); ?>
                            </strong>
                        </p>
                    </div>
                <?php }
               
                if ( 'emailsettings' == $action ) {
                ?>
                    <p><?php _e( 'Change settings for sending email notifications to Customers, to Admin, Tracking Coupons etc.', 'woocommerce-ac' ); ?></p>
                    <div id="content">
    
    					<form method="post" action="options.php">
                            <?php settings_fields     ( 'woocommerce_ac_settings' ); ?>
                            <?php do_settings_sections( 'woocommerce_ac_page' ); ?>
    						<?php settings_errors(); ?>
    						<?php submit_button(); ?>
    
                        </form>
                    </div>
                    
                    <script type="text/javascript">
    	                jQuery(document).on('change', '#ac_disable_guest_cart_email', function() {
    	                    jQuery(this).closest('tbody').find('#ac_track_guest_cart_from_cart_page').prop('disabled', this.checked);
    	                }); 
    	            </script>
                <?php 
                }elseif ( 'wcap_dashboard' == $action || '' == $action ) { 
                    include_once( 'includes/classes/class-wcap-dashboard-report-table.php' );
                    
                    $start_date = '';
                    $end_date   = '';
                    if( isset( $_GET['duration_select'] ) && '' != $_GET['duration_select'] ){
                        $selected_data_range = $_GET['duration_select'];
                    }else{
                        $selected_data_range = 'this_month';
                    }
                    if ( isset( $selected_data_range ) && 'other' == $selected_data_range ){
                        if( isset( $_GET['wcap_start_date'] ) && '' != $_GET['wcap_start_date'] ){
                            $start_date = $_GET['wcap_start_date'];
                        }
                        if( isset( $_GET['wcap_end_date'] ) && '' != $_GET['wcap_end_date'] ){
                            $end_date   = $_GET['wcap_end_date'];
                        }
                    }
                    $display_report = new Wcap_Dashboard_Report_Action();
                    $display_report->wcap_get_all_reports( $selected_data_range, $start_date, $end_date );
                
                }
                elseif ( 'listcart' == $action && ( !isset($_GET['action_details']) || 'orderdetails' != $_GET['action_details'] )
                    && ( !isset($_GET['wcap_download']) || 'wcap.csv' != $_GET['wcap_download'] ) && ( !isset($_GET['wcap_download']) || 'wcap.print' != $_GET['wcap_download'] )
                    ) {
                ?>                 
                    <p> 
                        <?php _e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-ac' ); ?> 
                    </p>       
                    <?php
                    global $wpdb;
                    
                    include_once( 'includes/classes/class-wcap-abandoned-orders-table.php' );
                    $wcap_abandoned_order_list = new WCAP_Abandoned_Orders_Table();
                    $wcap_abandoned_order_list->wcap_abandoned_order_prepare_items();
                    ?>
                    
                    <div class="wrap">
                        <form id="wcap-abandoned-orders" method="get" >
                            <input type="hidden" name="page" value="woocommerce_ac_page" />
                            <input type="hidden" name="action" value="listcart" />
                            <div class= "wcap_download" >
                                <a href="<?php echo esc_url( add_query_arg( 'wcap_download', 'wcap.print' ) ); ?>" target="_blank" class="button-secondary"><?php _e( 'Print', 'woocommerce-booking' ); ?></a>
    					        <a href="<?php echo esc_url( add_query_arg( 'wcap_download', 'wcap.csv' ) ); ?>"  class="button-secondary"><?php _e( 'CSV', 'woocommerce-booking' ); ?></a>
					        </div>
                            <?php $wcap_abandoned_order_list->display(); ?>
                        </form>
                    </div>
                <?php 
                }else if ( 'listcart' == $action && ( isset($_GET['wcap_download']) && 'wcap.csv' == $_GET['wcap_download'] ) ) {
                
                    /*
                     * Here we take all the previous echoed, printed data. Then we clear the buffer.
                     */
                    $old_data = ob_get_clean ();
                    $wcap_csv = $this->wcap_generate_csv_report();
                    
                    header("Content-type: application/x-msdownload");
                    header("Content-Disposition: attachment; filename=wcap_cart_report.csv");
                    header("Pragma: no-cache");
                    header("Expires: 0");
                    
                    /*
                     * Here Space before and after variable is needed other wise it is not printing th data in csv format. 
                     */
                    echo html_entity_decode ( " $wcap_csv " );
                    exit;
                } elseif ( 'emailtemplates' == $action && ( 'edittemplate' != $mode && 'addnewtemplate' != $mode && 'copytemplate' != $mode ) ) {
                ?>
                    <p> 
                        <?php _e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac' ); ?> 
                    </p>
                
                    <?php 
                    $insert_template_successfuly_pro = $update_template_successfuly_pro = '';
                    // Save the field values
                    if ( isset( $_POST['ac_settings_frm'] ) && 'save' == $_POST['ac_settings_frm'] ) {
                    
                        $coupon_code_id = "";
                        if ( isset( $_POST['coupon_ids'][0] ) ){
                            $coupon_code_id = $_POST['coupon_ids'][0];
                        }
                        
                        $active_post       = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
                        $unique_coupon     = ( empty( $_POST['unique_coupon'] ) ) ? '0' : '1';
                        $is_wc_template    = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
                        
                        if ( $active_post == 1 ) {
                            
                            $check_query   = "SELECT * FROM `" . $wpdb->prefix . "ac_email_templates` WHERE is_active='1' AND frequency= %d AND day_or_hour= %s ";
                            $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $_POST['email_frequency'], $_POST['day_or_hour'] ) );
                            if ( count( $check_results ) == 0 ) {
                                $query = "INSERT INTO `" . $wpdb->prefix . "ac_email_templates`( from_email, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, from_name, reply_email, default_template, discount, generate_unique_coupon_code, is_wc_template, wc_email_header )
                                VALUES ( '" . $_POST['woocommerce_ac_email_from'] . "', 
                                        '" . $_POST['woocommerce_ac_email_subject'] . "', 
                                        '" . $_POST['woocommerce_ac_email_body'] . "', 
                                        '" . $active_post . "', 
                                        '" . $_POST['email_frequency'] . "', 
                                        '" . $_POST['day_or_hour'] . "', 
                                        '" . $coupon_code_id . "', 
                                        '" . $_POST['woocommerce_ac_template_name'] . "',
                                        '" . $_POST['woocommerce_ac_from_name'] . "',
                                        '" . $_POST['woocommerce_ac_email_reply'] . "',
                                        '0',
                                        '0',
                                        '" . $unique_coupon . "',
                                        '" . $is_wc_template . "',
                                        '" . $_POST['wcap_wc_email_header'] . "' )";
                                
                                $insert_template_successfuly_pro = $wpdb->query( $query );
                            } else {
                                $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates` SET is_active='0' WHERE frequency='" . $_POST['email_frequency'] . "' AND day_or_hour='" . $_POST['day_or_hour'] . "' ";
                                $wpdb->query( $query_update );
                                $query_insert_new = "INSERT INTO `" . $wpdb->prefix . "ac_email_templates` ( from_email, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, from_name, reply_email, default_template, discount, generate_unique_coupon_code, is_wc_template, wc_email_header )
                                VALUES ( '" . $_POST['woocommerce_ac_email_from'] . "', 
                                        '" . $_POST['woocommerce_ac_email_subject'] . "', 
                                        '" . $_POST['woocommerce_ac_email_body'] . "', 
                                        '" . $active_post . "', 
                                        '" . $_POST['email_frequency'] . "', 
                                        '" . $_POST['day_or_hour'] . "', 
                                        '" . $coupon_code_id . "', 
                                        '" . $_POST['woocommerce_ac_template_name'] . "',
                                        '" . $_POST['woocommerce_ac_from_name'] . "',
                                        '" . $_POST['woocommerce_ac_email_reply'] . "', 
                                        '0', 
                                        '0',
                                        '" . $unique_coupon . "',
                                        '" . $is_wc_template . "',
                                        '" . $_POST['wcap_wc_email_header'] . "' )";
                                $insert_template_successfuly_pro = $wpdb->query( $query_insert_new );
                            }
                        } else {
                        
                            $query = "INSERT INTO `" . $wpdb->prefix . "ac_email_templates` ( from_email, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, from_name, reply_email, default_template, discount, generate_unique_coupon_code, is_wc_template, wc_email_header )
                                      VALUES ( '" . $_POST['woocommerce_ac_email_from'] . "',
                                               '" . $_POST['woocommerce_ac_email_subject'] . "',
                                               '" . $_POST['woocommerce_ac_email_body'] . "',
                                               '" . $active_post . "',
                                               '" . $_POST['email_frequency'] . "',
                                               '" . $_POST['day_or_hour'] . "',
                                               '" . $coupon_code_id . "',
                                               '" . $_POST['woocommerce_ac_template_name'] . "',
                                               '" . $_POST['woocommerce_ac_from_name'] . "',
                                               '" . $_POST['woocommerce_ac_email_reply'] . "',
                                               '0',
                                               '0',
                                               '" . $unique_coupon . "',
                                               '" . $is_wc_template . "',
                                               '" . $_POST['wcap_wc_email_header'] . "' )";
                              $insert_template_successfuly_pro = $wpdb->query( $query );
                        }
                    }
                
                    if ( isset( $_POST['ac_settings_frm'] ) && 'update' == $_POST['ac_settings_frm'] ) {
                    
                        if ( isset( $_POST['coupon_ids'] ) ) {
                            $coupon_code_id = $_POST['coupon_ids'][0];
                        } else {
                        
                            if ( isset( $_POST['coupon_ids'][0] ) ){
                                $coupon_code_id = $_POST['coupon_ids'][0];
                            }
                            else {
                                $coupon_code_id = "";
                            }
                        }
                    
                        $coupon_code_id_last_character = substr($coupon_code_id, -1);
                    
                        if ( "," == $coupon_code_id_last_character ){
                        
                            $coupon_code_id = rtrim( $coupon_code_id, ",");
                        }
                        $active         = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
                        $unique_coupon  = ( empty( $_POST['unique_coupon'] ) ) ? '0' : '1';
                        $is_wc_template = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
                    
                        if ( $active == 1 ) {
                            $check_query   = "SELECT * FROM `" . $wpdb->prefix."ac_email_templates` WHERE is_active='1' AND frequency= %d AND day_or_hour= %s";
                            $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $_POST['email_frequency'], $_POST['day_or_hour'] ) );
                        
                            if ( count( $check_results ) == 0 ) {
                            
                                $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates`
                                                 SET
                                                 from_email      = '" . $_POST['woocommerce_ac_email_from'] . "',
                                                 subject         = '" . $_POST['woocommerce_ac_email_subject'] . "',
                                                 body            = '" . $_POST['woocommerce_ac_email_body'] . "',
                                                 is_active       = '" . $active . "', frequency = '" . $_POST['email_frequency'] . "',
                                                 day_or_hour     = '" . $_POST['day_or_hour'] . "',
                                                 coupon_code     = '" . $coupon_code_id . "',
                                                 template_name   = '" . $_POST['woocommerce_ac_template_name'] . "',
                                                 from_name       = '" . $_POST['woocommerce_ac_from_name'] . "',
                                                 reply_email     = '" . $_POST['woocommerce_ac_email_reply'] . "',
                                                 generate_unique_coupon_code = '" . $unique_coupon . "',
                                                 is_wc_template  = '" . $is_wc_template . "',
                                                 wc_email_header = '" . $_POST['wcap_wc_email_header'] . "'
                                                 WHERE id        = '" . $_POST['id'] . "' ";
                            
                                $update_template_successfuly_pro = $wpdb->query( $query_update );
                            
                            } else {
                                $query_update_new = "UPDATE `" . $wpdb->prefix . "ac_email_templates`
                                                    SET
                                                    is_active       = '0'
                                                    WHERE frequency = '" . $_POST['email_frequency'] . "' AND day_or_hour='" . $_POST['day_or_hour'] . "' "; 
                                $update_template_successfuly_pro = $wpdb->query( $query_update_new );
                            
                                $query_update_latest = "UPDATE `" . $wpdb->prefix . "ac_email_templates`
                                                        SET
                                                        from_email      = '" . $_POST['woocommerce_ac_email_from'] . "',
                                                        subject         = '" . $_POST['woocommerce_ac_email_subject'] . "',
                                                        body            = '" . $_POST['woocommerce_ac_email_body'] . "',
                                                        is_active       = '" . $active . "', frequency = '" . $_POST['email_frequency'] . "',
                                                        day_or_hour     = '" . $_POST['day_or_hour'] . "',
                                                        coupon_code     = '" . $coupon_code_id . "',
                                                        template_name   = '" . $_POST['woocommerce_ac_template_name'] . "',
                                                        from_name       = '" . $_POST['woocommerce_ac_from_name'] . "',
                                                        reply_email     = '" . $_POST['woocommerce_ac_email_reply'] . "',
                                                        generate_unique_coupon_code = '" . $unique_coupon . "',
                                                        is_wc_template  = '" . $is_wc_template . "',
                                                        wc_email_header = '" . $_POST['wcap_wc_email_header'] . "'
                                                        WHERE id        = '" . $_POST['id'] . "' ";
                            
                                $update_template_successfuly_pro = $wpdb->query( $query_update_latest );
                            }
                        } else {
                            $check_query   = "SELECT * FROM `" . $wpdb->prefix . "ac_email_templates` WHERE is_active='1' AND frequency= %d AND day_or_hour= %s";
                            $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $_POST['email_frequency'], $_POST['day_or_hour'] ) );
                       
                            $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates`
                                            SET
                                            from_email      = '" . $_POST['woocommerce_ac_email_from'] . "',
                                            subject         = '" . $_POST['woocommerce_ac_email_subject'] . "',
                                            body            = '" . $_POST['woocommerce_ac_email_body'] . "',
                                            is_active       = '" . $active . "', frequency = '" . $_POST['email_frequency'] . "',
                                            day_or_hour     = '" . $_POST['day_or_hour'] . "',
                                            coupon_code     = '" . $coupon_code_id . "',
                                            template_name   = '" . $_POST['woocommerce_ac_template_name'] . "',
                                            from_name       = '" . $_POST['woocommerce_ac_from_name'] . "',
                                            reply_email     = '" . $_POST['woocommerce_ac_email_reply'] . "',
                                            generate_unique_coupon_code = '" . $unique_coupon . "',
                                            is_wc_template  = '" . $is_wc_template . "',
                                            wc_email_header = '" . $_POST[ 'wcap_wc_email_header'] . "'
                                            WHERE id        = '" . $_POST['id'] . "' ";
                    
                            $update_template_successfuly_pro = $wpdb->query( $query_update );
                        }
                    }
                
                    if ( $action == 'emailtemplates' && $mode == 'removetemplate' ) {
                        $id_remove    = $_GET['id'];
                        $query_remove = "DELETE FROM `" . $wpdb->prefix . "ac_email_templates` WHERE id='" . $id_remove . "' ";
                        $wpdb->query( $query_remove );
                    }
                
                    if ( $action == 'emailtemplates' && $mode == 'activate_template' ) {
                        $template_id             = $_GET['id'];
                        $current_template_status = $_GET['active_state'];
                        
                        if( "1" == $current_template_status ) {
                            $active = "0";
                        } else {
                            $active = "1";
                        }
                        $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates`
                                SET
                                is_active       = '" . $active . "'
                                WHERE id        = '" . $template_id . "' ";
                        $wpdb->query( $query_update );
                        
                        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates' ) );
                    }
                
                    if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' && (isset($insert_template_successfuly_pro) && $insert_template_successfuly_pro != '')) { 
                    ?>
                        <div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully added.', 'woocommerce-ac' ); ?></strong></p></div>
                    <?php } else if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' && (isset($insert_template_successfuly_pro) && $insert_template_successfuly_pro == '')){
    			    ?>
    			        <div id="message" class="error fade"><p><strong><?php _e( ' There was a problem adding the email template. Please contact the plugin author via <a href= "https://www.tychesoftwares.com/forums/forum/woocommerce-abandon-cart-pro/">support forum</a>.', 'woocommerce-ac' ); ?></strong></p></div>
    				<?php   
    				}
    
                    if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update'&& isset($update_template_successfuly_pro) && $update_template_successfuly_pro != '' ) { 
                    ?>
                        <div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully updated.', 'woocommerce-ac' ); ?></strong></p></div>
                    <?php } else if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'update'  && isset($update_template_successfuly_pro) && $update_template_successfuly_pro == '' ){
    				?>
    				    <div id="message" class="error fade"><p><strong><?php _e( ' There was a problem updating the email template. Please contact the plugin author via <a href= "https://www.tychesoftwares.com/forums/forum/woocommerce-abandon-cart-pro/">support forum</a>.', 'woocommerce-ac' ); ?></strong></p></div>
    				<?php   
    				}
    				?>
                
                    <div class="tablenav">
                        <p style="float:left;">
                            <a cursor: pointer; href="<?php echo "admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate"; ?>" class="button-secondary"><?php _e( 'Add New Template', 'woocommerce-ac' ); ?></a>
                        </p>
                        <?php
                        /* From here you can do whatever you want with the data from the $result link. */
                        include_once('includes/classes/class-wcap-templates-table.php');
                        $wcap_template_list = new WCAP_Templates_Table();
                        $wcap_template_list->wcap_templates_prepare_items();
                        ?>
                        <div class="wrap">
                            <form id="wcap-abandoned-templates" method="get" >
                                <input type="hidden" name="page" value="woocommerce_ac_page" />
                                <input type="hidden" name="action" value="emailtemplates" />
                                <?php $wcap_template_list->display(); ?>
                            </form>
                        </div>
                    </div>
                    <?php 
                } 
                elseif ( 'stats' == $action || '' == $action ) {
                ?>
            
                    <script language='javascript'>
                        jQuery(document).ready(function() {
                            jQuery( '#duration_select' ).change( function() {
                                var group_name  = jQuery( '#duration_select' ).val();
                                var today       = new Date();
                                var start_date  = "";
                                var end_date    = "";
                                
                                if ( group_name == "yesterday" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 ); 
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
                                } else if ( group_name == "today" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_seven" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 7 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_fifteen" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 15 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_thirty" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 30 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_ninety" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 90 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_year_days" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 365 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                }
                                var monthNames       = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];   
                                var start_date_value = start_date.getDate() + " " + monthNames[ start_date.getMonth() ] + " " + start_date.getFullYear();
                                var end_date_value   = end_date.getDate() + " " + monthNames[ end_date.getMonth() ] + " " + end_date.getFullYear();
                                jQuery( '#start_date' ) . val( start_date_value );
                                jQuery( '#end_date' ) . val( end_date_value );
                            } 
                            );
                        } );
                    </script>
                    <?php
                
                    $duration_range = "";
                    
                    if ( isset( $_POST['duration_select'] ) ) {
                        $duration_range = $_POST['duration_select'];
                    }
                    
                    if ( '' == $duration_range && isset( $_GET['duration_select'] ) ) {
                        $duration_range = $_GET['duration_select'];
                    }
                    
                    if ( '' == $duration_range ) { 
                        $duration_range = "last_seven";
                    }                       
                    ?>
                    <p>
                        <?php _e( 'The Report below shows how many Abandoned Carts we were able to recover for you by sending automatic emails to encourage shoppers.', 'woocommerce-ac' )  ?> 
                    </p>
                    <div id="recovered_stats_date" class="postbox" style="display:block">                        
                        <div class="inside">
                            <form method="post" action="admin.php?page=woocommerce_ac_page&action=stats" id="ac_stats">                        
                                <select id="duration_select" name="duration_select" >
                                    <?php
                                    foreach ( $this->duration_range_select as $key => $value ) {
                                        $sel = "";
                                        if ( $key == $duration_range ) {
                                            $sel = __( " selected ", "woocommerce-ac" );
                                        } 
                                        echo"<option value='" . $key . "' $sel> " . __( $value,'woocommerce-ac' ) . " </option>";
                                    }
                                    $date_sett = $this->start_end_dates[ $duration_range ];                         
                                    ?>
                                </select>
                                                         
                                <script type="text/javascript">
                                    jQuery( document ).ready( function() {
                                        var formats = [ "d.m.y", "d M yy", "MM d, yy" ];
                                        jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
                                        jQuery( "#start_date" ).datepicker( { dateFormat: formats[1] } );
                                    } );
                                    jQuery( document ).ready( function() {
                                        var formats = [ "d.m.y", "d M yy","MM d, yy" ];
                                        jQuery( "#end_date" ).datepicker( { dateFormat: formats[1] } );
                                    } );
                                </script>                                                
                               
                                <?php 
                                
                                include_once('includes/classes/class-wcap-recover-orders-table.php');
                                
                                $wcap_recover_orders_list = new WCAP_Recover_Orders_Table();
                                $wcap_recover_orders_list->wcap_recovered_orders_prepare_items();
                                
                                $start_date_range = '';
                                if ( isset( $_POST['start_date'] ) ){
                                    $start_date_range = $_POST['start_date'];
                                }
                                if ( '' == $start_date_range ) {
                                    $start_date_range = $date_sett['start_date'];
                                }
                            
                                $end_date_range = '';
                                if ( isset( $_POST['end_date'] ) ) {
                                    $end_date_range = $_POST['end_date'];
                                }
                                
                                if ( '' == $end_date_range ) {
                                    $end_date_range = $date_sett['end_date'];
                                }
                                ?>                       
                                <label class="start_label" for="start_day"> <?php _e( 'Start Date:', 'woocommerce-ac' ); ?> </label>
                                <input type="text" id="start_date" name="start_date" readonly="readonly" value="<?php echo $start_date_range; ?>"/>     
                                
                                <label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-ac' ); ?> </label>
                                <input type="text" id="end_date" name="end_date" readonly="readonly" value="<?php echo $end_date_range; ?>"/>  
                                
                                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-ac' ); ?>"  />
                            </form>
                        </div>
                    </div>
                    <div id="recovered_stats" class="postbox" style="display:block">
                        <div class="inside" >
                            <p style="font-size: 15px"><?php  _e( 'During the selected range ', 'woocommerce-ac' ); ?>
                                <strong>
                                    <?php $count = $wcap_recover_orders_list->total_abandoned_cart_count; 
                                          echo $count; ?> 
                                </strong>
                                <?php _e( 'carts totaling', 'woocommerce-ac' ); ?> 
                                <strong> 
                                    <?php $total_of_all_order = $wcap_recover_orders_list->total_order_amount; 
                                           
                                    echo $total_of_all_order; ?>
                                 </strong>
                                 <?php _e( ' were abandoned. We were able to recover', 'woocommerce-ac' ); ?> 
                                 <strong>
                                    <?php 
                                    $recovered_item = $wcap_recover_orders_list->recovered_item;
                                    
                                    echo $recovered_item; ?>
                                 </strong>
                                 <?php _e( ' of them, which led to an extra', 'woocommerce-ac' ); ?> 
                                 <strong>
                                    <?php 
                                        $recovered_total = $wcap_recover_orders_list->total_recover_amount;
                                        echo wc_price ( $recovered_total ); ?>
                                 </strong>
                                 <?php //_e( ' in sales', 'woocommerce-ac' ); ?>
                             </p>
                        </div>
                    </div>
                
                    <div class="wrap">
                        <form id="wcap-recover-orders" method="get" >
                            <input type="hidden" name="page" value="woocommerce_ac_page" />
                            <input type="hidden" name="action" value="stats" />
                            <?php $wcap_recover_orders_list->display(); ?>
                        </form>
                    </div>
                <?php
                } elseif ( 'emailstats' == $action ) {
                ?>                        
                    
                    <script language='javascript'>
                        jQuery( document ).ready( function() {
                            jQuery( '#duration_select_email' ).change( function() {
                                var group_name = jQuery( '#duration_select_email' ) . val();
                                var today      = new Date();
                                var start_date = "";
                                var end_date   = "";
                                
                                if ( group_name == "yesterday" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
                                    end_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
                                } else if ( group_name == "today" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_seven" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 7 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_fifteen" ) {
                                    start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 15 );
                                    end_date   = new Date(today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_thirty") {
                                    start_date = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 30 );
                                    end_date   = new Date(today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_ninety" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 90 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                } else if ( group_name == "last_year_days" ) {
                                    start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 365 );
                                    end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                }
                                
                                var monthNames       = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];                                    
                                var start_date_value = start_date.getDate() + " " + monthNames[start_date.getMonth()] + " " + start_date.getFullYear();
                                var end_date_value   = end_date.getDate() + " " + monthNames[end_date.getMonth()] + " " + end_date.getFullYear();
                                jQuery( '#start_date_email' ).val( start_date_value );
                                jQuery( '#end_date_email' ).val( end_date_value );
                            } );
                        } );
                    </script>                      
                    <?php
                
                    include_once('includes/classes/class-wcap-sent-emails-table.php');
                    $wcap_sent_emails_list = new WCAP_Sent_Emails_Table();
                    $wcap_sent_emails_list->wcap_sent_emails_prepare_items();
                     
                    $duration_range = '';
                    if ( isset( $_POST['duration_select_email'] ) ){
                        $duration_range = $_POST['duration_select_email'];
                    }
                    
                    if ( '' == $duration_range ) {
                        if ( isset( $_GET['duration_select_email'] ) ) {
                            $duration_range = $_GET['duration_select_email'];
                        }
                    }
                    
                    if ( '' == $duration_range ){
                        $duration_range = "last_seven";
                    }
                    
                    
                    if ( isset($_SESSION ['duration'] ) ){
                        $duration_range = $_SESSION ['duration'];
                    }
                    
                    ?>                        
                    <p> 
                        <?php _e( 'The Report below shows emails sent, emails opened and other related stats for the selected date range', 'woocommerce-ac' );?> 
                    </p>
                    <div id="email_stats" class="postbox" style="display:block">
                        <div class="inside">
                            <form method="post" action="admin.php?page=woocommerce_ac_page&action=emailstats" id="ac_email_stats">
                                <select id="duration_select_email" name="duration_select_email" >
                                
                                <?php
                                foreach ( $this->duration_range_select as $key => $value ) {
                                    $sel = "";
                                    if ( $key == $duration_range ) {
                                        $sel = __( " selected ", "woocommerce-ac" );
                                    }
                                    echo"<option value='$key' $sel> $value </option>";
                                }
                                $date_sett = $this->start_end_dates[$duration_range];
                                ?>
                                </select>                       
                                <script type="text/javascript">
                                    jQuery( document ).ready( function() {
                                        var formats = [ "d.m.y", "d M yy", "MM d, yy" ];
                                        jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "en-GB" ] );
                                        jQuery( "#start_date_email" ).datepicker( {
                                            dateFormat: formats[1] } );
                                    } );                       
                                    jQuery( document ).ready( function() {
                                        var formats = [ "d.m.y", "d M yy", "MM d, yy" ];
                                        jQuery( "#end_date_email" ).datepicker( {
                                            dateFormat: formats[1] } );
                                    } );
                                </script>  
                            
                                <?php                   
                                $start_date_range = '';
                                if ( isset( $_POST['start_date_email'] ) ){
                                    $start_date_range = $_POST['start_date_email'];
                                }
                                
                                if ( isset( $_SESSION ['start_date'] ) ){
                                    $start_date_range = $_SESSION ['start_date'];
                                }
                                
                                if ( '' == $start_date_range ) {
                                    $start_date_range = $date_sett['start_date'];
                                }
                                
                                $end_date_range = '';
                                if ( isset( $_POST['end_date_email'] ) ) {
                                    $end_date_range = $_POST['end_date_email'];
                                }
                                
                                if (isset($_SESSION ['end_date'])){
                                
                                    $end_date_range = $_SESSION ['end_date'];
                                }
                                
                                if ( '' == $end_date_range ) {
                                    $end_date_range = $date_sett['end_date'];
                                }
                                ?>                                    
                                <label class="start_label" for="start_day"> 
                                    <?php _e( 'Start Date:', 'woocommerce-ac' ); ?> 
                                </label>
                                <input type="text" id="start_date_email" name="start_date_email" readonly="readonly" value="<?php echo $start_date_range; ?>" />
                                <label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-ac' ); ?> </label>
                                <input type="text" id="end_date_email" name="end_date_email" readonly="readonly" value="<?php echo $end_date_range; ?>" />
                                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-ac' ); ?>"  />
                            </form>
                        </div>
                    </div>
                 
                    <div id="email_sent_stats" class="postbox" style="display:block">
                        <table class='wp-list-table widefat fixed posts' cellspacing='0' id='cart_data_sent' style="font-size : 15px">
                            <tr>
                                <td> 
                                    <p style="font-size : 15px"> <?php _e( 'Emails Sent :', 'woocommerce-ac' ); ?> 
                                        <?php echo $wcap_sent_emails_list->total_count; ?> 
                                    </p> 
                                </td>
                                <td> 
                                    <p style="font-size : 15px"> <?php _e( 'Emails Opened :', 'woocommerce-ac' ); ?> 
                                        <?php echo $wcap_sent_emails_list->open_emails; ?> 
                                    </p> 
                                </td>
                                <td> 
                                    <p style="font-size : 15px"> <?php _e( 'Links Clicked :', 'woocommerce-ac' ); ?> 
                                        <?php echo $wcap_sent_emails_list->link_click_count;  ?> 
                                    </p> 
                                </td>
                            </tr>
                        </table>
                    </div>                        
                
                    <div class="wrap">
                        <form id="wcap-sent-emails" method="get" >
                            <input type="hidden" name="page" value="woocommerce_ac_page" />
                            <input type="hidden" name="action" value="emailstats" />
                                <?php $wcap_sent_emails_list->display(); ?>
                        </form>
                    </div>
                <?php                             
                } elseif ( 'listcart' == $action && 'orderdetails' == $_GET['action_details']) {
                
                    $ac_order_id = $_GET['id'];
                    ?>
                        
                    <div id="ac_order_details" class="postbox" style="display:block">
                        
                        <div class="inside">
                            <table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed posts">
								<h3>
                                    <p> 
                                        <?php _e( "Abandoned Order #$ac_order_id Details", "woocommerce-ac" ); ?> 
                                    </p> 
                                </h3>
                                <tr>
                                    <th> 
                                        <?php _e( 'Item', 'woocommerce-ac' ); ?> 
                                    </th>
                                    <th> 
                                        <?php _e( 'Name', 'woocommerce-ac' ); ?> 
                                    </th>
                                    <th> 
                                        <?php _e( 'Quantity', 'woocommerce-ac' ); ?> 
                                    </th>
                                    <th> 
                                        <?php _e( 'Line Subtotal', 'woocommerce-ac' ); ?> 
                                    </th>
                                    <th> 
                                        <?php _e( 'Line Total', 'woocommerce-ac' ); ?> 
                                    </th>
                                </tr>                                           
                                <?php 
                                $query                   = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE id = %d ";
                                $results                 = $wpdb->get_results( $wpdb->prepare( $query,$_GET['id'] ) );                         
                                $shipping_charges        = 0;
                                $currency_symbol         = get_woocommerce_currency_symbol();
                                $biiling_field_display   = $email_field_display = $phone_field_display = $shipping_field_display = "block";
                                $user_ip_address         = '';
                                $user_ip_address_display = 'none';
                                if ( isset( $results[0]->ip_address ) && '' != $results[0]->ip_address ){
                                    $user_ip_address = $results[0]->ip_address;
                                    $user_ip_address_display = 'block';
                                }
                                if ( "GUEST" == $results[0]->user_type && "0" != $results[0]->user_id ) {
                                    
                                    $query_guest            = "SELECT * FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = %d";  
                                    $results_guest          = $wpdb->get_results( $wpdb->prepare( $query_guest, $results[0]->user_id ) );
                                    $user_email             = $results_guest[0]->email_id;
                                    $user_first_name        = $results_guest[0]->billing_first_name;
                                    $user_last_name         = $results_guest[0]->billing_last_name;
                                    $user_billing_postcode  = $results_guest[0]->billing_zipcode;
                                    $user_shipping_postcode = $results_guest[0]->shipping_zipcode;
                                    $shipping_charges       = $results_guest[0]->shipping_charges;
                                    $user_billing_phone     = $results_guest[0]->phone;
                                    $user_billing_company   = $user_billing_address_1 = $user_billing_address_2 = $user_billing_city = $user_billing_state = $user_billing_country  = "";
                                    $user_shipping_company  = $user_shipping_address_1 = $user_shipping_address_2 = $user_shipping_city = $user_shipping_state = $user_shipping_country = "";
                                
                                    $biiling_field_display = $shipping_field_display = "none";
                                    
                                    if ( isset($user_billing_phone) && $user_billing_phone == ''){
                                        $phone_field_display = "none";
                                    }
                                    
                                }else if ( $results[0]->user_type == "GUEST" && $results[0]->user_id == "0"  ) { 
                                    $user_email             = '';
                                    $user_first_name        = "Visitor";
                                    $user_last_name         = "";
                                    $user_billing_postcode  = '';
                                    $user_shipping_postcode = '';
                                    $shipping_charges       = '';
                                    $user_billing_phone     = '';
                                    $user_billing_company   = $user_billing_address_1 = $user_billing_address_2 = $user_billing_city = $user_billing_state = $user_billing_country  = "";
                                    $user_shipping_company  = $user_shipping_address_1 = $user_shipping_address_2 = $user_shipping_city = $user_shipping_state = $user_shipping_country = "";
                                    
                                    $biiling_field_display = $email_field_display = $phone_field_display = $shipping_field_display = "none";
                                }else {
                                    $user_id = $results[0]->user_id;                                
                                    if ( isset( $results[0]->user_login ) ) $user_login = $results[0]->user_login;
                                    $user_email = get_user_meta( $results[0]->user_id, 'billing_email', true );
                                    
                                    if($user_email == ""){  
                                        $user_data = get_userdata( $results[0]->user_id ); 
                                        $user_email = $user_data->user_email;   
                                    }
                                    
                                    $user_first_name = "";
                                    $user_first_name_temp = get_user_meta( $results[0]->user_id, 'first_name' );
                                    if ( isset( $user_first_name_temp[0] ) ) {
                                        $user_first_name = $user_first_name_temp[0];
                                    }
                                    
                                    $user_last_name = "";
                                    $user_last_name_temp = get_user_meta( $results[0]->user_id, 'last_name' );
                                    if ( isset( $user_last_name_temp[0] ) ) {
                                        $user_last_name = $user_last_name_temp[0];
                                    }
                                    
                                    $user_billing_first_name = get_user_meta( $results[0]->user_id, 'billing_first_name' );
                                    $user_billing_last_name  = get_user_meta( $results[0]->user_id, 'billing_last_name' );
                                    
                                    $user_billing_company_temp = get_user_meta( $results[0]->user_id, 'billing_company' );
                                    
                                    $user_billing_company = "";
                                    if ( isset( $user_billing_company_temp[0] ) ){
                                        $user_billing_company = $user_billing_company_temp[0];
                                    }
                                    
                                    $user_billing_address_1_temp = get_user_meta( $results[0]->user_id, 'billing_address_1' );
                                    $user_billing_address_1 = "";
                                    if ( isset( $user_billing_address_1_temp[0] ) ) {
                                        $user_billing_address_1 = $user_billing_address_1_temp[0];
                                    }
                                    
                                    $user_billing_address_2_temp = get_user_meta( $results[0]->user_id, 'billing_address_2' );
                                    $user_billing_address_2 = "";
                                    if ( isset( $user_billing_address_2_temp[0] ) ) {
                                        $user_billing_address_2 = $user_billing_address_2_temp[0];
                                    }
                                    
                                    $user_billing_city_temp = get_user_meta( $results[0]->user_id, 'billing_city' );
                                    $user_billing_city = "";
                                    if ( isset( $user_billing_city_temp[0] ) ) {
                                        $user_billing_city = $user_billing_city_temp[0];
                                    }
                                    
                                    $user_billing_postcode_temp = get_user_meta( $results[0]->user_id, 'billing_postcode' );
                                    $user_billing_postcode = "";
                                    if ( isset( $user_billing_postcode_temp[0] ) ) {
                                        $user_billing_postcode = $user_billing_postcode_temp[0];
                                    }
                                    
                                    $user_billing_state_temp = get_user_meta( $results[0]->user_id, 'billing_state' );
                                    $user_billing_state = "";
                                    if ( isset( $user_billing_state_temp[0] ) ){
                                        $user_billing_state = $user_billing_state_temp[0];
                                    }
                                    
                                    $user_billing_country_temp = get_user_meta( $results[0]->user_id, 'billing_country' );
                                    $user_billing_country = "";
                                    if ( isset( $user_billing_country_temp[0] ) ){
                                        $user_billing_country = $user_billing_country_temp[0];
                                    }
                                    
                                    $user_billing_phone_temp = get_user_meta( $results[0]->user_id, 'billing_phone' );
                                    $user_billing_phone = "";
                                    if ( isset( $user_billing_phone_temp[0] ) ){
                                        $user_billing_phone = $user_billing_phone_temp[0];
                                    }
                                    
                                    $user_shipping_first_name = get_user_meta( $results[0]->user_id, 'shipping_first_name' );
                                    $user_shipping_last_name  = get_user_meta( $results[0]->user_id, 'shipping_last_name' );
                                    
                                    $user_shipping_company_temp = get_user_meta( $results[0]->user_id, 'shipping_company' );
                                    $user_shipping_company = "";
                                    if ( isset( $user_shipping_company_temp[0] ) ) { 
                                        $user_shipping_company = $user_shipping_company_temp[0];
                                    }
                                    
                                    $user_shipping_address_1_temp = get_user_meta( $results[0]->user_id, 'shipping_address_1' );
                                    $user_shipping_address_1 = "";
                                    if ( isset( $user_shipping_address_1_temp[0] ) ) {
                                        $user_shipping_address_1 = $user_shipping_address_1_temp[0];
                                    }
                                    
                                    $user_shipping_address_2_temp = get_user_meta( $results[0]->user_id, 'shipping_address_2' );
                                    $user_shipping_address_2 = "";
                                    if ( isset( $user_shipping_address_2_temp[0] ) ) {
                                        $user_shipping_address_2 = $user_shipping_address_2_temp[0];
                                    }
                                    
                                    $user_shipping_city_temp = get_user_meta( $results[0]->user_id, 'shipping_city' );
                                    $user_shipping_city = "";
                                    if ( isset( $user_shipping_city_temp[0] ) ){
                                        $user_shipping_city = $user_shipping_city_temp[0];
                                    }
                                    
                                    $user_shipping_postcode_temp = get_user_meta( $results[0]->user_id, 'shipping_postcode' );
                                    $user_shipping_postcode = "";
                                    if ( isset( $user_shipping_postcode_temp[0] ) ) {
                                        $user_shipping_postcode = $user_shipping_postcode_temp[0];
                                    }
                                    
                                    $user_shipping_state_temp = get_user_meta( $results[0]->user_id, 'shipping_state' );
                                    $user_shipping_state = "";
                                    if ( isset( $user_shipping_state_temp[0] ) ){
                                        $user_shipping_state = $user_shipping_state_temp[0];
                                    }
                                    
                                    $user_shipping_country_temp = get_user_meta( $results[0]->user_id, 'shipping_country' );
                                    $user_shipping_country = "";
                                    if ( isset( $user_shipping_country_temp[0] ) ) {
                                        $user_shipping_country = $user_shipping_country_temp[0];
                                    }
                                }                            
                                $cart_info    = json_decode( $results[0]->abandoned_cart_info );
                                $cart_details = array();
                                
                                if( !empty( $cart_info ) ){
                                    $cart_details   = $cart_info->cart;
                                }
                                $item_subtotal  = $item_total = 0;
                                
                                foreach( $cart_details as $k => $v ) {
                                    $quantity_total = $v->quantity;
                                    $product_id     = $v->product_id;
                                    $prod_name      = get_post($product_id);
                                    $product_name   = '';
                                    if ( NULL != $prod_name || '' != $prod_name ) {
                                        $product_name   = $prod_name->post_title;
                                        if( isset( $v->variation_id ) && '' != $v->variation_id ){
                                            $variation_id               = $v->variation_id;
                                            $variation                  = wc_get_product( $variation_id );
                                            $name                       = $variation->get_formatted_name() ;
                                            $explode_all                = explode ( "&ndash;", $name );
                                            $pro_name_variation         = array_slice( $explode_all, 1, -1 );
                                            $product_name_with_variable = '';
                                            $explode_many_varaition     = array();
                                             
                                            foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ){
                                                $explode_many_varaition = explode ( ",", $pro_name_variation_value );
                                                if ( !empty( $explode_many_varaition ) ) {
                                                    foreach( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ){
                                                        $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                                                    }
                                                } else {
                                                    $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                                                }
                                            }
                                            $product_name = $product_name_with_variable;
                                        }
                                    }
                                    // Item subtotal is calculated as product total including taxes
                                    if ( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                                        $item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
                                    } else {
                                        $item_subtotal = $item_subtotal + $v->line_total;
                                    }
                                
                                    //  Line total
                                    $item_total    = $item_subtotal;
                                    $item_subtotal = $item_subtotal / $quantity_total;
                                    $item_total    = wc_price( $item_total );
                                    $item_subtotal = wc_price( $item_subtotal );                               
                                    $product       = get_product( $product_id );
                                    if ( NULL != $product || '' != $product ) {
                                        $prod_image = $product->get_image();
                                    }else{
                                        $prod_image = ''; 
                                    }
                                    ?>                   
                                    <tr>
                                        <td> 
                                            <?php echo $prod_image; ?>
                                        </td>
                                        <td> 
                                            <?php echo $product_name == '' ? 'Product no longer exists in store.': $product_name ; ?>
                                        </td>
                                        <td> 
                                            <?php echo $product_name == '' ? '': $quantity_total; ?>
                                        </td>
                                        <td>
                                            <?php echo $product_name == '' ? '': $item_subtotal; ?>
                                        </td>
                                        <td>
                                            <?php echo $product_name == '' ? '': $item_total; ?>
                                        </td>
                                    </tr>
                                    <?php 
                                    $item_subtotal = $item_total = 0;
                                }
                                ?>
                            </table>
                        </div>  
                    </div>
                    <div id="ac_order_customer_details" class="postbox" style="display:block">
                        
                        
                        <div class="inside" style="overflow: auto;, width: 100%;" >                                       
                            <div id="order_data" class="panel">
                                <div style="width:50%;float:left">
									<h3>
                                        <p> 
                                            <?php _e( 'Customer Details' , 'woocommerce-ac' ); ?> 
                                        </p> 
                                    </h3>
                                    <h3>
                                        <p> 
                                            <?php _e( 'Billing Details' , 'woocommerce-ac' ); ?> 
                                        </p> 
                                    </h3>
                                    <p> 
                                        <strong> 
                                            <?php _e( 'Name:' , 'woocommerce-ac' ); ?> 
                                        </strong>
                                        <?php echo $user_first_name . " " . $user_last_name; ?>
                                    </p>                                    
                                    <p style = "display:<?php echo $biiling_field_display; ?>;"> 
                                        <strong> 
                                            <?php _e( 'Address:' , 'woocommerce-ac' ); ?> 
                                        </strong>
                                    <?php echo $user_billing_company   . "</br>" .
                                               $user_billing_address_1 . "</br>" .
                                               $user_billing_address_2 . "</br>" .
                                               $user_billing_city      . "</br>" .
                                               $user_billing_postcode  . "</br>" .
                                               $user_billing_state     . "</br>" .
                                               $user_billing_country   . "</br>";
                                     ?> 
                                    </p>                                        
                                    <p style = "display:<?php echo $email_field_display; ?>;"> 
                                        <strong> 
                                            <?php _e( 'Email:', 'woocommerce-ac' ); ?> 
                                        </strong>
                                        <a href='mailto:$user_email'><?php echo $user_email; ?> </a>
                                    </p>                                            
                                    <p style = "display:<?php echo $phone_field_display; ?>;"> 
                                        <strong> 
                                            <?php _e( 'Phone:', 'woocommerce-ac' ); ?> 
                                        </strong>
                                        <?php echo $user_billing_phone; ?>
                                    </p>
									<br>
									<p style = "display:<?php echo $user_ip_address_display; ?>;" > 
                                        <strong> 
                                            <?php _e( 'IP Address:' , 'woocommerce-ac' ); ?> 
                                        </strong>
                                        <?php echo $user_ip_address; ?>
                                    </p>
                                    
                                </div>                                                                                   
                                <div style="width:50%;float:right; display:<?php echo $shipping_field_display; ?>; ">
                                    <h3> 
                                        <p > 
                                            <?php _e( 'Shipping Details', 'woocommerce-ac' ); ?> 
                                        </p> 
                                    </h3>                                       
                                    <p> 
                                        <strong> 
                                            <?php _e( 'Address:', 'woocommerce-ac' ); ?> 
                                        </strong>
                                        <?php 
                                        if ( $user_shipping_company     == '' &&
                                             $user_shipping_address_1   == '' &&
                                             $user_shipping_address_2   == '' &&
                                             $user_shipping_city        == '' &&
                                             $user_shipping_postcode    == '' &&
                                             $user_shipping_state       == '' &&
                                             $user_shipping_country     == '' ) {
                                                 
                                                echo "Shipping Address same as Billing Address";
                                            } else { ?>                                
                                        <?php echo $user_shipping_company . "</br>" .
                                               $user_shipping_address_1   . "</br>" .
                                               $user_shipping_address_2   . "</br>" .
                                               $user_shipping_city        . "</br>" .
                                               $user_shipping_postcode    . "</br>" .
                                               $user_shipping_state       . "</br>" .
                                               $user_shipping_country     . "</br>";
                                           ?> 
                                       <br> 
                                       <br>
                                       <strong> Shipping Charges: </strong>
                                       <?php if ( $shipping_charges != 0 ) echo $currency_symbol . $shipping_charges; ?>
                                    </p>
                                    <?php } ?>                            
                                </div>
                            </div>
                        </div>
                    </div>                
                <?php 
                }
                        
                if ( isset( $_GET['action'] ) ) {
                    $action = $_GET['action'];
                }
                if ( isset( $_GET['mode'] ) ) {
                    $mode = $_GET['mode'];
                }
            
                if ( 'emailtemplates' == $action && ( 'addnewtemplate' == $mode || 'edittemplate' == $mode || 'copytemplate' == $mode ) ) {
                    
                    if( 'edittemplate' == $mode ) {
                        $edit_id = $_GET['id'];
                        $query = "SELECT wpet . *  FROM `" . $wpdb->prefix . "ac_email_templates` AS wpet WHERE id= %d";
                        $results = $wpdb->get_results( $wpdb->prepare( $query,  $edit_id ) );
                    }
                    
                    if( 'copytemplate' == $mode ) {
                        $copy_id        = $_GET['id'];
                        $query_copy     = "SELECT wpet . *  FROM `" . $wpdb->prefix . "ac_email_templates` AS wpet WHERE id= %d";
                        $results_copy   = $wpdb->get_results( $wpdb->prepare( $query_copy,$copy_id ) );
                    }
                    $active_post = ( empty( $_POST['is_active'] ) ) ? '0' : '1';                       
                    ?>
    				<?php if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' ) { ?>
                        <div id="message" class="updated fade">
                            <p>
                                <strong>
                                    <?php _e( 'Your settings have been saved.', 'woocommerce-ac' ); ?>
                                </strong>
                            </p>
                        </div>
    					<?php } ?>
                        <div id="content">
                            <form method="post" action="admin.php?page=woocommerce_ac_page&action=emailtemplates" id="ac_settings">                            
                                <input type="hidden" name="mode" value="<?php echo $mode; ?>" />
                                <input type="hidden" name="id" value="<?php if( isset( $_GET['id'] ) ) echo $_GET['id']; ?>" />                           
                                <?php
                                $button_mode = "save";
                                $display_message = "Add Email Template";
    
                                if ( 'edittemplate' == $mode ) {
                                    $button_mode     = "update";
                                    $display_message = "Edit Email Template";
                                }
                                print'<input type="hidden" name="ac_settings_frm" value="'.$button_mode.'">'; ?>
                                    <div id="poststuff">
                                        <div> <!-- <div class="postbox" > -->
                                            <h3 class="hndle">
                                                <?php _e( $display_message, 'woocommerce-ac' ); ?>
                                            </h3>
                                            <div>
                                                <table class="form-table" id="addedit_template">                                                
                                                    <tr>
                                                        <th>
                                                            <label for="woocommerce_ac_template_name">
                                                                <b>
                                                                    <?php _e( 'Template Name:', 'woocommerce-ac' );?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                            $template_name = "";
                                                            if( 'edittemplate' == $mode ) {
                                                                $template_name = $results[0]->template_name;
                                                            }
        
                                                            if( 'copytemplate' == $mode ) {
                                                                $template_name = "Copy of ".$results_copy[0]->template_name;
                                                            }
                                                            print'<input type="text" name="woocommerce_ac_template_name" id="woocommerce_ac_template_name" class="regular-text" value="' . $template_name . '">'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter a template name for reference' , 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                                        </td> 
                                                    </tr>
                                                    <tr>
                                                        <th>
                                                            <label for="woocommerce_ac_email_frequency">
                                                                <b>
                                                                    <?php _e( 'Send this email:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>                                                 
                                                            <select name="email_frequency" id="email_frequency">
                                                        
                                                            <?php
                                                                $frequency_edit="";
                                                                
                                                                if( 'edittemplate' == $mode ) {
                                                                    $frequency_edit=$results[0]->frequency;
                                                                }
                                                                
                                                                if( 'copytemplate' == $mode ) {
                                                                    $frequency_edit=$results_copy[0]->frequency;
                                                                }
                                                            
                                                                for ( $i=1;$i<60;$i++ ) {
                                                                    printf( "<option %s value='%s'>%s</option>\n",
                                                                        selected( $i, $frequency_edit, false ),
                                                                        esc_attr( $i ),
                                                                        $i
                                                                    );
                                                                }
                                                            ?>
                                                            </select>          
                                                            <select name="day_or_hour" id="day_or_hour">
                                                            <?php
                                                                $days_or_hours_edit = "";
                                                                
                                                                if ( 'edittemplate' == $mode ) {
                                                                    $days_or_hours_edit=$results[0]->day_or_hour;
                                                                }
        
                                                                if ( 'copytemplate' == $mode ) {
                                                                    $days_or_hours_edit=$results_copy[0]->day_or_hour;
                                                                }
                                                                $days_or_hours = array(
                                                                       'Minutes'    => 'Minute(s)',
                                                                       'Days'       => 'Day(s)',
                                                                       'Hours'      => 'Hour(s)'
                                                                    );
        
                                                                foreach( $days_or_hours as $k => $v ) {
                                                                    printf( "<option %s value='%s'>%s</option>\n",
                                                                        selected( $k, $days_or_hours_edit, false ),
                                                                        esc_attr( $k ),
                                                                        $v
                                                                    );
                                                                }
                                                            ?>
                                                            </select>
                                                            <span class="description"><?php
                                                                echo __( 'after cart is abandoned.', 'woocommerce-ac' );
                                                            ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                       <th>
                                                            <label for="woocommerce_ac_from_name">
                                                                <b>
                                                                    <?php _e( 'Send From This Name:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                            $from_name = "Admin";
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $from_name=$results[0]->from_name;
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $from_name=$results_copy[0]->from_name;
                                                            }
                                                            print'<input type="text" name="woocommerce_ac_from_name" id="woocommerce_ac_from_name" class="regular-text" value="' . $from_name . '">'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the name that should appear in the email sent', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                        </td>
                                                    </tr>                                               
                                                    <tr>
                                                       <th>
                                                            <label for="woocommerce_ac_email_from">
                                                                <b>
                                                                    <?php _e( 'Send From This Email Address:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                            $from_edit = get_option( 'admin_email' );
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $from_edit=$results[0]->from_email;
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $from_edit=$results_copy[0]->from_email;
                                                            }
                                                            print'<input type="text" name="woocommerce_ac_email_from" id="woocommerce_ac_email_from" class="regular-text" value="' . $from_edit . '">'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Which email address should be shown in the "From Email" field for this email?', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                        </td>
                                                    </tr>                                                
                                                    <tr>
                                                        <th>
                                                            <label for="woocommerce_ac_email_reply">
                                                                <b>
                                                                    <?php _e( 'Send Reply Emails to:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                            $reply_edit = get_option( 'admin_email' );
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $reply_edit=$results[0]->reply_email;
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $reply_edit=$results_copy[0]->reply_email;
                                                            }
                                                            print'<input type="text" name="woocommerce_ac_email_reply" id="woocommerce_ac_email_reply" class="regular-text" value="' . $reply_edit . '">'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'When a contact receives your email and clicks reply, which email address should that reply be sent to?', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                        <?php ?>
                                                        </td>
                                                    </tr>            
                                                    <tr>
                                                       <th>
                                                            <label for="woocommerce_ac_email_subject">
                                                                <b>
                                                                    <?php _e( 'Subject:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
    
                                                        <?php
                                                            $subject_edit="";
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $subject_edit=$results[0]->subject;
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $subject_edit=$results_copy[0]->subject;
                                                            }
                                                            print'<input type="text" name="woocommerce_ac_email_subject" id="woocommerce_ac_email_subject" class="regular-text" value="' . $subject_edit . '">'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the subject that should appear in the email sent', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                            Add the shortcode {{customer.firstname}} or {{product.name}} to include the Customer First Name and Product name (first in the cart) to the Subject Line.
                                                           	For e.g. Hi John!! You left some Protein Bread in your cart. 
                                                       	<?php ?>
                                                        </td>
                                                    </tr>            
                                                    <tr>
                                                        <th>
                                                            <label for="woocommerce_ac_email_body">
                                                                <b>
                                                                    <?php _e( 'Email Body:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                
                                                        <?php
                                                            $initial_data = "";
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $initial_data = $results[0]->body;
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $initial_data = $results_copy[0]->body;
                                                            }
                                                            
                                                            $initial_data = str_replace ( "My document title", "", $initial_data );
                                                            	
                                                            wp_editor(
                                                                $initial_data,
                                                                'woocommerce_ac_email_body',
                                                                array(
                                                                'media_buttons' => true,
                                                                'textarea_rows' => 15,
                                                                'tabindex' => 4,
                                                                'tinymce' => array(
                                                                    'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
        							                             ),
                                                                )
                                                            );
                                                        ?>
                                                            <span class="description">
                                                            <?php
                                                                echo __( 'Message to be sent in the reminder email.', 'woocommerce-ac' );
                                                            ?>
                                                                <img width="16" height="16" src="<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/information.png" onClick="bkap_show_help_tips()"/>
                                                            </span>
                                                            <span id="help_message" style="display:none">
                                                                1. You can add customer & cart information in the template using this icon <img width="20" height="20" src="<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/ac_editor_icon.png" /> in top left of the editor.<br>
                                                                2. You can now customize the product information/cart contents table that is added when using the {{products.cart}} merge field.<br>
            													3. Add/Remove columns from the default table by selecting the column and clicking on the Remove Column Icon in the editor.<br>
            													4. Insert/Remove any of the new shortcodes that have been included for the product table.<br>
            													5. Change the look and feel of the table by modifying the table style properties using the Edit Table Icon in the editor. <br>
            													6. Change the background color of the table rows by using the Edit Table Row Icon in the editor. <br>
            													7. Use any of icons for the table in the editor to stylize the table as per your requirements.<img width="180" height="20" src="<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/tmce_table_editor.png" /> 
                                                            </span>
                                                        </td>
                                                    </tr>  
                                                    <script type="text/javascript">
                                                        function bkap_show_help_tips() {
                                                      	    if( jQuery( '#help_message' ) . css( 'display' ) == 'none') {
                                                        	    document.getElementById( "help_message" ).style.display = "block";
                                                      		}
                                                            else {
                                                                document.getElementById( "help_message" ) . style.display = "none";
                                                        	}
                                                        } 
                                                    </script>  
                                                
                                                    <tr>
                                                        <th>
                                                            <label for="is_wc_template">
                                                                <b>
                                                                    <?php _e( 'Use WooCommerce Template Style:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
    
                                                        <?php
                                                            $is_wc_template="";
                                                            if ( 'edittemplate' == $mode ) {
                                                                $use_wc_template = $results[0]->is_wc_template;
                                                                $is_wc_template = "";
                                                                if ( $use_wc_template == '1' ) {
                                                                    $is_wc_template = "checked";
                                                                }
                                                            }
        
                                                            if ( $mode == 'copytemplate' ) {
                                                                $use_wc_template = $results_copy[0]->generate_unique_coupon_code;
                                                                $is_wc_template = "";
                                                                if( '1' == $use_wc_template ) {
                                                                    $is_wc_template = "checked";
                                                                }
                                                            }
                                                            print'<input type="checkbox" name="is_wc_template" id="is_wc_template" ' . $is_wc_template . '>  </input>'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /> <a target = '_blank' href= <?php  echo wp_nonce_url( admin_url( '?wcap_preview_woocommerce_mail=true' ), 'woocommerce-ac' ) ; ?> > 
                                                            Click here to preview </a>how the email template will look with WooCommerce Template Style enabled. Alternatively, if this is unchecked, the template will appear as <a target = '_blank' href=<?php  echo wp_nonce_url( admin_url( '?wcap_preview_mail=true' ), 'woocommerce-ac' ) ; ?>>shown here</a>.  <br> <strong>Note: </strong>When this setting is enabled, then "Send From This Name:" & "Send From This Email Address:" will be overwritten with WooCommerce -> Settings -> Email -> Email Sender Options.
                                                        </td>
                                                    </tr> 
                                                
                                                    <tr>
                                                        <th>
                                                            <label for="wcap_wc_email_header">
                                                                <b>
                                                                    <?php _e( 'Email Template Header Text: ', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                        
                                                            $wcap_wc_email_header = "";  
                                                            if ( 'edittemplate' == $mode ) {
                                                                $wcap_wc_email_header = $results[0]->wc_email_header;
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $wcap_wc_email_header = $results_copy[0]->wc_email_header;
                                                            }
                                                            
                                                            if ( "" == $wcap_wc_email_header ){
                                                                $wcap_wc_email_header = "Abandoned cart reminder";
                                                            }
                                                            print'<input type="text" name="wcap_wc_email_header" id="wcap_wc_email_header" class="regular-text" value="' . $wcap_wc_email_header . '">'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-ac' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                        </td>
                                                    </tr>  
                                                
                                                    <tr>
                                                        <th>
                                                            <label for="is_active">
                                                                <b>
                                                                    <?php _e( 'Active:', 'woocommerce-ac' );  ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                            $is_active_edit="";
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $active_edit = $results[0]->is_active;
                                                                $is_active_edit = "";
                                                                if ( $active_edit == '1' ) {
                                                                    $is_active_edit = "checked";
                                                                }
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $active_edit = $results_copy[0]->is_active;
                                                                $is_active_edit = "";
                                                                if($active_edit == '1') {
                                                                    $is_active_edit = "checked";
                                                                }
                                                            }
                                                            print'<input type="checkbox" name="is_active" id="is_active" ' . $is_active_edit . '>  </input>'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Yes, This email should be sent to shoppers with abandoned carts', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                        </td>
                                                    </tr>            
                                                
                                                    <tr>
                                                        <th>
                                                            <label for="woocommerce_ac_coupon_auto_complete">
                                                                <b>
                                                                    <?php _e( 'Enter a coupon code to add into email:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>                                                    
                                                            <!-- code started for woocommerce auto-complete coupons field emoved from class : woocommerce_options_panelfor WC 2.5 -->
                                                            <div id="coupon_options" class="panel">
                                                                <div class="options_group">
                                                                    <p class="form-field" style="padding-left:0px !important;">
                                                                    
                                                                    <?php
                                                				            
                                            				            $json_ids      = array();
                                                                        $coupon_code_id = '';
                                                                        if ( 'edittemplate' == $mode ) {
                                                                            $coupon_code_id = $results[0]->coupon_code;
                                                                        }
            
                                                                        if ( 'copytemplate' == $mode ) {
                                                                            $coupon_code_id = $results_copy[0]->coupon_code;
                                                                        }
                                                                        if ( $coupon_code_id > 0 ) {
                                                                            
                                                                            if ( 'edittemplate' == $mode ) {
                                                                                $coupon_ids  = explode ( ",", $results[0]->coupon_code );
                                                                            }
                                                                            
                                                                            if ( 'copytemplate' == $mode ) {
                                                                                $coupon_ids  = explode ( ",", $results_copy[0]->coupon_code );
                                                                            }
                                                                            
                                                                            foreach ( $coupon_ids as $product_id ) {
                                                                                if ( $product_id > 0 ){
                                                                                    $product = get_the_title( $product_id );
                                                                                    $json_ids[ $product_id ] = $product ;
                                                                                }
                                                                            }
                                                                        }
                                                                    ?>
                                                                        <input type="hidden" id="coupon_ids" name="coupon_ids[]" class="wc-product-search" style="width: 30%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce' ); ?>" data-multiple="true" data-action="wcap_json_find_coupons" 
                                                    				           data-selected=" <?php echo esc_attr( json_encode( $json_ids ) ); ?> " value="<?php echo implode( ',', array_keys( $json_ids ) ); ?>"
                                                                        />
                                                    					
                                                    					<img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Search & select one coupon code that customers should use to get a discount.', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                                                    </p>
                                                                </div>
                                                            </div>                                                      
                                                            <!-- code ended for woocommerce auto-complete coupons field -->                                       
    													</td>
                                                    </tr> <!-- add new check box -->
                                                    <tr>
                                                        <th>
                                                            <label for="unique_coupon">
                                                                <b>
                                                                    <?php _e( 'Generate unique coupon codes:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>
                                                        <?php
                                                            $is_unique_coupon="";
                                                            
                                                            if ( 'edittemplate' == $mode ) {
                                                                $unique_coupon = $results[0]->generate_unique_coupon_code;
                                                                $is_unique_coupon = "";
                                                                if ( '1' == $unique_coupon ) {
                                                                    $is_unique_coupon = "checked";
                                                                }
                                                            }
        
                                                            if ( 'copytemplate' == $mode ) {
                                                                $unique_coupon = $results_copy[0]->generate_unique_coupon_code;
                                                                $is_unique_coupon = "";
                                                                if( '1' == $unique_coupon ) {
                                                                    $is_unique_coupon = "checked";
                                                                }
                                                            }
                                                            print'<input type="checkbox" name="unique_coupon" id="unique_coupon" ' . $is_unique_coupon . '>  </input>'; ?>
                                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Replace this coupon with unique coupon codes for each customer', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                        </td>
                                                    </tr>  
    
                                                    <tr>
                                                        <th>
                                                            <label for="woocommerce_ac_email_preview">
                                                                <b>
                                                                    <?php _e( 'Send a test email to:', 'woocommerce-ac' ); ?>
                                                                </b>
                                                            </label>
                                                        </th>
                                                        <td>                                       
                                                            <input type="text" id="send_test_email" name="send_test_email" class="regular-text" >
                                                            <input type="button" value="Send a test email" id="preview_email" onclick="javascript:void(0);">
                                                            <img   class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the email id to which the test email needs to be sent.', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                                            <div   id="preview_email_sent_msg" style="display:none;"></div>
                                                                                                            
                                                        </td>
                                                    </tr>                                                
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <p class="submit">
                                <?php
                                    $button_value = "Save Changes";
                                    if ( 'edittemplate' == $mode ) {
                                        $button_value = "Update Changes";
                                    } 
                                ?>
                                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( $button_value, 'woocommerce-ac' ); ?>"  />
                                </p>
                            </form>
                        </div>
                    <?php 
                } elseif ( $action == 'report' ) {
                    include_once('includes/classes/class-wcap-product-report-table.php');
    			    
    			    $wcap_product_report_list = new WCAP_Product_Report_Table();
    			    $wcap_product_report_list->wcap_product_report_prepare_items();
    			    
    			    ?>
    			    <div class="wrap">
    				    <form id="wcap-sent-emails" method="get" >
    				        <input type="hidden" name="page" value="woocommerce_ac_page" />
    				        <input type="hidden" name="action" value="report" />
                                    <?php $wcap_product_report_list->display(); ?>
                        </form>
                    </div>
    			<?php 
    		    }
            echo( "</table>" );
            }               
        }
    
        function cmp( $a, $b ) {
                    if ( $a == $b ) {
               return 0;
           }
           return ( $a < $b ) ? -1 : 1;
        }       
    
        function bubble_sort_function( $unsort_array, $order ) {
    
            $temp = array();
            foreach ( $unsort_array as $key => $value ){
              $temp[$key] = $value; //concatenate something unique to make sure two equal weights don't overwrite each other
            }
            
            asort( $temp, SORT_NUMERIC ); // or ksort($temp, SORT_NATURAL); see paragraph above to understand why
    
            if( $order == 'desc' ) {
           	 	$array = array_reverse( $temp, true );
            }
            else if($order == 'asc') {
            	$array = $temp;
            }
            unset( $temp );
    
            return $array;
        } 		                                    
        
        function wcap_action_javascript() {
        ?>
            <script type="text/javascript" >
                jQuery( document ).ready( function($) {
                <?php 
                    global $pagenow;
                    if( 'index.php' == $pagenow ){ 
                ?>
                        var data = {
                                
                                action: 'wcap_dashboard_widget_report'
                         };
                    
                         // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                         $.get( ajaxurl, data, function( response ) {                     
                     	    $('#abandoned_dashboard_carts .inside').html( response );
                 	     } );
                
                <?php } ?>
                } );
            </script>
            <?php                       
        }   
        
        function wcap_send_test_email() {
        ?>
            <script type="text/javascript" >                    
                jQuery( document ).ready( function($) {
                    jQuery( "table#addedit_template input#preview_email" ).click( function() {
        
                    	var email_body            = '';
        
        			    if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ){
                            email_body =  tinyMCE.get('woocommerce_ac_email_body').getContent();
                        }else{
                            email_body =  jQuery('#woocommerce_ac_email_body').val();
                        }
                        
                        var from_name_preview       = $( '#woocommerce_ac_from_name' ).val();
                        var from_email_preview      = $( '#woocommerce_ac_email_from' ).val();
                        var subject_email_preview   = $( '#woocommerce_ac_email_subject' ).val();
                        
                        var body_email_preview      = email_body;
                        var send_email_id           = $( '#send_test_email' ).val();
                        var reply_name_preview      = $( '#woocommerce_ac_email_reply' ).val();  
                        var is_wc_template          = document.getElementById("is_wc_template").checked;
                        var wc_template_header      = $( '#wcap_wc_email_header' ).val() != '' ? $( '#wcap_wc_email_header' ).val() : 'Abandoned cart reminder';
                                        
                        var data = {
                            from_name_preview       : from_name_preview,
                            from_email_preview      : from_email_preview,
                            subject_email_preview   : subject_email_preview,
                            body_email_preview      : body_email_preview,
                            send_email_id           : send_email_id,
                            reply_name_preview      : reply_name_preview,
                            is_wc_template          : is_wc_template,
                            wc_template_header      : wc_template_header,
                            action                  : 'wcap_preview_email_sent'
                        };
        
                        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                        $.post( ajaxurl, data, function( response ) {
                            $( "#preview_email_sent_msg" ).html( "<img src='<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/check.jpg'>&nbsp;Email has been sent successfully." );
                            $( "#preview_email_sent_msg" ).fadeIn();
                            setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();},3000);
                        } );
                    } );
                } ); 
            </script>
            <?php
        }   

        function wcap_preview_email_sent() {
            
            $from_email_name        = $_POST['from_name_preview'];
            $from_email_preview     = $_POST['from_email_preview'];
            $subject_email_preview  = $_POST['subject_email_preview'];
            $body_email_preview     = $_POST['body_email_preview'];
            $reply_name_preview     = $_POST['reply_name_preview']; 
            $is_wc_template         = $_POST['is_wc_template'];
            $wc_template_header     = $_POST[ 'wc_template_header' ];
            
            $headers                = "From: " . $from_email_name . " <" . $from_email_preview . ">" . "\r\n";
            $headers               .= "Content-Type: text/html" . "\r\n";
            $headers               .= "Reply-To:  " . $reply_name_preview . " " . "\r\n";
            
            $subject_email_preview  = str_replace( '{{customer.firstname}}', 'John', $subject_email_preview );
            $subject_email_preview  = str_replace( '{{product.name}}', 'Woman\'s Hand Bags', $subject_email_preview );
            
            $body_email_preview    = str_replace( '{{customer.firstname}}', 'John', $body_email_preview );
            $body_email_preview    = str_replace( '{{customer.lastname}}', 'Doe', $body_email_preview );
            $body_email_preview    = str_replace( '{{customer.fullname}}', 'John'." ".'Doe', $body_email_preview );
            
            $image_url             = '<img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandon-cart-pro/assets/images/handbag.jpg"/>';
            $body_email_preview    = str_replace( '{{item.image}}', $image_url, $body_email_preview );
            
            $body_email_preview    = str_replace( '{{item.name}}', "Woman\'\s Hand Bags", $body_email_preview );
            $body_email_preview    = str_replace( '{{item.price}}', "$100", $body_email_preview );
            $body_email_preview    = str_replace( '{{item.quantity}}', "1", $body_email_preview );
            $body_email_preview    = str_replace( '{{item.subtotal}}', "$100", $body_email_preview );
            $body_email_preview    = str_replace( '{{cart.total}}', "$100", $body_email_preview );
            
            $shop_name             = get_option( 'blogname' );  
            $body_email_preview    = str_replace( '{{shop.name}}',  $shop_name, $body_email_preview );
            
            $shop_url              = get_option( 'siteurl' );
            $body_email_preview    = str_replace( '{{shop.url}}',  $shop_url, $body_email_preview );
            
            $body_email_preview    = str_replace( '{{coupon.code}}', "TESTCOUPON", $body_email_preview );
            
            $current_time_stamp    = current_time( 'timestamp' );
            $test_date             = date( 'd M, Y h:i A', $current_time_stamp );
            $body_email_preview    = str_replace( '{{cart.abandoned_date}}', $test_date, $body_email_preview );
            
            $to_email_preview = "";
            if ( isset( $_POST[ 'send_email_id' ] ) ) {
                $to_email_preview = $_POST[ 'send_email_id' ];
            }
            
            $cart_url = wc_get_page_permalink( 'cart' );
            $body_email_preview    = str_replace( '{{cart.link}}', $cart_url, $body_email_preview );
            
            $checkout_url = wc_get_page_permalink( 'checkout' );
            $body_email_preview    = str_replace( '{{checkout.link}}', $checkout_url, $body_email_preview );
            
            $body_email_preview    = str_replace( '{{cart.unsubscribe}}', '<a href=#> </a>', $body_email_preview );
            
            $user_email            = get_option( 'admin_email' );
            $body_email_preview    = str_replace( '{{customer.email}}', $user_email, $body_email_preview );
            $subject_email_preview = stripslashes( $subject_email_preview );
            
            if ( isset( $is_wc_template ) && "true" == $is_wc_template ){
                ob_start();
                
                wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
                
                $email_body_template_header = ob_get_clean();
                
                ob_start();
                
                wc_get_template( 'emails/email-footer.php' );
                	
                $email_body_template_footer = ob_get_clean();
                
                $final_email_body =  $email_body_template_header . $body_email_preview . $email_body_template_footer;
                
                wc_mail( $to_email_preview, $subject_email_preview, stripslashes( $final_email_body ) , $headers );
            }else{
                wp_mail( $to_email_preview, $subject_email_preview, stripslashes( $body_email_preview ), $headers );
            }       
            echo "email sent";               
            die();
        }                    
            
        function wcap_json_find_coupons( $x = '', $post_types = array( 'shop_coupon' ) ) {
        
            check_ajax_referer( 'search-products', 'security' );                  
            $term = (string) urldecode( stripslashes( strip_tags( $_GET['term'] ) ) );
        
            if ( empty( $term ) ) {
                die();
            }
        
            if ( is_numeric( $term ) ) {            
                $args = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'post__in'          => array(0, $term),
                        'fields'            => 'ids'
                );                 
                $args2 = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'post_parent'       => $term,
                        'fields'            => 'ids'
                );                  
                $args3 = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'meta_query'        => array(
                                array(
                                        'key'       => '_sku',
                                        'value'     => $term,
                                        'compare'   => 'LIKE'
                                )
                        ),
                        'fields' => 'ids'
                );                   
                $posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ), get_posts( $args3 ) ) );                
            } else {
                $args = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        's'                 => $term,
                        'fields'            => 'ids'
                );                    
                $args2 = array(
                        'post_type'         => $post_types,
                        'post_status'       => 'publish',
                        'posts_per_page'    => -1,
                        'meta_query'        => array(
                                array(
                                        'key'       => '_sku',
                                        'value'     => $term,
                                        'compare'   => 'LIKE'
                                )
                        ),
                        'fields' => 'ids'
                );          
                $posts = array_unique( array_merge( get_posts( $args ), get_posts( $args2 ) ) );        
            }       
            $found_products = array();
        
            if ( $posts ) foreach ( $posts as $post ) {       
                $SKU = get_post_meta( $post, '_sku', true );
        
                if ( isset( $SKU ) && $SKU ) $SKU = ' ( SKU: ' . $SKU . ' )';    
                $found_products[ $post ] = get_the_title( $post ) . ' &ndash; #' . $post . $SKU;   
            }                   
            echo json_encode( $found_products );                   
            die();
        }
                
        function wcap_check_email_sent_for_order( $abandoned_order_id ) {
            global $wpdb;
        
            $query = "SELECT id FROM `" . $wpdb->prefix . "ac_sent_history`
                      WHERE abandoned_order_id = %d";                       
            $results = $wpdb->get_results( $wpdb->prepare( $query, $abandoned_order_id ) );                   
            
            if ( count( $results ) > 0 ) {
                return true;
            }
            return false;
        }

        /*
         * This function used to register template string to wpml
         * Like : Body, subject, Wc header text
         * 
         * Since : 2.7
         */
        function wcap_register_template_string_for_wpml() {
        
            if ( function_exists('icl_register_string') ) {
        
                global $wpdb;
                $context = 'WCAP';
                $template_table = $wpdb->prefix . 'ac_email_templates';
                $result = $wpdb->get_results("SELECT * FROM $template_table");
                foreach ($result as $each_template) {
        
                    $name_msg = 'wcap_template_' . $each_template->id . '_message';
                    $value_msg = $each_template->body;
                    icl_register_string($context, $name_msg, $value_msg); //for registering message
        
                    $name_sub = 'wcap_template_' . $each_template->id . '_subject';
                    $value_sub = $each_template->subject;
                    icl_register_string($context, $name_sub, $value_sub); //for registering subject
        
                    $template_name = 'wcap_template_' . $each_template->id . '_template_name';
                    $getvalue_template_name = $each_template->template_name;
                    icl_register_string($context, $template_name, $getvalue_template_name);
        
                    $wc_email_header = 'wcap_template_' . $each_template->id . '_wc_email_header';
                    $getvalue_wc_email_header = $each_template->wc_email_header;
                    icl_register_string($context, $wc_email_header, $getvalue_wc_email_header);
                }
            }
        }
        
        /*
         * This function used to generate the csv file
         *Since : 3.8
         */
        public static function wcap_generate_csv_report (){
            
            
            $wcap_report  = woocommerce_abandon_cart::wcap_generate_data( );
            $wcap_csv     = woocommerce_abandon_cart::wcap_generate_csv( $wcap_report );
            
            return  $wcap_csv;
            
        }
        /*
         * This function used to generate the Print data
         *Since : 3.8
         */
        public static function wcap_generate_print_report (){
        
            $wcap_report       = woocommerce_abandon_cart::wcap_generate_data( );
            $wcap_print_report = woocommerce_abandon_cart::wcap_generate_print_data( $wcap_report );
            
            echo $wcap_print_report;
            exit();
        }
        
        public static function wcap_generate_data (){

            global $wpdb;
            $return_abandoned_orders = array();
            $per_page                = 30;
            $results                 = array();
            $blank_cart_info         = '{"cart":[]}';
            $blank_cart_info_guest   = '[]';
            
            if( is_multisite() ) {
                // get main site's table prefix
                $main_prefix = $wpdb->get_blog_prefix(1);
                $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id
                WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC";
                $results = $wpdb->get_results($query);
            } else {
            // non-multisite - regular table name
                $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id
                WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC";
            
                $results = $wpdb->get_results($query);
            }
            
            $i = 0;
            $display_tracked_coupons = get_option( 'ac_track_coupons' );
            foreach( $results as $key => $value ) {
                if( $value->user_type == "GUEST" ) {
            	    $query_guest   = "SELECT * from `" . $wpdb->prefix . "ac_guest_abandoned_cart_history`
            	               WHERE id = %d";
        	        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
                }
                $abandoned_order_id = $value->id;
                $user_id            = $value->user_id;
                $user_login         = $value->user_login;
            
                if( $value->user_type == "GUEST" ) {
            	    if( isset( $results_guest[0]->email_id ) ) {
                        $user_email = $results_guest[0]->email_id;
            	    } elseif ( $value->user_id == "0" ) {
                        $user_email = '';
            	    } else {
                        $user_email = '';
            	    }
            	    if ( isset( $results_guest[0]->billing_first_name ) ) {
            	        $user_first_name = $results_guest[0]->billing_first_name;
                    } else if( $value->user_id == "0" ) {
            	        $user_first_name = "Visitor";
                    } else {
            	        $user_first_name = "";
                    }
                    if( isset( $results_guest[0]->billing_last_name ) ) {
                        $user_last_name = $results_guest[0]->billing_last_name;
                    } else if( $value->user_id == "0" ) {
                        $user_last_name = "";
                    } else {
                        $user_last_name = "";
                    }
                    if( isset( $results_guest[0]->phone ) ) {
                        $phone = $results_guest[0]->phone;
                    } elseif ( $value->user_id == "0" ) {
                        $phone = '';
                    } else {
                        $phone = '';
                    }
                } else {
                    $user_email_biiling = get_user_meta( $user_id, 'billing_email', true );
                    if( isset( $user_email_biiling ) && $user_email_biiling == "" ) {
                        $user_data  = get_userdata( $user_id );
                        $user_email = $user_data->user_email;
                    } else {
                        $user_email = $user_email_biiling;
                    }
                    
                    $user_first_name_temp = get_user_meta( $value->user_id, 'first_name' );
                    if( isset( $user_first_name_temp[0] ) ) {
                        $user_first_name = $user_first_name_temp[0];
                    } else {
                        $user_first_name = "";
                    }
                    
                    $user_last_name_temp = get_user_meta( $value->user_id, 'last_name' );
                    if( isset( $user_last_name_temp[0] ) ) {
                        $user_last_name = $user_last_name_temp[0];
                    } else {
                        $user_last_name = "";
                    }
                    $user_phone_number = get_user_meta( $value->user_id, 'billing_phone' );
                    if( isset( $user_phone_number[0] ) ) {
                        $phone = $user_phone_number[0];
                    } else {
                        $phone = "";
                    }
              }
                $cart_info        = json_decode( $value->abandoned_cart_info );
                $order_date       = "";
		        $cart_update_time = $value->abandoned_cart_time;
    
		        if( $cart_update_time != "" && $cart_update_time != 0 ) {
                    $order_date = date( 'd M, Y h:i A', $cart_update_time );
    		    }
        
    		    $ac_cutoff_time = get_option( 'ac_cart_abandoned_time' );
    		    $cut_off_time   = $ac_cutoff_time * 60;
    		    $current_time   = current_time( 'timestamp' );
    		    $compare_time   = $current_time - $cart_update_time;
                $cart_details   = array();
    		    $line_total     = 0;
    		    if( isset( $cart_info->cart ) ) {
                    $cart_details = $cart_info->cart;
    		    }
        
    		    $prod_name = '';
    		    if( count( $cart_details ) > 0 ) {
        		    foreach( $cart_details as $k => $v ) {
        		        
        		        $prod_name = get_the_title( $v->product_id ) . "</br>" . $prod_name;
        		        
            		    if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                            $line_total = $line_total + $v->line_total + $v->line_subtotal_tax;
            		    } else {
                            $line_total = $line_total + $v->line_total;
            		    }
        		    }
    		    }
        
		        $line_total     =  $line_total;
		        $quantity_total = 0;
		            if( count( $cart_details ) > 0) {
		            foreach( $cart_details as $k => $v ) {
		                $quantity_total = $quantity_total + $v->quantity;
		            }
	           }
    		    if( 1 == $quantity_total ) {
                    $item_disp = __( "item", "woocommerce-ac" );
    		    } else {
                    $item_disp = __( "items", "woocommerce-ac" );
    		    }
		        $coupon_details          = get_user_meta( $value->user_id, '_woocommerce_ac_coupon', true );
		        $coupon_detail_post_meta = get_post_meta( $value->id, '_woocommerce_ac_coupon');
		        if( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
                    $ac_status = __( "Abandoned", "woocommerce-ac" );
    		    } elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
    		        $ac_status = __( "Abandoned but new cart created after this", "woocommerce-ac" );
    		    } else {
                    $ac_status = "";
    		    }
        
    		    $coupon_code_used = $coupon_code_message = "";
                if ( $compare_time > $cut_off_time && $ac_status != "" ) {
                    $return_abandoned_orders[$i] = new stdClass();
    		        if( $quantity_total > 0 ) {
    		            $user_role = '';
    		            
    		            if( isset( $user_id ) ) {
    		                if ( $user_id == 0 ){
    		                    $user_role = 'Guest';
    		                }
    		                elseif ( $user_id >= 63000000 ){
    		                    $user_role = 'Guest';
    		                }else{
    		                    $user_role = wcap_common::wcap_get_user_role ( $user_id );
    		                }
    		            }
                        $abandoned_order_id                           = $abandoned_order_id;
                        $customer_information                         = $user_first_name . " ".$user_last_name;
                        $return_abandoned_orders[ $i ]->id            = $abandoned_order_id;
                        $return_abandoned_orders[ $i ]->email         = $user_email;
                        if( $phone == '' ) {
                            $return_abandoned_orders[ $i ]->customer      = $customer_information . "<br>" . $user_role;
                        } else {
                            $return_abandoned_orders[ $i ]->customer      = $customer_information . "<br>" . $phone . "<br>" . $user_role;
                        }
    		            $return_abandoned_orders[ $i ]->order_total   = $line_total;
    		            $return_abandoned_orders[ $i ]->quantity      = $quantity_total . " " . $item_disp;
    		            $return_abandoned_orders[ $i ]->date          = $order_date;
    		            $return_abandoned_orders[ $i ]->status        = $ac_status;
    		            $return_abandoned_orders[ $i ]->user_id       = $user_id;
    		            $return_abandoned_orders[ $i ]->product_names = $prod_name;
    
    		            if( $display_tracked_coupons == 'on' ) {
                            if( $coupon_detail_post_meta != '' ) {
                                foreach( $coupon_detail_post_meta as $key => $value ) {
                                    if( $coupon_detail_post_meta[$key]['coupon_code'] != '' ) {
                                        $coupon_code_used .= $coupon_detail_post_meta[$key]['coupon_code'] . "</br>";
                                    }
                                }
                                $return_abandoned_orders[ $i ]->coupon_code_used = $coupon_code_used;
                            }
                        if( $coupon_detail_post_meta != '' && $coupon_code_used !== '' ) {
                            foreach( $coupon_detail_post_meta as $key => $value ) {
                                $coupon_code_message .= $coupon_detail_post_meta[$key]['coupon_message'] . "</br>";
                            }
                            $return_abandoned_orders[ $i ]->coupon_code_status = $coupon_code_message;
                        }
                    }
                } else {
    	            $abandoned_order_id                    = $abandoned_order_id;
    	            $return_abandoned_orders[ $i ]->id     = $abandoned_order_id;
    	            $return_abandoned_orders[ $i ]->date   = $order_date;
    	            $return_abandoned_orders[ $i ]->status = $ac_status;
                }
                $i++;
                }
            }
		    // sort for order date
		    if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'date' ) {
    		    if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    		      usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_order_date_asc" ) );
    		    } else {
    		      usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_order_date_dsc" ) );
    		    }
		    } else if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'status' ) { // sort for customer name
    		    if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    		      usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_status_asc" ) );
    		    } else {
    		      usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_status_dsc" ) );
    		    }
		    }
		    
		    return $return_abandoned_orders;
        }
        
        public static function wcap_generate_csv ( $report ){
            
            // tracking coupons
            $display_tracked_coupons =  get_option( 'ac_track_coupons' );
            
            // Column Names
            if ( $display_tracked_coupons == 'on' ) {
                $csv               = 'ID, Email Address, Customer, Products, Order Total, Quantity, Abandoned Date, Coupon Code Used, Coupon Status, Status of cart';
                $csv              .= "\n";
            }else{
                $csv               = 'ID, Email Address, Customer, Products, Order Total, Quantity, Abandoned Date, Status of cart';
                $csv              .= "\n";
            }
            
            $currencey       = get_woocommerce_currency(); 
            $currency_symbol = get_woocommerce_currency_symbol( $currencey );
            foreach ( $report as $key => $value ) {
                // Order ID
                         
                
                if ( isset( $value->id ) ){
                    $order_id = $value->id;
                }else{
                    $order_id = '';
                }
                
                if ( isset( $value->email ) ){
                    $email_id = $value->email;
                }else{
                    $email_id = '';
                }
                
                if ( isset( $value->customer ) ){
                    $name = $value->customer;
                    $name = str_replace ('<br>',"\n" , $name );
                }else{
                    $name = '';
                }
                
                
                
                if ( isset( $value->product_names ) ){
                    $product_name = $value->product_names;
                }else{
                    $product_name = '';
                }
                
                $product_name   = str_replace ('</br>',"\n" , $product_name );
                
                if ( isset( $value->order_total ) ){
                    $order_total = $value->order_total;
                }else{
                    $order_total = '';
                }
                
                
                if ( isset( $value->quantity ) ){
                    $quantity = $value->quantity;
                }else{
                    $quantity = '';
                }
                
                if ( isset( $value->date ) ){
                    $abandoned_date = $value->date;
                }else{
                    $abandoned_date = '';
                }
                
                if ( isset( $value->status ) ){
                    $abandoned_status = $value->status;
                }else{
                    $abandoned_status = '';
                }
                
                
                if ( $display_tracked_coupons == 'on' ) {
                    
                    if ( isset( $value->coupon_code_used ) ){
                        $coupon_used = $value->coupon_code_used;
                    }else{
                        $coupon_used = '';
                    }
                    
                    $coupon_used   = str_replace ('</br>',"\n" , $coupon_used );
                    
                    
                    $coupon_status   = '';
                    if ( isset( $value->coupon_code_status ) && '' != $value->coupon_code_status ){
                        $coupon_status   =  $value->coupon_code_status;
                        
                        $coupon_status   = str_replace ('</br>',"\n" , $coupon_status );
                    }
                    /**
                     * When any string which contain comma in the csv we need to escape that. We need to wrap that sting in double quotes.
                     * So it will display string with comma.
                     */
                    // Create the data row
                    $csv             .= $order_id . ',' . $email_id . ','. "\" $name \"" . ',' . "\"  $product_name \"" . ',' .  $currency_symbol . $order_total . ',' . $quantity . ',' . "\" $abandoned_date\"". ',' . "\" $coupon_used \"" . ','. "\" $coupon_status \"" . ',' . $abandoned_status   ;
                    $csv             .= "\n";
                }else{
                    // Create the data row
                    
                    $csv             .= $order_id . ',' . $email_id . ','. "\" $name \"" . ',' . "\"  $product_name \"" . ',' . $currency_symbol . $order_total . ',' . $quantity . ',' . "\" $abandoned_date\"". ',' . $abandoned_status   ;
                    $csv             .= "\n";
                }
            }
            return $csv;
        }
        public static function wcap_generate_print_data ( $report ){
            
            // tracking coupons
            $display_tracked_coupons =  get_option( 'ac_track_coupons' );
            
            if ( $display_tracked_coupons == 'on' ) {
                $print_data_columns  = "
                					<tr>
                						<th style='border:1px solid black;padding:5px;'>".__( 'ID', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Email Address', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Customer Details', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Products', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Order Total', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Quantity', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Abandoned Date', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Coupon Code Used', 'woocommerce-booking' )."</th>
                                        <th style='border:1px solid black;padding:5px;'>".__( 'Coupon Status', 'woocommerce-booking' )."</th>  
                						<th style='border:1px solid black;padding:5px;'>".__( 'Status of cart', 'woocommerce-booking' )."</th>
                					</tr>";
            }else{
                
                $print_data_columns  = "
                					<tr>
                						<th style='border:1px solid black;padding:5px;'>".__( 'ID', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Email Address', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Customer Details', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Products', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Order Total', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Quantity', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Abandoned Date', 'woocommerce-booking' )."</th>
                						<th style='border:1px solid black;padding:5px;'>".__( 'Status of cart', 'woocommerce-booking' )."</th>
                					</tr>";
            }
            
            $print_data_row_data =  '';
            	
            $currency           = get_woocommerce_currency_symbol();
            foreach ( $report as $key => $value ) {
               
                if ( isset( $value->id ) ){
                    $abandoned_id = $value->id;
                }else{
                    $abandoned_id = '';
                }
                
                if ( isset( $value->email ) ){
                    $customer_email = $value->email;
                }else{
                    $customer_email = '';
                }
                
                if ( isset( $value->customer ) ){
                    $customer_name = $value->customer;
                }else{
                    $customer_name = '';
                }
                
                if ( isset( $value->product_names ) ){
                    $product_names = $value->product_names;
                }else{
                    $product_names = '';
                }
                
                if ( isset( $value->order_total ) ){
                    $order_total = $value->order_total;
                }else{
                    $order_total = '';
                }
                
                if ( isset( $value->quantity ) ){
                    $order_quantity = $value->quantity;
                }else{
                    $order_quantity = '';
                }
                
                if ( isset( $value->coupon_code_used ) ){
                    $coupon_code_used = $value->coupon_code_used;
                }else{
                    $coupon_code_used = '';
                }
                
                if ( isset( $value->coupon_code_status ) ){
                    $coupon_code_status = $value->coupon_code_status;
                }else{
                    $coupon_code_status = '';
                }
                
                if ( isset( $value->date ) ){
                    $abandoned_date = $value->date;
                }else{
                    $abandoned_date = '';
                }
                if ( isset( $value->status ) ){
                    $abandoned_status = $value->status;
                }else{
                    $abandoned_status = '';
                }
                
                
                if ( $display_tracked_coupons == 'on' ){
                    
                    $print_data_row_data .= "<tr>
        								<td style='border:1px solid black;padding:5px;'>".$abandoned_id."</td>
        								<td style='border:1px solid black;padding:5px;'>".$customer_email."</td>
        								<td style='border:1px solid black;padding:5px;'>".$customer_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$product_names."</td>
        								<td style='border:1px solid black;padding:5px;'>".$currency . $order_total."</td>
        								<td style='border:1px solid black;padding:5px;'>".$order_quantity."</td>
        								<td style='border:1px solid black;padding:5px;'>".$abandoned_date."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$coupon_code_used."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$coupon_code_status."</td>
        								<td style='border:1px solid black;padding:5px;'>".$abandoned_status."</td>
        								</tr>";
                }else{
                    
                    $print_data_row_data .= "<tr>
        								<td style='border:1px solid black;padding:5px;'>".$abandoned_id."</td>
        								<td style='border:1px solid black;padding:5px;'>".$customer_email."</td>
        								<td style='border:1px solid black;padding:5px;'>".$customer_name."</td>
        								<td style='border:1px solid black;padding:5px;'>".$product_names."</td>
        								<td style='border:1px solid black;padding:5px;'>".$currency . $order_total."</td>
        								<td style='border:1px solid black;padding:5px;'>".$order_quantity."</td>
        								<td style='border:1px solid black;padding:5px;'>".$abandoned_date."</td>
                                        <td style='border:1px solid black;padding:5px;'>".$abandoned_status."</td>
        								</tr>";
                }
            }
            $print_data_columns  =   $print_data_columns;
            $print_data_row_data =   $print_data_row_data;
            $print_data          =   "<table style='border:1px solid black;border-collapse:collapse;'>" . $print_data_columns . $print_data_row_data . "</table>";
            return $print_data;
        }
        
    }
}       
$woocommerce_abandon_cart = new woocommerce_abandon_cart();
?>