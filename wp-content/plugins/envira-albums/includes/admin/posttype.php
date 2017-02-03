<?php
/**
 * Posttype admin class.
 *
 * @since 1.0.0
 *
 * @package Envira_Gallery
 * @author  Thomas Griffin
 */
class Envira_Albums_Posttype_Admin {

    /**
     * Holds the class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;

    /**
     * Path to the file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Update post type messages.
        add_filter( 'post_updated_messages', array( $this, 'messages' ) );

    }

    /**
     * Contextualizes the post updated messages.
     *
     * @since 1.0.0
     *
     * @global object $post    The current post object.
     * @param array $messages  Array of default post updated messages.
     * @return array $messages Amended array of post updated messages.
     */
    public function messages( $messages ) {

        global $post;

        // Contextualize the messages.
        $messages['envira_album'] = apply_filters( 'envira_album_messages',
            array(
                0  => '',
                1  => __( 'Envira album updated.', 'envira-album' ),
                2  => __( 'Envira album custom field updated.', 'envira-album' ),
                3  => __( 'Envira album custom field deleted.', 'envira-album' ),
                4  => __( 'Envira album updated.', 'envira-album' ),
                5  => isset( $_GET['revision'] ) ? sprintf( __( 'Envira album restored to revision from %s.', 'envira-albums' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
                6  => __( 'Envira album published.', 'envira-albums' ),
                7  => __( 'Envira album saved.', 'envira-albums' ),
                8  => __( 'Envira album submitted.', 'envira-albums' ),
                9  => sprintf( __( 'Envira album scheduled for: <strong>%1$s</strong>.', 'envira-albums' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
                10 => __( 'Envira album draft updated.', 'envira-albums' )
            )
        );

        return $messages;

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Envira_Albums_Posttype_Admin object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Albums_Posttype_Admin ) ) {
            self::$instance = new Envira_Albums_Posttype_Admin();
        }

        return self::$instance;

    }

}

// Load the posttype admin class.
$envira_albums_posttype_admin = Envira_Albums_Posttype_Admin::get_instance();