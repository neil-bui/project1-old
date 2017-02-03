<?php
/*
* Plugin Name:         AdRoll for WooCommerce Stores
* Plugin URI:          https://www.adroll.com/
* Description:         This plugin easily integrates AdRoll with your WooCommerce site.
* Author:              AdRoll
* Version:             1.0.2
*/

global $adroll_base_url;
$adroll_base_url = "https://app.adroll.com";

class PixelInject {

	// initial point of action
//	public function init() {
		// Hide the menu page until we fix the persistent storage.
//		add_action('admin_menu', array($this, 'add_eid_edit_page'));
//		add_action('admin_init', array($this, 'eid_settings'));
//	}

	public function get_eids() {
		// Step 1: get host of site
		$server_name = $_SERVER['SERVER_NAME'];
//		$http_host = $_SERVER['HTTP_HOST'];
		// Step 2: get eids from our woocommerce integration API
		// ref: http://stackoverflow.com/questions/10217068/receive-json-object-from-http-get-in-php
		global $adroll_base_url;
		$get_eids_url =  "{$adroll_base_url}/woocommerce/get_eids/{$server_name}";
		$response = wp_remote_get($get_eids_url);
		if (is_wp_error($response)) {
			log_error($response->get_error_message());
			return;
		}
		$json = $response['body'];
		$eids = json_decode($json, true);
		if($eids) {
			// Set adv_id to be retrieved later.
			$this->adv_id = $eids['advertisable_eid'];
			// Step 3: update the eids in the options db.
			update_option('adroll_adv_eid', $eids['advertisable_eid']);
			update_option('adroll_pixel_eid', $eids['pixel_eid']);
		}
	}
	public function add_eid_edit_page() {
		add_options_page('AdRoll', " AdRoll ID's ", 'manage_options', 'wp_adroll', array($this, 'id_settings_page'));
	}
	// register settings group
	public function eid_settings() {
		register_setting('adrl_adv_id', 'adrl_adv_id');
		register_setting('adrl_adv_id', 'adrl_adv_id');
		add_settings_section(
			'adrl_settings_section',         // ID used to identify this section and with which to register options
			"AdRoll Unique ID's",                  // Title to be displayed on the administration page
			array($this, 'adrl_settings_callback'), // Callback used to render the description of the section
			'wp_adroll'                           // Page on which to add this section of options
		);
		add_settings_field(
			'adrl_adv_id',                      // ID used to identify the field throughout the theme
			'AdRoll adv_id',                           // The label to the left of the option interface element
			array($this, 'adrl_adv_id_callback'),   // The name of the function responsible for rendering the option interface
			'wp_adroll',                          // The page on which this option will be displayed
			'adrl_settings_section'         // The name of the section to which this field belongs
		);
		add_settings_field(
			'adrl_pix_id',                      // ID used to identify the field throughout the theme
			'AdRoll pix_id',                           // The label to the left of the option interface element
			array($this, 'adrl_pix_id_callback'),   // The name of the function responsible for rendering the option interface
			'wp_adroll',                          // The page on which this option will be displayed
			'adrl_settings_section'         // The name of the section to which this field belongs
		);

	}

	public function adrl_settings_callback() {
		$out = '';
		echo $out;
	}
	public function adrl_adv_id_callback() {
		$val = get_option('adroll_adv_eid', '');
		echo '<div><input type="text" id="adrl_adv_id" name="adroll_adv_eid" value="'.$val.'" /></div>';
	}
	public function adrl_pix_id_callback() {
		$val = get_option('adroll_pixel_eid', '');
		echo '<div><input type="text" id="adrl_pixel_id" name="adroll_pixel_eid" value="'.$val.'" /></div>';
	}
	// Render settings page template.
	public function id_settings_page() {
		include_once 'eid_edit_settings.php';
	}

}

function inject_pixel() {
	$adv_id = get_option('adroll_adv_eid');
	$pix_id = get_option('adroll_pixel_eid');

	if(!empty($adv_id) && !empty($pix_id)) {
		include_once 'dynamic_smart_pixel.php';
	}
}

function get_eids_from_adroll() {
    $wp_adroll = new PixelInject;
    $wp_adroll->get_eids();
    $adv_id = get_option('adroll_adv_eid', false);
    $pix_id = get_option('adroll_pixel_eid', false);
    // If it worked, let's mark the plugin as activated.
    if (($adv_id && $pix_id) == true) {
        global $adroll_base_url;
        $plugin_activate_url = "{$adroll_base_url}/woocommerce/plugin_activate/{$adv_id}";
        wp_remote_get($plugin_activate_url);
    }
}

function calculate_end_attempt_date() {
	$initial_date = get_option('adroll_initial_setup_date');
	$initial_date = DateTime::createFromFormat('Ymd', $initial_date);
	// Stop attempting 10 days from whenever it was first attempted.
	$end_attempt_date = $initial_date->add(new DateInterval("P10D"));
	return $end_attempt_date->format('Ymd');
}

function initialize_pixel_attempt_db_values() {
	// initil_setup_date: the date we first attempted to activate the plugin
	// adroll_pixel_inject_attempts: the number of times we've tried to inject a pixel
	add_option('adroll_initial_setup_date', date("Ymd"));
	add_option('adroll_final_attempt_date', calculate_end_attempt_date());
	add_option('adroll_pixel_inject_attempts', 0);
}

