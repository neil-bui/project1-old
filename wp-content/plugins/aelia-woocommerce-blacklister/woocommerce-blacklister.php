<?php if(!defined('ABSPATH')) exit; // Exit if accessed directly
/*
Plugin Name: WooCommerce Blacklister
Description: Prevents visitors from placing orders, based on blacklisting criteria.
Author: Aelia (Diego Zanella)
Version: 0.8.6.150612
*/

require_once dirname(__FILE__) . '/src/lib/classes/install/aelia-wc-blacklister-requirementscheck.php';
// If requirements are not met, deactivate the plugin
if(Aelia_WC_Blacklister_RequirementsChecks::factory()->check_requirements()) {
	require_once dirname(__FILE__) . '/src/plugin-main.php';
}
