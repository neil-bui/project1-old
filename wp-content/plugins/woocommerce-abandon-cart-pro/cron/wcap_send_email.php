<?php 
/**
* woocommerce_abandon_cart_cron class
**/
class Wcap_Abandoned_Cart_Cron_Job_Class {		   
    
    /**
     * Function to send emails
     */
    public static function wcap_abandoned_cart_send_email_notification() {
        
        global $wpdb;
        global $woocommerce;

        // Delete any guest ac carts that might be pending because user did not go to Order Received page after payment
        //search for the guest carts
        $query_guest_records = "SELECT id,email_id FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history`";				
        $results_guest_list  = $wpdb->get_results( $query_guest_records );
        
        // This is to ensure that recovered guest carts r removed from the delete list
        $query_records = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_type = 'GUEST' AND recovered_cart != '0'";
        $results_query = $wpdb->get_results( $query_records );
        foreach ( $results_guest_list as $key => $value ) {
        	$record_found = "NO";
        	foreach ( $results_query as $k => $v ) {
        		if ( $value->id == $v->user_id ) {
        			$record_found = "YES";
        		}
        	}
        	if ( $record_found == "YES" ) {
        		unset( $results_guest_list[ $key ] );
        	}
        }
        foreach ( $results_guest_list as $key => $value ) {
            $query_email_id      = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_billing_email' AND meta_value = %s";
            $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $value->email_id ) );

