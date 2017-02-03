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
			?>

					<section class="innerpage_content">
						<div class="row">
							<div class="col-lg-8 col-xs-12 col-sm-12 col-md-8">
								<div class="singleblog_section">

									<h1><?php echo the_title(); ?></h1>

									<div class="single_blogdatesec">
										<span>Posted: <?php echo get_the_date('F d, Y'); ?></span><span>Categories: <?php the_category( ', ' ); ?></span>
									</div>

									<?php if( has_post_thumbnail() ) : ?>
										<div class="single_blogimg">
											<img src="<?php echo the_post_thumbnail_url(); ?>" alt="Banner">
										</div>
									<?php endif; ?>

									<?php the_content(); ?>

								</div>
							</div>

							<?php get_sidebar('blog'); ?>

						</div>
					</section>

			<?php
						// End of the loop.
					endwhile;

				endif;
      ?>
    </div>
  </div>
  <!-- End Body Wrapper -->

<?php
get_footer();