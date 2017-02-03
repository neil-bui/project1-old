<?php
/**
 * FraudLabs Pro Integration.
 *
 * @package  WC_Integration_FraudLabs_Pro
 * @category Integration
 * @author   FraudLabs Pro
 */

if ( ! class_exists( 'WC_Integration_FraudLabs_Pro' ) ) :

class WC_Integration_FraudLabs_Pro extends WC_Integration {

	/**
	 * Initializes and hook in the integration.
	 */
	public function __construct() {
		$this->namespace			= 'woocommerce-fraudlabs-pro';
		$this->method_title			= __( 'FraudLabs Pro', 'woocommerce-fraudlabs-pro' );
		$this->method_description	= __( 'FraudLabs Pro helps you to screen your order transaction, such as credit card transaction, for online fraud. Get a <a href="http://www.fraudlabspro.com/sign-up?r=woocommerce" target="_blank">free API key</a> if you do not have one.', 'woocommerce-fraudlabs-pro' );

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->enabled				= $this->get_setting( 'enabled' );
		$this->api_key				= $this->get_setting( 'api_key' );
		$this->cancel_score			= $this->get_setting( 'cancel_score' );
		$this->hold_score			= $this->get_setting( 'hold_score' );
		$this->test_ip				= $this->get_setting( 'test_ip' );
		$this->store_admin_email	= $this->get_setting( 'store_admin_email' );
		$this->receive_report		= $this->get_setting( 'receive_report' );
		$this->approve_status		= $this->get_setting( 'approve_status' );
		$this->review_status		= $this->get_setting( 'review_status' );
		$this->reject_status		= $this->get_setting( 'reject_status' );
		$this->sms					= $this->get_setting( 'sms' );
		$this->sms_retries			= $this->get_setting( 'sms_retries' );
		$this->sms_template			= $this->get_setting( 'sms_template' );

		// Actions.
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 70 );
		add_action( 'woocommerce_settings_tabs_' . $this->namespace, array( $this, 'tab_content' ) );
		add_action( 'woocommerce_update_options_' . $this->namespace, array( $this, 'update_settings' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'change_order_status' ), 99, 3 );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ), 11 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column' ), 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'render_fraud_report' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_enqueues' ) );
		add_action( 'admin_notices', array( $this, 'admin_notifications' ) );
		add_action( 'wp_ajax_fraudlabspro_woocommerce_admin_notice', array( $this, 'plugin_dismiss_admin_notice' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'woocommerce_checkout_after_customer_details', array( $this, 'sms_form' ) );
	}

	/**
	 * Check for valid IP.
	 */
	function isvalidip( $ip ) {
		if ( ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) || ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) ) {
			return true;
		}
		else {
			return false;
		}
	}

	/**
	 * Gets user IP.
	 */
	function getip() {
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && $this->isvalidip( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
			$ip_address = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
		}
		else {
			$ip_address = $_SERVER[ 'REMOTE_ADDR' ];
		}

		if ( preg_match( '/,/', $ip_address ) ) {
			$tmp = explode( ',', $ip_address );
			$ip_address = trim( $tmp[ 0 ] );
		}
		return $ip_address;
	}

	/**
	 * Create sms counter table
	 */
	function create_sms_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'flp_sms_counter';

		$sql = "CREATE TABLE $table_name (
			`ip` varchar(50) NOT NULL,
			`counter` int DEFAULT '0',
			`last` int DEFAULT '0',
			`ajax` varchar(50) DEFAULT '',
			PRIMARY KEY (`ip`)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql ); // this will check if table structure needs to be updated and do it automatically

		return $table_name;
	}

	/**
	 * Query sms counter table
	 */
	function query_sms_table( $table_name, $ip ) {
		global $wpdb;

		return $wpdb->get_row( "SELECT * FROM $table_name WHERE ip = '" . $ip . "' LIMIT 1" );
	}

	/**
	 * Update sms counter table
	 */
	function update_sms_table( $table_name, $ip, $counter, $last, $ajax ) {
		global $wpdb;

		$wpdb->replace( $table_name, array( 'ip' => $ip, 'counter' => $counter, 'last' => $last, 'ajax' => $ajax ), array( '%s', '%d', '%d', '%s' ) );
	}

	/**
	 * Insert SMS authentication codes
	 */
	function sms_form() {
		if ( ( !$this->enabled ) || ( trim( $this->api_key ) == '' ) || ( $this->sms === 'no' ) )
			return;

		$table_name = $this->create_sms_table();

		$flp_ajax_field = 'flp_ajax_' . substr( hash( 'sha256', uniqid() ), 8, 24 ); // dynamic field name for authentication

		$ip = $this->getip();

		$smsdata = $this->query_sms_table( $table_name, $ip );

		if ( ( $smsdata === NULL ) || ( $smsdata->last < strtotime( '-10 minutes' ) ) ) {
			// reset counter
			$this->update_sms_table( $table_name, $ip, $this->sms_retries, time(), $flp_ajax_field );
			$smsdata = $this->query_sms_table( $table_name, $ip );
		}
		else {
			// just update ajax field
			$this->update_sms_table( $table_name, $ip, $smsdata->counter, $smsdata->last, $flp_ajax_field );
		}
		// refresh data
		$smsdata = $this->query_sms_table( $table_name, $ip );

		$retries = $smsdata->counter;
		echo '<h3 id="verifysms">SMS Verification <abbr class="required" title="required">*</abbr></h3>';
		echo '<div style="font-size: 12px; border: 1px solid silver; padding: 5px;">Before you can place an order, you will need to have your phone number verified via SMS so please ensure that your billing phone number is a mobile phone number capable of receiving an SMS. The SMS will contain an One-Time-Password (OTP) which you will need to key in below.<br /><br />Your phone number must include the country code.<br /><br />';
		echo '<span id="sms_status">' . ( ( $retries == 0 ) ? 'Maximum number of retries to send verification SMS exceeded.' : '' ) . '</span>';
		echo '<input type="text" name="sms_otp" id="sms_otp" value="" placeholder="Key in OTP here." style="margin-bottom: 10px;' . ( ( $retries == 0 ) ? 'display: none;' : '' ) . '">';
		echo '<input type="button" name="submit_otp" id="submit_otp" value="Submit OTP" style="margin-right: 5px; display: none;">';
		echo '<input type="button" name="get_otp" id="get_otp" value="Get OTP" style="margin-right: 5px;' . ( ( $retries == 0 ) ? 'display: none;' : '' ) . '">';
		echo '<input type="button" name="resend_otp" id="resend_otp" value="Resend OTP" style="margin-right: 5px; display: none;">';
		echo '<input type="hidden" name="sms_verified" id="sms_verified" value="">';
		echo '<input type="hidden" name="sms_tran_id" id="sms_tran_id" value="">';
		echo '<input type="hidden" name="sms_retries" id="sms_retries" value="' . $retries . '">';
		echo '</div>';
		echo '<script language="Javascript">
			function doOTP() {
				data = { "action": "send", "tel": jQuery("#billing_phone").val(), "country_code": jQuery("#billing_country").val(), "' . $flp_ajax_field . '": "dummy" };
				jQuery.ajax({
					type: "POST",
					url: "' . plugins_url() . '/fraudlabs-pro-for-woocommerce/ajax-sms.php",
					data: data,
					success: sms_success,
					error: sms_error,
					dataType: "text"
				});
			}
			function checkOTP() {
				data = { "action": "verify", "otp": jQuery("#sms_otp").val(), "tran_id": jQuery("#sms_tran_id").val(), "' . $flp_ajax_field . '": "dummy" };
				jQuery.ajax({
					type: "POST",
					url: "' . plugins_url() . '/fraudlabs-pro-for-woocommerce/ajax-sms.php",
					data: data,
					success: sms_success2,
					error: sms_error2,
					dataType: "text"
				});
			}
			jQuery("#sms_otp").bind("keypress", function(e) {
				var code = e.keyCode || e.which;
				if (code == 13) { //Enter keycode
					e.preventDefault();
				}
			});
			jQuery("#get_otp").click(function() {
				jQuery("#get_otp").hide();
				jQuery("#resend_otp").show();
				doOTP();
			});
			jQuery("#resend_otp").click(function() {
				doOTP();
			});
			jQuery("#submit_otp").click(function() {
				checkOTP();
			});
			jQuery(\'form[name="checkout"]\').submit(function(event) {
				if (jQuery.trim(jQuery("#sms_verified").val()) == "") {
					// not yet verified
					event.preventDefault();
					event.stopImmediatePropagation();
					alert("SMS verification not done.");
					jQuery("#sms_otp").focus();
					document.getElementById("verifysms").scrollIntoView(true);
				}
			});
			function sms_success(data) {
				// need to count down the number of retries
				jQuery("#sms_retries").val(jQuery("#sms_retries").val() - 1);
				if (data.indexOf("ERROR") == 0) {
					alert("Error: Could not send verification SMS.");
					if (jQuery("#sms_retries").val() == 0) {
						jQuery("#sms_otp").hide();
						jQuery("#submit_otp").hide();
						jQuery("#get_otp").hide();
						jQuery("#resend_otp").hide();
						jQuery("#sms_status").text("Maximum number of retries to send verification SMS exceeded.");
					}
				}
				else if (data.indexOf("OK") == 0) {
					alert("A verification SMS has been sent to " + jQuery("#billing_phone").val() + ".");
					// store the tran_id
					jQuery("#sms_tran_id").val(data.substr(2));
					// show the submit otp button
					jQuery("#submit_otp").show();
					if (jQuery("#sms_retries").val() == 0) {
						jQuery("#get_otp").hide();
						jQuery("#resend_otp").hide();
					}
				}
			}
			function sms_error() {
				alert("Error: Could not send verification SMS.");
			}
			function sms_success2(data) {
				if (data.indexOf("ERROR") == 0) {
					alert("Error: " + data.substr( 10 ) );
				}
				else if (data.indexOf("OK") == 0) {
					// update the sms verified field
					jQuery("#sms_verified").val("YES");
					// hide all buttons and text field for the SMS...
					jQuery("#sms_otp").hide();
					jQuery("#submit_otp").hide();
					jQuery("#get_otp").hide();
					jQuery("#resend_otp").hide();
					// show SMS verified message
					jQuery("#sms_status").text("SMS verification successful.");
				}
			}
			function sms_error2() {
				alert("Error: Could not perform verification.");
			}
		</script>
		';
	}

	/**
	 * Trigger fraud check when order status changed.
	 */
	public function change_order_status( $id, $old_status, $new_status ) {
		if ( 'completed' == $new_status || 'processing' == $new_status || 'on-hold' == $new_status ) {
			$result = get_post_meta( $id, '_fraudlabspro' );

			if( count( $result ) > 0 ) {
				return;
			}

			$this->check_order( $id );
		}
		/*elseif ( 'failed' == $new_status  ) {
			$result = get_post_meta( $id, '_fraudlabspro' );

			if( count( $result ) > 0 ) {
				return;
			}

			$this->order_payment_failed( $id );
		}*/
	}


	/**
	 * Define setting fields.
	 */
	private function get_fields() {

		$score_options = array();
		for ( $i = 100; $i > - 1; $i -- ) {
			if ( ( $i % 5 ) == 0 ) {
				$score_options[$i] = $i;
			}
		}

		$setting_fields = array(
			'settings_section' => array(
				'title'			=> __( 'FraudLabs Pro Settings', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'title',
				'desc'			=> '',
			),
			'enabled' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_enabled',
				'name'			=> __( 'Enabled', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'checkbox',
				'desc'			=> __( 'Enable or disable FraudLabs Pro.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> 'yes'
			),
			'api_key' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_api_key',
				'name'			=> __( 'API Key', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'text',
				'desc'			=> __( '<p>Please sign up an API key at <a href="http://www.fraudlabspro.com/pricing?utm_source=module&utm_medium=banner&utm_term=woocommerce&utm_campaign=module%20banner" target="_blank">FraudLabsPro</a></p>' ),
				'default'		=> '',
				'css'			=> 'width:50%',
			),
			'cancel_score'  => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_cancel_score',
				'name'			=> __( 'Cancel Score', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'select',
				'options'		=> $score_options,
				'desc'			=> __( 'Orders with risk score equal to or higher than this number will be cancelled.', 'woocommerce-fraudlabs-pro' ),
				'id'			=> 'wc_settings_' . $this->namespace . '_cancel_score',
				'default'		=> '90',
				'desc'			=> __('(Deprecated soon) <a href="http://www.fraudlabspro.com/tutorials/woocommerce-plugin-cancel-score-and-on-hold-score-deprecated?utm_source=module&utm_medium=banner&utm_term=woocommerce&utm_campaign=module%20banner" target="_blank">Learn More</a>'),
			),
			'hold_score'    => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_hold_score',
				'name'			=> __( 'On-hold Score', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'select',
				'options'		=> $score_options,
				'desc'			=> __( 'Orders with risk score equal to or higher than this number will be set on hold.', 'woocommerce-fraudlabs-pro' ),
				'id'			=> 'wc_settings_' . $this->namespace . '_hold_score',
				'default'		=> '70',
				'desc'			=> __('(Deprecated soon) <a href="http://www.fraudlabspro.com/tutorials/woocommerce-plugin-cancel-score-and-on-hold-score-deprecated?utm_source=module&utm_medium=banner&utm_term=woocommerce&utm_campaign=module%20banner" target="_blank">Learn More</a>'),
			),
			'test_ip' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_test_ip',
				'name'			=> __( 'Test IP', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'text',
				'desc'			=> __( 'Simulate visitor IP. Clear this value for production run.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> '',
				'css'			=> 'width:50%',
			),
			'store_admin_email' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_store_admin_email',
				'name'			=> __( 'Store Admin Email Address', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'text',
				'desc'			=> __( 'Inserts your email address to receive fraud check result in your inbox.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> '',
				'css'			=> 'width:50%',
			),
			'receive_report' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_receive_report',
				'name'			=> __( 'Receive Fraud Report', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'checkbox',
				'desc'			=> __( 'Enable or disable fraud report for each transaction by email.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> 'yes'
			),
			'approve_status'  => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_approve_status',
				'name'			=> __( 'Approve Status', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'select',
				'options'		=> array(
									'' => 'No Status Change',
									'wc-pending'    => 'Pending Payment',
									'wc-processing' => 'Processing',
									'wc-on-hold'    => 'On Hold',
									'wc-completed'  => 'Completed',
									'wc-cancelled'  => 'Cancelled',
									'wc-refunded'   => 'Refunded',
									'wc-failed'     => 'Failed',
								),
				'desc'			=> __( 'Change order status when Approve button is pressed or order is approved by FraudLabs Pro.', 'woocommerce-fraudlabs-pro' ),
				'id'			=> 'wc_settings_' . $this->namespace . '_approve_status',
				'default'		=> '',
				'desc_tip'		=> true
			),
			'review_status'  => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_review_status',
				'name'			=> __( 'Review Status', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'select',
				'options'		=> array(
									'' => 'No Status Change',
									'wc-pending'    => 'Pending Payment',
									'wc-processing' => 'Processing',
									'wc-on-hold'    => 'On Hold',
									'wc-completed'  => 'Completed',
									'wc-cancelled'  => 'Cancelled',
									'wc-refunded'   => 'Refunded',
									'wc-failed'     => 'Failed',
								),
				'desc'			=> __( 'Change order status when order is marked as review by FraudLabs Pro.', 'woocommerce-fraudlabs-pro' ),
				'id'			=> 'wc_settings_' . $this->namespace . '_review_status',
				'default'		=> '',
				'desc_tip'		=> true
			),
			'reject_status'  => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_reject_status',
				'name'			=> __( 'Reject Status', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'select',
				'options'		=> array(
									'' => 'No Status Change',
									'wc-pending'    => 'Pending Payment',
									'wc-processing' => 'Processing',
									'wc-on-hold'    => 'On Hold',
									'wc-completed'  => 'Completed',
									'wc-cancelled'  => 'Cancelled',
									'wc-refunded'   => 'Refunded',
									'wc-failed'     => 'Failed',
								),
				'desc'			=> __( 'Change order status when Reject button is pressed or order is rejected by FraudLabs Pro.', 'woocommerce-fraudlabs-pro' ),
				'id'			=> 'wc_settings_' . $this->namespace . '_reject_status',
				'default'		=> '',
				'desc_tip'		=> true
			),
			'sms' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_sms',
				'name'			=> __( 'SMS Verification Enabled', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'checkbox',
				'desc'			=> __( 'Enable or disable SMS verification.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> 'no'
			),
			'sms_retries' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_sms_retries',
				'name'			=> __( 'Max SMS Retries', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'text',
				'desc'			=> __( 'Maximum number of retries allowed to send SMS.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> '3',
				'css'			=> 'width:50%',
			),
			'sms_template' => array(
				'id'			=> 'wc_settings_' . $this->namespace . '_sms_template',
				'name'			=> __( 'SMS Template', 'woocommerce-fraudlabs-pro' ),
				'type'			=> 'text',
				'desc'			=> __( 'Template for SMS. Use {otp} for the generated OTP.', 'woocommerce-fraudlabs-pro' ),
				'desc_tip'		=> true,
				'default'		=> 'Hi, your OTP is {otp}.',
				'css'			=> 'width:50%',
			),
			'settings_section_end' => array(
				'type'			=> 'sectionend',
			),
		);

		return apply_filters( 'wc_settings_tab_' . $this->namespace, $setting_fields );
	}


	/**
	 * Add tab into settting page.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs[$this->namespace] = __( 'FraudLabs Pro', 'woocommerce-fraudlabs-pro' );

		return $settings_tabs;
	}


	/**
	 * Add fields into tab content.
	 */
	public function tab_content() {
		woocommerce_admin_fields( $this->get_fields() );
	}


	/**
	 * Get setting value by key.
	 */
	public function get_setting( $key ) {
		$fields = $this->get_fields();

		return apply_filters( 'wc_option_' . $key, get_option( 'wc_settings_' . $this->namespace . '_' . $key, ( ( isset( $fields[$key] ) && isset( $fields[$key]['default'] ) ) ? $fields[$key]['default'] : '' ) ) );
	}


	/**
	 * Update settings into WooCommerce
	 */
	public function update_settings() {
		woocommerce_update_options( $this->get_fields() );
	}

	/**
	 * Enqueue the script in dashboard.
	 */
	function plugin_enqueues() {
		if ( is_admin() && get_user_meta( get_current_user_id(), 'fraudlabspro_woocommerce_admin_notice', true ) !== 'dismissed' ) {

			wp_enqueue_script( 'fraudlabspro_woocommerce_admin_script', plugins_url( '/js/notice-update.js', dirname(__FILE__) ), array( 'jquery' ), '1.0', true );

			wp_localize_script( 'fraudlabspro_woocommerce_admin_script', 'fraudlabspro_woocommerce_admin', array( 'fraudlabspro_woocommerce_admin_nonce' => wp_create_nonce( 'fraudlabspro_woocommerce_admin_nonce' ), ));
		}
	}

	/**
	 * Add notification in dashboard.
	 */
	public function admin_notifications() {
		if ( get_user_meta( get_current_user_id(), 'fraudlabspro_woocommerce_admin_notice', true ) === 'dismissed' ) {
			return;
		}

		$currentscr = get_current_screen();

		if( 'plugins' == $currentscr->parent_base ) {
			if( ! $this->api_key ) {
				$settings_url = admin_url( 'admin.php?page=wc-settings&tab=woocommerce-fraudlabs-pro' );

				echo '
				<div id="fraudlabspro-woocommerce-notice" class="error notice is-dismissible">
					<p>
						' . __( 'FraudLabs Pro setup is not complete. Please go to <a href="' . $settings_url . '">setting page</a> to enter your API key.', 'woocommerce-fraudlabs-pro' ) . '
					</p>
				</div>
				';
			}
		}
	}

	/**
	 *	Dismiss the admin notice.
	 */
	function plugin_dismiss_admin_notice() {
		if ( ! isset( $_POST['fraudlabspro_woocommerce_admin_nonce'] ) || ! wp_verify_nonce( $_POST['fraudlabspro_woocommerce_admin_nonce'], 'fraudlabspro_woocommerce_admin_nonce' ) ) {
			wp_die();
		}

		update_user_meta( get_current_user_id(), 'fraudlabspro_woocommerce_admin_notice', 'dismissed' );
	}

	/**
	 * Proceess order with FraudLabs Pro API service.
	 */
	public function check_order( $order_id ) {
		if ( ! $this->enabled ) {
			return;
		}

		$order = wc_get_order( $order_id );
		//$billing_address = $order->get_address( 'billing' );
		//$shipping_address = $order->get_address( 'shipping' );
		$payment_gateway = wc_get_payment_gateway_by_order( $order );
		$items = $order->get_items();

		$qty = 0;

		foreach( $items as $key => $value ) {
			$qty += $value['qty'];
		}

		switch( $payment_gateway->id ) {
			case 'stripe':
				$paymentMode = 'creditcard';
				break;

			case 'bacs':
				$paymentMode = 'bankdeposit';
				break;

			case 'paypal':
				$paymentMode = 'paypal';
				break;

			default:
				$paymentMode = 'others';
		}

		/*$client_ip = $_SERVER['REMOTE_ADDR'];

		if( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && filter_var( $_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP ) )
			$client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];*/

		$url = 'https://api.fraudlabspro.com/v1/order/screen?' . http_build_query( array(
			'key'				=> $this->api_key ,
			'format'			=> 'json',
			'ip'				=> ( filter_var( $this->test_ip, FILTER_VALIDATE_IP ) ) ? $this->test_ip : $order->customer_ip_address,
			'first_name'		=> $order->billing_first_name,
			'last_name'			=> $order->billing_last_name,
			'bill_city'			=> $order->billing_city,
			'bill_state'		=> $order->billing_state,
			'bill_zip_code'		=> $order->billing_postcode,
			'bill_country'		=> $order->billing_country,
			'user_phone'		=> $order->billing_phone,
			'ship_addr'			=> trim($order->billing_address_1 . ' ' . $order->billing_address_2),
			'ship_city'			=> $order->shipping_city,
			'ship_state'		=> $order->shipping_state,
			'ship_zip_code'		=> $order->shipping_postcode,
			'ship_country'		=> $order->shipping_country,
			'email'				=> $order->billing_email,
			'email_domain'		=> substr( $order->billing_email, strpos( $order->billing_email, '@' ) + 1 ) ,
			'email_hash'		=> $this->hash_string( $order->billing_email ) ,
			'user_order_id'		=> $order->get_order_number(),
			'amount'			=> $order->get_total(),
			'quantity'			=> $qty,
			'currency'			=> $order->get_order_currency(),
			'payment_mode'		=> $paymentMode,
			'flp_checksum'		=> ( isset( $_COOKIE['flp_checksum'] ) ) ? $_COOKIE['flp_checksum'] : '',
			'source'			=> 'woocommerce',
			'source_version'	=> '2.8.14',
		) );

		$request = wp_remote_get( $url );

		// Network error, wait 2 seconds for next retry
		if ( is_wp_error( $request ) ) {
			for ( $i = 0; $i < 3; $i++ ) {
				sleep( 2 );

				$request = wp_remote_get( $url );

				if ( !is_wp_error( $request ) ) {
					break;
				}
			}
		}

		// Still having network issue after 3 retries, give up fraud check
		if ( is_wp_error( $request ) ) {
			$order->add_order_note( __( 'Fraud check skipped. Error: [ ' . $request->get_error_message() . ']', 'woocommerce-fraudlabs-pro' ) );
			return;
		}

		// Get the HTTP response
		$response = json_decode( wp_remote_retrieve_body( $request ) );

		// Make sure response is an object
		if ( ! is_object( $response ) ) {
			$order->add_order_note( __( 'Fraud check skipped due network issue.', 'woocommerce-fraudlabs-pro' ) );
			return;
		}

		// Save fraud check result
		add_post_meta( $order_id, '_fraudlabspro', json_encode( array(
			'order_id'						=> $order_id,
			'is_country_match'				=> $response->is_country_match,
			'is_high_risk_country'			=> $response->is_high_risk_country,
			'distance_in_km'				=> $response->distance_in_km,
			'distance_in_mile'				=> $response->distance_in_mile,
			'ip_address'					=> ( filter_var( $this->test_ip, FILTER_VALIDATE_IP ) ) ? $this->test_ip : $order->customer_ip_address,
			'ip_country'					=> $response->ip_country,
			'ip_region'						=> $response->ip_region,
			'ip_city'						=> $response->ip_city,
			'ip_continent'					=> $response->ip_continent,
			'ip_latitude'					=> $response->ip_latitude,
			'ip_longitude'					=> $response->ip_longitude,
			'ip_timezone'					=> $response->ip_timezone,
			'ip_elevation'					=> $response->ip_elevation,
			'ip_domain'						=> $response->ip_domain,
			'ip_mobile_mnc'					=> $response->ip_mobile_mnc,
			'ip_mobile_mcc'					=> $response->ip_mobile_mcc,
			'ip_mobile_brand'				=> $response->ip_mobile_brand,
			'ip_netspeed'					=> $response->ip_netspeed,
			'ip_isp_name'					=> $response->ip_isp_name,
			'ip_usage_type'					=> $response->ip_usage_type,
			'is_free_email'					=> $response->is_free_email,
			'is_new_domain_name'			=> $response->is_new_domain_name,
			'is_proxy_ip_address'			=> $response->is_proxy_ip_address,
			'is_bin_found'					=> $response->is_bin_found,
			'is_bin_country_match'			=> $response->is_bin_country_match,
			'is_bin_name_match'				=> $response->is_bin_name_match,
			'is_bin_phone_match'			=> $response->is_bin_phone_match,
			'is_bin_prepaid'				=> $response->is_bin_prepaid,
			'is_address_ship_forward'		=> $response->is_address_ship_forward,
			'is_bill_ship_city_match'		=> $response->is_bill_ship_city_match,
			'is_bill_ship_state_match'		=> $response->is_bill_ship_state_match,
			'is_bill_ship_country_match'	=> $response->is_bill_ship_country_match,
			'is_bill_ship_postal_match'		=> $response->is_bill_ship_postal_match,
			'is_ip_blacklist'				=> $response->is_ip_blacklist,
			'is_email_blacklist'			=> $response->is_email_blacklist,
			'is_credit_card_blacklist'		=> $response->is_credit_card_blacklist,
			'is_device_blacklist'			=> $response->is_device_blacklist,
			'is_user_blacklist'				=> $response->is_user_blacklist,
			'fraudlabspro_score'			=> $response->fraudlabspro_score,
			'fraudlabspro_distribution'		=> $response->fraudlabspro_distribution,
			'fraudlabspro_status'			=> $response->fraudlabspro_status,
			'fraudlabspro_id'				=> $response->fraudlabspro_id,
			'fraudlabspro_error_code'		=> $response->fraudlabspro_error_code,
			'fraudlabspro_message'			=> $response->fraudlabspro_message,
			'fraudlabspro_credits'			=> $response->fraudlabspro_credits,
			'api_key'						=> $this->api_key,
		) ) );

		if ( filter_var( $this->store_admin_email,  FILTER_VALIDATE_EMAIL ) && $this->receive_report ) {
			$location = array();

			if( strlen( $response->ip_country ) == 2 ) {
				$location = array(
					$this->fix_case( $response->ip_continent ),
					$this->get_country_by_code( $response->ip_country ),
					$this->fix_case( $response->ip_region ),
					$this->fix_case( $response->ip_city )
				);

				$location = array_unique( $location );
			}

			$message = array(
				'Your WooCommerce Order #' . $order_id . ' has been processed by FraudLabs Pro fraud prevention service.',
				'',
				'Please review the result:',
				'',
				'Is Country Match           : ' . $this->parse_fraud_result( $response->is_country_match ),
				'Is High Risk Country       : ' . $this->parse_fraud_result( $response->is_high_risk_country ),
				'Distance                   : ' . $response->distance_in_km . ' KM / ' . $response->distance_in_mile . ' Miles',
				'IP Address                 : ' . (( filter_var( $this->test_ip, FILTER_VALIDATE_IP ) ) ? $this->test_ip : $order->customer_ip_address),
				'ISP Name                   : ' . $response->ip_isp_name,
				'Location                   : ' . implode( ', ', $location ),
				'Using Free Email           : ' . $this->parse_fraud_result( $response->is_free_email ),
				'Using Proxy Server         : ' . $this->parse_fraud_result( $response->is_proxy_ip_address ),
				'Using Address Forwarder    : ' . $this->parse_fraud_result( $response->is_address_ship_forward ),
				'Is IP Blacklisted          : ' . $this->parse_fraud_result( $response->is_ip_blacklist ),
				'Is Email Blacklisted       : ' . $this->parse_fraud_result( $response->is_email_blacklist ),
				'Fraud Score                : ' . $response->fraudlabspro_score,
				'FraudLabs Pro Status       : ' . $response->fraudlabspro_status,
				'FraudLabs Pro Message      : ' . $response->fraudlabspro_message,
				'Credits Left               : ' . $response->fraudlabspro_credits,
				'Transaction Details        : https://www.fraudlabspro.com/merchant/transaction-details/' . $response->fraudlabspro_id,
				'',
				'',
				'Regards,',
				'FraudLabs Pro',
				'www.fraudlabspro.com',
			);

			wp_mail( $this->store_admin_email, '[#' . $order_id . '] FraudLabs Pro Result (Score: ' . $response->fraudlabspro_score . ')', implode( "\n", $message ) );
		}

		if ( $response->fraudlabspro_score >= $this->cancel_score ) {
			$order->add_order_note( __( 'Risk Score is higher than Cancel Score (' . $this->cancel_score . ').', 'woocommerce-fraudlabs-pro' ) );
			$order->update_status( 'wc-cancelled', __( '', 'woocommerce-fraudlabs-pro' ) );
		}
		elseif ( $response->fraudlabspro_status == 'REJECT' ) {
			$order->add_order_note( __( 'FraudLabs Pro rejected this order.', 'woocommerce-fraudlabs-pro' ) );

			if ( $this->reject_status ) {
				$order->update_status( $this->reject_status, __( '', 'woocommerce-fraudlabs-pro' ) );
			}
		}
		elseif ( $response->fraudlabspro_score >= $this->hold_score ) {
			$order->add_order_note( __( 'Risk Score is higher than On-hold Score (' . $this->hold_score . ').', 'woocommerce-fraudlabs-pro' ) );
			$order->update_status( 'wc-on-hold', __( '', 'woocommerce-fraudlabs-pro' ) );
		}
		elseif ( $response->fraudlabspro_status == 'REVIEW' ) {
			$order->add_order_note( __( 'FraudLabs Pro marked this order for review.', 'woocommerce-fraudlabs-pro' ) );

			if ( $this->review_status ) {
				$order->update_status( $this->review_status, __( '', 'woocommerce-fraudlabs-pro' ) );
			}
		}
		elseif ( $response->fraudlabspro_status == 'APPROVE' ) {
			$order->add_order_note( __( 'FraudLabs Pro approved this order.', 'woocommerce-fraudlabs-pro' ) );

			if ( $this->approve_status ) {
				$order->update_status( $this->approve_status, __( '', 'woocommerce-fraudlabs-pro' ) );
			}
		}

	}

	/**
	 * Proceess failed order with FraudLabs Pro API service.
	 */
	public function order_payment_failed( $order_id ) {
		if ( ! $this->enabled ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$payment_gateway = wc_get_payment_gateway_by_order( $order );
		$items = $order->get_items();

		$qty = 0;

		foreach( $items as $key => $value ) {
			$qty += $value['qty'];
		}

		switch( $payment_gateway->id ) {
			case 'stripe':
				$paymentMode = 'creditcard';
				break;

			case 'bacs':
				$paymentMode = 'bankdeposit';
				break;

			case 'paypal':
				$paymentMode = 'paypal';
				break;

			default:
				$paymentMode = 'others';
		}

		$url = 'https://api.fraudlabspro.com/v1/order/screen?' . http_build_query( array(
			'key'				=> $this->api_key ,
			'format'			=> 'json',
			'ip'				=> ( filter_var( $this->test_ip, FILTER_VALIDATE_IP ) ) ? $this->test_ip : $order->customer_ip_address,
			'first_name'		=> $order->billing_first_name,
			'last_name'			=> $order->billing_last_name,
			'bill_city'			=> $order->billing_city,
			'bill_state'		=> $order->billing_state,
			'bill_zip_code'		=> $order->billing_postcode,
			'bill_country'		=> $order->billing_country,
			'user_phone'		=> $order->billing_phone,
			'ship_addr'			=> trim($order->billing_address_1 . ' ' . $order->billing_address_2),
			'ship_city'			=> $order->shipping_city,
			'ship_state'		=> $order->shipping_state,
			'ship_zip_code'		=> $order->shipping_postcode,
			'ship_country'		=> $order->shipping_country,
			'email'				=> $order->billing_email,
			'email_domain'		=> substr( $order->billing_email, strpos( $order->billing_email, '@' ) + 1 ) ,
			'email_hash'		=> $this->hash_string( $order->billing_email ) ,
			'user_order_id'		=> $order->get_order_number(),
			'amount'			=> $order->get_total(),
			'quantity'			=> $qty,
			'currency'			=> $order->get_order_currency(),
			'payment_mode'		=> $paymentMode,
			'flp_checksum'		=> ( isset( $_COOKIE['flp_checksum'] ) ) ? $_COOKIE['flp_checksum'] : '',
			'source'			=> 'woocommerce',
			'source_version'	=> '2.8.14',
		) );

		$request = wp_remote_get( $url );

		// Network error, wait 2 seconds for next retry
		if ( is_wp_error( $request ) ) {
			for ( $i = 0; $i < 3; $i++ ) {
				sleep( 2 );

				$request = wp_remote_get( $url );

				if ( !is_wp_error( $request ) ) {
					break;
				}
			}
		}

		// Still having network issue after 3 retries, give up fraud check
		if ( is_wp_error( $request ) ) {
			$order->add_order_note( __( 'Fraud check skipped due network issue.', 'woocommerce-fraudlabs-pro' ) );
			return;
		}

		// Get the HTTP response
		$response = json_decode( wp_remote_retrieve_body( $request ) );

		// Make sure response is an object
		if ( ! is_object( $response ) ) {
			$order->add_order_note( __( 'Fraud check skipped due network issue.', 'woocommerce-fraudlabs-pro' ) );
			return;
		}

		// Save fraud check result
		add_post_meta( $order_id, '_fraudlabspro', json_encode( array(
			'order_id'						=> $order_id,
			'is_country_match'				=> $response->is_country_match,
			'is_high_risk_country'			=> $response->is_high_risk_country,
			'distance_in_km'				=> $response->distance_in_km,
			'distance_in_mile'				=> $response->distance_in_mile,
			'ip_address'					=> ( filter_var( $this->test_ip, FILTER_VALIDATE_IP ) ) ? $this->test_ip : $order->customer_ip_address,
			'ip_country'					=> $response->ip_country,
			'ip_region'						=> $response->ip_region,
			'ip_city'						=> $response->ip_city,
			'ip_continent'					=> $response->ip_continent,
			'ip_latitude'					=> $response->ip_latitude,
			'ip_longitude'					=> $response->ip_longitude,
			'ip_timezone'					=> $response->ip_timezone,
			'ip_elevation'					=> $response->ip_elevation,
			'ip_domain'						=> $response->ip_domain,
			'ip_mobile_mnc'					=> $response->ip_mobile_mnc,
			'ip_mobile_mcc'					=> $response->ip_mobile_mcc,
			'ip_mobile_brand'				=> $response->ip_mobile_brand,
			'ip_netspeed'					=> $response->ip_netspeed,
			'ip_isp_name'					=> $response->ip_isp_name,
			'ip_usage_type'					=> $response->ip_usage_type,
			'is_free_email'					=> $response->is_free_email,
			'is_new_domain_name'			=> $response->is_new_domain_name,
			'is_proxy_ip_address'			=> $response->is_proxy_ip_address,
			'is_bin_found'					=> $response->is_bin_found,
			'is_bin_country_match'			=> $response->is_bin_country_match,
			'is_bin_name_match'				=> $response->is_bin_name_match,
			'is_bin_phone_match'			=> $response->is_bin_phone_match,
			'is_bin_prepaid'				=> $response->is_bin_prepaid,
			'is_address_ship_forward'		=> $response->is_address_ship_forward,
			'is_bill_ship_city_match'		=> $response->is_bill_ship_city_match,
			'is_bill_ship_state_match'		=> $response->is_bill_ship_state_match,
			'is_bill_ship_country_match'	=> $response->is_bill_ship_country_match,
			'is_bill_ship_postal_match'		=> $response->is_bill_ship_postal_match,
			'is_ip_blacklist'				=> $response->is_ip_blacklist,
			'is_email_blacklist'			=> $response->is_email_blacklist,
			'is_credit_card_blacklist'		=> $response->is_credit_card_blacklist,
			'is_device_blacklist'			=> $response->is_device_blacklist,
			'is_user_blacklist'				=> $response->is_user_blacklist,
			'fraudlabspro_score'			=> $response->fraudlabspro_score,
			'fraudlabspro_distribution'		=> $response->fraudlabspro_distribution,
			'fraudlabspro_status'			=> $response->fraudlabspro_status,
			'fraudlabspro_id'				=> $response->fraudlabspro_id,
			'fraudlabspro_error_code'		=> $response->fraudlabspro_error_code,
			'fraudlabspro_message'			=> $response->fraudlabspro_message,
			'fraudlabspro_credits'			=> $response->fraudlabspro_credits,
			'api_key'						=> $this->api_key,
		) ) );

		if ( filter_var( $this->store_admin_email,  FILTER_VALIDATE_EMAIL ) && $this->receive_report ) {
			$location = array();

			if( strlen( $response->ip_country ) == 2 ) {
				$location = array(
					$this->fix_case( $response->ip_continent ),
					$this->get_country_by_code( $response->ip_country ),
					$this->fix_case( $response->ip_region ),
					$this->fix_case( $response->ip_city )
				);

				$location = array_unique( $location );
			}

			$message = array(
				'Your WooCommerce Order #' . $order_id . ' has been processed by FraudLabs Pro fraud prevention service.',
				'',
				'Please review the result:',
				'',
				'Is Country Match           : ' . $this->parse_fraud_result( $response->is_country_match ),
				'Is High Risk Country       : ' . $this->parse_fraud_result( $response->is_high_risk_country ),
				'Distance                   : ' . $response->distance_in_km . ' KM / ' . $response->distance_in_mile . ' Miles',
				'IP Address                 : ' . $client_ip,
				'ISP Name                   : ' . $response->ip_isp_name,
				'Location                   : ' . implode( ', ', $location ),
				'Using Free Email           : ' . $this->parse_fraud_result( $response->is_free_email ),
				'Using Proxy Server         : ' . $this->parse_fraud_result( $response->is_proxy_ip_address ),
				'Using Address Forwarder    : ' . $this->parse_fraud_result( $response->is_address_ship_forward ),
				'Is IP Blacklisted          : ' . $this->parse_fraud_result( $response->is_ip_blacklist ),
				'Is Email Blacklisted       : ' . $this->parse_fraud_result( $response->is_email_blacklist ),
				'Fraud Score                : ' . $response->fraudlabspro_score,
				'FraudLabs Pro Status       : ' . $response->fraudlabspro_status,
				'FraudLabs Pro Message      : ' . $response->fraudlabspro_message,
				'Credits Left               : ' . $response->fraudlabspro_credits,
				'Transaction Details        : https://www.fraudlabspro.com/merchant/transaction-details/' . $response->fraudlabspro_id,
				'',
				'',
				'Regards,',
				'FraudLabs Pro',
				'www.fraudlabspro.com',
			);

			wp_mail( $this->store_admin_email, '[#' . $order_id . '] FraudLabs Pro Result (Score: ' . $response->fraudlabspro_score . ')', implode( "\n", $message ) );
		}

		$result = get_post_meta( $order_id, '_fraudlabspro' );
		$row = json_decode( $result[0] );

		$requests = wp_remote_get( 'https://api.fraudlabspro.com/v1/order/feedback?' . http_build_query( array(
				'key'		=> $this->api_key,
				'action'	=> 'REJECT',
				'id'		=> $row->fraudlabspro_id,
				'format'	=> 'json'
			) ) );

		if ( ! is_wp_error( $requests ) ) {
			// Get the HTTP response
			$responses = json_decode( wp_remote_retrieve_body( $requests ) );

			if ( is_object( $responses ) ) {
				if( $responses->fraudlabspro_error_code == '' || $responses->fraudlabspro_error_code == '304' ) {
					$row->fraudlabspro_status = 'REJECT';
					update_post_meta( $order_id, '_fraudlabspro', json_encode( $row ) );
				}
			}
		}
	}


	/**
	 * Render fraud report into order details.
	 */
	public function render_fraud_report() {
		wp_enqueue_script( 'jquery' );

		if ( isset( $_POST['orderId'] ) ) {
			$order = wc_get_order( $_POST['orderId'] );
		}

		if ( isset( $_POST['approve'] ) ) {
			$request = wp_remote_get( 'https://api.fraudlabspro.com/v1/order/feedback?' . http_build_query( array(
				'key'		=> $this->api_key,
				'action'	=> 'APPROVE',
				'id'		=> $_POST['transactionId'],
				'format'	=> 'json'
			) ) );

			if ( ! is_wp_error( $request ) ) {
				// Get the HTTP response
				$response = json_decode( wp_remote_retrieve_body( $request ) );

				if ( is_object( $response ) ) {
					if( $response->fraudlabspro_error_code == '' || $response->fraudlabspro_error_code == '304' ) {
						$result = get_post_meta( $_GET['post'], '_fraudlabspro' );
						$row = json_decode( $result[0] );
						$row->fraudlabspro_status = 'APPROVE';
						update_post_meta( $_GET['post'], '_fraudlabspro', json_encode( $row ) );

						if( $this->approve_status ) {
							$order->add_order_note( __( 'FraudLabs Pro status changed from Review to Approved.', 'woocommerce-fraudlabs-pro' ) );
							$order->update_status( $this->approve_status, __( '', 'woocommerce-fraudlabs-pro' ) );

							echo '
							<script>window.location.href = window.location.href;</script>';
						}
						else{
							//only add the note
							$order->add_order_note( __( 'FraudLabs Pro status changed from Review to Approved.', 'woocommerce-fraudlabs-pro' ) );

							echo '
							<script>window.location.href = window.location.href;</script>';
						}
					}
				}
			}
		}

		if( isset( $_POST['reject'] ) ) {
			$request = wp_remote_get( 'https://api.fraudlabspro.com/v1/order/feedback?' . http_build_query( array(
				'key'		=> $this->api_key,
				'action'	=> 'REJECT',
				'id'		=> $_POST['transactionId'],
				'format'	=> 'json'
			) ) );

			if ( ! is_wp_error( $request ) ) {
				// Get the HTTP response
				$response = json_decode( wp_remote_retrieve_body( $request ) );

				if ( is_object( $response ) ) {
					if( $response->fraudlabspro_error_code == '' || $response->fraudlabspro_error_code == '304' ) {
						$result = get_post_meta( $_GET['post'], '_fraudlabspro' );
						$row = json_decode( $result[0] );
						$row->fraudlabspro_status = 'REJECT';
						update_post_meta( $_GET['post'], '_fraudlabspro', json_encode( $row ) );

						if( $this->reject_status ) {
							$order->add_order_note( __( 'FraudLabs Pro status changed from Review to Rejected.', 'woocommerce-fraudlabs-pro' ) );
							$order->update_status( $this->reject_status, __( '', 'woocommerce-fraudlabs-pro' ) );

							echo '
							<script>window.location.href = window.location.href;</script>';
						}
						else{
							//just add the note
							$order->add_order_note( __( 'FraudLabs Pro status changed from Review to Rejected.', 'woocommerce-fraudlabs-pro' ) );

							echo '
							<script>window.location.href = window.location.href;</script>';
						}
					}
				}
			}
		}

		$result = get_post_meta( $_GET['post'], '_fraudlabspro' );

		if( count( $result ) > 0 ) {
			$row = json_decode( $result[0] );
			$table = '
			<style type="text/css">
				.fraudlabspro {width:100%;}
				.fraudlabspro td{padding:10px 0; vertical-align:top}
				.flp-helper{text-decoration:none}

				/* color: Approve - #45b6af, Reject - #f3565d, Review - #dfba49 */
			</style>

			<table class="fraudlabspro">
				<tr>
					<td colspan="2" style="text-align:center; background-color:#ab1b1c; border:1px solid #ab1b1c; padding-top:10px; padding-bottom:10px;">
						<a href="http://www.fraudlabspro.com" target="_blank"><img src="https://www.fraudlabspro.com/images/logo_200.png" alt="FraudLabs Pro" /></a>
					</td>
				</tr>';

			$location = array();
			if( strlen( $row->ip_country ) == 2 ) {
				$location = array(
					$this->fix_case( $row->ip_continent ),
					$this->get_country_by_code( $row->ip_country ),
					$this->fix_case( $row->ip_region ),
					$this->fix_case( $row->ip_city )
				);
				$location = array_unique( $location );
			}

			switch( $row->fraudlabspro_status ) {
				case 'REVIEW':
					$fraudlabspro_status_display = "REVIEW";
					$color = 'dfba49';
					break;

				case 'REJECT':
					$fraudlabspro_status_display = "REJECTED";
					$color = 'f3565d';
					break;

				case 'APPROVE':
					$fraudlabspro_status_display = "APPROVED";
					$color = '45b6af';
					break;
			}

			$table .= '
				<tr>
					<td style="width:50%;">
						<b>FraudLabs Pro Score</b> <a href="javascript:;" class="flp-helper" title="Risk score, 0 (low risk) - 100 (high risk)."><span class="dashicons dashicons-editor-help"></span></a><br/>
						<img class="img-responsive" alt="" src="//fraudlabspro.hexa-soft.com/images/fraudscore/' . ( ( $row->fraudlabspro_score ) ? 'fraudlabsproscore' . $row->fraudlabspro_score . '.png' : 'nofraudprotection.png' ) . '" style="width:160px;" />
					</td>
					<td style="width:50%;">
						<b>FraudLabs Pro Status</b> <a href="javascript:;" class="flp-helper" title="FraudLabs Pro status."><span class="dashicons dashicons-editor-help"></span></a>
						<span style="color:#' . $color . ';font-size:28px; display:block;">' . $fraudlabspro_status_display . '</span>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<b>Transaction ID</b> <a href="javascript:;" class="flp-helper" title="Unique identifier for a transaction screened by FraudLabs Pro system."><span class="dashicons dashicons-editor-help"></span></a>
						<p><a href="http://www.fraudlabspro.com/merchant/transaction-details/' . $row->fraudlabspro_id . '" target="_blank">' . $row->fraudlabspro_id . '</a></p>
					</td>
				</tr>
				<tr>
					<td>
						<b>IP Address</b>
						<p>' . $row->ip_address . '</p>
					</td>
					<td>
						<b>IP Location</b> <a href="javascript:;" class="flp-helper" title="Location of the IP address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . implode( ', ', $location ) . ' <a href="http://www.geolocation.com/' . $row->ip_address . '" target="_blank">[Map]</a></p>
					</td>
				</tr>
				<tr>
					<td>
						<b>IP Net Speed</b> <a href="javascript:;" class="flp-helper" title="Connection speed."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->ip_netspeed . '</p>
					</td>
					<td>
						<b>IP ISP Name</b> <a href="javascript:;" class="flp-helper" title="ISP of the IP address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->ip_isp_name . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>IP Domain</b> <a href="javascript:;" class="flp-helper" title="Domain name of the IP address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->ip_domain . '</p>
					</td>
					<td>
						<b>IP Usage Type</b> <a href="javascript:;" class="flp-helper" title="Usage type of the IP address. E.g, ISP, Commercial, Residential."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . ( ($row->ip_usage_type == 'NA' ) ? 'Not available [<a href="http://www.fraudlabspro.com/plan" target="_blank">Upgrade</a>]' : $row->ip_usage_type ) . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>IP Time Zone</b> <a href="javascript:;" class="flp-helper" title="Time zone of the IP address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->ip_timezone . '</p>
					</td>
					<td>
						<b>IP Distance</b> <a href="javascript:;" class="flp-helper" title="Distance from IP address to Billing Location."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . ( ( $row->distance_in_km ) ? ( $row->distance_in_km . ' KM / ' . $row->distance_in_mile . ' Miles' ) : '-' ) . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>IP Latitude</b> <a href="javascript:;" class="flp-helper" title="Latitude of the IP address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->ip_latitude . '</p>
					</td>
					<td>
						<b>IP Longitude</b> <a href="javascript:;" class="flp-helper" title="Longitude of the IP address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->ip_longitude . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>High Risk Country</b> <a href="javascript:;" class="flp-helper" title="Whether IP address country is in the latest high risk country list."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_high_risk_country ) . '</p>
					</td>
					<td>
						<b>Free Email</b> <a href="javascript:;" class="flp-helper" title="Whether e-mail is from free e-mail provider."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_free_email ) . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>Ship Forward</b> <a href="javascript:;" class="flp-helper" title="Whether shipping address is a freight forwarder address."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_address_ship_forward ) . '</p>
					</td>
					<td>
						<b>Using Proxy</b> <a href="javascript:;" class="flp-helper" title="Whether IP address is from Anonymous Proxy Server."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_proxy_ip_address ) . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>BIN Found</b> <a href="javascript:;" class="flp-helper" title="Whether the BIN information matches our BIN list."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_bin_found ) . '</p>
					</td>
					<td>
						<b>Email Blacklist</b> <a href="javascript:;" class="flp-helper" title="Whether the email address is in our blacklist database."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_email_blacklist ) . '</p>
					</td>
				</tr>
				<tr>
					<td>
						<b>Credit Card Blacklist</b> <a href="javascript:;" class="flp-helper" title="Whether the credit card is in our blacklist database."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $this->parse_fraud_result( $row->is_credit_card_blacklist ) . '</p>
					</td>
					<td>
						<b>Balance</b> <a href="javascript:;" class="flp-helper" title="Balance of the credits available after this transaction."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . $row->fraudlabspro_credits . ' [<a href="http://www.fraudlabspro.com/plan" target="_blank">Upgrade</a>]</p>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<b>Message</b> <a href="javascript:;" class="flp-helper" title="FraudLabs Pro error message description."><span class="dashicons dashicons-editor-help"></span></a>
						<p>' . ( ( $row->fraudlabspro_message ) ? $row->fraudlabspro_error_code . ':' . $row->fraudlabspro_message : '-' ) . '</p>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<p>Please login to <a href="https://www.fraudlabspro.com/merchant/login" target="_blank">FraudLabs Pro Merchant Area</a> for more information about this order.</p>
					</td>
				</tr>
			</table>';

			if( $row->fraudlabspro_status == 'REVIEW' ) {
				$table .= '
				<form method="post">
					<p align="center">
					<input type="hidden" name="transactionId" value="' . $row->fraudlabspro_id . '" >
					<input type="hidden" name="orderId" value="' . $row->order_id . '" >
					<input type="submit" name="approve" id="approve-order" value="' . __( 'Approve', 'woocommerce-fraudlabs-pro' ) . '" style="padding:10px 5px; background:#22aa22; border:1px solid #ccc; min-width:100px; cursor: pointer;" />
					<input type="submit" name="reject" id="reject-order" value="' . __( 'Reject', 'woocommerce-fraudlabs-pro' ) . '" style="padding:10px 5px; background:#cd2122; border:1px solid #ccc; min-width:100px; cursor: pointer;" />
					</p>
				</form>';
			}
			echo '
			<script>
			jQuery(function(){
				jQuery("#woocommerce-order-items").before(\'<div class="metabox-holder"><div class="postbox"><h3>FraudLabs Pro Details</h3><blockquote>' . preg_replace( '/[\n]*/is', '', str_replace( '\'', '\\\'', $table ) ) . '</blockquote></div></div>\');
			});
			</script>';
		}
		else {
			echo '
			<script>
			jQuery(function(){
				jQuery("#woocommerce-order-items").before(\'<div class="metabox-holder"><div class="postbox"><h3>FraudLabs Pro Details</h3><blockquote>This order has not been screened by FraudLabs Pro.</blockquote></div></div>\');
			});
			</script>';
		}
	}


	/**
	 * Hash a string to send to FraudLabs Pro API.
	 */
	private function hash_string( $s ) {
		$hash = 'fraudlabspro_' . $s;

		for( $i = 0; $i < 65536; $i++ )
			$hash = sha1( 'fraudlabspro_' . $hash );

		return $hash;
	}


	/**
	 * Convert string into mix case.
	 */
	private function fix_case( $s ) {
		$s = ucwords( strtolower( $s ) );
		$s = preg_replace_callback( "/( [ a-zA-Z]{1}')([a-zA-Z0-9]{1})/s", create_function( '$matches', 'return $matches[1].strtoupper($matches[2]);' ) , $s );

		return $s;
	}


	/**
	 * Parse FraudLabs Pro API result.
	 */
	function parse_fraud_result( $result ) {
		if ( $result == 'Y' )
			return 'Yes';

		if ( $result == 'N' )
			return 'No';

		if ( $result == 'NA' )
			return '-';

		return $result;
	}


	/**
	 * Get country name by country code.
	 */
	function get_country_by_code( $code ) {
		$countries = array( 'AF' => 'Afghanistan','AL' => 'Albania','DZ' => 'Algeria','AS' => 'American Samoa','AD' => 'Andorra','AO' => 'Angola','AI' => 'Anguilla','AQ' => 'Antarctica','AG' => 'Antigua and Barbuda','AR' => 'Argentina','AM' => 'Armenia','AW' => 'Aruba','AU' => 'Australia','AT' => 'Austria','AZ' => 'Azerbaijan','BS' => 'Bahamas','BH' => 'Bahrain','BD' => 'Bangladesh','BB' => 'Barbados','BY' => 'Belarus','BE' => 'Belgium','BZ' => 'Belize','BJ' => 'Benin','BM' => 'Bermuda','BT' => 'Bhutan','BO' => 'Bolivia','BA' => 'Bosnia and Herzegovina','BW' => 'Botswana','BV' => 'Bouvet Island','BR' => 'Brazil','IO' => 'British Indian Ocean Territory','BN' => 'Brunei Darussalam','BG' => 'Bulgaria','BF' => 'Burkina Faso','BI' => 'Burundi','KH' => 'Cambodia','CM' => 'Cameroon','CA' => 'Canada','CV' => 'Cape Verde','KY' => 'Cayman Islands','CF' => 'Central African Republic','TD' => 'Chad','CL' => 'Chile','CN' => 'China','CX' => 'Christmas Island','CC' => 'Cocos (Keeling) Islands','CO' => 'Colombia','KM' => 'Comoros','CG' => 'Congo','CK' => 'Cook Islands','CR' => 'Costa Rica','CI' => 'Cote D\'Ivoire','HR' => 'Croatia','CU' => 'Cuba','CY' => 'Cyprus','CZ' => 'Czech Republic','CD' => 'Democratic Republic of Congo','DK' => 'Denmark','DJ' => 'Djibouti','DM' => 'Dominica','DO' => 'Dominican Republic','TP' => 'East Timor','EC' => 'Ecuador','EG' => 'Egypt','SV' => 'El Salvador','GQ' => 'Equatorial Guinea','ER' => 'Eritrea','EE' => 'Estonia','ET' => 'Ethiopia','FK' => 'Falkland Islands (Malvinas)','FO' => 'Faroe Islands','FJ' => 'Fiji','FI' => 'Finland','FR' => 'France','FX' => 'France, Metropolitan','GF' => 'French Guiana','PF' => 'French Polynesia','TF' => 'French Southern Territories','GA' => 'Gabon','GM' => 'Gambia','GE' => 'Georgia','DE' => 'Germany','GH' => 'Ghana','GI' => 'Gibraltar','GR' => 'Greece','GL' => 'Greenland','GD' => 'Grenada','GP' => 'Guadeloupe','GU' => 'Guam','GT' => 'Guatemala','GN' => 'Guinea','GW' => 'Guinea-bissau','GY' => 'Guyana','HT' => 'Haiti','HM' => 'Heard and Mc Donald Islands','HN' => 'Honduras','HK' => 'Hong Kong','HU' => 'Hungary','IS' => 'Iceland','IN' => 'India','ID' => 'Indonesia','IR' => 'Iran (Islamic Republic of)','IQ' => 'Iraq','IE' => 'Ireland','IL' => 'Israel','IT' => 'Italy','JM' => 'Jamaica','JP' => 'Japan','JO' => 'Jordan','KZ' => 'Kazakhstan','KE' => 'Kenya','KI' => 'Kiribati','KR' => 'Korea, Republic of','KW' => 'Kuwait','KG' => 'Kyrgyzstan','LA' => 'Lao People\'s Democratic Republic','LV' => 'Latvia','LB' => 'Lebanon','LS' => 'Lesotho','LR' => 'Liberia','LY' => 'Libyan Arab Jamahiriya','LI' => 'Liechtenstein','LT' => 'Lithuania','LU' => 'Luxembourg','MO' => 'Macau','MK' => 'Macedonia','MG' => 'Madagascar','MW' => 'Malawi','MY' => 'Malaysia','MV' => 'Maldives','ML' => 'Mali','MT' => 'Malta','MH' => 'Marshall Islands','MQ' => 'Martinique','MR' => 'Mauritania','MU' => 'Mauritius','YT' => 'Mayotte','MX' => 'Mexico','FM' => 'Micronesia, Federated States of','MD' => 'Moldova, Republic of','MC' => 'Monaco','MN' => 'Mongolia','MS' => 'Montserrat','MA' => 'Morocco','MZ' => 'Mozambique','MM' => 'Myanmar','NA' => 'Namibia','NR' => 'Nauru','NP' => 'Nepal','NL' => 'Netherlands','AN' => 'Netherlands Antilles','NC' => 'New Caledonia','NZ' => 'New Zealand','NI' => 'Nicaragua','NE' => 'Niger','NG' => 'Nigeria','NU' => 'Niue','NF' => 'Norfolk Island','KP' => 'North Korea','MP' => 'Northern Mariana Islands','NO' => 'Norway','OM' => 'Oman','PK' => 'Pakistan','PW' => 'Palau','PA' => 'Panama','PG' => 'Papua New Guinea','PY' => 'Paraguay','PE' => 'Peru','PH' => 'Philippines','PN' => 'Pitcairn','PL' => 'Poland','PT' => 'Portugal','PR' => 'Puerto Rico','QA' => 'Qatar','RE' => 'Reunion','RO' => 'Romania','RU' => 'Russian Federation','RW' => 'Rwanda','KN' => 'Saint Kitts and Nevis','LC' => 'Saint Lucia','VC' => 'Saint Vincent and the Grenadines','WS' => 'Samoa','SM' => 'San Marino','ST' => 'Sao Tome and Principe','SA' => 'Saudi Arabia','SN' => 'Senegal','SC' => 'Seychelles','SL' => 'Sierra Leone','SG' => 'Singapore','SK' => 'Slovak Republic','SI' => 'Slovenia','SB' => 'Solomon Islands','SO' => 'Somalia','ZA' => 'South Africa','GS' => 'South Georgia And The South Sandwich Islands','ES' => 'Spain','LK' => 'Sri Lanka','SH' => 'St. Helena','PM' => 'St. Pierre and Miquelon','SD' => 'Sudan','SR' => 'Suriname','SJ' => 'Svalbard and Jan Mayen Islands','SZ' => 'Swaziland','SE' => 'Sweden','CH' => 'Switzerland','SY' => 'Syrian Arab Republic','TW' => 'Taiwan','TJ' => 'Tajikistan','TZ' => 'Tanzania, United Republic of','TH' => 'Thailand','TG' => 'Togo','TK' => 'Tokelau','TO' => 'Tonga','TT' => 'Trinidad and Tobago','TN' => 'Tunisia','TR' => 'Turkey','TM' => 'Turkmenistan','TC' => 'Turks and Caicos Islands','TV' => 'Tuvalu','UG' => 'Uganda','UA' => 'Ukraine','AE' => 'United Arab Emirates','GB' => 'United Kingdom','US' => 'United States','UM' => 'United States Minor Outlying Islands','UY' => 'Uruguay','UZ' => 'Uzbekistan','VU' => 'Vanuatu','VA' => 'Vatican City State (Holy See)','VE' => 'Venezuela','VN' => 'Viet Nam','VG' => 'Virgin Islands (British)','VI' => 'Virgin Islands (U.S.)','WF' => 'Wallis and Futuna Islands','EH' => 'Western Sahara','YE' => 'Yemen','YU' => 'Yugoslavia','ZM' => 'Zambia','ZW' => 'Zimbabwe' );

		return ( isset( $countries[$code] ) ) ? $countries[$code] : NULL;
	}


	/**
	 * Show row meta on the plugin screen.
	 *
	 */
	public static function plugin_row_meta( $links, $file ) {
		if ( $file == 'fraudlabspro-woocommerce/fraudlabspro-woocommerce.php' ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'flp_docs_url', 'http://www.fraudlabspro.com/supported-platforms-woocommerce' ) ) . '" title="Documentation">Docs</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'flp_api_key_url', 'http://www.fraudlabspro.com/sign-up?r=woocommerce' ) ) . '" title="Register Free API Key">Register Free API Key</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return ( array ) $links;
	}

	public function add_column( $columns ) {
		$columns = array_merge( array_slice( $columns, 0, 5 ), array( 'fraudlabspro_score' => 'Risk Score' ), array_slice( $columns, 5 ) );

		return $columns;
	}

	public function render_column( $column ) {
		global $post;

		if ( 'fraudlabspro_score' == $column ) {

			$result = get_post_meta( $post->ID, '_fraudlabspro' );

			if ( count( $result ) > 0 ) {
				if ( is_null( $row = json_decode( $result[0] ) ) === FALSE ) {
					if ( $row->fraudlabspro_score > 80 ) {
						echo '<div style="color:#ff0000"><span class="dashicons dashicons-warning"></span> <strong>' . $row->fraudlabspro_score . '</strong></div>';
					}
					elseif ( $row->fraudlabspro_score > 60 ) {
						echo '<div style="color:#f0c850"><span class="dashicons dashicons-warning"></span> <strong>' . $row->fraudlabspro_score . '</strong></div>';
					}
					else {
						echo '<div style="color:#66cc00"><span class="dashicons dashicons-thumbs-up"></span> <strong>' . $row->fraudlabspro_score . '</strong></div>';
					}
				}
			}
		}
	}
}

endif;
