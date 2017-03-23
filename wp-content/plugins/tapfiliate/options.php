<div class="wrap">
<h2>Tapfiliate</h2>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>

<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>
<?php settings_fields('tapfiliate'); ?>

<table class="form-table">

<tr valign="top">
<th scope="row">Tap Account ID:</th>
<td><input type="text" name="tap_account_id" value="<?php echo get_option('tap_account_id'); ?>" /></td>
</tr>

<tr valign="top">
<th scope="row">Integrate for:</th>
<td>
	<div style="float: left; margin-right:20px":>
		<input type="radio" id="integrate_for_wp" value="wp"  name="integrate_for" <?php echo (get_option('integrate_for') == 'wp') ? 'checked' : null;  ?>/>
		<label for="integrate_for_wp">Wordpress</label>
	</div>
	<div style="float: left; margin-right:20px":>
		<input type="radio" id="integrate_for_wc" value="wc" name="integrate_for"  <?php echo (get_option('integrate_for') == 'wc') ? 'checked' : null;  ?>/>
		<label for="integrate_for_wc">WooCommerce</label>
	</div>
	<div style="float: left; margin-right:20px":>
		<input type="radio" id="integrate_for_ec" value="ec" name="integrate_for"  <?php echo (get_option('integrate_for') == 'ec') ? 'checked' : null;  ?>/>
		<label for="integrate_for_wc">WP Easy Cart</label>
	</div>
</td>
</tr>

<tbody id="integrate_for_wordpress_settings" style="display: none">
	<tr valign="top">
	<th scope="row">Conversion/Thank You page:</th>
	<td>
		<select name="thank_you_page">
			<?php
				foreach (get_pages() as $page) {
					$field = "<option value='{$page->post_name}'";
					$field .= (get_option('thank_you_page') === $page->post_name) ? " selected" : null;
					$field .= ">{$page->post_title}</option>";
					echo $field;
				}
			?>
		</select>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row">Optional: Transaction Id Query Parameter:</th>
	<td>
		<input type="text" name="query_parameter_transaction_id" value="<?php echo get_option('query_parameter_transaction_id'); ?>" />
	</td>
	</tr>

	<tr valign="top">
	<th scope="row">Optional: Transaction Amount Query Parameter:</th>
	<td>
		<input type="text" name="query_parameter_transaction_amount" value="<?php echo get_option('query_parameter_transaction_amount'); ?>" />
	</td>
	</tr>
</tbody>
<tbody id="integrate_for_wordpress_or_woocommerce_settings" style="display: none">
	<tr valign="top">
		<th scope="row">Optional: Program group id:</th>
		<td>
			<input type="text" name="program_group" value="<?php echo get_option('program_group'); ?>" />
		</td>
	</tr>

	</table>
</tbody>

<input type="hidden" name="action" value="update" />

<p class="submit">
<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script>
$(function() {
	$('[name=integrate_for]').on('change', function(){
		var ifor = $('[name=integrate_for]:checked').val();
		if (ifor == 'wp') {
			$('#integrate_for_wordpress_settings').show();
		} else {
			$('#integrate_for_wordpress_settings').hide();
		}

		if (ifor == 'wp' || ifor == 'wc') {
			$('#integrate_for_wordpress_or_woocommerce_settings').show();
		} else {
			$('#integrate_for_wordpress_or_woocommerce_settings').hide();
		}
	});

	$('[name=integrate_for]').change();
});
</script>
