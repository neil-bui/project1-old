<?php
/**
 * Plugin Name: Send data to netsuite restlet
 * Plugin URI: https://www.swagtron.com/
 * Description: A toolkit that helps you get coupon from swagway_coupon table 	
 * Version: 1.0
 * Author: Hamilton Nieri
 * Author URI: https://www.swagtron.com/
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * ----------------------------------------------------------------------
 * Copyright (C) 2016  Hamilton Nieri  (Email: hamiltonnieri8755@yahoo.com)
 * ----------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ----------------------------------------------------------------------
 */

// Including WP core file
if ( ! function_exists( 'get_plugins' ) )
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

// WP AJAX Called By NetSuite User Event Script
add_action( 'wp_ajax_nopriv_send_data2restlet', 'send_data2restlet' );
add_action( 'wp_ajax_send_data2restlet', 'send_data2restlet' );

function send_data2restlet() {

    set_time_limit( 0 );

    header('content-type: text/javascript');
    header('access-control-allow-origin: *');

	$ffSelectedList = $_GET['ffSelectedList'];
	$custpage_mpl_category = $_GET['custpage_mpl_category'];
	$inpt_custpage_mpl_category = $_GET['inpt_custpage_mpl_category'];

    $url = 'https://rest.sandbox.netsuite.com/app/site/hosting/restlet.nl?script=664&deploy=1';

    $curl = curl_init();

    $header = array();
    $header[] = 'Content-type: application/json';
    $header[] = 'Authorization: NLAuth nlauth_email=hamiltonnieri8755@yahoo.com, nlauth_signature=varnamed123, nlauth_account=277620, nlauth_role=3';

    curl_setopt($curl, CURLOPT_URL, $url); 
    curl_setopt($curl, CURLOPT_HTTPHEADER,$header);
    curl_setopt($curl, CURLOPT_POST,true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(array('ffSelectedList'=>$ffSelectedList,'custpage_mpl_category'=>$custpage_mpl_category,'inpt_custpage_mpl_category'=>$inpt_custpage_mpl_category)));  
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 3000);

    $res = curl_exec($curl);

    curl_close($curl);

    echo $_GET['callback'] . '(' . $res . ')';

    exit;

}
