<?php
/**
 * WP List Table Admin Class.
 *
 * @since 1.3.0
 *
 * @package Envira_Albums
 * @author  Tim Carr
 */
class Envira_Albums_Admin_Table {

    /**
     * Holds the class object.
     *
     * @since 1.3.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 1.3.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Holds the base class object.
     *
     * @since 1.3.0
     *
     * @var object
     */
    public $base;

    /**
     * Holds the metabox class object.
     *
     * @since 1.3.0
     *
     * @var object
     */
    public $metabox;
    
    /**
     * Primary class constructor.
     *
     * @since 1.3.0
     */
    public function __construct() {

        // Append data to various admin columns.
        add_filter( 'manage_edit-envira_album_columns', array( $this, 'envira_columns' ) );
        add_action( 'manage_envira_album_posts_custom_column', array( $this, 'envira_custom_columns'), 10, 2 );

    }

    /**
     * Customize the post columns for the Envira Album post type.
     *
     * @since 1.3.0
     *
     * @param array $columns  The default columns.
     * @return array $columns Amended columns.
     */
    public function envira_columns( $columns ) {

        // Add additional columns we want to display.
        $envira_columns = array(
            'cb'            => '<input type="checkbox" />',
            'title'         => __( 'Title', 'envira-albums' ),
            'shortcode'     => __( 'Shortcode', 'envira-albums' ),
            'galleries'     => __( 'Number of Galleries', 'envira-albums' ),
            'modified'      => __( 'Last Modified', 'envira-albums' ),
            'date'          => __( 'Date', 'envira-albums' )
        );

        // Allow filtering of columns
        $envira_columns = apply_filters( 'envira_albums_table_columns', $envira_columns, $columns );

        // Return merged column set.  This allows plugins to output their columns (e.g. Yoast SEO),
        // and column management plugins, such as Admin Columns, should play nicely.
        return array_merge( $envira_columns, $columns );

    }

    /**
     * Add data to the custom columns added to the Envira Album post type.
     *
     * @since 1.3.0
     *
     * @global object $post  The current post object
     * @param string $column The name of the custom column
     * @param int $post_id   The current post ID
     */
    public function envira_custom_columns( $column, $post_id ) {

        global $post;
        $post_id = absint( $post_id );

        switch ( $column ) {
            /**
            * Shortcode
            */
            case 'shortcode' :
                echo '
                <div class="envira-code">
                    <code id="envira_shortcode_' . $post_id . '">[envira-album id="' . $post_id . '"]</code>
                    <a href="#" title="' . __( 'Copy Shortcode to Clipboard', 'envira-album' ) . '" data-clipboard-target="#envira_shortcode_' . $post_id . '" class="dashicons dashicons-clipboard envira-clipboard">
                        <span>' . __( 'Copy to Clipboard', 'envira-album' ) . '</span>
                    </a>
                </div>';
                break;

            /**
            * Galleries
            */
            case 'galleries':
                $data = get_post_meta( $post_id, '_eg_album_data', true);
                echo ( isset( $data['galleryIDs'] ) ? count( $data['galleryIDs'] ) : 0 );
                break; 

            /**
            * Last Modified
            */
            case 'modified' :
                the_modified_date();
                break;
        }

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.3.0
     *
     * @return object The Envira_Albums_Admin_Table object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Albums_Admin_Table ) ) {
            self::$instance = new Envira_Albums_Admin_Table();
        }

        return self::$instance;

    }

}

// Load the table admin class.
$envira_albums_table_admin = Envira_Albums_Admin_Table::get_instance();