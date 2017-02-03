<?php
get_header();
?>

  <!-- Start Body Wrapper -->
  <div class="body_warp">
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
