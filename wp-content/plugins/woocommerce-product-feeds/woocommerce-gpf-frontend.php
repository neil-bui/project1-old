<?php
/**
 * Frontend class.
 *
 * Handles grabbing the products and invoking the relevant feed class to render the feed.
 */
class WoocommerceGpfFrontend {

	protected $feed        = null;
	protected $feed_format = '';
	protected $settings    = array();

	/**
	 * WC_Product_Factory instance.
	 *
	 * @var WC_Product_Factory
	 */
	private $factory;

	/**
	 * Constructor. Grab the settings, and add filters if we have stuff to do
	 *
	 * @access public
	 */
	public function __construct() {

		global $wp_query;

		if ( 'google' === $wp_query->query_vars['woocommerce_gpf'] ) {
			$this->feed        = new WoocommerceGpfFeedGoogle();
			$this->feed_format = 'google';
		} elseif ( 'googleinventory' === $wp_query->query_vars['woocommerce_gpf'] ) {
			$this->feed        = new WoocommerceGpfFeedGoogleInventory();
			$this->feed_format = 'googleinventory';
		} elseif ( 'bing' === $wp_query->query_vars['woocommerce_gpf'] ) {
			$this->feed        = new WoocommerceGpfFeedBing();
			$this->feed_format = 'bing';
		}
		$this->settings = get_option( 'woocommerce_gpf_config', array() );
		if ( ! empty( $this->feed ) ) {
			add_action( 'template_redirect', array( $this, 'render_product_feed' ), 15 );
		}

		if ( ! empty( $_GET['gpf_categories'] ) ) {
			add_filter( 'woocommerce_gpf_wc_get_products_args', array( $this, 'limit_categories' ) );
			add_filter( 'woocommerce_gpf_get_posts_args', array( $this, 'limit_categories' ) );
		}
	}

