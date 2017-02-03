<?php
global $paged, $wp_query;
get_header();
?>

<div class="hidden">
	<pre>
		<?php print_r( $paged ); ?>
	</pre>
	<pre>
		<?php //print_r( $wp_query ); ?>
	</pre>
</div>

  <!-- Start Body Wrapper -->
  <div class="body_warp">
    <div class="container">
			<section class="innerpage_content">

				<!--<div class="blog_banner">
					<img src="<?php echo get_template_directory_uri(); ?>/images/blogfull_image.jpg" alt="blog banner">
					<div class="blogbanner_content">
						<h1><a href="#">PREPARING FOR YOUR FIRST ADVENTURE</a></h1>
						<p><span>Posted: <a href="#">September 20,2016</a></span> <span>By: <a href="#">Swagtron Adventurers</a></span></p>
					</div>
				</div>-->

				<div class="blogpost_section">
					<?php
						if ( have_posts() ) : $count = $total_count = 0;

							// Start the loop.
							while ( have_posts() ) : the_post();

								$count++; $total_count++;
					?>

							<?php if( $count == 1 ) : ?>

								<div <?php echo ( $total_count == $wp_query->post_count ) ? 'class="row article-last" data-page="' . get_query_var( 'page', 1 ) . '"' : 'class="row"'; ?> >
									<div class="col-lg-6 col-sm-6 hidden-xs blogpost_image">
										<a href="<?php echo get_permalink(); ?>">
											<?php if( has_post_thumbnail() ) : ?>
												<img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title(); ?>">
											<?php else : ?>
												<img src="<?php echo get_template_directory_uri(); ?>/images/no-image-big.png" alt="<?php echo get_the_title(); ?>">
											<?php endif; ?>
										</a>
									</div>
									<div class="col-lg-6 col-sm-6 col-xs-12">
										<h2>
											<a href="<?php echo get_permalink(); ?>"><?php echo the_title(); ?></a>
										</h2>
										<div class="postblog_date">
											<span>Posted: <a><?php echo get_the_date('F d, Y'); ?></a></span>
											<span>By: <a><?php the_author(); ?></a></span>
										</div>
										<?php the_excerpt(); ?>
										<a href="<?php echo get_permalink(); ?>" class="default-btn">read more</a>
									</div>
								</div>

							<?php elseif( $count == 2 ) : ?>

								<div <?php echo ( $total_count == $wp_query->post_count ) ? 'class="row article-last" data-page="' . get_query_var( 'page', 1 ) . '"' : 'class="row"'; ?> >
									<div class="col-lg-6 col-sm-6 col-xs-12">
										<h2>
											<a href="<?php echo get_permalink(); ?>"><?php echo the_title(); ?></a>
										</h2>
										<div class="postblog_date">
											<span>Posted: <a><?php echo get_the_date('F d, Y'); ?></a></span>
											<span>By: <a><?php the_author(); ?></a></span>
										</div>
										<?php the_excerpt(); ?>
										<a href="<?php echo get_permalink(); ?>" class="default-btn">read more</a></div>
									<div class="col-lg-6 col-sm-6 hidden-xs blogpost_image">
										<a href="<?php echo get_permalink(); ?>">
											<?php if( has_post_thumbnail() ) : ?>
												<img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title(); ?>">
											<?php else : ?>
												<img src="<?php echo get_template_directory_uri(); ?>/images/no-image-big.png" alt="<?php echo get_the_title(); ?>">
											<?php endif; ?>
										</a>
									</div>
								</div>

							<?php endif; ?>

							<?php if( $count == 2 ) : $count = 0; ?>
								<div class="blog_fullad">
									<a href="<?php echo get_term_link( 53 ); ?>">
										<img src="<?php echo get_template_directory_uri(); ?>/images/full_ad3.jpg">
									</a>
								</div>
							<?php endif; ?>

					<?php
								// End of the loop.
							endwhile;
					?>

							<div class="row loading-container text-center" style="display:none">
								<div class="sk-folding-cube">
								  <div class="sk-cube1 sk-cube"></div>
								  <div class="sk-cube2 sk-cube"></div>
								  <div class="sk-cube4 sk-cube"></div>
								  <div class="sk-cube3 sk-cube"></div>
								</div>
							</div>

					<?php
						endif;
					?>
				</div>

			</section>
    </div>
  </div>
  <!-- End Body Wrapper -->

<?php
get_footer();
?>

<script type="text/javascript">
	$(document).ready(function(){

    $(window).scroll(function(){
    	//console.log( 'screen top: ' + $(window).scrollTop() );
    	if( $(".article-last").length ) {
	      if ( $(window).scrollTop() > $(".article-last").offset().top ) {
	        //console.log('visible');
	        var last_dom = $(".article-last");
	        last_dom.removeClass('article-last');
	        $('.loading-container').show();

	        // Call the ajax
	        $.ajax({
	        	url: ajaxurl,
	        	method: 'post',
	        	dataType: 'html',
	        	data: {
							page: $(last_dom).data('page'),
							action: 'load_more_blog_post'
						}
	        })
	        .success(function(msg){
	        	$(msg).insertBefore( '.loading-container' );
	        	$('.loading-container').hide();
	        });
	      }
			}
    });
	});
</script>
