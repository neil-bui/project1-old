<?php
/**
 * Product Bundles API functions
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Product Bundles
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*---------------*/
/*  Products     */
/*---------------*/

/**
 * Create a WC_Bundled_Item instance.
 *
 * @since  5.0.0
 *
 * @param  mixed  $item
 * @param  mixed  $parent
 * @return mixed
 */
function wc_pb_get_bundled_item( $item, $parent = false ) {

	$data = null;

	if ( is_numeric( $item ) ) {
		$data = WC_PB_DB::get_bundled_item( absint( $item ) );
	} elseif ( $item instanceof WC_Bundled_Item_Data ) {
		$data = $item;
	}

	if ( ! is_null( $data ) ) {
		$bundled_item = new WC_Bundled_Item( $data, $parent );

		if ( $bundled_item->exists() ) {
			return $bundled_item;
		}
	}

	return false;
}

/**
 * Get a map of the bundled item DB IDs and product bundle post IDs associated with a (bundled) product.
 *
 * @since  5.0.0
 *
 * @param  mixed    $product
 * @param  boolean  $allow_cache
 * @return array
 */
function wc_pb_get_bundled_product_map( $product, $allow_cache = true ) {

	if ( is_object( $product ) ) {
		$product = absint( $product->id );
	} else {
		$product = absint( $product );
	}

	$allow_cache = $allow_cache && ! defined( 'WC_PB_DEBUG_TRANSIENTS' ) && ! defined( 'WC_PB_UPDATING' );

	$transient_name             = 'wc_bundled_product_data_' . WC_Cache_Helper::get_transient_version( 'product' );
	$bundled_product_data_array = get_transient( $transient_name );
	$bundled_product_data       = false;

	if ( $allow_cache && false !== $bundled_product_data_array && is_array( $bundled_product_data_array ) && isset( $bundled_product_data_array[ $product ] ) && is_array( $bundled_product_data_array[ $product ] ) ) {
		$bundled_product_data = $bundled_product_data_array[ $product ];
	}

	if ( false === $bundled_product_data ) {

		$args = array(
			'product_id' => $product,
			'return'     => 'id=>bundle_id'
		);

		$bundled_product_data = WC_PB_DB::query_bundled_items( $args );

		if ( is_array( $bundled_product_data_array ) ) {
			$bundled_product_data_array[ $product ] = $bundled_product_data;
		} else {
			$bundled_product_data_array = array( $product => $bundled_product_data );
		}

		if ( ! defined( 'WC_PB_UPDATING' ) ) {
			set_transient( $transient_name, $bundled_product_data_array, DAY_IN_SECONDS * 30 );
		}
	}

	return $bundled_product_data;
}


/*---------------*/
/*  Cart         */
/*---------------*/

/**
 * Given a bundled cart item, find and return its container cart item - the Bundle - or its cart id when the $return_id arg is true.
 *
 * @since  5.0.0
 *
 * @param  array    $bundled_cart_item
 * @param  array    $cart_contents
 * @param  boolean  $return_id
 * @return mixed
 */
function wc_pb_get_bundled_cart_item_container( $bundled_cart_item, $cart_contents = false, $return_id = false ) {

	if ( ! $cart_contents ) {
		$cart_contents = WC()->cart->cart_contents;
	}

	$container = false;

	if ( wc_pb_maybe_is_bundled_cart_item( $bundled_cart_item ) ) {

		$bundled_by = $bundled_cart_item[ 'bundled_by' ];

		if ( isset( $cart_contents[ $bundled_by ] ) ) {
			$container = $return_id ? $bundled_by : $cart_contents[ $bundled_by ];
		}
	}

	return $container;
}

/**
 * Given a bundle container cart item, find and return its child cart items - or their cart ids when the $return_ids arg is true.
 *
 * @since  5.0.0
 *
 * @param  array    $container_cart_item
 * @param  array    $cart_contents
 * @param  boolean  $return_ids
 * @return mixed
 */
function wc_pb_get_bundled_cart_items( $container_cart_item, $cart_contents = false, $return_ids = false ) {

	if ( ! $cart_contents ) {
		$cart_contents = WC()->cart->cart_contents;
	}

	$bundled_cart_items = array();

	if ( wc_pb_is_bundle_container_cart_item( $container_cart_item ) ) {

		$bundled_items = $container_cart_item[ 'bundled_items' ];

		if ( ! empty( $bundled_items ) && is_array( $bundled_items ) ) {
			foreach ( $bundled_items as $bundled_cart_item_key ) {
				if ( isset( $cart_contents[ $bundled_cart_item_key ] ) ) {
					$bundled_cart_items[ $bundled_cart_item_key ] = $cart_contents[ $bundled_cart_item_key ];
				}
			}
		}
	}

	return $return_ids ? array_keys( $bundled_cart_items ) : $bundled_cart_items;
}

/**
 * True if a cart item is part of a bundle.
 * Instead of relying solely on cart item data, the function also checks that the alleged parent item actually exists.
 *
 * @since  5.0.0
 *
 * @param  array  $cart_item
 * @param  array  $cart_contents
 * @return boolean
 */
function wc_pb_is_bundled_cart_item( $cart_item, $cart_contents = false ) {

	$is_bundled = false;

	if ( wc_pb_get_bundled_cart_item_container( $cart_item, $cart_contents ) ) {
		$is_bundled = true;
	}

	return $is_bundled;
}

