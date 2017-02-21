<?php
/**
 * Template Name: Homepage
 */

get_header();

$shop_page_url = get_permalink( woocommerce_get_page_id( 'shop' ) );
$display_product_id = 60150;
$cart_url = WC()->cart->get_cart_url();
?>

<style>
.quantity{display:none !important}
/*.price{display:none !important}*/
.woocommerce-variation-availability{display:none !important}
.images{width:auto !important;}

.jck-wt-thumbnails-wrap,.jck-wt-images__slide{display:none}
.jck-wt-images__slide.jck-wt-images__slide--active{display:block}
</style>
  


  <!-- Start Body Wrapper -->
  <div class="body_warp">
    <div class="container">
			<!-- Banner Section -->
      <section class="bannersec">
        <div class="row">
					<div class="col-lg-12">
						<?php echo do_shortcode("[product_page id='$display_product_id']"); ?>
					</div>
        </div>
      </section>
			<!-- End of Banner Section -->



      <section class="partnerssec">
        <div class="heading_cation"><span>As Seen On</span></div>
        <div class="row">
          <div class="col-lg-12">
            <div class="partner_slider">
              <div class="row">
                <div class="col-lg-12">
                  <div id="seen_on" class="owl-carousel">
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo1.jpg" alt="Ellen"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo2.jpg" alt="USA Today"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo3.jpg" alt="Today"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo4.jpg" alt="Live Kelly & Michael"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo5.jpg" alt="Parade"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo6.jpg" alt="The Insider"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo7.jpg" alt="People"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo8.jpg" alt="Bella"></h3></div>
                    <div class="item"><h3><img src="<?php echo get_template_directory_uri(); ?>/images/partner_logo9.jpg" alt="KTLA 5"></h3></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>




<?php include (TEMPLATEPATH . '/templates/lineup.php'); ?>


      <!-- Banner Section -->
	      <section class="home_slide">
	        <h3 class="image_banner">
						<img src="<?php echo wp_get_attachment_url( get_woocommerce_term_meta( 60, 'thumbnail_id', true ) ); ?>" align="banner">
					</h3>
	        <div class="image_leftpaneltext">
	          <h2>Swagger Electric Scooter</h2>
	          <h3>GET THERE IN STYLE</h3>
	          <p>Whether you’re breezing through campus on your way to class, commuting to work, or exploring a new city, SWAGTRON’s all new electric scooter SWAGGER is a whole new way to travel.</p>
	          <div class="explore_productbtn">
							<a href="<?php echo get_term_link( 60 ); ?>">Explore Products</a>
						</div>
	          <div class="buy_productbtn">
							<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => 87263, 'variation_id' => 87265, 'attribute_pa_feed-color' => 'black', 'quantity' => 1 ), $cart_url ) ); ?>">BUY NOW</a>
						</div>
	        </div>
	      </section>

	      <section class="home_slide">
	        <h3 class="image_banner">
						<img src="<?php echo wp_get_attachment_url( get_woocommerce_term_meta( 59, 'thumbnail_id', true ) ); ?>" align="banner">
					</h3>
	        <div class="image_rightpaneltext">
	          <h2>Swagboard Electric Skateboard</h2>
	          <h3>GET THERE IN STYLE</h3>
	          <p>Zipping through the park, on your way to the mall, or thundering down a boardwalk? Travel at the speed of swag with SWAGTRON’s all new electric skateboard. </p>
	          <div class="explore_productbtn">
							<a href="<?php echo get_term_link( 59 ); ?>">Explore Products</a>
						</div>
	          <div class="buy_productbtn">
							<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => 85670, 'quantity' => 1 ), $cart_url ) ); ?>">BUY NOW</a>
						</div>
	        </div>
	      </section>

	      <section class="home_slide">
	        <h3 class="image_banner">
						<img src="<?php echo wp_get_attachment_url( get_woocommerce_term_meta( 54, 'thumbnail_id', true ) ); ?>" align="banner">
					</h3>
	        <div class="image_leftpaneltext">
	          <h2>SwagTron T3 Hoverboard</h2>
	          <h3>GET THERE IN STYLE</h3>
	          <p>Whether you’re rolling down to the mall, going to a concert, or exploring a city at night; let SWAGTRON’s T3 Hoverboard be your guide.</p>
	          <div class="explore_productbtn">
							<a href="<?php echo get_term_link( 54 ); ?>">Explore Products</a>
						</div>
	          <div class="buy_productbtn">
							<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => 70904, 'variation_id' => 70906, 'attribute_pa_feed-color' => 'white', 'quantity' => 1 ), $cart_url ) ); ?>">BUY NOW</a>
						</div>
	        </div>
	      </section>
      <!-- End of Banner Section -->




			<!-- Blog Section -->
      <section class="blog_sec">
        <h2 class="heading_cation"><span>latest from the adventure blog</span></h2>
        <div class="row">
					<?php
						$args = array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 2);
						$blog_posts = new WP_Query( $args );
					?>
					<?php if( $blog_posts->have_posts() ): ?>
						<?php while( $blog_posts->have_posts() ) : $blog_posts->the_post(); ?>
							<div class="col-lg-6 col-xs-12 col-sm-6">
								<h3 class="home_blogtitle">
									<a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a>
								</h3>
								<div class="home_postblog">
									<div class="home_blogdate">Posted: <?php echo get_the_date('F d, Y'); ?></div>
									<div class="home_blogcategories">Categories: <?php the_category( ', ' ); ?></div>
									<div class="home_blogimg">
										<a href="<?php echo get_permalink(); ?>">
											<?php if( has_post_thumbnail() ) : ?>
												<img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title(); ?>">
											<?php endif; ?>
										</a>
									</div>
									<div class="home_blogpara"><?php the_excerpt(); ?></div>
									<!--<div class="full_story"><a href="<?php echo get_permalink(); ?>">Get the full story</a></div>-->
								</div>
							</div>
						<?php endwhile; ?>
						<?php wp_reset_query(); ?>
					<?php endif; ?>
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
		$('.variation_buttons_wrapper').addClass('colorpanel');
		$('#pa_color_buttons, #pa_feed-color_buttons').prepend('<div class="color_lefttext">Available Colors:</div>');

		$('.single_add_to_cart_button').removeClass('button');

		$('.variations_button').prepend('<div class="explore_btn"><a href="<?php echo get_permalink($display_product_id); ?>">Explore Products</a></div>');
	});
</script>
