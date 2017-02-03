<?php
/*
Plugin Name: WooCommerce Show Single Variations
Plugin URI: https://iconicwp.com
Description: Show product variations in the main product loops
Version: 1.1.3
Author: Iconic
Author URI: https://iconicwp.com
Text Domain: jck-wssv
*/

defined('JCK_WSSV_PATH') or define('JCK_WSSV_PATH', plugin_dir_path( __FILE__ ));
defined('JCK_WSSV_URL') or define('JCK_WSSV_URL', plugin_dir_url( __FILE__ ));

class JCK_WSSV {

    public $name = 'WooCommerce Show Single Variations';
    public $shortname = 'Single Variations';
    public $slug = 'jck-wssv';
    public $version = "1.1.3";
    public $plugin_path;
    public $plugin_url;
    public $theme = false;

    /**
     * Class prefix
     *
     * @since 1.0.0
     * @access protected
     * @var string $class_prefix
     */
    protected $class_prefix = "Iconic_WSSV_";

    /**
     * WPML Class
     *
     * @since 1.1.1
     * @access protected
     * @var Iconic_WSSV_WPML $wpml
     */
    protected $wpml;

    /**
     * WP All Import Class
     *
     * @since 1.1.1
     * @access protected
     * @var Iconic_WSSV_WP_All_Import $wpml
     */
    protected $wp_all_import;

    /**
     * Unpublished variable IDs
     *
     * @since 1.1.1
     * @access protected
     * @var arr $unpublished_variable_product_ids
     */
    protected $unpublished_variable_product_ids;

    /**
     * Variation IDs with missing parent
     *
     * @since 1.1.1
     * @access protected
     * @var arr $variation_ids_with_missing_parent
     */
    protected $variation_ids_with_missing_parent;

/** =============================
    *
    * Construct the plugin
    *
    ============================= */

    public function __construct() {

        $this->set_constants();
        $this->load_classes();

        load_plugin_textdomain( 'jck-wssv', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

        add_action( 'init', array( $this, 'initiate_hook' ) );

    }

    /**
     * Load classes
     */
    private function load_classes() {

        spl_autoload_register( array( $this, 'autoload' ) );

        require_once( $this->plugin_path.'inc/admin/vendor/class-envato-market-github.php' );

        if( $this->is_plugin_active( 'woocommerce-multilingual/wpml-woocommerce.php' ) ) {
            $this->wpml = new Iconic_WSSV_WPML();
        }

        if( $this->is_plugin_active( 'wp-all-import-pro/wp-all-import-pro.php' ) ) {
            $this->wp_all_import = new Iconic_WSSV_WP_All_Import();
        }

    }

    /**
     * Autoloader
     *
     * Classes should reside within /inc and follow the format of
     * Iconic_The_Name ~ class-the-name.php or {{class-prefix}}The_Name ~ class-the-name.php
     */
    private function autoload( $class_name ) {

        /**
         * If the class being requested does not start with our prefix,
         * we know it's not one in our project
         */
        if ( 0 !== strpos( $class_name, 'Iconic_' ) && 0 !== strpos( $class_name, $this->class_prefix ) )
            return;

        $file_name = strtolower( str_replace(
            array( $this->class_prefix, 'Iconic_', '_' ),      // Prefix | Plugin Prefix | Underscores
            array( '', '', '-' ),                              // Remove | Remove | Replace with hyphens
            $class_name
        ) );

        // Compile our path from the current location
        $file = dirname( __FILE__ ) . '/inc/class-'. $file_name .'.php';

        // If a file is found
        if ( file_exists( $file ) ) {
            // Then load it up!
            require( $file );
        }

    }

/** =============================
    *
    * Setup Constants for this class
    *
    ============================= */

    public function set_constants() {

        $this->plugin_path = JCK_WSSV_PATH;
        $this->plugin_url = JCK_WSSV_URL;
        $this->theme = wp_get_theme();

    }

/** =============================
    *
    * Run after the current user is set (http://codex.wordpress.org/Plugin_API/Action_Reference)
    *
    ============================= */

	public function initiate_hook() {

        if(is_admin()) {

            add_action( 'woocommerce_variation_options',                  array( $this, 'add_variation_checkboxes' ), 10, 3 );
            add_action( 'woocommerce_product_after_variable_attributes',  array( $this, 'add_variation_additional_fields' ), 10, 3 );
            add_action( 'woocommerce_variable_product_bulk_edit_actions', array( $this, 'add_variation_bulk_edit_actions' ), 10 );
            add_action( 'woocommerce_bulk_edit_variations_default',       array( $this, 'bulk_edit_variations' ), 10, 4 );
            add_action( 'woocommerce_save_product_variation',             array( $this, 'save_product_variation' ), 10, 2 );

            add_action( 'wp_ajax_jck_wssv_add_to_cart',                   array( $this, 'add_to_cart' ) );
            add_action( 'wp_ajax_nopriv_jck_wssv_add_to_cart',            array( $this, 'add_to_cart' ) );

            add_action( 'woocommerce_create_product_variation',           array( $this, 'update_variation_order' ), 10, 1 );
            add_action( 'woocommerce_update_product_variation',           array( $this, 'update_variation_order' ), 10, 1 );

            add_action( 'save_post',                                      array( $this, 'on_product_save' ), 10, 1 );
            add_action( 'woocommerce_save_product_variation',             array( $this, 'on_variation_save' ), 10, 2 );

            add_action( 'set_object_terms',                               array( $this, 'set_variation_terms' ), 10, 6 );
            add_action( 'updated_post_meta',                              array( $this, 'updated_product_attributes' ), 10, 4 );

            add_action( 'woocommerce_order_status_changed',               array( $this, 'order_status_changed' ), 10, 3 );
            add_action( 'woocommerce_process_shop_order_meta',            array( $this, 'process_shop_order' ), 10, 2 );

            add_action( 'woocommerce_after_product_ordering',             array( $this, 'after_product_ordering' ), 10 );

        } else {

            add_action( 'wp_enqueue_scripts',                             array( $this, 'frontend_scripts' ) );
            add_action( 'wp_enqueue_scripts',                             array( $this, 'frontend_styles' ) );

            add_action( 'woocommerce_product_query',                      array( $this, 'add_variations_to_product_query' ), 50, 2 );
            add_filter( 'woocommerce_shortcode_products_query',           array( $this, 'add_variations_to_shortcode_query' ), 10, 2 );
            add_filter( 'woocommerce_get_filtered_term_product_counts_query', array( $this, 'filtered_term_product_counts_where_clause' ), 10, 1);

            add_filter( 'woocommerce_get_catalog_ordering_args',          array( $this, 'modify_catalog_ordering_args' ), 10, 1 );

            add_filter( 'post_class',                                     array( $this, 'add_post_classes_in_loop' ) );
            add_filter( 'woocommerce_product_is_visible',                 array( $this, 'filter_variation_visibility' ), 10, 2 );

            add_filter( 'the_title',                                      array( $this, 'change_variation_title' ), 10, 2 );
            add_filter( 'post_type_link',                                 array( $this, 'change_variation_permalink' ), 10, 2 );
            add_filter( 'woocommerce_loop_add_to_cart_link',              array( $this, 'change_variation_add_to_cart_link' ), 10, 2 );

            add_filter( 'woocommerce_product_add_to_cart_text',           array( $this, 'add_to_cart_text' ), 10, 2 );
            add_filter( 'woocommerce_product_add_to_cart_url',            array( $this, 'add_to_cart_url' ), 10, 2 );

            add_filter( 'woocommerce_product_gallery_attachment_ids',     array( $this, 'product_gallery_attachment_id' ), 10, 2 );

            add_filter( 'get_terms',                                      array( $this, 'change_term_counts' ), 100, 2 );

            add_filter( 'post_class',                                     array( $this, 'product_post_class' ), 20, 3 );

            add_action( 'delete_transient_wc_term_counts', array( $this, 'delete_term_counts_transient' ), 10, 1 );

            add_filter( 'woocommerce_price_filter_post_type', array( $this, 'add_product_variation_to_price_filter' ), 10, 1 );

        }

        $this->register_taxonomy_for_object_type();
        // $this->update_products_order();

	}

    /**
     * Modify the "filtered term product counts" where clause
     *
     * Adds post_type and post_parent__not_in parameter so unpublished variable
     * product variations are ignored in the filter counts
     *
     * @since 1.1.0
     * @param array $query
     * @return array
     */
	public function filtered_term_product_counts_where_clause( $query ) {

    	global $wpdb, $wp_the_query;

    	$query['where'] = str_replace("'product'", "'product', 'product_variation'", $query['where']);

    	if( empty( $wp_the_query->query_vars['post_parent__not_in'] ) )
    	    return $query;

        $query['where'] = sprintf("%s AND %s.post_parent NOT IN ('%s')", $query['where'], $wpdb->posts, implode("','", $wp_the_query->query_vars['post_parent__not_in']));

    	return $query;

	}

/**	=============================
    *
    * Frontend Styles
    *
    * @access public
    *
    ============================= */

    public function frontend_styles() {

        wp_register_style( $this->slug.'_styles', $this->plugin_url . 'assets/frontend/css/main.min.css', array(), $this->version );

        wp_enqueue_style( $this->slug.'_styles' );

    }

/**	=============================
    *
    * Frontend Scripts
    *
    * @access public
    *
    ============================= */

    public function frontend_scripts() {

        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_register_script( $this->slug.'_scripts', $this->plugin_url . 'assets/frontend/js/main'.$min.'.js', array( 'jquery', 'wc-add-to-cart' ), $this->version, true);

        wp_enqueue_script( $this->slug.'_scripts' );

        $vars = array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( $this->slug ),
			'pluginSlug' => $this->slug
		);

		wp_localize_script( $this->slug.'_scripts', 'jck_wssv_vars', $vars );

    }

/** =============================
    *
    * Frontend: Add variaitons to product query, similar to pre_get_posts
    *
    * @param  [obj] [$q] The current query
    *
    ============================= */

