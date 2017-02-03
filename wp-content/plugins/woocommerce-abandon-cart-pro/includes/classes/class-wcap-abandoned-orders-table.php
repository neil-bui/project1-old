<?php 
// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WCAP_Abandoned_Orders_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.4.7
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.4.7
	 */
	public $base_url;

	/**
	 * Total number of abandoned carts
	 *
	 * @var int
	 * @since 2.4.7
	 */
	public $total_count;

    /**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults
		parent::__construct( array(
    	        'singular' => __( 'abandoned_order_id', 'woocommerce-ac' ),  //singular name of the listed records
    	        'plural'   => __( 'abandoned_order_ids', 'woocommerce-ac' ), //plural name of the listed records
    			'ajax'     => false             			                 // Does this table support ajax?
    		    ) 
		);
		$this->process_bulk_action();
        $this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=listcart' );
	}
	
	public function wcap_abandoned_order_prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$sortable              = $this->get_sortable_columns();	
		$this->total_count     = $this->wcap_get_total_abandoned_count();
	    $data                  = $this->wcap_abandoned_cart_data();		
		$this->_column_headers = array( $columns, $hidden, $sortable);		
		$total_items           = $this->total_count;
				
		if( count($data) > 0 ) {
		  $this->items = $data;
		} else {
		    $this->items = array();
		}		 
		$this->set_pagination_args( array(
				'total_items' => $total_items,                  	      // WE have to calculate the total number of items
				'per_page'    => $this->per_page,                     	  // WE have to determine how many items to show on a page
				'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
		      )
		);
	}
	
	public function get_columns() {	    
        $display_tracked_coupons = get_option( 'ac_track_coupons' );
        $columns                 = array();
        if( "on" == $display_tracked_coupons ) {
            $columns = array(
                'cb'                => '<input type="checkbox" />',
                'id'                => __( 'Id', 'woocommerce-ac' ),
                'email'             => __( 'Email Address', 'woocommerce-ac' ),
                'customer'     		=> __( 'Customer Details', 'woocommerce-ac' ),
                'order_total'  		=> __( 'Order Total', 'woocommerce-ac' ),
                'quantity'  	    => __( 'Quantity', 'woocommerce-ac' ),
                'date'              => __( 'Abandoned Date', 'woocommerce-ac' ),
                'coupon_code_used'  => __( 'Coupon Code Used','woocommerce-ac' ),
                'coupon_code_status'=> __( 'Coupon Status','woocommerce-ac' ),
                'status'            => __( 'Status of Cart', 'woocommerce-ac' )
            );    
        } else {
        	$columns = array(
    	        'cb'                => '<input type="checkbox" />',
                'id'                => __( 'Id', 'woocommerce-ac' ),
    	        'email'             => __( 'Email Address', 'woocommerce-ac' ),
    			'customer'     		=> __( 'Customer Details', 'woocommerce-ac' ),
    			'order_total'  		=> __( 'Order Total', 'woocommerce-ac' ),
    	        'quantity'  	    => __( 'Quantity', 'woocommerce-ac' ),
    			'date'              => __( 'Abandoned Date', 'woocommerce-ac' ),
    			'status'            => __( 'Status of Cart', 'woocommerce-ac' ),
        	);
        }
    	return apply_filters( 'wcap_abandoned_orders_columns', $columns );
	}
	
	/*** 
	 * It is used to add the check box for the items
	 */
	function column_cb( $item ) {	    
	    $abandoned_order_id = '';
	    if( isset( $item->id ) && "" != $item->id ) {
	       $abandoned_order_id = $item->id; 
 	       return sprintf(
	           '<input type="checkbox" name="%1$s[]" value="%2$s" />',
	           'abandoned_order_id',
	           $abandoned_order_id
	       );    
	    }	    
	}
	
	public function get_sortable_columns() {
		$columns = array(
				'date' 	 => array( 'date', false ),
				'status' => array( 'status',false),
		);
		return apply_filters( 'wcap_abandoned_orders_sortable_columns', $columns );
	}
	
	/**
	 * Render the Email Column
	 *
	 * @access public
	 * @since 2.4.8
	 * @param array $abandoned_row_info Contains all the data of the abandoned order tabs row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect of delete.
	 */
	public function column_email( $abandoned_row_info ) {	
	    $row_actions       = array();
	    $value             = '';
	    $abandoned_order_id = 0;	    
	    if( isset( $abandoned_row_info->email ) ) {      
    	    $abandoned_order_id     = $abandoned_row_info->id ;
    	    $row_actions['edit']   = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'listcart', ' action_details' => 'orderdetails', 'id' => $abandoned_order_id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'View order', 'woocommerce-ac' ) . '</a>';
    	    $row_actions['delete'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'action' => 'wcap_delete', 'abandoned_order_id' => $abandoned_row_info->id ), $this->base_url ), 'abandoned_order_nonce') . '">' . __( 'Delete', 'woocommerce-ac' ) . '</a>';
    	    $email                 = $abandoned_row_info->email;
    	    $value                 = $email . $this->row_actions( $row_actions ); 
	    }
	    return apply_filters( 'wcap_abandoned_orders_single_column', $value, $abandoned_order_id, 'email' );
	}
	
	public function wcap_get_total_abandoned_count(  ) {
	    global $wpdb;
	    $results                 = array();
	    $blank_cart_info         = '{"cart":[]}';
	    $blank_cart_info_guest   = '[]';
	     
	    $ac_cutoff_time = get_option( 'ac_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	     
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;
	     
	    if( is_multisite() ) {
	         
	        // get main site's table prefix
	        $main_prefix = $wpdb->get_blog_prefix(1);
	        $query = "SELECT  * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE recovered_cart ='0' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_time <= '$compare_time' ORDER BY abandoned_cart_time DESC";
	        $results = $wpdb->get_results($query);
	    } else {
	        // non-multisite - regular table name
	        $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history` WHERE recovered_cart='0' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_time <= $compare_time ORDER BY abandoned_cart_time DESC";
	        $results = $wpdb->get_results($query);
	    }
	    return count( $results );
	}
    
	public function wcap_abandoned_cart_data() { 	    
	    global $wpdb;    		
		$return_abandoned_orders = array();
		$per_page                = $this->per_page;
		$results                 = array();
		$blank_cart_info         = '{"cart":[]}';
		$blank_cart_info_guest   = '[]';
		
		if( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		    $page_number = $_GET['paged'] - 1;
		
		    $start_limit = ( $per_page * $page_number );
		    $end_limit   =  $per_page;
		    $limit       = 'limit' .' '.$start_limit . ','. $end_limit;
		
		} else {
		    $start_limit = 0;
		    $end_limit   = $per_page;
		    $limit       = 'limit' .' '.$start_limit . ','. $end_limit;
		
		}
		
		if( is_multisite() ) {   
		    // get main site's table prefix
		    $main_prefix = $wpdb->get_blog_prefix(1);
		    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$main_prefix."users AS wpu ON wpac.user_id = wpu.id 
		                  WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC $limit";
		    $results = $wpdb->get_results($query);
		} else {
		    // non-multisite - regular table name
		    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history` AS wpac LEFT JOIN ".$wpdb->prefix."users AS wpu ON wpac.user_id = wpu.id 
		                  WHERE wpac.recovered_cart='0' AND wpac.abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND wpac.abandoned_cart_info NOT LIKE '$blank_cart_info_guest' ORDER BY wpac.abandoned_cart_time DESC $limit";
		    
		    $results = $wpdb->get_results($query);
		}
		
		$i = 0;
		$display_tracked_coupons = get_option( 'ac_track_coupons' );		
		foreach( $results as $key => $value ) {		        		  
		    if( $value->user_type == "GUEST" ) {
		        $query_guest   = "SELECT * from `" . $wpdb->prefix . "ac_guest_abandoned_cart_history` 
		                              WHERE id = %d";
		        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
		    }
		    $abandoned_order_id = $value->id;
		    $user_id            = $value->user_id;
		    $user_login         = $value->user_login;
		
		    if( $value->user_type == "GUEST" ) {		
    		    if( isset( $results_guest[0]->email_id ) ) {
		            $user_email = $results_guest[0]->email_id;
		        } elseif ( $value->user_id == "0" ) {
		            $user_email = '';
		        } else {
		            $user_email = '';
	            }
		        if ( isset( $results_guest[0]->billing_first_name ) ) { 
		            $user_first_name = $results_guest[0]->billing_first_name;
		        } else if( $value->user_id == "0" ) { 
		            $user_first_name = "Visitor";
		        } else {
		            $user_first_name = "";
		        }    		
		        if( isset( $results_guest[0]->billing_last_name ) ) {
		            $user_last_name = $results_guest[0]->billing_last_name;
		        } else if( $value->user_id == "0" ) {
		            $user_last_name = "";
		        } else {
		            $user_last_name = "";
		        }
		        if( isset( $results_guest[0]->phone ) ) {
		            $phone = $results_guest[0]->phone;
		        } elseif ( $value->user_id == "0" ) {
		            $phone = '';
		        } else {
		            $phone = '';
		        }
		    } else {    
		        $user_email_biiling = get_user_meta( $user_id, 'billing_email', true );
		        if( isset( $user_email_biiling ) && $user_email_biiling == "" ) {
		            $user_data  = get_userdata( $user_id );
		            $user_email = $user_data->user_email;
		        } else {
		            $user_email = $user_email_biiling;
		        }    		        
		        $user_first_name_temp = get_user_meta( $value->user_id, 'first_name' );
		        if( isset( $user_first_name_temp[0] ) ) {
		            $user_first_name = $user_first_name_temp[0];
		        } else {
		            $user_first_name = "";
		        }    		
		        $user_last_name_temp = get_user_meta( $value->user_id, 'last_name' );
		        if( isset( $user_last_name_temp[0] ) ) {
		            $user_last_name = $user_last_name_temp[0];
		        } else {
		            $user_last_name = "";
		        }
		        $user_phone_number = get_user_meta( $value->user_id, 'billing_phone' );
		        if( isset( $user_phone_number[0] ) ) {
		            $phone = $user_phone_number[0];
		        } else {
		            $phone = "";
		        }
		    }
		    $cart_info        = json_decode( $value->abandoned_cart_info );
		    $order_date       = "";
		    $cart_update_time = $value->abandoned_cart_time;
		
		    if( $cart_update_time != "" && $cart_update_time != 0 ) {
		        $order_date = date( 'd M, Y h:i A', $cart_update_time );
		    }
		
		    $ac_cutoff_time = get_option( 'ac_cart_abandoned_time' );
		    $cut_off_time   = $ac_cutoff_time * 60;
		    $current_time   = current_time( 'timestamp' );
		    $compare_time   = $current_time - $cart_update_time;
		    $cart_details   = array();
		    $line_total     = 0;		    
		    if( isset( $cart_info->cart ) ) {
		        $cart_details = $cart_info->cart;
		    }
		        		    
		    if( count( $cart_details ) > 0 ) {    		
    	        foreach( $cart_details as $k => $v ) {    		
    	            if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
    	                $line_total = $line_total + $v->line_total + $v->line_subtotal_tax;
    	            } else {
    	                $line_total = $line_total + $v->line_total;
    	            }
    	        }
		    }
		    
		    $line_total     = wc_price( $line_total );
		    $quantity_total = 0;
		    if( count( $cart_details ) > 0) {   		         
		        foreach( $cart_details as $k => $v ) {
		            $quantity_total = $quantity_total + $v->quantity;
		        }
		    }
		    if( 1 == $quantity_total ) {
		        $item_disp = __( "item", "woocommerce-ac" );
		    } else {
		        $item_disp = __( "items", "woocommerce-ac" );
		    }    
		    $coupon_details          = get_user_meta( $value->user_id, '_woocommerce_ac_coupon', true );
		    $coupon_detail_post_meta = get_post_meta( $value->id, '_woocommerce_ac_coupon');    		    
		    if( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
		        $ac_status = __( "Abandoned", "woocommerce-ac" );
		    } elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
		        $ac_status = __( "Abandoned but new cart created after this", "woocommerce-ac" );
		    } else {
		        $ac_status = "";
		    }
		    
		    $coupon_code_used = $coupon_code_message = "";
		    if ( $compare_time > $cut_off_time && $ac_status != "" ) { 		                       
		        $return_abandoned_orders[$i] = new stdClass();    		        
                if( $quantity_total > 0 ) {                        
                    $abandoned_order_id                         = $abandoned_order_id;
                    $customer_information                       = $user_first_name . " ".$user_last_name;
                    $return_abandoned_orders[ $i ]->id          = $abandoned_order_id;
                    $return_abandoned_orders[ $i ]->email       = $user_email;
                    if( $phone == '' ) {
                        $return_abandoned_orders[ $i ]->customer    = $customer_information;
                    } else { 
                        $return_abandoned_orders[ $i ]->customer    = $customer_information . "<br>" . $phone; 
                    }                 
                    $return_abandoned_orders[ $i ]->order_total = $line_total;
                    $return_abandoned_orders[ $i ]->quantity    = $quantity_total . " " . $item_disp;
                    $return_abandoned_orders[ $i ]->date        = $order_date;
                    $return_abandoned_orders[ $i ]->status      = $ac_status;
                    $return_abandoned_orders[ $i ]->user_id     = $user_id;
                    
                    if( $display_tracked_coupons == 'on' ) {                            
                        if( $coupon_detail_post_meta != '' ) {                                
                            foreach( $coupon_detail_post_meta as $key => $value ) {
                               if( $coupon_detail_post_meta[$key]['coupon_code'] != '' ) {
                                    $coupon_code_used .= $coupon_detail_post_meta[$key]['coupon_code'] . "</br>";
                               }
                            }
                            $return_abandoned_orders[ $i ]->coupon_code_used = $coupon_code_used;
                        }                            
                        if( $coupon_detail_post_meta != '' && $coupon_code_used !== '' ) {
                            foreach( $coupon_detail_post_meta as $key => $value ) {
                                $coupon_code_message .= $coupon_detail_post_meta[$key]['coupon_message'] . "</br>";
                            }
                            $return_abandoned_orders[ $i ]->coupon_code_status = $coupon_code_message;
                        }
                    }
                } else {
                   $abandoned_order_id                    = $abandoned_order_id;
                   $return_abandoned_orders[ $i ]->id     = $abandoned_order_id;
                   $return_abandoned_orders[ $i ]->date   = $order_date;
                   $return_abandoned_orders[ $i ]->status = $ac_status;
                }
                $i++;
            } 
		}	                    
        // sort for order date
    	 if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'date' ) {
    	     if( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    	         usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_order_date_asc" ) );
    	     } else {
    	         usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_order_date_dsc" ) );
    	     }
    	 } else if( isset( $_GET['orderby'] ) && $_GET['orderby'] == 'status' ) { // sort for customer name
    		if ( isset( $_GET['order'] ) && $_GET['order'] == 'asc' ) {
    			usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_status_asc" ) );
    		} else {
    			usort( $return_abandoned_orders, array( __CLASS__ , "wcap_class_status_dsc" ) );
    		}
    	}
    	
    	return apply_filters( 'wcap_abandoned_orders_table_data', $return_abandoned_orders );
    }
    	
    function wcap_class_order_date_asc( $value1,$value2 ) {    	    
	   if( isset( $value1->date ) && isset( $value2->date ) ) {
    	    $date_two           = $date_one = '';
    	    $value_one          = $value1->date;
    	    $value_two          = $value2->date; 
    	    $date_formatted_one = date_create_from_format( 'd M, Y h:i A', $value_one );
    	    $date_formatted_two = date_create_from_format( 'd M, Y h:i A', $value_two );
    	    if( isset( $date_formatted_one ) && $date_formatted_one != '' ) {
    	        $date_one = date_format( $date_formatted_one, 'Y-m-d h:i A' );
    	    }
    	    if( isset( $date_formatted_two ) && $date_formatted_two != '' ) {
    	        $date_two = date_format( $date_formatted_two, 'Y-m-d h:i A' );
    	    }
    	    return strtotime($date_one) - strtotime($date_two);
	    } else {	       
	        return 1;
	    }
	}
	
	function wcap_class_order_date_dsc( $value1,$value2 ) {	    
	   if( isset( $value1->date ) && isset( $value2->date ) ) {
    	    $date_two            = $date_one = '';
    	    $value_one           = $value1->date;
    	    $value_two           = $value2->date;    	     
    	    $date_formatted_one  = date_create_from_format( 'd M, Y h:i A', $value_one );
    	    $date_formatted_two  = date_create_from_format( 'd M, Y h:i A', $value_two );
    	    if( isset( $date_formatted_one ) && $date_formatted_one != '' ) {
    	        $date_one = date_format( $date_formatted_one, 'Y-m-d h:i A' );
    	    }
    	    if( isset( $date_formatted_two ) && $date_formatted_two != '' ) {
    	        $date_two = date_format( $date_formatted_two, 'Y-m-d h:i A' );
    	    }    	    
    	    return strtotime( $date_two ) - strtotime( $date_one );
	    } else {      
	        return 1;
	    };
	}
	
	function wcap_class_status_asc( $value1,$value2 ) {
	    return strcasecmp( $value1->status,$value2->status );
	}
	
	function wcap_class_status_dsc ( $value1,$value2 ) {
	    return strcasecmp( $value2->status,$value1->status );
	}
	
	public function column_default( $wcap_abandoned_orders, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {
	        case 'id' :
			    if( isset( $wcap_abandoned_orders->id ) ) {
			     $value = '<strong><a href="admin.php?page=woocommerce_ac_page&action=listcart&action_details=orderdetails&id='.$wcap_abandoned_orders->id.' ">'.$wcap_abandoned_orders->id.'</a> </strong>';
			    }
				break;
			case 'customer' :
			    if( isset( $wcap_abandoned_orders->customer ) ) {
			        
			        $user_role = '';
			        if ( $wcap_abandoned_orders->user_id == 0 ){
			            $user_role = 'Guest';
			        }
			        elseif ( $wcap_abandoned_orders->user_id >= 63000000 ){
			            $user_role = 'Guest';
			        }else{
			            $user_role = wcap_common::wcap_get_user_role ( $wcap_abandoned_orders->user_id );
			            
			        }
			        $value = $wcap_abandoned_orders->customer . "<br>" .$user_role ;
			        
			    }
				break;
			case 'order_total' :
			    if( isset( $wcap_abandoned_orders->order_total ) ) {
			       $value = $wcap_abandoned_orders->order_total;
			    }
				break;
			case 'quantity' :
			    if( isset( $wcap_abandoned_orders->quantity ) ) {
			       $value = $wcap_abandoned_orders->quantity;
			    }
				break;
			case 'date' :
			    if( isset( $wcap_abandoned_orders->date ) ) {
	 			   $value = $wcap_abandoned_orders->date;
			    }
				break;
			case 'status' :
			    if( isset( $wcap_abandoned_orders->status ) ) {
			     $value = $wcap_abandoned_orders->status;
			    }
			    break;
		    case 'coupon_code_used' :
		        if( isset( $wcap_abandoned_orders->coupon_code_used ) ) {
		            $value = $wcap_abandoned_orders->coupon_code_used;
		        }
		        break;
	        case 'coupon_code_status' :
	            if( isset( $wcap_abandoned_orders->coupon_code_status ) ) {
	                $value = $wcap_abandoned_orders->coupon_code_status;
	            }
	            break;
			default :    
				$value = isset( $wcap_abandoned_orders->$column_name ) ? $wcap_abandoned_orders->$column_name : '';
				break;
	    }
		return apply_filters( 'wcap_abandoned_orders_column_default', $value, $wcap_abandoned_orders, $column_name );
	}
	
	public function get_bulk_actions() {
	    return array(
	        'wcap_delete' => __( 'Delete', 'woocommerce-ac' )
	    );
	}
}
?>