<?php
/**
 * Swagtron functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * @package WordPress
 * @subpackage Swagtron
 * @since Swagtron 1.0
 */

if( ! function_exists( 'swagtron_theme_setup' ) ) {
  function swagtron_theme_setup(){

    /*
     * Make theme available for translation.
     * Translations can be filed in the /languages/ directory.
     * If you're building a theme based on Twenty Sixteen, use a find and replace
     * to change 'twentysixteen' to the name of your theme in all the template files
     */
    load_theme_textdomain( 'swagtron', get_template_directory() . '/languages' );

    /*
     * Let WordPress manage the document title.
     * By adding theme support, we declare that this theme does not use a
     * hard-coded <title> tag in the document head, and expect WordPress to
     * provide it for us.
     */
    add_theme_support( 'title-tag' );

    /*
     * Enable support for Post Thumbnails on posts and pages.
     *
     * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
     */
    add_theme_support( 'post-thumbnails' );
    set_post_thumbnail_size( 1200, 9999 );

    // This theme uses wp_nav_menu() in two locations.
    register_nav_menus( array(
      'footer1' => __( 'Footer Menu', 'swagtron' ),
			'footer2' => __( 'Footer Menu Bottom', 'swagtron' )
    ) );

    /*
     * Switch default core markup for search form, comment form, and comments
     * to output valid HTML5.
     */
    add_theme_support( 'html5', array(
      'search-form',
      'comment-form',
      'comment-list',
      'gallery',
      'caption',
    ) );

    /*
     * Enable support for Post Formats.
     *
     * See: https://codex.wordpress.org/Post_Formats
     */
    add_theme_support( 'post-formats', array(
      'aside',
      'image',
      'video',
      'quote',
      'link',
      'gallery',
      'status',
      'audio',
      'chat',
    ) );

		// Support WooCommerce
		add_theme_support( 'woocommerce' );
  }
}
add_action( 'after_setup_theme', 'swagtron_theme_setup' );


/**
 * Registers a widget area.
 *
 * @link https://developer.wordpress.org/reference/functions/register_sidebar/
 *
 * @since Goodcount Awards 1.0
 */
function swagtron_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Blog Sidebar', 'swagtron' ),
		'id'            => 'sidebar-blog',
		'description'   => __( 'Add widgets here to appear in your sidebar.', 'swagtron' ),
		'before_widget' => '',
		'after_widget'  => '',
		'before_title'  => '',
		'after_title'   => '',
	) );
}
add_action( 'widgets_init', 'swagtron_widgets_init' );


/**
 * Enqueues scripts and styles.
 *
 * @since Goodcount Awards 1.0
 */
