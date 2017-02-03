<?php
/**
 * WC_CP_Shipstation_Compatibility class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Composite Products
 * @since    3.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipstation Integration.
 *
 * @version  3.7.0
 */
class WC_CP_Shipstation_Compatibility {

	public static function init() {

		// Shipstation compatibility.
		add_action( 'woocommerce_api_wc_shipstation', array( __CLASS__, 'add_filters' ), 9 );
	}

	/**
	 * Modify the returned order items and products to return the correct items/weights/values for shipping.
	 */
	public static function add_filters() {

		add_filter( 'woocommerce_order_get_items', array( WC_CP()->order, 'get_order_items' ), 11, 2 );
		add_filter( 'woocommerce_get_product_from_item', array( WC_CP()->order, 'get_product_from_item' ), 11, 3 );
	}
}

WC_CP_Shipstation_Compatibility::init();
