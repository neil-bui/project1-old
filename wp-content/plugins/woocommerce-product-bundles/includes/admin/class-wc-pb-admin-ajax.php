<?php
/**
 * WC_PB_Admin_Ajax class
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
 * Admin AJAX meta-box handlers.
 *
 * @class     WC_PB_Admin_Ajax
 * @version   5.0.0
 * @since     5.0.0
 */
class WC_PB_Admin_Ajax {

	/**
	 * Hook in.
	 */
	public static function init() {

		// Ajax add bundled product.
		add_action( 'wp_ajax_woocommerce_add_bundled_product', array( __CLASS__, 'ajax_add_bundled_product' ) );

		// Ajax search bundled item variations.
		add_action( 'wp_ajax_woocommerce_search_bundled_variations', array( __CLASS__, 'ajax_search_bundled_variations' ) );
	}

	/**
	 * Ajax search for bundled variations.
	 */
	public static function ajax_search_bundled_variations() {

		WC_AJAX::json_search_products( '', array( 'product_variation' ) );
	}

	/**
	 * Handles adding bundled products via ajax.
	 */
	public static function ajax_add_bundled_product() {

		check_ajax_referer( 'wc_bundles_add_bundled_product', 'security' );

		$loop              = intval( $_POST[ 'id' ] );
		$post_id           = intval( $_POST[ 'post_id' ] );
		$product_id        = intval( $_POST[ 'product_id' ] );
		$item_id           = false;
		$toggle            = 'open';
		$tabs              = WC_PB_Meta_Box_Product_Data::get_bundled_product_tabs();

		$item_data         = array();

		$product           = wc_get_product( $product_id );
		$title             = $product->get_title();
		$sku               = $product->get_sku();
		$suffix            = sprintf( _x( '#%s', 'product identifier', 'woocommerce-product-bundles' ), $product_id );
		$title             = WC_PB_Helpers::format_product_title( $title, $sku, $suffix, true );
		$item_availability = '';

		$response          = array(
			'markup'  => '',
			'message' => ''
		);

		if ( $product ) {

			if ( in_array( $product->product_type, array( 'simple', 'variable', 'subscription', 'variable-subscription' ) ) ) {

				if ( ! $product->is_in_stock() ) {
					$item_availability = '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
				}

				ob_start();
				include( 'meta-boxes/views/html-bundled-product-admin.php' );
				$response[ 'markup' ] = ob_get_clean();

			} else {
				$response[ 'message' ] = __( 'The selected product cannot be bundled. Please select a simple product, a variable product, or a simple/variable subscription.', 'woocommerce-product-bundles' );
			}

		} else {
			$response[ 'message' ] = __( 'The selected product is invalid.', 'woocommerce-product-bundles' );
		}

		wp_send_json( $response );
	}
}

WC_PB_Admin_Ajax::init();