function swagtron_theme_scripts() {

	// Theme CSS
	wp_enqueue_style( 'basic-style', get_stylesheet_uri() );
	//wp_enqueue_style( 'font-awesome-style', get_template_directory_uri(). '/css/font-awesome.min.css' );
	wp_enqueue_style( 'font-awesome-style', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css' );
	wp_enqueue_style( 'main-style', get_template_directory_uri(). '/css/style.css' );
	wp_enqueue_style( 'bootstrap-style', get_template_directory_uri(). '/css/bootstrap.css' );
	//wp_enqueue_style( 'owl.carousel-style', get_template_directory_uri(). '/css/owl.carousel.css' );
	wp_enqueue_style( 'owl.carousel-style', 'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.css' );
	wp_enqueue_style( 'easy-responsive-tabs-style', get_template_directory_uri(). '/css/easy-responsive-tabs.css' );

  // Theme Javascript
  //wp_enqueue_script( 'jq-lib', get_template_directory_uri() . '/js/jquery.min.js', array(), '1.11.1', true );
	wp_enqueue_script( 'jq-lib', 'https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js', array(), '1.11.1', true );
	//wp_enqueue_script( 'bootstrap.min-js', get_template_directory_uri() . '/js/bootstrap.min.js', array('jq-lib'), '3.3.7', true );
	wp_enqueue_script( 'bootstrap.min-js', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js', array('jq-lib'), '3.3.7', true );
	//wp_enqueue_script( 'owl.carousel-js', get_template_directory_uri() . '/js/owl.carousel.min.js', array('jq-lib'), '1.3.3', true );
	wp_enqueue_script( 'owl.carousel-js', 'https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.js', array('jq-lib'), '1.3.3', true );
	wp_enqueue_script( 'easyResponsiveTabs-js', get_template_directory_uri() . '/js/easyResponsiveTabs.min.js', array('jq-lib'), '1.3.3', true );

	// Customer Information page
	if( is_page_template( 'templates/customer-information.php' ) ) {
		wp_enqueue_script( 'form-validation-js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.min.js', array('jq-lib'), '3.3.6', true );
		wp_enqueue_script( 'validation-additional-methods', 'https://cdn.jsdelivr.net/jquery.validation/1.15.0/additional-methods.min.js', array('jq-lib'), '3.3.6', true );
		wp_enqueue_script( 'jquery-validate-tooltip-js', get_template_directory_uri() . '/js/jquery-validate-tooltip.js', array('jq-lib'), '1.3.3', true );
	}

}
add_action( 'wp_enqueue_scripts', 'swagtron_theme_scripts' );




// Specify some tweaks to current theme
include_once( get_stylesheet_directory() . '/inc/tweaks.php' );




// Include ajax functions
include_once( get_stylesheet_directory() . '/inc/ajax-functions.php' );




// Add ajax script in head
function add_ajax_url() {
  echo '<script type="text/javascript">var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';
}
add_action( 'wp_head', 'add_ajax_url' );




// Starting Session
if( session_status() === PHP_SESSION_NONE ) {
	session_start();
}



// Administritive Menu
function swagtron_backend_menu() {
	// Customer information
	add_menu_page( 'Customer Information', 'Customer Information', 'manage_options', 'cust-info', 'include_cust_info_page', 'dashicons-chart-bar' );
}
add_action( 'admin_menu', 'swagtron_backend_menu' );



function include_cust_info_page() {
	if ( ! current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	include_once get_stylesheet_directory() . '/admin/cust-info.php';
	wp_enqueue_style( 'datatable-css', 'https://cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css' );
	wp_enqueue_script( 'datatable-js', 'https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js', array('jquery'), '1.10.11', true );
	wp_enqueue_style( 'datatable-responsive-css', 'https://cdn.datatables.net/responsive/2.1.0/css/responsive.dataTables.min.css' );
	wp_enqueue_script( 'datatable-responsive-js', 'https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js', array('jquery', 'datatable-js'), '2.1.0', true );
	wp_enqueue_script( 'script-js', get_stylesheet_directory_uri() . '/admin/js/script.js', array( 'jquery', 'datatable-js'), '1.0', true );
}



/***********************************************************************
 *	Extending WooCommerce
 **********************************************************************/

// Remove product tabs from homepage only
function woo_remove_product_tab($tabs) {

	if( is_front_page() ) {
		foreach( $tabs as $key => $tab ) {
			unset( $tabs[$key] );
		}
	}

 	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'woo_remove_product_tab', 98);




// remove WooCommerce SKU from homepage only
add_action('woocommerce_before_single_product_summary', function() {
	if( is_front_page() ) {
    add_filter('wc_product_sku_enabled', '__return_false');
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_related_products', 15);
	}
});




// Change 'Add to Cart' to 'Buy Now' text
function woo_custom_cart_button_text() {
	return ( is_front_page() ) ? __( 'Buy Now', 'woocommerce' ) : __( 'Add to Cart', 'woocommerce' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'woo_custom_cart_button_text' );




// Remove related products on homepage only
function wc_remove_related_products( $args ) {
	return ( is_front_page() ) ? array() : $args;
}
add_filter( 'woocommerce_related_products_args','wc_remove_related_products', 10 );




// Product details page
function start_wrapper_here() {
	$classes = get_body_class();

	if( ! is_front_page() && ! in_array( 'page-template-product-landing', $classes ) ) {
		echo '<div class="singlepage_body"><div class="container">';
	}
}
add_action(  'woocommerce_before_single_product', 'start_wrapper_here', 20  );

function end_wrapper_here() {
	$classes = get_body_class();

	if( ! is_front_page() && ! in_array( 'page-template-product-landing', $classes ) ) {
		echo '</div></div>';
	}
}
add_action(  'woocommerce_after_single_product', 'end_wrapper_here', 20  );




// Loading the template conditionally
function wpse138858_woocommerce_category_archive_template( $original_template ) {
  if ( is_product_category() ) {
    return get_template_directory().'/woocommerce-category-archive.php';
  } else {
    return $original_template;
  }
}
add_filter( 'template_include', 'wpse138858_woocommerce_category_archive_template' );



// Move WooCommerce price
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 25 );




// Removing WooCommerce review under title on product details page
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );




// WooCommerce - Remove password strength function
function wc_ninja_remove_password_strength() {
	if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
		wp_dequeue_script( 'wc-password-strength-meter' );
	}
}
add_action( 'wp_print_scripts', 'wc_ninja_remove_password_strength', 100 );




 //Will effect both the woocommerce category page
function set_row_count_archive( $query ){
  if ( is_tax('product_cat') ) {
    $query->set('posts_per_page', -1);
  }

	return $query;
}
add_filter('pre_get_posts', 'set_row_count_archive');


// Display 99 products per page
//add_filter( 'loop_shop_per_page', create_function( '$cols', 'return 99;' ), 20 );




// Remove "Sale" icon from product single page
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );




