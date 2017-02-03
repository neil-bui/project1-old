<?php
/*
Plugin Name: Color and Image Swatches pro for Product Attributes
Plugin URI: http://www.phoeniixx.com
Description: By using our plugin you can generate color and image swatches to display the available product variable attributes like colors, sizes, styles etc.
Version: 1.4
Text Domain: phoen-visual-attributes
Domain Path: /i18n/languages/
Author: Phoeniixx
Author URI: http://www.phoeniixx.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) 
{

	if (!class_exists('phoen_attr_color_add_Plugin')) {

		class phoen_attr_color_add_Plugin {
			
			private $product_attribute_images;

			public function __construct() {
				
				add_action( 'admin_menu', array( $this, 'phoe_color_swatches_admin_menu' ) ); //for admin menu

				require 'classes/phoen-old-product-data-option.php';
				
				require 'classes/phoen-new-swatches-product-data-tab-option.php'; 
				
				require 'classes/phoen-product-attribute-images-class.php';
				
				require 'classes/phoen-term-class.php';
				
				$this->product_data_tab = new PHOEN_PRODUCT_CUSTOM_DATA();
				
				add_action('init', array(&$this, 'on_init'));
				
				add_action( 'admin_enqueue_scripts',array(&$this, 'wp_enqueue_color_picker') );
				
				add_action( 'woocommerce_locate_template',array(&$this, 'phoen_locate_template'), 20, 5 );
				
				$this->product_attribute_images = new PHOEN_PRODUCT_ATTRIBUTES_SWATCHES('swatches_id', 'attr_image_size');
				
				register_activation_hook(__FILE__, array( $this, 'phoe_color_swatches_activation' ) );
				
			}
			
			public	function phoe_color_swatches_activation() {

				$color_swatches_setting_values =  get_option( 'color_swatches_setting_values' );
				
				if($color_swatches_setting_values == '')
				{
					
					$array 	= array();
						
					$array['swatches_style']  = '1';
					
					$array['swatches_term_label_show']  = '0';
					
					$array['image_size']  = '32';
					
					$array['image_size1']  = '32';
					
					$array['color_image_size']  = '32';
					
					$array['color_image_size1']  = '32';
					
					$array['text_image_size']  = '32';
					
					$array['text_image_size1']  = '32';
					
					$array['icon_image_size']  = '32';
					
					$array['icon_image_size1']  = '32';
					
					$array['active_bordercolor']  = '#474747';
					
					$array['default_bordercolor']  = '#e2e2e2';
					
					update_option('color_swatches_setting_values', $array);
					
				}
		
			}
			
			public function phoe_color_swatches_admin_menu() {

				add_menu_page(__('Color Swatches','phoe_color_swatches'), __('Color Image Swatches','phoe_color_swatches'), 'manage_options' , 'phoe_color_swatches_menu_pro' , '' , plugin_dir_url( __FILE__ )."assets/images/logo-wp.png" );

				add_submenu_page('phoe_color_swatches_menu_pro', __('Color Image Swatches','phoe_color_swatches'), __('Color Image Swatches','phoe_color_swatches'), 'manage_options', 'phoe_color_swatches_menu_pro', array( $this, 'phoe_color_swatches_menu_pro_func' ) );
		
			}

			public function phoe_color_swatches_menu_pro_func()
			{
				
					require 'classes/admin_settings.php';
					
			}
			
			public function phoen_attr_swatches_admin_product_meta( $post_id, $post ) {
				
				parent::process_meta_box( $post_id, $post );


				$swatch_type_options = isset( $_POST['phoe_swatch_options'] ) ? $_POST['phoe_swatch_options'] : false;
				$swatch_type = 'default';

				if ( $swatch_type_options && is_array( $swatch_type_options ) ) {
					
					foreach ( $swatch_type_options as $options ) {
						
						if ( isset( $options['type'] ) && $options['type'] != 'default' ) {
							
							$swatch_type = 'pickers';
							
							break;
							
						}
					}

					update_post_meta( $post_id, 'phoe_swatch_options', $swatch_type_options );
				}

				update_post_meta( $post_id, '_swatch_type', $swatch_type );
			}

			public function phoen_attr_swatches_admin_product_tab() {
				
				?>
				
					<script>
					
						jQuery( document ).ready( function($) {

								$("#product-type").change(function () {
									
									var value = this.value;
									
									if(value === 'grouped' || value === 'external'|| value === 'simple')
									{
										$('.phoen_color_image_swatch_list').hide();
									}
									else
									{
										$('.phoen_color_image_swatch_list').show();
									}
									
								});
								
								var valuep  = $('#product-type :selected').val();
								
								if( valuep === 'grouped' || valuep === 'external' || valuep === 'simple')
								{
									$('.phoen_color_image_swatch_list').hide();
								}
								else
								{
									$('.phoen_color_image_swatch_list').show();
								}
								
						});
							
							
					</script>
					
					<li class="phoen_color_image_swatch_list"><a href="#phoen_color_image_swatch"><?php _e('Color & image Swatches', 'phoen-visual-attributes'); ?></a></li>
				
				<?php
			}
			
			public function phoen_attr_swatches_admin_product_option()
			{
				
				global $post;
				
				$product_custom_options_added = array_filter( (array) get_post_meta( $post->ID, '_phoen_swatch_options', true ) );
				
				?>
					<div id="phoen_color_image_swatch" class="panel phoen_color_image_swatch_panel phoen_color_image_swatch-metaboxes-wrapper">
							
							<div id="color_and_image_swatch_option" class="phoen_color_image_swatch-metaboxes">
								
								<?php include( 'phoen_color_and_image_options_html.php' );?>
								
							</div>
							
					</div>
					
				<?php
				
			}
			
			function do_javascript() {
					
				global $woocommerce;
				
				ob_start();
				
					?>
					
					jQuery(document).ready(function($) {
						
						var current_field_wrapper;

						window.send_to_editor_default = window.send_to_editor;

						jQuery('#swatches').on('click', '.upload_image_button, .remove_image_button', function() {

							var post_id = jQuery(this).attr('rel');
							
							var parent = jQuery(this).parent();
							
							current_field_wrapper = parent;

							if (jQuery(this).is('.remove_image_button')) {

								jQuery('.upload_image_id', current_field_wrapper).val('');
								
								jQuery('img', current_field_wrapper).attr('src', '<?php echo woocommerce_placeholder_img_src(); ?>');
								
								jQuery(this).removeClass('remove');

							} else {

								window.send_to_editor = window.send_to_pidroduct;
								
								formfield = jQuery('.upload_image_id', parent).attr('name');
								
								tb_show('', 'media-upload.php?&amp;type=image&amp;TB_iframe=true');
							
							}

							return false;
						
						});

						window.send_to_pidroduct = function(html) {

							jQuery('body').append('<div id="temp_image">' + html + '</div>');

							var img = jQuery('#temp_image').find('img');

							imgurl = img.attr('src');
							
							imgclass 	= img.attr('class');
							
							imgid	= parseInt(imgclass.replace(/\D/g, ''), 10);

							jQuery('.upload_image_id', current_field_wrapper).val(imgid);
							
							jQuery('img', current_field_wrapper).attr('src', imgurl);
							
							var $preview = jQuery(current_field_wrapper).closest('div.sub_field').find('.swatch-wrapper');
							
							jQuery('img', $preview).attr('src', imgurl);
							
							tb_remove();
							
							jQuery('#temp_image').remove();

							window.send_to_editor = window.send_to_editor_default;
						
						}

					});

					<?php
				
				$javascript = ob_get_clean();
				
				//WC_Swatches_Compatibility::wc_enqueue_js( $javascript );
			}
			
			public function process_product_meta_custom_tab( $post_id ) {

				$product_custom_options = $this->save_all_data_in_db();
				
				update_post_meta( $post_id, '_phoen_swatch_options', $product_custom_options );
					
			}
			
			public function phoen_locate_template( $template, $template_name, $template_path ) {
				
				global $product;

				if ( strstr( $template, 'variable.php' ) ) {

					//Look within passed path within the theme - this is priority
					
					$template = locate_template(
					
						array(
						
							trailingslashit( 'woocommerce-swatches' ) . 'single-product/variable.php',
							
							$template_name
							
						)
					);

					//Get default template
					
					if ( !$template ) {
						
						$template = plugin_dir_path( __FILE__ ) . 'templates/single-product/variable.php';
						
					}
					
					
				}
				
				return $template;
			}
				
			public function wp_enqueue_color_picker( $hook_suffix ) {
				
				wp_enqueue_style( 'wp-color-picker' );
				
				wp_enqueue_script( 'wp-color-picker');
				
			}
			
			public function on_init() {
				
				global $woocommerce;

				$image_size = get_option('attr_image_size', array());
				
				$size = array();

				$size['width'] = isset($image_size['width']) && !empty($image_size['width']) ? $image_size['width'] : '32';
				$size['height'] = isset($image_size['height']) && !empty($image_size['height']) ? $image_size['height'] : '32';
				$size['crop'] = isset($image_size['crop']) ? $image_size['crop'] : 1;

				$image_size = apply_filters('woocommerce_get_image_size_swatches_image_size', $size);

				add_image_size('attr_image_size', apply_filters('woocommerce_swatches_size_width_default', $image_size['width']), apply_filters('woocommerce_swatches_size_height_default', $image_size['height']), $image_size['crop']);
			} 
			
		}
		
	}

	
	$GLOBALS['phoen_attr_color_swatches_add'] = new phoen_attr_color_add_Plugin();
}

?>
