<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-WCX7HZ');</script>
  <!-- End Google Tag Manager -->

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<link rel="shortcut icon" href="<?php echo get_template_directory_uri(); ?>/images/favicon-16x16.png">
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
</head>
<body <?php body_class(); ?> >

  <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WCX7HZ"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<div class="main_warp">
  <div class="landing_header">
    <div class="container">
      <div class="row">
        <div class="col-lg-10 col-xs-9 col-sm-10">
          <div class="landing_logo">
						<a href="<?php echo site_url(); ?>">
							<img src="<?php echo get_template_directory_uri(); ?>/images/landing_logo.png" alt="logo">
						</a>
					</div>
          <div class="top_menu landingdev">
            <ul>
              <li><a href="<?php echo get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ); ?>">My Account</a></li>
              <li><a href="<?php echo get_permalink(213); ?>">Warranty Reg.</a></li>
              <li><a href="<?php echo WC()->cart->get_checkout_url(); ?>">Checkout</a></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-2 col-xs-3 col-sm-2">
          <div class="landingcart_icon">
						<a href="<?php echo wc_get_cart_url(); ?>">
							<img src="<?php echo get_template_directory_uri(); ?>/images/landing_carticon.png" alt="cart">
						</a>
					</div>
        </div>
      </div>
    </div>
  </div>
