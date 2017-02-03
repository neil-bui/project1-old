<?php
/**
 * The front end user interface for the plugin
 *
 * @package  WooCommerce Waitlist
 */
class Pie_WCWL_Frontend_UI {

	/**
	 * WP_User object representing the currently logged in user
	 *
	 * @var object
	 * @access private
	 */
	private $User;

	/**
	 * WC_Product object currently being viewed
	 *
	 * @var object
	 * @access private
	 */
	private $Product;

	/**
	 * the string used by this plugin for passing product_ids around in $_REQUEST variables
	 *
	 * @var string
	 * @access private
	 */
	private $product_id_slug;

	/**
	 * woocommerce global, used in this plugin for holding user notifications and error messages
	 *
	 * @var object
	 * @access private
	 */
	private $messages;

	/**
	 * Hooks up the frontend initialisation and any functions that need to run before the 'init' hook
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action ( 'init', array( &$this, 'remove_woocommerce_add_to_cart_action_if_not_required' ), 5 );
		add_action ( 'wp', array( &$this, 'frontend_init' ) );
		add_action ( 'wp_enqueue_scripts', array( &$this, 'frontend_enqueue_scripts' ), 99999 );
	}

	/**
	 * Enqueue scripts and styles for the frontend if user is on a product page
	 *
	 * @access public
	 * @return void
	 * @since  1.3
	 */
	public function frontend_enqueue_scripts() {
		global $post;
		if ( 'product' !== get_post_type( $post ) )
			return;

		wp_enqueue_script( 'wcwl_frontend', plugins_url( 'woocommerce-waitlist/includes/js/wcwl_frontend.js' ), array(), '1.0.0', true );
		wp_enqueue_style( 'wcwl_frontend', plugins_url( 'woocommerce-waitlist/includes/css/wcwl_frontend.css' ) );
	}

	/**
	 * initialises the frontend UI, hooking up required functions and setting up required objects for each product type
	 *
	 * If we're not viewing a product in the frontend, the whole thing just exits. Otherwise we populate the Class
	 * parameters (including adding our Waitlist object to the WC_Product) and hook up any required functions.
	 *
	 * @hooked action init
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function frontend_init() {
		require_once WooCommerce_Waitlist_Plugin::$path . '/shortcodes.php';

		global $woocommerce;
		global $post;

		$this->User = is_user_logged_in() ? wp_get_current_user() : false ;

		if ( 'product' !== get_post_type( $post ) )
			return;

		$this->setup_frontend_class( $woocommerce, $post );

		if ( $this->Product->is_type( 'simple' ) && !$this->Product->is_in_stock() ) {

			if ( $this->request_is_valid() )
				$this->toggle_waitlist_action( $this->Product );

			$this->output_control_for_simple_product();
		}

		if ( $this->Product->is_type( 'variable' ) ) {

			$changed_product = false;
			if ( $this->request_is_valid() ) {
				$changed_product = $this->return_changed_variable_product();
				$this->toggle_waitlist_action( $changed_product );
			}
			$this->output_control_for_variable_products( $changed_product );
		}

		if ( $this->Product->is_type( 'grouped' ) ) {

			$changed_products = array();
			if ( $this->request_is_valid() ) {
				$changed_products = is_array( $this->return_changed_grouped_products( $changed_products ) ) ? $this->return_changed_grouped_products( $changed_products ) : array() ;
				$this->toggle_waitlist_action( $changed_products );
			}
			$this->output_control_for_grouped_products( $changed_products, false );
		}
	}

	/**
	 * Setup required variables for the frontend UI
	 *
	 * @param  object $woocommerce
	 * @param  object $post        current post object
	 * @access public
	 * @return void
	 * @since 1.3
	 */
	public function setup_frontend_class( $woocommerce, $post ) {
		$this->messages = $woocommerce;
		$this->Product = get_product( $post->ID );
		$this->product_id_slug = WCWL_SLUG . '_product_id';
		$this->setup_text_strings();
		add_filter( 'woocommerce_add_to_cart_url', array( &$this, 'remove_waitlist_parameters_from_query_string' ) );
		$this->Product->Waitlist = new Pie_WCWL_Waitlist( $this->Product );
	}

	/**
	 * Add filters to append the required waitlist control elements to the frontend for simple products
	 *
	 * @access public
	 * @return void
	 * @since  1.3
	 */
	public function output_control_for_simple_product() {
		add_filter( 'woocommerce_stock_html', array( &$this, 'append_waitlist_control' ), 20 );
		add_filter( 'woocommerce_get_availability', array( &$this, 'append_waitlist_message' ), 20, 2 );
	}

