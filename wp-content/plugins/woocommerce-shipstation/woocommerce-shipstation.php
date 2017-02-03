<?php
/**
 * Plugin Name: WooCommerce - ShipStation Integration
 * Plugin URI: http://www.woothemes.com/products/shipstation-integration/
 * Version: 4.1.7
 * Description: Adds ShipStation label printing support to WooCommerce. Requires server DomDocument support.
 * Author: WooThemes
 * Author URI: http://www.woothemes.com/
 * Text Domain: woocommerce-shipstation
 *
 * @todo Investigate feasibility of line item tracking before marking order complete.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '9de8640767ba64237808ed7f245a49bb', '18734' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

/**
 * Include shipstation class
 */
function __woocommerce_shipstation_init() {
	define( 'WC_SHIPSTATION_VERSION', '4.1.7' );
	define( 'WC_SHIPSTATION_FILE', __FILE__ );

	if ( ! defined( 'WC_SHIPSTATION_EXPORT_LIMIT' ) ) {
		define( 'WC_SHIPSTATION_EXPORT_LIMIT', 100 );
	}

	load_plugin_textdomain( 'woocommerce-shipstation', false, basename( dirname( __FILE__ ) ) . '/languages' );

	include_once( 'includes/class-wc-shipstation-integration.php' );
}

add_action( 'plugins_loaded', '__woocommerce_shipstation_init' );

/**
 * Define integration
 * @param  array $integrations
 * @return array
 */
function __woocommerce_shipstation_load_integration( $integrations ) {
	$integrations[] = 'WC_ShipStation_Integration';

	return $integrations;
}

add_filter( 'woocommerce_integrations', '__woocommerce_shipstation_load_integration' );

/**
 * Listen for API requests
 */
function __woocommerce_shipstation_api() {
	include_once( 'includes/class-wc-shipstation-api.php' );
}

add_action( 'woocommerce_api_wc_shipstation', '__woocommerce_shipstation_api' );
