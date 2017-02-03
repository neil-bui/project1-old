<?php
namespace Aelia\WC\Blacklister;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

//define('SCRIPT_DEBUG', 1);
//error_reporting(E_ALL);

require_once('lib/classes/definitions/wc-aelia-blacklister-definitions.php');

use Aelia\WC\Aelia_Plugin;
use Aelia\WC\Aelia_SessionManager;
use Aelia\WC\IP2Location;
use Aelia\WC\Blacklister\Settings;
use Aelia\WC\Blacklister\Settings_Renderer;
use Aelia\WC\Blacklister\Messages;

/**
 * Main plugin class.
 **/
class WC_Blacklister_Plugin extends Aelia_Plugin {
	public static $version = '0.8.6.150612';

	public static $plugin_slug = Definitions::PLUGIN_SLUG;
	public static $text_domain = Definitions::TEXT_DOMAIN;
	public static $plugin_name = 'WooCommerce Blacklister';

	/**
	 * Factory method.
	 */
	public static function factory() {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		$settings_key = self::$plugin_slug;

		// Settings and messages classes are loaded from the same namespace as the
		// plugin
		$settings_page_renderer = new Settings_Renderer();
		$settings_controller = new Settings(Settings::SETTINGS_KEY,
																				self::$text_domain,
																				$settings_page_renderer);
		$messages_controller = new Messages();

		$class = get_called_class();
		$plugin_instance = new $class($settings_controller, $messages_controller);
		return $plugin_instance;
	}

	/**
	 * Constructor.
	 *
	 * @param \Aelia\WC\Settings settings_controller The controller that will handle
	 * the plugin settings.
	 * @param \Aelia\WC\Messages messages_controller The controller that will handle
	 * the messages produced by the plugin.
	 */
	public function __construct($settings_controller = null,
															$messages_controller = null) {
		// Load Composer autoloader
		require_once(__DIR__ . '/vendor/autoload.php');

		parent::__construct($settings_controller, $messages_controller);
	}

	/**
	 * Performs additional checks before the checkout process is initiated.
	 */
	public function woocommerce_after_checkout_validation() {
		// Check if user is blacklisted. If he is, prevent him from completing the order
		$this->validate_checkout();

		// Adding an error will stop the checkout process
		//$this->woocommerce()->add_error();
	}

	protected function validate_checkout() {
		global $woocommerce;
		$validator = new Blacklist_Validator();
		$validator->validate_checkout($woocommerce->checkout());
	}

	/**
	 * Sets the hooks required by the plugin.
	 */
	protected function set_hooks() {
		parent::set_hooks();

		add_action('woocommerce_after_checkout_validation', array($this, 'woocommerce_after_checkout_validation'));
	}

	/**
	 * Determines if one of plugin's admin pages is being rendered. Override it
	 * if plugin implements pages in the Admin section.
	 *
	 * @return bool
	 */
	protected function rendering_plugin_admin_page() {
		$screen = get_current_screen();
		$page_id = $screen->id;

		return ($page_id == 'woocommerce_page_' . Definitions::MENU_SLUG);
	}

	/**
	 * Registers the script and style files needed by the admin pages of the
	 * plugin. Extend in descendant plugins.
	 */
	protected function register_plugin_admin_scripts() {
		// Scripts
		wp_register_script('jquery-ui',
											 '//code.jquery.com/ui/1.10.3/jquery-ui.js',
											 array('jquery'),
											 null,
											 true);
		wp_register_script('chosen',
											 '//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.jquery.min.js',
											 array('jquery'),
											 null,
											 true);

		// Styles
		wp_register_style('chosen',
												'//cdnjs.cloudflare.com/ajax/libs/chosen/1.1.0/chosen.min.css',
												array(),
												null,
												'all');
		wp_register_style('jquery-ui',
											'//code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css',
											array(),
											null,
											'all');

		wp_enqueue_style('jquery-ui');
		wp_enqueue_style('chosen');

		wp_enqueue_script('jquery-ui');
		wp_enqueue_script('chosen');

		parent::register_plugin_admin_scripts();
	}
}

$GLOBALS[WC_Blacklister_Plugin::$plugin_slug] = WC_Blacklister_Plugin::factory();
