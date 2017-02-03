<?php

class woocommerce_gpf_feed_item {

	/**
	 * The WC_Product that this item represents.
	 *
	 * @var WC_Product
	 */
	private $wc_product ;

	/**
	 * The feed format that the item is being prepared for.
	 *
	 * @var string
	 */
	private $feed_format;

	/**
	 * Array of prices to be used.
	 *
	 * @var array
	 */
	private $prices;

	/**
	 * Image style to be used when generated the image URLs.
	 *
	 * Override by using the filter 'woocommerce_gpf_image_style'
	 *
	 * @var string
	 */
	private $image_style = 'full';

	/**
	 * Unit of measurement to use when generating shipping height/width/length.
	 *
	 * Override by using the filter 'woocommerce_gpf_shipping_dimension_unit'.
	 * Valid values are 'in', or 'cm'.
	 *
	 * @var string
	 */
	private $shipping_dimension_unit = 'in';

	/**
	 * Whether this item represents a variation.
	 *
	 * @var boolean
	 */
	private $is_variation;

	/**
	 * The specific ID represented by this item.
	 *
	 * For variations, this will be the variation ID. For simple products, it
	 * will be the product ID.
	 *
	 * @var int
	 */
	private $specific_id;

	/**
	 * The post ID of the most general product represented by this item.
	 *
	 * For variations, this will be the parent product ID. For simple products,
	 * it will be the product ID.
	 *
	 * @var int
	 */
	private $general_id;

	public function __construct( WC_Product $wc_product, $feed_format = 'all' ) {

		$this->wc_product  = $wc_product;
		$this->feed_format = $feed_format;

		$this->image_style = apply_filters(
			'woocommerce_gpf_image_style',
			$this->image_style
		);
		$this->shipping_dimension_unit = apply_filters(
			'woocommerce_gpf_shipping_dimension_unit',
			'in'
		);
		$this->is_variation = $wc_product instanceof WC_Product_Variation;
		if ( $this->is_variation ) {
			if ( is_callable( array( $this->wc_product, 'get_parent_id' ) ) ) {
				// WC > 2.7.0
				$this->general_id  = $this->wc_product->get_parent_id();
				$this->specific_id = $this->wc_product->get_id();
			} else {
				// WC < 2.7.0
				$this->general_id  = $this->wc_product->id;
				$this->specific_id = $this->wc_product->variation_id;
			}
		} else {
			if ( is_callable( array( $this->wc_product, 'get_parent_id' ) ) ) {
				// WC > 2.7.0
				$this->general_id  = $this->wc_product->get_id();
				$this->specific_id = $this->wc_product->get_id();
			} else {
				// WC < 2.7.0
				$this->general_id  = $this->wc_product->id;
				$this->specific_id = $this->wc_product->id;
			}
		}
		$this->build_item();

	}

	private function build_item() {

		// Calculate the various prices we need.
		$this->get_product_prices();

		// Get main item information
		$this->ID    = $this->specific_id;
		$this->guid  = 'woocommerce_gpf_' . $this->ID;
		$this->title = $this->wc_product->get_title();
		if ( $this->is_variation ) {
			$suffix = $this->wc_product->get_formatted_variation_attributes(true);
			if ( ! empty( $suffix ) ) {
				$this->title .= ' (' . $suffix . ')';
			}
		}
		$this->title = apply_filters(
			'woocommerce_gpf_title',
			$this->title,
			$this->specific_id
		);
		// Use the variation description if possible, main product description if not.
		$this->description = null;
		if ( $this->is_variation ) {
			$this->description = $this->wc_product->get_variation_description();
		}
		if ( is_callable( array( $this->wc_product, 'get_description' ) ) ) {
			// WC > 2.7.0
			$description = $this->wc_product->get_description();
		} else {
			// WC < 2.7.0
			$description = $this->wc_product->post->post_content;
		}
		if ( empty( $this->description ) ) {
			$this->description = apply_filters(
				'the_content',
				$description
			);
		}
		$this->description = apply_filters(
			'woocommerce_gpf_description',
			$this->description,
			$this->general_id,
			$this->is_variation ? $this->specific_id : null
		);
		$this->image_link      = $this->get_the_post_thumbnail_src( $this->ID, $this->image_style );
		if ( $this->is_variation && empty( $this->image_link ) ) {
			$this->image_link = $this->get_the_post_thumbnail_src( $this->general_id, $this->image_style );
		}
		$this->purchase_link = $this->wc_product->get_permalink();
		$this->is_in_stock   = $this->wc_product->is_in_stock();
		$this->sku           = $this->wc_product->get_sku();
		$this->categories    = get_the_terms( $this->general_id, 'product_cat' );
		if ( false === $this->categories ) {
			$this->categories = array();
		}
		$this->shipping_weight = apply_filters(
			'woocommerce_gpf_shipping_weight',
			$this->wc_product->get_weight(),
			$this->ID
		);
		$this->additional_elements = array();

		// Add other elements.
		$this->general_elements();
		$this->get_additional_images();
		$this->shipping_height_elements();
		$this->shipping_width_elements();
		$this->shipping_length_elements();
		$this->all_or_nothing_shipping_elements();
		$this->force_stock_status();

		// General, or feed-specific items
		$this->additional_elements = apply_filters( 'woocommerce_gpf_elements', $this->additional_elements, $this->ID, isset( $this->variation_id ) ? $this->variation_id : null );
		$this->additional_elements = apply_filters( 'woocommerce_gpf_elements_' . $this->feed_format, $this->additional_elements, $this->ID, isset( $this->variation_id ) ? $this->variation_id : null );
	}

