<?php
/**
 * Handles all admin ajax interactions for the Envira Albums plugin.
 *
 * @since 1.0.0
 *
 * @package Envira_Albums
 * @author  Tim Carr
 */

add_action( 'wp_ajax_envira_albums_sort_galleries', 'envira_albums_sort_galleries' );
/**
 * Saves the sort order and metadata of galleries in an album.
 *
 * Fired via Javascript when a Gallery is added, moved or deleted from an Album.
 *
 * @since 1.0.0
 */
function envira_albums_sort_galleries() {

    // Run a security check first.
    check_ajax_referer( 'envira-albums-sort', 'nonce' );

    // Check variables exist
    if ( ! isset( $_POST['post_id']) ) {
        wp_send_json_error( __( 'No Album ID specified!', 'envira-albums' ) );
    }
    if ( ! isset( $_POST['gallery_ids']) ) {
        wp_send_json_error( __( 'No Gallery IDs specified!', 'envira-albums' ) );
    }
    if ( ! isset( $_POST['galleries']) ) {
        wp_send_json_error( __( 'No galleries specified!', 'envira-albums' ) );
    }

    // Prepare variables.
    $post_id = absint( $_POST['post_id'] );
    $gallery_ids = $_POST['gallery_ids'];

    // Make galleries an associative array.
    $galleries = array();
    foreach ( $_POST['galleries'] as $gallery ) {
        $galleries[ $gallery['id'] ] = $gallery;
    }

    // Get post meta
    $data = get_post_meta( $post_id, '_eg_album_data', true );
    if ( !$data || !is_array( $data ) ) { $data = array(); }

    // Update galleryIDs
    $data['galleryIDs'] = $gallery_ids;
    $data['galleries'] = $galleries;

    // Update post meta
    update_post_meta( $post_id, '_eg_album_data', $data );

    // Send back the response.
    wp_send_json_success( $gallery_ids );
    die;

}

add_action( 'wp_ajax_envira_albums_search_galleries', 'envira_albums_search_galleries' );
/**
 * Searches for Galleries based on the given search terms
 *
 * @since 1.1.0.3
 */
function envira_albums_search_galleries() {

    // Run a security check first.
    check_ajax_referer( 'envira-albums-search', 'nonce' );

    // Check variables exist
    if ( ! isset( $_POST['post_id'] ) ) {
        wp_send_json_error( __( 'Missing post_id parameter' ) );
    }
    if ( ! isset( $_POST['search_terms'] ) ) {
        wp_send_json_error( __( 'Missing search_terms parameter' ) );
    }

    // Prepare variables.
    $post_id = absint( $_POST['post_id'] );
    $search_terms = (string) $_POST['search_terms'];

    // Get post meta
    $album_data = get_post_meta( $post_id, '_eg_album_data', true );

    // Run query
    $arguments = array(
        'post_type'         => 'envira',
        'post_status'       => 'publish',
        'orderby'           => 'title',
        'order'             => 'ASC',
    );

    // Exclude galleries we already included in this album
    if ( isset( $album_data['galleryIDs'] ) ) {
        $arguments['post__not_in'] = $album_data['galleryIDs'];
    }

    // Search will be either blank (because the user has removed their search term), or at least
    // 3 characters.  If blank, just return the 10 most recent galleries. Otherwise, return all galleries
    // matching the search terms
    if ( !empty( $search_terms ) ) {
        $arguments['s']                 = $_POST['search_terms'];
        $arguments['posts_per_page']    = -1;
    } else {
        $arguments['posts_per_page']    = 10;
    }

    // Get galleries
    $galleries = new WP_Query( $arguments );
    if ( count( $galleries->posts ) == 0 ) {
        echo '<li>' . __( 'No Galleries found matching the given search terms.', 'envira-albums' ) . '</li>';
        die();
    }

    // Build output
    $instance = Envira_Gallery::get_instance();
    $metabox = Envira_Albums_Metaboxes::get_instance();

    ob_start();
    foreach ( $galleries->posts as $gallery ) {

        // Get Gallery
        $data = $instance->get_gallery( $gallery->ID );

        // Skip Default and Dynamic Galleries
        if ( isset( $data['config']['type'] ) ) {
            if ( $data['config']['type'] == 'dynamic' || $data['config']['type'] == 'defaults' ) {
                continue;
            }
        }

        // Build item array comprising of gallery metadata
        $item = array(
            'id'                => $data['id'],
            'title'             => $data['config']['title'],
            'caption'           => $data['config']['description'],
        );

        // Output <li> element with media modal
        $metabox->output_gallery_li( $gallery->ID, $item, $post_id );
    }
    $html = ob_get_clean();

    echo $html;
    die();

}

