<?php
/**
 * Metabox class.
 *
 * @since 1.0.0
 *
 * @package Envira_Albums
 * @author  Tim Carr
 */
class Envira_Albums_Metaboxes {

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
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Load the base class object.
        $this->base = Envira_Albums::get_instance();

        // Load metabox assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'fix_plugin_js_conflicts' ), 100 );

        // Load the metabox hooks and filters.
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 1 );

        // Add the envira-gallery class to the form, so our styles can be applied
        add_action( 'post_edit_form_tag', array( $this, 'add_form_class' ) );

        // Load all tabs.
        add_action( 'envira_albums_tab_galleries', array( $this, 'galleries_tab' ) );
        add_action( 'envira_albums_tab_config', array( $this, 'config_tab' ) );
        add_action( 'envira_albums_tab_lightbox', array( $this, 'lightbox_tab' ) );
        add_action( 'envira_albums_tab_thumbnails', array( $this, 'thumbnails_tab' ) );
        add_action( 'envira_albums_tab_mobile', array( $this, 'mobile_tab' ) );
        add_action( 'envira_albums_tab_standalone', array( $this, 'standalone_tab' ) );
        add_action( 'envira_albums_tab_misc', array( $this, 'misc_tab' ) );

        // Add action to save metabox config options.
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );

    }

    /**
     * Loads styles for our metaboxes.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on the proper screen.
     */
    public function styles() {

        // Get current screen.
        $screen = get_current_screen();

        // Bail if we're not on the Envira Post Type screen.
        if ( 'envira_album' !== $screen->post_type ) {
            return;
        }

        // Bail if we're not on an editing screen.
        if ( 'post' !== $screen->base ) {
            return;
        }

        // Get Envira Gallery instance
        $instance = Envira_Gallery::get_instance();

        // Load necessary metabox styles from Envira Gallery
        wp_register_style( $instance->plugin_slug . '-metabox-style', plugins_url( 'assets/css/metabox.css', $instance->file ), array(), $instance->version );
        wp_enqueue_style( $instance->plugin_slug . '-metabox-style' );
        wp_enqueue_style( 'media-views' );

        // Fire a hook to load in custom metabox styles.
        do_action( 'envira_album_metabox_styles' );

    }
	/**
	 * Remove plugins scripts that break Envira's admin.
	 *
	 * @access public
	 * @return void
	 */
	public function fix_plugin_js_conflicts(){

        global $id, $post;

        // Get current screen.
        $screen = get_current_screen();

        // Bail if we're not on the Envira Post Type screen.
        if ( 'envira_album' !== $screen->post_type ) {
            return;
        }

		wp_dequeue_script( 'ngg-igw' );

	}
    /**
     * Loads scripts for our metaboxes.
     *
     * @since 1.0.0
     *
     * @global int $id      The current post ID.
     * @global object $post The current post object.
     * @return null         Return early if not on the proper screen.
     */
    public function scripts( $hook ) {

        global $id, $post;

        // Get current screen.
        $screen = get_current_screen();

        // Bail if we're not on the Envira Post Type screen.
        if ( 'envira_album' !== $screen->post_type ) {
            return;
        }

        // Bail if we're not on an editing screen.
        if ( 'post' !== $screen->base ) {
            return;
        }

        // Get Envira Gallery instance
        $instance = Envira_Gallery::get_instance();

        // Set the post_id for localization.
        $post_id = isset( $post->ID ) ? $post->ID : (int) $id;

        // Load WordPress necessary scripts.
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-droppable' );

        // Image Uploader (to get Yoast 3.x working)
        if ( $post_id > 0 ) {
            wp_enqueue_media( array(
                'post' => $post_id,
            ) );
        }

        // Gallery Tabs
        wp_register_script( $instance->plugin_slug . '-tabs-script', plugins_url( 'assets/js/min/tabs-min.js', $instance->file ), array( 'jquery' ), $instance->version, true );
        wp_enqueue_script( $instance->plugin_slug . '-tabs-script' );

        // Gallery Clipboard
        wp_register_script( $instance->plugin_slug . '-clipboard-script', plugins_url( 'assets/js/min/clipboard-min.js', $instance->file ), array( 'jquery' ), $instance->version, true );
        wp_enqueue_script( $instance->plugin_slug . '-clipboard-script' );

        // Conditional Fields
        wp_register_script( $instance->plugin_slug . '-conditional-fields-script', plugins_url( 'assets/js/min/conditional-fields-min.js', $instance->file ), array( 'jquery' ), $instance->version, true );
        wp_enqueue_script( $instance->plugin_slug . '-conditional-fields-script' );

        // Album Metabox
        wp_enqueue_script( $this->base->plugin_slug . '-metabox-script', plugins_url( 'assets/js/min/metabox-min.js', $this->base->file ), array( 'jquery' ), $this->base->version, true );
        wp_localize_script(
            $this->base->plugin_slug . '-metabox-script',
            'envira_albums_metabox',
            array(
                'ajax'                      => admin_url( 'admin-ajax.php' ),
                'get_gallery_images_nonce'  => wp_create_nonce( 'envira-albums-get-gallery-images' ),
                'id'                        => $post_id,
                'remove'                    => __( 'Are you sure you want to remove this gallery from the album?', 'envira-albums' ),
                'save_nonce'                => wp_create_nonce( 'envira-albums-save' ),
                'saving'                    => __( 'Saving', 'envira-albums' ),
                'search'                    => wp_create_nonce( 'envira-albums-search' ),
                'sort'                      => wp_create_nonce( 'envira-albums-sort' ),
            )
        );

        // Add custom CSS for hiding specific things.
        add_action( 'admin_head', array( $this, 'meta_box_css' ) );

        // Fire a hook to load in custom metabox scripts.
        do_action( 'envira_albums_metabox_scripts' );

    }

    /**
     * Returns the post types to skip for loading Envira metaboxes.
     *
     * @since 1.0.7
     *
     * @return array Array of skipped posttypes.
     */
    public function get_skipped_posttypes() {

        return apply_filters( 'envira_album_skipped_posttypes', array( 'attachment', 'revision', 'nav_menu_item', 'soliloquy', 'soliloquyv2' ) );

    }

    /**
     * Hides unnecessary meta box items on Envira post type screens.
     *
     * @since 1.0.0
     */
    public function meta_box_css() {

        ?>
        <style type="text/css">.misc-pub-section:not(.misc-pub-post-status):not(.misc-pub-visibility) { display: none; }</style>
        <?php

        // Fire action for CSS on Envira post type screens.
        do_action( 'envira_gallery_admin_css' );

    }

    /**
     * Creates metaboxes for handling and managing galleries.
     *
     * @since 1.0.0
     */
    public function add_meta_boxes() {

        global $post;

        // Let's remove all of those dumb metaboxes from our post type screen to control the experience.
        $this->remove_all_the_metaboxes();

        // Add our metaboxes to Envira CPT.

        // Types Metabox
        // Allows the user to upload galleries or choose an External Album Type
        // We don't display this if the Album is a Dynamic or Default Album, as these settings don't apply
        $type = $this->get_config( 'type', $this->get_config_default( 'type' ) );
        if ( ! in_array( $type, array( 'defaults', 'dynamic' ) ) ) {
            add_meta_box( 'envira-albums', __( 'Envira Albums', 'envira-albums' ), array( $this, 'meta_box_album_callback' ), 'envira_album', 'normal', 'high' );
        }

        // Settings Metabox
        add_meta_box( 'envira-albums-settings', __( 'Envira Album Settings', 'envira-albums' ), array( $this, 'meta_box_callback' ), 'envira_album', 'normal', 'high' );

        // Display the Album Code metabox if we're editing an existing Album
        if ( $post->post_status != 'auto-draft' ) {
            add_meta_box( 'envira-albums-code', __( 'Envira Album Code', 'envira-albums' ), array( $this, 'meta_box_album_code_callback' ), 'envira_album', 'side', 'default' );
        }

    }

     /**
     * Removes all the metaboxes except the ones I want on MY POST TYPE. RAGE.
     *
     * @since 1.0.0
     *
     * @global array $wp_meta_boxes Array of registered metaboxes.
     * @return smile $for_my_buyers Happy customers with no spammy metaboxes!
     */
    public function remove_all_the_metaboxes() {

        global $wp_meta_boxes;

        // This is the post type you want to target. Adjust it to match yours.
        $post_type  = 'envira_album';

        // These are the metabox IDs you want to pass over. They don't have to match exactly. preg_match will be run on them.
        $pass_over_defaults = array( 'submitdiv', 'envira' );

        if ( Envira_Gallery_Settings::get_instance()->get_setting( 'standalone_enabled' ) ) {
            $pass_over_defaults[] = 'slugdiv';
            $pass_over_defaults[] = 'authordiv';
            $pass_over_defaults[] = 'wpseo_meta';
        }

        $pass_over  = apply_filters( 'envira_albums_metabox_ids', $pass_over_defaults );

        // All the metabox contexts you want to check.
        $contexts   = apply_filters( 'envira_albums_metabox_contexts', array( 'normal', 'advanced', 'side' ) );

        // All the priorities you want to check.
        $priorities = apply_filters( 'envira_albums_metabox_priorities', array( 'high', 'core', 'default', 'low' ) );

        // Loop through and target each context.
        foreach ( $contexts as $context ) {
            // Now loop through each priority and start the purging process.
            foreach ( $priorities as $priority ) {
                if ( isset( $wp_meta_boxes[$post_type][$context][$priority] ) ) {
                    foreach ( (array) $wp_meta_boxes[$post_type][$context][$priority] as $id => $metabox_data ) {
                        // If the metabox ID to pass over matches the ID given, remove it from the array and continue.
                        if ( in_array( $id, $pass_over ) ) {
                            unset( $pass_over[$id] );
                            continue;
                        }

                        // Otherwise, loop through the pass_over IDs and if we have a match, continue.
                        foreach ( $pass_over as $to_pass ) {
                            if ( preg_match( '#^' . $id . '#i', $to_pass ) ) {
                                continue;
                            }
                        }

                        // If we reach this point, remove the metabox completely.
                        unset( $wp_meta_boxes[$post_type][$context][$priority][$id] );
                    }
                }
            }
        }

    }

    /**
     * Adds an envira-gallery class to the form when adding or editing an Album,
     * so our plugin's CSS and JS can target a specific element and its children.
     *
     * @since 1.3.0
     *
     * @param   WP_Post     $post   WordPress Post
     */
    public function add_form_class( $post ) {

        // Check the Post is an Album
        if ( 'envira_album' != get_post_type( $post ) ) {
            return;
        }

        echo ' class="envira-gallery"';

    }

    /**
     * Callback for displaying the Current Galleries section.
     *
     * @since 1.3.0
     *
     * @param object $post The current post object.
     */
    public function meta_box_album_callback( $post ) {

        // Get all album data
        $album_data = get_post_meta( $post->ID, '_eg_album_data', true );

        ?>
        <!-- Types -->
        <div id="envira-types">
            <!-- Native Envira Album - Drag and Drop Galleries -->
            <div id="envira-album-native" class="envira-tab envira-clear<?php echo ( ( $this->get_config( 'type', $this->get_config_default( 'type' ) ) == 'default' ) ? ' envira-active' : '' ); ?>">
                <input type="hidden" name="galleryIDs" value="<?php echo ( ( isset( $album_data['galleryIDs']) ? implode( ',', $album_data['galleryIDs'] ) : '' ) ); ?>" />

                <!-- Galleries -->
                <ul id="envira-album-drag-drop-area" class="envira-gallery-images-output">
                    <?php
                    // Output existing galleries
                    if ( isset( $album_data['galleryIDs'] ) ) {
                        foreach ( $album_data['galleryIDs'] as $gallery_id ) {

                            // Skip blank entries
                            if ( empty ( $gallery_id ) ) {
                                continue;
                            }

                            // Get the album gallery metadata
                            $item = array();
                            if ( isset( $album_data['galleries'][ $gallery_id ] ) ) {
                                $item = $album_data['galleries'][ $gallery_id ];
                            }

                            // Output the Gallery
                            $this->output_gallery_li( $gallery_id, $item, $post->ID );

                        }
                    }
                    ?>
                </ul>

                <!-- Instructions -->
                <p class="drag-drop-info<?php echo ( ( isset( $album_data['galleryIDs'] ) && count( $album_data['galleryIDs'] ) > 0 ) ? ' hidden' : '' ); ?>">
                    <span class="drag"><?php _e( 'Drag and Drop Galleries Here', 'envira-albums' ); ?></span>
                    <small><?php _e( 'or', 'envira-albums' ); ?></small>
                    <span class="click"><?php _e( 'Select Galleries below and click the &quot;Add Selected Galleries to Album&quot; Button', 'envira-albums' ); ?></span>
                </p>
            </div>
        </div>
        <?php

    }

    /**
     * Callback for displaying the Gallery Settings section.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function meta_box_callback( $post ) {

        // Keep security first.
        wp_nonce_field( 'envira-albums', 'envira-albums' );

        // Load view
        $this->base->load_admin_partial( 'metabox-album-settings', array(
            'post'  => $post,
            'tabs'  => $this->get_envira_tab_nav(),
        ) );

    }

    /**
     * Callback for displaying the Album Code metabox.
     *
     * @since 1.3.0
     *
     * @param object $post The current post object.
     */
    public function meta_box_album_code_callback( $post ) {

        // Load view
        $this->base->load_admin_partial( 'metabox-album-code', array(
            'post'        => $post,
            'album_data'  => get_post_meta( $post->ID, '_eg_album_data', true ),
        ) );

    }

    /**
     * Returns the types of albums available.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     * @return array       Array of gallery types to choose.
     */
    public function get_envira_types( $post ) {

        $types = array(
            'default' => __( 'Default', 'envira-albums' )
        );

        return apply_filters( 'envira_albums_types', $types, $post );

    }


    /**
     * Callback for getting all of the tabs for Envira galleries.
     *
     * @since 1.0.0
     *
     * @return array Array of tab information.
     */
    public function get_envira_tab_nav() {

        $tabs = array(
            'galleries' => __( 'Galleries', 'envira-albums' ),
            'config'    => __(' Config', 'envira-albums' ),
            'lightbox'  => __( 'Lightbox', 'envira-albums' ),
            'mobile'    => __( 'Mobile', 'envira-albums' ),
        );

        if ( Envira_Gallery_Settings::get_instance()->get_setting( 'standalone_enabled' ) ) {
            $tabs['standalone'] = __( 'Standalone', 'envira-gallery' );
        }

        $tabs = apply_filters( 'envira_albums_tab_nav', $tabs );

        // "Misc" tab is required.
        $tabs['misc'] = __( 'Misc', 'envira-albums' );

        return $tabs;

    }

    /**
     * Callback for displaying the UI for the Available Galleries tab.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function galleries_tab( $post ) {

        // Output the display based on the type of album being created.
        echo '<div id="envira-albums-main" class="envira-clear">';

        // Allow Addons to display a WordPress-style notification message
        echo apply_filters( 'envira_albums_galleries_tab_notice', '', $post );

        // Output the tab panel for the Gallery Type
        $this->galleries_display( $this->get_config( 'type', $this->get_config_default( 'type' ) ), $post );

        echo '</div>
              <div class="spinner"></div>';

    }

    /**
     * Determines the Galleries tab display based on the type of album selected.
     *
     * @since 1.0.0
     *
     * @param string $type The type of display to output.
     * @param object $post The current post object.
     */
    public function galleries_display( $type = 'default', $post ) {

        // Output a unique hidden field for settings save testing for each type of slider.
        echo '<input type="hidden" name="_eg_album_data[type_' . $type . ']" value="1" />';

        // Output the display based on the type of slider available.
        switch ( $type ) {
            case 'default' :
                $this->do_default_display( $post );
                break;
            default:
                do_action( 'envira_albums_display_' . $type, $post );
                break;
        }

    }


    /**
     * Callback for displaying the default gallery UI.
     *
     * @since 1.0.9
     *
     * @param object $post The current post object.
     */
    public function do_default_display( $post ) {

        // Get all album data
        $album_data = get_post_meta( $post->ID, '_eg_album_data', true );

        // Output all other galleries not assigned to this album
        // Build arguments
        $arguments = array(
            'post_type'         => 'envira',
            'post_status'       => 'publish',
            'posts_per_page'    => 10,
        );

        // Exclude galleries we already included in this album
        if ( isset( $album_data['galleryIDs'] ) ) {
            $arguments['post__not_in'] = $album_data['galleryIDs'];
        }

        // Get galleries and output
        $galleries = new WP_Query( $arguments );
        $instance = Envira_Gallery::get_instance();
        ?>

        <!-- Title and Help -->
        <p class="envira-intro">
            <?php _e( 'Available Galleries', 'envira-albums' ); ?>
            <small>
                <?php _e( 'Displaying the most recent Envira Galleries. Please use the search box to display all matching Envira Galleries.', 'envira-albums' ); ?>

                <?php _e( 'Need some help?', 'envira-albums' ); ?>
                <a href="http://enviragallery.com/docs/albums-addon/" class="envira-doc" target="_blank">
                    <?php _e( 'Read the Documentation', 'envira-albums' ); ?>
                </a>
                or
                <a href="https://www.youtube.com/embed/GOQ7IVczsyM/?rel=0" class="envira-video" target="_blank">
                    <?php _e( 'Watch a Video', 'envira-albums' ); ?>
                </a>
            </small>
        </p>

        <!-- Add Selected & Search -->
        <nav class="envira-tab-options">
            <a href="#" class="button button-primary envira-galleries-add">
                <?php _e( 'Add Selected Galleries to Album', 'envira-albums' ); ?>
            </a>

            <input type="search" name="search" value="" placeholder="<?php _e( 'Search Galleries', 'envira-albums' ); ?>" id="envira-albums-gallery-search" />
        </nav>

        <?php
        do_action( 'envira_albums_do_default_display', $post );
        ?>
        <ul id="envira-albums-output" class="envira-gallery-images-output">
            <?php
            // Output Available Galleries
            if ( count( $galleries->posts ) > 0 ) {
                foreach ( $galleries->posts as $gallery ) {

                    // Get Gallery
                    $data = $instance->get_gallery( $gallery->ID );

                    if ( !$data ) {
                        // there is no data information - possibly a result of an auto-save without the user saving/publishing the post?
                        // generate the $item with just the data from the post itself

                        $gallery_post = get_post( $gallery->ID );

                        if ( trim( $gallery_post->post_title ) == '' ) {
                            // if there is no title, assign "(no title)" as the title
                            $item_title = __('(no title)', 'envira-albums');
                        } else {
                            $item_title = $gallery_post->post_title;
                        }

                        $item = array(
                            'id'                => $gallery->ID,
                            'title'             => $item_title,
                            'caption'           => ! empty( $gallery_post->post_excerpt ) ? $gallery_post->post_excerpt : '',
                        );

                    } else {

                        // Skip Default and Dynamic Galleries
                        if ( isset( $data['config']['type'] ) ) {
                            if ( $data['config']['type'] == 'dynamic' || $data['config']['type'] == 'defaults' ) {
                                continue;
                            }
                        }

                        // Attempt To Pull Post Title Over Internal Title
                        $gallery_post = get_post( $data['id'] );
                        if ( ! empty( $gallery_post->post_title ) ) {
                            $title = $gallery_post->post_title;
                        } else {
                            $title = ! empty( $data['config']['title'] ) ? $data['config']['title'] : '(no title)';

                        }

                        // Build item array comprising of gallery metadata
                        $item = array(
                            'id'                => $data['id'],
                            'title'             => $title,
                            'caption'           => ! empty( $data['config']['description'] ) ? $data['config']['description'] : '',
                        );

                    }

                    // Output <li> element
                    $this->output_gallery_li( $gallery->ID, $item, $post->ID );

                }
            }
            ?>
        </ul>

        <!-- Add Selected -->
        <nav class="envira-select-options">
            <a href="#" class="button button-primary envira-galleries-add">
                <?php _e( 'Add Selected Galleries to Album', 'envira-albums' ); ?>
            </a>
        </nav>
        <?php

    }

    /**
     * Outputs the <li> element for a gallery
     *
     * @param int       $gallery_id     The ID of the item to retrieve.
     * @param array     $item           The item data (i.e. album gallery metadata)
     * @param int       $post_id        Album ID
     * @return string                   The HTML output for the gallery item.
     * @return null
     */
    public function output_gallery_li( $gallery_id, $item, $album_id ) {

        // Define the required key/value pairs for the Gallery, if it's inserted into the Album
        $defaults = array(
            'id'                => '',
            'title'             => '',
            'caption'           => '',
            'alt'               => '',
            'publish_date'      => '',
            'cover_image_id'    => '',
            'cover_image_url'   => '',
        );

        // Merge the item with the defaults, so we always have a standardised array
        $item = array_merge( $defaults, $item );

        // Add id to $item for Backbone model
        $item['id'] = $gallery_id;

        // Get the cover image ID and URL
        $item['cover_image_id'] = $this->get_gallery_cover_image_id( $item );
        $item['cover_image_url'] = $this->get_gallery_cover_image_url( $item );

        // Allow addons to populate the item's data - for example, tags which are stored against the attachment
        $item = apply_filters( 'envira_albums_get_gallery_item', $item, $gallery_id, $album_id );
        $item['alt'] = str_replace( "&quot;", '\"', $item['alt'] );

        // Get the 150x150 thumbnail
        if ( ! empty( $item['cover_image_id'] ) && is_numeric( $item['cover_image_id'] ) ) {
            $thumbnail = wp_get_attachment_image_src( $item['cover_image_id'], 'thumbnail' );
        } else {
            $thumbnail = array( $item['cover_image_url'] );
        }

        // Output
        ?>
        <li id="envira-gallery-<?php echo $gallery_id; ?>" class="envira-gallery-image" data-envira-gallery="<?php echo $gallery_id; ?>" data-envira-album-gallery-model='<?php echo json_encode( $item, JSON_HEX_APOS ); ?>'>
            <?php
            if ( is_null( $thumbnail[0] ) ) {
                ?>
                <div class="placeholder-image"></div>
                <?php
            } else {
                ?>
                <img src="<?php echo esc_url( $thumbnail[0] ); ?>" />
                <?php
            }
            ?>

            <div class="meta">
                <div class="title"><?php echo $item['title']; ?></div>
            </div>

            <a href="#" class="check"><div class="media-modal-icon"></div></a>
            <a href="#" class="dashicons dashicons-trash envira-gallery-remove-image" title="<?php _e( 'Remove Gallery from Album?', 'envira-albums' ); ?>"></a>
            <a href="#" class="dashicons dashicons-edit envira-gallery-modify-image" title="<?php _e( 'Modify Gallery', 'envira-albums' ); ?>"></a>
        </li>
        <?php

    }

    /**
    * Helper method to retrieve a Gallery, and run a filter which allows
    * Addons to populate the Gallery data if necessary - for example, the Dynamic
    * and Instagram Addons hook into this to tell us the available images
    * at the time of the query
    *
    * @since 1.2.4.3
    *
    * @param int    $gallery_id     Gallery ID
    * @return array                 Gallery
    */
    public function get_gallery_data( $gallery_id ) {

        // Get gallery data from Post Meta
        $data = get_post_meta( $gallery_id, '_eg_gallery_data', true );

        // Allow Addons to filter the information
        $data = apply_filters( 'envira_albums_metaboxes_get_gallery_data', $data, $gallery_id );

        // Return
        return $data;

    }

    /**
     * Returns the Attachment ID of the gallery data's cover image.
     * If no cover image has been defined, returns the first available Attachment ID
     * within the gallery
     *
     * @since 1.2.4.3
     *
     * @param array  $item           Album Gallery Data
     * @return int                   Image ID
     */
    public function get_gallery_cover_image_id( $item ) {

        // If the Gallery within the Album already has a cover image ID defined, return that
        if ( isset ( $item['cover_image_id'] ) && ! empty( $item['cover_image_id'] ) ) {
            return $item['cover_image_id'];
        }

        // Get Gallery
        $gallery_data = Envira_Gallery::get_instance()->get_gallery( $item['id'] );

        // Get the first available image from the gallery, in case we need to use it
        // as the cover image
        if ( isset( $gallery_data['gallery'] ) && ! empty( $gallery_data['gallery'] ) ) {
            // Get the first image
            $images = $gallery_data['gallery'];
            reset( $images );
            $key = key( $images );
        }

        // Return the first image's attachment ID
        if ( isset( $key ) ) {
            return $key;
        }

    }

    /**
     * Returns the image URL of the gallery data's cover image.
     * If no cover image has been defined, returns the first available image URL
     * within the gallery
     *
     * @since 1.3.0
     *
     * @param array  $item           Album Gallery Data
     * @return string                Image URL
     */
    public function get_gallery_cover_image_url( $item ) {

        // If the Gallery within the Album already has a cover image URL defined, return that
        if ( isset ( $item['cover_image_url'] ) && ! empty( $item['cover_image_url'] ) ) {
            return $item['cover_image_url'];
        }

        // Get Gallery
        $gallery_data = Envira_Gallery::get_instance()->get_gallery( $item['id'] );

        // Allow External Galleries (Instagram, Featured Content) to inject images into the gallery array.
        // This ensures that a cover image URL can be found / chosen.
        $gallery_data['gallery'] = apply_filters( 'envira_albums_metabox_gallery_inject_images', ( isset( $gallery_data['gallery'] ) ? $gallery_data['gallery'] : array() ), $item['id'], $gallery_data );

        // Get the first available image from the gallery, as we need to use that
        if ( isset( $gallery_data['gallery'] ) && ! empty( $gallery_data['gallery'] ) ) {
            // Get the first image
            $images = $gallery_data['gallery'];
            reset( $images );
            $key = key( $images );
            $image = $images[ $key ];
        }

        // Return the first image's URL
        if ( isset( $image ) ) {
            return $image['src'];
        }

    }

    /**
     * Callback for displaying the UI for setting album config options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function config_tab( $post ) {

        ?>
        <div id="envira-config">
            <p class="envira-intro">
                <?php _e( 'Album Settings', 'envira-albums' ); ?>
                <small>
                    <?php _e( 'The settings below adjust the basic configuration options for the Album.', 'envira-albums' ); ?>
                    <br />
                    <?php _e( 'Need some help?', 'envira-albums' ); ?>
                    <a href="http://enviragallery.com/docs/albums-addon/" class="envira-doc" target="_blank">
                        <?php _e( 'Read the Documentation', 'envira-albums' ); ?>
                    </a>
                    or
                    <a href="https://www.youtube.com/embed/GOQ7IVczsyM/?rel=0" class="envira-video" target="_blank">
                        <?php _e( 'Watch a Video', 'envira-albums' ); ?>
                    </a>
                </small>
            </p>
            <?php

            // determine if this is a dynamic gallery - if so, add the type variable so that
            // it says probably along with the rest of the settings

            // get option
            $dynamic_id = get_option( 'envira_dynamic_album' );

            if ( $dynamic_id && $dynamic_id == $post->ID ) :

                echo '<input type="hidden" name="_eg_album_data[config][type]" value="dynamic" />';

            endif;

            ?>
            <table class="form-table" style="margin-bottom: 0;">
                <tbody>
                    <tr id="envira-config-columns-box">
                        <th scope="row">
                            <label for="envira-config-columns"><?php _e( 'Number of Album Columns', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-columns" name="_eg_album_data[config][columns]">
                                <?php foreach ( (array) $this->get_columns() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'columns', $this->get_config_default( 'columns' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Determines the number of columns in the gallery. Automatic will attempt to fill each row as much as possible before moving on to the next row.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php // New Automatic Layout / Justified Layout Options ?>
            <div id="envira-config-justified-settings-box">
                <table class="form-table" style="margin-bottom: 0;">
                    <tbody>
                        <tr id="envira-config-justified-row-height">
                            <th scope="row">
                                <label for="envira-config-justified-row-height"><?php _e( 'Automatic Layout: Row Height', 'envira-gallery' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-justified-row-height" type="number" name="_eg_album_data[config][justified_row_height]" value="<?php echo $this->get_config( 'justified_row_height', $this->get_config_default( 'justified_row_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                                <p class="description"><?php _e( 'Determines how high (in pixels) each row will be. 150px is default. ', 'envira-gallery' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-justified-margins">
                            <th scope="row">
                                <label for="envira-config-justified-margins"><?php _e( 'Automatic Layout: Margins', 'envira-gallery' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-justified-margins" type="number" name="_eg_album_data[config][justified_margins]" value="<?php echo $this->get_config( 'justified_margins', $this->get_config_default( 'justified_margins' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                                <p class="description"><?php _e( 'Sets the space between the images (defaults to 1)', 'envira-gallery' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-gallery-justified-last-row">
                            <th scope="row">
                                <label for="envira-config-gallery-last-row"><?php _e( 'Automatic Layout: Last Row', 'envira-gallery' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-gallery-last-row" name="_eg_album_data[config][justified_last_row]">
                                    <?php foreach ( (array) $this->get_justified_last_row() as $i => $data ) : ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'justified_last_row', $this->get_config_default( 'justified_last_row' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Sets how the last row is displayed.', 'envira-gallery' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-gallery-justified-theme-box">
                            <th scope="row">
                                <label for="envira-config-gallery-justified-theme"><?php _e( 'Automatic Layout: Gallery Theme', 'envira-gallery' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-gallery-justified-theme" name="_eg_album_data[config][justified_gallery_theme]">
                                    <?php foreach ( (array) $this->get_justified_gallery_themes() as $i => $data ) : ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'justified_gallery_theme', $this->get_config_default( 'justified_gallery_theme' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Sets the theme for the gallery display.', 'envira-gallery' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="envira-config-description-settings-box">
                <table class="form-table no-bottom-margin">
                    <tbody>
                    <!-- Back to Album Support -->
                    <tr id="envira-config-back-box">
                        <th scope="row">
                            <label for="envira-config-back"><?php _e( 'Display Back to Album Link?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-back" type="checkbox" name="_eg_album_data[config][back]" value="1" <?php checked( $this->get_config( 'back', $this->get_config_default( 'back' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If enabled and Lightbox is disabled, when the visitor clicks on a Gallery in this Album, they will see a link at the top of the Gallery to return back to this Album.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>

                    <!-- Back to Album Text -->
                    <tr id="envira-config-back-label-box">
                        <th scope="row">
                            <label for="envira-config-back-label"><?php _e( 'Back to Album Label', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-back-label" type="text" name="_eg_album_data[config][back_label]" value="<?php echo $this->get_config( 'back_label', $this->get_config_default( 'back_label' ) ); ?>" />
                        </td>
                    </tr>

                    <!-- Display Alignment -->
                    <tr id="envira-config-album-alignment-box">
                        <th scope="row">
                            <label for="envira-config-album-alignment"><?php _e( 'Align Album?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-album-alignment" name="_eg_album_data[config][album_alignment]">
                                <?php foreach ( (array) $this->get_album_alignment_options() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'album_alignment', $this->get_config_default( 'album_alignment' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Choose an alignment for this album. This will add a CSS class to the \'envira-album-wrap\' div.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <!-- Display Width Percentage -->
                    <tr id="envira-config-album-width-box">
                        <th scope="row">
                            <label for="envira-config-album-width"><?php _e( 'Album Width', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-album-width" type="number" name="_eg_album_data[config][album_width]" value="<?php echo $this->get_config( 'album_width', $this->get_config_default( 'album_width' ) ); ?>" /> <span class="envira-unit"><?php _e( '%', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'Overrides the default album width of 100%, especially useful if you are defining a left or right alignment for the album.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <tr id="envira-config-album-theme-box">
                        <th scope="row">
                            <label for="envira-config-album-theme"><?php _e( 'Album Theme', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-album-theme" name="_eg_album_data[config][gallery_theme]">
                                <?php foreach ( (array) $this->get_gallery_themes() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'gallery_theme', $this->get_config_default( 'gallery_theme' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the theme for the gallery display.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <!-- Display Description -->
                    <tr id="envira-config-display-description-box">
                        <th scope="row">
                            <label for="envira-config-display-description"><?php _e( 'Display Album Description?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-display-description" name="_eg_album_data[config][description_position]">
                                <?php foreach ( (array) $this->get_display_description_options() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'description_position', $this->get_config_default( 'description_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Choose to display a description above or below this album\'s galleries.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <!-- Description -->
                    <tr id="envira-config-description-box">
                        <th scope="row">
                            <label for="envira-album-description"><?php _e( 'Album Description', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <?php
                            $description = $this->get_config( 'description' );
                            if ( empty( $description ) ) {
                                $description = $this->get_config_default( 'description' );
                            }
                            wp_editor( $description, 'envira-album-description', array(
                                'media_buttons' => false,
                                'wpautop'       => true,
                                'tinymce'       => true,
                                'textarea_name' => '_eg_album_data[config][description]',
                            ) );
                            ?>
                            <p class="description"><?php _e( 'The description to display for this album.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <tr id="envira-config-title-automattic-box">
                        <th scope="row">
                            <label for="envira-config-title"><?php _e( 'Display Gallery Titles?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-title" type="checkbox" name="_eg_album_data[config][display_titles_automatic]" value="1" <?php checked( $this->get_config( 'display_titles_automatic', $this->get_config_default( 'display_titles_automatic' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Displays gallery titles as captions in automatic layout.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>
            <div id="envira-config-standard-settings-box">
                <table class="form-table">
                    <tbody>
                    <!-- Display Gallery Titles -->
                    <tr id="envira-config-title-box">
                        <th scope="row">
                            <label for="envira-config-title"><?php _e( 'Display Gallery Titles?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-title" name="_eg_album_data[config][display_titles]">
                                <?php

                                    // We need to support the previous "1" = below value, so ensure that 1 is mapped to "below"

                                    if ( $this->get_config( 'display_titles') == 1 ) {
                                        $display_title_config = 'below';
                                    } else {
                                        $display_title_config = $this->get_config( 'display_titles', $this->get_config_default( 'display_titles' ) );
                                    }

                                ?>
                                <?php
                                foreach ( (array) $this->get_title_placement_options() as $i => $data ) {
                                    ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $display_title_config ); ?>><?php echo $data['name']; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e( 'Choose to display an image title above or below the gallery image.', 'envira-albums' ); ?></p>
                        </td>

                        <?php /* <td>
                            <input id="envira-config-title" type="checkbox" name="_eg_album_data[config][display_titles]" value="1" <?php checked( $this->get_config( 'display_titles', $this->get_config_default( 'display_titles' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Displays gallery titles above or below each gallery image.', 'envira-albums' ); ?></span>
                        </td> */ ?>
                    </tr>

                    <!-- Display Gallery Caption -->
                    <tr id="envira-config-caption-box">
                        <th scope="row">
                            <label for="envira-config-caption"><?php _e( 'Display Gallery Captions?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-caption" type="checkbox" name="_eg_album_data[config][display_captions]" value="1" <?php checked( $this->get_config( 'display_captions', $this->get_config_default( 'display_captions' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Displays gallery captions below each gallery image.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>

                    <!-- Display Gallery Image Count -->
                    <tr id="envira-config-image-count-box">
                        <th scope="row">
                            <label for="envira-config-image-count"><?php _e( 'Display Gallery Image Count', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-image-count" type="checkbox" name="_eg_album_data[config][display_image_count]" value="1" <?php checked( $this->get_config( 'display_image_count', $this->get_config_default( 'display_image_count' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Displays the number of images in each gallery below each gallery image.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>

                    <!-- Gutter and Margin -->
                    <tr id="envira-config-gutter-box">
                        <th scope="row">
                            <label for="envira-config-gutter"><?php _e( 'Column Gutter Width', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-gutter" type="number" name="_eg_album_data[config][gutter]" value="<?php echo $this->get_config( 'gutter', $this->get_config_default( 'gutter' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'Sets the space between the columns (defaults to 10).', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-margin-box">
                        <th scope="row">
                            <label for="envira-config-margin"><?php _e( 'Margin Below Each Image', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-margin" type="number" name="_eg_album_data[config][margin]" value="<?php echo $this->get_config( 'margin', $this->get_config_default( 'margin' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'Sets the space below each item in the album.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <!-- Sorting -->
                    <tr id="envira-config-sorting-box">
                        <th scope="row">
                            <label for="envira-config-sorting"><?php _e( 'Sorting', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-sorting" name="_eg_album_data[config][sorting]">
                                <?php
                                foreach ( (array) $this->get_sorting_options() as $i => $data ) {
                                    ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'sorting', $this->get_config_default( 'sorting' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e( 'Choose to sort the galleries in a different order than displayed on the Galleries tab.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-sorting-direction-box">
                        <th scope="row">
                            <label for="envira-config-sorting-direction"><?php _e( 'Direction', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-sorting-direction" name="_eg_album_data[config][sorting_direction]">
                                <?php
                                foreach ( (array) $this->get_sorting_directions() as $i => $data ) {
                                    ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'sorting_direction', $this->get_config_default( 'sorting_direction' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </td>
                    </tr>

                    <!-- Image Sizes -->
                    <tr id="envira-config-crop-size-box">
                        <th scope="row">
                            <label for="envira-config-crop-width"><?php _e( 'Image Dimensions', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-crop-width" type="number" name="_eg_album_data[config][crop_width]" value="<?php echo $this->get_config( 'crop_width', $this->get_config_default( 'crop_width' ) ); ?>" /> &#215; <input id="envira-config-crop-height" type="number" name="_eg_album_data[config][crop_height]" value="<?php echo $this->get_config( 'crop_height', $this->get_config_default( 'crop_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'You should adjust these dimensions based on the number of columns in your album.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-crop-box">
                        <th scope="row">
                            <label for="envira-config-crop"><?php _e( 'Crop Images?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-crop" type="checkbox" name="_eg_album_data[config][crop]" value="<?php echo $this->get_config( 'crop', $this->get_config_default( 'crop' ) ); ?>" <?php checked( $this->get_config( 'crop', $this->get_config_default( 'crop' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If enabled, forces images to exactly match the sizes defined above for Image Dimensions.', 'envira-albums' ); ?></span>
                            <span class="description"><?php _e( 'If disabled, images will be resized to maintain their aspect ratio.', 'envira-albums' ); ?></span>

                        </td>
                    </tr>
                    <tr id="envira-config-dimensions-box">
                        <th scope="row">
                            <label for="envira-config-dimensions"><?php _e( 'Set Dimensions on Images?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-dimensions" type="checkbox" name="_eg_album_data[config][dimensions]" value="<?php echo $this->get_config( 'dimensions', $this->get_config_default( 'dimensions' ) ); ?>" <?php checked( $this->get_config( 'dimensions', $this->get_config_default( 'dimensions' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the width and height attributes on the img element. Only needs to be enabled if you need to meet Google Pagespeeds requirements.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-isotope-box">
                        <th scope="row">
                            <label for="envira-config-isotope"><?php _e( 'Enable Isotope?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-isotope" type="checkbox" name="_eg_album_data[config][isotope]" value="<?php echo $this->get_config( 'isotope', $this->get_config_default( 'isotope' ) ); ?>" <?php checked( $this->get_config( 'isotope', $this->get_config_default( 'isotope' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables isotope/masonry layout support for the main gallery images.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-css-animations-box">
                        <th scope="row">
                            <label for="envira-config-css-animations"><?php _e( 'Enable CSS Animations?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-css-animations" type="checkbox" name="_eg_album_data[config][css_animations]" value="<?php echo $this->get_config( 'css_animations', $this->get_config_default( 'css_animations' ) ); ?>" <?php checked( $this->get_config( 'css_animations', $this->get_config_default( 'css_animations' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables CSS animations when loading the main gallery images.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
					<tr id="envira-config-css-opacity-box">
						<th scope="row">
							<label for="envira-config-css-opacity"><?php _e( 'Image Opacity', 'envira-gallery' ); ?></label>
						</th>
						<td>
							<input id="envira-config-css-opacity" type="number" name="_eg_album_data[config][css_opacity]" min="0" max="100" step="1" value="<?php echo $this->get_config( 'css_opacity', $this->get_config_default( 'css_opacity' ) ); ?>" /><span class="envira-unit">%</span>
							<p class="description"><?php _e( 'The opacity to display images at when loading the main gallery images using CSS animations (between 1 and 100%).', 'envira-gallery' ); ?></p>
						</td>
					</tr>
                    
                    <?php do_action( 'envira_albums_config_box', $post ); ?>
                
                </tbody>
            </table>
        </div>

    </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting gallery lightbox options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function lightbox_tab( $post ) {

        ?>
        <div id="envira-lightbox">
            <p class="envira-intro">
                <?php _e( 'Lightbox Settings', 'envira-albums' ); ?>
                <small>
                    <?php _e( 'The settings below adjust the lightbox output.', 'envira-albums' ); ?>
                    <br />
                    <?php _e( 'Need some help?', 'envira-albums' ); ?>
                    <a href="http://enviragallery.com/docs/albums-addon/" class="envira-doc" target="_blank">
                        <?php _e( 'Read the Documentation', 'envira-albums' ); ?>
                    </a>
                    or
                    <a href="https://www.youtube.com/embed/GOQ7IVczsyM/?rel=0" class="envira-video" target="_blank">
                        <?php _e( 'Watch a Video', 'envira-albums' ); ?>
                    </a>
                </small>
            </p>
            <table class="form-table no-margin">
                <tbody>
                    <tr id="envira-config-lightbox">
                        <th scope="row">
                            <label for="envira-config-lightbox"><?php _e( 'Enable Lightbox?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-lightbox" type="checkbox" name="_eg_album_data[config][lightbox]" value="1"<?php checked( $this->get_config( 'lightbox', $this->get_config_default( 'lightbox' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'If checked, displays the Gallery in a lightbox when the album cover image is clicked.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div id="envira-lightbox-settings">
                <table class="form-table">
                    <tbody>
                        <tr id="envira-config-lightbox-theme">
                            <th scope="row">
                                <label for="envira-config-lightbox"><?php _e( 'Album Lightbox Theme', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-lightbox-theme" name="_eg_album_data[config][lightbox_theme]">
                                    <?php foreach ( (array) $this->get_lightbox_themes() as $i => $data ) : ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'lightbox_theme', $this->get_config_default( 'lightbox_theme' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Sets the theme for the album lightbox display.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-title-display-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-title-display"><?php _e( 'Caption Position', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-lightbox-title-display" name="_eg_album_data[config][title_display]">
                                    <?php foreach ( (array) $this->get_title_displays() as $i => $data ) : ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'title_display', $this->get_config_default( 'title_display' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Sets the display of the lightbox image\'s caption.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-arrows-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-arrows"><?php _e( 'Enable Gallery Arrows?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-arrows" type="checkbox" name="_eg_album_data[config][arrows]" value="1"<?php checked( $this->get_config( 'arrows', $this->get_config_default( 'arrows' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Enables or disables the gallery lightbox navigation arrows.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-arrows-position-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-arrows-position"><?php _e( 'Gallery Arrow Position', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-lightbox-arrows-position" name="_eg_album_data[config][arrows_position]">
                                    <?php foreach ( (array) $this->get_arrows_positions() as $i => $data ) : ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'arrows_position', $this->get_config_default( 'arrows_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Sets the position of the gallery lightbox navigation arrows.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-keyboard-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-keyboard"><?php _e( 'Enable Keyboard Navigation?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-keyboard" type="checkbox" name="_eg_album_data[config][keyboard]" value="<?php echo $this->get_config( 'keyboard', $this->get_config_default( 'keyboard' ) ); ?>" <?php checked( $this->get_config( 'keyboard', $this->get_config_default( 'keyboard' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Enables or disables keyboard navigation in the gallery lightbox.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-mousewheel-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-mousewheel"><?php _e( 'Enable Mousewheel Navigation?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-mousewheel" type="checkbox" name="_eg_album_data[config][mousewheel]" value="<?php echo $this->get_config( 'mousewheel', $this->get_config_default( 'mousewheel' ) ); ?>" <?php checked( $this->get_config( 'mousewheel', $this->get_config_default( 'mousewheel' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Enables or disables mousewheel navigation in the gallery.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-toolbar-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-toolbar"><?php _e( 'Enable Gallery Toolbar?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-toolbar" type="checkbox" name="_eg_album_data[config][toolbar]" value="1"<?php checked( $this->get_config( 'toolbar', $this->get_config_default( 'toolbar' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Enables or disables the gallery lightbox toolbar.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-toolbar-title-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-toolbar-title"><?php _e( 'Display Title in Gallery Toolbar?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-toolbar-title" type="checkbox" name="_eg_album_data[config][toolbar_title]" value="<?php echo $this->get_config( 'toolbar_title', $this->get_config_default( 'toolbar_title' ) ); ?>" <?php checked( $this->get_config( 'toolbar_title', $this->get_config_default( 'toolbar_title' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Display the gallery title in the lightbox toolbar.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-toolbar-position-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-toolbar-position"><?php _e( 'Gallery Toolbar Position', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-lightbox-toolbar-position" name="_eg_album_data[config][toolbar_position]">
                                    <?php foreach ( (array) $this->get_toolbar_positions() as $i => $data ) : ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'toolbar_position', $this->get_config_default( 'toolbar_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Sets the position of the lightbox toolbar.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-aspect-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-aspect"><?php _e( 'Keep Aspect Ratio?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-toolbar" type="checkbox" name="_eg_album_data[config][aspect]" value="<?php echo $this->get_config( 'aspect', $this->get_config_default( 'aspect' ) ); ?>" <?php checked( $this->get_config( 'aspect', $this->get_config_default( 'aspect' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'If enabled, images will always resize based on the original aspect ratio.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-loop-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-loop"><?php _e( 'Loop Gallery Navigation?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-loop" type="checkbox" name="_eg_album_data[config][loop]" value="<?php echo $this->get_config( 'loop', $this->get_config_default( 'loop' ) ); ?>" <?php checked( $this->get_config( 'loop', $this->get_config_default( 'loop' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Enables or disables infinite navigation cycling of the lightbox gallery.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>

                        <tr id="envira-config-lightbox-open-close-effect-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-open-close-effect"><?php _e( 'Lightbox Open/Close Effect', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-lightbox-open-close-effect" name="_eg_album_data[config][lightbox_open_close_effect]">
                                    <?php
                                    // Standard Effects
                                    foreach ( (array) $this->get_transition_effects() as $i => $data ) {
                                        ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'lightbox_open_close_effect', $this->get_config_default( 'lightbox_open_close_effect' ) ) ); ?>><?php echo $data['name']; ?></option>
                                        <?php
                                    }

                                    // Easing Effects
                                    foreach ( (array) $this->get_easing_transition_effects() as $i => $data ) {
                                        ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'lightbox_open_close_effect', $this->get_config_default( 'lightbox_open_close_effect' ) ) ); ?>><?php echo $data['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e( 'Type of transition when opening and closing the lightbox.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-effect-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-effect"><?php _e( 'Lightbox Transition Effect', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-lightbox-effect" name="_eg_album_data[config][effect]">
                                    <?php
                                    // Standard Effects
                                    foreach ( (array) $this->get_transition_effects() as $i => $data ) {
                                        ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'effect', $this->get_config_default( 'effect' ) ) ); ?>><?php echo $data['name']; ?></option>
                                        <?php
                                    }

                                    // Easing Effects
                                    foreach ( (array) $this->get_easing_transition_effects() as $i => $data ) {
                                        ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'effect', $this->get_config_default( 'effect' ) ) ); ?>><?php echo $data['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e( 'Type of transition between images in the lightbox view.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-gallery-sort">
                            <th scope="row">
                                <label for="envira-config-gallery-sort"><?php _e( 'Lightbox Sort', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <select id="envira-config-gallery-sort" name="_eg_album_data[config][gallery_sort]">
                                    <?php
                                    // Standard Effects
                                    foreach ( (array) $this->get_gallery_lightbox_sort_effects() as $i => $data ) {
                                        ?>
                                        <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'gallery_sort', $this->get_config_default( 'gallery_sort' ) ) ); ?>><?php echo $data['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e( 'Determine the sort order of the lightbox gallery images in this album.', 'envira-albums' ); ?></p>
                            </td>
                        </tr>
                        <tr id="envira-config-lightbox-html5-box">
                            <th scope="row">
                                <label for="envira-config-lightbox-html5"><?php _e( 'HTML5 Output?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-lightbox-html5" type="checkbox" name="_eg_album_data[config][html5]" value="<?php echo $this->get_config( 'html5', $this->get_config_default( 'html5' ) ); ?>" <?php checked( $this->get_config( 'html5', $this->get_config_default( 'html5' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'If enabled, uses data-envirabox-gallery instead of rel attributes for W3C HTML5 validation.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <tr id="envira-config-supersize-box">
                            <th scope="row">
                                <label for="envira-config-supersize"><?php _e( 'Enable Lightbox Supersize?', 'envira-albums' ); ?></label>
                            </th>
                            <td>
                                <input id="envira-config-supersize" type="checkbox" name="_eg_album_data[config][supersize]" value="<?php echo $this->get_config( 'supersize', $this->get_config_default( 'supersize' ) ); ?>" <?php checked( $this->get_config( 'supersize', $this->get_config_default( 'supersize' ) ), 1 ); ?> />
                                <span class="description"><?php _e( 'Enables or disables supersize mode for gallery lightbox images.', 'envira-albums' ); ?></span>
                            </td>
                        </tr>
                        <?php do_action( 'envira_albums_lightbox_box', $post ); ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Thumbnails -->
        <div id="envira-thumbnails-settings">
            <p class="envira-intro">
                <?php _e( 'Lightbox Thumbnail Settings', 'envira-albums' ); ?>
                <small>
                    <?php _e( 'The settings below adjust the thumbnail views for the lightbox display.', 'envira-albums' ); ?>
                </small>
            </p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-thumbnails-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails"><?php _e( 'Enable Gallery Thumbnails?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-thumbnails" type="checkbox" name="_eg_album_data[config][thumbnails]" value="1" <?php checked( $this->get_config( 'thumbnails', $this->get_config_default( 'thumbnails' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox thumbnails.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-thumbnails-width-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails-width"><?php _e( 'Gallery Thumbnails Width', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-thumbnails-width" type="number" name="_eg_album_data[config][thumbnails_width]" value="<?php echo $this->get_config( 'thumbnails_width', $this->get_config_default( 'thumbnails_width' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'Sets the width of each lightbox thumbnail.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-thumbnails-height-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails-height"><?php _e( 'Gallery Thumbnails Height', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-thumbnails-height" type="number" name="_eg_album_data[config][thumbnails_height]" value="<?php echo $this->get_config( 'thumbnails_height', $this->get_config_default( 'thumbnails_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'Sets the height of each lightbox thumbnail.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-thumbnails-position-box">
                        <th scope="row">
                            <label for="envira-config-thumbnails-position"><?php _e( 'Gallery Thumbnails Position', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <select id="envira-config-thumbnails-position" name="_eg_album_data[config][thumbnails_position]">
                                <?php foreach ( (array) $this->get_thumbnail_positions() as $i => $data ) : ?>
                                    <option value="<?php echo $data['value']; ?>"<?php selected( $data['value'], $this->get_config( 'thumbnails_position', $this->get_config_default( 'thumbnails_position' ) ) ); ?>><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Sets the position of the lightbox thumbnails.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <?php do_action( 'envira_albums_thumbnails_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

     /**
     * Callback for displaying the UI for setting mobile options.
     *
     * @since 1.2
     *
     * @param object $post The current post object.
     */
    public function mobile_tab( $post ) {

        ?>
        <div id="envira-mobile">
            <p class="envira-intro">
                <?php _e( 'Mobile Gallery Settings', 'envira-albums' ); ?>
                <small>
                    <?php _e( 'The settings below adjust configuration options for the Gallery when viewed on a mobile device..', 'envira-albums' ); ?>
                </small>
            </p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-mobile-box">
                        <th scope="row">
                            <label for="envira-config-mobile"><?php _e( 'Create Mobile Album Images?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile" type="checkbox" name="_eg_album_data[config][mobile]" value="<?php echo $this->get_config( 'mobile', $this->get_config_default( 'mobile' ) ); ?>" <?php checked( $this->get_config( 'mobile', $this->get_config_default( 'mobile' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables creating specific images for mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-size-box">
                        <th scope="row">
                            <label for="envira-config-mobile-width"><?php _e( 'Mobile Dimensions', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-width" type="number" name="_eg_album_data[config][mobile_width]" value="<?php echo $this->get_config( 'mobile_width', $this->get_config_default( 'mobile_width' ) ); ?>" /> &#215; <input id="envira-config-mobile-height" type="number" name="_eg_album_data[config][mobile_height]" value="<?php echo $this->get_config( 'mobile_height', $this->get_config_default( 'mobile_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-albums' ); ?></span>
                            <p class="description"><?php _e( 'These will be the sizes used for images displayed on mobile devices.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>

                    <?php do_action( 'envira_albums_mobile_box', $post ); ?>
                </tbody>
            </table>

            <!-- Lightbox -->
            <p class="envira-intro">
                <?php _e( 'Mobile Lightbox Settings', 'envira-albums' ); ?>
                <small>
                    <?php _e( 'The settings below adjust configuration options for the Lightbox when viewed on a mobile device.', 'envira-albums' ); ?>
                </small>
            </p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-mobile-lightbox-box">
                        <th scope="row">
                            <label for="envira-config-mobile-lightbox"><?php _e( 'Enable Lightbox?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-lightbox" type="checkbox" name="_eg_album_data[config][mobile_lightbox]" value="<?php echo $this->get_config( 'mobile_lightbox', $this->get_config_default( 'mobile_lightbox' ) ); ?>" <?php checked( $this->get_config( 'mobile_lightbox', $this->get_config_default( 'mobile_lightbox' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox on mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-touchwipe-box">
                        <th scope="row">
                            <label for="envira-config-mobile-touchwipe"><?php _e( 'Enable Gallery Touchwipe?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-touchwipe" type="checkbox" name="_eg_album_data[config][mobile_touchwipe]" value="<?php echo $this->get_config( 'mobile_touchwipe', $this->get_config_default( 'mobile_touchwipe' ) ); ?>" <?php checked( $this->get_config( 'mobile_touchwipe', $this->get_config_default( 'mobile_touchwipe' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables touchwipe support for the gallery lightbox on mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-touchwipe-close-box">
                        <th scope="row">
                            <label for="envira-config-mobile-touchwipe-close"><?php _e( 'Close Lightbox on Swipe Up?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-touchwipe-close" type="checkbox" name="_eg_album_data[config][mobile_touchwipe_close]" value="<?php echo $this->get_config( 'mobile_touchwipe_close', $this->get_config_default( 'mobile_touchwipe_close' ) ); ?>" <?php checked( $this->get_config( 'mobile_touchwipe_close', $this->get_config_default( 'mobile_touchwipe_close' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables closing the Lightbox when the user swipes up on mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-arrows-box">
                        <th scope="row">
                            <label for="envira-config-mobile-arrows"><?php _e( 'Enable Gallery Arrows?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-arrows" type="checkbox" name="_eg_album_data[config][mobile_arrows]" value="<?php echo $this->get_config( 'mobile_arrows', $this->get_config_default( 'mobile_arrows' ) ); ?>" <?php checked( $this->get_config( 'mobile_arrows', $this->get_config_default( 'mobile_arrows' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox navigation arrows on mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-toolbar-box">
                        <th scope="row">
                            <label for="envira-config-mobile-toolbar"><?php _e( 'Enable Gallery Toolbar?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-toolbar" type="checkbox" name="_eg_album_data[config][mobile_toolbar]" value="<?php echo $this->get_config( 'mobile_toolbar', $this->get_config_default( 'mobile_toolbar' ) ); ?>" <?php checked( $this->get_config( 'mobile_toolbar', $this->get_config_default( 'mobile_toolbar' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox toolbar on mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-thumbnails-box">
                        <th scope="row">
                            <label for="envira-config-mobile-thumbnails"><?php _e( 'Enable Gallery Thumbnails?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-thumbnails" type="checkbox" name="_eg_album_data[config][mobile_thumbnails]" value="<?php echo $this->get_config( 'mobile_thumbnails', $this->get_config_default( 'mobile_toolbar' ) ); ?>" <?php checked( $this->get_config( 'mobile_thumbnails', $this->get_config_default( 'mobile_thumbnails' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables the gallery lightbox thumbnails on mobile devices.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-thumbnails-width-box">
                        <th scope="row">
                            <label for="envira-config-mobile-thumbnails-width"><?php _e( 'Gallery Thumbnails Width', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-thumbnails-width" type="number" name="_eg_album_data[config][mobile_thumbnails_width]" value="<?php echo $this->get_config( 'mobile_thumbnails_width', $this->get_config_default( 'mobile_thumbnails_width' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'Sets the width of each lightbox thumbnail when on mobile devices.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-mobile-thumbnails-height-box">
                        <th scope="row">
                            <label for="envira-config-mobile-thumbnails-height"><?php _e( 'Gallery Thumbnails Height', 'envira-gallery' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-mobile-thumbnails-height" type="number" name="_eg_album_data[config][mobile_thumbnails_height]" value="<?php echo $this->get_config( 'mobile_thumbnails_height', $this->get_config_default( 'mobile_thumbnails_height' ) ); ?>" /> <span class="envira-unit"><?php _e( 'px', 'envira-gallery' ); ?></span>
                            <p class="description"><?php _e( 'Sets the height of each lightbox thumbnail when on mobile devices.', 'envira-gallery' ); ?></p>
                        </td>
                    </tr>
                    <?php do_action( 'envira_albums_mobile_lightbox_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the UI for setting album miscellaneous options.
     *
     * @since 1.0.0
     *
     * @param object $post The current post object.
     */
    public function misc_tab( $post ) {

        ?>
        <div id="envira-misc">
            <p class="envira-intro">
                <?php _e( 'Miscellaneous Settings', 'envira-albums' ); ?>
                <small>
                    <?php _e( 'The settings below adjust the miscellaneous options for the album.', 'envira-albums' ); ?>
                </small>
            </p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-title-box">
                        <th scope="row">
                            <label for="envira-config-title"><?php _e( 'Album Title', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-title" type="text" name="_eg_album_data[config][title]" value="<?php echo $this->get_config( 'title', $this->get_config_default( 'title' ) ); ?>" />
                            <p class="description"><?php _e( 'Internal album title for identification in the admin.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-slug-box">
                        <th scope="row">
                            <label for="envira-config-slug"><?php _e( 'Album Slug', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-slug" type="text" name="_eg_album_data[config][slug]" value="<?php echo $this->get_config( 'slug', $this->get_config_default( 'slug' ) ); ?>" />
                            <p class="description"><?php _e( '<strong>Unique</strong> internal album slug for identification and advanced album queries.', 'envira-albums' ); ?></p>
                        </td>
                    </tr>
                    <tr id="envira-config-import-export-box">
                        <th scope="row">
                            <label for="envira-config-import-gallery"><?php _e( 'Import/Export Album', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <form></form>
                            <?php $import_url = 'auto-draft' == $post->post_status ? add_query_arg( array( 'post' => $post->ID, 'action' => 'edit', 'envira-album-imported' => true ), admin_url( 'post.php' ) ) : add_query_arg( 'envira-album-imported', true ); ?>
                            <form action="<?php echo $import_url; ?>" id="envira-config-import-album-form" class="envira-albums-import-form" method="post" enctype="multipart/form-data">
                                <input id="envira-config-import-album" type="file" name="envira_import_album" />
                                <input type="hidden" name="envira_albums_import" value="1" />
                                <input type="hidden" name="envira_post_id" value="<?php echo $post->ID; ?>" />
                                <?php wp_nonce_field( 'envira-albums-import', 'envira-albums-import' ); ?>
                                <?php submit_button( __( 'Import Album', 'envira-albums' ), 'secondary', 'envira-albums-import-submit', false ); ?>
                                <span class="spinner envira-gallery-spinner"></span>
                            </form>

                            <hr />

                            <form id="envira-config-export-album-form" method="post">
                                <input type="hidden" name="envira_export" value="1" />
                                <input type="hidden" name="envira_post_id" value="<?php echo $post->ID; ?>" />
                                <?php wp_nonce_field( 'envira-albums-export', 'envira-albums-export' ); ?>
                                <?php submit_button( __( 'Export Album', 'envira-albums' ), 'secondary', 'envira-albums-export-submit', false ); ?>
                            </form>
                        </td>
                    </tr>
                    <tr id="envira-config-rtl-box">
                        <th scope="row">
                            <label for="envira-config-rtl"><?php _e( 'Enable RTL Support?', 'envira-albums' ); ?></label>
                        </th>
                        <td>
                            <input id="envira-config-rtl" type="checkbox" name="_eg_album_data[config][rtl]" value="<?php echo $this->get_config( 'rtl', $this->get_config_default( 'rtl' ) ); ?>" <?php checked( $this->get_config( 'rtl', $this->get_config_default( 'rtl' ) ), 1 ); ?> />
                            <span class="description"><?php _e( 'Enables or disables RTL support in Envira for right-to-left languages.', 'envira-albums' ); ?></span>
                        </td>
                    </tr>
                    <?php do_action( 'envira_albums_misc_box', $post ); ?>
                </tbody>
            </table>
        </div>
        <?php

    }

    /**
     * Callback for displaying the settings UI for the Standalone tab.
     *
     * @since 1.3.2
     *
     * @param object $post The current post object.
     */
    public function standalone_tab( $post ) {
        // Get post type so we load the correct metabox instance and define the input field names
        // Input field names vary depending on whether we are editing a Gallery or Album
        $post_type = get_post_type( $post );
        switch ( $post_type ) {
            /**
            * Album
            */
            case 'envira_album':
                $instance = Envira_Albums_Metaboxes::get_instance();
                $key = '_eg_album_data[config]';
                break;

        }

        // Gallery options only apply to Galleries, not Albums
        if ( 'envira_album' == $post_type ) {

            /* Get list of templates */

            $templates = get_page_templates();

            ?>
            <p class="envira-intro">
                <?php _e( 'The settings below adjust the Standalone settings.', 'envira-standalone' ); ?>
                <small>
                    <?php _e( 'Need some help?', 'envira-standalone' ); ?>
                    <a href="http://enviragallery.com/docs/standalone/" class="envira-doc" target="_blank">
                        <?php _e( 'Read the Documentation', 'envira-standalone' ); ?>
                    </a>
                    or
                    <a href="https://www.youtube.com/embed/dJ2t7uplFkw?autoplay=1&rel=0" class="envira-video" target="_blank">
                        <?php _e( 'Watch a Video', 'envira-standalone' ); ?>
                    </a>
                </small>
            </p>
            <table class="form-table">
                <tbody>
                    <tr id="envira-config-standalone-box">
                            <th scope="row">
                                <label for="envira-config-standalone-template"><?php _e( 'Template', 'envira-standalone' ); ?></label>
                            </th>
                            <td>
                                <?php

                                    if ( !empty($templates) ) :



                                ?>
                                <select id="envira-config-standalone-template" name="<?php echo $key; ?>[standalone_template]">
                                    <option value="">(Default)</option>
                                    <?php foreach ( (array) $templates as $name => $filename ) : ?>

                                    <option value="<?php echo $filename; ?>"<?php selected( $filename, $instance->get_config( 'standalone_template', $instance->get_config_default( 'standalone_template' ) ) ); ?>><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'By default we use single.php, which is the default template of the single blog post in your theme.', 'envira-zoom' ); ?></p>

                                <?php else: ?>

                                <p class="description"><?php _e( 'Your current theme does not have any custom templates. If you want to use a template besides the default, you need to add a custom template to your theme.', 'envira-zoom' ); ?></p>

                                <?php endif; ?>

                            </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }
    }

    /**
     * Helper method for retrieving config values.
     *
     * @since 1.0.0
     *
     * @global int $id        The current post ID.
     * @global object $post   The current post object.
     * @param string $key     The config key to retrieve.
     * @param string $default A default value to use.
     * @return string         Key value on success, empty string on failure.
     */
    public function get_config( $key, $default = false ) {

        global $id, $post;

        // Get the current post ID. If ajax, grab it from the $_POST variable.
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            $post_id = absint( $_POST['post_id'] );
        } else {
            $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        }

        $settings = get_post_meta( $post_id, '_eg_album_data', true );
        if ( isset( $settings['config'][$key] ) ) {
            return $settings['config'][$key];
        } else {
            return $default ? $default : '';
        }

    }

     /**
     * Helper method for setting default config values.
     *
     * @since 1.0.0
     *
     * @param string $key The default config key to retrieve.
     * @return string Key value on success, false on failure.
     */
    public function get_config_default( $key ) {

        $instance = Envira_Albums_Common::get_instance();
        return $instance->get_config_default( $key );

    }

    /**
     * Helper method for retrieving columns.
     *
     * @since 1.0.0
     *
     * @return array Array of column data.
     */
    public function get_columns() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_columns();

    }

    /**
     * Helper method for retrieving justified gallery themes.
     *
     * @since 1.1.1
     *
     * @return array Array of gallery theme data.
     */
    public function get_justified_gallery_themes() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_justified_gallery_themes();

    }

    /**
     * Helper method for retrieving description options.
     *
     * @since 1.0.0
     *
     * @return array Array of description options.
     */
    public function get_display_description_options() {

        return array(
            array(
                'name'  => __( 'Do not display', 'envira-albums' ),
                'value' => 0,
            ),
            array(
                'name'  => __( 'Display above gallery', 'envira-albums' ),
                'value' => 'above',
            ),
            array(
                'name'  => __( 'Display below gallery', 'envira-albums' ),
                'value' => 'below',
            ),
        );

    }

    /**
     * Helper method for retrieving album alignment options.
     *
     * @since 1.0.0
     *
     * @return array Array of description options.
     */
    public function get_album_alignment_options() {

        return array(
            array(
                'name'  => __( 'None', 'envira-albums' ),
                'value' => 0,
            ),
            array(
                'name'  => __( 'Left', 'envira-albums' ),
                'value' => 'left',
            ),
            array(
                'name'  => __( 'Right', 'envira-albums' ),
                'value' => 'right',
            ),
            array(
                'name'  => __( 'Center', 'envira-albums' ),
                'value' => 'center',
            ),
        );

    }

    /**
     * Helper method for retrieving lightbox themes.
     *
     * @since 1.1.1
     *
     * @return array Array of lightbox theme data.
     */
    public function get_lightbox_themes() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_lightbox_themes();

    }

    /**
     * Helper method for retrieving title placement options.
     *
     * @since 1.2.4.4
     *
     * @return array Array of sorting options.
     */
    public function get_title_placement_options() {

        $instance = Envira_Albums_Common::get_instance();
        return $instance->get_title_placement_options();

    }

    /**
     * Helper method for retrieving sorting options.
     *
     * @since 1.2.4.4
     *
     * @return array Array of sorting options.
     */
    public function get_sorting_options() {

        $instance = Envira_Albums_Common::get_instance();
        return $instance->get_sorting_options();

    }

    /**
     * Helper method for retrieving sorting directions.
     *
     * @since 1.2.4.4
     *
     * @return array Array of sorting directions.
     */
    public function get_sorting_directions() {

        $instance = Envira_Albums_Common::get_instance();
        return $instance->get_sorting_directions();

    }

    /**
     * Helper method for retrieving title displays.
     *
     * @since 1.0.0
     *
     * @return array Array of title display data.
     */
    public function get_title_displays() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_title_displays();

    }

    /**
     * Helper method for retrieving arrow positions.
     *
     * @since 1.1.1
     *
     * @return array Array of title display data.
     */
    public function get_arrows_positions() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_arrows_positions();

    }

    /**
     * Helper method for retrieving toolbar positions.
     *
     * @since 1.0.0
     *
     * @return array Array of toolbar position data.
     */
    public function get_toolbar_positions() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_toolbar_positions();

    }

    /**
     * Helper method for retrieving lightbox transition effects.
     *
     * @since 1.0.0
     *
     * @return array Array of transition effect data.
     */
    public function get_transition_effects() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_transition_effects();

    }

    /**
     * Helper method for retrieving lightbox transition effects.
     *
     * @since 1.0.0
     *
     * @return array Array of transition effect data.
     */
    public function get_gallery_lightbox_sort_effects() {

        $instance = Envira_Albums_Common::get_instance();
        return $instance->get_gallery_lightbox_sort_effects();

    }

    /**
     * Helper method for retrieving lightbox easing transition effects.
     *
     * @since 1.3.0.6
     *
     * @return array Array of transition effect data.
     */
    public function get_easing_transition_effects() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_easing_transition_effects();

    }

    /**
     * Helper method for retrieving thumbnail positions.
     *
     * @since 1.0.0
     *
     * @return array Array of thumbnail position data.
     */
    public function get_thumbnail_positions() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_thumbnail_positions();

    }

    /**
     * Helper method for retrieving album themes.
     *
     * @since 1.0.0
     *
     * @return array Array of gallery theme data.
     */
    public function get_gallery_themes() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_gallery_themes();

    }

    /**
     * Helper method for retrieving thumbnail positions.
     *
     * @since 1.0.0
     *
     * @return array Array of thumbnail position data.
     */
    public function get_justified_last_row() {

        $instance = Envira_Gallery_Common::get_instance();
        return $instance->get_justified_last_row();

    }

    /**
     * Callback for saving values from Envira metaboxes.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param object $post The current post object.
     */
    public function save_meta_boxes( $post_id, $post ) {

        // Bail out if we fail a security check.
        if ( ! isset( $_POST['envira-albums'] ) || ! wp_verify_nonce( $_POST['envira-albums'], 'envira-albums' ) ) {
            return;
        }

        // Bail out if running an autosave, ajax, cron or revision.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Bail out if the user doesn't have the correct permissions to update the slider.
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

		$common = Envira_Albums_Common::get_instance();

        // Get the existing settings
        $settings = get_post_meta( $post_id, '_eg_album_data', true );

        if ( !is_array( $settings ) || $settings === false ) {
            // there are no settings, so start with an array
            // also, this "resets" any "bad" data in this metadata
            $settings = array();
        }

        // If the ID of the album is not set or is lost, replace it now.
        if ( empty( $settings['id'] ) || ! $settings['id'] ) {
            $settings['id'] = $post_id;
        }

        // Build $settings array, comprising of
        // - galleryIDs - an array of gallery IDs to include in this album
        // - config - general configuration for this album

        // Convert gallery IDs to array
        if ( empty( $_POST['galleryIDs'] ) ) {
            unset( $settings['galleryIDs'] );
            unset( $settings['galleries'] );
        } else {
            $settings['galleryIDs']                 = explode( ',', $_POST['galleryIDs'] );
            $settings['galleryIDs']                 = array_filter( $settings['galleryIDs'] );
        }

        // Never have width equal zero, even if the user removes the value
        $album_width = absint( $_POST['_eg_album_data']['config']['album_width'] );
        if ( !$album_width ) { $album_width = 100;}

        // Store album config
        $settings['config'] = array();
        $settings['config']['type']                = isset( $_POST['_eg_album_data']['config']['type'] ) ? $_POST['_eg_album_data']['config']['type'] : $this->get_config_default( 'type' );
        $settings['config']['columns']              = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['columns'] );

        // Automatic/Justified
        $settings['config']['justified_gallery_theme']  = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['justified_gallery_theme'] );
        $settings['config']['justified_row_height']     = isset( $_POST['_eg_album_data']['config']['justified_row_height'] ) ? absint($_POST['_eg_album_data']['config']['justified_row_height'] ) : 150;
        $settings['config']['justified_margins']        = absint( $_POST['_eg_album_data']['config']['justified_margins'] );
        $settings['config']['justified_last_row']       = sanitize_text_field( esc_attr( $_POST['_eg_album_data']['config']['justified_last_row'] ) );

        $settings['config']['back']                 = ( isset( $_POST['_eg_album_data']['config']['back'] ) ? 1 : 0 );
        $settings['config']['back_label']           = sanitize_text_field( esc_attr ( $_POST['_eg_album_data']['config']['back_label'] ) );
        $settings['config']['description_position'] = sanitize_text_field( esc_attr ( $_POST['_eg_album_data']['config']['description_position'] ) );
        $settings['config']['album_alignment']      = sanitize_text_field( esc_attr( $_POST['_eg_album_data']['config']['album_alignment'] ) );
        $settings['config']['gallery_theme']        = sanitize_text_field( esc_attr( $_POST['_eg_album_data']['config']['gallery_theme'] ) );
        $settings['config']['album_width']          = ( $album_width ? $album_width : 100 );
        $settings['config']['description']          = trim( $_POST['_eg_album_data']['config']['description'] );
        $settings['config']['display_titles']       = sanitize_text_field( esc_attr(  $_POST['_eg_album_data']['config']['display_titles'] ) );
        $settings['config']['display_titles_automatic'] = ( isset( $_POST['_eg_album_data']['config']['display_titles_automatic'] ) ? 1 : 0 );
        
        $settings['config']['display_captions']     = ( isset( $_POST['_eg_album_data']['config']['display_captions'] ) ? 1 : 0 );
        $settings['config']['display_image_count']  = ( isset( $_POST['_eg_album_data']['config']['display_image_count'] ) ? 1 : 0 );
        $settings['config']['gutter']               = absint( $_POST['_eg_album_data']['config']['gutter'] );
        $settings['config']['margin']               = absint( $_POST['_eg_album_data']['config']['margin'] );
        $settings['config']['sorting']              = sanitize_text_field( esc_attr( $_POST['_eg_album_data']['config']['sorting'] ) );
        $settings['config']['sorting_direction']    = sanitize_text_field( esc_attr( $_POST['_eg_album_data']['config']['sorting_direction'] ) );
        $settings['config']['crop']                = isset( $_POST['_eg_album_data']['config']['crop'] ) ? 1 : 0;
        $settings['config']['dimensions']          = isset( $_POST['_eg_album_data']['config']['dimensions'] ) ? 1 : 0;
        $settings['config']['crop_width']          = absint( $_POST['_eg_album_data']['config']['crop_width'] );
        $settings['config']['crop_height']         = absint( $_POST['_eg_album_data']['config']['crop_height'] );
        $settings['config']['isotope']             = isset( $_POST['_eg_album_data']['config']['isotope'] ) ? 1 : 0;
        $settings['config']['css_animations']      = isset( $_POST['_eg_album_data']['config']['css_animations'] ) ? 1 : 0;

        // Lightbox
        $settings['config']['lightbox']             = isset( $_POST['_eg_album_data']['config']['lightbox'] ) ? 1 : 0;
        $settings['config']['lightbox_theme']       = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['lightbox_theme'] );
        $settings['config']['title_display']        = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['title_display'] );
        $settings['config']['arrows']               = isset( $_POST['_eg_album_data']['config']['arrows'] ) ? 1 : 0;
        $settings['config']['arrows_position']      = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['arrows_position'] );
        $settings['config']['keyboard']             = isset( $_POST['_eg_album_data']['config']['keyboard'] ) ? 1 : 0;
        $settings['config']['mousewheel']           = isset( $_POST['_eg_album_data']['config']['mousewheel'] ) ? 1 : 0;
        $settings['config']['toolbar']              = isset( $_POST['_eg_album_data']['config']['toolbar'] ) ? 1 : 0;
        $settings['config']['toolbar_title']        = isset( $_POST['_eg_album_data']['config']['toolbar_title'] ) ? 1 : 0;
        $settings['config']['toolbar_position']     = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['toolbar_position'] );
        $settings['config']['aspect']               = isset( $_POST['_eg_album_data']['config']['aspect'] ) ? 1 : 0;
        $settings['config']['loop']                 = isset( $_POST['_eg_album_data']['config']['loop'] ) ? 1 : 0;
        $settings['config']['lightbox_open_close_effect'] = preg_replace( '#[^A-Za-z0-9-_]#', '', $_POST['_eg_album_data']['config']['lightbox_open_close_effect'] );
        $settings['config']['effect']               = preg_replace( '#[^A-Za-z0-9-_]#', '', $_POST['_eg_album_data']['config']['effect'] );
        $settings['config']['html5']                = isset( $_POST['_eg_album_data']['config']['html5'] ) ? 1 : 0;
        $settings['config']['gallery_sort']         = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['gallery_sort'] );
        $settings['config']['supersize']            = isset( $_POST['_eg_album_data']['config']['supersize'] ) ? 1 : 0;

        // Lightbox Thumbnails
        $settings['config']['thumbnails']           = isset( $_POST['_eg_album_data']['config']['thumbnails'] ) ? 1 : 0;
        $settings['config']['thumbnails_width']     = absint( $_POST['_eg_album_data']['config']['thumbnails_width'] );
        $settings['config']['thumbnails_height']    = absint( $_POST['_eg_album_data']['config']['thumbnails_height'] );
        $settings['config']['thumbnails_position']  = preg_replace( '#[^a-z0-9-_]#', '', $_POST['_eg_album_data']['config']['thumbnails_position'] );

        // Mobile
        $settings['config']['mobile']              = isset( $_POST['_eg_album_data']['config']['mobile'] ) ? 1 : 0;
        $settings['config']['mobile_width']        = absint( $_POST['_eg_album_data']['config']['mobile_width'] );
        $settings['config']['mobile_height']       = absint( $_POST['_eg_album_data']['config']['mobile_height'] );
        $settings['config']['mobile_lightbox']     = isset( $_POST['_eg_album_data']['config']['mobile_lightbox'] ) ? 1 : 0;
        $settings['config']['mobile_touchwipe']    = isset( $_POST['_eg_album_data']['config']['mobile_touchwipe'] ) ? 1 : 0;
        $settings['config']['mobile_touchwipe_close'] = isset( $_POST['_eg_album_data']['config']['mobile_touchwipe_close'] ) ? 1 : 0;
        $settings['config']['mobile_arrows']       = isset( $_POST['_eg_album_data']['config']['mobile_arrows'] ) ? 1 : 0;
        $settings['config']['mobile_toolbar']      = isset( $_POST['_eg_album_data']['config']['mobile_toolbar'] ) ? 1 : 0;
        $settings['config']['mobile_thumbnails']   = isset( $_POST['_eg_album_data']['config']['mobile_thumbnails'] ) ? 1 : 0;
        $settings['config']['mobile_thumbnails_width']   =  isset( $_POST['_eg_album_data']['config']['mobile_thumbnails_width'] ) ? absint( $_POST['_eg_album_data']['config']['mobile_thumbnails_width'] ) :  $common->get_config_default('mobile_thumbnails_width');
        $settings['config']['mobile_thumbnails_height']   = isset( $_POST['_eg_album_data']['config']['mobile_thumbnails_height'] ) ? absint( $_POST['_eg_album_data']['config']['mobile_thumbnails_height'] ) : $common->get_config_default('mobile_thumbnails_height');

        // Standalone
        if ( Envira_Gallery_Settings::get_instance()->get_setting( 'standalone_enabled' ) ) {
            $settings['config']['standalone_template'] = ( isset( $_POST['_eg_album_data']['config']['standalone_template'] ) ? str_replace( '-php', '.php', sanitize_text_field( $_POST['_eg_album_data']['config']['standalone_template'] ) ) : '' );
        }

        // Misc
        $settings['config']['title']                = trim( strip_tags( esc_attr( $_POST['_eg_album_data']['config']['title'] ) ) );
        $settings['config']['slug']                 = sanitize_text_field( esc_attr ( $_POST['_eg_album_data']['config']['slug'] ) );
        $settings['config']['classes']              = ( isset ($_POST['_eg_album_data']['config']['classes'] ) ? explode( "\n", $_POST['_eg_album_data']['config']['classes'] ) : '' );
        $settings['config']['rtl']                  = ( isset( $_POST['_eg_album_data']['config']['rtl'] ) ? 1 : 0 );

        // If on an envira post type, map the title and slug of the post object to the custom fields if no value exists yet.
        if ( isset( $post->post_type ) && 'envira_album' == $post->post_type ) {
            if ( empty( $settings['config']['title'] ) ) {
                $settings['config']['title'] = trim( strip_tags( $post->post_title ) );
            }
            if ( empty( $settings['config']['slug'] ) ) {
                $settings['config']['slug']  = sanitize_text_field( $post->post_name );
            }
        }

        // We need to add metadata if the config slug doesn't match
        if ( $settings['config']['slug'] != $post->post_name ) {
            update_post_meta( $post_id, 'envira_album_slug', $settings['config']['slug'] );
        } else {
            // this metadata SHOULD no longer be needed, so let's delete it if it exists
            delete_post_meta( $post_id, 'envira_album_slug' );
        }

        // Provide a filter to override settings.
        $settings = apply_filters( 'envira_albums_save_settings', $settings, $post_id, $post );

        // Update the post meta.
        update_post_meta( $post_id, '_eg_album_data', $settings );

        // Fire a hook for addons that need to utilize the cropping feature.
        do_action( 'envira_albums_saved_settings', $settings, $post_id, $post );

        // Finally, flush all gallery caches to ensure everything is up to date.
        $this->flush_album_caches( $post_id, $settings['config']['slug'] );

    }

    /**
     * Helper method to flush gallery caches once a gallery is updated.
     *
     * @since 1.0.0
     *
     * @param int $post_id The current post ID.
     * @param string $slug The unique album slug.
     */
    public function flush_album_caches( $post_id, $slug ) {

        Envira_Albums_Common::get_instance()->flush_album_caches( $post_id, $slug );

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.0.0
     *
     * @return object The Envira_Albums_Metaboxes object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Albums_Metaboxes ) ) {
            self::$instance = new Envira_Albums_Metaboxes();
        }

        return self::$instance;

    }

}

// Load the metabox class.
$envira_albums_metaboxes = Envira_Albums_Metaboxes::get_instance();