    public function add_variations_to_product_query( $q, $wc_query ) {

        if( !is_admin() && is_woocommerce() && $q->is_main_query() && isset( $q->query_vars['wc_query'] ) ) {

            global $_chosen_attributes;

            // Add product variations to the query

            $post_type = (array) $q->get('post_type');
            $post_type[] = 'product_variation';
            if( !in_array('product', $post_type) ) $post_type[] = 'product';
            $q->set('post_type', array_filter( $post_type ) );

            // Don't get variations with unpublished parents

            $unpublished_variable_product_ids = $this->get_unpublished_variable_product_ids();
            if( $unpublished_variable_product_ids ) {
                $post_parent__not_in = (array) $q->get('post_parent__not_in');
                $q->set('post_parent__not_in', array_merge( $post_parent__not_in, $unpublished_variable_product_ids ) );
            }

            // Don't get variations with missing parents :(

            $variation_ids_with_missing_parent = $this->get_variation_ids_with_missing_parent();
            if( $variation_ids_with_missing_parent ) {
                $post__not_in = (array) $q->get('post__not_in');
                $q->set('post__not_in', array_merge( $post__not_in, $variation_ids_with_missing_parent ) );
            }

            // update the meta query to include our variations

            $meta_query = (array) $q->get('meta_query');
            $meta_query = $this->update_meta_query( $meta_query );

            $q->set('meta_query', $meta_query );

        }

    }


/** =============================
    *
    * Frontend: Add variaitons to shortcode queries
    *
    * @param arr $query_args
    * @param arr $shortcode_args
    *
    ============================= */

    public function add_variations_to_shortcode_query( $query_args, $shortcode_args ) {

        // Add product variations to the query

        $post_type = (array) $query_args['post_type'];
        $post_type[] = 'product_variation';

        $query_args['post_type'] = $post_type;

        // Don't get variations with unpublished parents

        $unpublished_variable_product_ids = $this->get_unpublished_variable_product_ids();
        if( $unpublished_variable_product_ids ) {
            $post_parent__not_in = isset( $query_args['post_parent__not_in'] ) ? (array) $query_args['post_parent__not_in'] : array();
            $query_args['post_parent__not_in'] = array_merge( $post_parent__not_in, $unpublished_variable_product_ids );
        }

        // Don't get variations with missing parents :(

        $variation_ids_with_missing_parent = $this->get_variation_ids_with_missing_parent();
        if( $variation_ids_with_missing_parent ) {
            $post__not_in = isset( $query_args['post__not_in'] ) ? (array) $query_args['post__not_in'] : array();
            $query_args['post__not_in'] = array_merge( $post__not_in, $variation_ids_with_missing_parent );
        }

        // update the meta query to include our variations

        $meta_query = (array) $query_args['meta_query'];
        $meta_query = $this->update_meta_query( $meta_query );

        $query_args['meta_query'] = $meta_query;

        return $query_args;

    }

/** =============================
    *
    * Helper: Update meta query
    *
    * Add OR parameters to also search for variations with specific visibility
    *
    * @param  [arr] [$meta_query]
    * @return [arr]
    *
    ============================= */

    public function update_meta_query( $meta_query ) {

        $index = 0;

        if( !empty($meta_query) ) {
            foreach( $meta_query as $index => $meta_query_item ) {
                if( isset( $meta_query_item['key'] ) && $meta_query_item['key'] == "_visibility" ) {

                    $meta_query[$index] = array();
                    $meta_query[$index]['relation'] = 'OR';

                    $meta_query[$index]['visibility_visible'] = array(
                        'key' => '_visibility',
                        'value' => 'visible',
                        'compare' => 'LIKE'
                    );

                    if( is_search() ) {

                        $meta_query[$index]['visibility_search'] = array(
                            'key' => '_visibility',
                            'value' => 'search',
                            'compare' => 'LIKE'
                        );

                    } else {

                        $meta_query[$index]['visibility_catalog'] = array(
                            'key' => '_visibility',
                            'value' => 'catalog',
                            'compare' => 'LIKE'
                        );

                    }

                    if( is_filtered() ) {

                        $meta_query[$index]['visibility_filtered'] = array(
                            'key' => '_visibility',
                            'value' => 'filtered',
                            'compare' => 'LIKE'
                        );

                    }

                }
            }
        }

        // Add variation_menu_order so that we can use it
        // for sorting later on
        /*
        $meta_query[] = array(
            'relation' => 'OR',
            'variation_menu_order' => array(
                'key' => 'variation_menu_order',
                'compare' => 'EXISTS',
                'type' => 'decimal(20,6)'
            )
        );
        */
        return $meta_query;

    }