add_action( 'wp_ajax_envira_albums_update_gallery', 'envira_albums_update_gallery' );
/**
 * Saves the metadata for a gallery in an album
 *
 * @since 1.0.0
 */
function envira_albums_update_gallery() {

    // Run a security check first.
    check_ajax_referer( 'envira-albums-save', 'nonce' );

    // Check variables exist
    if ( ! isset( $_POST['post_id']) ) {
        wp_send_json_error();
    }
    if ( ! isset( $_POST['gallery_id']) ) {
        wp_send_json_error();
    }
    if ( ! isset( $_POST['meta']) ) {
        wp_send_json_error();
    }

    // Prepare variables.
    $post_id    = absint( $_POST['post_id'] );
    $gallery_id = absint( $_POST['gallery_id'] );
    $meta       = stripslashes_deep( $_POST['meta'] );

    // Get Album configuration
    $album_data = get_post_meta( $post_id, '_eg_album_data', true );

    // Set gallery array if this is the first time we're saving settings
    if ( ! isset( $album_data['galleries'] ) ) {
        $album_data['galleries'] = array();
    }
    if ( ! isset( $album_data['galleries'][ $gallery_id ] ) ) {
        $album_data['galleries'][ $gallery_id ] = array();
    }

    // Set post meta values
    if ( isset( $meta['title'] ) ) {
        $album_data['galleries'][ $gallery_id ]['title']      = sanitize_text_field( esc_attr( $meta['title'] ) );
    }
    if ( isset( $meta['caption'] ) ) {
        $album_data['galleries'][ $gallery_id ]['caption']    = trim( $meta['caption'] );
    }
    if ( isset( $meta['alt'] ) ) {
        $album_data['galleries'][ $gallery_id ]['alt']        = sanitize_text_field( esc_attr(  $meta['alt'] ) );
    }
    if ( isset( $meta['cover_image_id'] ) ) {
        $album_data['galleries'][ $gallery_id ]['cover_image_id'] = absint( $meta['cover_image_id'] );
    }
    if ( isset( $meta['cover_image_url'] ) ) {
        $album_data['galleries'][ $gallery_id ]['cover_image_url'] = sanitize_text_field( $meta['cover_image_url'] );
    }
    if ( isset( $meta['link_new_window'] ) ) {
        $album_data['galleries'][ $gallery_id ]['link_new_window'] = sanitize_text_field( esc_attr( $meta['link_new_window'] ) );
    }
    if ( isset( $meta['gallery_lightbox'] ) ) {
        $album_data['galleries'][ $gallery_id ]['gallery_lightbox'] = sanitize_text_field( $meta['gallery_lightbox'] );
    }


    // Allow filtering of meta before saving.
    $album_data = apply_filters( 'envira_albums_update_gallery', $album_data, $meta, $gallery_id, $post_id );

    // Save post meta
    update_post_meta( $post_id, '_eg_album_data', $album_data );

    // Clear transient cache
    Envira_Albums_Common::get_instance()->flush_album_caches( $post_id );

    // Send back the response.
    wp_send_json_success();
    die();

}

add_action( 'wp_ajax_envira_albums_get_gallery_images', 'envira_albums_get_gallery_images' );
/**
 * Returns a JSON object of images for the given Gallery ID
 *
 * @since 1.3.0
 */
