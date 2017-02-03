<?php
//  Removing Admin Bar
add_filter('show_admin_bar', '__return_false');

//  Remove unnecessary meta tags from WordPress header
remove_action( 'wp_head', 'wp_generator' ) ;
remove_action( 'wp_head', 'wlwmanifest_link' ) ;
remove_action( 'wp_head', 'rsd_link' ) ;

// Remove WordPress' canonical links
remove_action('wp_head', 'rel_canonical');

// Removes the index link
remove_action( 'wp_head', 'index_rel_link');

// Removes the prev link 
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0);
remove_action( 'wp_head', 'previous_post_rel_link', 10, 0);
remove_action( 'wp_head', 'next_post_rel_link', 10, 0); 

// Removes the start link
remove_action( 'wp_head', 'start_post_rel_link', 10, 0); 

// Removes the relational links for the posts adjacent to the current post.
remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0); 
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10,0);

//  Hide the non-essential WordPress RSS Feeds
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

// Remove the Version Parameter from Scripts
function script_loader_src_example( $src ) {
    return remove_query_arg( 'ver', $src );
} 
add_filter( 'script_loader_src', 'script_loader_src_example' );
add_filter( 'style_loader_src', 'script_loader_src_example' );