<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-single-product.php.
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
	exit; // Exit if accessed directly
}

global $product;
$comments_count = wp_count_comments( get_the_ID() );
$cart_url = WC()->cart->get_cart_url();
$classes = get_body_class();

// Getting variation id
if( $product->product_type == 'variable' ) {
	$variations = $product->get_available_variations();
	$attribute_name = 'pa_feed-color';
	$selected_attr = $product->get_variation_default_attribute( $attribute_name );


	if( isset( $_GET['attribute_pa_feed-color'] ) ) {	// grabing color from GET parameter

		$var_color = sanitize_text_field( $_GET['attribute_pa_feed-color'] );

		if( count( $variations ) ) : $index = 0;
			foreach( $variations as $variation ) :
				if( ( $variation['attributes']['attribute_pa_feed-color'] == $var_color ) ) {
					$variation_index = $index;
					$variation_id = $variation['variation_id'];
				}
				$index++;
			endforeach;
		endif;

	} elseif( ! empty( $selected_attr ) ) {	// grabing default color value

		$var_color = $selected_attr;

		if( count( $variations ) ) : $index = 0;
			foreach( $variations as $variation ) :
				if( $variation['attributes']['attribute_pa_feed-color'] == $selected_attr ) {
					$variation_index = $index;
					$variation_id = $variation['variation_id'];
				}
				$index++;
			endforeach;
		endif;

	}
}
?>

<div class="hidden">
	<p>
		<?php //echo 'selected: ' . $selected_attr; ?>
	</p>
	<pre>
		<?php //print_r( $product ); ?>
	</pre>
	<pre>
		<?php //print_r( $variations ); ?>
	</pre>
</div>

<?php
	/**
	 * woocommerce_before_single_product hook.
	 *
	 * @hooked wc_print_notices - 10
	 */
	 do_action( 'woocommerce_before_single_product' );

	 if ( post_password_required() ) {
	 	echo get_the_password_form();
	 	return;
	 }
?>

<div itemscope itemtype="<?php echo woocommerce_get_product_schema(); ?>" id="product-<?php the_ID(); ?>" <?php post_class(); ?>>
	<section class="product_detailstop">
		<div class="row">

		<?php if( is_front_page() ) : ?>
			<div class="col-lg-5 col-xs-12 col-sm-6 text-center">
				<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
			</div>
			<div class="col-lg-6 col-xs-12 col-sm-6 float_right">
				<?php do_action( 'woocommerce_single_product_summary' ); ?>
			</div>
		<?php else: ?>

			<div class="col-lg-7 col-xs-12 col-md-7">

				<?php if( in_array( 'page-template-product-landing', $classes ) ) : ?>
					<div class="pickmodeltext">Pick Your Model</div>
					<div class="modeltabsec">
						<ul>
							<li <?php echo is_page( 112237 ) ? 'class="active"' : ''; ?> >
								<a href="<?php echo get_permalink( 112237 ); ?>">SWAGGER</a>
							</li>
							<li <?php echo is_page( 112241 ) ? 'class="active"' : ''; ?> >
								<a href="<?php echo get_permalink( 112241 ); ?>">SWAGBOARD</a>
							</li>
							<li <?php echo is_page( 89966 ) ? 'class="active"' : ''; ?> >
								<a href="<?php echo get_permalink( 89966 ); ?>">SWAGTRON T3</a>
							</li>
							<li <?php echo is_page( 91688 ) ? 'class="active"' : ''; ?> >
								<a href="<?php echo get_permalink( 91688 ); ?>">SWAGTRON T5</a>
							</li>
						</ul>
					</div>
				<?php endif; ?>

				<?php
					/**
					 * woocommerce_before_single_product_summary hook.
					 *
					 * @hooked woocommerce_show_product_sale_flash - 10
					 * @hooked woocommerce_show_product_images - 20
					 */
					do_action( 'woocommerce_before_single_product_summary' );
				?>

			</div>

			<div class="col-lg-5 col-xs-12 col-md-5">

				<?php
					/**
					 * woocommerce_single_product_summary hook.
					 *
					 * @hooked woocommerce_template_single_title - 5
					 * @hooked woocommerce_template_single_rating - 10
					 * @hooked woocommerce_template_single_price - 10
					 * @hooked woocommerce_template_single_excerpt - 20
					 * @hooked woocommerce_template_single_add_to_cart - 30
					 * @hooked woocommerce_template_single_meta - 40
					 * @hooked woocommerce_template_single_sharing - 50
					 */
					do_action( 'woocommerce_single_product_summary' );
				?>

			</div>

		<?php endif; ?>

		</div>

			<?php
				/**
				 * woocommerce_after_single_product_summary hook.
				 *
				 * @hooked woocommerce_output_product_data_tabs - 10
				 * @hooked woocommerce_upsell_display - 15
				 * @hooked woocommerce_output_related_products - 20
				 */
				//do_action( 'woocommerce_after_single_product_summary' );
			?>

			<meta itemprop="url" content="<?php the_permalink(); ?>" />

	</section>

	<?php if( ! is_front_page() ) : ?>
		<?php /*
					<section class="age_restrictedsec">
						<div class="row">
							<div class="col-lg-3 col-xs-12 col-sm-4 col-md-4">
								<div class="agepic"><img src="<?php echo get_template_directory_uri(); ?>/images/age_restic1.png"></div>
								<div class="ageres_name">Ages 7+</div>
							</div>
							<div class="col-lg-3 col-xs-12 col-sm-4 col-md-4">
								<div class="agepic"><img src="<?php echo get_template_directory_uri(); ?>/images/age_restic2.png"></div>
								<div class="ageres_name">11mi Range/8+MPH</div>
							</div>
							<div class="col-lg-3 col-xs-12 col-sm-4 col-md-4">
								<div class="agepic"><img src="<?php echo get_template_directory_uri(); ?>/images/age_restic3.png"></div>
								<div class="ageres_name">Holds 220lb</div>
							</div>
						</div>
					</section>
		*/  ?>