	/**
	 * Add actions filters to append the required waitlist control elements to the frontend for variable products
	 *
	 * @param  object $changed_product updated variable product
	 * @access public
	 * @return void
	 * @since  1.3
	 */
	public function output_control_for_variable_products( $changed_product ) {
		foreach ( $this->Product->get_children() as $key => $child_id ) {

			if ( $changed_product && $child_id == $changed_product->variation_id  )
				$this->Product->children[ $child_id ] = $changed_product;
			else {
				$this->Product->children[ $child_id ] = $this->Product->get_child( $child_id );
				$this->Product->children[ $child_id ]->Waitlist = new Pie_WCWL_Waitlist( $this->Product->children[ $child_id ] );
			}
			unset( $this->Product->children[ $key ] );
			$this->hook_functions_for_variable_products();
		}
	}

	/**
	 * Hook functions required to output frontend UI for waitlist on variable products
	 *
	 * @access public
	 * @return void
	 * @since  1.3
	 */
	public function hook_functions_for_variable_products() {
		add_filter( 'woocommerce_get_availability', array( &$this, 'append_waitlist_message' ), 20, 2 );
		add_action( 'woocommerce_get_availability', array( &$this, 'append_waitlist_control_for_variable_products' ), 21, 2 );
		add_filter( 'woocommerce_stock_html', array( &$this, 'append_waitlist_control_if_user_unknown' ), 20 );
	}

	/**
	 * Add actions filters to append the required waitlist control elements to the frontend for grouped products
	 *
	 * Different filters used before woocommerce version 2.1 for the checkboxes on each product
	 *
	 * @param  object $changed_products updated variable product
	 * @param  boolean $out_of_stock initially false, changed to true if product appears out of stock
	 * @access public
	 * @return void
	 * @since  1.3
	 */
	public function output_control_for_grouped_products( $changed_products, $out_of_stock ) {
		foreach ( $this->Product->get_children() as $child_id ) {

			if ( isset( $changed_products ) && array_key_exists( $child_id, $changed_products ) )
				$this->Product->children[ $child_id ] = $changed_products[$child_id];
			else {
				$this->Product->children[ $child_id ] = $this->Product->get_child( $child_id );
				$this->Product->children[ $child_id ]->Waitlist = new Pie_WCWL_Waitlist( $this->Product->children[ $child_id ] );
			}

			if ( $this->Product->children[ $child_id ]->is_in_stock() )
				continue;

			$out_of_stock = true;
		}

		if ( $out_of_stock )
			$this->hook_functions_for_grouped_products();
	}

