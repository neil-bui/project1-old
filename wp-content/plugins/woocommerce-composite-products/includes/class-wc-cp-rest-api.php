<?php
/**
 * WC_CP_REST_API class
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
 * Add custom REST API fields.
 *
 * @class    WC_CP_REST_API
 * @version  3.8.0
 */
class WC_CP_REST_API {

	/**
	 * Custom REST API product field names, indicating support for getting/updating.
	 * @var array
	 */
	private static $product_fields = array(
		'composite_components' => array( 'get' ),
		'composite_scenarios'  => array( 'get' ),
		'composite_layout'     => array( 'get', 'update' )
	);

	/**
	 * Setup order class.
	 */
	public static function init() {

		// Register WP REST API custom product fields.
		add_action( 'rest_api_init', array( __CLASS__, 'register_product_fields' ), 0 );

		// Filter WP REST API order line item fields.
		add_action( 'rest_api_init', array( __CLASS__, 'filter_order_fields' ), 0 );

		// Clear reserved price meta when saving, depending on type.
		add_action( 'woocommerce_rest_insert_product', array( __CLASS__, 'delete_reserved_price_meta' ) );

		// Hooks to add WC v1-v3 API custom order fields.
		self::add_legacy_hooks();
	}

	/**
	 * Filters REST API order responses to add custom data.
	 */
	public static function filter_order_fields() {

		// Filter WC REST API order response content to add composite container/children references.
		add_filter( 'woocommerce_rest_prepare_shop_order', array( __CLASS__, 'filter_order_response' ), 10, 3 );
		add_filter( 'woocommerce_rest_shop_order_schema', array( __CLASS__, 'filter_order_schema' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Products.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Register custom REST API fields for product requests.
	 */
	public static function register_product_fields() {

		foreach ( self::$product_fields as $field_name => $field_supports ) {

			$args = array(
				'schema' => self::get_product_field_schema( $field_name )
			);

			if ( in_array( 'get', $field_supports ) ) {
				$args[ 'get_callback' ] = array( __CLASS__, 'get_product_field_value' );
			}
			if ( in_array( 'update', $field_supports ) ) {
				$args[ 'update_callback' ] = array( __CLASS__, 'update_product_field_value' );
			}

			register_rest_field( 'product', $field_name, $args );
		}
	}

	/**
	 * Gets extended (unprefixed) schema properties for products.
	 *
	 * @return array
	 */
	private static function get_extended_product_schema() {

		return array(
			'composite_layout'     => array(
				'description' => __( 'Single-product template layout. Applicable to composite-type products.', 'woocommerce-composite-products' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'composite_components' => array(
				'description' => __( 'List of components that this product consists of. Applicable to composite-type products.', 'woocommerce-composite-products' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'id'                   => array(
						'description' => __( 'Component ID.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'title'                => array(
						'description' => __( 'Title of the component.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'description'          => array(
						'description' => __( 'Description of the component.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'query_type'           => array(
						'description' => __( 'Query type associated with component options.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'enum'        => array( 'product_ids', 'category_ids' ),
						'readonly'    => true,
					),
					'query_ids'            => array(
						'description' => __( 'Product IDs or category IDs to use for populating component options.', 'woocommerce-composite-products' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'default_option_id'    => array(
						'description' => __( 'The product ID of the default/pre-selected component opion.', 'woocommerce-composite-products' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'thumbnail_id'         => array(
						'description' => __( 'The attachment ID of the thumbnail associated with this Component.', 'woocommerce-composite-products' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'quantity_min'         => array(
						'description' => __( 'Minimum component quantity.', 'woocommerce-composite-products' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'quantity_max'         => array(
						'description' => __( 'Maximum component quantity.', 'woocommerce-composite-products' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'priced_individually'  => array(
						'description' => __( 'Indicates whether the price of this component is added to the base price of the composite.', 'woocommerce-composite-products' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipped_individually' => array(
						'description' => __( 'Indicates whether this component is shipped separately from the composite.', 'woocommerce-composite-products' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'optional'             => array(
						'description' => __( 'Indicates whether the component is optional.', 'woocommerce-composite-products' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'discount'             => array(
						'description' => __( 'Discount applied to the component, applicable when the Priced Individually option is enabled.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'options_style'        => array(
						'description' => __( 'Indicates which template to use for displaying component options.', 'woocommerce-composite-products' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'enum'        => array( 'dropdowns', 'thumbnails', 'radios' ),
						'readonly'    => true,
					)
				)
			),
			'composite_scenarios'  => array(
				'description' => __( 'Scenarios data. Applicable to composite-type products.', 'woocommerce-composite-products' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'id'           => array(
						'description' => __( 'Scenario ID.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'name'         => array(
						'description' => __( 'Name of the scenario.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'description'  => array(
						'description' => __( 'Optional short description of the scenario.', 'woocommerce-composite-products' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'data'         => array(
						'description' => __( 'Scenario data.', 'woocommerce-composite-products' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'component_id'      => array(
								'description' => __( 'Component ID.', 'woocommerce-composite-products' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'component_options' => array(
								'description' => __( 'Product/variation IDs in component targeted by the scenario (0 = any product or variation, -1 = no selection)', 'woocommerce-composite-products' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'options_modifier'  => array(
								'description' => __( 'Comparison modifier for the referenced product/variation IDs.', 'woocommerce-composite-products' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'enum'        => array( 'in', 'not-in', 'masked' ),
								'readonly'    => true,
							)
						)
					),
					'actions_data' => array(
						'description' => __( 'Scenario actions data.', 'woocommerce-composite-products' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true
					)
				)
			)
		);
	}

	/**
	 * Gets schema properties for CP product fields.
	 *
	 * @param  string  $field_name
	 * @return array
	 */
	public static function get_product_field_schema( $field_name ) {

		$extended_schema = self::get_extended_product_schema();
		$field_schema    = isset( $extended_schema[ $field_name ] ) ? $extended_schema[ $field_name ] : null;

		return $field_schema;
	}

	/**
	 * Gets values for CP product fields.
	 *
	 * @param  array            $response
	 * @param  string           $field_name
	 * @param  WP_REST_Request  $request
	 * @return array
	 */
	public static function get_product_field_value( $response, $field_name, $request ) {

		$data = null;

		if ( isset( $response[ 'id' ] ) ) {
			$product = wc_get_product( $response[ 'id' ] );
			$data    = self::get_product_field( $field_name, $product );
		}

		return $data;
	}

	/**
	 * Updates values for CP product fields.
	 *
	 * @param  mixed   $value
	 * @param  mixed   $response
	 * @param  string  $field_name
	 * @return array
	 */
	public static function update_product_field_value( $field_value, $response, $field_name ) {

		$product_id = false;

		if ( $response instanceof WP_Post ) {
			$product_id   = absint( $response->ID );
			$terms        = get_the_terms( $product_id, 'product_type' );
			$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
		} elseif ( $response instanceof WC_Product ) {
			$product_id   = WC_CP_Core_Compatibility::get_id( $response );
			$product_type = $response->get_type();
		}

		// Only possible to set fields of 'composite' type products.
		if ( $product_id && 'composite' === $product_type ) {
			switch ( $field_name ) {
				case 'composite_layout' :
					update_post_meta( $product_id, '_bto_style', wc_clean( $field_value ) );
				break;
			}
		}

		return true;
	}

	/**
	 * Gets composite-specific product data.
	 *
	 * @since  3.7.0
	 *
	 * @param  string      $key
	 * @param  WC_Product  $product
	 * @return array
	 */
	private static function get_product_field( $key, $product ) {

		$product_type = $product->get_type();

		switch ( $key ) {

			case 'composite_components' :

				$value = array();

				if ( 'composite' === $product_type ) {

					$components = $product->get_components();

					if ( ! empty( $components ) ) {
						foreach ( $components as $component ) {
							$value[] = array(
								'id'                   => (string) $component->get_id(),
								'title'                => $component->get_title(),
								'description'          => $component->get_description(),
								'query_type'           => isset( $component[ 'query_type' ] ) ? $component[ 'query_type' ] : 'product_ids',
								'query_ids'            => 'category_ids' === $component[ 'query_type' ] ? (array) $component[ 'assigned_category_ids' ] : (array) $component[ 'assigned_ids' ],
								'default_option_id'    => $component->get_default_option(),
								'thumbnail_id'         => isset( $component[ 'thumbnail_id' ] ) ? $component[ 'thumbnail_id' ] : '',
								'quantity_min'         => $component->get_quantity( 'min' ),
								'quantity_max'         => $component->get_quantity( 'max' ),
								'priced_individually'  => $component->is_priced_individually(),
								'shipped_individually' => $component->is_shipped_individually(),
								'optional'             => $component->is_optional(),
								'discount'             => $component->get_discount()
							);
						}
					}
				}

			break;

			case 'composite_layout' :

				$value = '';

				if ( 'composite' === $product_type ) {
					$value = $product->bto_style;
				}

			break;

			case 'composite_scenarios' :

				$value = array();

				if ( 'composite' === $product_type ) {
					$scenarios = $product->get_scenario_meta();

					foreach ( $scenarios as $id => $data ) {

						$scenario_data = array();

						foreach ( $data[ 'component_data' ] as $component_id => $component_data ) {
							$scenario_data[] = array(
								'component_id'      => $component_id,
								'component_options' => $component_data,
								'options_modifier'  => isset( $data[ 'modifier' ][ $component_id ] ) ? $data[ 'modifier' ][ $component_id ] : 'in'
							);
						}

						$value[] = array(
							'id'           => (string) $id,
							'name'         => $data[ 'title' ],
							'description'  => $data[ 'description' ],
							'data'         => $scenario_data,
							'actions_data' => $data[ 'scenario_actions' ]
						);
					}
				}

			break;
		}

		return $value;
	}


	/*
	|--------------------------------------------------------------------------
	| Orders.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Gets extended (unprefixed) schema properties for order line items.
	 *
	 * @return array
	 */
	private static function get_extended_order_line_item_schema() {

		return array(
			'composite_parent'   => array(
				'description' => __( 'ID of parent line item, applicable if the item is part of a composite.', 'woocommerce-composite-products' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'composite_children' => array(
				'description' => __( 'IDs of composited line items, applicable if the item is a composite container.', 'woocommerce-composite-products' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			)
		);
	}

	/**
	 * Adds 'composite_parent' and 'composite_children' schema properties to line items.
	 *
	 * @param  array  $schema
	 * @return array
	 */
	public static function filter_order_schema( $schema ) {

		foreach ( self::get_extended_order_line_item_schema() as $field_name => $field_content ) {
			$schema[ 'line_items' ][ 'properties' ][ $field_name ] = $field_content;
		}

		return $schema;
	}

	/**
	 * Filters WC REST API order responses to add references between composite container/children items. Also modifies expanded product data based on the pricing and shipping settings.
	 *
	 * @since  3.7.0
	 *
	 * @param  WP_REST_Response  $response
	 * @param  WP_Post           $post
	 * @param  WP_REST_Request   $request
	 * @return WP_REST_Response
	 */
	public static function filter_order_response( $response, $post, $request ) {

		if ( $response instanceof WP_HTTP_Response ) {

			$order_data = $response->get_data();
			$order      = wc_get_order( $post );
			$order_data = self::get_extended_order_data( $order_data, $order );

			$response->set_data( $order_data );
		}

		return $response;
	}

	/**
	 * Append CP data to order data.
	 *
	 * @param  array     $order_data
	 * @param  WC_Order  $order
	 * @return array
	 */
	private static function get_extended_order_data( $order_data, $order ) {

		if ( ! empty( $order_data[ 'line_items' ] ) ) {

			$order_items = $order->get_items();

			foreach ( $order_data[ 'line_items' ] as $order_data_item_index => $order_data_item ) {

				// Default values.
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'composite_parent' ]   = '';
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'composite_children' ] = array();

				$order_data_item_id = $order_data_item[ 'id' ];

				// Add relationship references.
				if ( ! isset( $order_items[ $order_data_item_id ] ) ) {
					continue;
				}

				$parent_id    = wc_cp_get_composited_order_item_container( $order_items[ $order_data_item_id ], $order, true );
				$children_ids = wc_cp_get_composited_order_items( $order_items[ $order_data_item_id ], $order, true );

				if ( false !== $parent_id ) {
					$order_data[ 'line_items' ][ $order_data_item_index ][ 'composite_parent' ] = $parent_id;
				} elseif ( ! empty( $children_ids ) ) {
					$order_data[ 'line_items' ][ $order_data_item_index ][ 'composite_children' ] = $children_ids;
				} else {
					continue;
				}

				// Modify product data.
				if ( ! isset( $order_data_item[ 'product_data' ] ) ) {
					continue;
				}

				add_filter( 'woocommerce_get_product_from_item', array( WC_CP()->order, 'get_product_from_item' ), 10, 3 );
				$product = $order->get_product_from_item( $order_items[ $order_data_item_id ] );
				remove_filter( 'woocommerce_get_product_from_item', array( WC_CP()->order, 'get_product_from_item' ), 10, 3 );

				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'price' ]                  = $product->get_price();
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'sale_price' ]             = $product->get_sale_price() ? $product->get_sale_price() : null;
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'regular_price' ]          = $product->get_regular_price();

				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'shipping_required' ]      = $product->needs_shipping();

				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'weight' ]                 = $product->get_weight() ? $product->get_weight() : null;
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'dimensions' ][ 'length' ] = $product->length;
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'dimensions' ][ 'width' ]  = $product->width;
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'product_data' ][ 'dimensions' ][ 'height' ] = $product->height;
			}
		}

		return $order_data;
	}

	/**
	 * Filters WC v1-v3 REST API order response content to add composite container/children item references.
	 */
	private static function add_legacy_hooks() {
		add_filter( 'woocommerce_api_order_response', array( __CLASS__, 'legacy_order_response' ), 10, 4 );
	}

	/**
	 * Filters WC v1-v3 REST API order responses to add references between composite container/children items. Also modifies expanded product data based on the pricing and shipping settings.
	 *
	 * @param  array          $order_data
	 * @param  WC_Order       $order
	 * @param  array          $fields
	 * @param  WC_API_Server  $server
	 * @return array
	 */
	public static function legacy_order_response( $order_data, $order, $fields, $server ) {

		$order_data = self::get_extended_order_data( $order_data, $order );

		return $order_data;
	}

	/**
	 * Delete price meta reserved to bundles/composites.
	 *
	 * @param  int  $post_id
	 * @return void
	 */
	public static function delete_reserved_price_meta( $post ) {

		if ( isset( $post->ID ) ) {

			// Get product type.
			$post_id      = $post->ID;
			$terms        = get_the_terms( $post_id, 'product_type' );
			$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';

			if ( false === in_array( $product_type, array( 'bundle', 'composite' ) ) ) {
				delete_post_meta( $post_id, '_wc_sw_max_regular_price' );
				delete_post_meta( $post_id, '_wc_sw_max_price' );
			}
		}
	}
}

WC_CP_REST_API::init();
