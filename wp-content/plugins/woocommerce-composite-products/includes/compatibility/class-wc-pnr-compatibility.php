<?php
/**
 * WC_CP_PnR_Compatibility class
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
 * Points and Rewards Compatibility.
 *
 * @version  3.8.0
 */
class WC_CP_PnR_Compatibility {

	public static function init() {

		// Points and Rewards support.
		add_filter( 'woocommerce_points_earned_for_cart_item', array( __CLASS__, 'points_earned_for_composited_cart_item' ), 10, 3 );
		add_filter( 'woocommerce_points_earned_for_order_item', array( __CLASS__, 'points_earned_for_composited_order_item' ), 10, 5 );

		// Change earn points message for Composites that contain individually-priced items.
		add_filter( 'wc_points_rewards_single_product_message', array( __CLASS__, 'points_rewards_composite_message' ), 10, 2 );

		// Remove PnR message from variations.
		add_action( 'woocommerce_composite_products_apply_product_filters', array( __CLASS__, 'points_rewards_remove_price_html_messages' ) );
		add_action( 'woocommerce_composite_products_remove_product_filters', array( __CLASS__, 'points_rewards_restore_price_html_messages' ) );
	}

	/**
	 * Return zero points for composited cart items if container item has product level points.
	 *
	 * @param  int     $points
	 * @param  string  $cart_item_key
	 * @param  array   $cart_item_values
	 * @return int
	 */
	public static function points_earned_for_composited_cart_item( $points, $cart_item_key, $cart_item_values ) {

		if ( $composite_container_item = wc_cp_get_composited_cart_item_container( $cart_item_values ) ) {

			$composite        = $composite_container_item[ 'data' ];
			$product_id       = $cart_item_values[ 'product_id' ];
			$component_id     = $cart_item_values[ 'composite_item' ];

			$component_option = $composite->get_component_option( $component_id, $product_id );

			// Check if earned points are set at product-level.
			$composite_points     = WC_Points_Rewards_Product::get_product_points( $composite );
			$has_composite_points = is_numeric( $composite_points );

			if ( $has_composite_points || false === $component_option->is_priced_individually() ) {
				$points = 0;
			}
		}

		return $points;
	}

	/**
	 * Return zero points for composited cart items if container item has product level points.
	 *
	 * @param  int       $points
	 * @param  string    $item_key
	 * @param  array     $item
	 * @param  WC_Order  $order
	 * @return int
	 */
	public static function points_earned_for_composited_order_item( $points, $product, $item_key, $item, $order ) {

		if ( $composite_container_item = wc_cp_get_composited_order_item_container( $item, $order ) ) {

			$composite_id = $composite_container_item[ 'product_id' ];

			// Check if earned points are set at product-level.
			$item_priced_individually = isset( $item[ 'component_priced_individually' ] ) ? $item[ 'component_priced_individually' ] : false;

			// Back-compat.
			if ( false === $item_priced_individually ) {
				$item_priced_individually = isset( $composite_container_item[ 'per_product_pricing' ] ) ? $composite_container_item[ 'per_product_pricing' ] : get_post_meta( $composite_container_item[ 'product_id' ], '_bto_per_product_pricing', true );
			}

			$composite_points = get_post_meta( $composite_id, '_wc_points_earned', true );

			if ( ! empty( $composite_points ) || 'no' === $item_priced_individually ) {
				$points = 0;
			}
		}

		return $points;
	}

	/**
	 * Points and Rewards single product message for Composites that contain individually-priced components.
	 *
	 * @param  string                     $message
	 * @param  WC_Points_Rewards_Product  $points_n_rewards
	 * @return string
	 */
	public static function points_rewards_composite_message( $message, $points_n_rewards ) {

		global $product;

		if ( 'composite' === $product->get_type() ) {

			if ( false === $product->contains( 'priced_individually' ) ) {
				return $message;
			}

			$composite_points = WC_Points_Rewards_Product::get_points_earned_for_product_purchase( $product );
			$message          = $points_n_rewards->create_at_least_message_to_product_summary( $composite_points );
		}

		return $message;
	}

	/**
	 * Filter option_wc_points_rewards_single_product_message in order to force 'WC_Points_Rewards_Product::render_variation_message' to display nothing.
	 */
	public static function points_rewards_remove_price_html_messages( $args ) {
		add_filter( 'option_wc_points_rewards_single_product_message', array( __CLASS__, 'return_empty_message' ) );
	}

	/**
	 * Restore option_wc_points_rewards_single_product_message. Forced in order to force 'WC_Points_Rewards_Product::render_variation_message' to display nothing.
	 */
	public static function points_rewards_restore_price_html_messages( $args ) {
		remove_filter( 'option_wc_points_rewards_single_product_message', array( __CLASS__, 'return_empty_message' ) );
	}

	/**
	 * @see points_rewards_remove_price_html_messages
	 *
	 * @param  string  $message
	 * @return void
	 */
	public static function return_empty_message( $message ) {
		return false;
	}
}

WC_CP_PnR_Compatibility::init();