/**
 * True if a cart item appears to be part of a bundle.
 * The result is purely based on cart item data - the function does not check that a valid parent item actually exists.
 *
 * @since  5.0.0
 *
 * @param  array  $cart_item
 * @return boolean
 */
function wc_pb_maybe_is_bundled_cart_item( $cart_item ) {

	$is_bundled = false;

	if ( ! empty( $cart_item[ 'bundled_by' ] ) && ! empty( $cart_item[ 'bundled_item_id' ] ) && ! empty( $cart_item[ 'stamp' ] ) ) {
		$is_bundled = true;
	}

	return $is_bundled;
}

/**
 * True if a cart item appears to be a bundle container item.
 *
 * @since  5.0.0
 *
 * @param  array  $cart_item
 * @return boolean
 */
function wc_pb_is_bundle_container_cart_item( $cart_item ) {

	$is_bundle = false;

	if ( isset( $cart_item[ 'bundled_items' ] ) && ! empty( $cart_item[ 'stamp' ] ) ) {
		$is_bundle = true;
	}

	return $is_bundle;
}


/*---------------*/
/*  Orders       */
/*---------------*/

/**
 * Given a bundled order item, find and return its container order item - the Bundle - or its order item id when the $return_id arg is true.
 *
 * @since  5.0.0
 *
 * @param  array     $bundled_order_item
 * @param  WC_Order  $order
 * @param  boolean   $return_id
 * @return mixed
 */
function wc_pb_get_bundled_order_item_container( $bundled_order_item, $order, $return_id = false ) {

	$container = false;

	if ( wc_pb_maybe_is_bundled_order_item( $bundled_order_item ) ) {

		$order_items = is_object( $order ) ? $order->get_items( 'line_item' ) : $order;

		foreach ( $order_items as $order_item_id => $order_item ) {

			$is_container = false;

			if ( isset( $order_item[ 'bundle_cart_key' ] ) ) {
				$is_container = $bundled_order_item[ 'bundled_by' ] === $order_item[ 'bundle_cart_key' ];
			} else {
				$is_container = isset( $order_item[ 'stamp' ] ) && $order_item[ 'stamp' ] === $bundled_order_item[ 'stamp' ] && ! isset( $order_item[ 'bundled_by' ] );
			}

			if ( $is_container ) {
				$container = $return_id ? $order_item_id : $order_item;
			}
		}
	}

	return $container;
}

/**
 * Given a bundle container order item, find and return its child order items - or their order item ids when the $return_ids arg is true.
 *
 * @since  5.0.0
 *
 * @param  array     $container_order_item
 * @param  WC_Order  $order
 * @param  boolean   $return_ids
 * @return mixed
 */
function wc_pb_get_bundled_order_items( $container_order_item, $order, $return_ids = false ) {

	$bundled_order_items = array();

	if ( wc_pb_is_bundle_container_order_item( $container_order_item ) ) {

		$bundled_cart_keys = unserialize( $container_order_item[ 'bundled_items' ] );

		if ( ! empty( $bundled_cart_keys ) && is_array( $bundled_cart_keys ) ) {

			$order_items = is_object( $order ) ? $order->get_items( 'line_item' ) : $order;

			foreach ( $order_items as $order_item_id => $order_item ) {

				$is_child = false;

				if ( isset( $order_item[ 'bundle_cart_key' ] ) ) {
					$is_child = in_array( $order_item[ 'bundle_cart_key' ], $bundled_cart_keys ) ? true : false;
				} else {
					$is_child = isset( $order_item[ 'stamp' ] ) && $order_item[ 'stamp' ] == $container_order_item[ 'stamp' ] && isset( $order_item[ 'bundled_by' ] ) ? true : false;
				}

				if ( $is_child ) {
					$bundled_order_items[ $order_item_id ] = $order_item;
				}
			}
		}
	}

	return $return_ids ? array_keys( $bundled_order_items ) : $bundled_order_items;
}

/**
 * True if an order item is part of a bundle.
 * Instead of relying solely on the existence of item meta, the function also checks that the alleged parent item actually exists.
 *
 * @since  5.0.0
 *
 * @param  array     $order_item
 * @param  WC_Order  $order
 * @return boolean
 */
function wc_pb_is_bundled_order_item( $order_item, $order ) {

	$is_bundled = false;

	if ( wc_pb_get_bundled_order_item_container( $order_item, $order ) ) {
		$is_bundled = true;
	}

	return $is_bundled;
}

/**
 * True if an order item appears to be part of a bundle.
 * The result is purely based on item meta - the function does not check that a valid parent item actually exists.
 *
 * @since  5.0.0
 *
 * @param  array  $order_item
 * @return boolean
 */
function wc_pb_maybe_is_bundled_order_item( $order_item ) {

	$is_bundled = false;

	if ( ! empty( $order_item[ 'bundled_by' ] ) ) {
		$is_bundled = true;
	}

	return $is_bundled;
}

/**
 * True if an order item appears to be a bundle container item.
 *
 * @since  5.0.0
 *
 * @param  array  $order_item
 * @return boolean
 */
function wc_pb_is_bundle_container_order_item( $order_item ) {

	$is_bundle = false;

	if ( isset( $order_item[ 'bundled_items' ] ) ) {
		$is_bundle = true;
	}

	return $is_bundle;
}