<?php /*
			<section class="productdetails_middleproductsec">
				<h2 class="middle_producttitle"><?php echo get_the_title() ?></h2>
				<div class="row">
					<div class="col-lg-6 col-xs-12 col-sm-6 col-md-6 hoverboard_img">
						<?php if( isset( $variation_index ) ) : ?>
							<h3><img src="<?php echo $variations[$variation_index]['image_link']; ?>" alt="<?php echo get_the_title() ?>"></h3>
						<?php else : ?>
							<h3><img src="<?php echo the_post_thumbnail_url(); ?>" alt="<?php echo get_the_title() ?>"></h3>
						<?php endif; ?>
					</div>
					<div class="col-lg-6 col-xs-12 col-sm-6 col-md-6">
						<div class="hoverboard_price">
							<span class="hoverbord_leftprice">
								<?php
									$var_price = isset( $variation_id ) ? $variations[$variation_index]['display_price'] : $product->get_price();
									$parts = explode( '.', $var_price );
									$price_part = empty( $parts[0] ) ? '00' : $parts[0];
									$fraction_part = empty( $parts[1] ) ? '00' : $parts[1];
									echo '<sup>' . get_woocommerce_currency_symbol() . '</sup>' . $price_part . '.<sup>' . $fraction_part . '</sup>';
								?>
							</span>
							<span class="freeshipping">(free shipping)</span>
						</div>
						<div class="simple_addtocartbtn">
							<?php
								// Building url
								$add_to_cart_url = isset( $variation_id ) ? esc_url( add_query_arg( array( 'add-to-cart' => get_the_ID(), 'variation_id' => $variation_id, 'attribute_pa_feed-color' => $var_color, 'quantity' => 1 ), $cart_url ) ) : esc_url( add_query_arg( array( 'add-to-cart' => get_the_ID() ), $cart_url ) );
							?>
							<a href="<?php echo $add_to_cart_url; ?>">ADD TO CART</a>
						</div>
						<div class="hoverboard_description">
							<?php the_excerpt(); ?>
						</div>
					</div>
				</div>

				<!-- Comments -->
				<?php
					// Getting one 5 star comment
					$args = array(
							'orderby' 		=> 'date',
							'post_type' 	=> 'product',
							'number' 			=> '1',
							'post_id' 		=> get_the_ID(),
							'status'			=> 'approve',
							'type'				=> 'comment',
							'meta_key' 		=> 'rating',
							'meta_value' 	=> 5,
					);
					$five_star_comment = get_comments($args);

					//echo '<pre>'; print_r( $five_star_comment ); echo '</pre>';


					// Getting all 4 comments
					$args = array(
							'orderby' 		=> 'date',
							'post_type' 	=> 'product',
							'number' 			=> '3',
							'post_id' 		=> get_the_ID(),
							'status'			=> 'approve',
							'type'				=> 'comment'
					);

					if( isset( $five_star_comment[0] ) ) {
						$args['comment__not_in'] = $five_star_comment[0]->comment_ID;
					}

					$comments = get_comments($args);

					//echo '<pre>'; print_r( $comments ); echo '</pre>';
				?>
				<?php if( count( $comments ) || count( $five_star_comment ) ) : ?>
					<div class="row">
						<div class="col-lg-6 col-sm-6 col-md-6 hidden-xs">
							<div class="testimonial_sec">

								<?php if( isset( $comments[0] ) ) : ?>
									<div class="testimonial_text"><?php echo ucfirst( wp_trim_words( $comments[0]->comment_content, 20 ) ); ?></div>
									<div class="testimonial_name"> SWAGTRON Customer: <?php echo $comments[0]->comment_author; ?> <br>
										<span class="date"><?php echo mysql2date( 'm.d.Y', $comments[0]->comment_date ); ?></span>
									</div>
								<?php endif; ?>

							</div>
						</div>
						<div class="col-lg-6 col-sm-6 col-md-6 hidden-xs border_left">
							<div class="Swagtronreview_heading">Swagtron Reviews</div>

							<?php if( isset( $five_star_comment[0] ) ) : ?>

								<div class="swagtronreview_subcomponent">
									<?php $comment_rating = get_comment_meta( $five_star_comment[0]->comment_ID, 'rating', true ) ?>
									<div class="reviewstar">
										<?php for( $i = 0; $i < $comment_rating; $i ++ ) : ?>
											<img src="<?php echo get_template_directory_uri(); ?>/images/fill_star.png">
										<?php endfor; ?>
									</div>
									<div class="reviewcount_text">
										<?php echo sprintf( _n( '(%s) Review', '(%s) Reviews', $comments_count->approved, 'swagtron' ), $comments_count->approved); ?>
									</div>
								</div>
								<div class="coustomerreview_fullrow">
									<div class="coustomerreview_fullrow_text"><?php echo ucfirst( wp_trim_words( $five_star_comment[0]->comment_content, 15 ) ); ?></div>
									<div class="coustomerreview_fullrow_name">SWAGTRON Customer: <?php echo $five_star_comment[0]->comment_author; ?> <span class="date"><?php echo mysql2date( 'm.d.Y', $five_star_comment[0]->comment_date ); ?></span></div>
								</div>

							<?php endif; ?>

							<div class="row">
								<div class="col-lg-6 col-sm-12 col-md-6">
									<?php if( isset( $comments[1] ) ) : ?>
										<div class="coustomer_reviewcontent"><?php echo ucfirst( wp_trim_words( $comments[1]->comment_content, 15 ) ); ?></div>
										<div class="coustomer_name">SWAGTRON Customer: <?php echo $comments[1]->comment_author; ?> <br>
											<span class="date"><?php echo mysql2date( 'm.d.Y', $comments[1]->comment_date ); ?></span>
										</div>
									<?php endif; ?>
								</div>
								<div class="col-lg-6 col-sm-12 col-md-6 border_left">
									<?php if( isset( $comments[2] ) ) : ?>
										<div class="coustomer_reviewcontent"><?php echo ucfirst( wp_trim_words( $comments[2]->comment_content, 15 ) ); ?></div>
										<div class="coustomer_name">SWAGTRON Customer: <?php echo $comments[2]->comment_author; ?> <br>
											<span class="date"><?php echo mysql2date( 'm.d.Y', $comments[2]->comment_date ); ?></span>
										</div>
									<?php endif; ?>
								</div>
							</div>
							<div class="viewall_reviewlink">
								<a id="showAllComments" style="cursor:pointer">View All Reviews (<?php echo $comments_count->approved; ?>)</a>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</section>
*/ ?>
			<?php //echo '<pre>'; print_r( $comments_count ); echo '</pre>'; ?>

			<!-- Tabs -->
			<section class="product_featuresec">
				<?php $tabs = apply_filters( 'woocommerce_product_tabs', array() ); ?>

				<?php if ( ! empty( $tabs ) ) : ?>

					<div id="parentHorizontalTab">
						<ul class="resp-tabs-list hor_1">

							<?php if( in_array( 'page-template-product-landing', $classes ) ) : ?>
								<li>Hoverboard Comparison</li>
							<?php endif; ?>

							<?php foreach ( $tabs as $key => $tab ) : ?>
								<li title="<?php echo esc_attr( $key ); ?>">
									<?php echo apply_filters( 'woocommerce_product_' . $key . '_tab_title', esc_html( $tab['title'] ), $key ); ?>
								</li>
							<?php endforeach; ?>
						</ul>
						<div class="resp-tabs-container hor_1">
							<?php if( in_array( 'page-template-product-landing', $classes ) ) : ?>
								<div id="comparison">
									<table width="100%" border="0" cellspacing="0" cellpadding="0" class="compare_table">
										<tbody>
											<tr>
												<td class="table_leftpanel"><div class="comparison_imagesec">
														<div class="comparison_pricetag"><sup>$</sup>349.<sup>99</sup></div>
														<div class="comparison_priceproductimg smllimg"><img src="<?php echo get_template_directory_uri(); ?>/images/compare_img.png"></div>
													</div></td>
												<td class="table_rightpanel"><div class="comparison_imagesec">
														<div class="comparison_pricetag"><sup>$</sup>449.<sup>99</sup></div>
														<div class="comparison_priceproductimg"><img src="<?php echo get_template_directory_uri(); ?>/images/compare_img2.png"></div>
													</div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="feature_heading">Features</div>
													<div class="comparison_firstproductname">SwagTron T1 Hoverboard</div></td>
												<td class="table_rightpanel"><div class="comparison_secndproductname">SwagTron T3 Hoverboard</div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse1"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/certified_image.png"></div>
														<div class="name">UL2722 Certified</div>
														<div id="collapse1" class="panel-collapse collapse"> UL is a global independent safety science company offering expertise across three strategic businesses: Commercial & Industrial, Consumer and UL Ventures. Our breadth, established objectivity and proven history mean we are a symbol of trust, enabling us to help provide peace </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse2"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/footgrip_image.png"></div>
														<div class="name">Non-slip Pads/Foot Grips</div>
														<div id="collapse2" class="panel-collapse collapse"> Firm, rubberized foot grips offer superior traction and provide added stability control for the T3 for maneuvering and acceleration.  Step Firmly. You lean – it responds! </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse3"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/frame_image.png"></div>
														<div class="name">Incombustible Frame</div>
														<div id="collapse3" class="panel-collapse collapse"> The T3 passed the E482805 full range of heat/fire/safety protocol tests. No fires here! </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse4"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/battery_image.png"></div>
														<div class="name">Sentry Shield Battery</div>
														<div id="collapse4" class="panel-collapse collapse"> Swagtron T3 developed the patented Sentry Shield™ multilayer electronics/battery protective exterior aluminum alloy shell.  </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse5"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/learningmode_image.png"></div>
														<div class="name">Learning Mode</div>
														<div id="collapse5" class="panel-collapse collapse"> Learn to ride before you fly! Swagtron T3 offers an exclusive Learning Mode to assist beginners learning before moving on to the advanced mode and greater speeds. </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse6"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/bluetooth_image.png"></div>
														<div class="name">Bluetooth Speakers</div>
														<div id="collapse6" class="panel-collapse collapse"> Rock while you Roll! Pair up your Bluetooth device to the hoverboard and roll to your favorite tunes with 2 high quality speakers. </div>
													</div>
													<div class="verified_icon"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse7"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/app_image.png"></div>
														<div class="name">App</div>
														<div id="collapse7" class="panel-collapse collapse"> View your route history, change riding mode, or check your battery life with the Swagtron App that connects directly to your hoverboard! </div>
													</div>
													<div class="verified_icon"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse8"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/warreanty_image.png"></div>
														<div class="name">1 Year Warranty</div>
														<div id="collapse8" class="panel-collapse collapse"> Your purchase is protected! Swagtron offers a 1 year limited warranty on all hoverboards. </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
											<tr>
												<td class="table_leftpanel"><div class="plus_icon"><a data-toggle="collapse" data-parent="#accordion" href="#collapse9"><img src="<?php echo get_template_directory_uri(); ?>/images/plus_icon.png"></a></div>
													<div class="comparsion_namelist">
														<div class="icon"><img src="<?php echo get_template_directory_uri(); ?>/images/led_image.png"></div>
														<div class="name">LED Headlights</div>
														<div id="collapse9" class="panel-collapse collapse"> Sharp LED lights not only guide the way, but additional lights provide indicators front and back for directional changes and reverse movements.  </div>
													</div>
													<div class="verified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
												<td class="table_rightpanel" valign="top"><div class="rightverified_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/feature_dot.png"></div></td>
											</tr>
										</tbody>
									</table>
								</div>
							<?php endif; ?>

							<?php foreach ( $tabs as $key => $tab ) : ?>
								<div id="tab-<?php echo esc_attr( $key ); ?>">
									<?php call_user_func( $tab['callback'], $key, $tab ); ?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

				<?php endif; ?>

			</section>

			<?php echo do_shortcode('[related_products per_page="4"]'); ?>

	<?php endif; ?>


</div><!-- #product-<?php the_ID(); ?> -->

<?php do_action( 'woocommerce_after_single_product' ); ?>
