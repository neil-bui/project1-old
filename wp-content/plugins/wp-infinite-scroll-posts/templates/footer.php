<?php
/**
 * @package wp_easy_scroll_posts
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<script type="text/javascript">
wp_easy_scroll_posts = jQuery.parseJSON(wp_easy_scroll_posts);
jQuery( wp_easy_scroll_posts.contentSelector ).infinitescroll( wp_easy_scroll_posts);
</script>

