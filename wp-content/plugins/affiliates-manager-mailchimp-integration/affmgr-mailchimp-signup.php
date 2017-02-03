<?php
/*
  Plugin Name: Affiliates Manager MailChimp Integration
  Version: v1.0
  Plugin URI: http://wpaffiliatemanager.com/
  Author: wp.insider, wpaffiliatemgr
  Author URI: http://wpaffiliatemanager.com/
  Description: An addon for the affiliates manager plugin to signup the affiliates to your MailChimp list after registration.
 */

if (!defined('ABSPATH')){
    exit; //Exit if accessed directly
}

include_once('affmgr-mailchimp-admin-menu.php');
include_once('affmgr-mailchimp-action.php');
