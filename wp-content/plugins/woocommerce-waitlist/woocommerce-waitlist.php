<?php
/*
   Plugin Name: WooCommerce Waitlist
   Plugin URI: http://www.woothemes.com/products/woocommerce-waitlist/
   Description: This plugin enables registered users to request an email notification when an out-of-stock product comes back into stock. It tallies these registrations in the admin panel for review and provides details.
   Version: 1.4.13
   Author: Neil Pie
   Author URI: http://neilpie.co.uk
   Requires at least: 4.0
   Tested up to: 4.5.2
   Text Domain: woocommerce-waitlist
   License: GNU General Public License v3.0
   License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Required functions
 */
if ( !function_exists( 'woothemes_queue_update' ) )
	require_once 'woo-includes/woo-functions.php';

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '55d9643a241ecf5ad501808c0787483f', '122144' );

if ( is_woocommerce_active() ) {

	/**
	 * Return version number of woocommerce by looking directly in the plugins directory
	 *
	 * @return woocommerce version number
	 */
	function wcwl_get_woocommerce_version_number() {
		if ( !function_exists( 'get_plugins' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		$plugin_folder = get_plugins( '/' . 'woocommerce' );
		$plugin_file = 'woocommerce.php';

		if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
			return $plugin_folder[$plugin_file]['Version'];

		} else {
			return false;
		}
	}

	/**
	 * Our admin notice to display if the version of WooCommerce trying to be used is too low
	 *
	 * @return void
	 */
	function wcwl_woocommerce_version_notice() {
	    ?>
	    <div class="error">
	        <p><?php _e( 'WooCommerce Waitlist has been deactivated. This extension is not available with your version of WooCommerce. Please install and activate WooCommerce version 2.0 or higher.', 'woocommerce-waitlist' ); ?></p>
	    </div>
	    <?php
	}

	// Check the version of WooCommerce is at least 2.1 before loading our plugin
	$wcwl_woocommerce_version = wcwl_get_woocommerce_version_number();
	if( $wcwl_woocommerce_version == false || version_compare( $wcwl_woocommerce_version, '2.1.0', '<' ) ) {
		global $pagenow;
		if ( $pagenow == 'plugins.php' ) {
			add_action( 'admin_notices', 'wcwl_woocommerce_version_notice' );
		}
		deactivate_plugins( plugin_dir_path( __FILE__ ) . 'woocommerce-waitlist.php' );
	} else {

		require_once 'definitions.php';

		if ( is_admin() ) {
			if ( !class_exists( 'Pie_WCWL_Custom_Tab' ) ) require_once 'classes/class-pie-wcwl-custom-admin-tab.php';
			if ( !class_exists( 'Pie_WCWL_Admin_UI' ) ) require_once 'classes/class-pie-wcwl-admin-ui.php';
			if ( !class_exists( 'Pie_WCWL_Waitlist_Settings' ) ) require_once 'classes/class-pie-wcwl-waitlist-settings.php';
			if ( !class_exists( 'Pie_WCWL_Waitlist_Archive' ) ) require_once 'classes/class-pie-wcwl-waitlist-archive.php';
		} else {
			if ( !class_exists( 'Pie_WCWL_Frontend_UI' ) ) require_once 'classes/class-pie-wcwl-frontend-ui.php';
		}
		if ( !class_exists( 'Pie_WCWL_Waitlist' ) ) require_once 'classes/class-pie-wcwl-waitlist.php';


		if ( !class_exists( 'WooCommerce_Waitlist_Plugin' ) ) {

			/**
			 * Namespace class for functions non-specific to any object within the plugin
			 *
			 * @package  WooCommerce Waitlist
			 */
			class WooCommerce_Waitlist_Plugin {

				/**
				 * Admin UI class object
				 *
				 * @static
				 * @var object
				 */
				static $Pie_WCWL_Admin_UI;

				/**
				 * Frontend UI class object
				 *
				 * @static
				 * @var object
				 */
				static $Pie_WCWL_Frontend_UI;

				/**
				 * Path to plugin directory
				 *
				 * @static
				 * @var string
				 */
				static $path;

				/**
				 * Initialise the plugin and load the required objects for the use context
				 *
				 * @static
				 * @access public
				 * @return void
				 * @since 1.0.0
				 */
				public static function init() {
					self::$path= plugin_dir_path( __FILE__ );
					if ( is_admin() )
						self::$Pie_WCWL_Admin_UI = new Pie_WCWL_Admin_UI();
					else
						self::$Pie_WCWL_Frontend_UI = new Pie_WCWL_Frontend_UI();

					register_activation_hook( 'woocommerce-waitlist/woocommerce-waitlist.php', array( __CLASS__, 'create_empty_waitlists_on_published_products_with_no_existing_waitlist' ) );
					add_action( 'import_end', array( __CLASS__, 'create_empty_waitlists_on_published_products_with_no_existing_waitlist' ) );

					add_action( 'plugins_loaded', array( __CLASS__, 'localization' ) );
					add_action( 'admin_init', array( __CLASS__, 'version_check' ) );
					add_action( 'plugins_loaded' , array( __CLASS__, 'email_loader' ) );
					add_action( 'shutdown', array( __CLASS__, 'update_variable_product_waitlist_counts' ) , 20 );

					add_action( 'woocommerce_reduce_order_stock', array( __CLASS__, 'check_order_for_waitlisted_items' ) );

					add_action( 'delete_user', array( __CLASS__, 'unregister_user_when_deleted' ) );

					if ( ! WooCommerce_Waitlist_Plugin::automatic_mailouts_are_disabled() ) {
						// Hook up mailouts when any stock changes take place
						add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'perform_api_mailout_stock_status' ), 10, 2 );
						add_action( 'woocommerce_product_set_stock', array( __CLASS__, 'perform_api_mailout_stock' ), 10, 1 );
						add_action( 'woocommerce_variation_set_stock_status', array( __CLASS__, 'perform_api_mailout_stock_status' ), 10, 2 );
						add_action( 'woocommerce_variation_set_stock', array( __CLASS__, 'perform_api_mailout_stock' ), 10, 1 );
					}
				}

				/**
				 * Perform mailouts when stock status is updated and product is in stock
				 * We only want to do this for variations and simple products NOT variable (parent) products
				 *
				 * @param $product_id
				 * @param $stock_status
				 */
				public function perform_api_mailout_stock_status( $product_id, $stock_status ) {
					$product = wc_get_product( $product_id );

					if ( 'variable' != $product->product_type ) {
						if ( 'instock' == $stock_status || $product->is_in_stock() ) {
							error_log( $product->product_type );
							$product->waitlist = new Pie_WCWL_Waitlist( $product );
							$product->waitlist->waitlist_mailout( $product_id );
						}
					}
				}

				/**
				 * Perform mailouts when stock quantity is updated and product registers as in stock
				 * We only want to do this for variations and simple products NOT variable (parent) products
				 *
				 * @param $product
				 */
				public function perform_api_mailout_stock( $product ) {
					if ( 'variable' != $product->product_type ) {
						if ( $product->is_in_stock() ) {
							error_log( $product->product_type );
							$product->waitlist = new Pie_WCWL_Waitlist( $product );
							$product->waitlist->waitlist_mailout( $product->id );
						}
					}
				}

				/**
				 * Get the user object, check which products they are on the waitlist for and unregister them from each one when deleted
				 *
				 * @static
				 * @param  int $user_id id of the user that is being deleted
				 * @access public
				 * @return void
				 * @since  1.3
				 */
				public static function unregister_user_when_deleted( $user_id ) {
					$posts = WooCommerce_Waitlist_Plugin::return_all_products();
					$user = get_user_by( 'id', $user_id );
					$products = array();

					if ( $posts->have_posts() ) {
						while ( $posts->have_posts() ) {
							$posts->the_post();
							$post_id = get_the_ID();
							$products[$post_id] = get_product( $post_id );
							$products[$post_id]->waitlist = new Pie_WCWL_Waitlist( $products[$post_id] );
							$products[$post_id]->waitlist->unregister_user( $user );
						}
					}
				}

				/**
				 * Return all posts of type 'product' or 'product_variation'
				 *
				 * @static
				 * @access public
				 * @return array all product posts
				 * @since  1.3
				 */
				public static function return_all_products() {
					$args = array(
								'post_type' => array( 'product', 'product_variation' ),
								'posts_per_page' => -1
							 );
					return new WP_Query( $args );
				}

				/**
				 * First stage of two step hookup to add custom email.
				 *
				 * Our email class depends upon the WC_Email class in woocommerce. As such we must defer loading until the
				 * 'plugins_loaded' hook. Otherwise, we could apply this filter directly on init - as it is we get an error
				 * the WC_Email does not exist if we do it this way.
				 *
				 * @hooked plugins_loaded
				 * @static
				 * @access public
				 * @return void
				 */
				public static function email_loader() {
					add_filter( 'woocommerce_email_classes', array( __CLASS__, 'waitlist_mailout_init' ) );
				}

				/**
				 * Appends our Pie_WCWL_Waitlist_Mailout class to the array of WC_Email objects.
				 *
				 * @static
				 * @param  array $emails the woocommerce array of email objects
				 * @access public
				 * @return array         the woocommerce array of email objects with our email appended
				 */
				public static function waitlist_mailout_init( $emails ) {
					$emails['Pie_WCWL_Waitlist_Mailout'] = require 'classes/class-pie-wcwl-waitlist-mailout.php';
					return $emails;
				}

				/**
				 * Setup localization for plugin
				 *
				 * @hooked action plugins_loaded
				 * @static
				 * @access public
				 * @return void
				 */
				public static function localization() {
					load_plugin_textdomain( 'woocommerce-waitlist', false, dirname( plugin_basename( __FILE__ ) ) . '/assets/languages/' );
				}

				/**
				 * Check plugin version in DB and call required upgrade functions
				 *
				 * @hooked action admin_init
				 * @static
				 * @access public
				 * @return void
				 * @since 1.0.1
				 */
				public static function version_check() {
					$options = get_option( WCWL_SLUG );

					if ( !isset( $options['version'] ) )
						self::upgrade_version_1_0();

					if ( version_compare( $options['version'], '1.1.0' ) < 0 )
						self::upgrade_version_1_0_4();

					$options = get_option( WCWL_SLUG );
					$options['version'] = WCWL_VERSION;
					update_option( WCWL_SLUG, $options );
				}

				/**
				 * Individually calls all functions required to upgrade from version 1.0
				 *
				 * @static
				 * @access public
				 * @return void
				 * @since 1.0.1
				 */
				public static function upgrade_version_1_0() {
					self::create_empty_waitlists_on_published_products_with_no_existing_waitlist();
				}

				/**
				 * Individually calls all functions required to upgrade from version 1.0.4
				 *
				 * @static
				 * @access public
				 * @return void
				 * @since 1.1.0
				 */
				public static function upgrade_version_1_0_4() {
					self::move_variable_product_waitlist_entries_to_first_out_of_stock_variation();
				}


				/**
				 * Moves all waitlist entries on variable products to one of their variations
				 *
				 * This function is necessary when upgrading to version 1.1.0 - Prior to 1.1.0, waitlists for variable
				 * products were tracked against the parent product, and it was not possible to register for a waitlist on
				 * a product variation. This missing feature caused problems when one variation was out of stock and another
				 * in stock.
				 *
				 * In version 1.1.0, this feature has been added. Product variations can now hold their own waitlist, and
				 * the variable product parents now hold a waitlist containing all registrations for their child products.
				 * To bridge this upgrade gap, any waitlist registrations for a variable product will be moved to the first
				 * product variation that is out of stock.
				 *
				 * @static
				 * @access public
				 * @return void
				 * @since 1.1.0
				 */
				public static function move_variable_product_waitlist_entries_to_first_out_of_stock_variation() {
					global $wpdb;
					$products = $wpdb->get_col( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '" . WCWL_SLUG . "' and meta_value <> 'a:0:{}'" );
					$moved_waitlists_at_1_0_4_upgrade = array();

					foreach ( $products as $product_id ) {
						$product = get_product( $product_id );

						if ( $product->is_type( 'variable' ) ) {

							$waitlist = get_post_meta( $product_id, WCWL_SLUG, true );
							$moved_waitlists_at_1_0_4_upgrade[ $product_id ] = array( 'origin' => $product_id, 'user_ids' => $waitlist, 'target' => 0 );

							foreach ( $product->get_children() as $variation_id ) {
								$variation = get_product( $variation_id, true );
								if ( !$variation->is_in_stock() ) {
									$variation->waitlist = new Pie_WCWL_Waitlist( $variation );

									foreach ( $waitlist as $user_id ) {
										$variation->waitlist->register_user( get_user_by( 'id', $user_id ) );
									}
									$moved_waitlists_at_1_0_4_upgrade[ $product_id ][ 'target' ] = $variation_id;
									break;
								}
							}
						}
					}
					if ( !empty( $moved_waitlists_at_1_0_4_upgrade ) ) {
						$options = get_option( WCWL_SLUG );
						$options['moved_waitlists_at_1_0_4_upgrade'] = $moved_waitlists_at_1_0_4_upgrade;
						update_option( WCWL_SLUG, $options );
						add_action( 'admin_notices', self::$Pie_WCWL_Admin_UI->alert_user_of_moved_waitlists_at_1_0_4_upgrade() );
					}
				}

				/**
				 * Adds an empty waitlist to all products with no waitlist
				 *
				 * lightweight function to loop through all products and create an empty waitlist for each. These empty
				 * waitlists fix a bug from 1.0 present when sorting by waitlist in the Admin UI. It is hooked onto
				 * activation and when an upgrade from version 1.0 is detected. It's also hooked onto import_end which
				 * lets us play nicely with WordPress Importer (http://wordpress.org/extend/plugins/wordpress-importer) and
				 * WooCommerce Product CSV Import Suite (http://www.woothemes.com/products/product-csv-import-suite)
				 *
				 * Due to memory issues reported when running this function on stores with many products, it has been
				 * wrapped in an if block to prevent triggering, and can be disbled by changing definitions.php and setting
				 * WCWL_AUTO_WAITLIST_CREATION to false.
				 *
				 * @hooked activation, import_end
				 * @static
				 * @access public
				 * @return void
				 * @since 1.0.1
				 */
				public static function create_empty_waitlists_on_published_products_with_no_existing_waitlist() {
					if ( WCWL_AUTO_WAITLIST_CREATION ) {
						global $wpdb;
						$products = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_type = 'product'" );

						foreach ( $products as $product_id ) {
							if ( !is_array( get_post_meta( $product_id, WCWL_SLUG, true ) ) )
								update_post_meta( $product_id, WCWL_SLUG, array() );
						}
					}
				}

				/**
				 * Check if users must log in to join waitlist
				 *
				 * This function is only returning true because the registration of logged out users onto waitlists is not
				 * currently being supported but may be added in a future version.
				 *
				 *
				 * @static
				 * @access public
				 * @return bool
				 * @since 1.0.1
				 */
				public static function users_must_be_logged_in_to_join_waitlist() {
					if ( get_option( 'woocommerce_waitlist_registration_needed' ) == 'yes' )
						return true;
					return false;
				}

				/**
				 * Check if persistent waitlists are disabled
				 *
				 * Filterable function to switch on persistent waitlists. Persistent waitlists will prevent users from being
				 * removed from a waitlist after email is sent, instead being removed when they purchase an item.
				 *
				 * @static
				 * @access public
				 * @return bool
				 * @since 1.1.1
                 *
                 * @todo include product id into filter to enable persistent waitlists for specific products
				 */
				public static function persistent_waitlists_are_disabled() {
					return apply_filters( 'wcwl_persistent_waitlists_are_disabled', true );
				}

				/**
				 * Check if we want to display empty waitlist meta boxes on in-stock products
				 *
				 * Filterable function to provide control over waitlist meta-box display. Default behaviour is for
				 * the waitlist meta-box to display on:
				 * 		products that are out of stock
				 * 		products that are in stock and have users on their waitlist
				 *
				 * making this filter return tru will display these meta boxes at all times
				 *
				 * @static
				 * @access public
				 * @return bool
				 * @since 1.3.9
				 */
				public static function display_empty_waitlists_on_in_stock_products() {
					return apply_filters('wcwl_display_empty_waitlists_on_in_stock_products', false );
				}

				/**
				 * Check if automatic mailouts are disabled. If so, no email will be sent to waitlisted users when a product
				 * returns to stock and as such they will remain on the waitlist.
				 *
				 * @static
				 * @access public
				 * @return bool
				 * @since  1.1.8
                 *
                 * @todo include product id into filter to enable mailouts for specific products
				 */
				public static function automatic_mailouts_are_disabled(){
					return apply_filters( 'wcwl_automatic_mailouts_are_disabled', false );
				}

				/**
				 * Function for making backwards compatible calls to wc_add_notice, supporting WC 2.0 and 2.1+
				 *
				 * @param  string $message
				 * @param  string $type
				 * @access public
				 * @since  1.1.7
				 */
				public static function add_notice( $message, $type = 'success' ){
					if ( function_exists( 'wc_add_notice' ) )
						return wc_add_notice( $message, $type );
					global $woocommerce;
					switch ( $type ) {
						case 'error':
							return $woocommerce->add_error( $message );
						default: //success
							return $woocommerce->add_message( $message );
					}
				}

				/**
				 * Removes user from waitlist on purchase if persistent waitlists are enabled
				 *
				 * @static
				 * @param  object $order WC_Order object
				 * @access public
				 * @return void
				 */
				public static function check_order_for_waitlisted_items( $order ) {
					if ( self::persistent_waitlists_are_disabled()  )
						return;
					if ( self::automatic_mailouts_are_disabled() )
						return;

					$user = get_user_by( 'id', $order->user_id );
					foreach ( $order->get_items() as $item ) {

						if ( $item['id']>0 ) {
							$_product = $order->get_product_from_item( $item );
							$waitlist = new Pie_WCWL_Waitlist( $_product );
							$waitlist->unregister_user( $user );
						}
					}
				}
				/**
				 * Wrapper for get_posts, returning all products for which the user is on the waitlist. This is currently a
				 * patchfix function to enable a user waitlist summary in the frontend. It really should be factored out in
				 * the future. Possibly change the way we store waitlists? add usermeta?
				 *
				 * @static
				 * @param  int $user_id
				 * @access public
				 * @return array    array of post objects
				 * @since  1.1.3
				 */
				public static function get_waitlist_products_by_user_id( $user_id  ) {
					$args = array(
						'post_type'=>'product',
						'numberposts'=>'-1',
						'meta_key' => WCWL_SLUG
					);
					return array_filter( get_posts( $args ), array( __CLASS__, 'current_user_is_on_waitlist_for_product' ) );
				}

				/**
				 * Patch fix removing closure from function above
				 *
				 * @static
				 * @access public
				 *
				 * @param $product
				 *
				 * @return bool
				 * @since  1.1.4
				 */

				public static function current_user_is_on_waitlist_for_product( $product ) {
					return in_array( get_current_user_id(), get_post_meta( $product->ID, WCWL_SLUG , true ) );
				}

				/**
				 * Updates the counts for variable products
				 *
				 * In order for the waitlist counts on the product listings in admin to be accurate for variable products,
				 * we need to total up the number of waitlist registrations for their child product variations. We do this
				 * by adding the parent variable product id to the $updated_products array used below through the
				 * wcwl_updated_variable_products filter every time a waitlist is changed for a WC_Product_Variation.
				 *
				 * We then loop through this array and combine all the waitlist registrations for child products into one
				 * waitlist entry attached to the parent product
				 *
				 * @hooked shutdown
				 * @static
				 * @access public
				 * @return void
				 * @since 1.1.0
				 */
				public static function update_variable_product_waitlist_counts() {
					$updated_products = apply_filters( 'wcwl_updated_variable_products' , array() );
					foreach ( array_unique( $updated_products ) as $product_id ) {
						$waitlist = array();
						$product = get_product( $product_id );
						foreach ( $product->get_children() as $child_id ) {
							$waitlist = array_merge( $waitlist, get_post_meta( $child_id, WCWL_SLUG, true ) );
						}
						update_post_meta( $product_id, WCWL_SLUG, $waitlist );
					}
				}
			}
			WooCommerce_Waitlist_Plugin::init();
		}
	}
}