	/**
	 * Hook functions required to output frontend UI for waitlist on grouped products
	 *
	 * @access public
	 * @return void
	 * @since  1.3
	 */
	public function hook_functions_for_grouped_products() {
		global $woocommerce;

		if( version_compare( $woocommerce->version, '2.1-beta-1', '>=' ) )
			add_filter( 'woocommerce_stock_html', array( &$this, 'append_waitlist_control_for_grouped_child_products' ), 20 );
		else
			add_filter( 'woocommerce_get_availability', array( &$this, 'outdated_append_waitlist_control_for_children_of_grouped_products' ), 20, 2 );

		add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'output_waitlist_control' ), 20 );
		add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'output_grouped_product_waitlist_message' ) );
		add_action( 'wp_print_styles', array( &$this, 'print_grouped_product_style_block' ) );
	}

	/**
	 * Return the variable product for which the waitlist needs to be updated
	 *
	 * @access public
	 * @return object $changed_product
	 * @since  1.3
	 */
	public function return_changed_variable_product() {

		$changed_product = get_product( $_REQUEST[ WCWL_SLUG ] );
		$changed_product->Waitlist = new Pie_WCWL_Waitlist ( $changed_product );
		return $changed_product;
	}

	/**
	 * Return the grouped products for which the waitlists needs to be updated
	 *
	 * @param  array $changed an array of requested products whose waitlist status needs to be updated
	 * @access public
	 * @return array $changed_products an array of changed product objects
	 * @since  1.3
	 */
	public function return_changed_grouped_products( $changed ) {
		$changed_products = array();

		if ( isset( $_REQUEST['wcwl_changed'] ) && !empty($_REQUEST['wcwl_changed']) )
			$changed = explode( ',', $_REQUEST['wcwl_changed']);

		foreach ( $changed as $changed_product_id ) {
			$changed_product_id = str_replace( 'wcwl_checked_', '', $changed_product_id );
			$changed_products[$changed_product_id] = get_product( $changed_product_id );
			$changed_products[$changed_product_id]->Waitlist = new Pie_WCWL_Waitlist( $changed_products[$changed_product_id] );
		}
		return $changed_products;
	}

	/**
	 * Check the update request is valid
	 *
	 * @access public
	 * @return boolean true if valid, false if not
	 * @since  1.3
	 */
	public function request_is_valid() {
		if ( isset( $_REQUEST[ WCWL_SLUG ] ) && is_numeric( $_REQUEST[ WCWL_SLUG ] ) && !isset( $_REQUEST['added-to-cart'] ) )
			return true;
		else
			return false;
	}

	/**
	 * This function modifies the string in place of the 'add to cart' option, adding in an email field when the user is not logged in.
	 * JS has to be added here as enqueuing it does not add it late enough (do not have access to the email input field yet)
	 *
	 * @param string  $string current waitlist string
	 * @access public
	 * @return string $string modified string
	 * @todo  get access to variation id via woocommerce hook at this point to easily modify information shown rather than using strpos to check
	 * the item is valid and str_replace to insert the email field in the correct place. Move JS to its own file
	 * @since  1.3
	 */
	public function append_waitlist_control_if_user_unknown( $string ) {
		if ( is_user_logged_in() )
			return $string;

		if( false !== strpos( $string, 'woocommerce_waitlist_nonce' ) ) {
			if ( !WooCommerce_Waitlist_Plugin::users_must_be_logged_in_to_join_waitlist() )
				$string = str_replace( '<div>', '</p>' . $this->get_waitlist_email_field() . '<div class="wcwl_control">', $string );

			$string .= '<script type="text/javascript">
   							jQuery(document).ready( function( $ ){
   								var a_href = $("a.woocommerce_waitlist").attr("href");
								$("#wcwl_email").on("input",function(e){
									var wcwl_email = $("#wcwl_email").val();
     								$("a.woocommerce_waitlist").prop("href", a_href+"&wcwl_email="+wcwl_email );
    							});
							});
						</script>';
		}
		return $string;
	}

	/**
	 * This function modifies the string outputted after the price field for grouped products, adding in a checkbox field for each out of stock product.
	 *
	 * At this stage global $product returns the object of the current child product as of woocommerce v2.1
	 * WC v2.2 requires a conditional to check for the 'out of stock' message before displaying our checkboxes otherwise they display for 'in stock' products also
	 *
	 * @param string $string current waitlist string
	 * @access public
	 * @return string modified string
	 * @since  1.3
	 */
	public function append_waitlist_control_for_grouped_child_products( $string ) {
		if ( strpos( $string, 'Out of stock' ) === false )
			return $string;

		global $product;
		$child_product_waitlist = $this->Product->children[ $product->id ]->Waitlist;

		if ( is_user_logged_in() && $child_product_waitlist->user_is_registered( $this->User ) ) {
			$context = 'leave';
			$checked = 'checked';
		}
		else {
			$context = 'join';
			$checked = '';
		}

		$string = '<p class="stock out-of-stock">' . __( 'Out of stock ', 'woocommerce-waitlist' ) . '<label class="' . WCWL_SLUG . '_label" > - ' . apply_filters( 'wcwl_' . $context . '_waitlist_button_text', $this->join_waitlist_button_text ) . '<input id="wcwl_checked_' . $product->id . '" class="wcwl_checkbox" type="checkbox" name="' . ( 'join' == $context ? $context : $this->product_id_slug . '[]' ) . '"' . $checked . '/></label></p>';
		return $string;
	}

	/**
	 * Appends the waitlist control HTML for child products of a grouped product to the 'availability' member of an array
	 * Not used since woocommerce 2.1
	 *
	 * @hooked filter woocommerce_get_availability
	 * @deprecated 2.0.20
	 * @deprecated global product returns correct child product id as of v2.1
	 * @param array   $array        'availability'=>availability string,'class'=>class for availability element
	 * @param object  $this_product WC_Product
	 * @access public
	 * @return array The $array parameter with appropriate button HTML appended to $array['availability']
	 * @since 1.0
	 */
	public function outdated_append_waitlist_control_for_children_of_grouped_products( $array, $child_product ) {
		if ( !$child_product->is_in_stock() ) {

			$child_product_waitlist = $this->Product->children[ $child_product->id ]->Waitlist ;

			$context = 'dummy';
			if ( is_user_logged_in() )
				$context = $child_product_waitlist->user_is_registered( $this->User ) ? 'leave' : 'join'  ;

			$array['availability'] .=  $this->outdated_get_grouped_product_control( $context, $this->Product->children[ $child_product->id ] )  ;

		}
		return $array;
	}

	/**
	 * Get waitlist control for grouped products
	 * Not used since woocommerce 2.1
	 *
	 * @deprecated 2.0.20
	 * @param mixed   $context       Description.
	 * @param mixed   $child_product Description.
	 * @access public
	 * @return mixed Value.
	 * @since 1.1.0
	 */
	public function outdated_get_grouped_product_control( $context, $child_product ) {
		return $this->get_waitlist_control( $context, 'checkbox' , $child_product );
	}

	/**
	 * This function currently returns HTML for a list table of all the products a user is on the waitlist for, with a
	 * link through to the product to remove themselves. I would suggest this can
	 * be refactored and possibly moved to the Waitlist Object. The HTML output could be removed also and placed within
	 * filters
	 *
	 * @access public
	 * @return string The HTML for the waitlist table
	 * @since 1.1.3
	 */
	public function current_user_waitlist() {
		if ( !$this->User )
			return;

		$waitlist_products = WooCommerce_Waitlist_Plugin::get_waitlist_products_by_user_id( $this->User->ID );

		$content = '<h2 class="my_account_titles">' . __( 'Your Waitlist', 'woocommerce-waitlist' ) . '</h2>';

		if ( is_array( $waitlist_products ) && !empty( $waitlist_products ) ) {

			$cached_product = $this->Product;
			$content .= '<p>' . __( 'You are currently on the waitlist for the following products.', 'woocommerce-waitlist' ) . '</p><table class="shop_table"><tbody>';

			foreach ( $waitlist_products as $post ) {
				$content = $this->return_html_for_each_product( $post, $content );
			}
			wp_reset_postdata();
			$this->Product = $cached_product;
			$content .= '</tbody></table>';

		} else
			$content .= '<p>' . __( 'You have not yet joined the waitlist for any products.', 'woocommerce-waitlist' ) . '</p>';

		return $content;
	}

	/**
	 * Returns the HTML for the required product in a table row ready for diplay on frontend
	 *
	 * @param  object $post    required product post object
	 * @param  string $content current HTML string
	 * @access public
	 * @return string          updated HTML string
	 */
	public function return_html_for_each_product( $post, $content ) {
		$this->Product = get_product( $post->ID );
		$this->Product->Waitlist = new Pie_WCWL_Waitlist( $this->Product );
		$content .= '<tr><td>';

		if ( has_post_thumbnail( $post->ID ) )
			$content .= get_the_post_thumbnail( $post->ID, 'shop_thumbnail' );
		$content .= '</td><td><a href="' . get_permalink( $post->ID ) . '"  >' . esc_html( get_the_title( $post->ID ) ) . '</a></td></tr>';

		return $content;
	}

	/**
	 * Catches the $_REQUEST parameter for waitlist toggling
	 *
	 * This function catches the input from any product type, performs some validation and then
	 * either sets the appropriate response message if invalid or calls the toggle_waitlist function if valid
	 *
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function toggle_waitlist_action( $product = false ) {
		if ( !is_user_logged_in() ) {

			if ( WooCommerce_Waitlist_Plugin::users_must_be_logged_in_to_join_waitlist() )
				return WooCommerce_Waitlist_Plugin::add_notice( $this->get_toggle_waitlist_no_user_message(), 'error' );

			return $this->handle_waitlist_when_new_user( $product );
		}

		if ( !wp_verify_nonce( $_REQUEST[ WCWL_SLUG . '_nonce'], __FILE__ ) )
			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_toggle_waitlist_ambiguous_error_message_text', $this->toggle_waitlist_ambiguous_error_message_text ), 'error' );

		if ( !$product && !is_array( $product ) )
			$product = $this->Product;

		if (  $this->Product->is_type( 'grouped' ) ) {

			if ( empty( $product ) )
				return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_toggle_waitlist_no_product_message_text', $this->toggle_waitlist_no_product_message_text ), 'error' );
			$changed_products = $product;

			foreach ( $changed_products as $changed_product ) {

				$Waitlist = $changed_product->Waitlist;

				if ( !$Waitlist->register_user( $this->User ) )
					$Waitlist->unregister_user( $this->User );
			}
			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_update_waitlist_success_message_text', $this->update_waitlist_success_message_text ) );
		}
		return $this->toggle_waitlist( $product->Waitlist );
	}

	/**
	 * Toggles the Frontend User waitlist status for the passed in waitlist, setting the appropriate response message.
	 *
	 * @param object  $Waitlist Pie_WCWL_Waitlist
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function toggle_waitlist( $Waitlist ) {
		if ( $Waitlist->user_is_registered( $this->User ) && $Waitlist->unregister_user( $this->User ) )
			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_leave_waitlist_success_message_text', $this->leave_waitlist_success_message_text ) );
		if ( !$Waitlist->user_is_registered( $this->User ) && $Waitlist->register_user( $this->User ) )
			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_join_waitlist_success_message_text', $this->join_waitlist_success_message_text ) );

		return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_toggle_waitlist_ambiguous_error_message_text', $this->toggle_waitlist_ambiguous_error_message_text ), 'error' );
	}

	/**
	 * Appends the waitlist button HTML to text string
	 *
	 * @hooked filter woocommerce_stock_html
	 * @param string  $string HTML for Out of Stock message
	 * @access public
	 * @return string HTML with Waitlist button appended if product is out of stock
	 * @since 1.0
	 */
	public function append_waitlist_control( $string = '' ) {
		global $woocommerce;
		$string .= '<div class="wcwl_control"';

		if ( !is_user_logged_in() && $this->Product->is_type( 'simple' ) )
			$string = $this->append_waitlist_control_for_logged_out_user( $string );
		else {
			if ( $this->Product->is_type( 'grouped' ) )
				$string = $this->append_waitlist_control_for_grouped_products( $string );
			elseif ( $this->Product->Waitlist->user_is_registered( $this->User )  )
				$string .=  $this->get_waitlist_control( 'leave' );
			else
				$string .=  $this->get_waitlist_control( 'join' );
		}
		$string .= '</div>';
		return $string;
	}

	/**
	 * Appends the email input field and waitlist button HTML to text string for simple products
	 *
	 * @param string  $string HTML for Out of Stock message
	 * @access public
	 * @return string HTML with email field and Waitlist button appended
	 * @since  1.3
	 */
	public function append_waitlist_control_for_logged_out_user( $string ) {
		$url = $this->toggle_waitlist_url();
		$string .= '<form name="wcwl_add_user_form" action="' . esc_url( $url ) . '" method="post">';

		if ( !WooCommerce_Waitlist_Plugin::users_must_be_logged_in_to_join_waitlist() )
			$string .= $this->get_waitlist_email_field();

		$string .= $this->get_waitlist_control( 'join' ) . '</form>';
		return $string;
	}

	/**
	 * Appends the waitlist button HTML to variable products
	 *
	 * @hooked filter woocommerce_get_availability
	 * @param array   $array        'availability'=>availability string,'class'=>class for availability element
	 * @param object  $this_product WC_Product
	 * @access public
	 * @return array The $array parameter with appropriate message text appended to $array['availability']
	 * @since 1.0
	 */
	public function append_waitlist_control_for_variable_products( $array, $product ) {
		$product = $this->Product->children[ $product->variation_id ];

		if ( $product->is_in_stock() )
			return $array;

		if ( !$product->Waitlist->user_is_registered( $this->User ) )
			$array['availability'] .= '<div>' . $this->get_variable_product_control( 'join', $product ) . '</div>';
		else
			$array['availability'] .= '<div>' . $this->get_variable_product_control( 'leave', $product ) . '</div>';

		return $array;
	}

	/**
	 * Checks if user is logged in and appends the email input field and waitlist button HTML to text string as required
	 *
	 * @param string  $string HTML for Out of Stock message
	 * @access public
	 * @return string HTML with appropriate fields and Waitlist button appended
	 * @since  1.3
	 */
	public function append_waitlist_control_for_grouped_products( $string ) {
		if ( !is_user_logged_in() && !WooCommerce_Waitlist_Plugin::users_must_be_logged_in_to_join_waitlist() ) {
			$string .= $this->get_waitlist_email_field();
			$string .= $this->get_waitlist_control( 'join', 'grouped' );
		}
		else
			$string .= $this->get_waitlist_control( 'update', 'grouped' );
		return $string;
	}

	/**
	 * Outputs the required HTML from 'append_waitlist_control'
	 *
	 * @access public
	 * @return void
	 */
	public function output_waitlist_control() {
		echo $this->append_waitlist_control();
	}

	/**
	 * Checks whether product is in stock and if not, appends the waitlist message of 'join/leave waitlist' to the 'out of stock' message
	 *
	 * @param array   $array   stock details
	 * @param object  $product the current product
	 * @access public
	 * @return mixed Value.
	 * @since 1.1.0
	 */
	public function append_waitlist_message( $array, $product ) {
		if ( $this->Product->is_type( 'variable' ) )
			$product = $this->Product->children[ $product->variation_id ];
		else
			$product = $this->Product;

		if ( !$product->is_in_stock() ) {
			if ( !is_user_logged_in() || !$product->Waitlist->user_is_registered( $this->User ) )
				$array['availability'] .= apply_filters( 'wcwl_join_waitlist_message_text', ' - ' . $this->join_waitlist_message_text );
			else
				$array['availability'] .= apply_filters( 'wcwl_leave_waitlist_message_text', ' - ' . $this->leave_waitlist_message_text );
		}
		return $array;
	}

	/**
	 * Outputs the appropriate Grouped Product message HTML
	 *
	 * @hooked action woocommerce_after_add_to_cart_form
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function output_grouped_product_waitlist_message() {
		$classes = implode( ' ', apply_filters( 'wcwl_grouped_product_message_classes', array( 'out-of-stock', WCWL_SLUG ) ) );

		if ( is_user_logged_in() )
			$text = apply_filters( WCWL_SLUG . '_grouped_product_message_text', $this->grouped_product_message_text );
		else
			$text = apply_filters( WCWL_SLUG . '_grouped_product_message_text', $this->no_user_grouped_product_message_text );

		echo apply_filters( WCWL_SLUG . '_grouped_product_message_html', '<p class="' . esc_attr( $classes ) . '">' . $text . '</p>' );
	}

	/**
	 * Get HTML for variable products
	 *
	 * @param string   $context       join/leave depending on whether user is on waitlist or not
	 * @param object   $child_product the required product
	 * @access public
	 * @return mixed Value.
	 * @since 1.1.0
	 */
	public function get_variable_product_control( $context, $child_product ) {
		return $this->get_waitlist_control( $context, 'anchor', $child_product );
	}

	/**
	 * Get HTML for waitlist elements depending on product type
	 *
	 * @param object  $product WC_Product for which to get button HTML
	 * @param string  $context the context in which the button should be generated (join|leave|dummy)
	 * @param string  $type    optional - the HTML element to generate. anchor|submit. Defaults to anchor
	 * @access public
	 * @return string HTML for join waitlist button
	 * @since 1.0
	 */
	public function get_waitlist_control( $context, $type = 'anchor', $product = false ) {
		$text_parameter = $context . '_waitlist_button_text' ;
		$classes = implode( ' ', apply_filters( 'wcwl_' . $context . '_waitlist_button_classes', array( 'button', 'alt', WCWL_SLUG, $context ) ) );
		$text = apply_filters( 'wcwl_' . $context . '_waitlist_button_text', $this->$text_parameter );
		$product = $product ? $product : $this->Product;

		switch ( $type ) {

		case 'submit':

			return apply_filters( 'wcwl_' . $context . '_waitlist_submit_button_html', '<input type="submit" class="' . esc_attr( $classes ) . '" id="' . esc_attr( WCWL_SLUG )  .'-product-'. esc_attr( $this->Product->id ).'" name="' . WCWL_SLUG . '" value="' . esc_attr( $text ) . '" />' );
			break;

		case 'checkbox':

			$checked = $product->Waitlist->user_is_registered( $this->User );

			return apply_filters( 'wcwl_' . $context . '_waitlist_submit_button_html', '<label> - ' . apply_filters( 'wcwl_' . $context . '_waitlist_button_text', $this->join_waitlist_button_text ) . '<input type="checkbox" class="wcwl_checkbox" id="wcwl_checked_' . esc_attr( $product ? $product->id : $this->Product->id ) . '" name="' . ( 'dummy' == $context ? $context : $this->product_id_slug . '[]' ) .'" value="' . esc_attr( $product ? $product->id : $this->Product->id ) . '" ' . ( $checked ? 'checked' : '' ) . ' /></label>' );
			break; //needed?

		case 'grouped':

			return $this->get_waitlist_control_for_grouped_product( $context, $classes, $text, $product );

		default: //anchor - variable and simple products

			if ( $product && $this->Product->is_type( 'variable' ) )
				return $this->get_waitlist_control_for_variable_product( $context, $classes, $text, $product );
			else
				return $this->get_waitlist_control_for_simple_product( $context, $classes, $text, $product );
		}
	}

	/**
	 * Get HTML for variable product waitlist button
	 *
	 * @param string  $context the context in which the button should be generated (join|leave)
	 * @param string  $classes the classes to apply to the control element
	 * @param string  $text    the text to display on the button (update|join|leave waitlist)
	 * @param object  $product WC_Product for which to get button HTML
	 * @access public
	 * @return string HTML for join waitlist button
	 * @since  1.3
	 */
	public function get_waitlist_control_for_variable_product( $context, $classes, $text, $product ) {
		$url = $this->toggle_waitlist_url( $product->variation_id );
		return apply_filters( 'wcwl_' . $context . '_waitlist_button_html', '<div class="wcwl_control"><a href="' . esc_url( $url ) . '" class="' . esc_attr( $classes ) . '" data-id="' . $product->variation_id . '" id="wcwl-product-'. esc_attr( $product->variation_id ).'">' . esc_html( $text ) . '</a></div>' );
	}

	/**
	 * Get HTML for grouped product waitlist button
	 *
	 * @param string  $context the context in which the button should be generated (join|leave)
	 * @param string  $classes the classes to apply to the control element
	 * @param string  $text    the text to display on the button (update|join|leave waitlist)
	 * @param object  $product WC_Product for which to get button HTML
	 * @access public
	 * @return string HTML for join waitlist button
	 * @since  1.3
	 */
	public function get_waitlist_control_for_grouped_product( $context, $classes, $text, $product ) {
		$url = $this->toggle_waitlist_url();
		return apply_filters( 'wcwl_' . $context . '_waitlist_submit_button_html', '<div class="wcwl_control"><a href="' . esc_url( $url ) . '" class="' . esc_attr( $classes ) . '" data-id="' . $product->id . '" id="wcwl-product-'. esc_attr( $product->id ).'">' . esc_html( $text ) . '</a></div>' );
	}

	/**
	 * Get HTML for simple product waitlist button
	 *
	 * @param string  $context the context in which the button should be generated (join|leave)
	 * @param string  $classes the classes to apply to the control element
	 * @param string  $text    the text to display on the button (update|join|leave waitlist)
	 * @param object  $product WC_Product for which to get button HTML
	 * @access public
	 * @return string HTML for join waitlist button
	 * @since  1.3
	 */
	public function get_waitlist_control_for_simple_product( $context, $classes, $text, $product ) {
		$url = $this->toggle_waitlist_url();
		return apply_filters( 'wcwl_' . $context . '_waitlist_button_html', '<div class="wcwl_control"><a href="' . esc_url( $url ) . '" class="' . esc_attr( $classes ) . '" data-id="' . $this->Product->id . '" id="wcwl-product-'. esc_attr( $this->Product->id ).'">' . esc_html( $text ) . '</a></div>' );
	}

	/**
	 * Get HTML for waitlist email
	 *
	 * @access public
	 * @return  string
	 * @since  1.3
	 */
	public function get_waitlist_email_field() {

		return '<div class="wcwl_email_field" style="float:none;margin-bottom: 6px;"><label for="wcwl_email">' . $this->email_field_placeholder_text . '</label><input type="email" name="wcwl_email" id="wcwl_email" /></div>';
	}

	/**
	 * Get URL to toggle waitlist status
	 *
	 * @param object  $this_product WC_Product for which to get URL
	 * @access private
	 * @return string Toggle Waitlist URL for $this_product
	 * @since 1.0
	 */
	private function toggle_waitlist_url( $product_id = false ) {
		$product_id = $product_id ? $product_id : $this->Product->id;
		$url = esc_url( add_query_arg( WCWL_SLUG , $product_id, get_permalink( $this->Product->id ) ) );
		$url = esc_url( add_query_arg( WCWL_SLUG . '_nonce' , wp_create_nonce( __FILE__ ), $url ) );
		return apply_filters( 'wcwl_toggle_waitlist_url',  $url );
	}

	/**
	 * Gets the appropriate error message when no user is logged in
	 *
	 * @param integer $product_id the ID of the product being added
	 * @access public
	 * @return string The error message, dependent whether or not account registration is allowed
	 * @since 1.0
	 */
	public function get_toggle_waitlist_no_user_message() {
		$login_url = get_permalink( woocommerce_get_page_id( 'myaccount' ) );

		if ( !$login_url )
			$login_url = wp_login_url( $this->toggle_waitlist_url() );

		$register_url = esc_url( add_query_arg( 'action', 'register' , $login_url ) );

		if ( get_option( 'users_can_register' ) )
			return apply_filters( 'wcwl_users_must_register_and_login_message_text', sprintf( $this->users_must_register_and_login_message_text, $register_url, $login_url  ) , $this->Product )  ;

		return apply_filters( 'wcwl_users_must_login_message_text', sprintf( $this->users_must_login_message_text,  $login_url  ) , $this->Product )  ;
	}

	/**
	 * Handles request to join the waitlist if user is not logged in
	 *
	 * Checks which waitlists need to be updated and processes the request, returning appropriate notifications upon completion
	 *
	 * @param  mixed val $product if multiple products need updating this will be an array, else the product object requiring updating
	 * @access public
	 * @return admin notices for success/failure
	 * @since  1.3
	 */
	public function handle_waitlist_when_new_user( $product ) {
		if ( is_array( $product ) ) {
			$changed_products = $product;
			$product = $this->Product;
		}

		if ( !isset( $_REQUEST['wcwl_email'] ) || !is_email( $_REQUEST['wcwl_email'] ) )
			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_join_waitlist_invalid_email_message_text', $this->join_waitlist_invalid_email_message_text ), 'error' );
		elseif ( $product->is_type( 'grouped' ) && empty( $_REQUEST['wcwl_changed'] ) )
			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_toggle_waitlist_no_product_message_text', $this->toggle_waitlist_no_product_message_text ), 'error' );

		else {
			if ( email_exists( $_REQUEST['wcwl_email'] ) )
				$current_user = get_user_by( 'email', $_REQUEST['wcwl_email'] );
			else
				$current_user = get_user_by( 'id', $product->Waitlist->create_new_customer_from_email( $_REQUEST['wcwl_email'] ) );

			if ( $product->is_type( 'grouped' ) ) {
				foreach ( $changed_products as $changed_product ) {
					$changed_product->Waitlist->register_user( $current_user );
				}
				if ( count( $changed_products ) > 1 )
					return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_grouped_multiple_products_joined_message_text', $this->grouped_multiple_products_joined_message_text ) );

				return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_grouped_single_product_joined_message_text', $this->grouped_single_product_joined_message_text ) );
			}

			if ( !$product->Waitlist->register_user( $current_user ) )
				return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_leave_waitlist_message_text', $this->leave_waitlist_message_text ) );

			return WooCommerce_Waitlist_Plugin::add_notice( apply_filters( 'wcwl_join_waitlist_success_message_text', $this->join_waitlist_success_message_text ) );
		}
	}

	/**
	 * Sets up the text strings used by the plugin in the front end
	 *
	 * @hooked action plugins_loaded
	 * @access private
	 * @return void
	 * @since 1.0
	 */
	private function setup_text_strings() {
		$this->join_waitlist_button_text = __( 'Join waitlist', 'woocommerce-waitlist' );
		$this->dummy_waitlist_button_text = __( 'Join waitlist', 'woocommerce-waitlist' );
		$this->leave_waitlist_button_text =  __( 'Leave waitlist', 'woocommerce-waitlist' );
		$this->update_waitlist_button_text =  __( 'Update waitlist', 'woocommerce-waitlist' );
		$this->join_waitlist_message_text =  __( "Join the waitlist to be emailed when this product becomes available", 'woocommerce-waitlist' );
		$this->leave_waitlist_message_text = __( 'You are on the waitlist for this product', 'woocommerce-waitlist' ) ;
		$this->leave_waitlist_success_message_text =  __( 'You have been removed from the waitlist for this product', 'woocommerce-waitlist' ) ;
		$this->join_waitlist_success_message_text =  __( 'You have been added to the waitlist for this product', 'woocommerce-waitlist' ) ;
		$this->update_waitlist_success_message_text =  __( 'You have updated your waitlist for these products', 'woocommerce-waitlist' ) ;
		$this->join_waitlist_invalid_email_message_text =  __( 'You must provide a valid email address to join the waitlist for this product', 'woocommerce-waitlist' ) ;
		$this->toggle_waitlist_no_product_message_text = __( 'You must select at least one product for which to update the waitlist', 'woocommerce-waitlist' );
		$this->toggle_waitlist_ambiguous_error_message_text = __( 'Something seems to have gone awry. Are you trying to mess with the fabric of the universe?', 'woocommerce-waitlist' ) ;
		$this->users_must_register_and_login_message_text = __( 'You must <a href="%1$s">create an account</a> and be <a href="%2$s">logged in</a> to join the waitlist for this product', 'woocommerce-waitlist' );
		$this->users_must_login_message_text = __( 'You must be <a href="%s">logged in</a> to join the waitlist for this product', 'woocommerce-waitlist' );
		$this->grouped_product_message_text = __( "Check the box alongside any Out of Stock products and update the waitlist to be emailed when those products become available", 'woocommerce-waitlist' );
		$this->no_user_grouped_product_message_text = __( "Check the box alongside any Out of Stock products, enter your email address and join the waitlist to be notified when those products become available", 'woocommerce-waitlist' );
		$this->grouped_multiple_products_joined_message_text = __( 'You have been added to the waitlist for the selected products', 'woocommerce-waitlist' );
		$this->grouped_single_product_joined_message_text = __( 'You have been added to the waitlist for the selected product', 'woocommerce-waitlist' );
		$this->email_field_placeholder_text = __( "Email address" , 'woocommerce-waitlist' );
	}

	/**
	 * unhooks the woocommerce 'add to cart' action if not required
	 *
	 * This function only unhooks the action in the condition the add-to-cart $_REQUEST is set and we also have our own
	 * $_REQUEST variable. This is necessary because on grouped products our submit button has to share the same form
	 * element as the add-to-cart button. If they have clicked our button, we want to ignore the fact that the
	 * 'add-to-cart' is present.
	 *
	 * @hooked action init
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function remove_woocommerce_add_to_cart_action_if_not_required() {
		if ( empty( $_REQUEST['add-to-cart'] ) || empty( $_REQUEST[ WCWL_SLUG ] ) )
			return;
		remove_action( 'init', 'woocommerce_add_to_cart_action' );
	}

	/**
	 * Removes waitlist parameters from query string
	 *
	 * @access public
	 * @param  string $query_string current query
	 * @return string               updated query
	 */
	public function remove_waitlist_parameters_from_query_string( $query_string ) {
		return esc_url( remove_query_arg( array( 'woocommerce_waitlist', 'woocommerce_waitlist_nonce' ),  $query_string ) );
	}

	/**
	 * Output style block for class "group_table" on Grouped Product
	 *
	 * @hooked action wp_print_styles
	 * @access public
	 * @return void
	 * @since 1.0
	 */
	public function print_grouped_product_style_block() {
		global $post;
		$product = get_product( $post->ID );

		if ( !$product->is_type( 'grouped' ) )
			return;

		$css = apply_filters( WCWL_SLUG . '_grouped_product_style_block_css', 'p.' . WCWL_SLUG . '{padding-top:20px;clear:both;margin-bottom:10px;}' );
		echo apply_filters( WCWL_SLUG . '_grouped_product_style_block', '<style type="text/css">' . $css . '</style>' );
	}
}
