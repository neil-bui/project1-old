<?php
/**
 * Uninstall routine for the WGDR plugin
 */

// If uninstall is not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

$option_name_1 = 'wgdr_plugin_options_1';
$option_name_2 = 'wgdr_plugin_options_2';
$option_name_3 = 'wgdr_plugin_options_3';
$option_name_4 = 'wgdr_plugin_options';


delete_option( $option_name_1 );
delete_option( $option_name_2 );
delete_option( $option_name_3 );
delete_option( $option_name_4 );

// For site options in Multisite
delete_site_option( $option_name_1 );
delete_site_option( $option_name_2 );
delete_site_option( $option_name_3 );
delete_site_option( $option_name_4 );