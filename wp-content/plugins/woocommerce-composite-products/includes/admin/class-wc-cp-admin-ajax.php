<?php
/**
 * WC_CP_Admin_Ajax class
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
 * Admin AJAX meta-box handlers.
 *
 * @class     WC_CP_Admin_Ajax
 * @version   3.7.0
 * @since     3.7.0
 */
class WC_CP_Admin_Ajax {

	/**
	 * Hook in.
	 */
	public static function init() {

		// Ajax save composite config.
		add_action( 'wp_ajax_woocommerce_bto_composite_save', array( __CLASS__, 'ajax_composite_save' ) );

		// Ajax add component.
		add_action( 'wp_ajax_woocommerce_add_composite_component', array( __CLASS__, 'ajax_add_component' ) );

		// Ajax add scenario.
		add_action( 'wp_ajax_woocommerce_add_composite_scenario', array( __CLASS__, 'ajax_add_scenario' ) );

		// Ajax search default component id.
		add_action( 'wp_ajax_woocommerce_json_search_default_component_option', array( __CLASS__, 'json_search_default_component_option' ) );

		// Ajax search products and variations in scenarios.
		add_action( 'wp_ajax_woocommerce_json_search_component_options_in_scenario', array( __CLASS__, 'json_search_component_options_in_scenario' ) );
	}

	/**
	 * Handles saving composite config via ajax.
	 *
	 * @return void
	 */
	public static function ajax_composite_save() {

		check_ajax_referer( 'wc_bto_save_composite', 'security' );

		parse_str( $_POST[ 'data' ], $posted_composite_data );

		$post_id = absint( $_POST[ 'post_id' ] );

		WC_CP_Meta_Box_Product_Data::save_configuration( $post_id, $posted_composite_data );

		wc_delete_product_transients( $post_id );

		wp_send_json( WC_CP_Meta_Box_Product_Data::$ajax_notices );
	}

	/**
	 * Handles adding components via ajax.
	 *
	 * @return void
	 */
	public static function ajax_add_component() {

		check_ajax_referer( 'wc_bto_add_component', 'security' );

		$id      = intval( $_POST[ 'id' ] );
		$post_id = intval( $_POST[ 'post_id' ] );

		$component_data = array();

		/**
		 * Action 'woocommerce_composite_component_admin_html'.
		 *
		 * @param  int     $id
		 * @param  array   $component_data
		 * @param  int     $post_id
		 * @param  string  $state
		 *
		 * @hooked {@see component_admin_html} - 10
		 */
		do_action( 'woocommerce_composite_component_admin_html', $id, $component_data, $post_id, 'open' );

		die();
	}

	/**
	 * Handles adding scenarios via ajax.
	 *
	 * @return void
	 */
	public static function ajax_add_scenario() {

		check_ajax_referer( 'wc_bto_add_scenario', 'security' );

		$id             = intval( $_POST[ 'id' ] );
		$post_id        = intval( $_POST[ 'post_id' ] );

		$composite_data = get_post_meta( $post_id, '_bto_data', true );
		$scenario_data  = array();

		/**
		 * Action 'woocommerce_composite_scenario_admin_html'.
		 *
		 * @param  int     $id
		 * @param  array   $scenario_data
		 * @param  array   $composite_data
		 * @param  int     $post_id
		 * @param  string  $state
		 *
		 * @hooked {@see scenario_admin_html} - 10
		 */
		do_action( 'woocommerce_composite_scenario_admin_html', $id, $scenario_data, $composite_data, $post_id, 'open' );

		die();
	}

	/**
	 * Search for default component option and echo json.
	 *
	 * @return void
	 */
	public static function json_search_default_component_option() {
		self::json_search_component_options();
	}

	/**
	 * Search for default component option and echo json.
	 *
	 * @return void
	 */
	public static function json_search_component_options_in_scenario() {
		self::json_search_component_options( 'search_component_options_in_scenario', $post_types = array( 'product', 'product_variation' ) );
	}

	/**
	 * Search for component options and echo json.
	 *
	 * @param   string $x (default: '')
	 * @param   string $post_types (default: array('product'))
	 * @return  void
	 */
	public static function json_search_component_options( $x = 'default', $post_types = array( 'product' ) ) {

		global $wpdb;

		ob_start();

		check_ajax_referer( 'search-products', 'security' );

		$term         = (string) wc_clean( stripslashes( $_GET[ 'term' ] ) );
		$like_term    = '%' . $wpdb->esc_like( $term ) . '%';

		$composite_id = $_GET[ 'composite_id' ];
		$component_id = $_GET[ 'component_id' ];

		if ( empty( $term ) || empty( $composite_id ) || empty( $component_id ) ) {
			die();
		}

		$composite_data = get_post_meta( $composite_id, '_bto_data', true );
		$component_data = isset( $composite_data[ $component_id ] ) ? $composite_data[ $component_id ] : false;

		if ( false == $composite_data || false == $component_data ) {
			die();
		}

		// Run query to get component option ids.
		$component_options = WC_CP_Component::query_component_options( $component_data );

		// Add variation ids to component option ids.
		if ( $x === 'search_component_options_in_scenario' ) {
			$variations_args = array(
				'post_type'      => array( 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'post_parent'    => array_merge( array( '0' ), $component_options ),
				'fields'         => 'ids'
			);

			$component_options_variations = get_posts( $variations_args );
			$component_options            = array_merge( $component_options, $component_options_variations );
		}

		if ( is_numeric( $term ) ) {

			$query = $wpdb->prepare( "
				SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE posts.post_status = 'publish'
				AND (
					posts.post_parent = %s
					OR posts.ID = %s
					OR posts.post_title LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
			", $term, $term, $term, $like_term );

		} else {

			$query = $wpdb->prepare( "
				SELECT ID FROM {$wpdb->posts} posts LEFT JOIN {$wpdb->postmeta} postmeta ON posts.ID = postmeta.post_id
				WHERE posts.post_status = 'publish'
				AND (
					posts.post_title LIKE %s
					or posts.post_content LIKE %s
					OR (
						postmeta.meta_key = '_sku' AND postmeta.meta_value LIKE %s
					)
				)
			", $like_term, $like_term, $like_term );
		}

		$query .= " AND posts.post_type IN ('" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "')";

		// Include results among component options only.
		$query .= " AND posts.ID IN (" . implode( ',', array_map( 'intval', $component_options ) ) . ")";

		// Include first 1000 results only.
		$query .= " LIMIT 1000";

		$posts          = array_unique( $wpdb->get_col( $query ) );
		$found_products = array();

		if ( $posts ) {
			foreach ( $posts as $post ) {

				$product = wc_get_product( $post );

				if ( $product->get_type() === 'variation' ) {
					$found_products[ $post ] = WC_CP_Helpers::get_product_variation_title( $product );
				} else {
					if ( $x === 'search_component_options_in_scenario' && $product->get_type() === 'variable' ) {
						$found_products[ $post ] = WC_CP_Helpers::get_product_title( $product ) . ' ' . __( '&mdash; All Variations', 'woocommerce-composite-products' );
					} else {
						$found_products[ $post ] = WC_CP_Helpers::get_product_title( $product );
					}
				}
			}
		}

		wp_send_json( $found_products );
	}
}

WC_CP_Admin_Ajax::init();
