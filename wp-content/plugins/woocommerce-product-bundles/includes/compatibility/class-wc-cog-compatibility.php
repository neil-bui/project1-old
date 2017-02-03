<?php
/**
 * WC_PB_COG_Compatibility class
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
 * Cost of Goods Compatibility.
 *
 * @since  4.11.4
 */
class WC_PB_COG_Compatibility {

	public static function init() {

		// Cost of Goods support.
		add_filter( 'wc_cost_of_goods_set_order_item_cost_meta_item_cost', array( __CLASS__, 'cost_of_goods_set_order_item_bundled_item_cost' ), 10, 3 );
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for bundled products that belong to statically priced bundles.
	 *
	 * @param  double    $cost
	 * @param  array     $item
	 * @param  WC_Order  $order
	 * @return double
	 */
	public static function cost_of_goods_set_order_item_bundled_item_cost( $cost, $item, $order ) {

		if ( $parent_item = wc_pb_get_bundled_order_item_container( $item, $order ) ) {

			$bundled_item_priced_individually = isset( $item[ 'bundled_item_priced_individually' ] ) ? $item[ 'bundled_item_priced_individually' ] : false;

			// Back-compat.
			if ( false === $bundled_item_priced_individually ) {
				$bundled_item_priced_individually = isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $parent_item[ 'product_id' ], '_wc_pb_v4_per_product_pricing', true );
			}

			if ( 'no' === $bundled_item_priced_individually ) {
				return 0;
			}
		}

		return $cost;
	}
}

WC_PB_COG_Compatibility::init();
