<?php

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	wp_enqueue_script('wp-color-picker'); //for color picker scripts

	wp_enqueue_style( 'wp-color-picker' );

	$tab = sanitize_text_field( $_GET['tab'] );

	if( isset($_POST['color_swatches_setting_form_nonce_field']) )
	{
		
		if ( ! isset( $_POST['color_swatches_setting_form_nonce_field'] ) || ! wp_verify_nonce( $_POST['color_swatches_setting_form_nonce_field'], 'color_swatches_setting_form_action' ) ) 
		{

		   print 'Sorry, your nonce did not verify.';
		   
		   exit;

		} 
		else
		{
			
			$swatches_style_p	 = sanitize_text_field($_POST['swatches_style']);
			
			$swatches_term_label_show_p	 = sanitize_text_field($_POST['swatches_term_label_show']);
			
			$image_size_p	 = sanitize_text_field($_POST['image_size']);
			
			$image_size1_p	 = sanitize_text_field($_POST['image_size1']);
			
			$color_image_size_p	 = sanitize_text_field($_POST['color_image_size']);
			
			$color_image_size1_p	 = sanitize_text_field($_POST['color_image_size1']);
			
			$icon_image_size_p	 = sanitize_text_field($_POST['icon_image_size']);
			
			$icon_image_size1_p	 = sanitize_text_field($_POST['icon_image_size1']);
			
			$text_image_size_p	 = sanitize_text_field($_POST['text_image_size']);
			
			$text_image_size1_p	 = sanitize_text_field($_POST['text_image_size1']);
			
			$active_bordercolor_p	 = sanitize_text_field($_POST['active_bordercolor']);
			
			$default_bordercolor_p	 = sanitize_text_field($_POST['default_bordercolor']);
			
			$swatch_color_p	 = sanitize_text_field($_POST['swatch_color']);
			
			$swatch_hover_color_p	 = sanitize_text_field($_POST['swatch_hover_color']);
			
			$array 	= array();
			
			
								
			$array['swatches_style']  = $swatches_style_p;
			
			$array['swatches_term_label_show']  = $swatches_term_label_show_p;
			
			$array['image_size']  = $image_size_p;
			
			$array['image_size1']  = $image_size1_p;
			
			$array['color_image_size']  = $color_image_size_p;
			
			$array['color_image_size1']  = $color_image_size1_p;
			
			$array['icon_image_size']  = $icon_image_size_p;
			
			$array['icon_image_size1']  = $icon_image_size1_p;
			
			$array['text_image_size']  = $text_image_size_p;
			
			$array['text_image_size1']  = $text_image_size1_p;
			
			$array['active_bordercolor']  = $active_bordercolor_p;
			
			$array['default_bordercolor']  = $default_bordercolor_p;
			
			$array['swatch_color']  = $swatch_color_p;
			
			$array['swatch_hover_color']  = $swatch_hover_color_p;
			
			update_option('color_swatches_setting_values', $array);
			
		}
	}

	$color_swatches_setting_values = get_option('color_swatches_setting_values');
	
	$swatches_style = $color_swatches_setting_values['swatches_style'];
	
	$swatches_term_label_show = $color_swatches_setting_values['swatches_term_label_show'];
			
	$image_size = $color_swatches_setting_values['image_size'];
	
	$image_size1 = $color_swatches_setting_values['image_size1'];
	
	$color_image_size = $color_swatches_setting_values['color_image_size'];
	
	$color_image_size1 = $color_swatches_setting_values['color_image_size1'];
	
	$icon_image_size = $color_swatches_setting_values['icon_image_size'];
	
	$icon_image_size1 = $color_swatches_setting_values['icon_image_size1'];
	
	$text_image_size = $color_swatches_setting_values['text_image_size'];
	
	$text_image_size1 = $color_swatches_setting_values['text_image_size1'];
	
	$active_bordercolor = $color_swatches_setting_values['active_bordercolor'];
	
	$default_bordercolor = $color_swatches_setting_values['default_bordercolor'];
	
	$swatch_color = $color_swatches_setting_values['swatch_color'];
	
	$swatch_hover_color = $color_swatches_setting_values['swatch_hover_color'];
	
