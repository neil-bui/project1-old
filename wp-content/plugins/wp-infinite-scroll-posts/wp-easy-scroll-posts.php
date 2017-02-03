<?php
/*
* Plugin Name: WP EasyScroll Posts
* Plugin URI: http://www.vivacityinfotech.net
* Description:  Easy and fast load plugin to append next page of posts to your current page when a user scrolls to the bottom.
* Version: 1.2
* Author:  Vivacity Infotech Pvt. Ltd.
* Author URI: http://www.vivacityinfotech.net
* Text Domain: wp-easy-scroll-posts
* Domain Path: /languages/
* @copyright  Copyright (c) 2014, Vivacity Infotech PVT. LTD.
* @license    http://www.gnu.org/licenses/gpl-2.0.html

*/
if ( ! defined( 'ABSPATH' ) ) exit;

	require_once dirname( __FILE__ ) . '/includes/scroll-options.php';
	require_once dirname( __FILE__ ) . '/includes/scroll-admin.php';

class vi_EasyScroll_Posts {
	static $instance;
	public $options;
	public $admin;
	public $submit;
	public $name      = 'WP EasyScroll Posts';
	public $slug      = 'wp-easy-scroll-posts'; 
	public $slug_     = 'wp_easy_scroll_posts'; 
	public $prefix    = 'wp_easy_scroll_posts_'; 
	public $file      = null;
	public $version   = '1.0';

	/**
	 * Construct the primary class and auto-load all child classes
	 */
	 
	function __construct() {
		self::$instance = &$this;
		$this->file    = __FILE__;
		$this->admin   = new vi_easy_scroll_posts_Admin( $this );
		$this->options = new vi_easy_scroll_posts_Options( $this );

		add_action( 'admin_init', array( &$this, 'vi_scroll_css' ) );
		add_action( 'admin_init', array( &$this, 'vi_upgrade_check' ) );
		add_action( 'init', array( &$this, 'vi_scroll_lang_support' ) );
		add_action( 'init', array( &$this, 'vi_init_defaults' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'vi_enqueue_js' ) );
		add_action( 'wp_footer', array( &$this, 'vi_footer' ), 100 ); 
		add_action( 'wp', array( &$this, 'vi_paged_404_fix' ) );
	}

	function vi_scroll_css() {
		wp_register_style('scroll_css', plugins_url('css/style.css',__FILE__ ));
		wp_enqueue_style('scroll_css');
	}
	/* default options */
	function vi_init_defaults() {
		$this->options->defaults = array(
			'loading' => array(
				'msgText'         => '<em>' . __( 'Loading...', 'wp-easy-scroll-posts' ) . '</em>',
				'finishedMsg'     => '<em>' . __( 'No additional posts.', 'wp-easy-scroll-posts' ) . '</em>',
				'img'             => plugins_url( 'img/ajax-loader-1.gif', __FILE__ ),
				'align'				=> 'center'
			),
			'nextSelector'    => '.paging-navigation a:first',
			'navSelector'     => '.paging-navigation',
			'itemSelector'    => '.post',
			'contentSelector' => '#content'
						
		);
	}

	function vi_enqueue_js() {
		if (!$this->vi_shouldLoadJavascript()) {
			return;
		}

		wp_enqueue_script( $this->slug, plugins_url( '/js/front-end/jquery.infinitescroll.dev.js', __FILE__ ), array( 'jquery' ), $this->version, true );
		$options = apply_filters( $this->prefix . 'js_options', $this->options->vi_get_options() );
		wp_localize_script($this->slug, $this->slug_, json_encode($options));

	}

	function vi_footer() {
		if (!$this->vi_shouldLoadJavascript()) {
			return;
		}

		require dirname( __FILE__ ) . '/templates/footer.php';
	}

	
	function vi_scroll_lang_support() {
		load_plugin_textdomain( $this->slug, false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	function vi_upgrade_check() {
		if ($this->options->db_version == $this->version) {
			return;
		}

		$this->vi_upgrade( $this->options->db_version, $this->version );

		do_action( $this->prefix . 'vi_upgrade', $this->version, $this->options->db_version );

		$this->options->db_version = $this->version;
	}

	function vi_upgrade( $from , $to ) {
		if ($from < "1.0") {
			//array of option conversions in the form of from => to
			$map = array(
				'js_calls' => 'callback',
				'image' => 'img',
				'text' => 'msgText',
				'donetext' => 'finishedMsg',
				'content_selector' => 'contentSelector',
				'post_selector' => 'itemSelector',
				'nav_selector' => 'navSelector',
				'next_selector' => 'nextSelector',
				'debug' => 'debug',
			);

			$old = get_option( 'vivascroll_options' );
			$new = array();

			if ( !$old ) {
				//loop through options and attempt to find
				foreach ( array_keys( $map ) as $option ) {
					$legacy = get_option( 'vivascroll_' . $option );

					if ( !$legacy )
						continue;

					//move to new option array and delete old
					$new[ $map[ $option ] ] = $legacy;
					delete_option( 'vivascroll_' . $option );
				}
			}

			foreach ( $map as $from => $to ) {

				if ( !$old || !isset( $old[ 'vivascroll_' . $from ] ) )
					continue;

				$new[ $to ] = $old[ 'vivascroll_' . $from ];

			}

			foreach ( array( 'contentSelector', 'itemSelector', 'navSelector', 'nextSelector' ) as $field ) {
				if ( isset( $new[$field] ) ) {
					$new[$field] = html_entity_decode($new[$field]);
				}
			}

			$new['loading'] = array( );

			foreach ( array( 'finishedMsg', 'msgText', "img" ) as $field ) {
				if ( isset( $new[$field] ) ) {
					$new['loading'][$field] = $new[$field];
					unset( $new[$field] );
				}
			}

			if( isset($new["loading"]['img']) && !strstr($new["loading"]["img"], "/img/ajax-loader-1.gif") )
				$new["loading"]['img'] = str_replace("/ajax-loader-1.gif", "/img/ajax-loader-1.gif", $new["loading"]['img']);

			//don't pass an empty array so the default filter can properly set defaults
			if ( empty( $new['loading'] ) )
				unset( $new['loading'] ); 

			$this->options->vi_set_options( $new );
			delete_option( 'vivascroll_options' );

		}

		if ($from < '1.0') {
			$old = get_option("wp_easy_scroll_posts");
			$new = $old;
			$new["loading"]["img"] = $old["img"];
			unset($new["img"]);
         $this->options->set_options($new);
		}
	}

	function vi_paged_404_fix( ) {
		global $wp_query;

		if ( is_404() || !is_paged() || 0 != count( $wp_query->posts ) )
			return;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	function vi_shouldLoadJavascript() {
		if (is_singular()) {
			return false;
		}
		return true;
	}
}
$wp_easy_scroll_posts = new vi_EasyScroll_Posts();
?>