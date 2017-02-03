<?php
/**
 * Plugin Name: WooCommerce Only Ship to Continental US
 * Plugin URI: https://gist.github.com/BFTrick/7805588
 * Description: Only Ship to the Continental US
 * Author: Patrick Rauland
 * Author URI: http://patrickrauland.com/
 * Version: 1.0.1
 *
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
 *
 * @author		Patrick Rauland
 * @since		1.0.1
 */

/**
* Only ship to the continental US
*
* @param array $available_methods
*/
function patricks_only_ship_to_continental_us( $available_methods ) {
	global $woocommerce;
	$excluded_states = array( 'AK','HI','GU','PR', 'AS', 'MP', 'UM', 'VI', 'AA', 'AE', 'AP'  );

	if( in_array( $woocommerce->customer->get_shipping_state(), $excluded_states ) ) {
		// Empty the $available_methods array
		$available_methods = array();
	}

	return $available_methods;
}
add_filter( 'woocommerce_package_rates', 'patricks_only_ship_to_continental_us', 10 );
