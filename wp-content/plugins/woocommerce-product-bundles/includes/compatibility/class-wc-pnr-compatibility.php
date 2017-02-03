<?php
/**
 * WC_PB_PnR_Compatibility class
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
 * Points and Rewards Compatibility.
 *
 * @since  4.11.4
 */
class WC_PB_PnR_Compatibility {

	public static function init() {

		// Points earned filters.
		add_filter( 'woocommerce_points_earned_for_cart_item', array( __CLASS__, 'points_earned_for_bundled_cart_item' ), 10, 3 );
		add_filter( 'woocommerce_points_earned_for_order_item', array( __CLASS__, 'points_earned_for_bundled_order_item' ), 10, 5 );

		// Change earn points message for Bundles that contain individually-priced items.
		add_filter( 'wc_points_rewards_single_product_message', array( __CLASS__, 'points_rewards_bundle_message' ), 10, 2 );

		// Remove PnR message from bundled variations.
		add_filter( 'option_wc_points_rewards_single_product_message', array( __CLASS__, 'return_empty_message' ) );
	}

	/**
	 * Return zero points for bundled cart items if container item has product level points.
	 *
	 * @param  int     $points
	 * @param  string  $cart_item_key
	 * @param  array   $cart_item_values
	 * @return int
	 */
	public static function points_earned_for_bundled_cart_item( $points, $cart_item_key, $cart_item_values ) {

		if ( $parent = wc_pb_get_bundled_cart_item_container( $cart_item_values ) ) {

			$bundle          = $parent[ 'data' ];
			$bundled_item_id = $cart_item_values[ 'bundled_item_id' ];
			$bundled_item    = $bundle->get_bundled_item( $bundled_item_id );

			// Check if earned points are set at product-level.
			$bundle_points     = WC_Points_Rewards_Product::get_product_points( $bundle );
			$has_bundle_points = is_numeric( $bundle_points );

			if ( $has_bundle_points || false === $bundled_item->is_priced_individually() ) {
				$points = 0;
			}
		}

		return $points;
	}

	/**
	 * Return zero points for bundled cart items if container item has product level points.
	 *
	 * @param  int       $points
	 * @param  string    $item_key
	 * @param  array     $item
	 * @param  WC_Order  $order
	 * @return int
	 */
	public static function points_earned_for_bundled_order_item( $points, $product, $item_key, $item, $order ) {

		if ( $parent_item = wc_pb_get_bundled_order_item_container( $item, $order ) ) {

			$bundle_product_id = $parent_item[ 'product_id' ];

			// Check if earned points are set at product-level.
			$bundled_item_priced_individually = isset( $item[ 'bundled_item_priced_individually' ] ) ? $item[ 'bundled_item_priced_individually' ] : false;

			// Back-compat.
			if ( false === $bundled_item_priced_individually ) {
				$bundled_item_priced_individually = isset( $parent_item[ 'per_product_pricing' ] ) ? $parent_item[ 'per_product_pricing' ] : get_post_meta( $parent_item[ 'product_id' ], '_wc_pb_v4_per_product_pricing', true );
			}

			$bundle_points = get_post_meta( $bundle_product_id, '_wc_points_earned', true );

			if ( ! empty( $bundle_points ) || 'no' === $bundled_item_priced_individually ) {
				$points = 0;
			}
		}

		return $points;
	}

	/**
	 * Points and Rewards single product message for Bundles that contain individually-priced items.
	 *
	 * @param  string                     $message
	 * @param  WC_Points_Rewards_Product  $points_n_rewards
	 * @return string
	 */
	public static function points_rewards_bundle_message( $message, $points_n_rewards ) {

		global $product;

		if ( $product->product_type === 'bundle' ) {

			if ( false === $product->contains( 'priced_individually' ) ) {
				return $message;
			}

			$bundle_points = WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $product );
			$message       = $points_n_rewards->create_at_least_message_to_product_summary( $bundle_points );
		}

		return $message;
	}

	/**
	 * @see points_rewards_remove_price_html_messages
	 *
	 * @param  string  $message
	 * @return void
	 */
	public static function return_empty_message( $message ) {
		if ( did_action( 'woocommerce_bundled_product_price_filters_added' ) > did_action( 'woocommerce_bundled_product_price_filters_removed' ) ) {
			$message = false;
		}
		return $message;
	}
}

WC_PB_PnR_Compatibility::init();
