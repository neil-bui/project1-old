<?php
/**
 * WC_PB_Shipstation_Compatibility class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Product Bundles
 * @since    4.11.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipstation Integration.
 *
 * @version  5.0.2
 */
class WC_PB_Shipstation_Compatibility {

	public static function init() {

		// Shipstation compatibility.
		add_action( 'woocommerce_api_wc_shipstation', array( __CLASS__, 'add_filters' ), 5 );
	}

	/**
	 * Modify the returned order items and products to return the correct items/weights/values for shipping.
	 */
	public static function add_filters() {

		add_filter( 'woocommerce_order_get_items', array( WC_PB()->order, 'get_order_items' ), 10, 2 );
		add_filter( 'woocommerce_get_product_from_item', array( WC_PB()->order, 'get_product_from_item' ), 10, 3 );
	}
}

WC_PB_Shipstation_Compatibility::init();