    /**
     * Get unpublished variable product IDs
     *
     * Get's an array of product IDs where the product
     * is variable and has not been published (i.e. is in the bin)
     *
     * @since 1.1.0
     * @return mixed bool|array
     */
    public function get_unpublished_variable_product_ids() {

        if( $this->unpublished_variable_product_ids )
            return $this->unpublished_variable_product_ids;

        $statuses = array('trash','future','auto-draft','pending');

        if( !current_user_can('edit_posts') )
            $statuses[] = 'draft';

        $args = array(
            'post_type' => 'product',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'variable',
                ),
            ),
            'posts_per_page' => -1,
            'post_status' => $statuses
        );

        $products = new WP_Query( $args );

        wp_reset_postdata();

        if ( !$products->have_posts() )
            return false;

        $this->unpublished_variable_product_ids = wp_list_pluck( $products->posts, 'ID' );

        return $this->unpublished_variable_product_ids;

    }

    /**
     * Get variation IDs with missing parents
     *
     * @since 1.1.2
     * @return mixed bool|array
     */
    public function get_variation_ids_with_missing_parent() {

        if( $this->variation_ids_with_missing_parent )
            return $this->variation_ids_with_missing_parent;

        global $wpdb;

        $variation_ids = $wpdb->get_results(
            "
            SELECT  p1.ID
            FROM $wpdb->posts p1
            WHERE p1.post_type = 'product_variation'
            AND p1.post_parent NOT IN (
                SELECT DISTINCT p2.ID
                FROM $wpdb->posts p2
                WHERE p2.post_type = 'product'
            )
            ", ARRAY_A
        );

        if( !$variation_ids )
            return false;

        $this->variation_ids_with_missing_parent = wp_list_pluck( $variation_ids, 'ID' );

        return $this->variation_ids_with_missing_parent;

    }

/** =============================
    *
    * Helper: Get filtered variation ids
    *
    * @return [arr]
    *
    ============================= */

    public function get_filtered_variation_ids() {

        global $_chosen_attributes;

        $variation_ids = array();

        $args = array(
            'post_type'  => 'product_variation',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key'     => '_visibility',
                    'value'   => 'filtered',
                    'compare' => 'LIKE',
                )
            )
        );

        $min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : false;
		$max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : false;

		if( $min_price !== false && $max_price !== false ) {

    		$args['meta_query'][] = array(
                'key' => '_price',
                'value' => array($min_price, $max_price),
                'compare' => 'BETWEEN',
                'type' => 'NUMERIC'
            );

		}

		if( $_chosen_attributes && !empty( $_chosen_attributes ) ) {

            $i = 10; foreach( $_chosen_attributes as $attribute_key => $attribute_data ) {

                $attribute_meta_key = sprintf('attribute_%s', $attribute_key);

                $attribute_term_slugs = array();

                foreach( $attribute_data['terms'] as $attribute_term_id ) {
                    $attribute_term = get_term_by('id', $attribute_term_id, $attribute_key);
                    $attribute_term_slugs[] = $attribute_term->slug;
                }

                if( $attribute_data['query_type'] == "or" ) {

                    $args['meta_query'][$i] = array(
                        'key'     => $attribute_meta_key,
                        'value'   => $attribute_term_slugs,
                        'compare' => 'IN',
                    );

                } else {

                    $args['meta_query'][$i] = array(
                        'relation' => 'AND'
                    );

                    foreach( $attribute_term_slugs as $attribute_term_slug ) {
                        $args['meta_query'][$i][] = array(
                            'key'     => $attribute_meta_key,
                            'value'   => $attribute_term_slug,
                            'compare' => '=',
                        );
                    }

                }

            $i++; }

        }

        $variations = new WP_Query( $args );

        if ( $variations->have_posts() ) {

        	while ( $variations->have_posts() ) {
        		$variations->the_post();

        		$variation_ids[] = get_the_id();
        	}

        }

        wp_reset_postdata();

        return $variation_ids;

    }

/** =============================
    *
    * Frontend: Add relevant product classes to loop item
    *
    * @param  [arr] [$classes]
    * @return [arr]
    *
    ============================= */

    public function add_post_classes_in_loop( $classes ) {

        global $post, $product;

        if( $product && $post->post_type === "product_variation" ) {

            $classes = array_diff($classes, array('hentry', 'post'));

            $classes[] = "product";
            // add other classes here, find woocommerce function

        }

        return $classes;

    }

/** =============================
    *
    * Admin: Add variation checkboxes
    *
    * @param  [str] [$loop]
    * @param  [arr] [$variation_data]
    * @param  [obj] [$variation]
    *
    ============================= */

    public function add_variation_checkboxes( $loop, $variation_data, $variation ) {

        include('inc/admin/variation-checkboxes.php');

    }

/** =============================
    *
    * Admin: Add variation options
    *
    * @param  [str] [$loop]
    * @param  [arr] [$variation_data]
    * @param  [obj] [$variation]
    *
    ============================= */

    public function add_variation_additional_fields( $loop, $variation_data, $variation ) {

        include('inc/admin/variation-additional-fields.php');

    }

/** =============================
    *
    * Admin: Add variation bulk edit actions
    *
    ============================= */

    public function add_variation_bulk_edit_actions() {

        include('inc/admin/variation-bulk-edit-actions.php');

    }

/** =============================
    *
    * Admin: Bulk edit actions
    *
    * @param  [str] [$bulk_action]
    * @param  [arr] [$data]
    * @param  [int] [$product_id]
    * @param  [arr] [$variations]
    *
    ============================= */

    public function bulk_edit_variations( $bulk_action, $data, $product_id, $variations ) {

        if ( method_exists( $this, "variation_bulk_action_$bulk_action" ) ) {
			call_user_func( array( $this, "variation_bulk_action_$bulk_action" ), $variations );
			$this->delete_term_counts_transient();
		}

    }

/** =============================
    *
    * Helper: Unset array item by the value
    *
    * @param  [arr] [$array]
    * @param  [str] [$value]
    * @return [arr]
    *
    ============================= */

    public function unset_item_by_value( $array, $value ) {

        if(($key = array_search($value, $array)) !== false) {
            unset($array[$key]);
        }

        return $array;

    }

/** =============================
    *
    * Admin: Bulk Action - Toggle Show in (x)
    *
    * @param  [arr] [$variations]
    * @param  [arr] [$show]
    *
    ============================= */

    private function variation_bulk_action_toggle_show_in( $variations, $show ) {

        foreach ( $variations as $i => $variation_id ) {

            $visibility = (array)get_post_meta( $variation_id, '_visibility', true );

            if( in_array( $show, $visibility ) ) {

                $visibility = $this->unset_item_by_value( $visibility, $show );

                if( $show == "filtered" ) {
                    $this->add_attributes_to_variation( $variation_id, false, "remove" );
                }

            } else {

                $visibility[] = $show;

                if( $show == "filtered" ) {
                    $this->add_attributes_to_variation( $variation_id, false, "add" );
                }

            }

            $this->add_taxonomies_to_variation( $variation_id );

            update_post_meta( $variation_id, '_visibility', $visibility );

            $this->delete_term_counts_transient();

        }

    }

/** =============================
    *
    * Admin: Bulk Action - Toggle Show in Search
    *
    * @param  [arr] [$variations]
    *
    ============================= */

    private function variation_bulk_action_toggle_show_in_search( $variations ) {

        $this->variation_bulk_action_toggle_show_in( $variations, 'search' );

	}

