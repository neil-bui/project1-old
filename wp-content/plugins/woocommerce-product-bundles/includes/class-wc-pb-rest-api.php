<?php
/**
 * WC_PB_REST_API class
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
 * Add custom REST API fields.
 *
 * @class    WC_PB_REST_API
 * @version  5.0.0
 */
class WC_PB_REST_API {

	/**
	 * Custom REST API product field names, indicating support for getting/updating.
	 * @var array
	 */
	private static $product_fields = array(
		'bundled_by'    => array( 'get' ),
		'bundled_items' => array( 'get' )
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

		// Filter WC REST API order response content to add bundle container/children references.
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
			'bundled_by'                 => array(
				'description' => __( 'List of product bundle IDs that contain this product.', 'woocommerce-product-bundles' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'bundled_items'              => array(
				'description' => __( 'List of bundled items contained in this product.', 'woocommerce-product-bundles' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'id'                                    => array(
						'description' => __( 'Bundled item ID.', 'woocommerce-product-bundles' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'product_id'                            => array(
						'description' => __( 'Bundled product ID.', 'woocommerce-product-bundles' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'menu_order'                            => array(
						'description' => __( 'Bundled item menu order.', 'woocommerce-product-bundles' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'quantity_min'                          => array(
						'description' => __( 'Minimum bundled item quantity.', 'woocommerce-product-bundles' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'quantity_max'                          => array(
						'description' => __( 'Maximum bundled item quantity.', 'woocommerce-product-bundles' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'priced_individually'                   => array(
						'description' => __( 'Indicates whether the price of this bundled item is added to the base price of the bundle.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'shipped_individually'                  => array(
						'description' => __( 'Indicates whether the bundled product is shipped separately from the bundle.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'override_title'                        => array(
						'description' => __( 'Indicates whether the title of the bundled product is overridden in front-end and e-mail templates.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'title'                                 => array(
						'description' => __( 'Title of the bundled product to display instead of the original product title, if overridden.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'override_description'                  => array(
						'description' => __( 'Indicates whether the short description of the bundled product is overridden in front-end templates.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'description'                           => array(
						'description' => __( 'Short description of the bundled product to display instead of the original product short description, if overridden.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'optional'                              => array(
						'description' => __( 'Indicates whether the bundled item is optional.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'hide_thumbnail'                        => array(
						'description' => __( 'Indicates whether the bundled product thumbnail is hidden in the single-product template.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'discount'                              => array(
						'description' => __( 'Discount applied to the bundled product, applicable when the Priced Individually option is enabled.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'override_variations'                   => array(
						'description' => __( 'Indicates whether variations filtering is active, applicable for variable bundled products only.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'allowed_variations'                    => array(
						'description' => __( 'List of enabled variation IDs, applicable when variations filtering is active.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'override_default_variation_attributes' => array(
						'description' => __( 'Indicates whether the default variation attribute values are overridden, applicable for variable bundled products only.', 'woocommerce-product-bundles' ),
						'type'        => 'boolean',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'default_variation_attributes'          => array(
						'description' => __( 'Overridden default variation attribute values, if applicable.', 'woocommerce-product-bundles' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
						'properties'  => array(
							'id' => array(
								'description' => __( 'Attribute ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Attribute name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'option' => array(
								'description' => __( 'Selected attribute term name.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
					'single_product_visibility'             => array(
						'description' => __( 'Indicates whether the bundled product is visible in the single-product template.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'enum'        => array( 'visible', 'hidden' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'cart_visibility'                       => array(
						'description' => __( 'Indicates whether the bundled product is visible in cart templates.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'enum'        => array( 'visible', 'hidden' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'order_visibility'                      => array(
						'description' => __( 'Indicates whether the bundled product is visible in order/e-mail templates.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'enum'        => array( 'visible', 'hidden' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'single_product_price_visibility'       => array(
						'description' => __( 'Indicates whether the bundled product price is visible in the single-product template, applicable when the Priced Individually option is enabled..', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'enum'        => array( 'visible', 'hidden' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'cart_price_visibility'                 => array(
						'description' => __( 'Indicates whether the bundled product price is visible in cart templates, applicable when the Priced Individually option is enabled..', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'enum'        => array( 'visible', 'hidden' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'order_price_visibility'                => array(
						'description' => __( 'Indicates whether the bundled product price is visible in order/e-mail templates, applicable when the Priced Individually option is enabled..', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'enum'        => array( 'visible', 'hidden' ),
						'context'     => array( 'view', 'edit' ),
						'readonly'    => true,
					),
					'stock_status'                          => array(
						'description' => __( 'Stock status of the bundled item, taking minimum quantity into account.', 'woocommerce-product-bundles' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
						'enum'        => array( 'in_stock', 'on_backorder', 'out_of_stock' ),
						'readonly'    => true,
					)
				)
			)
		);
	}

	/**
	 * Gets schema properties for PB product fields.
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
	 * Gets values for PB product fields.
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
	 * Updates values for PB product fields.
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
			$product_id   = absint( $response->id );
			$product_type = $response->product_type;
		}

		// Only possible to set fields of 'bundle' type products.
		if ( $product_id && 'bundle' === $product_type ) {
			// No fields to set yet :)
		}

		return true;
	}

	/**
	 * Gets bundle-specific product data.
	 *
	 * @since  5.0.0
	 *
	 * @param  string      $key
	 * @param  WC_Product  $product
	 * @return array
	 */
	private static function get_product_field( $key, $product ) {

		$product_type = $product->product_type;

		switch ( $key ) {

			case 'bundled_by' :

				$value = array();

				if ( 'bundle' !== $product_type ) {
					$bundle_ids = array_values( wc_pb_get_bundled_product_map( $product->id ) );
					$value = ! empty( $bundle_ids ) ? $bundle_ids : array();
				}

			break;
			case 'bundled_items' :

				$value = array();

				if ( 'bundle' === $product_type ) {

					$args = array(
						'bundle_id' => $product->id,
						'return'    => 'objects',
						'order_by'  => array( 'menu_order' => 'ASC' )
					);

					$data_items = WC_PB_DB::query_bundled_items( $args );

					if ( ! empty( $data_items ) ) {
						foreach ( $data_items as $data_item ) {
							$value[] = array(
								'bundled_item_id'                       => $data_item->get_id(),
								'product_id'                            => $data_item->get_product_id(),
								'menu_order'                            => $data_item->get_menu_order(),
								'quantity_min'                          => $data_item->get_meta( 'quantity_min' ),
								'quantity_max'                          => $data_item->get_meta( 'quantity_max' ),
								'priced_individually'                   => 'yes' === $data_item->get_meta( 'priced_individually' ),
								'shipped_individually'                  => 'yes' === $data_item->get_meta( 'shipped_individually' ),
								'override_title'                        => 'yes' === $data_item->get_meta( 'override_title' ),
								'title'                                 => $data_item->get_meta( 'title' ),
								'override_description'                  => 'yes' === $data_item->get_meta( 'override_description' ),
								'description'                           => $data_item->get_meta( 'description' ),
								'optional'                              => 'yes' === $data_item->get_meta( 'optional' ),
								'hide_thumbnail'                        => 'yes' === $data_item->get_meta( 'hide_thumbnail' ),
								'discount'                              => $data_item->get_meta( 'discount' ),
								'override_variations'                   => 'yes' === $data_item->get_meta( 'override_variations' ),
								'allowed_variations'                    => (array) $data_item->get_meta( 'allowed_variations' ),
								'override_default_variation_attributes' => 'yes' === $data_item->get_meta( 'override_default_variation_attributes' ),
								'default_variation_attributes'          => self::get_bundled_item_attribute_defaults( $data_item ),
								'single_product_visibility'             => $data_item->get_meta( 'single_product_visibility' ),
								'cart_visibility'                       => $data_item->get_meta( 'cart_visibility' ),
								'order_visibility'                      => $data_item->get_meta( 'order_visibility' ),
								'single_product_price_visibility'       => $data_item->get_meta( 'single_product_price_visibility' ),
								'cart_price_visibility'                 => $data_item->get_meta( 'cart_price_visibility' ),
								'order_price_visibility'                => $data_item->get_meta( 'order_price_visibility' ),
								'stock_status'                          => self::get_bundled_item_stock_status( $data_item, $product )
							);
						}
					}
				}

			break;
		}

		return $value;
	}

	/**
	 * Get default bundled variable product attributes - @see 'WC_REST_Products_Controller::get_default_attributes'.
	 *
	 * @param  WC_Bundled_Item_Data  $bundled_item_data
	 * @return array
	 */
	private static function get_bundled_item_attribute_defaults( $bundled_item_data ) {

		$default = array();
		$product = wc_get_product( $bundled_item_data->get_product_id() );

		if ( $product && $product->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $bundled_item_data->get_meta( 'default_variation_attributes' ), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => self::get_attribute_taxonomy_label( $key ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => str_replace( 'pa_', '', $key ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	/**
	 * Get attribute taxonomy label - @see 'WC_REST_Products_Controller::get_attribute_taxonomy_label'.
	 *
	 * @param  string  $name
	 * @return string
	 */
	private static function get_attribute_taxonomy_label( $name ) {
		$tax    = get_taxonomy( $name );
		$labels = get_taxonomy_labels( $tax );

		return $labels->singular_name;
	}

	/**
	 * Get bundled item stock status, taking min quantity into account.
	 *
	 * @param  WC_Bundled_Item_Data  $bundled_item_data
	 * @param  WC_Product_Bundle     $bundle
	 * @return string
	 */
	private static function get_bundled_item_stock_status( $bundled_item_data, $bundle ) {

		$bundled_item = wc_pb_get_bundled_item( $bundled_item_data, $bundle );
		$stock_status = 'in_stock';

		if ( $bundled_item ) {
			if ( false === $bundled_item->is_in_stock() ) {
				$stock_status = 'out_of_stock';
			} elseif ( $bundled_item->is_on_backorder() ) {
				$stock_status = 'on_backorder';
			}
		}

		return $stock_status;
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
			'bundled_by'     => array(
				'description' => __( 'Item ID of parent line item, applicable if the item is part of a Bundle.', 'woocommerce-product-bundles' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'bundled_items' => array(
				'description' => __( 'Item IDs of bundled line items, applicable if the item is a Bundle container.', 'woocommerce-product-bundles' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'bundled_item_title' => array(
				'description' => __( 'Title of the bundled product to display instead of the original product title.', 'woocommerce-product-bundles' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			)
		);
	}

	/**
	 * Adds 'bundled_by' and 'bundled_items' schema properties to line items.
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
	 * Filters WC REST API order responses to add references between bundle container/children items. Also modifies expanded product data based on the pricing and shipping settings.
	 *
	 * @since  5.0.0
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
	 * Append bundled items data to order data.
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
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'bundled_by' ]         = '';
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'bundled_item_title' ] = '';
				$order_data[ 'line_items' ][ $order_data_item_index ][ 'bundled_items' ]      = array();

				$order_data_item_id = $order_data_item[ 'id' ];

				// Add relationship references.
				if ( ! isset( $order_items[ $order_data_item_id ] ) ) {
					continue;
				}

				$parent_id    = wc_pb_get_bundled_order_item_container( $order_items[ $order_data_item_id ], $order, true );
				$children_ids = wc_pb_get_bundled_order_items( $order_items[ $order_data_item_id ], $order, true );

				if ( false !== $parent_id ) {
					$order_data[ 'line_items' ][ $order_data_item_index ][ 'bundled_by' ] = $parent_id;

					// Add overridden title.
					if ( isset( $order_items[ $order_data_item_id ][ 'bundled_item_title' ] ) ) {
						$order_data[ 'line_items' ][ $order_data_item_index ][ 'bundled_item_title' ] = $order_items[ $order_data_item_id ][ 'bundled_item_title' ];
					}

				} elseif ( ! empty( $children_ids ) ) {
					$order_data[ 'line_items' ][ $order_data_item_index ][ 'bundled_items' ] = $children_ids;
				} else {
					continue;
				}

				// Modify product data.
				if ( ! isset( $order_data_item[ 'product_data' ] ) ) {
					continue;
				}

				add_filter( 'woocommerce_get_product_from_item', array( WC_PB()->order, 'get_product_from_item' ) );
				$product = $order->get_product_from_item( $order_items[ $order_data_item_id ] );
				remove_filter( 'woocommerce_get_product_from_item', array( WC_PB()->order, 'get_product_from_item' ) );

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
	 * Filters WC v1-v3 REST API order response content to add bundle container/children item references.
	 */
	private static function add_legacy_hooks() {
		add_filter( 'woocommerce_api_order_response', array( __CLASS__, 'legacy_order_response' ), 10, 4 );
	}

	/**
	 * Filters WC v1-v3 REST API order responses to add references between bundle container/children items. Also modifies expanded product data based on the pricing and shipping settings.
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
				delete_post_meta( $post_id, '_wc_sw_min_price' );
				delete_post_meta( $post_id, '_wc_sw_max_price' );
			}
		}
	}
}

WC_PB_REST_API::init();
