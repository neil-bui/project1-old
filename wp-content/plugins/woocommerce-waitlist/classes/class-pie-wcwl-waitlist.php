<?php
/**
 * Pie_WCWL_Waitlist
 *
 * @package WooCommerce Waitlist
 */
class Pie_WCWL_Waitlist {

	/**
	 * Waitlist object representing the waitlist for current product
	 *
	 * @var object
	 * @access private
	 */
	private $waitlist;

	/**
	 * An array of all users on current products waitlist
	 *
	 * @var array
	 * @access private
	 */
	private $users;

	/**
	 * Product unique ID
	 *
	 * @var int
	 * @access public
	 */
	public $product_id;

	/**
	 * Products parent unique ID - used for variable products
	 *
	 * @var int
	 * @access public
	 */
	public $parent_id;

	/**
	 * Constructor function to hook up actions and filters and class properties
	 *
	 * @param object $Product WC_Product or derivative thereof
	 *
	 * @access public
	 */
	public function __construct( $Product ) {
		// Init
		$this->setup_product( $Product );
		$this->setup_waitlist();
		$this->setup_text_strings();

		// Save waitlist
		add_action( 'shutdown', array( &$this, 'save_waitlist' ) );
	}

	/**
	 * Setup product class variables
	 *
	 * @param  object $Product current product
	 * @access public
	 * @return void
	 */
	public function setup_product( $Product ) {
		if ( get_class( $Product ) == 'WC_Product_Variation' ) {
			$this->product_id = $Product->variation_id;
			$this->parent_id = $Product->id;
		} else {
			$this->product_id = $Product->id;
			$this->parent_id = false;
			$this->pre_update_stock_status = get_post_meta( $this->product_id, '_stock_status', true );
		}
	}

	/**
	 * Setup waitlist and users array
	 *
	 * @access public
	 * @return void
	 */
	public function setup_waitlist() {
		$waitlist = get_post_meta( $this->product_id, WCWL_SLUG, true );
		if ( !is_array( $waitlist ) )
			$waitlist = array();

		$this->users = array();
		$this->waitlist = array();
		foreach ( $waitlist as $user_id ) {
			if ( get_user_by( 'id', $user_id ) != false ) {
				$this->users[] = get_user_by( 'id', $user_id );
				$this->waitlist[] = $user_id;
			}
		}
	}

	/**
	 * Append the parent product to the waitlist array
	 *
	 * @param  array $product_ids current waitlist products list
	 * @access public
	 * @return array $product_ids updated waitlist products list
	 */
	public function append_parent_id_to_array( $product_ids ) {
		$product_ids[] = $this->parent_id;
		return $product_ids;
	}

	/**
	 * Save the current waitlist into the database
	 *
	 * @return void
	 */
	public function save_waitlist( ) {
		update_post_meta( $this->product_id, WCWL_SLUG, $this->waitlist );
		if ( $this->parent_id )
			add_filter( 'wcwl_updated_variable_products', array( &$this, 'append_parent_id_to_array' ) );
	}

	/**
	 * For some bizarre reason around 1.2.0, this funciton has started emitting notices. It is caused by the original
	 * assignment of WCWL_Frontend_UI->User being set to false when a user is not logged in. All around the application,
	 * this is now being called on as an object.
	 *
	 * @param object $User WP_User Object
	 * @access public
	 * @return boolean     Whether or not the User is registered to this waitlist, if they are a valid user
	 */
	public function user_is_registered( $User ) {
		return $User && in_array( $User->ID, $this->waitlist );
	}

	/**
	 * Remove user from the current waitlist
	 *
	 * @param  object $User user to be removed
	 * @access public
	 * @return boolean true|false depending on success of removal
	 */
	public function unregister_user( $User ) {
		if ( $this->user_is_registered( $User ) ) {
			do_action( 'wcwl_before_remove_user_from_waitlist' , $this->product_id , $User );
			$this->waitlist = array_diff( $this->waitlist, array ( $User->ID ) );
			do_action( 'wcwl_after_remove_user_from_waitlist' , $this->product_id, $User );
			$this->save_waitlist();
			return true;
		}
		return false;
	}