/** =============================
    *
    * Admin: Bulk Action - Toggle Show in Filtered
    *
    * @param  [arr] [$variations]
    *
    ============================= */

    private function variation_bulk_action_toggle_show_in_filtered( $variations ) {

        $this->variation_bulk_action_toggle_show_in( $variations, 'filtered' );

	}

/** =============================
    *
    * Admin: Bulk Action - Toggle Show in Catalog
    *
    * @param  [arr] [$variations]
    *
    ============================= */

    private function variation_bulk_action_toggle_show_in_catalog( $variations ) {

        $this->variation_bulk_action_toggle_show_in( $variations, 'catalog' );

	}

/** =============================
    *
    * Admin: Bulk Action - Toggle Featured
    *
    * @param  [arr] [$variations]
    *
    ============================= */

    private function variation_bulk_action_toggle_featured( $variations ) {

        foreach ( $variations as $variation_id ) {

            $featured = get_post_meta( $variation_id, '_featured', true );
            $featured = $featured === "yes" ? "no" : "yes";

            update_post_meta( $variation_id, '_featured', $featured );

        }

	}

/** =============================
    *
    * Admin: Bulk Action - Toggle Disable "Add to Cart"
    *
    * @param  [arr] [$variations]
    *
    ============================= */

    private function variation_bulk_action_toggle_disable_add_to_cart( $variations ) {

        foreach ( $variations as $variation_id ) {

            $disable_add_to_cart = get_post_meta( $variation_id, '_disable_add_to_cart', true );

            if( $disable_add_to_cart ) {

                delete_post_meta( $variation_id, '_disable_add_to_cart' );

            } else {

                update_post_meta( $variation_id, '_disable_add_to_cart', true );

            }

        }

	}

/** =============================
    *
    * Admin: Bulk Action - Update Total Sales
    *
    * @param  [arr] [$variations]
    *
    ============================= */

    private function variation_bulk_action_update_total_sales( $variations ) {

        if( $variations && !empty( $variations ) ) {
            foreach( $variations as $variation_id ) {

                $total_sales = $this->get_variation_sales( $variation_id );
                update_post_meta( $variation_id, 'total_sales', $total_sales );

            }
        }

	}

/** =============================
    *
    * Admin: Save variation options
    *
    * @param  [int] [$variation_id]
    * @param  [int] [$i]
    *
    ============================= */

    public function save_product_variation( $variation_id, $i ) {

        // setup posted data

        $visibility = array();
        $title = isset( $_POST['jck_wssv_display_title'] ) ? $_POST['jck_wssv_display_title'][ $i ] : false;

        if( isset( $_POST['jck_wssv_variable_show_search'][$i] ) )
            $visibility[] = "search";

        if( isset( $_POST['jck_wssv_variable_show_filtered'][$i] ) )
            $visibility[] = "filtered";

        if( isset( $_POST['jck_wssv_variable_show_catalog'][$i] ) )
            $visibility[] = "catalog";

        // set visibility

        if( !empty( $visibility ) ) {
            update_post_meta( $variation_id, '_visibility', $visibility );
        } else {
            delete_post_meta( $variation_id, '_visibility' );
        }

        // set featured

        if( isset( $_POST['jck_wssv_variable_featured'][$i] ) && $_POST['jck_wssv_variable_featured'][$i] == "on" ) {
            update_post_meta( $variation_id, '_featured', "yes" );
        } else {
            delete_post_meta( $variation_id, '_featured' );
        }

        // set add to cart

        if( isset( $_POST['jck_wssv_variable_disable_add_to_cart'][$i] ) && $_POST['jck_wssv_variable_disable_add_to_cart'][$i] == "on" ) {
            update_post_meta( $variation_id, '_disable_add_to_cart', true );
        } else {
            delete_post_meta( $variation_id, '_disable_add_to_cart' );
        }

		// set display title

		if( $title ) {

    		global $wpdb;

    		update_post_meta( $variation_id, '_jck_wssv_display_title', $title );

    		// Update variation title to be included in search

    		$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), array( 'ID' => $variation_id ) );

        }

    }

    /**
     * Get total variation sales
     *
     * @param int $variation_id
     * @return int
     */
    public function get_variation_sales( $variation_id ) {

        global $wpdb;

        $total_sales = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT SUM(`quantities`.`meta_value`)
                FROM `{$wpdb->prefix}woocommerce_order_itemmeta` as `itemmeta`
                 LEFT JOIN  `{$wpdb->prefix}woocommerce_order_itemmeta` AS  `quantities` ON `itemmeta`.`order_item_id` = `quantities`.`order_item_id`
                  AND `quantities`.`meta_key` = '_qty'
                 LEFT JOIN `{$wpdb->prefix}woocommerce_order_items` as `items` ON `items`.`order_item_id`=`itemmeta`.`order_item_id`
                WHERE `itemmeta`.`meta_key` = '_variation_id'
                 AND `itemmeta`.`meta_value` = %d
                ",
                $variation_id
            )
        );

        return $total_sales;

    }

/** =============================
    *
    * Frontend: Change variation title
    *
    * @param  [str] [$title]
    * @param  [int] [$id]
    * @return [str]
    *
    ============================= */

    public function change_variation_title( $title, $id = false ) {

        if( $id && $this->is_product_variation( $id ) ) {
            $title = $this->get_variation_title( $id );
        }

        return $title;

    }

/** =============================
    *
    * Helper: Get default variation title
    *
    * @param  [int] [$variation_id]
    * @return [str]
    *
    ============================= */

    public function get_variation_title( $variation_id ) {

        if( !$variation_id || $variation_id == "" )
            return "";

        $variation = wc_get_product( absint( $variation_id ), array( 'product_type' => 'variable' ) );
        $variation_title = ( $variation->get_title() != "Auto Draft" ) ? $variation->get_title() : "";
        $variation_custom_title = get_post_meta($variation->variation_id, '_jck_wssv_display_title', true);

        return ( $variation_custom_title ) ? $variation_custom_title : $variation_title;

    }

/** =============================
    *
    * Frontend: Change variation permalink
    *
    * @param  [str] [$url]
    * @param  [str] [$post]
    * @return [str]
    *
    ============================= */

    public function change_variation_permalink( $url, $post ) {

        if ( 'product_variation' == $post->post_type ) {

            $variation = wc_get_product( absint( $post->ID ), array( 'product_type' => 'variable' ) );

            return $this->get_variation_url( $variation );

        }

        return $url;

    }

/** =============================
    *
    * Helper: Get variation URL
    *
    * @param  [str] [$variation]
    * @return [str]
    *
    ============================= */

    public function get_variation_url( $variation ) {

        $url = "";

        if( $variation->variation_id ) {

            $variation_data = array_filter( wc_get_product_variation_attributes( $variation->variation_id ) );
            $parent_product_id = $variation->id;
            $parent_product_url = get_the_permalink( $parent_product_id );

            $url = add_query_arg( $variation_data, $parent_product_url );

        }

        return $url;

    }

