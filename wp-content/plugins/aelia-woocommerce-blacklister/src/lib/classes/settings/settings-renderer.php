<?php
namespace Aelia\WC\Blacklister;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements a class that will render the settings page.
 */
class Settings_Renderer extends \Aelia\WC\Settings_Renderer {
	// @var string The URL to the support portal.
	const SUPPORT_URL = 'http://aelia.freshdesk.com/support/home';
	// @var string The URL to the contact form for general enquiries.
	const CONTACT_URL = 'http://dev.pathtoenlightenment.net/contact/';

	/*** Settings Tabs ***/
	const TAB_BLACKLISTS = 'blacklists';
	const TAB_MESSAGES = 'messages';
	const TAB_SUPPORT = 'support';

	/*** Settings sections ***/
	const SECTION_BLACKLISTS = 'blacklists_section';
	const SECTION_MESSAGES = 'messages_section';
	const SECTION_SUPPORT = 'support_section';

	/**
	 * Sets the tabs to be used to render the Settings page.
	 */
	protected function add_settings_tabs() {
		// General settings
		$this->add_settings_tab($this->_settings_key,
														self::TAB_BLACKLISTS,
														__('Blacklists', $this->_textdomain));
		// Messages
		$this->add_settings_tab($this->_settings_key,
														self::TAB_MESSAGES,
														__('Messages', $this->_textdomain));
		// Support tab
		$this->add_settings_tab($this->_settings_key,
														self::TAB_SUPPORT,
														__('Support', $this->_textdomain));
	}

	/**
	 * Configures the plugin settings sections.
	 */
	protected function add_settings_sections() {
		// Add Blacklists section
		$this->add_settings_section(
				self::SECTION_BLACKLISTS,
				__('Blacklists', $this->_textdomain),
				array($this, 'blacklists_settings_section_callback'),
				$this->_settings_key,
				self::TAB_BLACKLISTS
		);

		// Add Messages section
		$this->add_settings_section(
				self::SECTION_MESSAGES,
				__('Messages', $this->_textdomain),
				array($this, 'messages_settings_section_callback'),
				$this->_settings_key,
				self::TAB_MESSAGES
		);

		// Add Support section
		$this->add_settings_section(
				self::SECTION_SUPPORT,
				__('Support Information', $this->_textdomain),
				array($this, 'support_section_callback'),
				$this->_settings_key,
				self::TAB_SUPPORT
		);
	}

	/**
	 * Configures the plugin settings fields.
	 */
	protected function add_settings_fields() {
		// Blacklists
		// Load currently blacklisted email addresses
		$blacklisted_emails = $this->_settings_controller->get_blacklisted_emails();

		// Add "Blacklisted emails" field
		$blacklisted_emails_field_id = Settings::FIELD_BLACKLISTED_EMAILS;
		// Prepare multi-select to allow choosing the Currencies to use
		add_settings_field(
			$blacklisted_emails_field_id,
			__('Blacklisted email addresses', $this->_textdomain) .
			'<p>' .
	    __('Enter the email addresses that you would like to blacklist (one per line). ' .
				 'You can also enter regular expressions by wrapping it with slashes. ' .
				 '<br />' .
				 '<span class="label">Example</span>: ' .
				 '<em>/some_email.*@domain[x|y|z]\.com/</em>',
				 $this->_textdomain) .
			'</p>',
	    array($this, 'render_textbox'),
	    $this->_settings_key,
	    self::SECTION_BLACKLISTS,
	    array(
				'settings_key' => $this->_settings_key,
				'id' => $blacklisted_emails_field_id,
				'label_for' => $blacklisted_emails_field_id,
				'value' => $blacklisted_emails,
				// Input field attributes
				'attributes' => array(
					'class' => 'blacklist ' . $blacklisted_emails_field_id,
					'multiline' => true,
					'rows' => 10,
					'cols' => 35,
				),
			)
		);
		// Load currently blacklisted email addresses
		$blacklisted_ip_addresses = $this->_settings_controller->get_blacklisted_ip_addresses();

		// Add "Blacklisted ip_addresses" field
		$blacklisted_ip_addresses_field_id = Settings::FIELD_BLACKLISTED_IP_ADDRESSES;
		// Prepare multi-select to allow choosing the Currencies to use
		add_settings_field(
			$blacklisted_ip_addresses_field_id,
			__('Blacklisted IP addresses', $this->_textdomain) .
			'<p>' .
	    __('Enter the IP addresses that you would like to blacklist (one per line). ' .
				 'You can also enter ranges as follows: ' .
				 '<ul>' .
				 '<li>CIDR: 123.123.123.0/24</li>' .
				 '<li>Wildcard: 123.123.123.*</li>' .
				 '<li>Range: 123.123.123.1-123.123.123.254</li>' .
				 '</ul>',
				 $this->_textdomain) .
			'</p>',
	    array($this, 'render_textbox'),
	    $this->_settings_key,
	    self::SECTION_BLACKLISTS,
	    array(
				'settings_key' => $this->_settings_key,
				'id' => $blacklisted_ip_addresses_field_id,
				'label_for' => $blacklisted_ip_addresses_field_id,
				'value' => $blacklisted_ip_addresses,
				// Input field attributes
				'attributes' => array(
					'class' => 'blacklist ' . $blacklisted_ip_addresses_field_id,
					'multiline' => true,
					'rows' => 10,
					'cols' => 35,
				),
			)
		);


		// Messages
		// Add "Message when email is blacklisted" field
		$blacklisted_email_message_field_id = Settings::FIELD_BLACKLISTED_EMAIL_MESSAGE;
		$current_message =
			$this->current_settings($blacklisted_email_message_field_id,
															__('Email address "' . Definitions::PLACEHOLDER_EMAIL . '" is blacklisted and cannot be used ' .
																 'place an order.', $this->_textdomain));

		// Prepare select to allow choosing how often to update the Exchange Rates
		add_settings_field(
			$blacklisted_email_message_field_id,
	    __('Blacklisted email address', $this->_textdomain) .
			'<p>' .
			__('This message is displayed when the billing email address entered by a visitor ' .
				 'is blacklisted. Use the <i>' . Definitions::PLACEHOLDER_EMAIL . '</i> placeholder to ' .
				 'display the blocked email address with the message.', $this->_textdomain) .
			'</p>',
	    array($this, 'render_textbox'),
	    $this->_settings_key,
	    self::SECTION_MESSAGES,
	    array(
				'settings_key' => $this->_settings_key,
				'id' => $blacklisted_email_message_field_id,
				'label_for' => $blacklisted_email_message_field_id,
				'value' => $current_message,
				// Input field attributes
				'attributes' => array(
					'class' => $blacklisted_email_message_field_id,
					'multiline' => true,
					'rows' => 5,
					'cols' => 45,
				),
			)
		);

		// Add "Message when IP is blacklisted" field
		$blacklisted_ip_address_field_id = Settings::FIELD_BLACKLISTED_IP_MESSAGE;
		$current_message =
			$this->current_settings($blacklisted_ip_address_field_id,
															__('IP address "' . Definitions::PLACEHOLDER_IP_ADDR .'" is blacklisted. It is not possible to ' .
																 'place an order from such IP address.', $this->_textdomain));

		// Prepare select to allow choosing how often to update the Exchange Rates
		add_settings_field(
			$blacklisted_ip_address_field_id,
	    __('Blacklisted IP  address', $this->_textdomain) .
			'<p>' .
			__('This message is displayed when the visitor\'s IP address ' .
				 'is blacklisted. Use the <i>' . Definitions::PLACEHOLDER_IP_ADDR . '</i> placeholder to ' .
				 'display the blocked IP address with the message.', $this->_textdomain) .
			'</p>',
	    array($this, 'render_textbox'),
	    $this->_settings_key,
	    self::SECTION_MESSAGES,
	    array(
				'settings_key' => $this->_settings_key,
				'id' => $blacklisted_ip_address_field_id,
				'label_for' => $blacklisted_ip_address_field_id,
				'value' => $current_message,
				// Input field attributes
				'attributes' => array(
					'class' => $blacklisted_ip_address_field_id,
					'multiline' => true,
					'rows' => 5,
					'cols' => 45,
				),
			)
		);
	}

