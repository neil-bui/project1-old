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
							<p>
								<img src="<?php echo get_template_directory_uri(); ?>/images/catagories0bannerimg.png" align="productimage">
							</p>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<?php $_product = wc_get_product( $static_product_id ); ?>
							<div class="cat_heading1"><span>ARRIVE WITH SWAG</span></div>
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
							<p>
								<img src="<?php echo get_template_directory_uri(); ?>/images/scooter.png" align="productimage">
							</p>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<div class="cat_heading1 topgaping"><span style="background:#fcbe23">ARRIVE WITH SWAG</span></div>
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
							<p>
								<img src="<?php echo get_template_directory_uri(); ?>/images/swagboard.png" align="productimage">
							</p>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<div class="cat_heading1"><span style="background:#3d3d3d">ARRIVE WITH SWAG</span></div>
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

				<!-- Category: Electric Bikes -->
		<?php elseif( is_product_category( 'electric-bike' ) ) : ?>

			<section class="category_banner bluebgbc">
				<div class="body_container">
					<div class="row">
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6" style="text-align:center;">
							<p>
								<img src="<?php echo get_template_directory_uri(); ?>/images/ebike.png" align="productimage">
							</p>
						</div>
						<div class="col-lg-6 col-xs-12 col-sm-12 col-md-6">
							<div class="cat_heading1 topgaping" ><span style="background:#3599f1">ARRIVE WITH SWAG</span></div>
							<h1 class="cat_heading2">SwagCycle Electric Bike</h1>
							<div class="cat_pricenow"><span style="background:#3599f1">NOW $399</span></div>
							<div class="cat_deascrption">Whether you're an urban commuter, a student on campus, running errands, or just cruising around, you can now enjoy SWAGTRON’s all new electric bike.</div>
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
											<h2><a href="<?php echo get_permalink( get_the_ID() ); ?>" title="<?php echo get_the_title(); ?>">
												<img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title(); ?>">
											</a></h2>
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

					<?php include (TEMPLATEPATH . '/templates/lineup.php'); ?>

			</div>	<!-- End of .container -->
    </section>





	</div>

<?php get_footer(); ?>
