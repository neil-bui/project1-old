<?php
/**
 * WC_CP_COG_Compatibility class
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
 * Cost of Goods Compatibility.
 *
 * @version  3.7.0
 */
class WC_CP_COG_Compatibility {

	public static function init() {

		// Cost of Goods support
		add_filter( 'wc_cost_of_goods_set_order_item_cost_meta_item_cost', array( __CLASS__, 'cost_of_goods_set_order_item_cost_composited_item_cost' ), 10, 3 );
	}

	/**
	 * Cost of goods compatibility: Zero order item cost for composited products that belong to statically priced composites.
	 *
	 * @param  double    $cost
	 * @param  array     $item
	 * @param  WC_Order  $order
	 * @return double
	 */
	public static function cost_of_goods_set_order_item_cost_composited_item_cost( $cost, $item, $order ) {

		if ( $parent_item = wc_cp_get_composited_order_item_container( $item, $order ) ) {

			$item_priced_individually = isset( $item[ 'component_priced_individually' ] ) ? $item[ 'component_priced_individually' ] : false;

			// Back-compat.
			if ( false === $item_priced_individually ) {
				$item_priced_individually = isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $parent_item[ 'product_id' ], '_bto_per_product_pricing', true );
			}

			if ( 'no' === $item_priced_individually ) {
				return 0;
			}
		}

		return $cost;
	}
}

WC_CP_COG_Compatibility::init();