// Disable Variable Product Price Range
function my_variation_price_format( $price, $product ) {
	// Main Price
	$prices = array( $product->get_variation_price( 'min', true ), $product->get_variation_price( 'max', true ) );
	$price = $prices[0] !== $prices[1] ? sprintf( __( '%1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );

	// Sale Price
	$prices = array( $product->get_variation_regular_price( 'min', true ), $product->get_variation_regular_price( 'max', true ) );
	sort( $prices );
	$saleprice = $prices[0] !== $prices[1] ? sprintf( __( '%1$s', 'woocommerce' ), wc_price( $prices[0] ) ) : wc_price( $prices[0] );

	if ( $price !== $saleprice ) {
		$price = '<del>MSRP: ' . $saleprice . '</del> USD: <ins>' . $price . '</ins>';
	}
	return $price;
}
add_filter( 'woocommerce_variable_sale_price_html', 'my_variation_price_format', 10, 2 );
add_filter( 'woocommerce_variable_price_html', 'my_variation_price_format', 10, 2 );




// Include customized add-to-cart-variation.js
function load_theme_scripts_new() {
	global $wp_scripts;
	$wp_scripts->registered[ 'wc-add-to-cart-variation' ]->src = get_template_directory_uri() . '/woocommerce/js/add-to-cart-variation.js';
}
add_action( 'wp_enqueue_scripts', 'load_theme_scripts_new' );




// LOAD PRETTY PHOTO for the whole site
function frontend_scripts_include_lightbox() {
  global $woocommerce;
  $suffix      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
  $lightbox_en = get_option( 'woocommerce_enable_lightbox' ) == 'yes' ? true : false;

  if ( $lightbox_en ) {
    wp_enqueue_script( 'prettyPhoto', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
    wp_enqueue_script( 'prettyPhoto-init', $woocommerce->plugin_url() . '/assets/js/prettyPhoto/jquery.prettyPhoto.init' . $suffix . '.js', array( 'jquery' ), $woocommerce->version, true );
    wp_enqueue_style( 'woocommerce_prettyPhoto_css', $woocommerce->plugin_url() . '/assets/css/prettyPhoto.css' );
  }
}
add_action( 'wp_enqueue_scripts', 'frontend_scripts_include_lightbox' );




/*********************************************************************************************/
/** WooCommerce - Modify each individual input type $args defaults /**
/*********************************************************************************************/

function wc_form_field_args( $args, $key, $value = null ) {

	/*********************************************************************************************/
	/** This is not meant to be here, but it serves as a reference
	/** of what is possible to be changed. /**

	$defaults = array(
			'type'              => 'text',
			'label'             => '',
			'description'       => '',
			'placeholder'       => '',
			'maxlength'         => false,
			'required'          => false,
			'id'                => $key,
			'class'             => array(),
			'label_class'       => array(),
			'input_class'       => array(),
			'return'            => false,
			'options'           => array(),
			'custom_attributes' => array(),
			'validate'          => array(),
			'default'           => '',
	);
	/*********************************************************************************************/

// Start field type switch case

		switch ( $args['type'] ) {

				case "select" :  /* Targets all select input type elements, except the country and state select input types */
						$args['class'][] = 'form-group'; // Add a class to the field's html element wrapper - woocommerce input types (fields) are often wrapped within a <p></p> tag
						$args['input_class'] = array('form-control', 'input-lg'); // Add a class to the form input itself
						//$args['custom_attributes']['data-plugin'] = 'select2';
						$args['label_class'] = array('control-label');
						$args['custom_attributes'] = array( 'data-plugin' => 'select2', 'data-allow-clear' => 'true', 'aria-hidden' => 'true',  ); // Add custom data attributes to the form input itself
				break;

				case 'country' : /* By default WooCommerce will populate a select with the country names - $args defined for this specific input type targets only the country select element */
						$args['class'][] = 'form-group single-country';
						$args['input_class'] = array('form-control', 'input-lg'); // add class to the form input itself
						$args['label_class'] = array('control-label');
				break;

				case "state" : /* By default WooCommerce will populate a select with state names - $args defined for this specific input type targets only the country select element */
						$args['class'][] = 'form-group'; // Add class to the field's html element wrapper
						$args['input_class'] = array('form-control', 'input-lg'); // add class to the form input itself
						//$args['custom_attributes']['data-plugin'] = 'select2';
						$args['label_class'] = array('control-label');
						$args['custom_attributes'] = array( 'data-plugin' => 'select2', 'data-allow-clear' => 'true', 'aria-hidden' => 'true',  );
				break;


				case "password" :
				case "text" :
				case "email" :
				case "tel" :
				case "number" :
						$args['class'][] = 'form-group';
						//$args['input_class'][] = 'form-control input-lg'; // will return an array of classes, the same as bellow
						$args['input_class'] = array('form-control', 'input-lg');
						$args['label_class'] = array('control-label');
				break;

				case 'textarea' :
						$args['input_class'] = array('form-control', 'input-lg');
						$args['label_class'] = array('control-label');
				break;

				case 'checkbox' :
				break;

				case 'radio' :
				break;

				default :
						$args['class'][] = 'form-group';
						$args['input_class'] = array('form-control', 'input-lg');
						$args['label_class'] = array('control-label');
				break;
    }

    return $args;
}
add_filter('woocommerce_form_field_args','wc_form_field_args',10,3);




// Adding CSS in head
function hook_css() {
	$output = '';
	$classes = get_body_class();

	// Woocommerce account
	if( in_array('woocommerce-account', $classes) ) {
		$output="<style>.woocommerce{margin-top:30px}</style>";
	}

	// Woocommerce checkout
	if( in_array('woocommerce-checkout', $classes) ) {
		$output="<style>.woocommerce{margin-top:30px}</style>";
	}

	echo $output;
}
add_action('wp_head','hook_css');


// Product Video
function product_video( $atts ) {
    global $vid_title, $vid_description, $vid_youtube_id, $vid_call_to_action, $vid_call_to_action_link;
    // Attributes
    extract( shortcode_atts(
        array(
            'vid_title' => '',
            'vid_description' => '',
		    'vid_youtube_id' => '',
            'vid_call_to_action' => '',
			'vid_call_to_action_link' => '',

        ), $atts )
    );

    $vid_title = $vid_title;
    $vid_description = $vid_description;
	$vid_youtube_id = $vid_youtube_id;
	$vid_call_to_action = $vid_call_to_action;
	$vid_call_to_action_link = $vid_call_to_action_link;

}
add_shortcode( 'product_video', 'product_video' );


// Product Video Anywhere
function product_video_anywhere($atts = [], $content = null){

   $movie_details="";
   // get attibutes and set defaults
        extract(shortcode_atts(array(
                'vidany_title' => '',
                'vidany_youtube_id' => '',
				'vidany_call_to_action' => '',
				'vidany_call_to_action_link' => '',
                'vidany_description' => ''
       ), $atts));
    // Display info 
    $movie_details = '	
	<section class="productdetails_videosec" style="padding: 10px; width: 100%; background-color: #fcfcfc;border-top: 1px solid #e8e8e8;">
	<div style="border-bottom: 1px solid #e8e8e8; margin-bottom: 9px"><img src="http://150percenthosting.com/swagtron/video.png" width="70" height="70" alt=""/>
	<h2 style="float: right">' .$vidany_title. '</h2>
	</div><div class="row">
		<div class="video_box" >		
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>';
    $movie_details .= ' <script>
var tag = document.createElement("script");
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName("script")[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

var player;
function onYouTubeIframeAPIReady() {
    player = new YT.Player("ytplayer", {
        height: "250",
        width: "444",
    videoId: "' .$vidany_youtube_id. '",
    events: {
      "onReady": onPlayerReady
    }
  });
}

function onPlayerReady(event) {
    event.target.playVideo();
    event.target.setPlaybackQuality("hd720");
    event.target.setVolume(0);
}


      $(window).scroll(function() {
        $("iframe").each( function() {
            if( $(window).scrollTop() > $(this).offset().top - 225 ) {
                $(this).css("opacity",1);
                player.playVideo();
            } else {
                $(this).css("opacity",1);
                player.stopVideo();
            }
        }); 
    });
	

    </script>';
    $movie_details .= '					<div class="video-container">
         <div id="ytplayer">Swagtron</div>

</div>

	    </div>
		<div class="video_description" >	

			<div class="hoverboard_description" style="line-height: 25px; font-size: 14px; padding: 20px; margin: 0px ">
				' .$vidany_description. '
		   
		<a href="' .$vidany_call_to_action_link. '">   <button type="submit" class="single_add_to_cart_button alt" style="width: 100%; padding: 17px 40px 11px">' .$vidany_call_to_action. '</button></a>
		    
		      </div>
	    </div></section>';
    return $movie_details;
}
//add our shortcode movie
add_shortcode('product_video_anywhere', 'product_video_anywhere');

