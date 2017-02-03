<?php
/**
 * Pie_WCWL_Custom_Tab
 *
 * @package WooCommerce Waitlist
 */
class Pie_WCWL_Custom_Tab {

	private $product;
    private $waitlist;
    private $pre_update_stock_status;

    /**
     * Assigns the settings that have been passed in to the appropriate parameters
     *
     * @access protected
     * @param  object    $product current product
     */
	function __construct( $product ) {
		// Init
		$this->product = $product;
        $this->set_pre_update_stock_status();
		$this->setup_text_strings();

		// Setup panel
		add_action( 'woocommerce_product_write_panel_tabs', array( &$this, 'custom_tab_options_tab' ) );
		add_action( 'woocommerce_product_write_panels', array( &$this, 'custom_tab_options' ) );

		// Update waitlists
		add_action( 'save_post', array ( &$this, 'update_waitlist_for_simple_product' ) );
        if( version_compare( wcwl_get_woocommerce_version_number(), '2.4.0', '<' ) ) {
            add_action( 'woocommerce_process_product_meta_variable', array ( &$this, 'save_variable_product_data' ), 10, 1 );
        } else {
		    add_action( 'woocommerce_process_product_meta_variable', array ( &$this, 'update_waitlists_for_variations' ), 10, 1 );
        }

		// Scripts and styles
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );
	}

	/**
	 * Check if product is out of stock
	 * Checks to see if at least one variation is out of stock if variable product
	 *
	 * @param  object $product current product
	 *
	 * @return bool
	 */
	public function product_is_out_of_stock( $product ) {

		if ( $product->is_type( 'variable' ) ) {
			return $this->variation_is_out_of_stock( $product );
		}
		if ( $product->is_type( 'simple' ) ) {
			return ! $product->is_in_stock();
		} else {
			return false;
		}
	}

	/**
	 * Check that at least one variation is out of stock before displaying waitlist
	 *
	 * @param  object $product current product
	 * @return bool
	 */
	public function variation_is_out_of_stock( $product ) {

		$variations = $product->get_available_variations();

		foreach ( $variations as $variation ) {
			if ( ! $variation['is_in_stock'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Enqueue any styles and scripts used for the custom tab
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'wcwl_admin_custom_tab_css', plugins_url() . '/woocommerce-waitlist/includes/css/wcwl_admin_custom_tab.css' );
		wp_enqueue_script( 'wcwl_admin_custom_tab_js', plugins_url() . '/woocommerce-waitlist/includes/js/wcwl_admin_custom_tab.js', array(), '1.0.0', true );
	}

    /**
     * Add custom waitlist tab to the product
     */
    public function custom_tab_options_tab() {
		?>
		    <li class="wcwl_waitlist_tab"><a href="#wcwl_waitlist_data"><?php _e( 'Waitlists', 'woocommerce_waitlist'); ?></a></li>
		<?php
	}

	/**
	 * Output the HTML required for the custom tab
	 */
	public function custom_tab_options() {

		?><div id="wcwl_waitlist_data" class="panel woocommerce_options_panel"><?php
		if ( $this->product->is_type( 'variable' ) ) {
				$this->build_custom_tab_for_variable();
		} else {
			$this->build_custom_tab();
		}
		echo $this->return_link_for_archive();
		?>
		</div>
		<?php
	}

	/**
	 * Return link for archive page for this product
	 *
	 * @return string html required for archive link
	 */
	public function return_link_for_archive() {
		return '<div class="wcwl_archive_wrapper"><a class="wcwl_view_archive_link" href="' . admin_url( '?page=wcwl-waitlist-archive&product_id=' . $this->product->id ) . '" >' . esc_html( apply_filters( 'wcwl_waitlist_view_waitlist_archive_text' , $this->view_waitlist_archive_text ) ) . '</a></div>';
	}

	/**
	 * Output required HTML for the custom tab for simple products
	 *
	 * @access public
	 * @return void
	 */
	public function build_custom_tab() {

        $this->waitlist = new Pie_WCWL_Waitlist( $this->product );
		$users = $this->waitlist->get_registered_users();
		if (  ! empty( $users ) || ! $this->product->is_in_stock() ) {
			echo '<div class="wcwl_product_tab_wrap">';
			if ( empty( $users ) ) {
				echo '<p class="wcwl_no_users_text">' . esc_html( apply_filters( 'wcwl_empty_waitlist_introduction' , $this->empty_waitlist_introduction ) ) . '</p>';
				echo $this->return_option_to_add_user( $this->waitlist );
			} else {
				echo '<p class="wcwl_intro_tab">' . esc_html( apply_filters( 'wcwl_waitlist_introduction', $this->waitlist_introduction ) ) . '</p>';
				echo '<div id="wcwl_waitlist_tab"><table class="widefat wcwl_product_tab">';
				foreach ( $users as $user ) {
					echo $this->return_user_info( $user, $this->waitlist );
				}
				echo '</table></div>';
				echo $this->return_option_to_add_user( $this->waitlist );
				echo '<p><div class="dashicons dashicons-email-alt wcwl_email_all_tab"></div><a href="' . esc_url_raw( $this->get_mailto_link_content( $this->waitlist ) ) . '" >' . esc_html( $this->email_all_users_on_list_text ) . '</a></p>';
			}
			echo '</div>';
        } else {
			echo '<p id="wcwl_in_stock_notice" class="wcwl_in_stock_notice">' . esc_html( apply_filters( 'wcwl_waitlist_variation_instock_introduction' , $this->product_instock_intro  ) ) . '</p>';
		}
	}

    /**
     * Output required HTML for the custom tab for variable products
     */
    public function build_custom_tab_for_variable() {

        $children = $this->product->get_available_variations();

        foreach ( $children as $key => $child ) {

            $variation          = get_product( $child['variation_id'] );
            $variation_waitlist = new Pie_WCWL_Waitlist( $variation );
            $users              = $variation_waitlist->get_registered_users();
            if (  ! empty( $users ) || ! $variation->is_in_stock() ) {
	            echo '<div id="wcwl_variation_' . $child['variation_id']  . '" class="wcwl_product_tab_wrap">';
	            echo '<div class="wcwl_header_wrap"><h3>' . $this->return_variation_tab_title( $variation_waitlist ) . '</h3></div>';
	            echo '<div class="wcwl_body_wrap">';
	            if ( empty( $users ) ) {
		            echo '<p class="wcwl_no_users_text">' . esc_html( apply_filters( 'wcwl_empty_waitlist_introduction', $this->empty_waitlist_introduction ) ) . '</p>';
		            echo $this->return_option_to_add_user( $variation_waitlist );
	            } else {
		            echo '<p class="wcwl_intro_tab">' . esc_html( apply_filters( 'wcwl_waitlist_introduction', $this->waitlist_introduction ) ) . '</p>';
		            echo '<div class="wcwl_waitlist_tab"><table class="widefat wcwl_product_tab">';
		            foreach ( $users as $user ) {
			            echo $this->return_user_info( $user, $variation_waitlist );
		            }
		            echo '</table></div>';
		            echo $this->return_option_to_add_user( $variation_waitlist );
		            echo '<p><div class="dashicons dashicons-email-alt wcwl_email_all_tab"></div><a href="' . esc_url_raw( $this->get_mailto_link_content( $variation_waitlist ) ) . '" >' . esc_html( $this->email_all_users_on_list_text ) . '</a></p>';
	            }
	            echo '</div></div>';
            }

            unset( $children[ $key ] );
        }
	    echo '<p id="wcwl_in_stock_notice" class="wcwl_in_stock_notice">' . esc_html( apply_filters( 'wcwl_waitlist_variation_instock_introduction' , $this->variable_instock_intro ) ) . '</p>';
    }

	/**
	 * Return title to be applied to the custom tab for variations
	 *
	 * @access public
	 *
	 * @param $waitlist
	 *
	 * @return string
	 */
	public function return_variation_tab_title( $waitlist ) {
		$title = $this->get_variation_name( $waitlist->parent_id, $waitlist->product_id );
        $variable_title = sprintf( $this->variation_tab_title, $title, $waitlist->get_number_of_registrations() );
		return $variable_title;
	}

	/**
	 * Get the name of the variation that matches the given ID - returning each attribute
	 * To be used as the title for each variation waitlist on the tab
	 *
	 * @param  int $parent_id id number of the parent product
	 * @param  int $variation_id id number of the variation
	 * @access public
	 * @return string the attribute of the required variation
	 */
	public function get_variation_name( $parent_id, $variation_id ) {

		$product = get_product( $parent_id );
		$variations = $product->get_available_variations();

		foreach ( $variations as $variation ) {

			$attributes = $variation['attributes'];
			$title = '';
			foreach ( $attributes as $attribute ) {
				$title .= ucwords( $attribute ) . ' ';
                if ( $variation['variation_id'] == $variation_id && !empty( $attribute ) )
                    return '#' .  $variation_id . ' ' . $title;
			}
		}
		return '#' . $variation_id;
	}

	/**
	 * Return table row for current user to add to waitlist tab
	 *
	 * @param $user
	 * @param $waitlist
	 *
	 * @return string html for table elements
	 */
	public function return_user_info( $user, $waitlist ) {

		return '<tr>
					<td><strong><a title="'. esc_attr( $this->view_user_profile_text ) .'" href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' . $user->display_name . '</a></strong></td>
					<td><a href="mailto:' . $user->user_email . '" title="' . esc_attr( $this->email_user_text ) . '" ><div class="dashicons dashicons-email-alt"></div></a></td>
					<td><label class="wcwl_remove_text_tab" title="' . esc_attr( $this->remove_user_from_waitlist_text ) . '">' . esc_html( $this->remove_text ) . ' </label><input class="wcwl_remove_check_tab" type="checkbox" name="' . WCWL_SLUG . '_unregister_' . $waitlist->product_id . '_tab[]" value="' . $user->ID . '" /></td>
				</tr>';
	}

	/**
	 * Return option to add user using waitlist tab
	 *
	 * @param $waitlist
	 *
	 * @return string html for table elements
	 */
	public function return_option_to_add_user( $waitlist ) {

		$id = $waitlist->product_id;

		return '<div class="wcwl_add_new_emails_tab wcwl_reveal_tab" >
					<p class="wcwl_add_user_link_tab"><a href="#" onclick="return false;">Add new user</a></p>
					<p class="wcwl_hidden_tab">' . $this->must_update_text . '</p>
					<p class="wcwl_hidden_tab wcwl_emails_tab" >
					  <input class="wcwl_email_text_tab" type="email" name="' . WCWL_SLUG . '_add_email_tab" />
					  <input type="button" class="button wcwl_email_button_tab" value="Add"></p>
					</p>
					<input type="text" name="' . WCWL_SLUG . '_email_list_' . $id . '_tab" class="wcwl_email_list_tab" style="display:none;" ></td>
                 </div>
                 <table class="wcwl_new_users_tab" ><tbody></tbody></table>';
	}

    /**
     * Sets $pre_update_stock_status to the stock status of a product
     *
     * As of WC 2.4.0 it is harder to obtain the stock status for variables so have saved this to post meta
     * making it easily retrievable later on
     *
     * @access public
     * @return void
     */
    public function set_pre_update_stock_status() {

        if ( $this->product->is_type( 'simple' ) ) {

            $this->pre_update_stock_status = array( $this->product->id => $this->product->is_in_stock() ? 'instock' : 'outofstock' );

        } elseif ( $this->product->is_type( 'variable' ) ) {

            $wcwl_woocommerce_version = wcwl_get_woocommerce_version_number();

            $stock_status = array();

            foreach ( $this->product->get_available_variations() as $variation ) {

	            if( version_compare( $wcwl_woocommerce_version, '2.4.0', '>=' ) ) {
		            $stock_status[$variation['variation_id']] = get_post_meta( $variation['variation_id'], 'wcwl_variation_is_in_stock', true );
	            } else {
		            $stock_status[$variation['variation_id']] = $variation['is_in_stock'] ? 'instock' : 'outofstock';
	            }
            }
	        $this->pre_update_stock_status = $stock_status;
        }
    }

    /**
     * Carries out checks to make sure user is allowed to save current product then modifies waitlist accordingly
     *
     * Check whether manage stock is checked - if so use stock quantity to determine whether product is in or out of stock
     *
     * @hooked action save_post
     * @param  int $post_id current post ID
     */
    public function update_waitlist_for_simple_product( $post_id ) {

        if (
            ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
            ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

	    if ( $this->product->is_type( 'simple' ) ) {
		    $this->update_waitlist( $post_id );
	    }
    }

	/**
	 * Updates the waitlist for each variation when post is saved
	 *
	 * $_POST array is different before WC 2.2.0 and so this needs to be checked in order to find stock status
	 *
	 * @hooked action woocommerce_process_product_meta_variable
	 * @param  int $post_id parent post ID
	 */
	public function save_variable_product_data( $post_id ) {
		if ( isset( $_POST['variable_post_id'] ) ) {
			$variations = $_POST['variable_post_id'];
			$this->update_variation_waitlists( $variations );
		}
	}

    /**
     * Updates the waitlist for each variation when post is saved
     *
     * $_POST array is different again after WC 2.4.0 so this function is only hooked for these versions
     *
     * @hooked action woocommerce_process_product_meta_variable
     * @param  int $post_id parent post ID
     */
    public function update_waitlists_for_variations( $post_id ) {
        if ( isset( $_POST['product-type'] ) && 'variable' == $_POST['product-type'] ) {
            $variations = $this->product->get_children();
	        $this->update_variation_waitlists( $variations );
        }
    }

	/**
	 * Run through each updated variation and update the waitlist as required
	 *
	 * @param $variations
	 */
	public function update_variation_waitlists( $variations ) {
		for ( $i = 0; $i < sizeof( $variations ); $i ++ ) {
			$variation_id = (int) $variations[ $i ];
			$this->update_waitlist( $variation_id );
		}
	}

	/**
	 * Update waitlist by adding/removing users as defined in the admin
	 *
	 * @param $product_id
	 */
	public function update_waitlist( $product_id ) {
		$product  = wc_get_product( $product_id );
		$waitlist = new Pie_WCWL_Waitlist( $product );
		$this->remove_users_from_waitlist( $waitlist );
		$this->add_users_to_waitlist( $waitlist );
	}

	/**
	 * Check to see whether variation has any stock
	 *
	 * Required for woocommerce versions before 2.2.0
	 *
	 * @param  int $stock_level quantity in stock
	 *
	 * @return string
	 */
	public function check_variation_stock_for_status( $stock_level ) {

		if ( $stock_level > 0 ) {
			return 'instock';
		} else {
			return 'outofstock';
		}
	}

	/**
	 * Compare the stock status before and after post is saved to determine if product/variation is back in stock
	 *
	 * @param string $stock_status new stock status
	 * @param int    $id           current product id
	 *
	 * @return bool
	 */
	public function product_is_back_in_stock( $stock_status, $id ) {

		if ( 'outofstock' == $this->pre_update_stock_status[$id] && 'instock' == $stock_status ) {
			return true;
		} else {
            return false;
        }
	}

	/**
	 * Compare the stock status before and after post is saved to determine if variation is back in stock
	 *
	 * @param string $pre_stock_status previous stock status
	 * @param array  $availability     current availability
	 *
	 * @return bool
	 */
    public function variation_is_back_in_stock( $pre_stock_status, $availability ) {

	    $current_stock_status = strpos( $availability['availability'], 'Out' ) === false ? true : false;

        if ( 'outofstock' == $pre_stock_status && true == $current_stock_status ) {
            return true;
        } else {
            return false;
        }
    }

	/**
	 * Removes selected users from the waitlist
	 *
	 * @access public
	 *
	 * @param $waitlist
	 */
	 public function remove_users_from_waitlist( $waitlist ) {

		 $value = isset( $_POST['woocommerce_waitlist_unregister_' . $waitlist->product_id . '_tab'] ) ? $_POST['woocommerce_waitlist_unregister_' . $waitlist->product_id . '_tab'] : '';

         if (
             '' == $value ||
             empty( $value ) ||
             ! is_array( $value )
         ) return;

         foreach ( $value as $user ) {
             $waitlist->unregister_user( get_user_by( 'id', $user ) );
         }
         $waitlist->save_waitlist();
	 }

	/**
	 * Adds the entered email to the waitlist for the current product/variation
	 *
	 * @access public
	 *
	 * @param $waitlist
	 */
	 public function add_users_to_waitlist( $waitlist ) {

		 $value = isset( $_POST['woocommerce_waitlist_email_list_' . $waitlist->product_id . '_tab'] ) ? $_POST['woocommerce_waitlist_email_list_' . $waitlist->product_id . '_tab']: '';

         if (
             '' == $value ||
             empty( $value )
         ) return;

         $emails = array_unique( explode( ',', $value ) );

         foreach ( $emails as $email ) {

             $email = trim( $email );

             if( !is_email( $email ) )
                 continue;

             $current_user = get_user_by( 'id', $waitlist->create_new_customer_from_email( $email ) );
             $waitlist->register_user( $current_user );
         }
	 }
	
	/**
	 * Sets up text strings used by the Waitlist Custom Tab
	 *
	 * @access public
	 * @return void
	 */
	public function setup_text_strings() {

		$this->variation_tab_title              = __( 'Waitlist for variation - %1$s: %2$d' , 'woocommerce-waitlist' );
		$this->waitlist_introduction            = __( 'The following users are currently on the waiting list for this product:', 'woocommerce-waitlist' );
		$this->empty_waitlist_introduction      = __( 'There are no users on the waiting list for this product.', 'woocommerce-waitlist' );
		$this->email_user_text                  = __( 'Email User', 'woocommerce-waitlist' );
		$this->view_user_profile_text           = __( 'View User Profile', 'woocommerce-waitlist' );
		$this->email_all_users_on_list_text     = __( 'Email all users on list', 'woocommerce-waitlist' );
		$this->remove_user_from_waitlist_text   = __( 'Remove user from waitlist', 'woocommerce-waitlist' );
		$this->remove_text                      = __( 'Remove:', 'woocommerce-waitlist' );
        $this->must_update_text                 = __( 'Product must be updated to save users onto the waitlist', 'woocommerce-waitlist' );
		$this->variable_instock_intro           = __( 'This product has no out of stock variations and there are no users registered on any variation waitlists.', 'woocommerce-waitlist' );
		$this->product_instock_intro            = __( 'This product is currently in stock and there are no users registered on the waitlist.', 'woocommerce-waitlist' );
		$this->view_waitlist_archive_text       = __( 'View archived waitlists', 'woocommerce-waitlist' );
		$this->persistent_waitlist_notification = __( 'Waitlists will remain visible regardless of stock status as persistent waitlists are currently enabled.', 'woocommerce-waitlist' );
	}

	/**
	 * Returns information needed for the 'email user' links in product tab
	 *
	 * @access private
	 *
	 * @param $waitlist
	 *
	 * @return string 'mailto' information required
	 */
	private function get_mailto_link_content( $waitlist ) {
		return 'mailto:' . get_option( 'woocommerce_email_from_address' ) . '?bcc=' . implode( ',', $waitlist->get_registered_users_email_addresses() ) ;
	}

}