<?php

/**
 * Ajax Function: Load blog post
 * @author: Biswajit Paul
 *
 * This function creates infinite scrolling functionality
 */
 if( ! function_exists( 'load_post_ajax_func' ) ) {

	add_action( 'wp_ajax_load_more_blog_post', 'load_post_ajax_func' );
	add_action( 'wp_ajax_nopriv_load_more_blog_post', 'load_post_ajax_func' );

	function load_post_ajax_func() {
    global $wpdb;
    $pagenum = filter_var( $_POST['page'], FILTER_SANITIZE_NUMBER_INT );
    $next_page = ( $pagenum ) ? $pagenum + 1 : 2;
    $posts_per_page = get_option('posts_per_page');

		$query_args = array(
		  'post_type' => 'post',
		  'post_status' => 'publish',
		  'posts_per_page' => $posts_per_page,
		  'paged' => $next_page
		);

		$blog_posts = new WP_Query( $query_args );

		if( $blog_posts->have_posts() ) {
			$count = $total_count = 0;

			while( $blog_posts->have_posts() ) {
				$blog_posts->the_post();
				$count++; $total_count++;

				if( $count == 1 ) {
?>
					<div <?php echo ( $total_count == $blog_posts->post_count ) ? 'class="row article-last" data-page="' . $next_page . '"' : 'class="row"'; ?> >
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
<?php
				}

				elseif( $count == 2 ) {
?>
					<div <?php echo ( $total_count == $blog_posts->post_count ) ? 'class="row article-last" data-page="' . $next_page . '"' : 'class="row"'; ?> >
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
<?php
				}

				if( $count == 2 ) {
					$count = 0;
?>
					<div class="blog_fullad">
						<a href="<?php echo get_term_link( 53 ); ?>">
							<img src="<?php echo get_template_directory_uri(); ?>/images/full_ad3.jpg">
						</a>
					</div>
<?php
				}

			}	// End while
		}	// End if

    exit;
  }
}
