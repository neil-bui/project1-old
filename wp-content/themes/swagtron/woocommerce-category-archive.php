<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $wp_query;
$static_product_id = 60150;
$cart_url = WC()->cart->get_cart_url();

get_header(); ?>

  <div class="body_warp">

		<div class="hidden">
			<pre>
				<?php print_r( $wp_query ); ?>
			</pre>
		</div>

		<!-- Category: Hoverboard -->
		<?php if( is_product_category( 'hoverboard' ) ) : ?>

			<section class="category_banner">
				<div class="body_container">
					<div class="row">
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<h3>
								<img src="<?php echo get_template_directory_uri(); ?>/images/catagories0bannerimg.png" align="productimage">
							</h3>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<?php $_product = wc_get_product( $static_product_id ); ?>
							<div class="cat_heading1"><span>GET THERE IN STYLE</span></div>
							<h1 class="cat_heading2"><?php echo $_product->get_title(); ?></h1>
							<div class="cat_pricenow">
								<span>NOW <?php echo get_woocommerce_currency_symbol() . $_product->get_price(); ?></span>
							</div>
							<div class="cat_deascrption">Whether you're an urban commuter, a student on campus, running errands, or just cruising around, you can now enjoy SWAGTRON's all new Hoverboard.</div>
							<div class="cat_buynowbtn">
								<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => 70904, 'variation_id' => 70906, 'attribute_pa_feed-color' => 'white', 'quantity' => 1 ), $cart_url ) ); ?>">BUY NOW</a>
							</div>
						</div>
					</div>
				</div>
			</section>

		<!-- Category: Electric Scooters -->
		<?php elseif( is_product_category( 'electric-scooters' ) ) : ?>

			<section class="category_banner yellowbgbc">
				<div class="body_container">
					<div class="row">
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6" style="text-align:center;">
							<h3>
								<img src="<?php echo get_template_directory_uri(); ?>/images/scooter.png" align="productimage">
							</h3>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<div class="cat_heading1 topgaping"><span style="background:#fcbe23">GET THERE IN STYLE</span></div>
							<h1 class="cat_heading2">Swagger Electric Scooter</h1>
							<div class="cat_pricenow">
								<span style="background:#fcbe23">NOW $399.99</span>
							</div>
							<div class="cat_deascrption">Whether you're an urban commuter, a student on campus, running errands, or just cruising around, you can now enjoy SWAGTRON’s all new electric scooter, Swagger.</div>
							<div class="cat_buynowbtn">
								<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => 87263, 'variation_id' => 87265, 'attribute_pa_feed-color' => 'black', 'quantity' => 1 ), $cart_url ) ); ?>">BUY NOW</a>
							</div>
						</div>
					</div>
				</div>
			</section>

		<!-- Category: Electric Skateboards -->
		<?php elseif( is_product_category( 'electric-skateboard' ) ) : ?>

			<section class="category_banner blackbgbc">
				<div class="body_container">
					<div class="row">
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<h3>
								<img src="<?php echo get_template_directory_uri(); ?>/images/swagboard.png" align="productimage">
							</h3>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<div class="cat_heading1"><span style="background:#3d3d3d">GET THERE IN STYLE</span></div>
							<h1 class="cat_heading2">Swagboard e-Skateboard</h1>
							<div class="cat_pricenow"><span style="background:#3d3d3d">NOW $299.99</span></div>
							<div class="cat_deascrption">Whether you're an urban commuter, a student on campus, running errands, or just cruising around, you can now enjoy SWAGTRON’s all new electric skateboard, Swagboard.</div>
							<div class="cat_buynowbtn">
								<a href="<?php echo esc_url( add_query_arg( array( 'add-to-cart' => 85670, 'quantity' => 1 ), $cart_url ) ); ?>">BUY NOW</a>
							</div>
						</div>
					</div>
				</div>
			</section>

		<?php endif; ?>

    <section class="category_product">
      <div class="container">

					<?php if ( have_posts() ) : ?>

						<div class="row">

							<?php while ( have_posts() ) : the_post(); ?>

								<div class="col-lg-3 col-xs-12 col-sm-4">
									<div class="product_border">
										<div class="product_image">
											<h3><a href="<?php echo get_permalink( get_the_ID() ); ?>" title="<?php echo get_the_title(); ?>">
												<img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title(); ?>">
											</a></h3>
										</div>
										<h4 class="catproduct_name">
											<a href="<?php echo get_permalink( get_the_ID() ); ?>" title="<?php echo get_the_title(); ?>">
												<?php echo wp_trim_words( get_the_title(), 8 ); ?>
											</a>
										</h4>
										<div class="viewitem_btn">
											<a href="<?php echo get_permalink( get_the_ID() ); ?>">View items</a>
										</div>
									</div>
								</div>

							<?php endwhile; ?>

							<?php wp_reset_query(); ?>

						</div>	<!-- End of .row -->

					<?php else : ?>

						<div class="row"><?php _e( 'No products found!', 'swagtron' ); ?></div>

					<?php endif; ?>

			</div>	<!-- End of .container -->
    </section>

    <section class="more_swagtron">
      <div class="container">
        <div class="morefrom_swagtron">More from swagtron</div>
        <div class="row">
          <div class="col-lg-4 col-xs-12 col-sm-4 linuphover">
						<a href="<?php echo get_term_link( 54 ); ?>">
							<div class="orangediv">
								<div class="lineuptext">HOVERBOARDS</div>
								<h3 class="lineupimagediv">
									<img src="<?php echo get_template_directory_uri(); ?>/images/lineup_image1.png" alt="HOVERBOARDS">
								</h3>
							</div>
						</a>
					</div>
          <div class="col-lg-4 col-xs-12 col-sm-4 linuphover">
						<a href="<?php echo get_term_link( 59 ); ?>">
							<div class="blackdiv">
								<div class="lineuptext">ELECTRIC SKATEBOARDS</div>
								<h3 class="lineupimagediv">
									<img src="<?php echo get_template_directory_uri(); ?>/images/lineup_image2.png" alt="ELECTRIC SKATEBOARDS">
								 </h3>
							</div>
						</a>
					</div>
          <div class="col-lg-4 col-xs-12 col-sm-4 linuphover">
						<a href="<?php echo get_term_link( 60 ); ?>">
							<div class="yellowdiv">
								<div class="lineuptext">ELECTRIC SCOOTERS</div>
								<h3 class="lineupimagediv">
									<img src="<?php echo get_template_directory_uri(); ?>/images/lineup_image3.png" alt="ELECTRIC SCOOTERS">
								</h3>
							</div>
						</a>
					</div>
        </div>
      </div>
    </section>

	</div>

<?php get_footer(); ?>
