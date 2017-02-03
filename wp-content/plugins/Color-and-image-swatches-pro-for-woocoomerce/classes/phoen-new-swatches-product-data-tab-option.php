<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PHOEN_PRODUCT_CUSTOM_DATA extends phoen_old_product_data_tab {

	public function __construct() {
		
		parent::__construct( array('phoen_color_image_swatch', 'show_if_variable'), 'phoen_color_image_swatch', 'Color & Image Swatch');
		
		
	}

	public function on_admin_head() {
		
		global $woocommerce_swatches;
		
		parent::on_admin_head();
		
	}

	public function render_product_tab_content() {
		
		wp_enqueue_style( 'phoen_font_awesome_lib11','//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
		
		wp_enqueue_style( 'style-name2', plugin_dir_url(__FILE__). "./../assets/css/fontawesome-iconpicker.css" );
		
		wp_enqueue_script( 'script-name', plugin_dir_url(__FILE__)."./../assets/js/fontawesome-iconpicker.js");
		
		wp_enqueue_script( 'admin-script-name', plugin_dir_url(__FILE__)."./../assets/js/phoen_admin_assests.js");
		
		global $woocommerce, $post;
		
		global $_wp_additional_image_sizes;

		$post_id = $post->ID;

		if ( function_exists( 'get_product' ) ) {    
			
			$product = get_product( $post->ID );
		
		} else {
			
			$product = new WC_Product( $post->ID );
		} 

		$product_type_array = array( 'variable', 'variable-subscription' );

		if ( !in_array($product->product_type, $product_type_array) ) {
			return;
		}

		$swatch_type_options = get_post_meta( $post_id, 'phoe_swatch_options', true );
		
		$swatch_type = get_post_meta( $post_id, '_swatch_type', true );
		
		$swatch_size = get_post_meta( $post_id, '_swatch_size', true );
		
		if ( !$swatch_type_options ) {
			
			$swatch_type_options = array();
		
		}

		if ( !$swatch_type ) {
			
			$swatch_type = 'standard';
		
		}

		if ( !$swatch_size ) {
			$swatch_size = 'swatches_image_size';
		}

		echo '<div class="options_group toolbar">';
		
		?>

		<div class="fields_header">
			
			<table class="wcsap widefat">
				
				<thead>
				
				<th class="attribute_swatch_label">
					<?php _e( 'Product Attribute Name', 'phoen-visual-attributes' ); ?>
				</th>
				
				<th class="attribute_swatch_type">
					<?php _e( 'Attribute Control Type', 'phoen-visual-attributes' ); ?>
				</th>
				
				</thead>
			
			</table>
			
		</div>
		
		<div class="fields toolbar">

			<?php
			
			$woocommerce_taxonomies = wc_get_attribute_taxonomies();
			
			$woocommerce_taxonomy_tax = array();
			
			foreach ( $woocommerce_taxonomies as $tax ) {
				
				$woocommerce_taxonomy_tax[wc_attribute_taxonomy_name( $tax->attribute_name )] = $tax;
			}
			
			$tax = null;
			
			$attributes = $product->get_variation_attributes(); //Attributes configured on this product already.
			
			if ( $attributes && count( $attributes ) ) :
				
				$attribute_names = array_keys( $attributes );
				
				$count = 0;
				
				foreach ( $attribute_names as $name ) :
					
					$key =  md5( sanitize_title( $name ) ) ;
					
					$old_key = sanitize_title( $name );

					$key_attr = ( str_replace( '-', '_', sanitize_title( $name ) ) );

					$current_is_taxonomy = taxonomy_exists( $name );
					
					$current_type = 'default';
					
					$current_type_description = 'None';
					
					$current_size = 'swatches_image_size';
					
					$current_layout = 'default';
					
					$current_size_height = '32';
					
					$current_size_width = '32';

					$current_label = 'Unknown';
					
					$current_options = false;

					if ( isset( $swatch_type_options[$key] ) ) {
						
						$current_options = ($swatch_type_options[$key]);
					
					} elseif ( isset( $swatch_type_options[$old_key] ) ) {
						
						$current_options = ($swatch_type_options[$old_key]);
					
					}
					
					if ( $current_options ) {

						$current_size = $current_options['size'];
						
						$current_type = $current_options['type'];
						
						$current_layout = isset( $current_options['layout'] ) ? $current_options['layout'] : 'default';

						if ( $current_type != 'default' ) {
							
							$current_type_description = ($current_type == 'term_options' ? __( 'Taxonomy Colors and Images', 'phoen-visual-attributes' ) : __( 'Custom Product Colors and Images', 'phoen-visual-attributes' ));
						
						}
					}
					

					$the_size = isset( $_wp_additional_image_sizes[$current_size] ) ? $_wp_additional_image_sizes[$current_size] : $_wp_additional_image_sizes['swatches_image_size'];
					
					if ( isset( $the_size['width'] ) && isset( $the_size['height'] ) ) {
					
						$current_size_width = $the_size['width'];
						
						$current_size_height = $the_size['height'];
					
					} else {
						
						$current_size_width = 32;
						
						$current_size_height = 32;
					}

					$attribute_terms = array();
					
					if ( taxonomy_exists( $name ) ) {
						
						$tax = get_taxonomy( $name );
						
						$woocommerce_taxonomy = $woocommerce_taxonomy_tax[$name];
						
						$current_label = isset( $woocommerce_taxonomy->attribute_label ) && !empty( $woocommerce_taxonomy->attribute_label ) ? $woocommerce_taxonomy->attribute_label : $woocommerce_taxonomy->attribute_name;

						$terms = get_terms( $name, array('hide_empty' => false) );
						
						$selected_terms = isset( $attributes[$name] ) ? $attributes[$name] : array();
						
						foreach ( $terms as $term ) {
							
							if ( in_array( $term->slug, $selected_terms ) ) {
								
								$attribute_terms[] = array('id' =>  md5(  $term->slug  ) , 'label' => $term->name, 'old_id' => $term->slug);
							
							}
						}
						
					}else{
						$current_label = esc_html( $name );
						foreach ( $attributes[$name] as $term ) {
							$attribute_terms[] = array('id' => (  md5(  sanitize_title( strtolower( $term )  ) ) ), 'label' => esc_html( $term ), 'old_id' => esc_attr( sanitize_title( $term ) ));
						}
					}
					
					
					
					?>
					<div class="field">
						<div class="wcsap_field_meta">
							<table class="wcsap widefat">
								<tbody>
									<tr>
										<td class="attribute_swatch_label">
											<strong><a class="wcsap_edit_field row-title" href="javascript:;"><?php echo $current_label; ?></a></strong>
										</td>
										<td class="attribute_swatch_type">
											<?php echo $current_type_description; ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="field_form_mask">
							<div class="field_form">
								<table class="wcsap_input widefat wcsap_field_form_table">
									<tbody>
										<tr class="attribute_swatch_type">
											<td class="label">
												<label for="phoe_swatch_options_<?php echo $key_attr; ?>_type">Type</label>
											</td>

											<td>
												<select class="phoe_swatch_options_type" id="phoe_swatch_options_<?php echo $key_attr; ?>_type" name="phoe_swatch_options[<?php echo $key; ?>][type]">
													<option <?php selected( $current_type, 'default' ); ?> value="default">None</option>
													<?php if ( $current_is_taxonomy ) : ?>
														<option <?php selected( $current_type, 'term_options' ); ?> value="term_options"><?php _e( 'Taxonomy Colors and Images', 'phoen-visual-attributes' ); ?></option>
													<?php endif; ?>
													<option <?php selected( $current_type, 'product_custom' ); ?> value="product_custom"><?php _e( 'Custom Colors and Images', 'phoen-visual-attributes' ); ?></option>
												</select>
											</td>
										</tr>

										<tr class="field_option field_option_product_custom field_option_term_options" style="<?php echo $current_type != 'product_custom' && $current_type != 'term_options' ? 'display:none;' : ''; ?>">
											<td class="label">
												<label for="phoe_swatch_options_<?php echo $key_attr; ?>_layout">Layout</label>
											</td>
											<td>
												<?php $layouts = array('default' => __( 'No Label', 'phoen-visual-attributes' ), 'label_above' => __( 'Show above label', 'phoen-visual-attributes' )); ?>
												<select name="phoe_swatch_options[<?php echo $key; ?>][layout]">
													<?php foreach ( $layouts as $layout => $layout_name ) : ?>
														<option <?php selected( $current_layout, $layout ); ?> value="<?php echo $layout; ?>"><?php echo $layout_name; ?></option>
													<?php endforeach; ?>
												</select>
											</td>
										</tr>

										<tr class="field_option field_option_term_default" style="<?php echo $current_type != 'default' ? 'display:none;' : ''; ?>">
											<td class="label">

											</td>
											<td>
												<p>
													
												</p>
											</td>
										</tr>

										<tr class="field_option field_option_term_options" style="<?php echo $current_type != 'term_options' ? 'display:none;' : ''; ?>">
											<td class="label">

											</td>
											<td>
												<p>
													
												</p>
											</td>
										</tr>

										<tr class="field_option field_option_product_custom" style="<?php echo $current_type != 'product_custom' ? 'display:none;' : ''; ?>">

											<td class="label">
												<label>Attribute Configuration</label>
											</td>
											<td>
												<div class="product_custom">

													<div class="fields_header">
														<table class="wcsap widefat">
															<thead>
															<th class="attribute_swatch_preview">
																<?php _e( 'Preview', 'phoen-visual-attributes' ); ?>
															</th>
															<th class="attribute_swatch_label">
																<?php _e( 'Attribute', 'phoen-visual-attributes' ); ?>
															</th>
															<th class="attribute_swatch_type">
																<?php _e( 'Type', 'phoen-visual-attributes' ); ?>
															</th>
															</thead>
														</table>
													</div>

													<div class="fields">
													
													<?php 
														$i=0;
														
														foreach ( $attribute_terms as $attribute_term ) : 
														 
															
															$attribute_term['id'] = ( $attribute_term['id'] );

															$current_attribute_type = 'color';
															
															$current_attribute_color = '#FFFFFF';
															
															$current_attribute_image_id = 0;
															
															$current_attribute_options = false;
															
															if ( isset( $current_options['attributes'][$attribute_term['id']] ) ) {
																
																$current_attribute_options = isset( $current_options['attributes'][$attribute_term['id']] ) ? $current_options['attributes'][$attribute_term['id']] : false;
															
															} elseif ( isset( $current_options['attributes'][$attribute_term['old_id']] ) ) {
																
																$current_attribute_options = isset( $current_options['attributes'][$attribute_term['old_id']] ) ? $current_options['attributes'][$attribute_term['old_id']] : false;
															}
														
															if ( $current_attribute_options ) :
																$current_attribute_type = $current_attribute_options['type'];
																$current_attribute_color = $current_attribute_options['color'];
																$current_attribute_image_src = $current_attribute_options['image'];
																$current_attribute_icon = $current_attribute_options['icon'];
																
															elseif ( $current_is_taxonomy ) :

															endif;
															
															if($current_attribute_type == '')
															{
																$current_attribute_type = 'Text';
															}
																		
															?>

															<div class="sub_field field">

																<div class="wcsap_field_meta">

																	<table class="wcsap widefat">

																		<tbody>
																		
																		<td class="attribute_swatch_preview">
																		
																			<div class="select-option swatch-wrapper">
																				
																				<a id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_color_preview_swatch" href="javascript:;"
																				   class="swatch"
																					style="text-indent:-9999px;width:16px;height:16px;background-color:<?php echo $current_attribute_color; ?>;<?php echo ($current_attribute_type == 'color' || $current_attribute_type == 'Text' ) ? '' : 'display:none;'; ?>"><?php echo $attribute_term['label']; ?>
																				</a>
																				
																				<a id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_color_preview_image" href="javascript:;"
																				   class="image"
																				   style="width:16px;height:16px;<?php echo $current_attribute_type == 'image' ? '' : 'display:none;'; ?>">
																					<img src="<?php echo $current_attribute_image_src; ?>" class="wp-post-image" width="16px" height="16px" />
																				</a>
																				<a id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_color_preview_icon" href="javascript:;"
																				   class="icon"
																				   style="width:16px;height:16px;background-color:<?php echo $current_attribute_color; ?>;<?php echo $current_attribute_type == 'icon' ? '' : 'display:none;'; ?>">
																					<i class="fa <?php echo $current_attribute_icon; ?>"></i>
																				
																				</a>
																				
																				
																			</div>
																		</td>
																		<td class="attribute_swatch_label">
																			<strong><a class="wcsap_edit_field row-title" href="javascript:;"><?php echo $attribute_term['label']; ?></a></strong>
																		</td>
																		<td class="attribute_swatch_type">
																		
																			<?php _e( $current_attribute_type.' Swatch', 'phoen-visual-attributes' ); ?>
																		</td>
																		<tbody>

																	</table>

																</div>

																<div class="field_form_mask">
																	<div class="field_form">
																		<table class="wcsap_input widefat">
																			<tbody>
																				<tr class="attribute_swatch_type">
																					<td class="label">
																						<label for="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo esc_attr( $attribute_term['id'] ); ?>">
																							<?php _e( 'Attribute Color or Image', 'phoen-visual-attributes' ); ?>
																						</label>
																					</td>
																					<td>
																						<select class="phoe_swatch_options_attribute_type" id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo esc_attr( $attribute_term['id'] ); ?>_type" name="phoe_swatch_options[<?php echo $key; ?>][attributes][<?php echo esc_attr( $attribute_term['id'] ); ?>][type]">
																							<option <?php selected( $current_attribute_type, '' ); ?> value="">Text</option>
																							<option <?php selected( $current_attribute_type, 'color' ); ?> value="color">Color</option>
																							<option <?php selected( $current_attribute_type, 'image' ); ?> value="image">Image</option>
																							<option <?php selected( $current_attribute_type, 'icon' ); ?> value="icon">Icon</option>
																						</select>
																					</td>
																				</tr>

																				<tr class="field_option field_option_color" style="<?php echo $current_attribute_type == 'color' ? '' : 'display:none;'; ?>">
																					<td class="label">
																						<label><?php _e( 'Color', 'phoen-visual-attributes' ); ?></label>
																					</td>
																					<td class="section-color-swatch">
																						<div id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_color_picker" class="colorSelector"><div></div></div>
																						<input class="woo-color" id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_color" type="text" class="text" name="phoe_swatch_options[<?php echo $key; ?>][attributes][<?php echo esc_attr( $attribute_term['id'] ); ?>][color]" value="<?php echo $current_attribute_color; ?>" />
																					</td>
																				</tr>

																				<tr class="field_option field_option_image" style="<?php echo $current_attribute_type == 'image' ? '' : 'display:none;' ?>">
																					<td class="label">
																						<label><?php _e( 'Image', 'phoen-visual-attributes' ); ?></label>
																					</td>
																					<td>

																						<div style="line-height:60px;">
																							<div id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_image_thumbnail" style="float:left;margin-right:10px;">
																								<img src="<?php echo $current_attribute_image_src; ?>" alt="<?php _e( 'Thumbnail Preview', 'phoen-visual-attributes' ); ?>" class="wp-post-image swatch_image_swatches_id_<?php echo $count.'_'.$i; ?>" width="<?php echo '16px'; ?>" height="<?php echo '16px'; ?>">
																							</div>
																							<input class="upload_image_id_<?php echo $count.'_'.$i; ?>" type="hidden" id="phoe_swatch_options_<?php echo $key_attr; ?>_<?php echo $attribute_term['id']; ?>_image" name="phoe_swatch_options[<?php echo $key; ?>][attributes][<?php echo esc_attr( $attribute_term['id'] ); ?>][image]" value="<?php echo $current_attribute_image_src; ?>" />
																							<button type="submit" class="upload_image_button upload_image_button_<?php echo $count.'_'.$i; ?> button" rel="<?php echo $post_id; ?>"><?php _e( 'Upload/Add image', 'woocommerce' ); ?></button>
																							<button type="submit" class="remove_image_button_<?php echo $count.'_'.$i; ?> button" rel="<?php echo $post_id; ?>"><?php _e( 'Remove image', 'woocommerce' ); ?></button>
																						</div>
																						
																						<script type="text/javascript">
																						
																							var custom_upload;

																							jQuery(document).on("click",".upload_image_button_<?php echo $count.'_'.$i; ?>",uploadimage_button);

																							function uploadimage_button(){

																								var custom_upload = wp.media({

																								title: 'Add Media',

																								button: {

																									text: 'Insert Image'

																								},

																								multiple: false  // Set this to true to allow multiple files to be selected

																							})

																							.on('select', function() {

																								var attachment = custom_upload.state().get('selection').first().toJSON();
																								
																								jQuery('.swatch-photopa_colour_swatches_id').val(attachment.id);
																								
																								jQuery('.upload_image_id_<?php echo $count.'_'.$i; ?>').val(attachment.url);
																								
																								jQuery('.swatch_image_swatches_id_<?php echo $count.'_'.$i; ?>').attr('src' ,attachment.url);
																								
																								

																							})

																							.open();

																						 
																								return false;
																							}
																							
																					
																							jQuery('.remove_image_button_<?php echo $count.'_'.$i; ?>').live('click', function() {
																								jQuery('.swatch_image_swatches_id_<?php echo $count.'_'.$i; ?>').attr('src', '<?php echo $woocommerce->plugin_url() . '/assets/images/placeholder.png'; ?>');
																								
																								jQuery('.upload_image_id_<?php echo $count.'_'.$i; ?>').val('');
																								
																								return false;
																							});

																						</script>
																						<div class="clear"></div>

																					</td>
																				</tr>
																				
																				
																				<tr class="form-field field_option_icon" style="<?php echo $current_attribute_type == 'icon' ? '' : 'display:none;' ?>">
																					
																					<th scope="row" valign="top"><label><?php _e('Icon', 'phoen-visual-attributes'); ?></label></th>
																					
																					<td>
																						
																						<div "line-height:60px;" >
																						
																							<input type="hidden" id="product_attribute_<?php echo $this->meta_key; ?>" name="phoe_swatch_options[<?php echo $key; ?>][attributes][<?php echo esc_attr( $attribute_term['id'] ); ?>][icon]" value="<?php echo $current_attribute_icon; ?>" />
																							
																							<input type='text' class="icon_for_swatches_<?php echo $count.'_'.$i; ?>" name="phoe_swatch_options[<?php echo $key; ?>][attributes][<?php echo esc_attr( $attribute_term['id'] ); ?>][icon]" value="<?php echo $current_attribute_icon; ?>" >
																						
																						</div>
																						
																						<script type="text/javascript">

																							jQuery(document).ready(function(){
																								
																								jQuery('.icon_for_swatches_<?php echo $count.'_'.$i; ?>').click(function(){
																									
																										jQuery(this).iconpicker({
																								
																											placement:'top',
																									
																										});
																									
																								});
																								
																								
																							});
																						
																						</script>
																							
																					   <div class="clear"></div>
																					   
																					</td>
																					
																				</tr>
																			</tbody>
																		</table>
																	</div>
																</div>

															</div>
														<?php 
														$i++;
															endforeach; ?>
													</div>

												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
					<?php
					
				$count++;	
				endforeach;
				
			else :
				echo '<p>' . __( 'Add a at least one attribute / variation combination to this product that has been configured with color swatches or photos. After you add the attributes from the "Attributes" tab and create a variation, save the product and you will see the option to configure the swatch or photo picker here.', 'phoen-visual-attributes' ) . '</p>';
			endif;
			?>


		</div>

		<?php
		echo '</div>';

		parent::render_product_tab_content();
		?>
		<style>
			.phoen_color_image_swatch {width: 100%; float: none;}
			.phoen_color_image_swatch label {margin: 0;}
			.phoen_color_image_swatch .fields_header table tr th.attribute_swatch_label{ width:30%;}
			.phoen_color_image_swatch .field table tr td.attribute_swatch_label{ width:30%;}
			.phoen_color_image_swatch .field_form_mask .field_form table tr td.label{ width:30%;}
			.phoen_color_image_swatch .sub_field table tr td.attribute_swatch_preview { width:20%;}
			
			.phoen_color_image_swatch .product_custom tr th.attribute_swatch_preview {
				width: 20%;
			}	
			
			.phoen_color_image_swatch .product_custom tr td.attribute_swatch_label {
				width: 40%!important;
			}	
			
			.phoen_color_image_swatch .product_custom table tr th.attribute_swatch_label{ width:40%; }
			
			.phoen_color_image_swatch .fields table tr.field_option_image td div{ line-height:30px!important;}
			.phoen_color_image_swatch .fields table tr.field_option_image td div div{ line-height:30px!important; display:inline-block; float:none!imporatnt; vertical-align:middle;}
		</style>
		<?php
		
	}

	public function render_attribute_images( $product_id, $name, $is_taxonomy ) {
	?>
		<div class="product_swatches_configuration">
			
			<table>
				
				<?php if ( $is_taxonomy ) : ?>
					
					<?php $terms = get_terms( $name, array('hide_empty' => false) ); ?>
					
					<?php foreach ( $terms as $term ) : ?>
					
							<?php $this->edit_attributre_thumbnail_field( $product_id, $term, $name ); ?>
					
					<?php endforeach; ?>
				
				<?php endif; ?>
			
			</table>
		
		</div>
	<?php
	}   

	public function process_meta_box( $post_id, $post ) {
		
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

}
?>