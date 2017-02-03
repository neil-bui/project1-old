<?php
/**
 * WC_CP_Products class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Composite Products
 * @since    3.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API functions to support product modifications when contained in Composites.
 *
 * @class    WC_CP_Products
 * @version  3.8.1
 */
class WC_CP_Products {

	/**
	 * Composited product being filtered - @see 'add_filters'.
	 * @var WC_CP_Product|false
	 */
	public static $filtered_component_option = false;

	/**
	 * Setup hooks.
	 */
	public static function init() {

		// Reset query cache when clearing product transients.
		add_action( 'woocommerce_delete_product_transients', array( __CLASS__, 'delete_cp_query_transients' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| API Methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Add filters to modify products when contained in Composites.
	 *
	 * @param  WC_CP_Product  $product
	 * @return void
	 */
	public static function add_filters( $component_option ) {

		self::$filtered_component_option = $component_option;

		add_filter( 'woocommerce_get_price', array( __CLASS__, 'filter_show_product_get_price' ), 16, 2 );
		add_filter( 'woocommerce_get_regular_price', array( __CLASS__, 'filter_show_product_get_regular_price' ), 16, 2 );
		add_filter( 'woocommerce_get_sale_price', array( __CLASS__, 'filter_show_product_get_sale_price' ), 16, 2 );
		add_filter( 'woocommerce_variation_prices', array( __CLASS__, 'filter_get_variation_prices' ), 16, 2 );

		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_show_product_get_price_html' ), 5, 2 );
		add_filter( 'woocommerce_get_variation_price_html', array( __CLASS__, 'filter_show_product_get_price_html' ), 5, 2 );

		add_filter( 'woocommerce_available_variation', array( __CLASS__, 'filter_available_variation' ), 10, 3 );
		add_filter( 'woocommerce_show_variation_price', array( __CLASS__, 'filter_show_variation_price' ), 10, 3 );

		add_filter( 'woocommerce_bundles_update_price_meta', array( __CLASS__, 'filter_show_product_bundles_update_price_meta' ), 10, 2 );
		add_filter( 'woocommerce_bundle_contains_priced_items', array( __CLASS__, 'filter_bundle_contains_priced_items' ), 10, 2 );
		add_filter( 'woocommerce_bundled_item_is_priced_individually', array( __CLASS__, 'filter_bundled_item_is_priced_individually' ), 10, 2 );
		add_filter( 'woocommerce_bundled_item_raw_price_cart', array( __CLASS__, 'filter_bundled_item_raw_price_cart' ), 10, 4 );

		add_filter( 'woocommerce_nyp_html', array( __CLASS__, 'filter_show_product_get_nyp_price_html' ), 15, 2 );

		/**
		 * Action 'woocommerce_composite_products_apply_product_filters'.
		 *
		 * @param  WC_Product            $product
		 * @param  string                $component_id
		 * @param  WC_Product_Composite  $composite
		 */
		do_action( 'woocommerce_composite_products_apply_product_filters', $component_option->get_product(), $component_option->get_component_id(), $component_option->get_composite() );
	}

	/**
	 * Remove filters - @see 'add_filters'.
	 *
	 * @return void
	 */
	public static function remove_filters() {

		/**
		 * Action 'woocommerce_composite_products_remove_product_filters'.
		 */
		do_action( 'woocommerce_composite_products_remove_product_filters' );

		self::$filtered_component_option = false;

		remove_filter( 'woocommerce_get_price', array( __CLASS__, 'filter_show_product_get_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_regular_price', array( __CLASS__, 'filter_show_product_get_regular_price' ), 16, 2 );
		remove_filter( 'woocommerce_get_sale_price', array( __CLASS__, 'filter_show_product_get_sale_price' ), 16, 2 );
		remove_filter( 'woocommerce_variation_prices', array( __CLASS__, 'filter_get_variation_prices' ), 16, 2 );

		remove_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_show_product_get_price_html' ), 5, 2 );
		remove_filter( 'woocommerce_get_variation_price_html', array( __CLASS__, 'filter_show_product_get_price_html' ), 5, 2 );

		remove_filter( 'woocommerce_available_variation', array( __CLASS__, 'filter_available_variation' ), 10, 3 );
		remove_filter( 'woocommerce_show_variation_price', array( __CLASS__, 'filter_show_variation_price' ), 10, 3 );

		remove_filter( 'woocommerce_bundles_update_price_meta', array( __CLASS__, 'filter_show_product_bundles_update_price_meta' ), 10, 2 );
		remove_filter( 'woocommerce_bundle_contains_priced_items', array( __CLASS__, 'filter_bundle_contains_priced_items' ), 10, 2 );
		remove_filter( 'woocommerce_bundled_item_is_priced_individually', array( __CLASS__, 'filter_bundled_item_is_priced_individually' ), 10, 2 );
		remove_filter( 'woocommerce_bundled_item_raw_price_cart', array( __CLASS__, 'filter_bundled_item_raw_price_cart' ), 10, 4 );

		remove_filter( 'woocommerce_nyp_html', array( __CLASS__, 'filter_show_product_get_nyp_price_html' ), 15, 2 );
	}

	/**
	 * Get the shop price of a product incl or excl tax, depending on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  WC_Product  $product
	 * @param  double      $price
	 * @return double
	 */
	public static function get_product_display_price( $product, $price = '' ) {

		if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_4() ) {
			return $product->get_display_price( $price );
		}

		if ( ! $price ) {
			return $price;
		}

		if ( wc_cp_tax_display_shop() === 'excl' ) {
			$product_price = $product->get_price_excluding_tax( 1, $price );
		} else {
			$product_price = $product->get_price_including_tax( 1, $price );
		}

		return $product_price;
	}


	/*
	|--------------------------------------------------------------------------
	| Hooks.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Filter get_variation_prices() calls for bundled products to include discounts.
	 *
	 * @param  array                $prices_array
	 * @param  WC_Product_Variable  $product
	 * @return array
	 */
	public static function filter_get_variation_prices( $prices_array, $product ) {

		$filtered_component_option = self::$filtered_component_option;

		if ( ! empty( $filtered_component_option  ) ) {

			$prices         = array();
			$regular_prices = array();
			$sale_prices    = array();

			$discount           = $filtered_component_option->get_discount();
			$priced_per_product = $filtered_component_option->is_priced_individually();

			// Filter regular prices.
			foreach ( $prices_array[ 'regular_price' ] as $variation_id => $regular_price ) {

				if ( $priced_per_product ) {
					$regular_prices[ $variation_id ] = $regular_price === '' ? $prices_array[ 'price' ][ $variation_id ] : $regular_price;
				} else {
					$regular_prices[ $variation_id ] = 0;
				}
			}

			// Filter prices.
			foreach ( $prices_array[ 'price' ] as $variation_id => $price ) {

				if ( $priced_per_product ) {
					if ( false === $filtered_component_option->is_discount_allowed_on_sale_price() ) {
						$regular_price = $regular_prices[ $variation_id ];
					} else {
						$regular_price = $price;
					}
					$price                   = empty( $discount ) ? $price : round( ( double ) $regular_price * ( 100 - $discount ) / 100, wc_get_price_decimals() );
					$prices[ $variation_id ] = apply_filters( 'woocommerce_composited_variation_price', $price, $variation_id, $discount, $filtered_component_option );
				} else {
					$prices[ $variation_id ] = 0;
				}
			}

			// Filter sale prices.
			foreach ( $prices_array[ 'sale_price' ] as $variation_id => $sale_price ) {

				if ( $priced_per_product ) {
					$sale_prices[ $variation_id ] = empty( $discount ) ? $sale_price : $prices[ $variation_id ];
				} else {
					$sale_prices[ $variation_id ] = 0;
				}
			}

			if ( false === $filtered_component_option->is_discount_allowed_on_sale_price() ) {
				asort( $prices );
			}

			$prices_array = array(
				'price'         => $prices,
				'regular_price' => $regular_prices,
				'sale_price'    => $sale_prices
			);
		}

		return $prices_array;
	}

	/**
	 * Filters variation data in the show_product function.
	 *
	 * @param  mixed                 $variation_data
	 * @param  WC_Product            $bundled_product
	 * @param  WC_Product_Variation  $bundled_variation
	 * @return mixed
	 */
	public static function filter_available_variation( $variation_data, $product, $variation ) {

		if ( ! empty( self::$filtered_component_option ) ) {

			// Add/modify price data.

			WC_CP_Helpers::extend_price_display_precision();
			$price_incl_tax                        = $variation->get_price_including_tax( 1, 1000 );
			$price_excl_tax                        = $variation->get_price_excluding_tax( 1, 1000 );
			WC_CP_Helpers::reset_price_display_precision();

			$variation_data[ 'price' ]             = $variation->get_price();
			$variation_data[ 'regular_price' ]     = $variation->get_regular_price();

			$variation_data[ 'price_tax' ]         = $price_incl_tax / $price_excl_tax;

			// Add/modify availability data.

			$quantity_min                          = self::$filtered_component_option->get_quantity_min();
			$quantity_max                          = self::$filtered_component_option->get_quantity_max( true, $variation );
			$availability                          = self::$filtered_component_option->get_availability( $variation );
			$availability_html                     = empty( $availability[ 'availability' ] ) ? '' : '<p class="stock ' . esc_attr( $availability[ 'class' ] ) . '">' . wp_kses_post( $availability[ 'availability' ] ) . '</p>';

			$variation_data[ 'availability_html' ] = apply_filters( 'woocommerce_stock_html', $availability_html, $availability[ 'availability' ], $variation );

			$variation_data[ 'min_qty' ]           = $quantity_min;
			$variation_data[ 'max_qty' ]           = $quantity_max;

			if ( ! $variation->is_in_stock() || ! $variation->has_enough_stock( $variation_data[ 'min_qty' ] ) ) {
				$variation_data[ 'is_in_stock' ] = false;
			}
		}

		return $variation_data;
	}

	/**
	 * Filter condition that allows WC to calculate variation price_html.
	 *
	 * @param  boolean               $show
	 * @param  WC_Product_Variable   $product
	 * @param  WC_Product_Variation  $variation
	 * @return boolean
	 */
	public static function filter_show_variation_price( $show, $product, $variation ) {

		if ( ! empty( self::$filtered_component_option ) ) {

			$show = false;

			if ( self::$filtered_component_option->is_priced_individually() && false === self::$filtered_component_option->get_component()->hide_selected_option_price() ) {
				$show = true;
			}
		}

		return $show;
	}

	/**
	 * Components discounts should not trigger bundle price updates.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public static function filter_show_product_bundles_update_price_meta( $update, $bundle ) {
		return false;
	}

	/**
	 * Filter 'woocommerce_bundle_is_composited'.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public static function filter_bundle_is_composited( $is, $bundle ) {
		return true;
	}

	/**
	 * If a component is not priced individually, this should force bundled items to return a zero price.
	 *
	 * @param  boolean          $is
	 * @param  WC_Bundled_Item  $bundled_item
	 * @return boolean
	 */
	public static function filter_bundled_item_is_priced_individually( $is_priced_individually, $bundled_item ) {

		if ( ! empty( self::$filtered_component_option ) ) {
			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				$is_priced_individually = false;
			}
		}

		return $is_priced_individually;
	}

	/**
	 * If a component is not priced individually, this should force bundled items to return a zero price.
	 *
	 * @param  boolean            $is
	 * @param  WC_Product_Bundle  $bundle
	 * @return boolean
	 */
	public static function filter_bundle_contains_priced_items( $contains, $bundle ) {

		if ( ! empty( self::$filtered_component_option ) ) {
			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				$contains = false;
			}
		}

		return $contains;
	}

	/**
	 * Filters get_price_html to include component discounts.
	 *
	 * @param  string      $price_html
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function filter_show_product_get_price_html( $price_html, $product ) {

		if ( ! empty( self::$filtered_component_option ) ) {

			// Tells NYP to back off.
			$product->is_filtered_price_html = 'yes';

			if ( ! self::$filtered_component_option->is_priced_individually() || self::$filtered_component_option->get_component()->hide_component_option_prices() ) {

				$price_html = '';

			} else {

				$add_suffix = true;

				// Don't add /pc suffix to products in composited bundles (possibly duplicate).
				$filtered_product = self::$filtered_component_option->get_product();
				if ( $filtered_product->id != $product->id ) {
					$add_suffix = false;
				}

				if ( $add_suffix ) {
					$suffix     = self::$filtered_component_option->get_quantity_min() > 1 ? ' ' . __( '/ pc.', 'woocommerce-composite-products' ) : '';
					$price_html = $price_html . $suffix;
				}
			}

			$price_html = apply_filters( 'woocommerce_composited_item_price_html', $price_html, $product, self::$filtered_component_option->get_component_id(), self::$filtered_component_option->get_composite_id() );
		}

		return $price_html;
	}

	/**
	 * Filters get_price_html to hide nyp prices in static pricing mode.
	 *
	 * @param  string      $price_html
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function filter_show_product_get_nyp_price_html( $price_html, $product ) {

		if ( ! empty( self::$filtered_component_option ) ) {
			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				$price_html = '';
			}
		}

		return $price_html;
	}

	/**
	 * Filters get_price to include component discounts.
	 *
	 * @param  double      $price
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function filter_show_product_get_price( $price, $product ) {

		if ( ! empty( self::$filtered_component_option ) ) {

			if ( $price === '' ) {
				return $price;
			}

			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				return (double) 0;
			}

			if ( false === self::$filtered_component_option->is_discount_allowed_on_sale_price() ) {
				$regular_price = $product->get_regular_price();
			} else {
				$regular_price = $price;
			}

			if ( $discount = self::$filtered_component_option->get_discount() ) {
				$price = empty( $regular_price ) ? $regular_price : round( (double) $regular_price * ( 100 - $discount ) / 100, wc_cp_price_num_decimals() );
			}
		}

		return $price;
	}

	/**
	 * Filters get_regular_price to include component discounts.
	 *
	 * @param  double      $price
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function filter_show_product_get_regular_price( $price, $product ) {

		$filtered_component_option = self::$filtered_component_option;

		if ( ! empty( $filtered_component_option  ) ) {

			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				return (double) 0;
			}

			if ( empty( $price ) ) {
				self::$filtered_component_option = false;
				$price = $product->get_price();
				self::$filtered_component_option = $filtered_component_option;
			}

		}

		return $price;
	}

	/**
	 * Filters get_sale_price to include component discounts.
	 *
	 * @param  double      $price
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function filter_show_product_get_sale_price( $price, $product ) {

		if ( ! empty( self::$filtered_component_option ) ) {

			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				return (double) 0;
			}

			if ( self::$filtered_component_option->get_discount() ) {
				$price = $product->get_price();
			}
		}

		return $price;
	}

	/**
	 * Filters 'woocommerce_bundled_item_raw_price_cart' to include component + bundled item discounts.
	 *
	 * @param  double           $price
	 * @param  WC_Product       $product
	 * @param  mixed            $bundled_discount
	 * @param  WC_Bundled_Item  $bundled_item
	 * @return string
	 */
	public static function filter_bundled_item_raw_price_cart( $price, $product, $bundled_discount, $bundled_item ) {

		if ( ! empty( self::$filtered_component_option ) ) {

			if ( $price === '' ) {
				return $price;
			}

			if ( ! self::$filtered_component_option->is_priced_individually() ) {
				return (double) 0;
			}

			if ( false === self::$filtered_component_option->is_discount_allowed_on_sale_price() ) {
				$regular_price = $product->regular_price;
			} else {
				$regular_price = $price;
			}

			if ( $discount = self::$filtered_component_option->get_discount() ) {
				$price = empty( $regular_price ) ? $regular_price : round( (double) $regular_price * ( 100 - $discount ) / 100, wc_cp_price_num_decimals() );
			}
		}

		return $price;
	}

	/**
	 * Delete component options query cache on product save.
	 *
	 * @param  int   $post_id
	 * @return void
	 */
	public static function delete_cp_query_transients( $post_id ) {
		if ( $post_id > 0 ) {
			delete_transient( 'wc_cp_query_results_' . $post_id );
		}
	}
}

WC_CP_Products::init();