/** =============================
    *
    * Frontend: Change variation add to cart link
    *
    * @param  [str] [$anchor]
    * @param  [str] [$product]
    * @return [str]
    *
    ============================= */

    public function change_variation_add_to_cart_link( $anchor, $product ) {

        if( $product->variation_id ) {

            $anchor = sprintf( '<a href="%s" rel="nofollow" data-product_id="%s" data-product_sku="%s" data-quantity="%s" class="button %s product_type_%s" data-variation_id="%s">%s</a>',
                esc_url( $product->add_to_cart_url() ),
                esc_attr( $product->id ),
                esc_attr( $product->get_sku() ),
                esc_attr( isset( $quantity ) ? $quantity : 1 ),
                $this->is_purchasable( $product ) && $product->is_in_stock() ? apply_filters( 'jck_wssv_add_to_cart_button_class', 'add_to_cart add_to_cart_button jck_wssv_add_to_cart' ) : '',
                esc_attr( $product->product_type ),
                esc_html( $product->variation_id ),
                $this->get_add_to_cart_button_text( $product )
            );

        }

        return $anchor;

    }

    /**
     * Helper: Get add to cart button text
     *
     * @param obj $product
     * @return str
     */
    public function get_add_to_cart_button_text( $product ) {

        $text = esc_html( $product->add_to_cart_text() );

        if( $this->theme->get( 'Name' ) === "Atelier" ) {
            $text = sprintf('<i class="sf-icon-add-to-cart"></i><span>%s</span>', $text);
        }

        return $text;

    }

/** =============================
    *
    * Helper: Is product variation?
    *
    * @param  [int] [$id]
    * @return [bool]
    *
    ============================= */

    public function is_product_variation( $id ) {

        $post_type = get_post_type( $id );

        return $post_type == "product_variation" ? true : false;

    }

/** =============================
    *
    * Admin: Get variation checkboxes
    *
    * @param  [obj] [$variation]
    * @param  [int] [$index]
    * @return [arr]
    *
    ============================= */

    public function get_variation_checkboxes( $variation, $index ) {

        $visibility = get_post_meta($variation->ID, '_visibility', true);
        $featured = get_post_meta($variation->ID, '_featured', true);
        $disable_add_to_cart = get_post_meta($variation->ID, '_disable_add_to_cart', true);

        $checkboxes = array(
            array(
                'class' => 'jck_wssv_variable_show_search',
                'name' => sprintf('jck_wssv_variable_show_search[%d]', $index),
                'id' => sprintf('jck_wssv_variable_show_search-%d', $index),
                'checked' => is_array( $visibility ) && in_array('search', $visibility) ? true : false,
                'label' => __( 'Show in Search Results?', 'jck-wssv' )
            ),
            array(
                'class' => 'jck_wssv_variable_show_filtered',
                'name' => sprintf('jck_wssv_variable_show_filtered[%d]', $index),
                'id' => sprintf('jck_wssv_variable_show_filtered-%d', $index),
                'checked' => is_array( $visibility ) && in_array('filtered', $visibility) ? true : false,
                'label' => __( 'Show in Filtered Results?', 'jck-wssv' )
            ),
            array(
                'class' => 'jck_wssv_variable_show_catalog',
                'name' => sprintf('jck_wssv_variable_show_catalog[%d]', $index),
                'id' => sprintf('jck_wssv_variable_show_catalog-%d', $index),
                'checked' => is_array( $visibility ) && in_array('catalog', $visibility) ? true : false,
                'label' => __( 'Show in Catalog?', 'jck-wssv' )
            ),
            array(
                'class' => 'jck_wssv_variable_featured',
                'name' => sprintf('jck_wssv_variable_featured[%d]', $index),
                'id' => sprintf('jck_wssv_variable_featured-%d', $index),
                'checked' => $featured === "yes" ? true : false,
                'label' => __( 'Featured', 'jck-wssv' )
            ),
            array(
                'class' => 'jck_wssv_variable_disable_add_to_cart',
                'name' => sprintf('jck_wssv_variable_disable_add_to_cart[%d]', $index),
                'id' => sprintf('jck_wssv_variable_disable_add_to_cart-%d', $index),
                'checked' => $disable_add_to_cart ? true : false,
                'label' => __( 'Disable "Add to Cart"?', 'jck-wssv' )
            ),
        );

        return $checkboxes;

    }

/** =============================
    *
    * Helper: Filter variaiton visibility
    *
    * Set variation to is_visible() if the options are selected
    *
    * @param  [bool] [$visible]
    * @param  [bool] [$id]
    * @return [bool]
    *
    ============================= */

    public function filter_variation_visibility( $visible, $id ) {

        global $product;

        if( isset( $product->variation_id ) ) {

            $visibility = get_post_meta($product->variation_id, '_visibility', true);

            if( is_array( $visibility ) ) {

                // visible in search

                if( $this->is_visible_when('search', $product->variation_id) ) {
                    $visible = true;
                }

                // visible in filtered

                if( $this->is_visible_when('filtered', $product->variation_id) ) {
                    $visible = true;
                }

                // visible in catalog

                if( $this->is_visible_when('catalog', $product->variation_id) ) {
                    $visible = true;
                }


            }

        }

        return $visible;

    }

/** =============================
    *
    * Helper: Is visible when...
    *
    * Check if a variation is visible when search, filtered, catalog
    *
    * @param  [str] [$when]
    * @param  [int] [$id]
    * @return [bool]
    *
    ============================= */

    public function is_visible_when( $when = false, $id ) {

        $visibility = get_post_meta($id, '_visibility', true);

        if( is_array( $visibility ) ) {

            // visible in search

            if( is_search() && in_array($when, $visibility) ) {
                return true;
            }

            // visible in filtered

            if( is_filtered() && in_array($when, $visibility) ) {
                return true;
            }

            // visible in catalog

            if( !is_filtered() && !is_search() && in_array($when, $visibility) ) {
                return true;
            }


        }

        return false;

    }

/** =============================
    *
    * Ajax: Add to cart
    *
    ============================= */

    public static function add_to_cart() {

		ob_start();

		$product_id           = apply_filters( 'jck_wssv_add_to_cart_product_id', absint( $_POST['product_id'] ) );
		$variation_id         = apply_filters( 'jck_wssv_add_to_cart_variation_id', absint( $_POST['variation_id'] ) );
		$quantity             = empty( $_POST['quantity'] ) ? 1 : wc_stock_amount( $_POST['quantity'] );
		$passed_validation    = apply_filters( 'jck_wssv_add_to_cart_validation', true, $variation_id, $quantity );
		$product_status       = get_post_status( $variation_id );
		$variations           = array();
		$variation            = wc_get_product( $variation_id, array( 'product_type' => 'variable' ) );
		$variation_attributes = $variation->get_variation_attributes();

		if ( $passed_validation && WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attributes ) && 'publish' === $product_status ) {

			do_action( 'woocommerce_ajax_added_to_cart', $variation_id );
			if ( get_option( 'woocommerce_cart_redirect_after_add' ) == 'yes' ) {
				wc_add_to_cart_message( $product_id );
			}

			$wc_ajax = new WC_AJAX();

			// Return fragments
			$wc_ajax->get_refreshed_fragments();

		} else {

			// If there was an error adding to the cart, redirect to the product page to show any errors
			$data = array(
				'error'       => true,
				'product_url' => apply_filters( 'woocommerce_cart_redirect_after_error', get_permalink( $product_id ), $product_id )
			);

			wp_send_json( $data );

		}

		wp_die();
	}

/** =============================
    *
    * Add product_variation to tags and categories
    *
    ============================= */

    public function register_taxonomy_for_object_type() {

        register_taxonomy_for_object_type( 'product_cat', 'product_variation' );
        register_taxonomy_for_object_type( 'product_tag', 'product_variation' );

    }

