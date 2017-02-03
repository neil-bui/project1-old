<?php
/**
 * Related Products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/related.php.
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
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product, $woocommerce_loop;
$cart_url = WC()->cart->get_cart_url();

if ( empty( $product ) || ! $product->exists() ) {
	return;
}

if ( ! $related = $product->get_related( $posts_per_page ) ) {
	return;
}

$args = apply_filters( 'woocommerce_related_products_args', array(
	'post_type'            => 'product',
	'ignore_sticky_posts'  => 1,
	'no_found_rows'        => 1,
	'posts_per_page'       => $posts_per_page,
	'orderby'              => $orderby,
	'post__in'             => $related,
	'post__not_in'         => array( $product->id )
) );

$products                    = new WP_Query( $args );
$woocommerce_loop['name']    = 'related';
$woocommerce_loop['columns'] = apply_filters( 'woocommerce_related_products_columns', $columns );

if ( $products->have_posts() ) : ?>

	<section class="related products product_essentials">

		<div class="essentials_heading"><?php _e( 'The Swagtron Essentials', 'woocommerce' ); ?></div>

		<div class="row">
			<div id="essentialslider" class="owl-carousel">

			<?php //woocommerce_product_loop_start(); ?>

				<?php while ( $products->have_posts() ) : $products->the_post(); ?>

					<?php //wc_get_template_part( 'content', 'product' ); ?>

					<?php $_product = wc_get_product( get_the_ID() ); ?>

					<div class="item">
						<div class="col-lg-12">
							<div class="essentials_productrow">
								<?php if ( $_product->is_on_sale() ) : ?>
									<div class="saletag">Sale</div>
								<?php endif; ?>
								<div class="essentials_productimg">
									<a href="<?php echo get_permalink( get_the_ID() ); ?>">
										<img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title(); ?>">
									</a>
								</div>
								<div class="essentials_productname">
									<a href="<?php echo get_permalink( get_the_ID() ); ?>"><?php echo get_the_title(); ?></a>
								</div>
								<div class="essentials_bottomsec">
									<div class="pricetag">
										<?php
											$parts = explode('.', $_product->get_price());
											$price_part = empty( $parts[0] ) ? '00' : $parts[0];
											$fraction_part = empty( $parts[1] ) ? '00' : $parts[1];
											echo '<sup>' . get_woocommerce_currency_symbol() . '</sup>' . $price_part . '.<sup>' . $fraction_part . '</sup>';
										?>
									</div>
									<div class="adtocart_btn">
										<a href="<?php echo get_permalink( get_the_ID() ); ?>">ADD TO CART</a>
									</div>
								</div>
							</div>
						</div>
					</div>

				<?php endwhile; // end of the loop. ?>

				<?php wp_reset_query(); ?>

			<?php //woocommerce_product_loop_end(); ?>

			</div>
		</div>

	</section>

<?php endif;

wp_reset_postdata();
