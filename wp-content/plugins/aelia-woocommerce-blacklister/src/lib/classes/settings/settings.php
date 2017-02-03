<?php
namespace Aelia\WC\Blacklister;
if(!defined('ABSPATH')) exit; // Exit ifaccessed directly

/**
 * Handles the settings for the Blacklister plugin and provides convenience
 * methods to read and write them.
 */
class Settings extends \Aelia\WC\Settings {
	/*** Settings Key ***/
	// @var string The key to identify plugin settings amongst WP options.
	const SETTINGS_KEY = 'wc_aelia_blacklister';

	/*** Settings fields ***/
	const FIELD_BLACKLISTED_EMAILS = 'blacklisted_emails';
	const FIELD_BLACKLISTED_IP_ADDRESSES = 'blacklisted_ip_addresses';

	const FIELD_BLACKLISTED_EMAIL_MESSAGE = 'blacklisted_email_message';
	const FIELD_BLACKLISTED_IP_MESSAGE = 'blacklisted_ip_message';

	/**
	 * Returns the default settings for the plugin. Used mainly at first
	 * installation.
	 *
	 * @param string key If specified, method will return only the setting identified
	 * by the key.
	 * @param mixed default The default value to return if the setting requested
	 * via the "key" argument is not found.
	 * @return array|mixed The default settings, or the value of the specified
	 * setting.
	 *
	 * @see WC_Aelia_Settings:default_settings().
	 */
	public function default_settings($key = null, $default = null) {
		// TODO Implement method
		$default_options = array(
			self::FIELD_BLACKLISTED_EMAILS => array(
			),
			self::FIELD_BLACKLISTED_IP_ADDRESSES => array(
			),
		);

		if(empty($key)) {
			return $default_options;
		}
		else {
			return get_value($key, $default_options, $default);
		}
	}

	/**
	 * Returns a list of Schedule options, retrieved from WordPress list.
	 *
	 * @return array An array of Schedule ID => Schedule Name pairs.
	 */
	public function get_schedule_options() {
		$wp_schedules = wp_get_schedules();
		uasort($wp_schedules, array($this, 'sort_schedules'));

		$result = array();
		foreach($wp_schedules as $schedule_id => $settings) {
			$result[$schedule_id] = $settings['display'];
		}
		return $result;
	}

	/**
	 * Returns an array containing the email addresses that have been blacklisted.
	 *
	 * @return array
	 */
	public function get_blacklisted_emails() {
		$blacklisted_emails = $this->current_settings(self::FIELD_BLACKLISTED_EMAILS);

		return $blacklisted_emails;
	}

	/**
	 * Returns an array containing the IP addresses that have been blacklisted.
	 *
	 * @return array
	 */
	public function get_blacklisted_ip_addresses() {
		$blacklisted_ip_addresses = $this->current_settings(self::FIELD_BLACKLISTED_IP_ADDRESSES);

		return $blacklisted_ip_addresses;
	}

	/**
	 * Callback method, used with uasort() function.
	 * Sorts WordPress Scheduling options by interval (ascending). In case of two
	 * identical intervals, it sorts them by label (comparison is case-insensitive).
	 *
	 * @param array a First Schedule Option.
	 * @param array b Second Schedule Option.
	 * @return int Zero if (a == b), -1 if (a < b), 1 if (a > b).
	 *
	 * @see uasort().
	 */
	public function sort_schedules($a, $b) {
		if($a['interval'] == $b['interval']) {
			return strcasecmp($a['display'], $b['display']);
		}

		return ($a['interval'] < $b['interval']) ? -1 : 1;
	}

	/**
	 * Validates the settings specified via the Options page.
	 *
	 * @param array settings An array of settings.
	 */
	public function validate_settings($settings) {
		// TODO Implement method
		//var_dump($settings);die();
		$processed_settings = $this->current_settings();

		$blacklisted_emails = get_value(self::FIELD_BLACKLISTED_EMAILS, $settings, array());
		$blacklisted_emails = $this->cleanup_entries($blacklisted_emails);
		$processed_settings[self::FIELD_BLACKLISTED_EMAILS] = $blacklisted_emails;

		$blacklisted_ip_addresses = get_value(self::FIELD_BLACKLISTED_IP_ADDRESSES, $settings, array());
		$blacklisted_ip_addresses = $this->cleanup_entries($blacklisted_ip_addresses);
		$processed_settings[self::FIELD_BLACKLISTED_IP_ADDRESSES] = $blacklisted_ip_addresses;

		// Validate messages to display when a user is blocked
		if($this->validate_messages($settings) === true) {
			$processed_settings[self::FIELD_BLACKLISTED_EMAIL_MESSAGE] = $settings[self::FIELD_BLACKLISTED_EMAIL_MESSAGE];
			$processed_settings[self::FIELD_BLACKLISTED_IP_MESSAGE] = $settings[self::FIELD_BLACKLISTED_IP_MESSAGE];
		}

		// Return the array processing any additional functions filtered by this action.
		return apply_filters('wc_aelia_blacklister_validate_settings', $processed_settings, $settings);
	}

	/**
	 * Class constructor.
	 */
	public function __construct($settings_key = self::SETTINGS_KEY,
															$textdomain = '',
															\Aelia\WC\Settings_Renderer $renderer = null) {
		if(empty($renderer)) {
			// Instantiate the render to be used to generate the settings page
			$renderer = new \Aelia\WC\Settings_Renderer();
		}
		parent::__construct($settings_key, $textdomain, $renderer);

		add_action('admin_init', array($this, 'init_settings'));

		// If no settings are registered, save the default ones
		if($this->load() === null) {
			$this->save();
		}
	}

	/**
	 * Factory method.
	 *
	 * @param string settings_key The key used to store and retrieve the plugin settings.
	 * @param string textdomain The text domain used for localisation.
	 * @param string renderer The renderer to use to generate the settings page.
	 * @return WC_Aelia_Settings.
	 */
	public static function factory($settings_key = self::SETTINGS_KEY,
																 $textdomain = '') {
		$class = get_called_class();
		$settings_manager = new $class($settings_key, $textdomain, $renderer);

		return $settings_manager;
	}

	/**
	 * Cleans up the list of blacklisted email addresses, removing duplicates and
	 * empty ones.
	 *
	 * @param string blacklisted_emails The list of emails entered by the user.
	 * @return string
	 */
	protected function cleanup_entries($entries) {
		$entries_list = explode("\n", str_replace("\r", '', $entries));
		$entries_list = array_unique(array_filter($entries_list, 'strlen'));

		return implode("\n", $entries_list);
	}

	/*** Validation methods ***/
	protected function validate_messages($settings) {
		$result = true;

		$field_error_messages = array(
			self::FIELD_BLACKLISTED_EMAIL_MESSAGE => __('Please enter an error message to be displayed when ' .
																									'visitor is blocked due to a blacklisted email address.',
																									$this->textdomain),
			self::FIELD_BLACKLISTED_IP_MESSAGE => __('Please enter an error message to be displayed when ' .
																							 'visitor is blocked due to a blacklisted IP address.',
																							 $this->textdomain),
		);

		foreach($field_error_messages as $field_id => $error_message) {
			$settings_message = trim(get_value($field_id, $settings));
			if(empty($settings_message)) {
				add_settings_error($this->_settings_key,
													 'invalid-' . $field_id,
													 $error_message);
				$result = false;
			}

			return $result;
		}
	}
}