	/**
	 * Returns the title for the menu item that will bring to the plugin's
	 * settings page.
	 *
	 * @return string
	 */
	protected function menu_title() {
		return __('Blacklister settings', $this->_textdomain);
	}

	/**
	 * Returns the slug for the menu item that will bring to the plugin's
	 * settings page.
	 *
	 * @return string
	 */
	protected function menu_slug() {
		return Definitions::MENU_SLUG;
	}

	/**
	 * Returns the title for the settings page.
	 *
	 * @return string
	 */
	protected function page_title() {
		return __('Blacklister settings', $this->_textdomain) .
					 sprintf('&nbsp;(v. %s)', WC_Blacklister_Plugin::$version);
	}

	/**
	 * Returns the description for the settings page.
	 *
	 * @return string
	 */
	protected function page_description() {
		return __('In this page you can configure the settings for the Blacklister. ' .
							'By entering some blacklisting criteria, visitors matching it will not ' .
							'be able to place orders. This will help reducing the amount of bogus ' .
							'orders placed by rogue users, as well as the amount of chargebacks ' .
							'and refunds caused by them.' .
							$this->_textdomain);
	}

	/*** Settings sections callbacks ***/
	public function blacklists_settings_section_callback() {
		echo __('In this section you can configure the blacklists that will prevent ' .
						'a visitor from placing an order.', $this->_textdomain);
	}

	public function messages_settings_section_callback() {
		echo __('In this section you can configure the messages that will be presented ' .
						'to a visitor when he has been blocked by one of the blacklists.', $this->_textdomain);
	}

	public function support_section_callback() {
		echo '<div class="support_information">';
		echo '<p>';
		echo __('We designed this plugin to be robust and effective, ' .
						'as well as intuitive and easy to use. However, we are aware that, despite ' .
						'all best efforts, issues can arise and that there is always room for ' .
						'improvement.',
						$this->_textdomain);
		echo '</p>';
		echo '<p>';
		echo __('Should you need assistance, or if you just would like to get in touch ' .
						'with us, please use one of the links below.',
						$this->_textdomain);
		echo '</p>';

		// Support links
		echo '<ul id="contact_links">';
		echo '<li>' . sprintf(__('<span class="label">To request support</span>, please use our <a href="%s">Support portal</a>. ' .
														 'The portal also contains a Knowledge Base, where you can find the ' .
														 'answers to the most common questions related to our products.',
														 $this->_textdomain),
													self::SUPPORT_URL) . '</li>';
		echo '<li>' . sprintf(__('<span class="label">To send us general feedback</span>, suggestions, or enquiries, please use ' .
														 'the <a href="%s">contact form on our website.</a>',
														 $this->_textdomain),
													self::CONTACT_URL) . '</li>';
		echo '</ul>';

		echo '</div>';
	}

	/*** Rendering methods ***/
}