	/**
	 * For some bizarre reason around 1.2.0, this function has started emitting notices. It is caused by the original
	 * assignment of WCWL_Frontend_UI->User being set to false when a user is not logged in. All around the application,
	 * this is now being called on as an object.
	 *
	 * @param type    $User
	 * @access public
	 * @return boolean
	 */
	public function register_user( $User ) {
		if ( false === $User )
			return false;
		if ( $this->user_is_registered( $User ) )
			return false;

		do_action( 'wcwl_before_add_user_to_waitlist' , $this->product_id, $User );
		$this->waitlist[] = $User->ID;
		do_action( 'wcwl_after_add_user_to_waitlist' , $this->product_id, $User );
		return true;
	}

	/**
	 * Return the number of users on the current waitlist
	 *
	 * @access public
	 * @return int
	 */
	public function get_number_of_registrations() {
		return count( $this->waitlist );
	}

	/**
	 * Return number of users on requested waitlist
	 *
	 * @param  int $product_id requested product id
	 * @access public
	 * @static
	 * @return int
	 */
	public static function get_number_of_registrations_by_product_id($product_id){
		$waitlist = get_post_meta( $product_id, WCWL_SLUG, true );
		return count( $waitlist );
	}

	/**
	 * Return an array of the users on the current waitlist
	 *
	 * @access public
	 * @return array user_ids
	 */
	public function get_registered_users() {
		return $this->users;
	}

	/**
	 * Return an array of users emails from current waitlist
	 *
	 * @access public
	 * @return array user_emails
	 * @since 1.0.2
	 */

	public function get_registered_users_email_addresses() {
		return wp_list_pluck( $this->get_registered_users(), 'user_email' );
	}

	/**
	 * Checks if user is registered, if not creates a new customer and sends welcome email
	 *
	 * This function overrides woocommerce options to ensure that the user is created when joining the waitlist, options are reset afterwards
	 *
	 * @param  string $email users email address
	 * @access public
	 * @return object $current_user the customer's user object
	 * @since  1.3
	 */
	public function create_new_customer_from_email( $email ) {
		if( email_exists( $email ) )
			$current_user = email_exists( $email );
		else {
			add_filter( 'pre_option_woocommerce_registration_generate_password', array( $this, 'return_option_setting_yes' ), 10 );
			add_filter( 'pre_option_woocommerce_registration_generate_username', array( $this, 'return_option_setting_yes' ), 10 );

			$current_user = $this->create_new_customer( $email );

			remove_filter( 'pre_option_woocommerce_registration_generate_password', array( $this, 'return_option_setting_yes' ), 10 );
			remove_filter( 'pre_option_woocommerce_registration_generate_username', array( $this, 'return_option_setting_yes' ), 10 );
		}
		return $current_user;
	}

	/**
	 * Create new customer using the given email and send user a welcome email with login details
	 *
	 * This function is required before woocommerce v2.1 as handling user creation is handled differently from then
	 *
	 * @deprecated 2.0.20
	 * @deprecated woocommerce provides new functionality for handling customer creation from v2.1
	 * @access public
	 * @param  string $email users email address
	 * @return int $user_id current user ID
	 * @since  1.3
	 */
	public function create_new_customer( $email ) {
		$username = sanitize_user( current( explode( '@', $email ) ) );

		// Ensure username is unique
		$append     = 1;
		$o_username = $username;
		while ( username_exists( $username ) ) {
			$username = $o_username . $append;
			$append ++;
		}

		$password = wp_generate_password();
		$userdata = array(
    			'user_login'  =>  $username,
    			'user_email'  =>  $email,
    			'user_pass'   =>  $password,
    			'role'		  =>  'customer'
		);

		$user_id = wp_insert_user( $userdata ) ;

		// send the user a confirmation email and their login details
		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'registration-error', '<strong>' . __( 'ERROR', 'woocommerce' ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce' ) );
		}

		do_action( 'woocommerce_created_customer', $user_id, $userdata, true );

		return $user_id;
	}

	/**
	 * Returns 'yes' for options to be overidden when creating new users
	 *
	 * @access public
	 * @return string 'yes'
	 * @since  1.3
	 */
	public function return_option_setting_yes() {
		return 'yes';
	}

