<?php
/**
 * Template Name: Customer Information
 */

 global $wpdb;
 $wpdb->capture_users = $wpdb->prefix . 'capture_users';


if( isset( $_POST['info_nonce'] ) ) {
	if( wp_verify_nonce( $_POST['info_nonce'], 'swag_cust_action' ) ) {

		$fullname = sanitize_text_field( $_POST['fullname'] );
		$email = sanitize_email( $_POST['email'] );


		if( empty( $fullname ) || empty( $email ) ) {
			$error_msg = 'Please fill all the required fields.';
		} else {

			$wpdb->insert(
				$wpdb->capture_users,
				array(
					'fullname' 			=> $fullname,
					'email' 				=> $email,
					'date_captured' => current_time('mysql')
				),
				array(
					'%s',
					'%s',
					'%s'
				)
			);


			$_SESSION['move_to_cart'] = true;
			//echo '<pre>'; print_r( $_SESSION ); echo '</pre>'; die;

			wp_redirect( WC()->cart->get_checkout_url() );
			exit;
		}

	} else {
		$error_msg = 'Something went wrong. Please try again later.';
	}
}

get_header();
?>

<?php //echo '<pre>'; print_r( $_SESSION ); echo '</pre>'; ?> 
<div class="hidden">
	<pre>
		<?php print_r( $_SESSION ); ?>
	</pre>
</div>

  <!-- Start Body Wrapper -->
  <div class="body_warp">
    <div class="container">
			<div class="row">
				<div class="col-md-12">
					<legend>
						<h2>Customer Information</h2>
					</legend>
				</div>

				<?php if( isset( $error_msg ) ) : ?>
					<p class="message error"><span class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-times fa-stack-1x fa-inverse"></i></span> <?php echo $error_msg; ?></p>
				<?php endif; ?>

				<?php if( isset( $success_msg ) ) : ?>
					<p class="message success"><span class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-check fa-stack-1x fa-inverse"></i></span> <?php echo $success_msg; ?></p>
				<?php endif; ?>

				<div class="col-md-6 col-xs-12">
					<p>Before proceeding to checkout page, please fill out this form</p>
					<form id="quote_form" method="post" action="">
						<div class="form-group">
							<label>Full Name <span class="required">*</span></label>
							<input class="form-control input-lg" type="text" name="fullname">
						</div>
						<div class="form-group">
							<label>Email address <span class="required">*</span></label>
							<input class="form-control input-lg" type="email" name="email">
						</div>
						<?php wp_nonce_field( 'swag_cust_action', 'info_nonce' ); ?>
						<button type="submit" class="default-bluebtn">Submit</button>
					</form>
				</div>
			</div>
    </div>
  </div>
  <!-- End Body Wrapper -->

<?php
get_footer();
?>

<script type="text/javascript">
	$(document).ready(function(){
		// Validating Form
    $("#quote_form").validate({
      rules: {
        fullname: {required: true},
				email: {
					email: true,
					required: true
				}
      },
      messages: {
        fullname: {
					required: 'Please enter your full name'
				},
        email: {
          email: 'Please enter valid email format',
					required: 'Please enter your email'
        }
      }
    });
	});
</script>