<?php
/**
 * Plugin Name: Deny P.O. Boxes in WooCommerce
 * Plugin URI: danielsantoro.com/contact
 * Description: Rejects P.O. Boxes during cart submission in WooCommerce
 * Version: 1.0.0
 * Author: Daniel Santoro
 * Author URI: danielsantoro.com
 */
/*  Copyright 2015  danielsantoro.com  (email : contact@danielsantoro.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('woocommerce_after_checkout_validation', 'deny_pobox_postcode');
function deny_pobox_postcode( $posted ) {
 global $woocommerce;
  $address  = ( isset( $posted['shipping_address_1'] ) ) ?     
 $posted['shipping_address_1'] : $posted['billing_address_1'];
 $postcode = ( isset( $posted['shipping_postcode'] ) ) ?  
 $posted['shipping_postcode'] : $posted['billing_postcode'];
 $replace  = array(" ", ".", ",");
 $address  = strtolower( str_replace( $replace, '', $address ) );
 $postcode = strtolower( str_replace( $replace, '', $postcode ) );
 if ( strstr( $address, 'pobox' ) || strstr( $postcode, 'pobox' ) ) {
   $notice = sprintf( __( '%1$sSorry, we are unable to ship to P.O. Boxes. Please call your card issuer to add another non-P.O. Box shipping address to your account or use PayPal with a Verified non-P.O. Box address.' , 'error' ) , '<strong>' , '</strong>' );
        if ( version_compare( WC_VERSION, '2.3', '<' ) ) {
            $woocommerce->add_error( $notice );
        } else {
            wc_add_notice( $notice, 'error' );
        }
  }
}