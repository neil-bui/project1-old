<?php
namespace Aelia\WC\Blacklister;
if(!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Implements a base class to store and handle the messages returned by the
 * plugin. This class is used to extend the basic functionalities provided by
 * standard WP_Error class.
 */
class Definitions {
	// @var string The menu slug for plugin's settings page.
	const MENU_SLUG = 'wc_aelia_blacklister';
	// @var string The plugin slug
	const PLUGIN_SLUG = 'wc-aelia-blacklister-plugin';
	// @var string The plugin text domain
	const TEXT_DOMAIN = 'wc-aelia-blacklister-plugin';

	const PLACEHOLDER_EMAIL = '{email_address}';
	const PLACEHOLDER_IP_ADDR = '{ip_address}';
}