function envira_albums_get_gallery_images() {

    // Run a security check first.
    check_ajax_referer( 'envira-albums-get-gallery-images', 'nonce' );

    // Check variables exist
    if ( ! isset( $_POST['gallery_id'] ) ) {
        wp_send_json_error();
    }

    // Prepare variables.
    $gallery_id = absint( $_POST['gallery_id'] );

    // Get Gallery
    $data = Envira_Gallery::get_instance()->get_gallery( $gallery_id );
    if ( ! $data ) {
        wp_send_json_error( __( 'Gallery not found.', 'envira-albums' ) );
    }

    // Allow External Galleries (Instagram, Featured Content) to inject images into the gallery array.
    // This ensures that a cover image URL can be found / chosen in the Edit Metadata modal.
    $data['gallery'] = apply_filters( 'envira_albums_metabox_gallery_inject_images', $data['gallery'], $gallery_id, $data );

    // Bail if no images in the Gallery
    if ( ! isset( $data['gallery'] ) || count( $data['gallery'] ) == 0 ) {
        wp_send_json_error( __( 'No images found in this Gallery.', 'envira-albums' ) );
    }

    // Build images array that's Backbone compatible i.e. an array of objects and not an object of objects.
    $images = array();
    foreach ( $data['gallery'] as $image_id => $image ) {
        // Get a 150x150 thumbnail for the modal gallery cover image selection grid
        $thumb = wp_get_attachment_image_src( $image_id, 'thumbnail' );

        // Build the array
        $images[] = array(
            'id'    => $image_id,
            'title' => $image['title'],
            'src'   => $image['src'],
            'thumb' => ( is_array( $thumb ) ? $thumb[0] : $image['src'] ),
        );
    }

    // Return the images
    wp_send_json_success( $images );
    die();

}

/**
 * Returns Albums, with an optional search term
 *
 * @since 1.3.0
 */
add_action( 'wp_ajax_envira_albums_editor_get_albums', 'envira_albums_editor_get_albums' );
function envira_albums_editor_get_albums() {

    // Check nonce
    check_ajax_referer( 'envira-gallery-editor-get-galleries', 'nonce' );

    // Get POSTed fields
    $search         = (bool) $_POST['search'];
    $search_terms   = sanitize_text_field( $_POST['search_terms'] );
    $prepend_ids    = stripslashes_deep( $_POST['prepend_ids'] );
    $results        = array();    

    // Get albums
    $albums = Envira_Albums::get_instance()->get_albums( false, true, ( $search ? $search_terms : '' ) );

    // Build array of just the data we need.
    foreach ( ( array ) $albums as $album ) {
        // Add gallery to results
        $results[] = array(
            'id'        => ( ! empty( $album['id'] ) ? $album['id'] : '' ),
            'title'     => ( ! empty( $album['config']['title'] ) ? $album['config']['title'] : '' ),
            'thumbnail' => '',
            'action'    => 'album', // Tells the editor modal whether this is a Gallery or Album for the shortcode output
        );
    }

    // If any prepended Album IDs were specified, get them now
    // These will typically be a Defaults Album, which wouldn't be included in the above get_albums() call
    if ( is_array( $prepend_ids ) && count( $prepend_ids ) > 0 ) {
        $prepend_results = array();
        
        // Get each Album
        foreach ( $prepend_ids as $album_id ) {

            // Get album
            $album = get_post_meta( $album_id, '_eg_album_data', true );
						
            // Add album to results
            $prepend_results[] = array(
                'id'        => $album['id'],
                'title'     => $album['config']['title'],
                'thumbnail' => '',
                'action'    => 'album', // Tells the editor modal whether this is a Gallery or Album for the shortcode output
            );
        }

        // Add to results
        if ( is_array( $prepend_results ) && count( $prepend_results ) > 0 ) {
            $results = array_merge( $prepend_results, $results );
        }
    }

    // Return galleries
    wp_send_json_success( $results );

}