	/**
	 * Triggers instock notification email to each user on the waitlist for a product, then deletes the waitlist
	 *
	 * @param int $post_id
	 * @access public
	 * @return void
	 */
	public function waitlist_mailout( $post_id ) {

		if ( !empty( $this->waitlist ) ) {

			if ( 'yes' == get_option( 'woocommerce_waitlist_archive_on' ) ) {
				$this->archive_waitlist_before_mailouts( $post_id );
			}

			global $woocommerce ;
			$woocommerce->mailer();

			foreach ( $this->waitlist as $user_id ) {
				$user = get_user_by( 'id', $user_id );
				if ( ! WooCommerce_Waitlist_Plugin::automatic_mailouts_are_disabled() ) {
					do_action( 'woocommerce_waitlist_mailout_send_email', $user_id, $post_id );
				}

				if ( WooCommerce_Waitlist_Plugin::persistent_waitlists_are_disabled() ) {
					$this->unregister_user( $user );
				}
			}
		}
	}

	/**
	 * Save a record of the waitlist before the users are unregistered
	 *
	 * @param int $post_id current post id
	 */
	public function archive_waitlist_before_mailouts( $post_id ) {

		$existing_archive = get_post_meta( $post_id, 'wcwl_waitlist_archive', true );
		$existing_archive = is_array( $existing_archive ) ? $existing_archive : array();
		$current_archive  = array( time() => $this->waitlist );
		$archives         = $existing_archive + $current_archive;

		update_post_meta( $post_id, 'wcwl_waitlist_archive', $archives );
	}

	/**
	 * Create message to be sent out to user
	 *
	 * @param  int $user_id ID of the user we are sending to
	 * @param  int $post_id ID of the product that is now back in stock
	 * @access public
	 * @return string the completed email message
	 * @since  1.3
	 */
	public function create_message_for_mailout( $user_id, $post_id ) {
		if ( get_post_type( $post_id ) == 'product_variation' ) {
			$post = get_post( $post_id );
			$permalink_id = $post->post_parent;
		}

		$user = get_user_by( 'id', $user_id );
		$username = $user->display_name;
		$product_title = get_the_title( $post_id ) ;
		$product_link =  get_permalink( isset( $permalink_id ) ? $permalink_id : $post_id ) ;
		$message = '<p>' . apply_filters( 'wcwl_email_salutation' , sprintf( $this->email_salutation, $username ) ) . '</p><p>';
		$message .= apply_filters( 'wcwl_email_product_back_in_stock_text', sprintf( $this->specific_product_back_in_stock_text, $product_title, get_bloginfo( 'title' ) ) ) . '. ';
		$message .= apply_filters( 'wcwl_email_mailout_disclaimer_text', $this->mailout_disclaimer_text ) . '. ';
		$message .= apply_filters( 'wcwl_email_visit_this_link_to_purchase_text', sprintf( $this->visit_this_link_to_purchase_text, $product_title, $product_link, $product_link ) );
		$message .= '</p><p>' . apply_filters( 'wcwl_email_mailout_signoff',  $this->mailout_signoff ) . get_bloginfo( 'title' )  . '</p>';
		return apply_filters( 'wcwl_mailout_html', $message ) ;
	}

	/**
	 * Sets up the text strings used by the plugin
	 *
	 * @hooked action plugins_loaded
	 * @access private
	 * @return void
	 */
	private function setup_text_strings() {
		$this->mailout_signoff = _x( 'Regards,<br>', 'Email signoff', 'woocommerce-waitlist' ) ;
		$this->mailout_disclaimer_text = __( 'You have been sent this email because your email address was registered on a waitlist for this product', 'woocommerce-waitlist' );
		$this->visit_this_link_to_purchase_text = __( 'If you would like to purchase %1$s please visit the following link: <a href="%2$s">%3$s</a>', 'woocommerce-waitlist' );
		$this->specific_product_back_in_stock_text = __( '%1$s is now back in stock at %2$s', 'woocommerce-waitlist' ) ;
		$this->email_salutation = _x( 'Hi %s,', 'Email Salutation', 'woocommerce-waitlist' );
		$this->generic_product_back_in_stock_text = __( 'A product you are waiting for is back in stock', 'woocommerce-waitlist' );
		$this->join_waitlist_button_text = __( 'Join waitlist', 'woocommerce-waitlist' );
	}
}
