<?php
	// Checking user is logged in or not
	//if( ! is_user_logged_in() ) {
	//
	//	// Checking if it is checkout page
	//	if( is_checkout() && ! isset( $_SESSION['move_to_cart'] ) ) {
	//
	//		wp_redirect( get_permalink( 89940 ) );
	//		exit;
	//
	//		// Checking if cart item total is greated than zero
	//		if( WC()->cart->get_cart_contents_count() > 0 ) {
	//
	//			wp_redirect( WC()->cart->get_checkout_url() );
	//			exit;
	//
	//		}	else {
	//			wp_redirect( WC()->cart->get_cart_url() );
	//			exit;
	//
	//		}
	//
	//	}
	//}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/images/favicon-16x16.png">
<?php wp_head(); ?>

<!--Start of Zendesk Chat Script-->
<script type="text/javascript">
window.$zopim||(function(d,s){var z=$zopim=function(c){z._.push(c)},$=z.s=
d.createElement(s),e=d.getElementsByTagName(s)[0];z.set=function(o){z.set.
_.push(o)};z._=[];z.set._=[];$.async=!0;$.setAttribute("charset","utf-8");
$.src="https://v2.zopim.com/?3G8F6VmqgFim0eNWkOi3bbKkYaJvZnv5";z.t=+new Date;$.
type="text/javascript";e.parentNode.insertBefore($,e)})(document,"script");
</script>
<!--End of Zendesk Chat Script-->
<script type="text/javascript">
adroll_adv_id = "JPT2NB4WORCQFGEDIZZQG4";
adroll_pix_id = "NZFAOQU73BC2PKUQX4TOTC";


// If this is an order-received page, will fill in these additional values for enhanced conversion tracking.
adroll_conversion_value = "";
adroll_currency = "";
adroll_email = "";
adroll_custom_data = {
    "ORDER_ID": "",
    "USER_ID": ""
};
adroll_product_id = "";
adroll_checkout_ids = [];

var oldonload = window.onload;
window.onload = function(){
    __adroll_loaded=true;
    var scr = document.createElement("script");
    var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
    scr.setAttribute('async', 'true');
    scr.type = "text/javascript";
    scr.src = host + "/j/roundtrip.js";
    ((document.getElementsByTagName('head') || [null])[0] ||
    document.getElementsByTagName('script')[0].parentNode).appendChild(scr);
    if(oldonload){oldonload()}
}();
</script>
<style>
  
  /* TEMP HOMEPAGE SOLUTION FOR IMAGES ON HERO */

body.home div.iconic-woothumbs-images__slide  {
  display: none;
}
body.home div.iconic-woothumbs-images__slide--active {
  display: block;
}
body.home div.iconic-woothumbs-thumbnails {
  display: none;
}

</style>
</head>

