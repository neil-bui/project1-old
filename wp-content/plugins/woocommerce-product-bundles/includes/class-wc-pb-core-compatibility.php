<?php
/**
 * WC_PB_Core_Compatibility class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Product Bundles
 * @since    4.7.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions for WC core back-compatibility.
 *
 * @class  WC_PB_Core_Compatibility
 * @since  4.7.6
 */
class WC_PB_Core_Compatibility {

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @since  4.7.6
	 *
	 * @return string
	 */
	private static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.7 or greater.
	 *
	 * @since  5.0.0
	 *
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_7() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.7', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.6 or greater.
	 *
	 * @since  5.0.0
	 *
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_6() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.6', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.5 or greater.
	 *
	 * @since  4.10.2
	 *
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.4 or greater.
	 *
	 * @since  4.10.2
	 *
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_4() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.4', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.3 or greater.
	 *
	 * @since  4.7.6
	 *
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_3() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.3', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.2 or greater.
	 *
	 * @since  4.7.6
	 *
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is less than 2.2.
	 *
	 * @since  4.7.6
	 *
	 * @return boolean
	 */
	public static function is_wc_version_lt_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '<' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @since  4.7.6
	 *
	 * @param  string  $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
	}

	/**
	 * Get the WC Product instance for a given product ID or post.
	 *
	 * get_product() is soft-deprecated in WC 2.2
	 *
	 * @since 4.7.6
	 *
	 * @param  bool|int|string|WP_Post  $the_product
	 * @param  array                    $args
	 * @return WC_Product
	 */
	public static function wc_get_product( $the_product = false, $args = array() ) {

		if ( self::is_wc_version_gte_2_2() ) {
			return wc_get_product( $the_product, $args );
		} else {

			return get_product( $the_product, $args );
		}
	}

	/**
	 * Get all product cats for a product by ID, including hierarchy.
	 *
	 * @since  4.13.1
	 *
	 * @param  int  $product_id
	 * @return array
	 */
	public static function wc_get_product_cat_ids( $product_id ) {

		if ( self::is_wc_version_gte_2_5() ) {
			$product_cats = wc_get_product_cat_ids( $product_id );
		} else {

			$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( "fields" => "ids" ) );

			foreach ( $product_cats as $product_cat ) {
				$product_cats = array_merge( $product_cats, get_ancestors( $product_cat, 'product_cat' ) );
			}
		}

		return $product_cats;
	}

	/**
	 * Wrapper for wp_get_post_terms which supports ordering by parent.
	 *
	 * @since  4.13.1
	 *
	 * @param  int     $product_id
	 * @param  string  $taxonomy
	 * @param  array   $args
	 * @return array
	 */
	public static function wc_get_product_terms( $product_id, $attribute_name, $args ) {

		if ( self::is_wc_version_gte_2_3() ) {
			return wc_get_product_terms( $product_id, $attribute_name, $args );
		} else {

			$orderby = wc_attribute_orderby( sanitize_title( $attribute_name ) );

			switch ( $orderby ) {
				case 'name' :
					$args = array( 'orderby' => 'name', 'hide_empty' => false, 'menu_order' => false );
				break;
				case 'id' :
					$args = array( 'orderby' => 'id', 'order' => 'ASC', 'menu_order' => false );
				break;
				case 'menu_order' :
					$args = array( 'menu_order' => 'ASC' );
				break;
			}

			$terms = get_terms( sanitize_title( $attribute_name ), $args );

			return $terms;
		}
	}

	/**
	 * Get rounding precision.
	 *
	 * @since  4.14.6
	 *
	 * @return int
	 */
	public static function wc_get_rounding_precision( $price_decimals = false ) {
		if ( false === $price_decimals ) {
			$price_decimals = wc_get_price_decimals();
		}
		return absint( $price_decimals ) + 2;
	}

	/**
	 * Return the number of decimals after the decimal point.
	 *
	 * @since  4.13.1
	 *
	 * @return int
	 */
	public static function wc_get_price_decimals() {

		if ( self::is_wc_version_gte_2_3() ) {
			return wc_get_price_decimals();
		} else {
			return absint( get_option( 'woocommerce_price_num_decimals', 2 ) );
		}
	}

	/**
	 * Output a list of variation attributes for use in the cart forms.
	 *
	 * @since 4.13.1
	 *
	 * @param array  $args
	 */
	public static function wc_dropdown_variation_attribute_options( $args = array() ) {
		return wc_dropdown_variation_attribute_options( $args );
	}

	/**
	 * Display a WooCommerce help tip.
	 *
	 * @since  4.14.0
	 *
	 * @param  string  $tip
	 * @return string
	 */
	public static function wc_help_tip( $tip ) {

		if ( self::is_wc_version_gte_2_5() ) {
			return wc_help_tip( $tip );
		} else {
			return '<img class="help_tip woocommerce-help-tip" data-tip="' . $tip . '" src="' . WC()->plugin_url() . '/assets/images/help.png" />';
		}
	}

	/**
	 * Back-compat wrapper for 'wc_variation_attribute_name'.
	 *
	 * @since  5.0.2
	 *
	 * @param  string  $attribute_name
	 * @return string
	 */
	public static function wc_variation_attribute_name( $attribute_name ) {
		if ( self::is_wc_version_gte_2_6() ) {
			return wc_variation_attribute_name( $attribute_name );
		} else {
			return 'attribute_' . sanitize_title( $attribute_name );
		}
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once..
	 *
	 * @since  5.0.0
	 *
	 * @param  string  $group
	 * @return string
	 */
	public static function wc_cache_helper_get_cache_prefix( $group ) {

		if ( self::is_wc_version_gte_2_5() ) {
			return WC_Cache_Helper::get_cache_prefix( $group );
		} else {
			// Get cache key - uses cache key wc_orders_cache_prefix to invalidate when needed
			$prefix = wp_cache_get( 'wc_' . $group . '_cache_prefix', $group );

			if ( false === $prefix ) {
				$prefix = 1;
				wp_cache_set( 'wc_' . $group . '_cache_prefix', $prefix, $group );
			}

			return 'wc_cache_' . $prefix . '_';
		}
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @since  5.0.0
	 *
	 * @param  string  $group
	 */
	public static function wc_cache_helper_incr_cache_prefix( $group ) {
		if ( self::is_wc_version_gte_2_5() ) {
			WC_Cache_Helper::incr_cache_prefix( $group );
		} else {
			wp_cache_incr( 'wc_' . $group . '_cache_prefix', 1, $group );
		}
	}
}
