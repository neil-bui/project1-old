<?php
/**
 * WC_PB_Product_Prices class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Product Bundles
 * @since    5.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Price functions and hooks.
 *
 * @class    WC_PB_Product_Prices
 * @version  5.0.0
 * @since    5.0.0
 */
class WC_PB_Product_Prices {

	/**
	 * Bundled item whose prices are currently being filtered.
	 *
	 * @var WC_Bundled_Item
	 */
	public static $bundled_item;

	/**
	 * Filters the 'woocommerce_price_num_decimals' option to use the internal WC rounding precision.
	 */
	public static function extend_price_display_precision() {
		add_filter( 'option_woocommerce_price_num_decimals', array( 'WC_PB_Core_Compatibility', 'wc_get_rounding_precision' ) );
	}

	/**
	 * Reset applied filters to the 'woocommerce_price_num_decimals' option.
	 */
	public static function reset_price_display_precision() {
		remove_filter( 'option_woocommerce_price_num_decimals', array( 'WC_PB_Core_Compatibility', 'wc_get_rounding_precision' ) );
	}

	/**
	 * Calculates bundled product prices incl. or excl. tax depending on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  WC_Product  $product
	 * @param  double      $price
	 * @return double
	 */
	public static function get_product_display_price( $product, $price ) {

		if ( ! $price ) {
			return $price;
		}

		if ( get_option( 'woocommerce_tax_display_shop' ) === 'excl' ) {
			$product_price = $product->get_price_excluding_tax( 1, $price );
		} else {
			$product_price = $product->get_price_including_tax( 1, $price );
		}

		return $product_price;
	}