<body <?php body_class(); ?> >
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WCX7HZ');</script>
<!-- End Google Tag Manager -->
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WCX7HZ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<!-- Start Main Wrapper -->
<div class="main_warp">
<!-- Start Header -->
  <div class="header">
    <div class="top_header">
      <div class="body_container">
        <div class="row">
          <div class="col-lg-5">
            <div class="flag_icon"><img src="<?php echo get_template_directory_uri(); ?>/images/us_flag.png" alt="flag"></div>
            <div class="top_menu">
              <ul>
                <li><a href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>">My Account</a></li>
                <li><a href="<?php echo get_permalink(213); ?>">Warranty Reg.</a></li>
                <li><a href="<?php echo WC()->cart->get_checkout_url(); ?>">Checkout</a></li>
								<?php if( is_user_logged_in() ) : ?>
									<li>
										<a href="<?php echo wp_logout_url( get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) ?>">Logout</a>
									</li>
								<?php endif; ?>
              </ul>
            </div>
          </div>
          <div class="col-lg-7">
            <div class="cart_icon">
							<a href="<?php echo wc_get_cart_url(); ?>">
								<img src="<?php echo get_template_directory_uri(); ?>/images/cart_icon.png" alt="cart">
							</a>
						</div>
            <div class="social_icon">
              <ul>
                <li><a href="https://www.facebook.com/SwagTronUSA/"><i class="fa fa-facebook"></i></a></li>
                <li><a href="https://twitter.com/swagtronusa"><i class="fa fa-twitter"></i></a></li>
                <li><a href="https://www.instagram.com/swagtronusa/"><i class="fa fa-instagram"></i></a></li>
                <li><a href="https://www.youtube.com/c/SwagTronUSA"><i class="fa fa-youtube-play"></i></a></li>
                <li><a href="https://www.pinterest.com/SwagTronusa/"><i class="fa fa-pinterest-p"></i></a></li>
              </ul>
            </div>
            <div class="topright_menu">
              <ul>
                <li><a href="/faq-page/">FAQ</a></li>
                <li><a href="/news/">News</a></li>
                <li><a href="/media/">Press</a></li>
                <li><a href="/contact/">Contact Us</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="menu_header">
      <div class="body_container">
      <div class="hamberger_menu"><img src="<?php echo get_template_directory_uri(); ?>/images/hamberger.png"></div>
        <div class="hamberger_menuclose"><img src="<?php echo get_template_directory_uri(); ?>/images/hamberger_close.png"></div>
        <div class="mobile_logo">
					<a href="<?php echo site_url(); ?>">
						<img src="<?php echo get_template_directory_uri(); ?>/images/mobile_logo.png" alt="logo">
					</a>
				</div>
        <div class="mobile_carticon">
					<a href="<?php echo wc_get_cart_url(); ?>">
						<img src="<?php echo get_template_directory_uri(); ?>/images/mobile_carticon.png" >
					</a>
				</div>
        <div class="navigation">
          <ul>
          <li class="mobilelogo_collaps">
							<a href="<?php echo site_url(); ?>">
								<img src="<?php echo get_template_directory_uri(); ?>/images/menulogo.png" alt="logo">
							</a>
						</li>
            <li><a href="<?php echo get_term_link(53); ?>">Hoverboards</a>
              <ul>
                <li><a href="/product/swagtron-t5-hoverboard/">SwagTron T5</a></li>
                <li><a href="<?php echo get_term_link(54); ?>">SwagTron T3</a></li>
                <li><a href="<?php echo get_term_link(47); ?>">SwagTron T1</a></li>
                <li><a href="<?php echo get_permalink( 133905 ); ?>">X1 S&D $149</a></li>
                <li><a href="<?php echo get_permalink( 133910 ); ?>">X2 S&D $149</a></li>
              </ul>
            </li>
            
            <li><a href="<?php echo get_term_link(60); ?>">Electric Scooters</a>
              <ul>
                <li><a href="/product/swagger-carbon-fiber-electric-scooter">Swagger Scooter</a></li>
              </ul>
            </li>
            
           <li><a href="<?php echo get_term_link(59); ?>">Electric Skateboards</a>
              <ul>
                <li><a href="/product/swagboard-ng-1-nextgen-electric-skateboard/">Swagboard</a></li>
                <li><a href="/product/swagtron-voyager-professional-42-electric-longboard-with-remote-control/">Voyager Longboard</a></li>
              </ul>
            </li>
            <li class="homelogo" style="margin-right:20px; padding:0">
							<a href="<?php echo site_url(); ?>">
								<img src="<?php echo get_template_directory_uri(); ?>/images/logo.png" alt="logo">
							</a>
						</li>
            <li>
              <a href="/product-category/electric-bike/">Electric Bikes</a>
              <ul>
                <li>
                  <a href="/product/swagcycle-e-bike-folding-electric-bicycle-by-swagtron/">SwagCycle E-Bike</a>
                </li>
              </ul>
            </li> 
            <li>
              <a href="<?php echo get_term_link(52); ?>">Parts & Accessories</a>
                <ul>
                  <li>
                    <a href="<?php echo get_term_link(49); ?>">Clothing & Bags</a>
                  </li>
                  <li>
                    <a href="/product/1-year-accidental-damage-warranty/">Accidental Warranty</a>
                  </li>
                </ul>

            </li>
            <li>
              <a href="<?php echo get_term_link(51); ?>">CLEARANCE</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <!-- End Header -->