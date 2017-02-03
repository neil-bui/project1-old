<?php
/**
 * Settings admin class.
 *
 * @since 1.3.0
 *
 * @package Envira_Albums
 * @author  Tim Carr
 */
class Envira_Albums_Settings_Admin {

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
     * Primary class constructor.
     *
     * @since 1.3.0
     */
    public function __construct() {

        // NextGEN Importer Addon Support
        add_filter( 'envira_nextgen_importer_settings_tab_nav', array( $this, 'nextgen_settings_register_tabs' ) );
        add_action( 'envira_nextgen_importer_tab_settings_albums', array( $this, 'nextgen_settings_tab' ) );

    }

    /**
     * Adds an Albums Tab to the NextGEN Importer Settings Screen
     *
     * @since 1.3.0
     *
     * @param   array   $tabs   Tabs
     * @return  array           Tabs
     */
    public function nextgen_settings_register_tabs( $tabs ) {

        $tabs['albums'] = __( 'Albums', 'envira-nextgen-importer' );
        return $tabs;

    }

    /**
     * Callback for displaying the UI for the Albums Settings tab in the NextGEN Importer.
     *
     * @since 1.3.0
     */
    public function nextgen_settings_tab() {

        // Check and see if NextGEN is installed... if not, do not attempt to display settings and instead report an error
        if ( !is_plugin_active( 'nextgen-gallery/nggallery.php' ) ) { ?>
            <div id="envira-nextgen-importer-settings-galleries">
                <p>Please install and activate the <a href="https://wordpress.org/plugins/nextgen-gallery/" target="_blank">NextGEN Gallery plugin</a> before using this addon.</p>
            </div>
        <?php return;
        }

        // Get NextGEN Albums
        $albums = Envira_Nextgen_Wrapper::get_instance()->get_albums();

        // Get settings (contains imported albums)
        $settings = get_option( 'envira_nextgen_importer' );
        ?>

        <!-- Progress Bar -->
        <div id="album-progress"><div id="album-progress-label"></div></div>

        <div id="envira-nextgen-importer-settings-albums">
            <form id="envira-nextgen-importer-albums" method="post">
                <table class="form-table">
                    <tbody>
                        <tr id="envira-settings-key-box">
                            <th scope="row">
                                <label for="envira-settings-key"><?php _e( 'Albums to Import', 'envira-nextgen-importer' ); ?></label>
                            </th>
                            <td>
                                <?php
                                if ( $albums !== false ) {
                                    foreach ( $albums as $album ) {
                                        // Check if album imported from NextGEN previously
                                        $imported = ( ( isset( $settings['albums'] ) && isset( $settings['albums'][ $album->id ] ) ) ? true : false );
                                        ?>
                                        <label for="albums-<?php echo $album->id; ?>" data-id="<?php echo $album->id; ?>"<?php echo ( $imported ? ' class="imported"' : '' ); ?>>
                                            <input type="checkbox" name="albums" id="albums-<?php echo $album->id; ?>" value="<?php echo $album->id; ?>" />
                                            <?php echo $album->name; ?>
                                            <span>
                                                <?php
                                                if ( $imported ) {
                                                    // Already imported
                                                    _e( 'Imported', 'envira-nextgen-importer' );
                                                }
                                                ?>
                                            </span>
                                        </label>
                                        <?php
                                    }
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                &nbsp;
                            </th>
                            <td>
                                <?php
                                submit_button( __( 'Import Albums', 'envira-nextgen-importer' ), 'primary', 'envira-gallery-verify-submit', false );
                                ?>
                            </td>
                        </tr>
                        <?php do_action( 'envira_nextgen_importer_settings_albums_box' ); ?>
                    </tbody>
                </table>
            </form>
        </div>
        <?php

    }

    /**
     * Returns the singleton instance of the class.
     *
     * @since 1.3.0
     *
     * @return object The Envira_Albums_Settings_Admin object.
     */
    public static function get_instance() {

        if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Envira_Albums_Settings_Admin ) ) {
            self::$instance = new Envira_Albums_Settings_Admin();
        }

        return self::$instance;

    }

}

// Load the settings admin class.
$envira_albums_settings_admin = Envira_Albums_Settings_Admin::get_instance();