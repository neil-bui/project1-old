<?php
/**
 * Template Name: Product Landing
 */

global $product;
$comments_count = wp_count_comments( get_the_ID() );
$cart_url = WC()->cart->get_cart_url();

get_header('landing');
?>



  <!-- Start Body Wrapper -->
  <div class="landing_body">
    <div class="container">

			<?php
				if ( have_posts() ) :

					// Start the loop.
					while ( have_posts() ) : the_post();

						the_content();

						// End of the loop.
					endwhile;

				endif;
			?>

    </div>
  </div>
  <!-- End Body Wrapper -->


<?php
get_footer();
?>

<script type="text/javascript">
	$(document).ready(function(){
		$('.variation_buttons_wrapper').addClass('colorpanel');
		$('#pa_color_buttons').prepend('<div class="color_lefttext">Colors:</div>');

		$('.single_add_to_cart_button').removeClass('button');
	});
</script>