	public function limit_categories( $args ) {
		$categories = explode( ',', $_GET['gpf_categories'] );
		$categories = array_map( 'intval', $categories );
		if ( 'woocommerce_gpf_get_posts_args' === current_action() ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'terms'    => $categories,
				),
			);
		} else {
			// Map the term IDs to slugs.
			$slugs = array();
			foreach ( $categories as $term_id ) {
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) ) {
					$slugs[] = $term->slug;
				}
			}
			$args['category'] = $slugs;
		}
		return $args;
	}

	/**
	 * Set a number of optimsiations to make sure the plugin is usable on lower end setups.
	 *
	 * We stop plugins trying to cache, or compress the output since that causes everything to be
	 * held in memory and causes memory issues. We also tell WP not to add loaded objects to the
	 * cache since on setups without a persistent object store that would result in everything being
	 * in memory again.
	 */
	private function set_optimisations() {

		global $wpdb;

		// Don't cache feed under WP Super-Cache.
		define( 'DONOTCACHEPAGE', true );

		// Cater for large stores.
		$wpdb->hide_errors();
		@set_time_limit( 0 );
		while ( ob_get_level() ) {
			@ob_end_clean();
		}
	}

	/**
	 * Work out if a feed item should be excluded from the feed.
	 *
	 * @param  Object  $woocommerce_product The WooCommerce product object.
	 * @return bool                         True if the product should be excluded. False otherwise.
	 */
	private function product_is_excluded( $woocommerce_product ) {
		$excluded = false;
		$visibility = is_callable( array( $woocommerce_product, 'get_catalog_visibility' ) ) ?
		              $woocommerce_product->get_catalog_visibility() :
					  $woocommerce_product->visibility;
		// Check to see if the product is set as Hidden within WooCommerce.
		if ( 'hidden' === $visibility ) {
			$excluded = true;
		}

		// Check to see if the product has been excluded in the feed config.
		if ( is_callable( array( $woocommerce_product, 'get_meta' ) ) ) {
			// WC > 2.7.0.
			$gpf_data = get_post_meta( $woocommerce_product->get_id(), '_woocommerce_gpf_data', true );
		} else {
			// WC < 2.7.0.
			if ( $gpf_data = $woocommerce_product->woocommerce_gpf_data ) {
				$gpf_data = maybe_unserialize( $gpf_data );
			} else {
				$gpf_data = array();
			}
		}
		if ( ! empty( $gpf_data ) ) {
			$gpf_data = maybe_unserialize( $gpf_data );
		}
		if ( ! empty( $gpf_data['exclude_product'] ) ) {
			$excluded = true;
		}
		if ( $woocommerce_product instanceof WC_Product_Variation ) {
			if ( is_callable( array( $woocommerce_product, 'get_parent_id' ) ) ) {
				// WC > 2.7.0
				$id = $woocommerce_product->get_parent_id();
			} else {
				// WC < 2.7.0
				$id = $woocommerce_product->id;
			}
		} else {
			if ( is_callable( array( $woocommerce_product, 'get_id' ) ) ) {
				// WC > 2.7.0
				$id = $woocommerce_product->get_id();
			} else {
				// WC < 2.7.0
				$id = $woocommerce_product->id;
			}
		}
		return apply_filters( 'woocommerce_gpf_exclude_product', $excluded, $id, $this->feed_format );
	}

	/**
	 * Generate the query function to use, and argument array.
	 *
	 * Identifies the query function to be used to retrieve products, either
	 * WordPress' get_posts(), or wc_get_products() depending on whether
	 * wc_get_products() is available.
	 *
	 * Also constructs the base arguments array to be passed to the query
	 * function.
	 *
	 * @param  int    $chunk_size  The number of products to be retrieved per
	 *                             query.
	 *
	 * @return array               Array containing the query function name at
	 *                             index 0, and the arguments array at index 1.
	 */
	private function get_query_args( $chunk_size ) {
		global $wp_query;

		$offset = isset( $wp_query->query_vars['gpf_start'] ) ?
				  (int) $wp_query->query_vars['gpf_start'] :
				  0;
		if ( function_exists( 'wc_get_products' ) ) {
			$args = array(
				'status'      => array( 'publish' ),
				'type'        => array( 'simple', 'external', 'variable' ),
				'limit'       => $chunk_size,
				'offset'      => $offset,
				'return'      => 'objects',
			);
			return array(
				'wc_get_products',
				apply_filters(
					'woocommerce_gpf_wc_get_products_args',
					$args
				),
			);
		} else {
			$args = array(
				'post_type'   => 'product',
				'numberposts' => $chunk_size,
				'offset'      => $offset,
			);
			return array(
				'get_posts',
				apply_filters(
					'woocommerce_gpf_get_posts_args',
					$args
				),
			);
		}
	}

	/**
	 * Render the product feed requests - calls the sub-classes according
	 * to the feed required.
	 *
	 * @access public
	 */
	public function render_product_feed() {

		global $wp_query, $post, $_wp_using_ext_object_cache;

		$this->factory = new WC_Product_Factory();

		$this->set_optimisations();
		$this->feed->render_header();

		$chunk_size = apply_filters( 'woocommerce_gpf_chunk_size', 10 );

		list($query_function, $args) = $this->get_query_args( $chunk_size );

		$gpf_limit = isset( $wp_query->query_vars['gpf_limit'] ) ?
		             (int) $wp_query->query_vars['gpf_limit'] :
		             false;

		$output_count = 0;

		// Query for the products, and process them.
		$products     = $query_function( $args );

		while ( count( $products ) ) {
			foreach ( $products as $post ) {
				if ( $this->process_product( $post ) ) {
					$output_count++;
				}
				// Quit if we've done all of the products
				if ( $gpf_limit && $output_count === $gpf_limit ) {
					break;
				}
			}
			if ( $gpf_limit && $output_count === $gpf_limit ) {
				break;
			}
			$args['offset'] += $chunk_size;

			// If we're using the built in object cache then flush it every chunk so
			// that we don't keep churning through memory.
			if ( ! $_wp_using_ext_object_cache ) {
				wp_cache_flush();
			}
			$products = $query_function( $args );
		}
		$this->feed->render_footer();
	}


	/**
	 * Process a product, outputting its information.
	 *
	 * Uses process_simple_product() to process simple products, or all products if variation
	 * support is disabled. Uses process_variable_product() to process variable products.
	 *
	 * @param  object  $post  WordPress post object / WC_Product instance
	 * @return bool           True if one or more products were output, false otherwise.
	 */
	private function process_product( $post ) {
		setup_postdata( $post );
		if ( ! $post instanceof WC_Product ) {
			$woocommerce_product = wc_get_product( $post );
		} else {
			$woocommerce_product = $post;
		}
		if ( $this->product_is_excluded( $woocommerce_product ) ) {
			return false;
		}
		if ( empty( $this->settings['include_variations'] ) ||
		     $woocommerce_product->is_type( 'simple' ) ) {
			return $this->process_simple_product( $post, $woocommerce_product );
		} elseif ( $woocommerce_product->is_type( 'variable' ) ) {
			return $this->process_variable_product( $post, $woocommerce_product );
		}
	}

	/**
	 * Process a simple product, and output its elements.
	 *
	 * @param  object  $post                 WordPress post object
	 * @param  object  $woocommerce_product  WooCommerce Product Object (May not be Simple)
	 * @return bool                          True if one or more products were output, false
	 *                                       otherwise.
	 */
	private function process_simple_product( $post, $woocommerce_product ) {
		// Construct the data for this item.
		$feed_item = new woocommerce_gpf_feed_item( $woocommerce_product, $this->feed_format );

		// Allow other plugins to modify the item before its rendered to the feed
		$feed_item = apply_filters( 'woocommerce_gpf_feed_item', $feed_item );
		$feed_item = apply_filters( 'woocommerce_gpf_feed_item_' . $this->feed_format, $feed_item );

		return $this->feed->render_item( $feed_item );
	}

	/**
	 * Process a variable product, and output its elements.
	 *
	 * @param  object  $post                 WordPress post object
	 * @param  object  $woocommerce_product  WooCommerce Product Object
	 * @return bool                          True if one or more products were output, false
	 *                                       otherwise.
	 */
	private function process_variable_product( $post, $woocommerce_product ) {
		$success    = false;
		$variations = $woocommerce_product->get_available_variations();

		foreach ( $variations as $variation ) {
			// Get the variation product.
			$variation_id      = $variation['variation_id'];
			$variation_product = $this->factory->get_product( $variation_id );
			// Skip to the next if this variation isn't to be included.
			if ( $this->product_is_excluded( $variation_product ) ) {
				continue;
			}
			// Construct the data for this item.
			$feed_item = new woocommerce_gpf_feed_item( $variation_product, $this->feed_format );

			// Allow other plugins to modify the item before its rendered to the feed
			$feed_item = apply_filters( 'woocommerce_gpf_feed_item', $feed_item );
			$feed_item = apply_filters( 'woocommerce_gpf_feed_item_' . $this->feed_format, $feed_item );

			// Render it.
			$success |= $this->feed->render_item( $feed_item );
		}
		return $success;
	}
}

global $woocommerce_gpf_frontend;
$woocommerce_gpf_frontend = new WoocommerceGpfFrontend();
