<?php
/**
 * WC_CP_Core_Compatibility class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Composite Products
 * @since    3.5.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Functions related to WC core backwards compatibility.
 *
 * @class    WC_CP_Core_Compatibility
 * @version  3.8.0
 */
class WC_CP_Core_Compatibility {

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @since 3.0.0
	 * @return string
	 */
	private static function get_wc_version() {

		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.5 or greater.
	 *
	 * @since 3.2.0
	 * @return boolean
	 */
	public static function use_wc_ajax() {
		return apply_filters( 'woocommerce_composite_use_wc_ajax', self::is_wc_version_gte_2_4() );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.7 or greater.
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_7() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.7', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.6 or greater.
	 *
	 * @since 3.6.5
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_6() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.6', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.5 or greater.
	 *
	 * @since 3.5.0
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_5() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.5', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.4 or greater.
	 *
	 * @since 3.2.0
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_4() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.4', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.3 or greater.
	 *
	 * @since 3.0.0
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_3() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.3', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is 2.2 or greater.
	 *
	 * @since 3.0.0
	 * @return boolean
	 */
	public static function is_wc_version_gte_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '>=' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is less than 2.2.
	 *
	 * @since 3.0.0
	 * @return boolean
	 */
	public static function is_wc_version_lt_2_2() {
		return self::get_wc_version() && version_compare( self::get_wc_version(), '2.2', '<' );
	}

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @since 3.0.0
	 * @param string $version the version to compare
	 * @return boolean
	 */
	public static function is_wc_version_gt( $version ) {
		return self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
	}

	/**
	 * Get the WC Product instance for a given product ID or post.
	 *
	 * get_product() is soft-deprecated in WC 2.2.
	 *
	 * @since  3.0.0
	 * @param  bool|int|string|WP_Post $the_product
	 * @param  array                   $args
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
	 * Wrapper for wp_get_post_terms which supports ordering by parent.
	 *
	 * @since  3.5.2
	 * @param  int $product_id
	 * @param  string $taxonomy
	 * @param  array  $args
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
	 * WC_Product_Variable::get_variation_default_attribute() back-compat wrapper.
	 *
	 * @since  3.5.2
	 * @return string
	 */
	public static function wc_get_variation_default_attribute( $product, $attribute_name ) {

		if ( self::is_wc_version_gte_2_4() ) {
			return $product->get_variation_default_attribute( $attribute_name );
		} else {

			$defaults       = $product->get_variation_default_attributes();
			$attribute_name = sanitize_title( $attribute_name );

			return isset( $defaults[ $attribute_name ] ) ? $defaults[ $attribute_name ] : '';
		}
	}

	/**
	 * Output a list of variation attributes for use in the cart forms.
	 *
	 * @since 3.5.2
	 * @param array $args
	 */
	public static function wc_dropdown_variation_attribute_options( $args = array() ) {
		return wc_dropdown_variation_attribute_options( $args );
	}

	/**
	 * Get all product cats for a product by ID, including hierarchy.
	 *
	 * @since  3.5.2
	 * @param  int $product_id
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
	 * Display a WooCommerce help tip.
	 *
	 * @since  3.6.0
	 *
	 * @param  string $tip        Help tip text
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
	 * @since  3.8.0
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
	 * Back-compat wrapper for 'get_parent_id'.
	 *
	 * @since  3.8.0
	 *
	 * @param  WC_Product  $product
	 * @return mixed
	 */
	public static function get_parent_id( $product ) {
		if ( self::is_wc_version_gte_2_7() ) {
			return $product->get_parent_id();
		} else {
			return $product->is_type( 'variation' ) ? absint( $product->id ) : 0;
		}
	}

	/**
	 * Back-compat wrapper for 'get_id'.
	 *
	 * @since  3.8.0
	 *
	 * @param  WC_Product  $product
	 * @return mixed
	 */
	public static function get_id( $product ) {
		if ( self::is_wc_version_gte_2_7() ) {
			return $product->get_id();
		} else {
			return $product->is_type( 'variation' ) ? absint( $product->variation_id ) : absint( $product->id );
		}
	}

	/**
	 * Back-compat wrapper for getting CRUD object props directly.
	 *
	 * @since  3.8.0
	 *
	 * @param  object  $obj
	 * @param  string  $name
	 * @param  string  $context
	 * @return mixed
	 */
	public static function get_prop( $obj, $name, $context = 'view' ) {
		if ( self::is_wc_version_gte_2_7() ) {
			$get_fn = 'get_' . $name;
			return is_callable( array( $obj, $get_fn ) ) ? $obj->$get_fn( $context ) : null;
		} else {

			if ( 'status' === $name ) {
				$value = isset( $obj->post->post_status ) ? $obj->post->post_status : null;
			} elseif ( 'short_description' === $name ) {
				$value = isset( $obj->post->post_excerpt ) ? $obj->post->post_excerpt : null;
			} else {
				$value = $obj->$name;
			}

			return $value;
		}
	}

	/**
	 * Back-compat wrapper for setting CRUD object props directly.
	 *
	 * @since  3.8.0
	 *
	 * @param  WC_Product  $product
	 * @param  string      $name
	 * @param  mixed       $value
	 * @return void
	 */
	public static function set_prop( $obj, $name, $value ) {
		if ( self::is_wc_version_gte_2_7() ) {
			$set_fn = 'set_' . $name;
			$obj->$set_fn( $value );
		} else {
			$obj->$name = $value;
		}
	}

	/**
	 * Back-compat wrapper for 'wc_get_formatted_variation'.
	 *
	 * @since  3.8.0
	 *
	 * @param  WC_Product_Variation  $variation
	 * @param  boolean               $flat
	 * @return string
	 */
	public static function wc_get_formatted_variation( $variation, $flat ) {
		if ( self::is_wc_version_gte_2_7() ) {
			return wc_get_formatted_variation( $variation, $flat );
		} elseif ( self::is_wc_version_gte_2_5() ) {
			return $variation->get_formatted_variation_attributes( $flat );
		} else {
			return wc_get_formatted_variation( $variation->get_variation_attributes(), $flat );
		}
	}

	/**
	 * Get rounding precision.
	 *
	 * @since  3.6.9
	 *
	 * @return int
	 */
	public static function wc_get_rounding_precision( $price_decimals = false ) {
		if ( false === $price_decimals ) {
			$price_decimals = wc_cp_price_num_decimals();
		}
		return absint( $price_decimals ) + 2;
	}
}
