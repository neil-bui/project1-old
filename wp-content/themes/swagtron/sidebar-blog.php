<div class="col-lg-4 hidden-xs hidden-sm col-md-4">
	<div class="innerpage_rightlogo">
		<img src="<?php echo get_template_directory_uri(); ?>/images/innerpage_rightlogo.png">
	</div>
	<div class="swagtron_info">
		<?php if ( is_active_sidebar( 'sidebar-blog' )  ) : ?>
			<?php dynamic_sidebar( 'sidebar-blog' ); ?>
		<?php endif; ?>
	</div>
	<div class="innerpage_relatedheading">Related Articles</div>

	<?php $rand_posts = new WP_Query( array ( 'orderby' => 'rand', 'posts_per_page' => '3' ) );?>

	<?php if( $rand_posts->have_posts() ) : ?>
		<?php while( $rand_posts->have_posts() ) : $rand_posts->the_post(); ?>
			<div class="innerpage_articlerow">
				<div class="articlerow_image">
					<a href="<?php echo get_permalink(); ?>">
						<?php if( has_post_thumbnail() ) : ?>
							<img src="<?php echo the_post_thumbnail_url(); ?>" alt="Related Articles">
						<?php else : ?>
							<img src="<?php echo get_template_directory_uri(); ?>/images/no-image.png" alt="Related Articles">
						<?php endif; ?>
					</a>
				</div>
				<div class="articlerow_rightsec">
					<div class="articlename"><a href="<?php echo get_permalink(); ?>"><?php echo the_title(); ?></a></div>
					<div class="articledes"><?php echo wp_trim_words( get_the_excerpt(), 10 ); ?></div>
				</div>
			</div>
		<?php endwhile; ?>
		<?php wp_reset_query(); ?>
	<?php endif; ?>
	<div class="amazon_ads">
		<a href="<?php echo get_term_link( 53 ); ?>">
			<img src="<?php echo get_template_directory_uri(); ?>/images/amazon_ads.jpg">
		</a>
	</div>
</div>