?>
	
	<h3><?php _e("Plugin Settings",'phoen-visual-attributes'); ?></h3>
	
	<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
	
		<a class="nav-tab <?php if($tab == 'general' || $tab == ''){ echo esc_html( "nav-tab-active" ); } ?>" href="?page=phoe_color_swatches_menu_pro&tab=general">General</a>
			
	</h2>
	
	
	<div class="wrap" id="profile-page">
			
		<form action="" id="form7" method="post">
			
			<?php wp_nonce_field( 'color_swatches_setting_form_action', 'color_swatches_setting_form_nonce_field' ); ?>
			
			<table class="form-table">
			
				<tbody>
					
					<tr class="popup-user-nickname-wrap">

						<th><label for="swatches_style"><?php _e('Swatch Border Style','phoen-visual-attributes'); ?>:</label></th>

						<td>
						
							<label for="square"><input id="square" class='swatches_style' type="radio" <?php if($swatches_style == 1){ echo "checked"; } ?> value="1" name="swatches_style">Square</label>

							<label for="circle"><input id="circle" class='swatches_style' type="radio" <?php if($swatches_style == 2){ echo "checked"; } ?>  value="2" name="swatches_style" >Circle</label>

						</td>

					</tr>
					
					
					<tr class="popup-user-nickname-wrap">

						<th><label for="swatches_style"><?php _e('Show Attribute Term Label','phoen-visual-attributes'); ?>:</label></th>

						<td>
						
							<input id="term_label_show" class='swatches_term_label_style' type="checkbox" <?php if($swatches_term_label_show == 1){ echo "checked"; } ?> value="1" name="swatches_term_label_show">

						</td>

					</tr>
					
					<tr class="user-nickname-wrap">
								
						<th><label for="swatch_image_size"> <?php _e('Swatch Image Thumbnail Size','phoen-visual-attributes'); ?>:</label></th>

						<td>
							<span class="long">
							
								<label class="up grey">Height(px)
								
									<input type="number" name="image_size" id="image_size" value="<?php echo $image_size; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply">&nbsp; X &nbsp;  </span>
				
							<span class="wid">
							
								<label class="up grey">Width(px)
								
									<input type="number" name="image_size1" id="image_size1" value="<?php echo $image_size1; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply"></span>
							
						</td>
							
					</tr>
					<tr class="user-nickname-wrap">
								
						<th><label for="swatch_image_size"> <?php _e('Swatch Color Thumbnail Size','phoen-visual-attributes'); ?>:</label></th>

						<td>
							<span class="long">
							
								<label class="up grey">Height(px)
								
									<input type="number" name="color_image_size" id="color_image_size" value="<?php echo $color_image_size; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply">&nbsp; X &nbsp;  </span>
				
							<span class="wid">
							
								<label class="up grey">Width(px)
								
									<input type="number" name="color_image_size1" id="color_image_size1" value="<?php echo $color_image_size1; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply"></span>
							
						</td>
							
					</tr>
					<tr class="user-nickname-wrap">
								
						<th><label for="swatch_image_size"> <?php _e('Swatch Icon Thumbnail Size','phoen-visual-attributes'); ?>:</label></th>

						<td>
							<span class="long">
							
								<label class="up grey">Height(px)
								
									<input type="number" name="icon_image_size" id="icon_image_size" value="<?php echo $icon_image_size; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply">&nbsp; X &nbsp;  </span>
				
							<span class="wid">
							
								<label class="up grey">Width(px)
								
									<input type="number" name="icon_image_size1" id="icon_image_size1" value="<?php echo $icon_image_size1; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply"></span>
							
						</td>
							
					</tr>
					<tr class="user-nickname-wrap">
								
						<th><label for="swatch_image_size"> <?php _e('Swatch Text Thumbnail Size','phoen-visual-attributes'); ?>:</label></th>

						<td>
							<span class="long">
							
								<label class="up grey">Height(px)
								
									<input type="number" name="text_image_size" id="text_image_size" value="<?php echo $text_image_size; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply">&nbsp; X &nbsp;  </span>
				
							<span class="wid">
							
								<label class="up grey">Width(px)
								
									<input type="number" name="text_image_size1" id="text_image_size1" value="<?php echo $text_image_size1; ?>" class="regular-text up" min="10" style="width:100px">
								
								</label>
							
							</span>
							
							<span class="px-multiply"></span>
							
						</td>
							
					</tr>
					
					<tr class="user-last-name-wrap">

						<th><label for="default_bordercolor">Default Swatch Border Color</label></th>

						<td><input type="text" class="regular-text" value="<?php echo $default_bordercolor; ?>" id="default_bordercolor" name="default_bordercolor"></td>

					</tr>
					
					<tr class="user-last-name-wrap">

						<th><label for="active_bordercolor">Active Swatch Border Color</label></th>

						<td><input type="text" class="regular-text" value="<?php echo $active_bordercolor; ?>" id="active_bordercolor" name="active_bordercolor"></td>

					</tr>
					
					<tr class="user-last-name-wrap">

						<th><label for="swatch_color"> Swatch Color</label></th>

						<td><input type="text" class="regular-text" value="<?php echo $swatch_color; ?>" id="swatch_color" name="swatch_color"></td>

					</tr>
					
					<tr class="user-last-name-wrap">

						<th><label for="swatch_hover_color">Swatch Hover Color</label></th>

						<td><input type="text" class="regular-text" value="<?php echo $swatch_hover_color; ?>" id="swatch_hover_color" name="swatch_hover_color"></td>

					</tr>
					
				</tbody>
				
			</table>
			
			<p>
			
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save" /> 
			
				<a class="button button-primary" id="phoe_reset_form" href="javascript:void(0);">reset</a> 
			
			</p>
			
		</form>
			
	</div>
	
	<script>

		jQuery(document).ready(function($) {

			jQuery(" #active_bordercolor , #default_bordercolor , #swatch_color , #swatch_hover_color ").wpColorPicker();
			
			$("#phoe_reset_form").click(function($) {
				
				jQuery("input[name=swatches_style][value='1']").prop("checked",true);
				
				jQuery('#image_size , #image_size1').val('32');
				
				jQuery('#active_bordercolor').wpColorPicker('color','#474747');
				
				jQuery('#default_bordercolor').wpColorPicker('color','#e2e2e2');
			
			});
			
		});
	
	</script>
	
	<style>
		.form-table th {
		width: 270px;
		padding: 25px;
	}

	.form-table td {
		
		padding: 20px 10px;
	}

	.form-table {
		background-color: #fff;
	}

	h3 {
		padding: 10px;
	}

	.px-multiply{ color:#ccc; vertical-align:bottom;}

	.long{ display:inline-block; vertical-align:middle; }

	.wid{ display:inline-block; vertical-align:middle;}

	.up{ display:block;}

	.grey{ color:#b0adad;}
	</style>