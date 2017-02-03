<?php
/**
 * Single product short description
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/short-description.php.
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

global $post;


$short_desc = ( ! $post->post_excerpt ) ? wp_trim_words( $post->post_content, 50 ) : $post->post_excerpt;
?>
<div itemprop="description" class="product_des">
	<?php echo apply_filters( 'woocommerce_short_description', $short_desc ) ?>
</div>

<div class="updown" style="display:none">
	<a id="updown-link" style="cursor:pointer"><i class="fa fa-angle-double-down"></i> Read more</a>
</div>
