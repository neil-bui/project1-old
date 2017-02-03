<?php
/**
 * WC_PB_Helpers class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Product Bundles
 * @since    4.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Bundle Helper Functions.
 *
 * @class    WC_PB_Helpers
 * @version  5.0.0
 * @since    4.0.0
 */
class WC_PB_Helpers {

	/**
	 * Runtime cache for simple storage.
	 *
	 * @var array
	 */
	public static $cache = array();

	/**
	 * Simple runtime cache getter.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public static function cache_get( $key ) {
		$value = null;
		if ( isset( self::$cache[ $key ] ) ) {
			$value = self::$cache[ $key ];
		}
		return $value;
	}

	/**
	 * Simple runtime cache setter.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public static function cache_set( $key, $value ) {
		self::$cache[ $key ] = $value;
	}

	/**
	 * True when processing a FE request.
	 *
	 * @return boolean
	 */
	public static function is_front_end() {
		$is_fe = ( ! is_admin() ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		return $is_fe;
	}

	/**
	 * Loads variation ids for a given variable product.
	 *
	 * @param  int  $item_id
	 * @return array
	 */
	public static function get_product_variations( $item_id ) {

		$transient_name = 'wc_product_children_' . $item_id;
		$transient      = get_transient( $transient_name );
		$variations     = isset( $transient[ 'all' ] ) && is_array( $transient[ 'all' ] ) ? $transient[ 'all' ] : false;

        if ( false === $variations ) {

			$args = array(
				'post_type'   => 'product_variation',
				'post_status' => array( 'publish' ),
				'numberposts' => -1,
				'orderby'     => 'menu_order',
				'order'       => 'asc',
				'post_parent' => $item_id,
				'fields'      => 'ids'
			);

			$variations = get_posts( $args );
		}

		return $variations;
	}

	/**
	 * Return a formatted product title based on id.
	 *
	 * @param  mixed  $product_id
	 * @return string
	 */
	public static function get_product_title( $product, $suffix = '' ) {

		if ( is_object( $product ) ) {
			$title = $product->get_title();
			$sku   = $product->get_sku();
		} else {
			$title = get_the_title( $product );
			$sku   = get_post_meta( $product, '_sku', true );
		}

		if ( $suffix ) {
			$title = sprintf( _x( '%1$s %2$s', 'product title followed by suffix', 'woocommerce-product-bundles' ), $title, $suffix );
		}

		if ( $sku ) {
			$sku = sprintf( __( 'SKU: %s', 'woocommerce-product-bundles' ), $sku );
		} else {
			$sku = '';
		}

		return self::format_product_title( $title, $sku, '', true );
	}

	/**
	 * Return a formatted product title based on variation id.
	 *
	 * @param  int  $item_id
	 * @return string
	 */
	public static function get_product_variation_title( $variation_id ) {

		if ( is_object( $variation_id ) ) {
			$variation = $variation_id;
		} else {
			$variation = wc_get_product( $variation_id );
		}

		if ( ! $variation ) {
			return false;
		}

		if ( WC_PB_Core_Compatibility::is_wc_version_gte_2_5() ) {
			$description = $variation->get_formatted_variation_attributes( true );
		} else {
			$description = wc_get_formatted_variation( $variation->get_variation_attributes(), true );
		}

		$title = $variation->get_title();
		$sku   = $variation->get_sku();

		if ( $sku ) {
			$identifier = $sku;
		} else {
			$identifier = '#' . $variation->variation_id;
		}

		return self::format_product_title( $title, $identifier, $description );
	}

	/**
	 * Format a product title.
	 *
	 * @param  string   $title
	 * @param  string   $sku
	 * @param  string   $meta
	 * @param  boolean  $paren
	 * @return string
	 */
	public static function format_product_title( $title, $sku = '', $meta = '', $paren = false ) {

		if ( $sku && $meta ) {
			if ( $paren ) {
				$title = sprintf( _x( '%1$s &mdash; %2$s (%3$s)', 'product title followed by meta and sku in parenthesis', 'woocommerce-product-bundles' ), $title, $meta, $sku );
			} else {
				$title = sprintf( _x( '%1$s &ndash; %2$s &mdash; %3$s', 'sku followed by product title and meta', 'woocommerce-product-bundles' ), $sku, $title, $meta );
			}
		} elseif ( $sku ) {
			if ( $paren ) {
				$title = sprintf( _x( '%1$s (%2$s)', 'product title followed by sku in parenthesis', 'woocommerce-product-bundles' ), $title, $sku );
			} else {
				$title = sprintf( _x( '%1$s &ndash; %2$s', 'sku followed by product title', 'woocommerce-product-bundles' ), $sku, $title );
			}
		} elseif ( $meta ) {
			if ( $paren ) {
				$title = sprintf( _x( '%1$s (%2$s)', 'product title followed by meta in parenthesis', 'woocommerce-product-bundles' ), $title, $meta );
			} else {
				$title = sprintf( _x( '%1$s &mdash; %2$s', 'product title followed by meta', 'woocommerce-product-bundles' ), $title, $meta );
			}
		}

		return $title;
	}

	/**
	 * Format a product title incl qty, price and suffix.
	 *
	 * @param  string  $title
	 * @param  string  $qty
	 * @param  string  $price
	 * @param  string  $suffix
	 * @return string
	 */
	public static function format_product_shop_title( $title, $qty = '', $price = '', $suffix = '' ) {

		$quantity_string = '';
		$price_string    = '';
		$suffix_string   = '';

		if ( $qty ) {
			$quantity_string = sprintf( _x( ' &times; %s', 'qty string', 'woocommerce-product-bundles' ), $qty );
		}

		if ( $price ) {
			$price_string = sprintf( _x( ' &ndash; %s', 'price suffix', 'woocommerce-product-bundles' ), $price );
		}

		if ( $suffix ) {
			$suffix_string = sprintf( _x( ' &ndash; %s', 'suffix', 'woocommerce-product-bundles' ), $suffix );
		}

		$title_string = sprintf( _x( '%1$s%2$s%3$s%4$s', 'title, quantity, price, suffix', 'woocommerce-product-bundles' ), $title, $quantity_string, $price_string, $suffix_string );

		return $title_string;
	}
}
