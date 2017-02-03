<?php 

if( session_id() === '' ){
    //session has not started
    session_start();
}
// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WCAP_Sent_Emails_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.5
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.5
	 */
	public $base_url;

	/**
	 * Total number of recovered orders
	 *
	 * @var int
	 * @since 2.5
	 */
	public $total_count;
	
	/**
	 * Total number of recovered orders
	 *
	 * @var int
	 * @since 2.5
	 */
	public $open_emails;
	
	/**
	 * Total amount of abandoned orders
	 *
	 * @var int
	 * @since 2.5
	 */
	public $link_click_count;
	
	/**
	 * Total number recovered orders
	 *
	 * @var int
	 * @since 2.5
	 */
	public $start_date_db;
	
	/**
	 * Total number recovered orders total
	 *
	 * @var int
	 * @since 2.5
	 */
	public $end_date_db;
	
	public $duration;
	
    /**
	 * Get things started
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {

		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
	        'singular' => __( 'sent_email_id', 'woocommerce-ac' ), //singular name of the listed records
	        'plural'   => __( 'sent_email_ids', 'woocommerce-ac' ), //plural name of the listed records
			'ajax'      => true             			// Does this table support ajax?
		) );
		
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page&action=stats' );
	}
	
	public function wcap_sent_emails_prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns
		$data                  = $this->wcap_sent_emails_data();
		$total_items           = $this->total_count;
 		$open_emails           = $this->open_emails;
 		$link_click_count      = $this->link_click_count;
 		$end_date_db           = $this->end_date_db;
 		$start_date_db         = $this->start_date_db;
        $duration              = $this->duration;
		$this->items           = $data;
		$this->_column_headers = array( $columns, $hidden);
		$this->set_pagination_args( array(
                'total_items' => $total_items,                  	// WE have to calculate the total number of items
                'per_page'    => $this->per_page,                     	// WE have to determine how many items to show on a page
                'total_pages' => ceil( $total_items / $this->per_page )   // WE have to calculate the total number of pages
            )
		);
	}
	
	public function get_columns() {
	    
	    $columns = array(
	        'user_email_id'     => __( 'User Email Address'       , 'woocommerce-ac' ),
            'sent_time'         => __( 'Email Sent Time'          , 'woocommerce-ac' ),
	        'date_time_opened'  => __( 'Date / Time  Email Opened', 'woocommerce-ac' ),
			'link_clicked'  	=> __( 'Link Clicked'             , 'woocommerce-ac' ),
            'template_name'     => __( 'Sent Email Template'      , 'woocommerce-ac'),
	    );
		return apply_filters( 'wcap_sent_emails_columns', $columns );
	}
    /**
	 * Render the user name Column
	 *
	 * @access public
	 * @since 2.5
	 * @param array $abandoned_row_info Contains all the data of the template row 
	 * @return string Data shown in the Email column
	 * 
	 * This function used for individual delete of row, It is for hover effect delete.
	 */
	public function column_user_email_id( $sent_emails_row_info ) {
	
	    $row_actions = array();
	    $value = '';
	    $sent_id = 0;
	    
	    if( isset( $sent_emails_row_info->user_email_id ) ){
	        $display_link = $sent_emails_row_info->display_link ;
    	    if ( "Abandoned" == $display_link ){
                $view_link = "admin.php?page=woocommerce_ac_page&action=listcart&action_details=orderdetails&id=" . $sent_emails_row_info->abandoned_order_id;
    	        $view_name = __( "Abandoned Order", "woocommerce-ac" );
    	        $row_actions['view_details']   = "<a target=_blank href = $view_link>". $view_name ."</a>";
    	    }else{
    	        $view_link = "post.php?post=" . $sent_emails_row_info->recover_order_id . "&action=edit";
    	        $view_name = __( " Recovered Order", "woocommerce-ac" );
    	        $row_actions['view_details']   = "<a target=_blank href = $view_link>". $view_name ."</a>";
    	    }
    	    $user_name = $sent_emails_row_info->user_email_id;
            $value = $user_name . $this->row_actions( $row_actions );
	    }
        return apply_filters( 'wcap_sent_emails_single_column', $value, $sent_id, 'email' );
	}
    
	public function wcap_sent_emails_data() { 
		global $wpdb;
		$wcap_class 	 = new woocommerce_abandon_cart ();
		$ac_results_sent = $ac_results_opened = array();
		$duration_range  = '';
		
		if ( isset( $_POST['duration_select_email'] ) && '' != $_POST['duration_select_email'] ){
		    $duration_range         = $_POST['duration_select_email'];
		    $_SESSION['duration']   = $duration_range;
		}
		
		if ( isset( $_SESSION ['duration'] ) && '' != $_SESSION ['duration'] ){
            $duration_range         = $_SESSION ['duration'];
		}
		
		if ( '' == $duration_range ) {
		    $duration_range         = "last_seven";
		    $_SESSION['duration']   = $duration_range;
		}
		
		$start_date_range = '';
		if ( isset( $_POST['start_date_email'] ) && '' != $_POST['start_date_email'] ) {
		    $start_date_range        = $_POST['start_date_email'];
		    $_SESSION ['start_date'] = $start_date_range;
		}
		
		if ( isset( $_SESSION ['start_date'] ) &&  '' != $_SESSION ['start_date'] ) {
            $start_date_range = $_SESSION ['start_date'];
		}
		
		if ( '' == $start_date_range ) {
		   $start_date_range = $wcap_class->start_end_dates[$duration_range]['start_date'];
		   $_SESSION ['start_date'] = $start_date_range;
		}
		
		$end_date_range = '';
		if ( isset( $_POST['end_date_email'] ) && '' != $_POST['end_date_email'] ){
            $end_date_range = $_POST['end_date_email'];
            $_SESSION ['end_date'] = $end_date_range;
        }
		
		if ( isset($_SESSION ['end_date'] ) && '' != $_SESSION ['end_date'] ){
            $end_date_range = $_SESSION ['end_date'];
		}
		
		if ( '' == $end_date_range ) {
		    $end_date_range = $wcap_class->start_end_dates[$duration_range]['end_date'];
		    $_SESSION ['end_date'] = $end_date_range;
		}
		
		$start_date             = strtotime( $start_date_range." 00:01:01" );
		$end_date               = strtotime( $end_date_range." 23:59:59" );
		$start_date_db          = date( 'Y-m-d H:i:s', $start_date );
		$end_date_db            = date( 'Y-m-d H:i:s', $end_date );
		
		/* Now we use the LIMIT clause to grab a range of rows */
		$query_ac_sent          = "SELECT * FROM " . $wpdb->prefix . "ac_sent_history WHERE sent_time >= %s AND sent_time <= %s ORDER BY `id` DESC";
		$ac_results_sent        = $wpdb->get_results( $wpdb->prepare( $query_ac_sent, $start_date_db, $end_date_db ) );
		
		$query_ac_clicked       = "SELECT DISTINCT email_sent_id FROM " . $wpdb->prefix . "ac_link_clicked_email WHERE time_clicked >= '" . $start_date_db . "' AND time_clicked <= '" . $end_date_db . "' ORDER BY id DESC ";
		$ac_results_clicked     = $wpdb->get_results( $query_ac_clicked, ARRAY_A );
		
		$query_ac_opened        = "SELECT DISTINCT wpoe.email_sent_id, wpsh . id FROM " . $wpdb->prefix . "ac_opened_emails as wpoe LEFT JOIN ".$wpdb->prefix."ac_sent_history AS wpsh ON wpsh.id = wpoe.email_sent_id WHERE time_opened >= '" . $start_date_db . "' AND time_opened <= '" . $end_date_db . "' AND wpsh.id = wpoe.email_sent_id ORDER BY id DESC ";
		$ac_results_opened      = $wpdb->get_results( $query_ac_opened, ARRAY_A );
		
		$this->total_count      = count ($ac_results_sent);
		$this->open_emails      = count ($ac_results_opened);
		$this->link_click_count = count ($ac_results_clicked);
		
		$i = 0;
    	foreach ( $ac_results_sent as $key => $value ) {
		    
		    $sent_tmstmp                = strtotime( $value->sent_time );
		    $sent_date                  = date( 'd M Y h:i A', $sent_tmstmp );
		    $query_template_name        = "SELECT template_name FROM " . $wpdb->prefix . "ac_email_templates WHERE id= %d";
		    $ac_results_template_name   = $wpdb->get_results( $wpdb->prepare( $query_template_name, $value->template_id ) );
		    $link_clicked               = '';
		
		    $ac_email_template_name     = '';
		    if ( isset( $ac_results_template_name[0]->template_name ) ) {
		        $ac_email_template_name = $ac_results_template_name[0]->template_name;
		    }
            $return_sent_emails[ $i ]     = new stdClass();
		    
		    foreach( $ac_results_clicked as $clicked_key => $clicked_value ) {
		        
		        if ( $clicked_value['email_sent_id'] == $value->id ) {
		            
		            $query_links        = "SELECT * FROM " . $wpdb->prefix . "ac_link_clicked_email WHERE email_sent_id= %d ORDER BY `id` DESC LIMIT 1";
		            $results_links      = $wpdb->get_results( $wpdb->prepare( $query_links,$value->id ) );
		            $checkout_page_id   = get_option( 'woocommerce_checkout_page_id' );
		            $checkout_page      = get_post( $checkout_page_id );
		            $checkout_page_link = $checkout_page->guid;
		            $checkout_page_link = get_permalink( $checkout_page->ID );
		            $cart_page_id       = get_option( 'woocommerce_cart_page_id' );
		            $cart_page          = get_post( $cart_page_id );
		            $cart_page_link     = $cart_page->guid;
		            $cart_page_link     = get_permalink( $cart_page->ID );
		
		            if( $results_links[0]->link_clicked == $checkout_page_link ) {
		                $link_clicked   = "Checkout Page";
		            } elseif( $results_links[0]->link_clicked == $cart_page_link ) {
		                $link_clicked   = "Cart Page";
		            }
		        }
		    }
		    $email_opened = "";
            foreach( $ac_results_opened as $opened_key => $opened_value ) {
		
		        if ( $opened_value['email_sent_id'] == $value->id ) {
		            $query_opens   = "SELECT * FROM " . $wpdb->prefix . "ac_opened_emails WHERE email_sent_id= %d ORDER BY `id` DESC LIMIT 1";
		            $results_opens = $wpdb->get_results( $wpdb->prepare( $query_opens, $value->id ) );
		            $opened_tmstmp = strtotime( $results_opens[0]->time_opened );
		            $email_opened  = date( 'd M Y h:i A', $opened_tmstmp );
		        }
		    }
		    $view_order_query   = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history WHERE id= %d";
		    $view_order_results = $wpdb->get_results( $wpdb->prepare( $view_order_query, $value->abandoned_order_id ) );
		
		    $view_link = $view_name = $recover_id = '';
            $view_name_flag = 'Abandoned';
            
		    if ( isset( $view_order_results[0]->recovered_cart ) ) {
                if ( $view_order_results[0]->recovered_cart == 0 ) {
		            $view_name_flag = "Abandoned";
		        } else {
		            $recover_id = $view_order_results[0]->recovered_cart;
		            $view_name_flag = "";
		        }
		    }
		    $return_sent_emails[ $i ]->sent_time          = $sent_date ;
		    $return_sent_emails[ $i ]->user_email_id      = $value->sent_email_id;
		    $return_sent_emails[ $i ]->date_time_opened   = $email_opened;
		    $return_sent_emails[ $i ]->link_clicked       = $link_clicked;
		    $return_sent_emails[ $i ]->template_name      = $ac_email_template_name;
		    $return_sent_emails[ $i ]->display_link       = $view_name_flag;
		    $return_sent_emails[ $i ]->abandoned_order_id = $value->abandoned_order_id;
		    $return_sent_emails[ $i ]->recover_order_id   = $recover_id;
		    
		    $i++;
		 }
         $per_page        = $this->per_page;
		 if ( isset( $_GET['paged'] ) && $_GET['paged'] > 1 ) {
		     $page_number = $_GET['paged'] - 1;
		     $k           = $per_page * $page_number;
		 }else {
		     $k           = 0;
		 }
		 
		 $return_sent_email_display = array();
		 for ( $j = $k; $j < ( $k + $per_page ); $j++ ) {
            if ( isset( $return_sent_emails[ $j ] ) ) {
		         $return_sent_email_display[ $j ] = $return_sent_emails[ $j ];
		     }else {
		         break;
		     }
		 }
		return apply_filters( 'wcap_sent_emails_table_data', $return_sent_email_display );
	}
	
	public function column_default( $wcap_sent_emails, $column_name ) {
	    $value = '';
	    switch ( $column_name ) {
	        
	        case 'user_email_id' :
			    if( isset( $wcap_sent_emails->user_email_id ) ){
			        $value = $wcap_sent_emails->user_email_id;
			    }
				break;
			
			case 'sent_time' :
			    if( isset( $wcap_sent_emails->sent_time ) ){
                    $value = $wcap_sent_emails->sent_time;
			    }
				break;
			
			case 'date_time_opened' :
			    if( isset( $wcap_sent_emails->date_time_opened ) ){
                    $value   = $wcap_sent_emails->date_time_opened;
			    }
				break;
			
			case 'link_clicked' :
			    if( isset( $wcap_sent_emails->link_clicked ) ){
                    $value = $wcap_sent_emails->link_clicked;
			    }
				break;
			
			case 'template_name' :
			    if( isset( $wcap_sent_emails->template_name ) ){
                    $value = $wcap_sent_emails->template_name;
			    }
			    break;
		    default:
			    
				$value = isset( $wcap_sent_emails->$column_name ) ? $wcap_sent_emails->$column_name : '';
				break;
	    }
		
		return apply_filters( 'wcap_sent_emails_column_default', $value, $wcap_sent_emails, $column_name );
	}
}
?>