function increment_attempts() {
	// last_attempted_date: last time a 'pixel_inject' was attempted
	// Increment this number, or update the date of attempt
	if (get_option('adroll_last_attempted_date', false) ) {
		if ( get_option('adroll_last_attempted_date') < date('Ymd') ) {
			update_option('adroll_last_attempted_date', date("Ymd"));
			update_option('adroll_pixel_inject_attempts', 0);
		} else {
			$num_of_attempts = get_option('adroll_pixel_inject_attempts', 0);
			update_option('adroll_pixel_inject_attempts', $num_of_attempts + 1);
		}
	} else {
		add_option('adroll_last_attempted_date', date("Ymd"));
		add_option('adroll_pixel_inject_attempts', 0);
	}
}

function admin_notice__success() {
	?>
	<div class="notice notice-success is-dismissible">
		<p>Successfully Installed AdRoll plugin. Go to <a href="http://app.adroll.com/onboarding/welcome">AdRoll's Website</a> to launch your campaign!</p>
	</div>
	<?php
	delete_option('admin_notice_success');
}

function admin_notice__warning() {
	?>
	<div class="notice notice-warning is-dismissible">
		<p>WARNING! Plugin will not work until you’ve connected an AdRoll account. Create an Account or Login to AdRoll <a href="http://app.adroll.com/onboarding/register">here</a>.</p>
	</div>
	<?php
	delete_option('admin_notice_warning');
}


add_action('activated_plugin', 'adroll_plugin_activated');
function adroll_plugin_activated() {
	if (get_option('adroll_do_activation') == "1") {
		initialize_pixel_attempt_db_values();
		get_eids_from_adroll();
		$adv_id = get_option('adroll_adv_eid', false);
		global $adroll_base_url;
		if ($adv_id) {
			add_option('admin_notice_success', 1);
			delete_option('adroll_do_activation');
		} else {
			add_option('admin_notice_warning', 1);
			delete_option('adroll_do_activation');
		}
		delete_option('adroll_do_activation');
	}
}

function admin_notice() {
	if (get_option('admin_notice_success') == "1") {
		admin_notice__success();
	} elseif (get_option('admin_notice_warning') == "1") {
		admin_notice__warning();
	}
}


// We're setting a database value to trigger running the post activation code. Since `add_action('activated_plugin', 'foo')`
// gets triggered when *any* plugin is activated - we want to make sure this only happens when our plugin is activated.
// Ditto for deactivation. http://stackoverflow.com/questions/4890723/wordpress-plugin-need-to-fire-off-a-function-immediately-after-the-plugin-is-ac
register_activation_hook(__FILE__, 'setup_activation');
function setup_activation() {
	add_option('adroll_do_activation',"1");
}

add_action('deactivated_plugin', 'adroll_plugin_mark_deactivated');
function adroll_plugin_mark_deactivated() {
	if (get_option('adroll_do_deactivation') == "1") {
		$adv_id = get_option('adroll_adv_eid', false);
		global $adroll_base_url;
		if ($adv_id) {
			$plugin_deactivate_url = "{$adroll_base_url}/woocommerce/plugin_deactivate/{$adv_id}";
			wp_remote_get($plugin_deactivate_url);
		}
		// Teardown db fields.
		$adroll_db_fields = array('adroll_initial_setup_date', 'adroll_final_attempt_date', 'adroll_pixel_inject_attempts',
							'adroll_adv_eid', 'adroll_pixel_eid', 'adroll_plugin_silenced', 'adroll_last_attempted_date',
							'adroll_do_deactivation');

		foreach ($adroll_db_fields as $field) {
			delete_option($field);
		}
	}
}

register_deactivation_hook(__FILE__, 'deactivated_hook_setup');
function deactivated_hook_setup() {
	add_option('adroll_do_deactivation',"1");
}

// For accounts that don't have an existing database object in AdRoll - we have the plugin check to see if the objects
// with (EID's) exist.  We'll want to limit the amount of times the website pings AdRoll's servers.
if (((get_option('adroll_adv_eid', false) && get_option('adroll_pixel_eid', false)) == false)) {
	if (get_option('adroll_initial_setup_date', false) ) {
		if ((get_option('adroll_pixel_inject_attempts', 0) <= 10 and date('Ymd') <= get_option('adroll_final_attempt_date'))) {
			// On every page load we'll check if the EID's are in the database.
			get_eids_from_adroll();
			increment_attempts();
		} elseif (date('Ymd') > get_option('adroll_final_attempt_date') and !get_option('adroll_plugin_silenced', false)) {
			$server_name = $_SERVER['SERVER_NAME'];
			$plugin_silenced_url = "{$adroll_base_url}/woocommerce/plugin_silenced/{$server_name}";
			wp_remote_post($plugin_silenced_url);
			add_option('adroll_plugin_silenced', true);
		}
	}
}

function log_error($error_message) {
	echo "<script>console.log('debug')</script>";
	echo "<script>console.log('".print_r($error_message, true)."')</script>";
}


// Inject pixel every time store is loaded.
add_action('wp_footer', 'inject_pixel');
admin_notice();
