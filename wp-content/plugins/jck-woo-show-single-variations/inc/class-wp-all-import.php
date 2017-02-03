<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Iconic_WSSV_WP_All_Import.
 *
 * Helper for importing variation data
 *
 * @class    Iconic_WSSV_WP_All_Import
 * @version  1.0.0
 * @package  JCK_WSSV
 * @category Class
 * @author   Iconic
 */
class Iconic_WSSV_WP_All_Import {

    /*
     * Construct
     */
    function __construct(  ) {

        add_action( 'pmxi_update_post_meta', array( $this, 'format_visibility' ), 10, 3 );
        add_action( 'pmxi_update_post_meta', array( $this, 'format_featured' ), 10, 3 );
        add_action( 'pmxi_saved_post', array( $this, 'on_variation_save' ), 10, 1 );

    }

    /**
     * Format visibility field on import
     *
     * @param int $pid
     * @param str $meta_key
     * @param str $meta_value
     */
    public function format_visibility( $post_id, $meta_key, $meta_value ) {

        global $jck_wssv;

        if( get_post_type( $post_id ) !== "product_variation" )
            return;

        if( $meta_key !== "_visibility" )
            return;

        $visibility = explode( ',', $meta_value );

        update_post_meta( $post_id, $meta_key, $visibility );

        if( in_array( 'filtered', $visibility ) ) {

            $jck_wssv->add_attributes_to_variation( $post_id,  false, "add" );

        } else {

            $jck_wssv->add_attributes_to_variation( $post_id,  false, "remove" );

        }

        $jck_wssv->delete_term_counts_transient();

    }

    /**
     * Format featured field on import
     *
     * @param int $post_id
     * @param str $meta_key
     * @param str $meta_value
     */
    public function format_featured( $post_id, $meta_key, $meta_value ) {

        if( get_post_type( $post_id ) !== "product_variation" )
            return;

        if( $meta_key !== "_featured" )
            return;

        $featured = $meta_value == 1 ? "yes" : "no";

        update_post_meta($post_id, $meta_key, $featured);

    }

    /**
     * On variation save
     *
     * @param int $post_id
     */
    public function on_variation_save( $post_id ) {

        global $jck_wssv;

        if( get_post_type( $post_id ) !== "product_variation" )
            return;

        $jck_wssv->add_taxonomies_to_variation( $post_id );

    }

}