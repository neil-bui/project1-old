<?php
/**
 * Plugin Name: Affiliates Manager WooCommerce Coupons Integration
 * Plugin URI: https://wpaffiliatemanager.com/tracking-affiliate-commission-using-woocommerce-coupons-or-discount-codes/
 * Description: Addon for using WooCommerce Coupons with the Affiliates Manager plugin
 * Version: 1.0.2
 * Author: wp.insider, affmngr
 * Author URI: https://wpaffiliatemanager.com
 * Requires at least: 3.0
*/

if (!defined('ABSPATH')) exit;

if (!class_exists('AFFMGR_WOO_COUPON_ADDON')){

class AFFMGR_WOO_COUPON_ADDON{
	var $version = '1.0.2';
	var $db_version = '1.0';
	var $plugin_url;
	var $plugin_path;
	
	function __construct() {
            $this->define_constants();
            $this->includes();
            $this->loader_operations();
            //Handle any db install and upgrade task
            add_action( 'init', array($this, 'plugin_init' ), 0 );
            add_action('wpam_after_main_admin_menu', array($this, 'do_admin_menu'));
            add_filter('wpam_woo_override_refkey',array($this,'aff_woo_check_coupons'), 10, 2);             
	}
	
	function define_constants(){
            define('AFFMGR_WOO_COUPON_ADDON_VERSION', $this->version);
            define('AFFMGR_WOO_COUPON_ADDON_URL', $this->plugin_url());
            define('AFFMGR_WOO_COUPON_ADDON_PATH', $this->plugin_path());
	}
	
	function includes() {
            include_once('affmgr-woo-coupons-settings.php');
            include_once('class-affmgr-woo-coupons-association.php');
	}
	
	function loader_operations(){
            add_action('plugins_loaded',array(&$this, 'plugins_loaded_handler'));//plugins loaded hook		
	}
	
	function plugins_loaded_handler(){//Runs when plugins_loaded action gets fired
            $this->do_db_upgrade_check();
	}
	
	function do_db_upgrade_check(){
            //NOP
	}
	
	function plugin_url() { 
            if ( $this->plugin_url ) return $this->plugin_url;
            return $this->plugin_url = plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}
	
	function plugin_path() { 	
            if ( $this->plugin_path ) return $this->plugin_path;		
            return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
	
	function plugin_init(){//Gets run with WP Init is fired
            		
	}
	
	function do_admin_menu($menu_parent_slug)
	{
            add_submenu_page($menu_parent_slug, __("WooCommerce Coupons", 'wpam'), __("Woo Coupons", 'wpam'), 'manage_options', 'wpam-woo-coupons', 'wpam_woo_coupons_admin_interface');
	}	
	
	function aff_woo_check_coupons($wpam_refkey, $order)
	{
            WPAM_Logger::log_debug("WooCommerce Coupons Integration - Checking coupons for this transaction");
            $txn_coupons = $order->get_used_coupons();
            if ( sizeof( $txn_coupons ) > 0 ) 
            {
                foreach ( $txn_coupons as $code ) {
                    if ( ! $code ){
                            continue;
                    }
                    WPAM_Logger::log_debug("WooCommerce Coupons Integration - Found a coupon code for this transaction. Coupon Code: ".$code);
                    //$coupon = new WC_Coupon( $code );
                    $collection_obj = AFFMGR_WOO_COUPONS_ASSOC::get_instance();
                    $item = $collection_obj->find_item_by_code($code);
                    $aff_id = $item->aff_id;                   
                    if(isset($aff_id) && !empty($aff_id)){
                        WPAM_Logger::log_debug("WooCommerce Coupons Integration - Affiliate ID value for this coupon code: ".$aff_id);
                        //checking to see if affiliate ID is present in the order meta (new tracking system)
                        $wpam_id = get_post_meta($order->id, '_wpam_id', true);
                        if(!empty($wpam_id)){
                            WPAM_Logger::log_debug("WooCommerce Coupons Integration - Commission will be awarded to Affiliate ID: ".$aff_id);
                            $wpam_refkey = $wpam_id;
                            return $wpam_refkey;
                        }
                        //
                        WPAM_Logger::log_debug("WooCommerce Coupons Integration - Generating refkey for this affiliate");
                        $refkey = wpam_generate_refkey_from_affiliate_id($aff_id);
                        if($refkey==NULL){
                            WPAM_Logger::log_debug("WooCommerce Coupons Integration - A valid refkey could not be generated!");
                        }
                        else{
                            $wpam_refkey = $refkey; 
                            WPAM_Logger::log_debug("WooCommerce Coupons Integration - New refkey: ".$wpam_refkey);
                            return $wpam_refkey;
                        }

                    }
                    else{
                        WPAM_Logger::log_debug("WooCommerce Coupons Integration - No Affiliate ID associated with coupon code: ".$code);
                    }
                }
            }
            else{
                WPAM_Logger::log_debug("WooCommerce Coupons Integration - No coupons used for this woocommerce transaction.");
            }
            return $wpam_refkey;
	}
	
}//End of plugin class

}//End of class not exists check

$GLOBALS['AFFMGR_WOO_COUPON_ADDON'] = new AFFMGR_WOO_COUPON_ADDON();
