<?php

add_action('wpam_front_end_registration_form_submitted', 'wpam_do_mailchimp_signup', 10, 2);

function wpam_do_mailchimp_signup($model, $request) {
    
    $first_name = strip_tags($request['_firstName']);
    $last_name = strip_tags($request['_lastName']);
    $email = strip_tags($request['_email']);

    $wpam_mc_settings = get_option('wpam_mailchimp_settings');
    $mc_list_name = $wpam_mc_settings['mc_list_name'];
    $enable_mc_signup = $wpam_mc_settings['enable_mc_signup'];
    if($enable_mc_signup != '1'){
        WPAM_Logger::log_debug("Mailchimp integration addon - Mailchimp signup is disabled in the settings.");
        return;
    }

    WPAM_Logger::log_debug("Mailchimp integration addon. After registration hook. Debug data: " . $mc_list_name . "|" . $email . "|" . $first_name . "|" . $last_name);

    if (empty($mc_list_name)) {//This level has no mailchimp list name specified for it
        return;
    }

    WPAM_Logger::log_debug("Mailchimp integration - Doing list signup...");

    include_once('lib/WPAM_MCAPI.class.php');
    
    $api_key = $wpam_mc_settings['mc_api_key'];
    if (empty($api_key)) {
        WPAM_Logger::log_debug("MailChimp API Key value is not saved in the settings. Go to MailChimp settings and enter the API Key.", 4);
        return;
    }

    $api = new WPAM_MCAPI($api_key);

    $target_list_name = $mc_list_name;
    $list_filter = array();
    $list_filter['list_name'] = $target_list_name;
    $all_lists = $api->lists($list_filter);
    $lists_data = $all_lists['data'];
    $found_match = false;
    foreach ($lists_data as $list) {
        WPAM_Logger::log_debug("Checking list name : " . $list['name'], 0);
        if (strtolower($list['name']) == strtolower($target_list_name)) {
            $found_match = true;
            $list_id = $list['id'];
            WPAM_Logger::log_debug("Found a match for the list name on MailChimp. List ID :" . $list_id, 0);
        }
    }
    if (!$found_match) {
        WPAM_Logger::log_debug("Could not find a list name in your MailChimp account that matches with the target list name: " . $target_list_name, 4);
        return;
    }
    WPAM_Logger::log_debug("List ID to subscribe to:" . $list_id, 0);

    //Create the merge_vars data
    $merge_vars = array('FNAME' => $first_name, 'LNAME' => $last_name, 'INTERESTS' => '');

    $retval = $api->listSubscribe($list_id, $email, $merge_vars);

    if ($api->errorCode) {
        WPAM_Logger::log_debug("Unable to load listSubscribe()!", 4);
        WPAM_Logger::log_debug("\tCode=" . $api->errorCode, 4);
        WPAM_Logger::log_debug("\tMsg=" . $api->errorMessage, 4);
    } else {
        WPAM_Logger::log_debug("MailChimp Signup was successful.", 0);
    }
    
}