<?php
/**
 * WC_PB_Install class
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
 * Handles installation and updating tasks.
 *
 * @class    WC_PB_Install
 * @version  5.0.0
 * @since    5.0.0
 */
class WC_PB_Install {

	/** @var array DB updates and callbacks that need to be run per version */
	private static $db_updates = array(
		'3.0.0' => array(
			'wc_pb_update_300'
		),
		'5.0.0' => array(
			'wc_pb_update_500_main',
			'wc_pb_update_500_delete_unused_meta'
		)
	);

	/** @var object Background update class */
	private static $background_updater;

	/** @var string Plugin version */
	private static $current_version;

	/** @var string Plugin DB version */
	private static $current_db_version;

	/**
	 * Hook in tabs.
	 */
	public static function init() {

		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'init', array( __CLASS__, 'check_updating' ) );
		add_action( 'admin_init', array( __CLASS__, 'check_version' ) );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		// Get PB plugin and plugin DB versions.
		self::$current_version    = get_option( 'woocommerce_product_bundles_version', null );
		self::$current_db_version = get_option( 'woocommerce_product_bundles_db_version', null );

		include_once( 'class-wc-pb-background-updater.php' );
	}

	/**
	 * Init background updates.
	 */
	public static function init_background_updater() {
		self::$background_updater = new WC_PB_Background_Updater();
	}

	/**
	 * Check version and run the updater if necessary.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( current_user_can( 'manage_woocommerce' ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			if ( self::$current_version !== WC_PB()->version ) {
				self::install();
			} else {
				if ( ! empty( $_GET[ 'force_wc_pb_db_update' ] ) && wp_verify_nonce( $_GET[ '_wc_pb_admin_nonce' ], 'wc_pb_force_db_update_nonce' ) ) {
					self::force_update();
				}
			}
		}
	}

	/**
	 * If the DB version is out-of-date, a DB update must be in progress: define a 'WC_PB_UPDATING' constant.
	 */
	public static function check_updating() {
		if ( is_null( self::$current_db_version ) || version_compare( self::$current_db_version, max( array_keys( self::$db_updates ) ), '<' ) ) {
			if ( ! defined( 'WC_PB_UPDATING' ) ) {
				define( 'WC_PB_UPDATING', true );
			}
		}
	}

	/**
	 * Install PB.
	 */
	public static function install() {

		// Create tables.
		self::create_tables();

		// if bundle type does not exist, create it.
		if ( false === $bundle_term_exists = get_term_by( 'slug', 'bundle', 'product_type' ) ) {
			wp_insert_term( 'bundle', 'product_type' );
		}

		// Update plugin version - once set, 'check_version()' will not call 'install()' again.
		self::update_version();

		// Plugin data exists - queue upgrade tasks.
		if ( $bundle_term_exists && ( is_null( self::$current_db_version ) || version_compare( self::$current_db_version, max( array_keys( self::$db_updates ) ), '<' ) ) ) {
			self::update();
		// Nothing found - this is a new install :)
		} else {
			self::update_db_version();
		}
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Tables:
	 *     woocommerce_bundled_items - Each bundled item id is associated with a "contained" product id (the bundled product), and a "container" bundle id (the product bundle).
	 *     woocommerce_bundled_itemmeta - Bundled item meta for storing extra data.
	 */
	private static function create_tables() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( self::get_schema() );
	}

	/**
	 * Get table schema.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$max_index_length = 191;

		$tables = "
CREATE TABLE {$wpdb->prefix}woocommerce_bundled_items (
  bundled_item_id bigint(20) NOT NULL auto_increment,
  product_id bigint(20) NOT NULL,
  bundle_id bigint(20) NOT NULL,
  menu_order bigint(20) NOT NULL,
  PRIMARY KEY  (bundled_item_id),
  KEY product_id (product_id),
  KEY bundle_id (bundle_id)
) $collate;
CREATE TABLE {$wpdb->prefix}woocommerce_bundled_itemmeta (
  meta_id bigint(20) NOT NULL auto_increment,
  bundled_item_id bigint(20) NOT NULL,
  meta_key varchar(255) default NULL,
  meta_value longtext NULL,
  PRIMARY KEY  (meta_id),
  KEY bundled_item_id (bundled_item_id),
  KEY meta_key (meta_key($max_index_length))
) $collate;
		";

		return $tables;
	}

	/**
	 * Update WC PB version to current.
	 */
	private static function update_version() {
		delete_option( 'woocommerce_product_bundles_version' );
		add_option( 'woocommerce_product_bundles_version', WC_PB()->version );
	}

	/**
	 * Push all needed DB updates to the queue for processing.
	 */
	private static function update() {

		$logger        = new WC_Logger();
		$update_queued = false;

		foreach ( self::$db_updates as $version => $update_callbacks ) {
			if ( version_compare( self::$current_db_version, $version, '<' ) ) {
				$logger->add( 'wc_pb_db_updates', sprintf( 'Updating to version %s.', $version ) );
				foreach ( $update_callbacks as $update_callback ) {
					$logger->add( 'wc_pb_db_updates', sprintf( '- Queuing %s callback.', $update_callback ) );
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			// Define 'WC_PB_UPDATING' constant.
			if ( ! defined( 'WC_PB_UPDATING' ) ) {
				define( 'WC_PB_UPDATING', true );
			}
			// Add option to keep track of time.
			delete_option( 'wc_pb_update_init' );
			add_option( 'wc_pb_update_init', gmdate( 'U' ) );
			// Add 'updating' notice and save early (saving on the 'shutdown' action will fail if a chained request arrives before the 'shutdown' hook fires).
			WC_PB_Admin_Notices::add_maintenance_notice( 'updating' );
			WC_PB_Admin_Notices::save_notices();
			// Dispatch.
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * Force re-start the update cron if everything else fails.
	 */
	public static function force_update() {
		/**
		 * 'wp_wc_pb_updater_cron' action.
		 */
		do_action( 'wp_wc_pb_updater_cron' );
		wp_safe_redirect( admin_url() );
	}

	/**
	 * Updates plugin DB version when all updates have been processed.
	 */
	public static function update_complete() {

		$logger = new WC_Logger();

		$logger->add( 'wc_pb_db_updates', 'Data update complete.' );
		self::update_db_version();
		delete_option( 'wc_pb_update_init' );
	}

	/**
	 * True if an update is in progress.
	 *
	 * @return boolean
	 */
	public static function is_update_pending() {
		return defined( 'WC_PB_UPDATING' );
	}

	/**
	 * True if an update is in progress.
	 *
	 * @return boolean
	 */
	public static function is_update_in_progress() {
		return self::$background_updater->is_updating();
	}

	/**
	 * True if an update process is running.
	 *
	 * @return boolean
	 */
	public static function is_update_process_running() {
		return self::$background_updater->is_process_running();
	}

	/**
	 * Update DB version to current.
	 *
	 * @param  string  $version
	 */
	private static function update_db_version( $version = null ) {
		delete_option( 'woocommerce_product_bundles_db_version' );
		add_option( 'woocommerce_product_bundles_db_version', is_null( $version ) ? WC_PB()->version : $version );
	}

	/**
	 * Show row meta on the plugin screen.
	 *
	 * @param	mixed  $links
	 * @param	mixed  $file
	 * @return	array
	 */
	public static function plugin_row_meta( $links, $file ) {

		if ( $file == WC_PB()->plugin_basename() ) {
			$row_meta = array(
				'docs'    => '<a href="https://docs.woocommerce.com/document/bundles/">' . __( 'Docs', 'woocommerce-product-bundles' ) . '</a>',
				'support' => '<a href="https://support.woocommerce.com/">' . __( 'Support', 'woocommerce-product-bundles' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return $links;
	}
}

WC_PB_Install::init();
