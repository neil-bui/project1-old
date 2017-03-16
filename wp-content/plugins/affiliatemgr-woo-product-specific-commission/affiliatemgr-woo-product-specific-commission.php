<?php

/**
 * Plugin Name: Affiliates Manager WooCommerce Product Specific Commission
 * Plugin URI: https://wpaffiliatemanager.com/product-specific-affiliate-commission-for-woocommerce-products/
 * Description: Addon for using WooCommerce product specific commission options with the Affiliates Manager plugin
 * Version: 1.0.1
 * Author: wp.insider, affmngr
 * Author URI: https://wpaffiliatemanager.com
 * Requires at least: 3.0
 */
if (!defined('ABSPATH')){
    exit;
}

//Add the meta box in the woocommerce product add/edit interface
add_action('add_meta_boxes', 'wpam_woocommerce_product_specific_commission_meta_boxes');

function wpam_woocommerce_product_specific_commission_meta_boxes() {
    add_meta_box('wpam-woo-product-specific-commission-data', 'WP Affiliates Manager Settings', 'wpam_woo_product_specific_commission_data_box', 'product', 'normal', 'high');
}

function wpam_woo_product_specific_commission_data_box($wp_post_obj) {
    $commission_type = get_post_meta($wp_post_obj->ID, 'wpam_woo_product_specific_commission_type', true);
    $commission_amount = get_post_meta($wp_post_obj->ID, 'wpam_woo_product_specific_commission', true);
    echo '<p>'.__('Commission Type: ', 'wpam');
    echo '<select id="wpam_woo_product_specific_commission_type" name="wpam_woo_product_specific_commission_type">';
    echo '<option value="percent"'.($commission_type=="percent" ? ' selected="selected"' : '').'>'.__('Percentage', 'wpam').'</option>';
    echo '<option value="fixed"'.($commission_type=="fixed" ? ' selected="selected"' : '').'>'.__('Fixed Amount', 'wpam').'</option>';
    echo '</select></p>';
    echo __('Commission Amount: ', 'wpam');
    echo '<input type="text" size="5" name="wpam_woo_product_specific_commission" value="' . $commission_amount . '" />';
    echo '<p>'.__('Product specific commission for this product (example value: 25). Only enter the number (do not use "%" or "$" sign).', 'wpam').'</p>';
}

//Save the membership level data to the post meta with the product when it is saved
add_action('save_post', 'wpam_woo_save_product_specific_commission_data', 10, 2);

function wpam_woo_save_product_specific_commission_data($post_id, $post_obj) {
    // Check post type for woocommerce product
    if ($post_obj->post_type == 'product') {
        // Store data in post meta table if present in post data
        if (isset($_POST['wpam_woo_product_specific_commission_type'])) {
            update_post_meta($post_id, 'wpam_woo_product_specific_commission_type', $_POST['wpam_woo_product_specific_commission_type']);
        }
        if (isset($_POST['wpam_woo_product_specific_commission'])) {
            update_post_meta($post_id, 'wpam_woo_product_specific_commission', $_POST['wpam_woo_product_specific_commission']);
        }
    }
}

add_filter('wpam_commission_tracking_override', 'wpam_woo_product_specific_commission_override', 10, 3);

