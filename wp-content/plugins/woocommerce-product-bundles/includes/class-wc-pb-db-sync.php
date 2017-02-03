<?php
/**
 * WC_PB_DB_Sync class
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
 * Product hooks for bundled items and bundled item meta db lifecycle management.
 *
 * @class    WC_PB_DB_Sync
 * @version  5.0.0
 * @since    5.0.0
 */
class WC_PB_DB_Sync {

	/**
	 * Setup Admin class.
	 */
	public static function init() {

		// Duplicate bundled items when duplicating a bundle.
		add_action( 'woocommerce_duplicate_product', array( __CLASS__, 'duplicate_product' ), 10, 2 );

		// Delete bundled item DB entries when: i) the container bundle is deleted, or ii) the associated product is deleted.
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ), 11 );

		// When deleting a bundled item from the DB, clear the transients of the container bundle (also invalidates product transients, including 'wc_bundled_product_data').
		add_action( 'woocommerce_delete_bundled_item', array( __CLASS__, 'delete_bundled_item' ) );

		// Delete bundle-specific transients.
		add_action( 'woocommerce_delete_product_transients', array( __CLASS__, 'delete_bundle_transients' ) );

		if ( ! defined( 'WC_PB_DEBUG_STOCK_CACHE' ) ) {

			// Delete bundled item stock meta cache when stock changes.
			add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'product_stock_changed' ), 100 );
			add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'product_stock_changed' ), 100 );

			// Delete bundled item stock meta cache when stock status changes.
			add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'product_stock_status_changed' ), 100 );
		}
	}

	/**
	 * Duplicates bundled items when duplicating a bundle.
	 *
	 * @param  mixed    $new_product_id
	 * @param  WP_Post  $post
	 */
	public static function duplicate_product( $new_product_id, $post ) {

		$bundled_items = WC_PB_DB::query_bundled_items( array(
			'bundle_id' => $post->ID,
			'return'    => 'objects'
		) );

		if ( ! empty( $bundled_items ) ) {
			foreach ( $bundled_items as $bundled_item ) {
				$bundled_item_data = $bundled_item->get_data();
				WC_PB_DB::add_bundled_item( array(
					'bundle_id'  => $new_product_id,                    // Use the new bundle id.
					'product_id' => $bundled_item_data[ 'product_id' ],
				 	'menu_order' => $bundled_item_data[ 'menu_order' ],
				 	'meta_data'  => $bundled_item_data[ 'meta_data' ]
				 ) );
			}
			WC_Cache_Helper::get_transient_version( 'product', true );
		}
	}

	/**
	 * Deletes bundled item DB entries when: i) their container product bundle is deleted, or ii) the associated bundled product is deleted.
	 *
	 * @param  mixed  $id  ID of post being deleted.
	 */
	public static function delete_post( $id ) {

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			if ( 'product' === $post_type ) {

				// Delete bundled item DB entries and meta when deleting a bundle.
				$bundled_items = WC_PB_DB::query_bundled_items( array(
					'bundle_id' => $id,
					'return'    => 'objects'
				) );

				if ( ! empty( $bundled_items ) ) {
					foreach ( $bundled_items as $bundled_item ) {
						$bundled_item->delete();
					}
				}

				// Delete bundled item DB entries and meta when deleting an associated product.
				$bundled_item_ids = array_keys( wc_pb_get_bundled_product_map( $id, false ) );

				if ( ! empty( $bundled_item_ids ) ) {
					foreach ( $bundled_item_ids as $bundled_item_id ) {
						WC_PB_DB::delete_bundled_item( $bundled_item_id );
					}
				}
			}
		}
	}

	/**
	 * When deleting a bundled item from the DB, clear the transients of the container bundle (also invalidates product transients, including 'wc_bundled_product_data').
	 *
	 * @param  WC_Bundled_Item_Data  $item  The bundled item DB object being deleted.
	 */
	public static function delete_bundled_item( $item ) {
		$bundle_id = $item->get_bundle_id();
		wc_delete_product_transients( $bundle_id );
	}

	/**
	 * Delete bundled item stock meta cache when an associated product stock (status) changes.
	 *
	 * @param  WC_Product  $product
	 * @return void
	 */
	public static function delete_bundled_items_stock_cache( $product_id ) {
		global $wpdb;

		$bundled_item_ids = array_keys( wc_pb_get_bundled_product_map( $product_id ) );

		if ( ! empty( $bundled_item_ids ) ) {

			$wpdb->query( "
				DELETE FROM {$wpdb->prefix}woocommerce_bundled_itemmeta
				WHERE meta_key IN ( 'stock_status', 'max_stock' )
				AND bundled_item_id IN (" . implode( ',', $bundled_item_ids ) . ")
			" );

			do_action( 'woocommerce_delete_bundled_items_stock_cache', $product_id, $bundled_item_ids );

			/**
			 * 'woocommerce_sync_bundled_items_stock_status' filter.
			 *
			 * Use this filter to always re-sync all bundled items stock meta when the associated product stock (status) changes.
			 * Instead of deleting the bundled items stock meta and refreshing them on-demand, this will effectively keep them in sync all the time.
			 *
			 * Off by default -- enabling this may put a heavy load on the server in cases where the same product is contained in a large number of bundles.
			 *
			 * This makes it possible, for instance, to reliably run bundled item stock meta queries in order to:
			 *
			 * - Get all bundle ids that contain out of stock items.
			 * - Get all product ids associated with out of stock bundled items.
			 *
			 * @param  boolean  $sync_bundled_item_stock_meta
			 */
			if ( apply_filters( 'woocommerce_sync_bundled_items_stock_status', false ) ) {
				foreach ( $bundled_item_ids as $bundled_item_id ) {

					// Create a 'WC_Bundled_Item' instance to re-sync and update the bundled item stock meta.
					$bundled_item = wc_pb_get_bundled_item( $bundled_item_id );

					if ( $bundled_item ) {
						$bundled_item->sync_stock();
					}
				}
			}
		}
	}

	/**
	 * Delete bundled item stock meta cache when an associated product stock changes.
	 *
	 * @param  WC_Product  $product
	 * @return void
	 */
	public static function product_stock_status_changed( $product_id ) {
		self::delete_bundled_items_stock_cache( $product_id );
	}

	/**
	 * Delete bundled item stock meta cache when an associated product stock changes.
	 *
	 * @param  WC_Product  $product
	 * @return void
	 */
	public static function product_stock_changed( $product ) {
		self::delete_bundled_items_stock_cache( $product->id );
	}

	/**
	 * Ensure bundle-specific transients are cleared when the core ones are cleared.
	 *
	 * @param  mixed  $post_id
	 * @return void
	 */
	public static function delete_bundle_transients( $post_id ) {
		if ( $post_id > 0 ) {

			// Delete bundled items cache.
			delete_transient( 'wc_bundled_items_' . $post_id );

			/*
			 * Delete associated bundled items stock cache when clearing product transients.
			 * Workaround for https://github.com/somewherewarm/woocommerce-product-bundles/issues/22 .
			 */
			self::delete_bundled_items_stock_cache( $post_id );
		}
	}
}

WC_PB_DB_Sync::init();
