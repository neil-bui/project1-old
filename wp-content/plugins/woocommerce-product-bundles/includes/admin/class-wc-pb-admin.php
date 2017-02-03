<?php
/**
 * WC_PB_Admin class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Product Bundles
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Bundles Admin Class.
 *
 * Loads admin scripts, includes admin classes and adds admin hooks.
 *
 * @class    WC_PB_Admin
 * @version  5.0.0
 */
class WC_PB_Admin {

	/**
	 * Setup Admin class.
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'admin_init' ) );

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ), 11 );

		// Template override scan path.
		add_filter( 'woocommerce_template_overrides_scan_paths', array( __CLASS__, 'template_scan_path' ) );

		// Add "Insufficient Stock" report tab.
		add_filter( 'woocommerce_admin_reports', array( __CLASS__, 'add_insufficient_stock_report_tab' ) );
	}

	/**
	 * Admin init.
	 */
	public static function admin_init() {
		self::includes();
	}

	/**
	 * Inclusions.
	 */
	public static function includes() {

		require_once( 'class-wc-pb-admin-post-types.php' );
		require_once( 'meta-boxes/class-wc-pb-meta-box-product-data.php' );
		require_once( 'class-wc-pb-admin-ajax.php' );
	}

	/**
	 * Admin writepanel scripts.
	 */
	public static function admin_scripts() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( WC_PB_Core_Compatibility::is_wc_version_gte_2_2() ) {
			$writepanel_dependency = 'wc-admin-meta-boxes';
		} else {
			$writepanel_dependency = 'woocommerce_admin_meta_boxes';
		}

		wp_register_script( 'wc-pb-admin-product-panel', WC_PB()->plugin_url() . '/assets/js/wc-pb-admin-write-panels' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', $writepanel_dependency ), WC_PB()->version );
		wp_register_style( 'wc-pb-admin-css', WC_PB()->plugin_url() . '/assets/css/wc-pb-admin.css', array(), WC_PB()->version );
		wp_register_style( 'wc-pb-admin-product-css', WC_PB()->plugin_url() . '/assets/css/wc-pb-admin-write-panels.css', array( 'woocommerce_admin_styles' ), WC_PB()->version );
		wp_register_style( 'wc-pb-admin-edit-order-css', WC_PB()->plugin_url() . '/assets/css/wc-pb-admin-edit-order.css', array( 'woocommerce_admin_styles' ), WC_PB()->version );

		wp_enqueue_style( 'wc-pb-admin-css' );

		// Get admin screen id.
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// WooCommerce admin pages.
		if ( in_array( $screen_id, array( 'product' ) ) ) {
			wp_enqueue_script( 'wc-pb-admin-product-panel' );

			$params = array(
				'add_bundled_product_nonce' => wp_create_nonce( 'wc_bundles_add_bundled_product' ),
				'is_wc_version_gte_2_3'     => WC_PB_Core_Compatibility::is_wc_version_gte_2_3() ? 'yes' : 'no',
				'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
				'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
			);

			wp_localize_script( 'wc-pb-admin-product-panel', 'wc_bundles_admin_params', $params );
		}

		if ( in_array( $screen_id, array( 'edit-product', 'product' ) ) ) {
			wp_enqueue_style( 'wc-pb-admin-product-css' );
		}

		if ( $screen_id === 'edit-product' ) {
			wc_enqueue_js( "
				jQuery( function( $ ) {
					jQuery( '.show_insufficient_stock_items' ).on( 'click', function() {
						var anchor = jQuery( this ),
							panel  = jQuery( this ).parent().find( '.insufficient_stock_items' );

						if ( anchor.hasClass( 'closed' ) ) {
							anchor.removeClass( 'closed' );
							panel.show();
						} else {
							anchor.addClass( 'closed' );
							panel.hide();
						}
						return false;
					} );
				} );
			" );
		}

		if ( in_array( $screen_id, array( 'shop_order', 'edit-shop_order' ) ) ) {
			wp_enqueue_style( 'wc-pb-admin-edit-order-css' );
		}
	}

	/**
	 * Support scanning for template overrides in extension.
	 *
	 * @param  array  $paths
	 * @return array
	 */
	public static function template_scan_path( $paths ) {

		$paths[ 'WooCommerce Product Bundles' ] = WC_PB()->plugin_path() . '/templates/';

		return $paths;
	}

	/**
	 * Adds an "Insufficient stock" tab to the WC stock reports.
	 *
	 * @param  array  $reports
	 * @return array
	 */
	public static function add_insufficient_stock_report_tab( $reports ) {

		$reports[ 'stock' ][ 'reports' ][ 'insufficient_stock' ] = array(
			'title'       => __( 'Insufficient stock', 'woocommerce-product-bundles' ),
			'description' => '',
			'hide_title'  => true,
			'callback'    => array( __CLASS__, 'get_insufficient_stock_report_content' )
		);

		return $reports;
	}

	/**
	 * Renders the "Insufficient stock" report content.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public static function get_insufficient_stock_report_content( $name ) {

		require_once( 'reports/class-wc-pb-report-insufficient-stock.php' );

		$report = new WC_PB_Report_Insufficient_Stock;
		$report->output_report();
	}
}

WC_PB_Admin::init();
