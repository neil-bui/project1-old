<?php
/**
* Template Name: Contact Us
*/
get_header();
?>

  <!-- Start Body Wrapper -->
  <div class="body_warp">
    <div class="container">
    	<section class="innerpage_content">
    		<div class="row">
    			<div class="col-lg-8 col-xs-12 col-sm-12 col-md-8">
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

		      <?php get_sidebar('blog'); ?>

	      </div>
	    </section>
    </div>
  </div>
  <!-- End Body Wrapper -->

<?php
get_footer();
