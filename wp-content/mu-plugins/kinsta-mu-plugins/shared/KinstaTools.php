<?php
/**
 * Kinsta Tools
 * KinstaTools contains all the common functionality required for our UI
 *
 * @package KinstaMUPlugins
 * @subpackage KinstaTools
 * @since 1.0.0
 */

namespace Kinsta;

class KinstaTools {

    /**
     * Plugin constructor
     * Sets the hooks required for the plugin's functionality
     */
    function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'wp_ajax_kinsta_save_option', array( $this, 'save_option' ) );
        add_action( 'admin_head', array( $this, 'init_tooltipster') );
        //add_action( 'admin_menu', array( $this, 'admin_menu_item' ) );
        //add_action( 'admin_head', array( $this, 'menu_icon_style' ) );
    }


    function setOptionsArrayValue( &$array, $name, $value ) {
        if( substr( $name, -1 ) !== ']' ) {
            $array[$name] = $value;
        }
        else {
            $name = str_replace( '][', '|', $name );
            $name = substr( str_replace( '[', '|', $name ), 0, -1 );
            $name = explode( '|', $name );
        }

        $count = count( $name );

        if( $count == 2 ) {
            $array[$name[0]][$name[1]] = $value;
        }

        if( $count == 3 ) {
            $array[$name[0]][$name[1]][$name[2]] = $value;
        }

        if( $count == 4 ) {
            $array[$name[0]][$name[1]][$name[2]][$name[3]] = $value;
        }

    }


    function save_option() {
        if ( ! wp_verify_nonce( $_POST['nonce'], $_POST['name'] ) ) {
            die();
            return;
        }

        $options = get_option( $_POST['option_name'] );
        $this->setOptionsArrayValue( $options, $_POST['name'], $_POST['value'] );
        update_option( $_POST['option_name'], $options );
        die();
    }

    function assets( $page ) {
        if( substr_count( $page , 'kinsta' ) == 0 ) {
            return;
        }

        wp_enqueue_style( 'kinsta-shared', plugin_dir_url( __FILE__ ) . '/styles/common.css', array(), KINSTAMU_VERSION );
        wp_enqueue_script( 'kinsta-loader', plugin_dir_url( __FILE__ ) . '/scripts/kinsta-loader.js', array( 'jquery', 'jquery-effects-core' ), KINSTAMU_VERSION, true );
        wp_enqueue_script( 'kinsta-quicksave', plugin_dir_url( __FILE__ ) . '/scripts/kinsta-quicksave.js', array( 'jquery' ), KINSTAMU_VERSION, true );

        wp_enqueue_script( 'tooltipster', plugin_dir_url( __FILE__ ) . '/scripts/tooltipster.bundle.min.js', array( 'jquery' ), KINSTAMU_VERSION );
        wp_enqueue_style( 'tooltipster', plugin_dir_url( __FILE__ ) . '/styles/tooltipster.bundle.css', array(), KINSTAMU_VERSION );

    }

    function init_tooltipster() {
        $screen = get_current_screen();
        if( substr_count( $screen->id , 'kinsta' ) == 0 ) {
            return;
        }

        ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('.kinsta-tooltip').tooltipster({
                    theme: 'tooltipster-borderless',
                    interactive: true,
                    maxWidth: 360
                });
            });
        </script>
        <?php
    }


    /**
     * Load translations
     *
     * @since 1.0.0
     * @author Daniel Pataki
     *
     */
    function load_textdomain() {
        load_muplugin_textdomain('kinsta-tools', dirname( plugin_basename(__FILE__) ) . '/translations' );
    }

    /**
     * Add main Kinsta Tools menu item
     */
    function admin_menu_item() {
        add_menu_page(
            __( 'Kinsta Cache', 'kinsta-tools' ),
            __( 'Kinsta Cache', 'kinsta-tools' ),
            'manage_options',
            'kinsta-tools',
            array( $this, 'admin_menu_page' ),
            'none',
            '3.19992919'
        );
    }

     function admin_menu_page() {

     }


     function menu_icon_style() { ?>
         <style>
             #adminmenu .toplevel_page_kinsta-tools .wp-menu-image {
                 background-repeat:no-repeat;
                 background-position: 50% -28px;
                 background-image: url( '<?php echo plugin_dir_url( __FILE__ ) ?>images/menu-icon.svg' )
             }
             #adminmenu .toplevel_page_kinsta-tools:hover .wp-menu-image,  #adminmenu .toplevel_page_kinsta-tools.wp-has-current-submenu .wp-menu-image, #adminmenu .toplevel_page_kinsta-tools.current .wp-menu-image {
                 background-position: 50% 6px;
             }
         </style>
         <?php
     }

     public static function kinsta_switch( $option_name, $name, $value, $label, $quicksave = true, $info = false ) {
         $class = ( $quicksave == true ) ? 'kinsta-quicksave' : '';
         ?>
         <div class='kinsta-switch kinsta-control-container <?php echo $class ?>' data-option-name="<?php echo $option_name ?>">

             <label class='kinsta-control-ui'>
                 <input id='<?php echo $name ?>' class="kinsta-control" type='checkbox' name='<?php echo $name ?>' <?php checked( $value, true ) ?> >
                 <span class='kinsta-switch-label' data-on="Yes" data-off="No"></span>
                 <span class='kinsta-switch-handle'></span>
             </label>

             <span class='kinsta-label'><?php echo $label ?></span>
             <input type='hidden' name='kinsta-nonce' value='<?php echo wp_create_nonce( $name ) ?>'>

             <?php if( !empty( $info ) ) : ?>
                  <?php self::kinsta_tooltip( $info, $name ) ?>
            <?php endif ?>

         </div>
         <?php
     }


     public static function kinsta_number_field( $option_name, $name, $value, $label, $quicksave = true, $info = false ) {
         $class = ( $quicksave == true ) ? 'kinsta-quicksave' : '';
         ?>
         <div class='kinsta-number-field kinsta-control-container <?php echo $class ?>' data-option-name="<?php echo $option_name ?>">
             <label>
                 <input type='text' class='kinsta-control' name='<?php echo $name ?>' value='<?php echo $value ?>'>
                 <span class='kinsta-label'><?php echo $label ?></span>
             </label>
             <input type='hidden' name='kinsta-nonce' value='<?php echo wp_create_nonce( $name ) ?>'>

             <?php self::kinsta_tooltip( $info, $name ) ?>

         </div>
         <?php
     }

     public static function kinsta_tooltip( $content, $name ) {
         if( !empty( $content ) ) :
             $name = str_replace( array( '[', ']' ), '_', $name );
        ?>
             <span class="kinsta-tooltip" data-tooltip-content="#kinsta-tooltip-<?php echo $name ?>"><img src='<?php echo plugin_dir_url( __FILE__ ) ?>/images/info.svg'></span>

             <div class="kinsta-tooltip-content">
                 <span id="kinsta-tooltip-<?php echo $name ?>">
                     <?php echo $content ?>
                 </span>
             </div>
         <?php endif;
     }

}



new KinstaTools;
