<?php
/**
 * Lost password form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-lost-password.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wc_print_notices(); ?>

<form method="post" class="woocommerce-ResetPassword lost_reset_password">

	<p><?php echo apply_filters( 'woocommerce_lost_password_message', __( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'woocommerce' ) ); ?></p>

	<div class="row">
		<div class="col-md-4 col-md-4 col-sm-6 col-xs-12">
			<label for="user_login"><?php _e( 'Username or email', 'woocommerce' ); ?></label>
			<input class="form-control input-lg" type="text" name="user_login" id="user_login" />
		</div>
	</div>

	<div class="clear" style="margin:5px"></div>

	<?php do_action( 'woocommerce_lostpassword_form' ); ?>



	<div class="">
		<input type="hidden" name="wc_reset_password" value="true" />
		<input style="margin-top:20px;" type="submit" class="default-bluebtn" value="<?php esc_attr_e( 'Reset Password', 'woocommerce' ); ?>" />
	</div>

	<?php wp_nonce_field( 'lost_password' ); ?>

</form>
