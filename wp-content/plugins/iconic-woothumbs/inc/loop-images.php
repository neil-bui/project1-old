<?php
/**
 * Loop main slider images
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $post;

$product_settings = (array) get_post_meta($post->ID, '_iconic_woothumbs', true);
$video_url = isset( $product_settings['video_url'] ) && $product_settings['video_url'] != "" ? $product_settings['video_url'] : false;

?>

<?php if(!empty($images)) { ?>

    <?php do_action( 'iconic_woothumbs_before_images_wrap' ); ?>

    <div class="<?php echo $this->slug; ?>-images-wrap" dir="ltr">

        <?php do_action( 'iconic_woothumbs_before_images' ); ?>

    	<div class="<?php echo $this->slug; ?>-images <?php if( $this->settings['fullscreen_general_click_anywhere'] && $this->settings['fullscreen_general_enable'] ) echo $this->slug."-images--click_anywhere"; ?>">

        	<?php $i = 0; foreach($images as $image): ?>

        		<div class="<?php echo $this->slug; ?>-images__slide <?php if($i == 0) echo $this->slug."-images__slide--active"; ?>" data-index="<?php echo $i; ?>">

        		    <?php
                    $src = $i == 0 ? $image['single'][0] : "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=";
                    $data_src = $i == 0 ? false : $image['single'][0];
                    $aspect = $i == 0 ? false : ($image['single'][2]/$image['single'][1])*100;
                    $srcset = isset( $image['single']['retina'][0] ) ? sprintf('data-srcset="%s, %s 2x"', $image['single'][0], $image['single']['retina'][0]) : "";
                    ?>

        			<img class="<?php echo $this->slug; ?>-images__image" src="<?php echo $src; ?>" <?php echo $srcset; ?> <?php if($data_src) printf('data-iconic-woothumbs-src="%s"', $data_src); ?> data-large-image="<?php echo $image['large'][0]; ?>" data-large-image-width="<?php echo $image['large'][1]; ?>" data-large-image-height="<?php echo $image['large'][2]; ?>" title="<?php echo $image['title']; ?>" alt="<?php echo $image['alt']; ?>" width="<?php echo $image['single'][1]; ?>" height="<?php echo $image['single'][2]; ?>" <?php if($aspect) printf('style="padding-top: %s%%; height: 0px;"', $aspect); ?> />

        		</div>

        	<?php $i++; endforeach; ?>

    	</div>

    	<?php if( $this->settings['fullscreen_general_enable'] ) { ?>
    	    <a href="javascript: void(0);" class="iconic-woothumbs-fullscreen" data-iconic-woothumbs-tooltip="<?php _e('Fullscreen', 'iconic-woothumbs'); ?>"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-fullscreen"></i></a>
    	<?php } ?>

    	<?php if( $video_url ) { ?>
    	    <a href="javascript: void(0);" class="iconic-woothumbs-play" data-iconic-woothumbs-tooltip="<?php _e('Play Video', 'iconic-woothumbs'); ?>"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-play"></i></a>
    	<?php } ?>

    	<div class="<?php echo $this->slug; ?>-loading-overlay"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-loading"></i></div>

    	<?php do_action( 'iconic_woothumbs_after_images' ); ?>

    </div>

    <?php if( $video_url ) { ?>

        <div id="iconic-woothumbs-video-template" style="display: none;">

            <div class="iconic-woothumbs-video-wrapper">
                <div class="iconic-woothumbs-responsive-video">
                    <?php echo wp_oembed_get( $video_url ); ?>
                </div>
            </div>

        </div>

    <?php } ?>

    <?php do_action( 'iconic_woothumbs_after_images_wrap' ); ?>

<?php } ?>