/** =============================
    *
    * Admin: Add main product taxonomies to variation on variaition save
    *
    * @param  [int] [$variation_id]
    * @param  [int] [$i]
    *
    ============================= */

    public function add_taxonomies_to_variation( $variation_id, $i = false ) {

        $parent_product_id = wp_get_post_parent_id( $variation_id );

        if( $parent_product_id ) {

            // add categories and tags to variaition
            $taxonomies = array(
                'product_cat',
                'product_tag'
            );

            foreach( $taxonomies as $taxonomy ) {

                $terms = (array) wp_get_post_terms( $parent_product_id, $taxonomy, array("fields" => "ids") );
                wp_set_post_terms( $variation_id, $terms, $taxonomy );

            }

        }

    }

/** =============================
    *
    * Admin: Save variation attributes
    *
    * @param  [int] [$variation_id]
    * @param  [int] [$i]
    * @param bool $force
    *
    ============================= */

    public function add_attributes_to_variation( $variation_id, $i = false, $force = false ) {

        $attributes = wc_get_product_variation_attributes( $variation_id );

        if( $attributes && !empty( $attributes ) ) {

            foreach( $attributes as $taxonomy => $value ) {

                $taxonomy = str_replace('attribute_', '', $taxonomy);
                $term = get_term_by('slug', $value, $taxonomy);

                if( $force == "add" || isset($_POST['jck_wssv_variable_show_filtered'][$i]) && $_POST['jck_wssv_variable_show_filtered'][$i] == "on" ) {

                    wp_set_object_terms( $variation_id, $value, $taxonomy );

                } else {

                    if( $term && ( !$force || $force == "remove" ) ) {

                        $products_in_term = wc_get_term_product_ids( $term->term_id, $taxonomy );

                        if(($key = array_search($variation_id, $products_in_term)) !== false) {
                            unset($products_in_term[$key]);
                        }

                        update_woocommerce_term_meta( $term->term_id, 'product_ids', $products_in_term );
                        wp_remove_object_terms( $variation_id, $term->term_id, $taxonomy );
                    }

                }

                if( $term ) {

                    $this->delete_count_transient( $taxonomy, $term->term_taxonomy_id );

                }

            }

        }

    }

    /**
	 * Admin: Fired when a product's terms have been set.
	 *
	 * @param int    $object_id  Object ID.
	 * @param array  $terms      An array of object terms.
	 * @param array  $tt_ids     An array of term taxonomy IDs.
	 * @param string $taxonomy   Taxonomy slug.
	 * @param bool   $append     Whether to append new terms to the old terms.
	 * @param array  $old_tt_ids Old array of term taxonomy IDs.
	 */
    public function set_variation_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {

        $post_type = get_post_type( $object_id );

        if( $post_type === "product" ) {

            if( $taxonomy === "product_cat" || $taxonomy === "product_tag" ) {

                $variations = get_children(array(
                    'post_parent' => $object_id,
                    'post_type' => 'product_variation'
                ), ARRAY_A);

                if( $variations && !empty( $variations ) ) {

                    $variation_ids = array_keys( $variations );

                    foreach( $variation_ids as $variation_id ) {
                        wp_set_object_terms( $variation_id, $terms, $taxonomy, $append );
                    }

                }

            }

        }

    }

/** =============================
    *
    * Admin: Clean variation attributes
    *
    * @param  [int] [$variation_id]
    *
    ============================= */

    public function clean_variation_attributes( $variation_id ) {

        $taxonomies = get_object_taxonomies( 'product_variation', 'names' );

        if( $taxonomies && !empty( $taxonomies ) ) {

            $attributes = array_filter($taxonomies, function ($v) {
                return substr($v, 0, 3) === 'pa_';
            });

            if( !empty( $attributes ) ) {

                foreach( $attributes as $attribute ) {

                    $terms = wp_get_object_terms( $variation_id, $attribute, array('fields' => 'ids') );
                    wp_remove_object_terms( $variation_id, $terms, $attribute );

                }

            }

        }

    }



/** =============================
    *
    * Frontend: is_purchasable
    *
    * @param  [obj] [$product]
    * @return [bool]
    *
    ============================= */

    public function is_purchasable( $product ) {

        $purchasable = $product->is_purchasable();

        if( $product->variation_id ) {

            $disable_add_to_cart = get_post_meta( $product->variation_id, '_disable_add_to_cart', true );

            if( $disable_add_to_cart ) {

                $purchasable = false;

            } else {

                if( $product->variation_data && !empty( $product->variation_data ) ) {
                    foreach( $product->variation_data as $value ) {
                        if( $value == "" ) {
                            $purchasable = false;
                        }
                    }
                }

            }

        }

        return $purchasable;

    }

/** =============================
    *
    * Frontend: Add to Cart Text
    *
    * @param  [str] [$text]
    * @param  [obg] [$product]
    * @return [str]
    *
    ============================= */

    public function add_to_cart_text( $text, $product ) {

        if( $product->variation_id ) {

            $text = $this->is_purchasable( $product ) && $product->is_in_stock() ? $text : __( 'Select options', 'woocommerce' );

        }

        return $text;

    }

/** =============================
    *
    * Frontend: Add to Cart URL
    *
    * @param  [str] [$url]
    * @param  [obg] [$product]
    * @return [str]
    *
    ============================= */

    public function add_to_cart_url( $url, $product ) {

        if( $product->variation_id ) {

            $url = $this->is_purchasable( $product ) && $product->is_in_stock() ? $url : $this->get_variation_url( $product );

        }

        return $url;

    }