	/**
	 * Determines the lowest price (inc & ex. VAT) for a product, taking into
	 * account its child products as well as the main product price.
	 */
	private function get_product_prices() {

		// Grab the price of the main product.
		$prices = $this->generate_prices_for_product();

		// Adjust the price if there are cheaper child products.
		$prices = $this->adjust_prices_for_children( $prices );

		// Set the prices into the object.
		$this->sale_price_ex_tax     = $prices['sale_price_ex_tax'];
		$this->sale_price_inc_tax    = $prices['sale_price_inc_tax'];
		$this->regular_price_ex_tax  = $prices['regular_price_ex_tax'];
		$this->regular_price_inc_tax = $prices['regular_price_inc_tax'];
		$this->sale_price_start_date = $prices['sale_price_start_date'];
		$this->sale_price_end_date   = $prices['sale_price_end_date'];
		$this->price_ex_tax          = $prices['price_ex_tax'];
		$this->price_inc_tax         = $prices['price_inc_tax'];
	}

	/**
	 * Generates the inc, and ex. tax prices for both the regular, and sale
	 * price for a specific product, and returns them.
	 *
	 * @param WC_Product $product  Optional product to use. If not provided then
	 *                             $this->wc_product is used.
	 */
	private function generate_prices_for_product( $product = null ) {
		$prices = array();
		if ( is_null( $product ) ) {
			$product = $this->wc_product;
		}
		$prices['sale_price_ex_tax']     = null;
		$prices['sale_price_inc_tax']    = null;
		$prices['regular_price_ex_tax']  = null;
		$prices['regular_price_inc_tax'] = null;
		$prices['sale_price_start_date'] = null;
		$prices['sale_price_end_date']   = null;

		// Grab the regular price of the base product.
		$regular_price = $product->get_regular_price();
		if ( '' !== $regular_price ) {
			if ( function_exists( 'wc_get_price_excluding_tax' ) ) {
				$prices['regular_price_ex_tax']  = wc_get_price_excluding_tax( $product, array( 'price' => $regular_price ) );
				$prices['regular_price_inc_tax'] = wc_get_price_including_tax( $product, array( 'price' => $regular_price ) );
			} else {
				$prices['regular_price_ex_tax']  = $product->get_price_excluding_tax( 1, $regular_price );
				$prices['regular_price_inc_tax'] = $product->get_price_including_tax( 1, $regular_price );
			}
		}

		// Grab the sale price of the base product.
		$sale_price = $product->get_sale_price();
		if ( '' !== $sale_price ) {
			$prices['sale_price_ex_tax']     = $product->get_price_excluding_tax( 1, $sale_price );
			$prices['sale_price_inc_tax']    = $product->get_price_including_tax( 1, $sale_price );
			if ( is_callable( array( $product, 'get_date_on_sale_from' ) ) ) {
				// WC > 2.7.0
				$prices['sale_price_start_date'] = $product->get_date_on_sale_from();
				$prices['sale_price_end_date']   = $product->get_date_on_sale_to();
			} else {
				// WC < 2.7.0
				$prices['sale_price_start_date'] = $product->sale_price_dates_from;
				$prices['sale_price_end_date']   = $product->sale_price_dates_to;
			}
		}

		// Populate a "price", using the sale price if there is one, the actual price if not.
		if ( null !== $prices['sale_price_ex_tax'] ) {
			$prices['price_ex_tax']  = $prices['sale_price_ex_tax'];
			$prices['price_inc_tax'] = $prices['sale_price_inc_tax'];
		} else {
			$prices['price_ex_tax']  = $prices['regular_price_ex_tax'];
			$prices['price_inc_tax'] = $prices['regular_price_inc_tax'];
		}
		return $prices;
	}

