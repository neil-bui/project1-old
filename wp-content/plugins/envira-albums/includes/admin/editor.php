<?php
/**
 * Editor class.
 *
 * @since 1.0.0
 *
 * @package Envira_Albums
 * @author  Tim Carr
 */
class Envira_Albums_Editor {

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
     * Holds the base class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $base;

    /**
     * Flag to determine if media modal is loaded.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public $loaded = false;

    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Envira_Albums::get_instance();

        // Add a custom media button to the editor.
        add_filter( 'media_buttons_context', array( $this, 'media_button' ) );

    }

    /**
     * Adds a custom gallery insert button beside the media uploader button.
     *
     * @since 1.0.0
     *
     * @param string $buttons  The media buttons context HTML.
     * @return string $buttons Amended media buttons context HTML.
     */
    public function media_button( $buttons ) {

        // Create the media button.
        $button = '<a id="envira-media-modal-button" href="#" class="button envira-albums-choose-album" data-action="album" title="' . esc_attr__( 'Add Album', 'envira-albums' ) . '" >
            <span class="envira-media-icon"></span> ' .
             __( 'Add Album', 'envira-albums' ) . 
        '</a>';

        // Filter the button
        $button = apply_filters( 'envira_albums_media_button', $button, $buttons );

        // Append the button.
        return $buttons . $button;

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Envira_Albums_Editor object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Albums_Editor ) ) {
            self::$instance = new Envira_Albums_Editor();
        }

        return self::$instance;

    }

}

// Load the editor class.
$envira_albums_editor = Envira_Albums_Editor::get_instance();