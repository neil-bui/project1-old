<?php
/**
 * The template for displaying pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that
 * other "pages" on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Swagtron
 * @since Swagtron 1.0
 */

get_header(); ?>

  <!-- Start Body Wrapper -->
  <?php if( ! is_product() ) : ?>
  	<div class="body_warp">
  <?php endif; ?>	
  
	  <?php if( is_shop() ) : ?>
	  	<div class="container">
	  <?php endif; ?>	  
  
		<?php woocommerce_content(); ?>
		
		  <?php if( is_shop() ) : ?>
		  	</div>
		  <?php endif; ?>	 		
		
  <?php if( ! is_product() ) : ?>
  	</div>
  <?php endif; ?>	
  <!-- End Body Wrapper -->

<?php get_footer(); ?>