function wpam_woo_product_specific_commission_override($override, $affiliate, $args) {
    global $wpdb;
    $aff_id = $args['aff_id'];
    $txn_id = $args['txn_id'];
    $pieces = explode("_", $txn_id); //woocommerce subscription ID will have a date appended to the order ID. We need to explode it to get the actual Order ID.
    $order_id = $pieces[0];
    WPAM_Logger::log_debug('== WooCommerce Product Specific Commission Addon - Order ID: '.$order_id.', Txn ID: '.$txn_id.', Affiliate ID: '.$aff_id.'==');
    $product_comm_amount = 0;
    $total_commission_amount = 0;
    $order = new WC_Order($order_id);
    $order_items = $order->get_items();
    $amount = $order->order_total;

    foreach ($order_items as $item_id => $item) {
        if ($item['type'] == 'line_item') {
            $_product = $order->get_product_from_item($item);
            $post_id = $_product->id;
            $p_comm_type = get_post_meta($post_id, 'wpam_woo_product_specific_commission_type', true);
            $p_comm_amount = get_post_meta($post_id, 'wpam_woo_product_specific_commission', true);
            $line_subtotal = $item['line_total']; //Includes the total actual price paid (after discount, fees etc.)
            $item_qty = $item['qty'];
            WPAM_Logger::log_debug('Product ID: '.$post_id);
            //WPAM_Logger::log_debug_array($item); 
            if ($p_comm_amount == "0") {
                //== Product specific commisison override to 0. No commisison for this product.
                $product_comm_amount = 0;
                WPAM_Logger::log_debug('Product specific commission for this product is set to 0. No commission needs to be calculated for this product'); 
            } 
            else if (is_numeric($p_comm_amount) && $p_comm_amount > 0) {
                //== Calculate product specific commision for this product ==
                WPAM_Logger::log_debug('Product specific commission for this product: '.$p_comm_amount.', commission type: '.$p_comm_type);
                if ($p_comm_type=="fixed") {
                    //using fixed commission rate model
                    $product_comm_amount = $item_qty * $p_comm_amount;
                } 
                else {
                    //using % commission model
                    //The total item price includes the (individual item price * quantity)
                    $product_comm_amount = ($line_subtotal * $p_comm_amount / 100);
                }
            }
            else{ //calculate commission normally
                WPAM_Logger::log_debug('No product specific commission for this product. Commission will be calculated based on the affiliate profile.');
                WPAM_Logger::log_debug('Affiliate ID: '.$aff_id.', commission type: '.$affiliate->bountyType);
                if ($affiliate->bountyType == 'fixed')
                {
                    $product_comm_amount = $item_qty * $affiliate->bountyAmount;
                }
                else{
                    $product_comm_amount = ($line_subtotal * $affiliate->bountyAmount / 100);
                }
            }
            $total_commission_amount = $total_commission_amount + $product_comm_amount;           
            WPAM_Logger::log_debug('Line Total: '.$line_subtotal.', Quantity: '.$item_qty);
            WPAM_Logger::log_debug('Product Commission Amount: '.$product_comm_amount);
        }
    }//End of foreach

    $override = "Commission overriden by WooCommerce product specific commission addon";
    if ($total_commission_amount <= 0) {
        WPAM_Logger::log_debug('The total commission amount is 0 for this transaction. So no commission will be awarded.');
        WPAM_Logger::log_debug('== End of WooCommerce product specific commission addon tasks ==');
        return $override;
    }

    //Round up the amounts
    $creditAmount = round($total_commission_amount, 2);   
    $creditAmount = apply_filters( 'wpam_credit_amount', $creditAmount, $amount, $txn_id );
    $currency = WPAM_MoneyHelper::getCurrencyCode();
    $description = "Credit for sale of $amount $currency (PURCHASE LOG ID = $txn_id)";
    $query = "
    SELECT *
    FROM ".WPAM_TRANSACTIONS_TBL."
    WHERE referenceId = %s    
    ";
    $txn_record = $wpdb->get_row($wpdb->prepare($query, $txn_id));
    if($txn_record != null) {  //found a record
        WPAM_Logger::log_debug('Commission for this sale has already been awarded. PURCHASE LOG ID: '.$txn_id.', Purchase amount: '.$amount);        
    } 
    else {
        $table = WPAM_TRANSACTIONS_TBL;
        $data = array();
        $data['dateModified'] = date("Y-m-d H:i:s", time());
        $data['dateCreated'] = date("Y-m-d H:i:s", time());
        $data['referenceId'] = $txn_id;
        $data['affiliateId'] = $affiliate->affiliateId;
        $data['type'] = 'credit';
        $data['description'] = $description;
        $data['amount'] = $creditAmount;
        WPAM_Logger::log_debug('Awarding commission for PURCHASE LOG ID: '.$txn_id.', Purchase amount: '.$amount.'. Commission amount: '.$creditAmount);
        $wpdb->insert( $table, $data);
    }   
    WPAM_Logger::log_debug('== End of WooCommerce product specific commission addon tasks ==');
    return $override;
}
