<?php
$classes = get_body_class();
?>

  <!-- Start Footer Wrapper -->
	<div class="footer_sec">
		<div class="container">
			<div class="row">
				<div class="col-lg-3 col-sm-4 hidden-xs">
					<div class="footer_name">Information</div>
					<div class="footer_list">
						<ul>
              <li><a href="<?php echo get_permalink(62); ?>">Terms & Conditions</a></li>
              <li><a href="<?php echo get_permalink(95); ?>">Privacy Policy</a></li>
              <li><a href="<?php echo get_permalink(97); ?>">Shipping Information</a></li>
              <li><a href="<?php echo get_permalink(81067); ?>">Product Recall<br><br></a></li>
              <li><a href="mailto:<?php echo antispambot( 'corporate@swagtron.com', 1 ); ?>">Distribution/Wholesale Inquiries</a></li>
              <li><a href="mailto:<?php echo antispambot( 'marketing@swagtron.com', 1 ); ?>">Marketing Contact</a></li>
              <li><a href="mailto:<?php echo antispambot( 'media@swagtron.com', 1 ); ?>">Press/Media Inquiries</a></li>
              <li><a href="<?php echo get_permalink(1132); ?>">Join our Affiliate Program <br><br></a></li>
              <li><a href="<?php echo get_permalink(213); ?>">Warranty Registration</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-3 col-sm-4 col-xs-12">
					<div class="footer_name">Contact Info</div>
					<div class="footer_list">
						<ul>
							<li><a href="https://swagtron.com/"><span>Swagtron.com</span></a><br> 3431 William Richardson Dr., Suite F<br>South Bend IN 46628<br><em>(This is an office location, not for product purchasing or customer pickup)</em><br>
								<br>
							</li>
							<li><a href="tel:844-299-0625">844-299-0625</a><br> Mon-Fri 10:00am-8:30PM EST</li>
							<li><a href="mailto:<?php echo antispambot( 'support@swagtron.com', 1 ); ?>">support@swagtron.com</a></li>
						</ul>
					</div>
				</div>
				<div class="col-lg-3 col-sm-4 col-xs-12">
					<div class="footer_name">Swagtron Affiliate Program</div>
					<div class="footer_list">
						<ul class="login_te">
							<li><a href="https://swagtron.com/affiliate-home/affiliate-register/">Sign Up</a> or <a href="https://swagtron.com/affiliate-home/affiliate-login/">Login</a></li>
						</ul>
						<div class="footer_social">
							<ul>
                <li><a href="https://www.facebook.com/SwagTronUSA/"><i class="fa fa-facebook"></i></a></li>
                <li><a href="https://twitter.com/swagtronusa"><i class="fa fa-twitter"></i></a></li>
                <li><a href="https://www.instagram.com/swagtronusa/"><i class="fa fa-instagram"></i></a></li>
                <li><a href="https://www.youtube.com/c/SwagTronUSA"><i class="fa fa-youtube-play"></i></a></li>
                <li><a href="https://www.pinterest.com/SwagTronusa/"><i class="fa fa-pinterest-p"></i></a></li>
							</ul>
						</div>
					</div>
				</div>
				<div class="col-lg-3 col-sm-4 hidden-xs hidden-sm">
					<div class="footer_name">Secure Shopping</div>
					<div class="footer_list">
            <div class="authorzed_logo">
								<img src="<?php echo get_template_directory_uri(); ?>/images/authorize.gif" alt="authorizedlogo">
						</div>
            <div class="ssl_logo"><img src="<?php echo get_template_directory_uri(); ?>/images/ssl_logo.png" alt="ssl"></div>
<div style="clear:both;padding-top: 10px;"><img src="https://swagtron.com/wp-content/uploads/2016/11/swagtron-BBB-logo-150.png">
            <div class="footer_content">*Swagway and Swagway LLC are not authorized by, endorsed by, affiliated with or otherwise approved by Segway Inc.</div>
					</div>
				</div>
			</div>
			<div class="footer_menu">
				<ul>
          <li><a href="<?php echo site_url(); ?>">Home</a></li>
          <li><a href="<?php echo get_permalink(9); ?>">Buy Now</a></li>
          <li><a href="<?php echo get_permalink(89929); ?>">FAQ</a></li>
          <li><a href="<?php echo get_permalink(54217); ?>">Galleries</a></li>
          <li><a href="<?php echo get_permalink(54217); ?>">Images</a></li>
          <li><a href="<?php echo get_permalink(105); ?>">Video Playlists</a></li>
          <li><a href="<?php echo get_permalink(89933); ?>">Media & Press</a></li>
          <li><a href="<?php echo get_permalink(89937); ?>">Contact Us</a></li>
          <li><a href="<?php echo get_permalink(89927); ?>">Hoverboard News</a></li>
				</ul>
			</div>
		</div>
	</div>
  <!-- End Footer Wrapper -->
</div>
<!-- end Main Wrapper -->


<?php wp_footer(); ?>

<script>
$(document).ready(function() {

	$("#seen_on").owlCarousel({
		items 						: 9,
		itemsDesktop      : [1199,9],
		itemsDesktopSmall	: [979,7],
		itemsTablet       : [768,5],
		itemsMobile       : [479,3]
  });

  $(".hamberger_menu").click(function(){
    $(".navigation").animate({left: '0'});
		$(".hamberger_menuclose").show();
		$(this).hide();
  });

  $(".hamberger_menuclose").click(function(){
    $(".navigation").animate({left: '-270px'});
		$(".hamberger_menu").show();
		$(this).hide();
  });

	$("#essentialslider,#essentialslider-upsell").owlCarousel({
		items : 4,
		itemsDesktop      : [1199,4],
		itemsDesktopSmall     : [979,3],
		itemsTablet       : [768,2],
		itemsMobile       : [479,1],
		pagination:false
	});

	$('#parentHorizontalTab').easyResponsiveTabs({
		type: 'default',
		width: 'auto',
		fit: true,
		tabidentify: 'hor_1',
		activate: function(event) {
			var $tab = $(this);
			var $info = $('#nested-tabInfo');
			var $name = $('span', $info);
			$name.text($tab.text());
			$info.show();
		}
	});

	<?php if( is_product() ) : ?>
		$('.variation_buttons_wrapper').addClass('colorpanel');
		$('#pa_color_buttons, #pa_feed-color_buttons').prepend('<div class="color_lefttext">Colors:</div>');
	<?php endif; ?>

	$('#showAllComments').click(function(){
		$('ul.resp-tabs-list li[title="reviews"]').trigger('click');
	});

	if( $('.product_des').length > 0 ) {
		if( $('.product_des').height() > 160 ) {
			$('.product_des').addClass('height-160');
			$('.updown').show();
		}
	}

	$('#updown-link').click(function() {
		if( $('.updown').is(':visible') ) {
			$('.product_des').toggleClass('height-160');

			if( $('.product_des').hasClass('height-160') ) {
				$('#updown-link').html('<i class="fa fa-angle-double-down"></i> Read more');
			} else {
				$('#updown-link').html('<i class="fa fa-angle-double-up"></i> Read less');
			}
		}
	});

});


// FAQ Accordion
var acc = document.getElementsByClassName('accordion');
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].onclick = function(){
    this.classList.toggle('active');
    this.nextElementSibling.classList.toggle('show');
  }
}
</script>

<script data-cfasync="false">window.ju_num="7E488C5B-635C-408F-8848-A119FDBB58FC";window.asset_host='//cdn.justuno.com/';(function() {var s=document.createElement('script');s.type='text/javascript';s.async=true;s.src=asset_host+'vck.js';var x=document.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);})();</script>
</body>
</html>