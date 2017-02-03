<?php
namespace Aelia\WC\Blacklister;
if(!defined('ABSPATH')) exit; // Exit ifaccessed directly

/**
 * Handles the settings for the Blacklister plugin and provides convenience
 * methods to read and write them.
 */
class Blacklist_Validator extends \Aelia\WC\Base_Class {
	protected $log_id = Definitions::PLUGIN_SLUG;
	protected $text_domain = Definitions::TEXT_DOMAIN;

	/**
	 * Adds an error to WooCommerce, so that it can be displayed to the customer.
	 *
	 * @param string error_message The error message to display.
	 */
	protected function add_woocommerce_error($error_message) {
		wc_add_notice($error_message, 'error');
	}

	/*
	 * Determines if an IP is located in a specific range as specified via several
	 * alternative formats.
	 *
	 * @param string ip The IP address to check against the range.
	 * Network ranges can be specified as:
	 * 1. Wildcard format: 1.2.3.*
	 * 2. CIDR format: 1.2.3/24  OR  1.2.3.4/255.255.255.0
	 * 3. Start-End IP format: 1.2.3.0-1.2.3.255
	 *
	 * @author Paul Gregg <pgregg@pgregg.com>
	 * @link http://www.pgregg.com/projects/php/ip_in_range/
	 */
	protected function ip_in_range($ip, $range) {
		if(strpos($range, '/') !== false) {
			// $Range is in IP/NETMASK format
			list($range, $netmask) = explode('/', $range, 2);
			if(strpos($netmask, '.') !== false) {
				// $netmask is a 255.255.0.0 format
				$netmask = str_replace('*', '0', $netmask);
				$netmask_dec = ip2long($netmask);

				return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
			}
			else {
				// $netmask is a CIDR size block
				// fix the range argument
				$x = explode('.', $range);
				while(count($x)<4) $x[] = '0';
				list($a,$b,$c,$d) = $x;
				$range = sprintf("%u.%u.%u.%u",
												 empty($a) ? '0' : $a,
												 empty($b) ? '0' : $b,
												 empty($c) ? '0' : $c,
												 empty($d) ? '0' : $d);
				$range_dec = ip2long($range);
				$ip_dec = ip2long($ip);

				// Use math to create the netmask
				$wildcard_dec = pow(2, (32-$netmask)) - 1;
				$netmask_dec = ~ $wildcard_dec;

				return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
			}
		}
		else {
			// range might be 255.255.*.* or 1.2.3.0-1.2.3.255
			if(strpos($range, '*') !== false) {
				// a.b.*.* format
				// Just convert to A-B format by setting * to 0 for A and 255 for B
				$lower = str_replace('*', '0', $range);
				$upper = str_replace('*', '255', $range);
				$range = "$lower-$upper";
			}

			if(strpos($range, '-')!== false) {
				// A-B format
				list($lower, $upper) = explode('-', $range, 2);
				$lower_dec = (float)sprintf("%u",ip2long($lower));
				$upper_dec = (float)sprintf("%u",ip2long($upper));
				$ip_dec = (float)sprintf("%u",ip2long($ip));

				return (($ip_dec>=$lower_dec) && ($ip_dec<=$upper_dec));
			}
		}

		$e = new Exception(sprintf(__('Invalid IP range specified: "%s".',
																	$this->text_domain),
															 $range));
		throw $e;
	}

	/**
	 * Returns the settings contoller used by the blacklisted plugin.
	 *
	 * @return WC_Aelia_Settings
	 */
	protected function settings() {
		return WC_Blacklister_Plugin::settings();
	}

	/**
	 * Returns a value indicating if the email address used to place an order has
	 * been blacklisted.
	 *
	 * @param string email The email address to validate.
	 * @return bool
	 */
	protected function email_blacklisted($email) {
		$this->log(sprintf(__('Checking email address "%s" against blacklist...',
													$this->text_domain),
											 $email));

		$email_is_blacklisted = false;
		$blacklisted_emails = explode("\n", $this->settings()->get_blacklisted_emails());

		foreach($blacklisted_emails as $blacklisted_email) {
			// Remove trailing comment and spaces, if any
			$blacklisted_email = trim(preg_replace('~//.*$~', '', $blacklisted_email));

			if(strpos($blacklisted_email, '/') === false) {
				$this->log(sprintf(__('Checking against blacklisted email address "%s"...',
															$this->text_domain),
													 $blacklisted_email),
									 true);

				// If email address does not contain slashes, it's considered full address,
				// to compare as is
				$email_is_blacklisted = (strcasecmp($email, $blacklisted_email) == 0);
			}
			else {
				$this->log(sprintf(__('Checking against regular expression "%s"...',
															$this->text_domain),
													 $blacklisted_email),
									 true);

				// If email address contains slashes, it's considered a regular expression
				$regex_result = preg_match($blacklisted_email, $email);

				// A result of FALSE indicates that the regular expression is not valid
				if($regex_result === false) {
					$this->log(sprintf(__('An error occurred while checking email address ' .
																'against regular expression "%s". Please make sure ' .
																'that the regular expression is valid.',
																$this->text_domain),
														 $blacklisted_email));
				}
				else {
					$email_is_blacklisted = $regex_result;
				}
			}

			if($email_is_blacklisted) {
				break;
			}
		}

		$result_msg = $email_is_blacklisted ? 'Passed.' : 'Blacklisted.';
		$this->log(__($result_msg, $this->text_domain));

		return $email_is_blacklisted;
	}