	/**
	 * Adjusts the prices of the feed item according to child products.
	 */
	private function adjust_prices_for_children( $current_prices ) {
		if ( ! $this->wc_product->has_child() ) {
			return $current_prices;
		}
		$children = $this->wc_product->get_children();
		foreach ( $children as $child ) {
			$child_product = $this->wc_product->get_child( $child );
			if ( ! $child_product ) {
				continue;
			}
			if ( 'variation' === $child_product->product_type ) {
				$child_is_visible = $this->variation_is_visible( $child_product );
			} else {
				$child_is_visible = $child_product->is_visible();
			}
			if ( ! $child_is_visible ) {
				continue;
			}
			$child_prices = $this->generate_prices_for_product( $child_product );
			if ( ( 0 == $current_prices['price_inc_tax'] ) && ( $child_prices['price_inc_tax'] > 0 ) ) {
				$current_prices = $child_prices;
			} elseif ( ($child_prices->price_inc_tax > 0) && ($child_prices->price_inc_tax < $current_prices->price_inc_tax) ) {
				$current_prices = $child_prices;
			}
		}
		return $current_prices;
	}

	/**
	 * Helper function for WooCommerce v2.0.x
	 * Checks if a variation is visible or not.
	 */
	private function variation_is_visible( $variation ) {
		if ( method_exists( $variation, 'variation_is_visible' ) ) {
			return $variation->variation_is_visible();
		}
		$visible = true;
		if ( 'publish' !== get_post_status( $variation->variation_id ) ) {
			// Published == enabled checkbox
			$visible = false;
		} elseif ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) {
			// Out of stock visibility
			$visible = false;
		} elseif ( $variation->get_price() === '' ) {
			// Price not set.
			$visible = false;
		}
		return $visible;
	}

	/**
	 * Retrieve Post Thumbnail URL
	 *
	 * @param int     $post_id (optional) Optional. Post ID.
	 * @param string  $size    (optional) Optional. Image size.  Defaults to 'post-thumbnail'.
	 * @return string|bool Image src, or false if the post does not have a thumbnail.
	 */
	private function get_the_post_thumbnail_src( $post_id = null, $size = 'post-thumbnail' ) {
		$post_thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( ! $post_thumbnail_id ) {
			return false;
		}
		list( $src ) = wp_get_attachment_image_src( $post_thumbnail_id, $size, false );
		return $src;
	}

	/**
	 * Add the "advanced" information to the field based on either the
	 * per-product settings, category settings, or store defaults.
	 *
	 * @access private
	 */
	private function general_elements() {

		global $woocommerce_gpf_common;

		// Retrieve the info set against the product by this plugin.
		$product_values = $woocommerce_gpf_common->get_values_for_product( $this->general_id, $this->feed_format );

		// Merge variation values over the top if this is a variation.
		if ( $this->is_variation ) {
			$variation_values = $woocommerce_gpf_common->get_values_for_variation( $this->specific_id, $this->feed_format );
			$product_values = array_merge( $product_values, $variation_values );
		}

		if ( ! empty( $product_values ) ) {
			foreach ( $product_values as $key => $value ) {
				// Deal with fields that can have multiple, comma separated values
				if ( isset( $woocommerce_gpf_common->product_fields[ $key ]['multiple'] ) && $woocommerce_gpf_common->product_fields[ $key ]['multiple'] && ! is_array( $value ) ) {
					$value = explode( ',', $value );
				}
				$elements[ $key ] = (array) $value;
			}
		}
		$this->additional_elements = $elements;
	}

	/**
	 * Retrieve a measurement for a product in inches.
	 *
	 * @param  int     $product_id  The product ID to retrieve the measurement for.
	 * @param  string  $dimension   The dimension to retrieve. "length", "width" or "height"
	 * @return float                The requested dimension for the given product.
	 */
	private function get_shipping_dimension( $dimension ) {
		if ( 'width' !== $dimension &&
		     'length' !== $dimension &&
			 'height' !== $dimension ) {
				 return null;
		}
		$function = 'get_' . $dimension;
		if ( is_callable( array( $this->wc_product, $function ) ) ) {
			$measurement = $this->wc_product->{$function}();
		} else {
			$measurement = $this->wc_product->$dimension;
		}
		if ( empty( $measurement ) ) {
			return null;
		}
		$measurement = wc_get_dimension( $measurement, $this->shipping_dimension_unit );
		return $measurement;
	}

	/**
	 * Add shipping_length to the elements array if the product has a length
	 * configured.
	 */
	private function shipping_length_elements() {
		$length = $this->get_shipping_dimension('length' );
		if ( empty( $length ) ) {
			return;
		}
		$this->additional_elements['shipping_length'] = array( $length . ' ' . $this->shipping_dimension_unit );
	}

	/**
	 * Add shipping_width to the elements array if the product has a width
	 * configured.
	 */
	private function shipping_width_elements() {
		$width = $this->get_shipping_dimension( 'width' );
		if ( empty( $width ) ) {
			return;
		}
		$this->additional_elements['shipping_width'] = array( $width . ' ' . $this->shipping_dimension_unit );
	}

	/**
	 * Add shipping_height to the elements array if the product has a height
	 * configured.
	 */
	private function shipping_height_elements() {
		$height = $this->get_shipping_dimension( 'height' );
		if ( empty( $height ) ) {
			return;
		}
		$this->additional_elements['shipping_height'] = array( $height . ' ' . $this->shipping_dimension_unit );
	}

	/**
	 * Send all shipping measurements, or none.
	 *
	 * Make sure that *if* we have length, width or height, that we send all three. If we're
	 * missing any then we send none of them.
	 *
	 * @param  array  $elements   The current feed item elements.
	 * @param  int    $product_id The product to get the length of.
	 * @return array              The modified feed item elements.
	 */
	private function all_or_nothing_shipping_elements() {
		if ( empty( $this->additional_elements['shipping_width'] ) &&
			 empty( $this->additional_elements['shipping_length'] ) &&
			 empty( $this->additional_elements['shipping_height'] ) ) {
			return;
		}
		if ( empty( $this->additional_elements['shipping_width'] ) ||
		     empty( $this->additional_elements['shipping_length'] ) ||
		     empty( $this->additional_elements['shipping_height'] ) ) {
			unset( $this->additional_elements['shipping_length'] );
		    unset( $this->additional_elements['shipping_width'] );
		    unset( $this->additional_elements['shipping_height'] );
		}
	}

	/**
	 * Make sure we always send a stock value.
	 */
	private function force_stock_status() {
		if ( ! $this->is_in_stock && empty( $this->additional_elements['availability'] ) ) {
			$this->additional_elements['availability'] = array( 'out of stock' );
		}
	}

	/**
	 * Add additional images to the feed item.
	 */
	private function get_additional_images() {
		// Look for additional images.
		$this->additional_images = array();
		// Variations do not have additional images.
		if ( $this->is_variation ) {
			return;
		}
		$excluded_ids[] = get_post_meta( $this->ID, '_thumbnail_id', true );

		// List product gallery images first.
		if ( apply_filters( 'woocommerce_gpf_include_product_gallery_images', true ) ) {
			$product_gallery_images = get_post_meta( $this->ID, '_product_image_gallery', true );
			if ( !empty( $product_gallery_images ) ) {
				$product_gallery_images = explode( ',', $product_gallery_images );
				foreach ( $product_gallery_images as $product_gallery_image_id ) {
					if ( in_array( $product_gallery_image_id, $excluded_ids ) ) {
						continue;
					}
					$full_image_src = wp_get_attachment_image_src( $product_gallery_image_id, $this->image_style );
					$this->additional_images[] = $full_image_src[0];
					$excluded_ids[] = $product_gallery_image_id;
				}
			}
		}
		if ( apply_filters( 'woocommerce_gpf_include_attached_images', true ) ) {
			$images = get_children(
				array(
					'post_parent' => $this->ID,
					'post_status' => 'inherit',
					'post_type' => 'attachment',
					'post_mime_type' => 'image',
					'order' => 'ASC',
					'orderby' => 'menu_order',
				)
			);
			if ( is_array( $images ) && count( $images ) ) {
				foreach ( $images as $image ) {
					if ( in_array( $image->ID, $excluded_ids ) ) {
						continue;
					}
					$full_image_src = wp_get_attachment_image_src( $image->ID, $this->image_style );
					$this->additional_images[] = $full_image_src[0];
				}
			}
		}
	}
}