/**	=============================
    *
    * Get Woo Version Number
    *
    * @return mixed bool/str NULL or Woo version number
    *
    ============================= */

    public function get_woo_version_number() {

        // If get_plugins() isn't available, require it
        if ( ! function_exists( 'get_plugins' ) )
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        // Create the plugins folder and file variables
        $plugin_folder = get_plugins( '/' . 'woocommerce' );
        $plugin_file = 'woocommerce.php';

        // If the plugin version number is set, return it
        if ( isset( $plugin_folder[$plugin_file]['Version'] ) ) {
            return $plugin_folder[$plugin_file]['Version'];

        } else {
            // Otherwise return null
            return NULL;
        }

    }

    /**
     * Admin: When the order status changes
     *
     * @param int $order_id
     * @param str $old_status
     * @param str $new_status
     */
    public function order_status_changed( $order_id, $old_status, $new_status ) {

        $accepted_status = array('completed', 'processing', 'on-hold');

        if( in_array($new_status, $accepted_status) ) {

            $this->record_variation_sales( $order_id );

        }

    }

    /**
     * Admin: When an Admin manually creates an order
     *
     * @param int $post_id
     * @param obj $post
     */
    public function process_shop_order( $post_id, $post ) {

        $accepted_status = array('wc-completed', 'wc-processing', 'wc-on-hold');

        if( in_array($post->post_status, $accepted_status) ) {

            $this->record_variation_sales( $post_id );

        }

    }

    /**
     * Helper: Record variaiton sales
     *
     * Updates the variation sales count for an order
     *
     * @param int $order_id
     */
    public function record_variation_sales( $order_id ) {

        if ( 'yes' === get_post_meta( $order_id, '_recorded_variation_sales', true ) ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( sizeof( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['variation_id'] > 0 ) {
					$sales = (int) get_post_meta( $item['variation_id'], 'total_sales', true );
					$sales += (int) $item['qty'];
					if ( $sales ) {
						update_post_meta( $item['variation_id'], 'total_sales', $sales );
					}
				}
			}
		}

		update_post_meta( $order_id, '_recorded_variation_sales', 'yes' );

		/**
		 * Called when sales for an order are recorded
		 *
		 * @param int $order_id order id
		 */
		do_action( 'woocommerce_recorded_variation_sales', $order_id );

    }

    /**
	 * Update variation Gallery if WooThumbs is being used
	 *
	 * @param arr $ids Array of gallery image IDs
	 * @param obj $product
	 * @return arr
	 */
    public function product_gallery_attachment_id( $ids, $product ) {

        if( $product->product_type === "variation" ) {

            $ids = array();

            // additional images

            $additional_ids = get_post_meta( $product->variation_id, 'variation_image_gallery', true );

            if( $additional_ids ) {

                $ids = explode(',', $additional_ids);

            }

        }

        return $ids;

    }

    /**
     * Frontend: Change Term Counts
     *
     * @param arr $terms
     * @param arr $taxonomies
     * @return arr
     */
    public function change_term_counts( $terms, $taxonomies ) {

    	if ( is_admin() || is_ajax() )
            return $terms;

        if ( ! isset( $taxonomies[0] ) || ! in_array( $taxonomies[0], apply_filters( 'woocommerce_change_term_counts', array( 'product_cat', 'product_tag' ) ) ) )
            return $terms;

        if ( false === ( $variation_term_counts = get_transient( 'jck_wssv_term_counts' ) ) ) {

            $variation_term_counts = array();

            foreach ( $terms as &$term ) {

    		    if ( !is_object( $term ) )
    		        continue;

                $variation_term_counts[ $term->term_id ] = absint( $this->get_variations_count_in_term( $term ) );

            }

            set_transient( 'jck_wssv_term_counts', $variation_term_counts );

        }

        $term_counts = get_transient( 'wc_term_counts' );

        foreach ( $terms as &$term ) {

    		if ( !is_object( $term ) )
    		    continue;

    		if( !isset( $term_counts[ $term->term_id ] ) )
    		    continue;

    		$child_term_count = isset( $variation_term_counts[ $term->term_id ] ) ? $variation_term_counts[ $term->term_id ] : 0;

            $term_counts[ $term->term_id ] = $term_counts[ $term->term_id ] + $child_term_count;

			if ( empty( $term_counts[ $term->term_id ] ) )
			    continue;

			$term->count = absint( $term_counts[ $term->term_id ] );

    	}

    	return $terms;

	}

	/**
	 * Delete term counts transient
	 *
	 * When recount terms is run in backend of woo,
	 * delete our additional term counts transient, too.
	 */
    public function delete_term_counts_transient() {

        delete_transient( 'jck_wssv_term_counts' );

    }

	/**
	 * Helper: Get Variaitons count in term
	 *
	 * @param obj $term
	 * @return int
	 */
    public function get_variations_count_in_term( $term ) {

        global $wpdb;

        $sql = "
            SELECT COUNT(*) FROM `wp_posts` wp
            INNER JOIN `wp_postmeta` wm ON (wm.`post_id` = wp.`ID` AND wm.`meta_key`='_visibility')
            INNER JOIN `wp_term_relationships` wtr ON (wp.`ID` = wtr.`object_id`)
            INNER JOIN `wp_term_taxonomy` wtt ON (wtr.`term_taxonomy_id` = wtt.`term_taxonomy_id`)
            INNER JOIN `wp_terms` wt ON (wt.`term_id` = wtt.`term_id`)
            AND wtt.taxonomy = '".$term->taxonomy."' AND wt.`slug` = '".$term->slug."'
            AND wp.post_status = 'publish' AND ( wm.meta_value LIKE '%visible%' OR wm.meta_value LIKE '%catalog%' )
            AND wp.post_type = 'product_variation'
            ORDER BY wp.post_date DESC
        ";

        $count = $wpdb->get_var( $sql );

        return apply_filters( 'iconic_wssv_variations_count_in_term', $count, $term );

    }

    /**
     * Helper: Get current view
     *
     * @return str
     */
    public function get_current_view() {

        if( is_search() ) {
            return 'search';
        }

        if( is_filtered() ) {
            return 'filtered';
        }

        return 'catalog';

    }

    /**
     * Frontend: Taxonomies to change term counts for
     *
     * @param arr $taxonomies
     * @return arr
     */
    public function term_count_taxonomies( $taxonomies ) {

        $attributes = wc_get_attribute_taxonomies();

        if( $attributes && !empty( $attributes ) ) {
            foreach( $attributes as $attribute ) {
                $taxonomies[] = sprintf('pa_%s', $attribute->attribute_name);
            }
        }

        return $taxonomies;

    }

    /**
     * Admin: On product save
     */
    public function on_product_save( $post_id ) {

        if ( wp_is_post_revision( $post_id ) )
		    return;

        $post_type = get_post_type( $post_id );

        if( $post_type != "product" )
            return;

        $this->add_non_variation_attributes_to_variation( $post_id );
        if( isset( $_POST['menu_order'] ) ) { $this->update_product_order( $post_id, $_POST['menu_order'] ); }
        $this->delete_term_counts_transient();

    }

    /**
     * Admin: On variation save
     */
    public function on_variation_save( $variation_id, $i ) {

        $this->add_taxonomies_to_variation( $variation_id, $i );
        $this->add_attributes_to_variation( $variation_id, $i );
        $this->delete_term_counts_transient();

    }


    /**
     * Admin: Add non variaition attributes to variations
     *
     * This allows them to be seen in the layered nav query
     *
     * @param int $post_id
     */
    public function updated_product_attributes( $meta_id, $object_id, $meta_key, $_meta_value ) {

    	if( $meta_key == "_product_attributes" ) {

            $this->add_non_variation_attributes_to_variation( $object_id );

    	}
	}

    /**
     * Admin: Add non variaition attributes to variations
     *
     * This allows them to be seen in the layered nav query
     *
     * @param int $post_id
     */
	public function add_non_variation_attributes_to_variation( $post_id ) {

        if( $product = wc_get_product( $post_id ) ) {

            $variations = $product->get_children();

            if( $attributes = $product->get_attributes() ) {
                foreach( $attributes as $taxonomy => $attribute_data ) {
                    if( $attribute_data['is_variation'] == 0 ) {

                        $terms = wp_get_post_terms( $post_id, $taxonomy );

                        if( $variations && $terms && !is_wp_error( $terms ) ) {
                            foreach( $variations as $i => $variation_id ) {

                                $term_ids = array();

                                foreach( $terms as $term ) {

                                    $term_ids[] = $term->term_id;

                                }

                                $set_terms = wp_set_object_terms( $variation_id, $term_ids, $taxonomy );

                                $this->delete_count_transient( $taxonomy, $term->term_taxonomy_id );

                            }
                        }

                    }
                }
            }

        }

	}

	/**
	 * Helper: Delete count transient
	 *
	 * @param str $taxonomy
	 * @param int $taxonomy_id
	 */
    public function delete_count_transient( $taxonomy, $taxonomy_id ) {

        $transient_name = 'wc_ln_count_' . md5( sanitize_key( $taxonomy ) . sanitize_key( $taxonomy_id ) );
        delete_transient($transient_name);

    }

    /**
     * Admin: After product ordering
     */
    public function after_product_ordering() {

        global $wpdb;

        $product_id = isset( $_POST['id'] ) ? $_POST['id'] : false;
        $previd  = isset( $_POST['previd'] ) ? $_POST['previd'] : false;
		$nextid  = isset( $_POST['nextid'] ) ? $_POST['nextid'] : false;
		$new_pos = array(); // store new positions for ajax

		if( $product_id === false ) return;

		$siblings = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, menu_order FROM {$wpdb->posts} AS posts
			WHERE 	posts.post_type 	= 'product'
			AND 	posts.post_status 	IN ( 'publish', 'pending', 'draft', 'future', 'private' )
			AND 	posts.ID			NOT IN (%d)
			ORDER BY posts.menu_order ASC, posts.ID DESC
		", $product_id ) );

		$menu_order = 0;

		foreach ( $siblings as $sibling ) {

			// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
			if ( $nextid == $sibling->ID ) {
				$this->update_product_order( $product_id, $menu_order );
				$menu_order++;
			}

			// if repositioned post has been set, and new items are already in the right order, we can stop
			if ( isset( $new_pos[ $product_id ] ) && $sibling->menu_order >= $menu_order ) {
				break;
			}

			// set the menu order of the current sibling and increment the menu order
			$this->update_product_order( $sibling->ID, $menu_order );
			$new_pos[ $sibling->ID ] = $menu_order;
			$menu_order++;

			if ( ! $nextid && $previd == $sibling->ID ) {
    			$this->update_product_order( $product_id, $menu_order );
				$new_pos[$product_id] = $menu_order;
				$menu_order++;
			}

		}

    }

    /**
     * Helper: Get menu_order
     *
     * @param int $id
     */
    public function get_menu_order( $id ) {

        global $wpdb;

        return $wpdb->get_var( $wpdb->prepare( "
            SELECT menu_order FROM {$wpdb->posts} AS posts
            WHERE posts.ID = %d
        ", $id ) );

    }

    /**
     * Admin: Update variation_menu_order
     *
     * @param int $variation_id
     */
    public function update_variation_order( $variation_id, $parent_menu_order = false ) {

        $variation = get_post( absint( $variation_id ) );

        if( $variation ) {
            $variation_parent = get_post( absint( $variation->post_parent ) );
            $menu_order = sprintf('%d.%04d', $parent_menu_order ? $parent_menu_order : $variation_parent->menu_order, $variation->menu_order+1);

            update_post_meta($variation_id, 'variation_menu_order', $menu_order);
        }

    }

    /**
     * Helper: Update all variations variation_menu_order meta of a parent product
     *
     * @param int $product_id
     * @param int $parent_menu_order
     */
    public function update_variations_order( $product_id, $parent_menu_order ) {

        $product = wc_get_product( $product_id );

        if( $product ) {

            $variations = $product->get_children();

            if( !empty( $variations ) ) {

                foreach( $variations as $variation_id ) {
                    $this->update_variation_order( $variation_id, $parent_menu_order );
                }

            }

        }

    }

    /**
     * Helper: Update product variation_menu_order meta
     *
     * @param int $product_id
     * @param int $menu_order
     */
    public function update_product_order( $product_id, $menu_order ) {

        $menu_order = sprintf('%d.%04d', $menu_order, 1);

        update_post_meta($product_id, 'variation_menu_order', $menu_order);

        $this->update_variations_order($product_id, $menu_order);

    }

    /**
     * Helper: If not done so already, update the menu_order
     * for all products to include variations
     */
    public function update_products_order() {

        $order_set = get_option( 'jck_wssv_order_set' );

        if( !$order_set ) {

            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1
            );

            $products = new WP_Query( $args );

            if ( $products->have_posts() ) {

            	foreach( $products->posts as $product ) {

                	$this->update_product_order( $product->ID, $product->menu_order );

            	}

            }

            wp_reset_postdata();

            update_option( 'jck_wssv_order_set', true );

        }

    }

    /**
     * Frontend: modify catalog ordering args
     *
     * Adds 'variation_menu_order' to the sorting params, which
     * sorts by our custom meta_value of the same name
     *
     * @param arr $args
     */
    public function modify_catalog_ordering_args( $args ) {

        if( $args['orderby'] == "menu_order title" ) {
            $args['orderby'] = sprintf('variation_menu_order %s', $args['orderby'] );
        }

        return $args;

    }

    /**
     * Add product type (product_variation) to post class
     *
     * @since 1.1.0
     * @param array $classes
     * @param string|array $class
     * @param int $post_id
     * @return array
     */
    public function product_post_class( $classes, $class = '', $post_id = '' ) {

        if ( ! $post_id || 'product_variation' !== get_post_type( $post_id ) || version_compare($this->get_woo_version_number(), '2.6.0', '<') ) {
            return $classes;
        }

        $product = wc_get_product( $post_id );

        if ( $product ) {
            $classes[] = wc_get_loop_class();
            $classes[] = $product->stock_status;

            if ( $product->is_on_sale() ) {
                $classes[] = 'sale';
            }
            if ( $product->is_featured() ) {
                $classes[] = 'featured';
            }
            if ( $product->is_downloadable() ) {
                $classes[] = 'downloadable';
            }
            if ( $product->is_virtual() ) {
                $classes[] = 'virtual';
            }
            if ( $product->is_sold_individually() ) {
                $classes[] = 'sold-individually';
            }
            if ( $product->is_taxable() ) {
                $classes[] = 'taxable';
            }
            if ( $product->is_shipping_taxable() ) {
                $classes[] = 'shipping-taxable';
            }
            if ( $product->is_purchasable() ) {
                $classes[] = 'purchasable';
            }
            if ( $product->get_type() ) {
                $classes[] = "product-type-" . $product->get_type();
            }
        }

        if ( false !== ( $key = array_search( 'hentry', $classes ) ) ) {
            unset( $classes[ $key ] );
        }

        return $classes;

    }

    /**
     * Check whether the plugin is inactive.
     *
     * Reverse of is_plugin_active(). Used as a callback.
     *
     * @since 3.1.0
     * @see is_plugin_active()
     *
     * @param string $plugin Base plugin path from plugins directory.
     * @return bool True if inactive. False if active.
     */
    public function is_plugin_active( $plugin ) {

        return in_array( $plugin, (array) get_option( 'active_plugins', array() ) ) || $this->is_plugin_active_for_network( $plugin );

    }

    /**
     * Check whether the plugin is active for the entire network.
     *
     * Only plugins installed in the plugins/ folder can be active.
     *
     * Plugins in the mu-plugins/ folder can't be "activated," so this function will
     * return false for those plugins.
     *
     * @since 3.0.0
     *
     * @param string $plugin Base plugin path from plugins directory.
     * @return bool True, if active for the network, otherwise false.
     */
    public function is_plugin_active_for_network( $plugin ) {
        if ( !is_multisite() )
            return false;
        $plugins = get_site_option( 'active_sitewide_plugins');
        if ( isset($plugins[$plugin]) )
            return true;
        return false;
    }

    /**
     * Add product_variation to price filter widget
     *
     * @param arr $post_types
     * @return arr
     */
    public function add_product_variation_to_price_filter( $post_types ) {

        $post_types[] = 'product_variation';

        return $post_types;

    }

}

$jck_wssv = new JCK_WSSV();