            //if any orders are found with the same email addr..delete those ac records
            if ( $results_query_email ) {
                    
                for ( $i = 0; $i < count( $results_query_email ); $i++ ) {
                    $query_post   = "SELECT post_date,post_status FROM `" . $wpdb->prefix . "posts` WHERE ID = %d";
                    $results_post = $wpdb->get_results ( $wpdb->prepare( $query_post, $results_query_email[ $i ]->post_id ) );

                    if ( $results_post[0]->post_status == "wc-pending" || $results_post[0]->post_status == "wc-failed" ) {
                    	continue;
                    }	
                    $order_date_time = $results_post[0]->post_date;
                    $order_date	     = substr( $order_date_time , 0 , 10 );
                    $current_time    = current_time( 'timestamp' );
                    $today_date	     = date( 'Y-m-d', $current_time );

                    if ( $order_date == $today_date ) {
                        
                        $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1' WHERE user_id = '" . $value->id . "'";
                        $wpdb->query( $query_ignored );
                        break;
                    }
                }
            }
        }

        // Delete any logged in user carts that might be pending because user did not go to Order Received page after payment
        $query_records = "SELECT DISTINCT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_type = 'REGISTERED' AND cart_ignored = '0' AND recovered_cart = '0'";
        $results_list  = $wpdb->get_results( $query_records );

        foreach ( $results_list as $key => $value ) {            
            $user_id            = $value->user_id;
            $key                = 'billing_email';
            $single             = true;
            $user_billing_email = get_user_meta( $user_id, $key, $single );
            if( isset( $user_billing_email ) && $user_billing_email == '' ){
                $user_id        = $value->user_id;
                if( is_multisite() ) {
                    // get main site's table prefix
                    $main_prefix = $wpdb->get_blog_prefix(1);
                    $query_email = "SELECT user_email FROM `".$main_prefix."users` WHERE ID = %d";
                     
                } else {
                    // non-multisite - regular table name
                    $query_email = "SELECT user_email FROM `".$wpdb->prefix."users` WHERE ID = %d";
                }
                $results_email       = $wpdb->get_results( $wpdb->prepare( $query_email, $user_id ) );
                if( isset( $results_email[0]->user_email ) && $results_email[0]->user_email != '' ) {
                    $user_billing_email  = $results_email[0]->user_email;
                }
            } 
            $query_email_id      = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_billing_email' AND meta_value = %s";
            $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $user_billing_email ) );
    
            //if any orders are found with the same email addr..delete those ac records
            if ( is_array( $results_query_email ) && count( $results_query_email ) > 0 ) {                    
                for ( $i = 0; $i < count( $results_query_email ); $i++ ) {
                    $query_post   = "SELECT post_date,post_status FROM `" . $wpdb->prefix . "posts` WHERE ID = %d";	
                    $results_post = $wpdb->get_results ( $wpdb->prepare( $query_post, $results_query_email[ $i ]->post_id ) );
    
                    if ( $results_post[0]->post_status == "wc-pending" || $results_post[0]->post_status == "wc-failed" ) {
                    	continue; 
                    }
                    $order_date_time = $results_post[0]->post_date;
                    $order_date	     = substr( $order_date_time, 0, 10 );
                    $current_time    = current_time( 'timestamp' );
                    $today_date    	 = date( 'Y-m-d', $current_time );
    
                    if ( $order_date == $today_date ) {

                        $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1' WHERE user_id = '" . $user_id . "'
                                        AND cart_ignored = '0'
                                        AND recovered_cart = '0'";
                        $wpdb->query( $query_ignored );
                        break;
                    }
                }
            }
        }	
        $enable_email = get_option( 'ac_enable_cart_emails' );

        if ( $enable_email == 'on' ) {
            global $woocommerce;
            //Grab the cart abandoned cut-off time from database.
            $cut_off_time              = get_option( 'ac_cart_abandoned_time' ) ;
            $cart_abandon_cut_off_time = $cut_off_time * 60;
            //Fetch all active templates present in the system
            $query                     = "SELECT wpet . * FROM `" . $wpdb->prefix . "ac_email_templates` AS wpet WHERE wpet.is_active = '1' ORDER BY `day_or_hour` DESC, `frequency` ASC ";
            $results_template          = $wpdb->get_results ( $query );
            $minute_seconds	           = 60;
            $hour_seconds	           = 3600; // 60 * 60
            $day_seconds	           = 86400; // 24 * 60 * 60
            $admin_abandoned_email     = '';

            foreach ( $results_template as $results_template_key => $results_template_value ) {                
                if ( $results_template_value->day_or_hour == 'Minutes') {
                        $time_to_send_template_after = $results_template_value->frequency * $minute_seconds;
                } elseif ( $results_template_value->day_or_hour == 'Days' ) {
                        $time_to_send_template_after = $results_template_value->frequency * $day_seconds;
                } elseif ( $results_template_value->day_or_hour == 'Hours' ) {
                        $time_to_send_template_after = $results_template_value->frequency * $hour_seconds;
                }
                $carts                  = Wcap_Abandoned_Cart_Cron_Job_Class ::get_carts ( $time_to_send_template_after, $cart_abandon_cut_off_time );
                /**
                 * When there are 3 templates and for cart id 1 all template time has been reached. BUt all templates are deactivated.
                 * If we activate all 3 template then at a 1 time all 3 email templates send to the users.
                 * So below function check that after first email is sent time and then from that time it will send the 2nd template time.  ( It will not consider the cart abadoned time in this case. ) 
                 */
                $carts                  = Wcap_Abandoned_Cart_Cron_Job_Class ::wcap_remove_cart_for_mutiple_templates ( $carts, $time_to_send_template_after, $results_template_value->id );
                $carts                  = Wcap_Abandoned_Cart_Cron_Job_Class ::wcap_update_abandoned_cart_status_for_placed_orders ( $carts, $time_to_send_template_after );
                
                $email_frequency        = $results_template_value->frequency;
                $email_body_template    = $results_template_value->body;
                $template_email_subject = $results_template_value->subject;
                $headers                = "From: " . $results_template_value->from_name . " <" . $results_template_value->from_email . ">" . "\r\n";
                $headers                .= "Content-Type: text/html"."\r\n";
                $headers                .= "Reply-To:  " . $results_template_value->reply_email . " " . "\r\n";
                $template_id            = $results_template_value->id;
                $template_name          = $results_template_value->template_name;
                $coupon_id              = $results_template_value->coupon_code;
                $coupon_to_apply        = get_post( $coupon_id, ARRAY_A );
                $coupon_code            = $coupon_to_apply['post_title'];
                $default_template       = $results_template_value->default_template;
                $discount_amount        = $results_template_value->discount;
                $generate_unique_code   = $results_template_value->generate_unique_coupon_code;
                $is_wc_template         = $results_template_value->is_wc_template;
                $wc_template_header_t   = $results_template_value->wc_email_header != '' ? $results_template_value->wc_email_header : __( 'Abandoned cart reminder', 'woocommerce-ac ');
                $coupon_code_to_apply   = $email_subject = '';                    
                    
                foreach ( $carts as $key => $value ) {                        
                    $selected_lanaguage = '';
                    if ( $value->user_type == "GUEST" && $value->user_id != '0' ) {
                            $value->user_login  = "";
                            $query_guest        = "SELECT billing_first_name, billing_last_name, email_id FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = %d";
                            $results_guest      = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
							if (count ($results_guest) > 0 ){
                            	$value->user_email  = $results_guest[0]->email_id;
							}		
                    } else {                   
    			        $user_id = $value->user_id;
    			        $key = 'billing_email';
    			        $single = true;
    			        $user_biiling_email = get_user_meta( $user_id, $key, $single );
    			        if( isset( $user_biiling_email ) && $user_biiling_email != '' ){
    			           $value->user_email = $user_biiling_email;
    			       }
    			    }
    			    $cart = array();
                    $cart_info_db_field = json_decode( $value->abandoned_cart_info );
    
                    if( !empty( $cart_info_db_field ) ) {
                        $cart           = $cart_info_db_field->cart;
                    }
                    
                    $validate_email_format = Wcap_Abandoned_Cart_Cron_Job_Class::wcap_validate_email_format ( $value->user_email );
                    
                    if( count( $cart ) > 0 && $value->user_id != '0' && $validate_email_format === 1 ) {   
                        
                        $cart_update_time   = $value->abandoned_cart_time;
                        $new_user           = Wcap_Abandoned_Cart_Cron_Job_Class::check_sent_history( $value->user_id, $cart_update_time, $template_id, $value->id );
                        $selected_lanaguage = $value->language;
                                    
                        if ( $new_user == true ) {                        
                            $selected_lanaguage     = $value->language;                        
                            $name_msg               = 'wcap_template_' . $template_id . '_message';
                            $email_body_template    = Wcap_Abandoned_Cart_Cron_Job_Class::wcap_get_translated_texts( $name_msg, $results_template_value->body, $selected_lanaguage );
                            
                            $name_sub               = 'wcap_template_' . $template_id . '_subject';
                            $template_email_subject = Wcap_Abandoned_Cart_Cron_Job_Class::wcap_get_translated_texts ( $name_sub, $results_template_value->subject, $selected_lanaguage );
                            
                            $wc_template_header_text = $wc_email_header = 'wcap_template_' . $template_id . '_wc_email_header';
                            $wc_template_header      = Wcap_Abandoned_Cart_Cron_Job_Class::wcap_get_translated_texts ( $wc_template_header_text, $wc_template_header_t, $selected_lanaguage );
                        
                            $cart_info_db = $value->abandoned_cart_info;
                            $email_body   = $email_body_template;  
                            $email_body   .= '{{email_open_tracker}}';
    
                            if ( $value->user_type == "GUEST" ) {
                                if ( isset( $results_guest[0]->billing_first_name ) ) {
                                	$email_body    = str_replace( "{{customer.firstname}}", $results_guest[0]->billing_first_name, $email_body );
                                	$email_subject = str_replace( "{{customer.firstname}}", $results_guest[0]->billing_first_name, $template_email_subject );
                                }
    
                                if ( isset( $results_guest[0]->billing_last_name ) ) {
                                    $email_body = str_replace( "{{customer.lastname}}", $results_guest[0]->billing_last_name, $email_body );
                                }
    
                                if ( isset( $results_guest[0]->billing_first_name ) && isset( $results_guest[0]->billing_last_name ) ) {
                                    $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_first_name . " " . $results_guest[0]->billing_last_name, $email_body );
                                }
                                else if ( isset( $results_guest[0]->billing_first_name ) ){
                                    $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_first_name, $email_body );
                                }
                                else if ( isset( $results_guest[0]->billing_last_name) ){
                                    $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_last_name, $email_body );
                                }
                            } else {
                                $email_body    = str_replace( "{{customer.firstname}}", get_user_meta( $value->user_id, 'first_name', true ), $email_body );
                                $email_subject = str_replace( "{{customer.firstname}}", get_user_meta( $value->user_id, 'first_name', true ), $template_email_subject );
                                $email_body    = str_replace( "{{customer.lastname}}", get_user_meta( $value->user_id, 'last_name', true ), $email_body );
                                $email_body    = str_replace( "{{customer.fullname}}", get_user_meta( $value->user_id, 'first_name', true )." ".get_user_meta( $value->user_id, 'last_name', true ), $email_body );
                            }
                            $email_body = str_replace( "{{customer.email}}", $value->user_email, $email_body );
                            $order_date = "";
    
                            if ( $cart_update_time != "" && $cart_update_time != 0 ) {
                                    $order_date = date( 'd M, Y h:i A', $cart_update_time );
                            }
                            //$coupon_code = $coupon_to_apply['post_title'];
                            if( preg_match( "{{coupon.code}}", $email_body, $matched ) ) {
                                $coupon_post_meta = '';
                                if( '1' == $default_template && $coupon_code == '' ) {
                                    if( '5' == $discount_amount ) {
                                        $amount               = $discount_amount; // Amount
                                        $discount_type        = 'percent';
                                        $expiry_date          = date( "Y-m-d", strtotime( date( 'Y-m-d' ) . " +7 days" ) );
                                        $coupon_code_to_apply = Wcap_Abandoned_Cart_Cron_Job_Class::wp_coupon_code ( $amount, $discount_type, $expiry_date, $coupon_post_meta );
                                    } elseif ( '10' == $discount_amount ) {
                                        $amount               = $discount_amount; // Amount
                                        $discount_type        = 'percent';
                                        $expiry_date          = date( "Y-m-d", strtotime( date( 'Y-m-d' ) . " +7 days" ) );
                                        $coupon_code_to_apply = Wcap_Abandoned_Cart_Cron_Job_Class::wp_coupon_code ( $amount, $discount_type, $expiry_date, $coupon_post_meta );
                                    }
                                } elseif( $coupon_code != '' && $generate_unique_code == '1' ) {
                                	$coupon_post_meta = get_post_meta( $coupon_id );
                                	$discount_type    = $coupon_post_meta['discount_type'][0];
                                	$amount           = $coupon_post_meta['coupon_amount'][0];
                                	if( isset( $coupon_post_meta['expiry_date'][0] ) && $coupon_post_meta['expiry_date'][0] != '' ) {
                                		$expiry_date = $coupon_post_meta['expiry_date'][0];
                                	} else {
                                		$expiry_date = date( "Y-m-d", strtotime( date( 'Y-m-d' ) . " +7 days" ) );
                                	}
                                	$coupon_code_to_apply = Wcap_Abandoned_Cart_Cron_Job_Class::wp_coupon_code( $amount, $discount_type, $expiry_date, $coupon_post_meta );
                                } else {
                                	$coupon_code_to_apply = $coupon_code;
                                }
                                $email_body = str_replace( "{{coupon.code}}", $coupon_code_to_apply, $email_body );	
                            }
                                            
                            $email_body = str_replace( "{{cart.abandoned_date}}", $order_date, $email_body );	
                            $email_body = str_replace( "{{shop.name}}", get_option( 'blogname' ), $email_body );
                            $email_body = str_replace( "{{shop.url}}", get_option( 'siteurl' ), $email_body );
    						if ( $woocommerce->version < '2.3' ) {
                                $checkout_page_link = $woocommerce->cart->get_checkout_url();
    						} else {
                                $checkout_page_id   = wc_get_page_id( 'checkout' );
                                $checkout_page_link = '';
                                if( $checkout_page_id ) {    
                                    // Get the checkout URL
                                    $checkout_page_link = get_permalink( $checkout_page_id );
                                    
                                    if( function_exists( 'icl_register_string' ) ) {
                                        if( 'en' == $selected_lanaguage  ) {
                                            $checkout_page_link = $checkout_page_link;
                                        } else {
                                            $checkout_page_link = apply_filters( 'wpml_permalink', $checkout_page_link, $selected_lanaguage );
                                        }
                                    }
                                    // Force SSL if needed
                                    if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
                                        $checkout_page_link = str_replace( 'http:', 'https:', $checkout_page_link );
                                    }
                                }
                            }
                            $query_sent = "INSERT INTO `" . $wpdb->prefix . "ac_sent_history` ( template_id, abandoned_order_id, sent_time, sent_email_id )
                                          VALUES ( '" . $template_id . "', '" . $value->id . "', '" . current_time('mysql') . "', '" . $value->user_email . "' )";		
                            $wpdb->query( $query_sent );		
                            $query_id          = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history` WHERE template_id= %d AND abandoned_order_id= %d ORDER BY id DESC LIMIT 1 ";	
                            $results_sent      = $wpdb->get_results ( $wpdb->prepare( $query_id, $template_id, $value->id ) );	
                            $email_sent_id     = $results_sent[0]->id;
                            $encoding_checkout = $email_sent_id . '&url=' . $checkout_page_link;
                            $validate_checkout = Wcap_Abandoned_Cart_Cron_Job_Class::encrypt_validate( $encoding_checkout );
                                            
                            if( isset( $coupon_code_to_apply ) && $coupon_code_to_apply != '' ) {
                                $encypted_coupon_code = Wcap_Abandoned_Cart_Cron_Job_Class::encrypt_validate( $coupon_code_to_apply );
                                $checkout_link_track = get_option( 'siteurl' ) . '/?wacp_action=track_links&validate=' . $validate_checkout . '&c='.$encypted_coupon_code;
                            } else {
                                $checkout_link_track = get_option( 'siteurl' ) . '/?wacp_action=track_links&validate=' . $validate_checkout;
                            }
                                            
                            // Populate the product name if its present in the email subject line
                            $sub_line_prod_name = '';
                            $cart_details       = $cart_info_db_field->cart;
                            foreach ( $cart_details as $k => $v ) {
                            	$sub_line_prod_name = get_the_title( $v->product_id );
                            	break;
                            }
                            $email_subject = str_replace( "{{product.name}}", $sub_line_prod_name, $email_subject );
                            // Populate the products.cart shortcode if it exists
                            if( preg_match( "{{products.cart}}", $email_body, $matched ) ) {
                            	$var = '
                                    <h3> Your Shopping Cart </h3>
                                    <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                    <tr>
                                    <th> Item </th>
                                    <th> Name </th>
                                    <th> Quantity </th>
                                    <th> Price </th>
                                    <th> Line Subtotal </th>
                                    </tr>';			
                                $cart_details = $cart_info_db_field->cart;
                                $cart_total   = $item_subtotal = $item_total = 0;
                                $bundle_child = array();
                                foreach( $cart_details as $k => $v ) {
                                	$quantity_total	= $v->quantity;
                                	$product_id	    = $v->product_id;
                                	$prod_name      = get_post( $product_id );
                                	$product_name   = $prod_name->post_title;
                                	if( isset( $v->variation_id ) && '' != $v->variation_id ){
                                	    $variation_id               = $v->variation_id;
                                	    $variation                  = wc_get_product( $variation_id );
                                	    $name                       = $variation->get_formatted_name() ;
                                	    $explode_all                = explode( "&ndash;", $name );
                                	    $pro_name_variation         = array_slice( $explode_all, 1, -1 );
                                	    $product_name_with_variable = '';
                                	    $explode_many_varaition     = array();
                                	
                                	    foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ){
                                	        $explode_many_varaition = explode ( ",", $pro_name_variation_value );
                                	        if ( !empty( $explode_many_varaition ) ) {
                                	            foreach( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ){
                                	                $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                                	            }
                                	        } else {
                                	            $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                                	        }
                                	    }
                                	
                                	    $product_name = $product_name_with_variable;
                                	}
                                	// Item subtotal is calculated as product total including taxes
                                	if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                                		$item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
                                	} else {
                                		$item_subtotal = $item_subtotal + $v->line_total;
                                	}
    
                                	//	Line total
                                	$item_total         = $item_subtotal;
                                	$item_subtotal	    = $item_subtotal / $quantity_total;
                                	$item_total_display = wc_price( $item_total );
                                	$item_subtotal	    = wc_price( $item_subtotal );
                                	$cart_total         += $item_total;
                                	$product            = get_product( $product_id );
                                	
                                	// If bundled product, get the list of sub products
                                	if ( isset( $product->bundle_data ) && is_array( $product->bundle_data ) && count( $product->bundle_data ) > 0) {
                                		foreach ( $product->bundle_data as $b_key => $b_value ) {
                                			$bundle_child[] = $b_key;
                                		}
                                	}
                                	
                                	// check if the product is a part of the bundles product, if yes, set qty and totals to blanks
                                	if ( isset( $bundle_child ) && count( $bundle_child ) > 0 ) {
                                		if ( in_array( $product_id, $bundle_child ) ) {
                                			$item_subtotal = $item_total_display = $quantity_total = '';
                                		}
                                	}
                                	$image_url = wp_get_attachment_url( get_post_thumbnail_id( $product_id ) );
                                	$var       .= '<tr>
                                	<td> <a href="' . $checkout_link_track . '"><img src="' . $image_url . '" alt="" height="42" width="42" /></a></td>
                                	<td> <a href="' . $checkout_link_track . '">' . $product_name . '</a></td>
                                	<td> ' . $quantity_total . '</td>';
                                	if ($item_subtotal == '' && $item_total_display == '') {
                                		$var .= '<td></td>
                                				<td></td>';
                                	} else {
                                    	$var .= '<td> ' . $item_subtotal . '</td>
                                    			<td> ' . $item_total_display . '</td>';
                                	}
                                	$var .= '</tr>';
                                	$item_subtotal = $item_total = 0;
                                }
                                $cart_total = wc_price( $cart_total );
                                $var .= '<tr>
                                <td> </td>
                                <td> </td>
                                <td> </td>
                                <td> Cart Total : </td>
                                <td> ' . $cart_total . '</td>
                                </tr>';
                                $var .= '</table>
                                ';
                                $email_body = str_replace( "{{products.cart}}", $var, $email_body );
                            }// populate the cart table, if it exists
                            else if ( preg_match( "{{item.image}}", $email_body, $matched ) || preg_match( "{{item.name}}", $email_body, $matched ) || preg_match( "{{item.price}}", $email_body, $matched ) || preg_match( "{{item.quantity}}", $email_body, $matched ) || preg_match( "{{item.subtotal}}", $email_body, $matched ) || preg_match( "{{cart.total}}", $email_body, $matched ) ) {
                            	$replace_html   = '';
                                $cart_details   = $cart_info_db_field->cart;
                            	$cart_total     = $item_subtotal = $item_total = 0;
                            	// This array will be used to house the columns in the hierarchy they appear
                            	$position_array = array();
                            	$start_position = $end_position = $image_start_position = $name_start_position = 0;
                            	//check which columns are present
                            	if ( preg_match( "{{item.image}}", $email_body, $matched ) ) {
                            		$image_start_position = strpos( $email_body, '{{item.image}}' );
                            		$position_array[ $image_start_position ] = 'image';
                            	}
                            	if ( preg_match( "{{item.name}}", $email_body, $matched ) ) {
                            		$name_start_position = strpos( $email_body,'{{item.name}}' );
                            		$position_array[ $name_start_position ] = 'name';
                            	}
                            	if ( preg_match( "{{item.price}}", $email_body, $matched ) ) {
                            		$price_start_position = strpos( $email_body, '{{item.price}}' );
                            		$position_array[ $price_start_position ] = 'price';
                            	}
                            	if ( preg_match( "{{item.quantity}}", $email_body, $matched ) ) {
                            		$quantity_start_position = strpos( $email_body, '{{item.quantity}}' );
                            		$position_array[ $quantity_start_position ] = 'quantity';
                            	}
                            	if ( preg_match( "{{item.subtotal}}", $email_body, $matched ) ) {
                            		$subtotal_start_position = strpos( $email_body,'{{item.subtotal}}' );
                            		$position_array[ $subtotal_start_position ] = 'subtotal';
                            	}	
                            	
                            	// Complete populating the array
                            	ksort( $position_array );
                                        	
                            	 $tr_array   = explode( "<tr", $email_body );
                                 $check_html = $style = ''; 
                                 foreach ( $tr_array as $tr_key => $tr_value ) {                                     
                                     if( (preg_match( "{{item.image}}", $tr_value, $matched ) || preg_match( "{{item.name}}", $tr_value, $matched) || preg_match( "{{item.price}}", $tr_value, $matched ) || preg_match( "{{item.quantity}}", $tr_value, $matched) || preg_match( "{{item.subtotal}}", $tr_value, $matched)) && ! preg_match( "{{cart.total}}", $tr_value, $matched ) ) {                                         
                                         $style_start  = strpos( $tr_value, 'style' );
                                         $style_end    = strpos( $tr_value, '>', $style_start );
                                         $style_end    = $style_end - $style_start;
                                         $style        = substr( $tr_value, $style_start, $style_end );
                                         $tr_value     = "<tr" . $tr_value;
                                         $end_position = strpos( $tr_value, '</tr>' );
                                         $end_position = $end_position + 5;
                                         $check_html   = substr( $tr_value, 0, $end_position );
                                     }
                                 }
                                        	 
                            	$i = 1;
                            	$bundle_child = array();
                            	foreach ( $cart_details as $k => $v ) {                        	   
                                	// Product image
                            		$product   = get_product( $v->product_id );
                            		$image_url =  wp_get_attachment_url( get_post_thumbnail_id( $v->product_id ) );                        		
                            		// Populate the name variable
                            		$prod_name = get_the_title( $v->product_id );                        		
                            		// Populate qty
                            		$quantity  = $v->quantity;
                            		// Show variation
                            		if( isset( $v->variation_id ) && '' != $v->variation_id ){
                            		    $variation_id               = $v->variation_id;
                            		    $variation                  = wc_get_product( $variation_id );
                            		    $name                       = $variation->get_formatted_name() ;
                            		    $explode_all                = explode( "&ndash;", $name );
                            		    $pro_name_variation         = array_slice( $explode_all, 1, -1 );
                            		    $product_name_with_variable = '';
                            		    $explode_many_varaition     = array();
                            		
                            		    foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ){
                            		        $explode_many_varaition = explode ( ",", $pro_name_variation_value );
                            		        if ( !empty( $explode_many_varaition ) ) {
                            		            foreach( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ){
                            		                $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                            		            }
                            		        } else {
                            		            $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                            		        }
                            		    }
                            		    $prod_name = $product_name_with_variable;
                            		}
                            		// Price and Item Subtotal
                            		// Item subtotal is calculated as product total including taxes
                            		if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                            			$item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
                            		} else {
                            			$item_subtotal = $item_subtotal + $v->line_total;
                            		}	
                            		//	Line total
                            		$item_total            = $item_subtotal;
                            		$item_price	           = $item_subtotal / $quantity;
                            		$item_subtotal_display = wc_price( $item_total );
                            		$item_price	           = wc_price( $item_price );
                            		$cart_total            += $item_total;
                            		$item_subtotal         = $item_total = 0;
                            		if( $i % 2 == 0 ) {
                            			$replace_html .= '<tr>';
                            		} else {
                            			$replace_html .= '<tr ' . $style . '>';
                            		}
                            		
                            		// If bundled product, get the list of sub products
                            		if( isset( $v->product_type ) && 'bundle' == $v->product_type && isset( $product->bundle_data ) && is_array( $product->bundle_data ) && count( $product->bundle_data ) > 0 ) {
                            			foreach ( $product->bundle_data as $b_key => $b_value ) {
                            				$bundle_child[] = $b_key;
                            			}
                            		}
                            		// check if the product is a part of the bundles product, if yes, set qty and totals to blanks
                            		if( isset( $bundle_child ) && count( $bundle_child ) > 0 ) {
                            			if ( in_array( $v->product_id, $bundle_child ) ) {
                            				$item_subtotal_display = $item_price = $quantity = '';
                            			}
                            		}
                                    			
                            		foreach( $position_array as $k => $v ) {
                            			switch( $v ) {
                            				case 'image':
                            					$replace_html .= '<td> <a href="' . $checkout_link_track . '"><img src="' . $image_url . '" alt="" height="42" width="42" /></a> </td>';
                            					break;
                            				case 'name':
                            					$replace_html .= '<td> <a href="' . $checkout_link_track . '">' . $prod_name . '</a> </td>';
                            					break;
                            				case 'price':
                            					if ( $item_price == '' ) {
                            						$replace_html .= '<td></td>';
                            					}
                            					else {
                            						$replace_html .= '<td>' . $item_price . '</td>';
                            					}
                            					break;
                            				case 'quantity':
                            					$replace_html .= '<td>' . $quantity . '</td>';
                            					break;
                            				case 'subtotal':
                            					if ( $item_subtotal_display == '' ) {
                            						$replace_html .= '<td></td>';
                            					}
                            					else {
                            						$replace_html .= '<td>' . $item_subtotal_display . '</td>';
                            					}
                            					break;
                            				default:
                            					$replace_html .= '<td></td>';
                            			}
                            		}
                            		$replace_html .= '</tr>';
                            		$i++;
                        		}
                            	// Calculate the cart total
                            	$cart_total = wc_price( $cart_total );
                            	// Populate/Add the product rows
                            	$email_body = str_replace( $check_html, $replace_html, $email_body );
                            	// Populate the cart total
                            	$email_body = str_replace( "{{cart.total}}", $cart_total, $email_body ); 
    
                            	$email_body = str_replace( "{{item.name}}", $prod_name, $email_body );
            				}
    						if( $woocommerce->version < '2.3' ) {
                                $cart_page_link = $woocommerce->cart->get_cart_url();
    						} else {
                                $cart_page_id   = wc_get_page_id( 'cart' );
                                $cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
                            }
                            
                            if( function_exists( 'icl_register_string' ) ) {
                                if( 'en' == $selected_lanaguage ) {
                                    $cart_page_link = $cart_page_link;
                                } else {
                                    $cart_page_link = apply_filters( 'wpml_permalink', $cart_page_link, $selected_lanaguage );
                                }
                            }
                            $email_body	   = str_replace( "{{checkout.link}}", $checkout_link_track, $email_body );				
                            $encoding_cart = $email_sent_id . '&url=' . $cart_page_link;
                            $validate_cart = Wcap_Abandoned_Cart_Cron_Job_Class::encrypt_validate( $encoding_cart );
                            
                            if( isset( $coupon_code_to_apply ) && $coupon_code_to_apply != '' ) {
                                $encypted_coupon_code = Wcap_Abandoned_Cart_Cron_Job_Class::encrypt_validate( $coupon_code_to_apply );
                                $cart_link_track = get_option( 'siteurl' ) . '/?wacp_action=track_links&validate=' . $validate_cart . '&c=' . $encypted_coupon_code;
                            } else {
                                $cart_link_track = get_option( 'siteurl' ) . '/?wacp_action=track_links&validate=' . $validate_cart;
                            }				
                            $email_body	                   = str_replace( "{{cart.link}}", $cart_link_track, $email_body );				
                            $validate_unsubscribe          = Wcap_Abandoned_Cart_Cron_Job_Class::encrypt_validate( $email_sent_id );					
                            $email_sent_id_address         = $results_sent[0]->sent_email_id;
                            $encrypt_email_sent_id_address = hash( 'sha256', $email_sent_id_address );
                            $plugins_url                   = get_option( 'siteurl' ) . "/?wcap_track_unsubscribe=wcap_unsubscribe&validate=" . $validate_unsubscribe . "&track_email_id=" . $encrypt_email_sent_id_address;
                            $unsubscribe_link_track        = $plugins_url;
                            $email_body                    = str_replace( "{{cart.unsubscribe}}" , $unsubscribe_link_track , $email_body );                                                     
                            $plugins_url_track_image       = get_option( 'siteurl' ) . '/?wcap_track_email_opens=wcap_email_open&email_id=';
                            $hidden_image                  = '<img style="border:0px;" height="1" width="1" alt="" src="' . $plugins_url_track_image . $email_sent_id . '" >';
                            $email_body                    = str_replace( "{{email_open_tracker}}" , $hidden_image , $email_body );
                            $user_email                    = $value->user_email;
                                        
                            if( isset( $is_wc_template ) && "1" == $is_wc_template ){
                                
                                ob_start();
                                
                                wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );                                
                                $email_body_template_header = ob_get_clean();
                                
                                ob_start();
                                
                                wc_get_template( 'emails/email-footer.php' );                                 
                                $email_body_template_footer = ob_get_clean();
                                
                                $final_email_body =  $email_body_template_header . $email_body . $email_body_template_footer;
                                
                                wc_mail( $user_email, $email_subject, $final_email_body, $headers );
                                
                                $admin_abandoned_email      = get_option( 'ac_email_admin_on_abandoned' );
                                if( isset( $admin_abandoned_email ) && 'on' == $admin_abandoned_email ) {
                                    $admin_email     = get_option( 'admin_email' );
                                    wc_mail( $admin_email, $email_subject, $final_email_body, $headers );
                                }
                                            
                            } else {
                                wp_mail( $user_email, $email_subject, $email_body, $headers );
                                $admin_abandoned_email      = get_option( 'ac_email_admin_on_abandoned' );
                                if( isset( $admin_abandoned_email ) && 'on' == $admin_abandoned_email ) {
                                    $admin_email     = get_option( 'admin_email' );
                                    wp_mail( $admin_email, $email_subject, $email_body, $headers );
                                }
                            }

                            
                            
                         }
                    }
                }
            }
        }
   }
   
    /******
    *  This function is used to encode the validate string.
    ******/
    public static function encrypt_validate( $validate ) {
        $cryptKey         = get_option( 'ac_security_key' );        
        $validate_encoded = Wcap_Aes_Ctr::encrypt( $validate, $cryptKey, 256 );
        return( $validate_encoded );
    }

    /**
     * get all carts which have the creation time earlier than the one that is passed
     */
    public static function get_carts( $template_to_send_after_time, $cart_abandon_cut_off_time ) {
        global $wpdb;		
        $cart_time = current_time( 'timestamp' ) - $template_to_send_after_time - $cart_abandon_cut_off_time;		
        if ( is_multisite() ){
            // get main site's table prefix
            $main_prefix = $wpdb->get_blog_prefix(1);
            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id WHERE cart_ignored = '0' AND unsubscribe_link = '0' AND abandoned_cart_time < %d ORDER BY `id` ASC ";
             
        }else{
            // non-multisite - regular table name
            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id WHERE cart_ignored = '0' AND unsubscribe_link = '0' AND abandoned_cart_time < %d ORDER BY `id` ASC ";
        }
        $results   = $wpdb->get_results ( $wpdb->prepare( $query, $cart_time ) );
        return $results;
        exit;
    }
    
    public static function wcap_remove_cart_for_mutiple_templates ( $carts, $time_to_send_template_after, $template_id ){
        global $wpdb;
        foreach ( $carts as $carts_key => $carts_value ){
            
            $wcap_get_last_email_sent_time               = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history` WHERE abandoned_order_id = $carts_value->id ORDER BY `sent_time` DESC LIMIT 1";
            $wcap_get_last_email_sent_time_results_list  = $wpdb->get_results( $wcap_get_last_email_sent_time );
            
            if ( count( $wcap_get_last_email_sent_time_results_list ) > 0 ){
                $last_template_send_time  = strtotime( $wcap_get_last_email_sent_time_results_list[0]->sent_time );
                $second_template_send_time = $last_template_send_time + $time_to_send_template_after ;
                $current_time_test         = current_time( 'timestamp' );
                
                if ( $second_template_send_time > $current_time_test ){
                    unset ( $carts [ $carts_key ] );
                }
            }
        }
        return $carts;
    }

    public  static function get_delete_carts( $template_to_send_after_time, $cart_abandon_cut_off_time ) {    
    	global $wpdb;
    	$cart_time = current_time( 'timestamp' ) - $template_to_send_after_time - $cart_abandon_cut_off_time;
    	if ( is_multisite() ){
    	    // get main site's table prefix
    	    $main_prefix = $wpdb->get_blog_prefix(1);
    	    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id WHERE unsubscribe_link = '0' AND abandoned_cart_time < %d ORDER BY `id` ASC ";
    	
    	} else {
    	    // non-multisite - regular table name
    	    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id WHERE unsubscribe_link = '0' AND abandoned_cart_time < %d ORDER BY `id` ASC ";
    	}
    	$results   = $wpdb->get_results ( $wpdb->prepare( $query, $cart_time ) );
    	return $results;
    	exit;
    }
    
    public static function wcap_update_abandoned_cart_status_for_placed_orders ( $carts, $time_to_send_template_after ){
        global $wpdb;
        foreach ( $carts as $carts_key => $carts_value ){
    
            $abandoned_cart_time = $carts_value->abandoned_cart_time;
            $user_id             = $carts_value->user_id;
            $user_type           = $carts_value->user_type;
            $cart_id             = $carts_value->id;
            if ( $user_id >= '63000000' &&  'GUEST' ==  $user_type ){
                $updated_value = Wcap_Abandoned_Cart_Cron_Job_Class ::wcap_update_status_of_guest ( $cart_id, $abandoned_cart_time , $time_to_send_template_after );
                if ( 1 == $updated_value ){
                    unset ( $carts [ $carts_key ] );
                }
            }elseif ( $user_id < '63000000' &&  'REGISTERED' ==  $user_type ){
    
                $updated_value = Wcap_Abandoned_Cart_Cron_Job_Class ::wcap_update_status_of_loggedin ( $cart_id, $abandoned_cart_time , $time_to_send_template_after );
                if ( 1 == $updated_value ){
                    unset ( $carts [ $carts_key ] );
                }
            }
    
        }
        return $carts;
    }
    
    public static function wcap_update_status_of_loggedin ( $cart_id, $abandoned_cart_time , $time_to_send_template_after ) {
    
        global $wpdb;
    
        // Update the record of the loggedin user who had paid before the first abandoned cart reminder email send to customer.
        $query_records = "SELECT DISTINCT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_type = 'REGISTERED' AND id = $cart_id AND recovered_cart = '0'";
        $results_list  = $wpdb->get_results( $query_records );
        foreach ( $results_list as $key => $value ) {
    
            $user_id            = $value->user_id;
            $key                = 'billing_email';
            $single             = true;
            $user_billing_email = get_user_meta( $user_id, $key, $single );
    
            if( isset( $user_billing_email ) && $user_billing_email == '' ){
                $user_id        = $value->user_id;
                if( is_multisite() ) {
                    // get main site's table prefix
                    $main_prefix = $wpdb->get_blog_prefix(1);
                    $query_email = "SELECT user_email FROM `".$main_prefix."users` WHERE ID = %d";
                     
                } else {
                    // non-multisite - regular table name
                    $query_email = "SELECT user_email FROM `".$wpdb->prefix."users` WHERE ID = %d";
                }
                $results_email       = $wpdb->get_results( $wpdb->prepare( $query_email, $user_id ) );
                $user_billing_email  = $results_email[0]->user_email;
            }
    
            $query_email_id      = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_billing_email' AND meta_value = %s";
            $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $user_billing_email ) );
    
            //if any orders are found with the same email addr..delete those ac records
            if ( is_array( $results_query_email ) && count( $results_query_email ) > 0 ) {
                for ( $i = 0; $i < count( $results_query_email ); $i++ ) {
    
                    $cart_abandoned_time = date ('Y-m-d h:i:s', $abandoned_cart_time);
    
                    $query_post   = "SELECT post_date,post_status FROM `" . $wpdb->prefix . "posts` WHERE ID = %d AND post_date >= %s";
                    $results_post = $wpdb->get_results ( $wpdb->prepare( $query_post, $results_query_email[ $i ]->post_id, $cart_abandoned_time ) );
    
                    if ( count ($results_post) > 0 ){
                        if ( $results_post[0]->post_status == "wc-pending" || $results_post[0]->post_status == "wc-failed" ) {
                            return 0; //if status of the order is pending or falied then return 0 so it will not delete that cart and send reminder email
                        }
                        $order_date_time = $results_post[0]->post_date;
                        if ( strtotime( $order_date_time ) >=  $abandoned_cart_time ) {
    
                            $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                            $wpdb->query( $query_ignored );
                            return 1; //We return here 1 so it indicate that the cart has been modifed so do not sent email and delete from the array.
                        }
                    }
                }
            }
        }
        return 0;
    }
    
    public static function wcap_update_status_of_guest ( $cart_id, $abandoned_cart_time , $time_to_send_template_after ) {
    
        global $wpdb;
        
        $query_guest_records = "SELECT id,email_id FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history`";
        $results_guest_list  = $wpdb->get_results( $query_guest_records );
        
        // This is to ensure that recovered guest carts r removed from the delete list
        $query_records = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_type = 'GUEST' AND recovered_cart != '0'";
        $results_query = $wpdb->get_results( $query_records );
        foreach ( $results_guest_list as $key => $value ) {
            $record_found = "NO";
            foreach ( $results_query as $k => $v ) {
                if ( $value->id == $v->user_id ) {
                    $record_found = "YES";
                }
            }
            if ( $record_found == "YES" ) {
                unset( $results_guest_list[ $key ] );
            }
        }
        foreach ( $results_guest_list as $key => $value ) {
            $query_email_id      = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_billing_email' AND meta_value = %s";
            $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $value->email_id ) );
        
            //if any orders are found with the same email addr..delete those ac records
            if ( $results_query_email ) {
        
                for ( $i = 0; $i < count( $results_query_email ); $i++ ) {
                    $query_post   = "SELECT post_date,post_status FROM `" . $wpdb->prefix . "posts` WHERE ID = %d";
                    $results_post = $wpdb->get_results ( $wpdb->prepare( $query_post, $results_query_email[ $i ]->post_id ) );
        
                    if ( $results_post[0]->post_status == "wc-pending" || $results_post[0]->post_status == "wc-failed" ) {
                        continue;
                    }
                    $order_date_time = $results_post[0]->post_date;
                    if ( strtotime( $order_date_time ) > $abandoned_cart_time ) { 
                        
                        $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                        $wpdb->query( $query_ignored );
                        return 1; //We return here 1 so it indicate that the cart has been modifed so do not sent email and delete from the array.
                    }
                }
            }
        }
        return 0;
    }
    
    public static function check_sent_history( $user_id, $cart_update_time, $template_id, $id ) {
        global $wpdb;
        $query   = "SELECT wpcs . * , wpac . abandoned_cart_time , wpac . user_id FROM `" . $wpdb->prefix . "ac_sent_history` AS wpcs LEFT JOIN " . $wpdb->prefix . "ac_abandoned_cart_history AS wpac ON wpcs.abandoned_order_id =  wpac.id WHERE template_id= %d AND wpcs.abandoned_order_id = %d ORDER BY 'id' DESC LIMIT 1 ";
        $results = $wpdb->get_results ( $wpdb->prepare( $query , $template_id , $id ) );

        if ( count( $results ) == 0 ) {
            return true;
        } elseif ( $results[0]->abandoned_cart_time < $cart_update_time ) {
            return true;
        } else {
            return false;
        }
    }

    public static function wp_coupon_code( $discount_amt, $get_discount_type, $get_expiry_date, $coupon_post_meta ) {        
        $ten_random_string = Wcap_Abandoned_Cart_Cron_Job_Class::wp_random_string();
        $first_two_digit   = rand( 0, 99 );
        $final_string      = $first_two_digit.$ten_random_string;
        $datetime          = $get_expiry_date ; //date( "Y-m-d", strtotime( date( 'Y-m-d' )." +7 days" ) );                    
        $coupon_code       = $final_string;
        
        $coupon_product_categories         = isset( $coupon_post_meta['product_categories'] [ 0 ] ) && $coupon_post_meta['product_categories'] [ 0 ] != '' ? unserialize( $coupon_post_meta['product_categories'] [ 0 ] )  : array();
    	$coupon_exculde_product_categories = isset( $coupon_post_meta['exclude_product_categories'] [ 0 ] ) && $coupon_post_meta['exclude_product_categories'] [ 0 ] != '' ? unserialize ( $coupon_post_meta['exclude_product_categories'] [ 0 ] ) : array();
    	$coupon_product_ids                = isset( $coupon_post_meta['product_ids'] [ 0 ] ) && $coupon_post_meta['product_ids'] [ 0 ] != '' ? $coupon_post_meta['product_ids'] [ 0 ] : '';
    	$coupon_exclude_product_ids        = isset( $coupon_post_meta['exclude_product_ids'] [ 0 ] ) && $coupon_post_meta['exclude_product_ids'] [ 0 ] != '' ? $coupon_post_meta['exclude_product_ids'] [ 0 ] : '';
    	$individual_use                    = isset( $coupon_post_meta['individual_use'] [ 0 ] ) && $coupon_post_meta['individual_use'] [ 0 ] != '' ? $coupon_post_meta['individual_use'] [ 0 ] : 'no';
    	$coupon_free_shipping              = isset( $coupon_post_meta['free_shipping'] [ 0 ] ) && $coupon_post_meta['free_shipping'] [ 0 ] != '' ? $coupon_post_meta['free_shipping'] [ 0 ] : 'no';                	
    	$coupon_minimum_amount             = isset( $coupon_post_meta['minimum_amount'] [ 0 ] ) && $coupon_post_meta['minimum_amount'] [ 0 ] != '' ? $coupon_post_meta['minimum_amount'] [ 0 ] : '';       
    	$coupon_maximum_amount             = isset( $coupon_post_meta['maximum_amount'] [ 0 ] ) && $coupon_post_meta['maximum_amount'] [ 0 ] != '' ? $coupon_post_meta['maximum_amount'] [ 0 ] : '';                      	
    	$coupon_exclude_sale_items         = isset( $coupon_post_meta['exclude_sale_items'] [ 0 ] ) && $coupon_post_meta['exclude_sale_items'] [ 0 ] != '' ? $coupon_post_meta['exclude_sale_items'] [ 0 ] : 'no';
    	$use_limit                         = isset( $coupon_post_meta['usage_limit'] [ 0 ] ) && $coupon_post_meta['usage_limit'] [ 0 ] != '' ? $coupon_post_meta['usage_limit'] [ 0 ] : '';       
    	$use_limit_user                    = isset( $coupon_post_meta['usage_limit_per_user'] [ 0 ] ) && $coupon_post_meta['usage_limit_per_user'] [ 0 ] != '' ? $coupon_post_meta['usage_limit_per_user'] [ 0 ] : '';             
    	$amount        = $discount_amt;
        $discount_type = $get_discount_type; // Type: fixed_cart, percent, fixed_product, percent_product
        $coupon        = array(
                'post_title'   => $coupon_code,
                'post_content' => 'This coupon provides 5% discount on cart price.',
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'shop_coupon',
                'post_expiry_date' => $datetime,
                );
        $new_coupon_id = wp_insert_post( $coupon );
        // Add meta
        update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
        update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
        update_post_meta( $new_coupon_id, 'minimum_amount', $coupon_minimum_amount );      
        update_post_meta( $new_coupon_id, 'maximum_amount', $coupon_maximum_amount );
        update_post_meta( $new_coupon_id, 'individual_use', $individual_use );
        update_post_meta( $new_coupon_id, 'free_shipping', $coupon_free_shipping );
        update_post_meta( $new_coupon_id, 'product_ids', '' );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
        update_post_meta( $new_coupon_id, 'usage_limit', $use_limit );
        update_post_meta( $new_coupon_id, 'usage_limit_per_user', $use_limit_user ); 
        update_post_meta( $new_coupon_id, 'expiry_date', $datetime );
        update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );    
        update_post_meta( $new_coupon_id, 'product_ids', $coupon_product_ids );
        update_post_meta( $new_coupon_id, 'exclude_sale_items', $coupon_exclude_sale_items );
        update_post_meta( $new_coupon_id, 'exclude_product_ids', $coupon_exclude_product_ids );
        update_post_meta( $new_coupon_id, 'product_categories', $coupon_product_categories );
        update_post_meta( $new_coupon_id, 'exclude_product_categories', $coupon_exculde_product_categories );
        
        return $final_string;
    }
        
    public static function wp_random_string() {
        $character_set_array   = array();
        $character_set_array[] = array( 'count' => 5, 'characters' => 'abcdefghijklmnopqrstuvwxyz' );
        $character_set_array[] = array( 'count' => 5, 'characters' => '0123456789' );
        $temp_array            = array();
        foreach ( $character_set_array as $character_set ) {
            for ( $i = 0; $i < $character_set['count']; $i++ ) {
                $temp_array[] = $character_set['characters'][ rand( 0, strlen( $character_set['characters'] ) - 1 ) ];
                }
            }
        shuffle( $temp_array );
        return implode( '', $temp_array );
    }
    
    public  static function wcap_get_translated_texts( $get_translated_text, $message, $language ) {
        if( function_exists( 'icl_register_string' ) ) {
            if ( $language == 'en' ) {
                return $message;
            } else {
                global $wpdb;
                $context = 'WCAP';
                $translated = '';
                $results = $wpdb->get_results( $wpdb->prepare("
                    SELECT s.name, s.value, t.value AS translation_value, t.status
                    FROM  {$wpdb->prefix}icl_strings s
                    LEFT JOIN {$wpdb->prefix}icl_string_translations t ON s.id = t.string_id
                    WHERE s.context = %s
                    AND (t.language = %s OR t.language IS NULL)
                    ", $context, $language ), ARRAY_A);
                foreach( $results as $each_entry ) {
                    if( $each_entry['name'] == $get_translated_text ) {
                        if( $each_entry['translation_value'] ) {
                            $translated = $each_entry['translation_value'];
                        } else {
                            $translated = $each_entry['value'];
                        }
                    }
                }
                if ( $translated != '' ) {
                    return $translated;
                } else {
                    return $message;
                }
            }
        } else {
            return $message;
       }
    }
    
    public static function delete_ac_carts( $value, $cart_update_time ) {    
        global $wpdb;
        $delete_ac_after_days      = get_option( 'ac_delete_abandoned_order_days' );
        $delete_ac_after_days_time = $delete_ac_after_days * 86400;
        $current_time              = current_time( 'timestamp' );
        $check_time                = $current_time - $cart_update_time;
    
        if ( $check_time > $delete_ac_after_days_time && $delete_ac_after_days_time != 0 && $delete_ac_after_days_time != "" ) {
            $user_id           = $value->user_id;
            $query             = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE user_id = '$user_id' AND abandoned_cart_time = '$cart_update_time'";
            $results2          = $wpdb->get_results ( $query );
            
            $query_delete_cart = "DELETE FROM `" . $wpdb->prefix."usermeta` WHERE user_id = '$user_id' AND meta_key = '_woocommerce_persistent_cart' ";
            $results_delete    = $wpdb->get_results ( $query_delete_cart );
    
            if ( $user_id >= '63000000' ) {
                $guest_query   = "DELETE FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` WHERE id = '" . $user_id . "'";
                $results_guest = $wpdb->get_results ( $guest_query );
            }
        }
    }
    
    public static function wcap_validate_email_format( $wcap_email_address) {
        
        if ( version_compare( phpversion(), '5.2.0', '>=') ) {
            $validated_value = filter_var( $wcap_email_address, FILTER_VALIDATE_EMAIL );
            
            if ( $validated_value == $wcap_email_address ){
                $validated_value = 1;
            }else{
                $validated_value = 0;
            }
        } else{
            $pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
            $validated_value = preg_match( $pattern, $wcap_email_address ) ;
        }
        return $validated_value;
    }
    
    public static function wcap_delete_abandoned_carts_after_x_days() {
        global $wpdb;
        $query = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history` WHERE recovered_cart = '0' ";
        $carts = $wpdb->get_results ( $query );
        foreach( $carts as $cart_key => $cart_value ) {
            $cart_update_time = $cart_value->abandoned_cart_time;
            wcap_abandoned_cart_cron_job_class ::delete_ac_carts( $cart_value, $cart_update_time );
        }
    }
}
?>