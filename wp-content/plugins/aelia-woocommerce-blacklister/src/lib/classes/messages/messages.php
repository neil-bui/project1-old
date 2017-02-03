<?php
namespace Aelia\WC\Blacklister;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

use \WP_Error;

/**
 * Implements a base class to store and handle the messages returned by the
 * plugin. This class is used to extend the basic functionalities provided by
 * standard WP_Error class.
 */
class Messages extends \Aelia\WC\Messages {
	// Error codes
	const ERR_EMAIL_BLACKLISTED = 1000;
	const ERR_IP_BLACKLISTED = 1001;
	const ERR_USER_BLACKLISTED = 1001;

	// @var string The text domain used by the class
	protected $_text_domain = Definitions::TEXT_DOMAIN;

	/**
	 * Loads all the error message used by the plugin. This class should be
	 * extended during implementation, to add all error messages used by
	 * the plugin.
	 */
	public function load_error_messages() {
		parent::load_error_messages();

		$this->add_error_message(self::ERR_EMAIL_BLACKLISTED,
														 __('Email address "' . Definitions::PLACEHOLDER_EMAIL . '" is blacklisted and cannot be used ' .
																'place an order.', $this->_text_domain));
		$this->add_error_message(self::ERR_IP_BLACKLISTED,
														 __('IP address "' . Definitions::PLACEHOLDER_IP_ADDR .'" is blacklisted. It is not possible to ' .
																'place an order from such IP address.', $this->_text_domain));

		// TODO Add here all the error messages used by the plugin
	}
}