	/**
	 * Returns the recurring price component of a subscription product.
	 *
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function get_recurring_price_html_component( $product ) {

		$sync_date = $product->subscription_payment_sync_date;
		$product->subscription_payment_sync_date = 0;

		$sub_price_html = WC_Subscriptions_Product::get_price_string( $product, array( 'price' => '%s', 'sign_up_fee' => false ) );

		$product->subscription_payment_sync_date = $sync_date;

		return $sub_price_html;
	}


	/**
	 * Add price filters to modify child product prices depending on the bundled item pricing setup.
	 *
	 * @param  WC_Bundled_Item  $bundled_item
	 */
	public static function add_price_filters( $bundled_item ) {

		self::$bundled_item = $bundled_item;

		add_filter( 'woocommerce_get_price', array( __CLASS__, 'filter_get_price' ), 15, 2 );
		add_filter( 'woocommerce_get_sale_price', array( __CLASS__, 'filter_get_sale_price' ), 15, 2 );
		add_filter( 'woocommerce_get_regular_price', array( __CLASS__, 'filter_get_regular_price' ), 15, 2 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_get_price_html' ), 10, 2 );
		add_filter( 'woocommerce_show_variation_price', array( __CLASS__, 'filter_show_variation_price' ), 10, 3 );
		add_filter( 'woocommerce_get_variation_price_html', array( __CLASS__, 'filter_get_price_html' ), 10, 2 );
		add_filter( 'woocommerce_variation_prices', array( __CLASS__, 'filter_get_variation_prices' ), 10, 3 );

		/**
		 * 'woocommerce_bundled_product_price_filters_added' hook.
		 *
		 * @param  WC_Bundled_Item  $bundled_item
		 */
		do_action( 'woocommerce_bundled_product_price_filters_added', $bundled_item );
	}

	/**
	 * Remove price filters after modifying child product prices depending on the bundled item pricing setup.
	 */
	public static function remove_price_filters() {

		remove_filter( 'woocommerce_get_price', array( __CLASS__, 'filter_get_price' ), 15, 2 );
		remove_filter( 'woocommerce_get_sale_price', array( __CLASS__, 'filter_get_sale_price' ), 15, 2 );
		remove_filter( 'woocommerce_get_regular_price', array( __CLASS__, 'filter_get_regular_price' ), 15, 2 );
		remove_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_get_price_html' ), 10, 2 );
		remove_filter( 'woocommerce_show_variation_price', array( __CLASS__, 'filter_show_variation_price' ), 10, 3 );
		remove_filter( 'woocommerce_get_variation_price_html', array( __CLASS__, 'filter_get_price_html' ), 10, 2 );
		remove_filter( 'woocommerce_variation_prices', array( __CLASS__, 'filter_get_variation_prices' ), 10, 3 );

		/**
		 * 'woocommerce_bundled_product_price_filters_added' hook.
		 *
		 * @param  WC_Bundled_Item  $bundled_item
		 */
		do_action( 'woocommerce_bundled_product_price_filters_removed', self::$bundled_item );

		self::$bundled_item = false;
	}

	/**
	 * Filter get_variation_prices() calls for bundled products to include discounts.
	 *
	 * @param  array                $prices_array
	 * @param  WC_Product_Variable  $product
	 * @param  boolean              $display
	 * @return array
	 */
	public static function filter_get_variation_prices( $prices_array, $product, $display ) {

		$bundled_item = self::$bundled_item;

		if ( $bundled_item ) {

			$prices         = array();
			$regular_prices = array();
			$sale_prices    = array();

			/** @var Documented in 'WC_Bundled_Item::sync_price'. */
			$discount_from_regular = apply_filters( 'woocommerce_bundled_item_discount_from_regular', true, $bundled_item );
			$discount              = $bundled_item->get_discount();
			$priced_per_product    = $bundled_item->is_priced_individually();
			$valid_children        = $bundled_item->get_children();

			// Filter regular prices.
			foreach ( $prices_array[ 'regular_price' ] as $variation_id => $regular_price ) {

				if ( ! in_array( $variation_id, $valid_children ) ) {
					continue;
				}

				if ( $priced_per_product ) {
					$regular_prices[ $variation_id ] = $regular_price === '' ? $prices_array[ 'price' ][ $variation_id ] : $regular_price;
				} else {
					$regular_prices[ $variation_id ] = 0;
				}
			}

			// Filter prices.
			foreach ( $prices_array[ 'price' ] as $variation_id => $price ) {

				if ( ! in_array( $variation_id, $valid_children ) ) {
					continue;
				}

				if ( $priced_per_product ) {
					if ( $discount_from_regular ) {
						$regular_price = $regular_prices[ $variation_id ];
					} else {
						$regular_price = $price;
					}
					$price                   = empty( $discount ) ? $price : round( ( double ) $regular_price * ( 100 - $discount ) / 100, wc_get_price_decimals() );
					$prices[ $variation_id ] = apply_filters( 'woocommerce_bundled_variation_price', $price, $variation_id, $discount, $bundled_item );
				} else {
					$prices[ $variation_id ] = 0;
				}
			}

			// Filter sale prices.
			foreach ( $prices_array[ 'sale_price' ] as $variation_id => $sale_price ) {

				if ( ! in_array( $variation_id, $valid_children ) ) {
					continue;
				}

				if ( $priced_per_product ) {
					$sale_prices[ $variation_id ] = empty( $discount ) ? $sale_price : $prices[ $variation_id ];
				} else {
					$sale_prices[ $variation_id ] = 0;
				}
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
	 * Filter condition that allows WC to calculate variation price_html.
	 *
	 * @param  boolean               $show
	 * @param  WC_Product_Variable   $product
	 * @param  WC_Product_Variation  $variation
	 * @return boolean
	 */
	public static function filter_show_variation_price( $show, $product, $variation ) {

		$bundled_item = self::$bundled_item;

		if ( $bundled_item ) {

			$show = false;

			if ( $bundled_item->is_priced_individually() && $bundled_item->is_price_visible( 'product' ) ) {
				$show = true;
			}
		}

		return $show;
	}

	/**
	 * Filter get_price() calls for bundled products to include discounts.
	 *
	 * @param  double      $price
	 * @param  WC_Product  $product
	 * @return double
	 */
	public static function filter_get_price( $price, $product ) {

		$bundled_item = self::$bundled_item;

		if ( $bundled_item ) {

			if ( $price === '' ) {
				return $price;
			}

			if ( ! $bundled_item->is_priced_individually() ) {
				return 0;
			}

			/** Documented in 'WC_Bundled_Item::sync_prices()'. */
			if ( apply_filters( 'woocommerce_bundled_item_discount_from_regular', true, $bundled_item ) ) {
				$regular_price = $product->get_regular_price();
			} else {
				$regular_price = $price;
			}

			$discount                    = $bundled_item->get_discount();
			$bundled_item_price          = empty( $discount ) ? $price : ( empty( $regular_price ) ? $regular_price : round( ( double ) $regular_price * ( 100 - $discount ) / 100, wc_get_price_decimals() ) );

			$product->bundled_item_price = $bundled_item_price;

			/** Documented in 'WC_Bundled_Item::get_raw_price()'. */
			$price = apply_filters( 'woocommerce_bundled_item_price', $bundled_item_price, $product, $discount, $bundled_item );
		}

		return $price;
	}

	/**
	 * Filter get_regular_price() calls for bundled products to include discounts.
	 *
	 * @param  double      $price
	 * @param  WC_Product  $product
	 * @return double
	 */
	public static function filter_get_regular_price( $regular_price, $product ) {

		$bundled_item = self::$bundled_item;

		if ( $bundled_item ) {

			if ( ! $bundled_item->is_priced_individually() ) {
				return 0;
			}

			$regular_price = empty( $regular_price ) ? $product->price : $regular_price;
		}

		return $regular_price;
	}

	/**
	 * Filter get_sale_price() calls for bundled products to include discounts.
	 *
	 * @param  double      $price
	 * @param  WC_Product  $product
	 * @return double
	 */
	public static function filter_get_sale_price( $sale_price, $product ) {

		$bundled_item = self::$bundled_item;

		if ( $bundled_item ) {

			if ( ! $bundled_item->is_priced_individually() ) {
				return 0;
			}

			$discount   = $bundled_item->get_discount();
			$sale_price = empty( $discount ) ? $sale_price : self::filter_get_price( $product->price, $product );
		}

		return $sale_price;
	}

	/**
	 * Filter the html price string of bundled items to show the correct price with discount and tax - needs to be hidden when the bundled item is priced individually.
	 *
	 * @param  string      $price_html
	 * @param  WC_Product  $product
	 * @return string
	 */
	public static function filter_get_price_html( $price_html, $product ) {

		$bundled_item = self::$bundled_item;

		if ( $bundled_item ) {

			if ( ! $bundled_item->is_priced_individually() ) {
				return '';
			}

			if ( ! $bundled_item->is_price_visible( 'product' ) ) {
				return '';
			}

			$quantity = $bundled_item->get_quantity();

			/**
			 * 'woocommerce_bundled_item_price_html' filter.
			 *
			 * @param  string           $price_html
			 * @param  WC_Bundled_Item  $bundled_item
			 */
			$price_html = apply_filters( 'woocommerce_bundled_item_price_html', $quantity > 1 ? sprintf( __( '%1$s <span class="bundled_item_price_quantity">/ pc.</span>', 'woocommerce-product-bundles' ), $price_html, $quantity ) : $price_html, $price_html, $bundled_item );
		}

		return $price_html;
	}
}
