<?php

class default_template_settings {
    
    /* This function will load default settings.
    * 
    * @since: AFter 2.3.5 version
    */
   function create_default_settings() {
	   	add_option( 'ac_enable_cart_emails', 'on' );
	   	add_option( 'ac_cart_abandoned_time', '60' );
	   	add_option( 'ac_delete_abandoned_order_days', '' );
	   	add_option( 'ac_email_admin_on_recovery', '' );
	   	add_option( 'ac_track_coupons', '' );
	   	add_option( 'ac_disable_guest_cart_email', '' );
	   	update_option( 'ac_settings_status', 'INDIVIDUAL' );      
   }

   /* This function will load default template while activating the plugin.
    * 
    * @since: AFter 2.3.5 version
    */
   function create_default_templates() {
       global $wpdb;

       $template_name_array    = array ( 'Initial', 'Interim', 'Final' );
       $site_title             = get_bloginfo( 'name' );
       $template_subject_array = array ( $site_title . ": Did you have checkout trouble?", $site_title . ": We want your business!", $site_title . ": Final Offer!" ); 
       $active_post_array      = array ( 0, 0, 0 );
       $email_frequency_array  = array ( 1, 1, 2 );
       $day_or_hour_array      = array ( 'Hours', 'Days', 'Days' );
       $body_content_array     = array ( addslashes ( '<html>
                                   <head>
                                       <title>My document title</title>
                                   </head>
                                       <body>

                                       <p> Hello {{customer.fullname}}, </p>
                                       <p> &nbsp; </p>
                                       <p> We\'re following up with you, because we noticed that on {{cart.abandoned_date}} you attempted to purchase the following products on {{shop.name}}. </p>
                                       <p> &nbsp; </p>
                                       <p> 
                                       <table border="0" cellspacing="5">
                                            <caption>
                                                <b>Cart Details</b>
                                            </caption>
                                			<tbody>
                                    			<tr>
                                        			<th></th>
                                        			<th>Product</th>
                                        			<th>Price</th>
                                        			<th>Quantity</th>
                                        			<th>Total</th>
                                    			</tr>
                                    			<tr style="background-color:#f4f5f4;"><td>{{item.image}}</td>
                                                    <td>{{item.name}}</td>
                                                    <td>{{item.price}}</td>
                                                    <td>{{item.quantity}}</td>
                                                    <td>{{item.subtotal}}</td>
                                                </tr>
                                    			<tr>
                                        			<td>&nbsp;</td>
                                        			<td>&nbsp;</td>
                                        			<td>&nbsp;</td>
                                        			<th>Cart Total:</th>
                                        			<td>{{cart.total}}</td>
                                    			</tr>
                                            </tbody>
                                        </table> 
                                        </p>
                                        <p> &nbsp; </p>
                                        <p> If you had any purchase troubles, could you please Contact to share them? </p>
                                        <p> &nbsp; </p>
                                        <p> Otherwise, how about giving us another chance? Shop <a href="{{shop.url}}">{{shop.name}}</a>. </p>
                                        <hr></hr>
                                        <p> You may <a href="{{cart.unsubscribe}}">unsubscribe</a> to stop receiving these emails. </p> 
                                        <p> &nbsp; </p>
                                        <p> <a href="{{shop.url}}">{{shop.name}}</a> appreciates your business.  </p>

                                   </body>
                           </html>' ),
                        addslashes( '<html>
                                   <head>
                                       <title>My document title</title>
                                   </head>
                                       <body>

                                       <p> Hello {{customer.fullname}}, </p>
                                       <p> &nbsp; </p>
                                       <p> We\'re following up with you, because we noticed that on {{cart.abandoned_date}} you attempted to purchase the following products on {{shop.name}}. </p>
                                       <p> &nbsp; </p>
                                       <p> 
                                       <table border="0" cellspacing="5">
                                            <caption>
                                                <b>Cart Details</b>
                                            </caption>
                                			<tbody>
                                    			<tr>
                                        			<th></th>
                                        			<th>Product</th>
                                        			<th>Price</th>
                                        			<th>Quantity</th>
                                        			<th>Total</th>
                                    			</tr>
                                    			<tr style="background-color:#f4f5f4;">
                                                    <td>{{item.image}}</td>
                                                    <td>{{item.name}}</td>
                                                    <td>{{item.price}}</td>
                                                    <td>{{item.quantity}}</td>
                                                    <td>{{item.subtotal}}</td>
                                                </tr>
                                    			<tr>
                                        			<td>&nbsp;</td>
                                        			<td>&nbsp;</td>
                                        			<td>&nbsp;</td>
                                        			<th>Cart Total:</th>
                                        			<td>{{cart.total}}</td>
                                    			</tr>
                                            </tbody>
                                        </table>
                                        </p>
                                        <p> &nbsp; </p>
                                        <p> If you had any purchase troubles, could you please Contact to share them? </p>
                                        <p> &nbsp; </p>
                                        <p> Otherwise, how about giving us another chance? Shop <a href="{{shop.url}}">{{shop.name}}</a>. </p>
                                        <p> &nbsp; </p>
                                        <p> As a thank you for coming back, here\'s a single-use coupon for 5% off. You can use discount code "{{coupon.code}}"&nbsp;during <a href="{{checkout.link}}" >checkout</a>. </p>
                                        <hr></hr>
                                        <p> You may <a href="{{cart.unsubscribe}}">unsubscribe</a> to stop receiving these emails. </p> 
                                        <p> &nbsp; </p>
                                        <p> <a href="{{shop.url}}">{{shop.name}}</a> appreciates your business.  </p>

                                   </body>
                           </html>' ),
           addslashes ( '<html>
                                   <head>
                                       <title>My document title</title>
                                   </head>
                                       <body>

                                       <p> Hello {{customer.fullname}}, </p>
                                       <p> &nbsp; </p>
                                       <p> We\'re following up with you, because we noticed that on {{cart.abandoned_date}} you attempted to purchase the following products on {{shop.name}}. </p>
                                       <p> &nbsp; </p>
                                       <p> 
                                       <table border="0" cellspacing="5">
                                            <caption>
                                                <b>Cart Details</b>
                                            </caption>
                                			<tbody>
                                    			<tr>
                                        			<th></th>
                                        			<th>Product</th>
                                        			<th>Price</th>
                                        			<th>Quantity</th>
                                        			<th>Total</th>
                                    			</tr>
                                    			<tr style="background-color:#f4f5f4;">
                                                    <td>{{item.image}}</td>
                                                    <td>{{item.name}}</td>
                                                    <td>{{item.price}}</td>
                                                    <td>{{item.quantity}}</td>
                                                    <td>{{item.subtotal}}</td>
                                                </tr>
                                    			<tr>
                                        			<td>&nbsp;</td>
                                        			<td>&nbsp;</td>
                                        			<td>&nbsp;</td>
                                        			<th>Cart Total:</th>
                                        			<td>{{cart.total}}</td>
                                    	   	   </tr>
                                           </tbody>
                                       </table> 
                                       </p>
                                       <p> &nbsp; </p>
                                       <p> If you had any purchase troubles, could you please Contact to share them? </p>
                                       <p> &nbsp; </p>
                                       <p> Otherwise, how about giving us another chance? Shop <a href="{{shop.url}}">{{shop.name}}</a>. </p>
                                       <p> &nbsp; </p>
                                       <p> As a thank you for coming back, here\'s a single-use coupon for 10% off. You can use discount code "{{coupon.code}}"&nbsp;during <a href="{{checkout.link}}" >checkout</a>. </p>
                                       <hr></hr>
                                       <p> You may <a href="{{cart.unsubscribe}}">unsubscribe</a> to stop receiving these emails. </p> 
                                       <p> &nbsp; </p>
                                       <p> <a href="{{shop.url}}">{{shop.name}}</a> appreciates your business.  </p>

                                   </body>
                           </html>' ) ) ;

       $from_email       = get_option( 'admin_email' );
       $coupon_code_id   = '';
       $ac_from_name     = 'Admin'; 
       $ac_email_reply   = get_option( 'admin_email' );
       $default_template = 1;
       $discount_array   = array( '0', '5', '10' );
       $is_wc_template   =  1 ;

       for ( $insert_count = 0 ; $insert_count < 3 ; $insert_count++ ) {

           $query = "INSERT INTO `" . $wpdb->prefix . "ac_email_templates`
           ( from_email, subject, body, is_active, frequency, day_or_hour, coupon_code, template_name, from_name, reply_email, default_template, discount, is_wc_template )
           VALUES ( '" . $from_email . "',
                    '" . $template_subject_array [ $insert_count ] . "',
                    '" . $body_content_array [ $insert_count ] . "',
                    '" . $active_post_array [ $insert_count ] . "',
                    '" . $email_frequency_array [ $insert_count ] . "',
                    '" . $day_or_hour_array [ $insert_count ] . "',
                    '" . $coupon_code_id . "',
                    '" . $template_name_array [ $insert_count ] . "',
                    '" . $ac_from_name . "',
                    '" . $ac_email_reply . "', 
                    '" . $default_template . "',
                    '" . $discount_array [ $insert_count ] . "',
                    '" . $is_wc_template . "' )";

           $wpdb->query( $query );
           
       }
   }
}