<?php
/**
 * WC_CP_Admin class
 *
 * @author   SomewhereWarm <sw@somewherewarm.net>
 * @package  WooCommerce Composite Products
 * @since    2.2.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup admin hooks.
 *
 * @class    WC_CP_Admin
 * @version  3.7.0
 */
class WC_CP_Admin {

	/**
	 * Setup admin hooks.
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'admin_init' ) );

		// Admin jquery.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'composite_admin_scripts' ) );

		// Template override scan path.
		add_filter( 'woocommerce_template_overrides_scan_paths', array( __CLASS__, 'composite_template_scan_path' ) );
	}

	/**
	 * Admin init.
	 */
	public static function admin_init() {
		self::includes();
	}

	/**
	 * Include classes.
	 */
	public static function includes() {
		require_once( 'meta-boxes/class-wc-cp-meta-box-product-data.php' );
		require_once( 'class-wc-cp-admin-ajax.php' );
	}

	/**
	 * Include scripts.
	 */
	public static function composite_admin_scripts() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( WC_CP_Core_Compatibility::is_wc_version_gte_2_2() ) {
			$writepanel_dependency = 'wc-admin-meta-boxes';
		} else {
			$writepanel_dependency = 'woocommerce_admin_meta_boxes';
		}

		wp_register_script( 'wc_composite_writepanel', WC_CP()->plugin_url() . '/assets/js/wc-composite-write-panels' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', $writepanel_dependency ), WC_CP()->version );
		wp_register_style( 'wc_composite_admin_css', WC_CP()->plugin_url() . '/assets/css/wc-composite-admin.css', array(), WC_CP()->version );
		wp_register_style( 'wc_composite_writepanel_css', WC_CP()->plugin_url() . '/assets/css/wc-composite-write-panels.css', array( 'woocommerce_admin_styles' ), WC_CP()->version );
		wp_register_style( 'wc_composite_edit_order_css', WC_CP()->plugin_url() . '/assets/css/wc-composite-edit-order.css', array( 'woocommerce_admin_styles' ), WC_CP()->version );

		wp_enqueue_style( 'wc_composite_admin_css' );

		// Get admin screen id.
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// WooCommerce admin pages.
		if ( in_array( $screen_id, array( 'product' ) ) ) {
			wp_enqueue_script( 'wc_composite_writepanel' );

			$params = array(
				'save_composite_nonce'        => wp_create_nonce( 'wc_bto_save_composite' ),
				'add_component_nonce'         => wp_create_nonce( 'wc_bto_add_component' ),
				'add_scenario_nonce'          => wp_create_nonce( 'wc_bto_add_scenario' ),
				'i18n_no_default'             => __( 'No default option&hellip;', 'woocommerce-composite-products' ),
				'i18n_all'                    => __( 'Any Product or Variation', 'woocommerce-composite-products' ),
				'i18n_none'                   => _x( 'No selection', 'optional component property controlled in scenarios', 'woocommerce-composite-products' ),
				'i18n_matches_1'              => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
				'i18n_matches_n'              => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
				'i18n_no_matches'             => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
				'i18n_ajax_error'             => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_1'      => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_short_n'      => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_1'       => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
				'i18n_input_too_long_n'       => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_1'   => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
				'i18n_selection_too_long_n'   => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
				'i18n_load_more'              => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
				'i18n_searching'              => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
				'i18n_choose_component_image' => __( 'Choose a Component Image', 'woocommerce-composite-products' ),
				'i18n_set_component_image'    => __( 'Set Component Image', 'woocommerce-composite-products' ),
				'wc_placeholder_img_src'      => wc_placeholder_img_src(),
				'is_wc_version_gte_2_3'       => WC_CP_Core_Compatibility::is_wc_version_gte_2_3() ? 'yes' : 'no',
			);

			wp_localize_script( 'wc_composite_writepanel', 'wc_composite_admin_params', $params );
		}

		if ( in_array( $screen_id, array( 'edit-product', 'product' ) ) )
			wp_enqueue_style( 'wc_composite_writepanel_css' );

		if ( in_array( $screen_id, array( 'shop_order', 'edit-shop_order' ) ) )
			wp_enqueue_style( 'wc_composite_edit_order_css' );
	}

	/**
	 * Support scanning for template overrides in extension.
	 *
	 * @param  array  $paths
	 * @return array
	 */
	public static function composite_template_scan_path( $paths ) {
		$paths[ 'WooCommerce Composite Products' ] = WC_CP()->plugin_path() . '/templates/';
		return $paths;
	}
}

WC_CP_Admin::init();