	/**
	 * Returns the visitor's IP address, handling the case in which a standard
	 * reverse proxy is used.
	 *
	 * @return string
	 */
	protected function get_visitor_ip_address() {
		$visitor_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		$visitor_ip = apply_filters('wc_aelia_blacklister_visitor_ip', $visitor_ip);
		return $visitor_ip;
	}

	/**
	 * Returns a value indicating if the IP address used to place an order has
	 * been blacklisted.
	 *
	 * @param string ip_address The IP address to validate.
	 * @return bool
	 */
	protected function ip_address_blacklisted($ip_address) {
		$this->log(sprintf(__('Checking IP address "%s" against blacklist...',
													$this->text_domain),
											 $ip_address));

		$ip_is_blacklisted = false;
		$blacklisted_ip_addresses = explode("\n", $this->settings()->get_blacklisted_ip_addresses());

		foreach($blacklisted_ip_addresses as $blacklisted_ip) {
			// Remove trailing comment and spaces, if any
			$blacklisted_ip = trim(preg_replace('~//.*$~', '', $blacklisted_ip));

			// TODO Handle IPv6
			if(preg_match('~[\*|\-|/]~', $blacklisted_ip) == 1) {
				// Blacklisted IP is a range
				try {
					$this->log(sprintf(__('Checking against blacklisted IP range "%s"...',
																$this->text_domain),
														 $blacklisted_ip),
										 true);

					$ip_is_blacklisted = $this->ip_in_range($ip_address, $blacklisted_ip);
				}
				catch(Exception $e) {
					$this->log($e->getMessage());
				}
			}
			else {
				$this->log(sprintf(__('Checking against blacklisted IP address "%s"...',
															$this->text_domain),
													 $blacklisted_ip),
									 true);
				// Blacklisted IP is a single address
				$ip_is_blacklisted = ($ip_address == $blacklisted_ip);
			}

			if($ip_is_blacklisted) {
				break;
			}
		}

		$result_msg = $ip_is_blacklisted ? 'Passed.' : 'Blacklisted.';
		$this->log(__($result_msg, $this->text_domain));

		return $ip_is_blacklisted;
	}

	/**
	 * Returns the error message to display to the visitor when the checkout is
	 * blocked by a blacklist.
	 *
	 * @param int error_code The error code indicating the reason why the checkout
	 * has been blocked.
	 * @return string
	 */
	protected function get_blocking_message($error_code) {
		$code_field_map = array(
			Messages::ERR_EMAIL_BLACKLISTED => Settings::FIELD_BLACKLISTED_EMAIL_MESSAGE,
			Messages::ERR_IP_BLACKLISTED => Settings::FIELD_BLACKLISTED_IP_MESSAGE,
		);

		$settings_field_id = get_value($error_code, $code_field_map);
		// If no valid message has been configured, take the default one
		$message = trim($this->settings()->get($settings_field_id));
		if(empty($message)) {
			$message = WC_Blacklister_Plugin::messages()->get_message($error_code);
		}

		return $message;
	}

	/**
	 * Validates the checkout to ensure that the visitor is not blacklisted.
	 *
	 * @param WC_Checkout checkout The instance of WC_Checkout passed by WooCommerce.
	 * @return bool
	 */
	public function validate_checkout(\WC_Checkout $checkout) {
		$result = true;

		// Check if email is blacklisted
		$billing_email = get_value('billing_email', $checkout->posted);
		if(!empty($billing_email) &&
			 $this->email_blacklisted($billing_email)) {

			$error_message = $this->get_blocking_message(Messages::ERR_EMAIL_BLACKLISTED);
			$error_message = str_replace(Definitions::PLACEHOLDER_EMAIL,
																	 $billing_email,
																	 $error_message);

			$this->add_woocommerce_error($error_message);
			$result = false;
		}

		// Check if IP address is blacklisted
		$visitor_ip_address = $this->get_visitor_ip_address();

		if($this->ip_address_blacklisted($visitor_ip_address)) {
			$error_message = $this->get_blocking_message(Messages::ERR_IP_BLACKLISTED);
			$error_message = str_replace(Definitions::PLACEHOLDER_IP_ADDR,
																	 $visitor_ip_address,
																	 $error_message);

			$this->add_woocommerce_error($error_message);
			$result = false;
		}
		return $result;
	}
}
