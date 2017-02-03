<?php
/**
 * Outputs the Album Code Metabox Content.
 *
 * @since   1.3.0
 *
 * @package Envira_Album
 * @author 	Tim Carr
 */
?>
<p><?php _e( 'You can place this album anywhere into your posts, pages, custom post types or widgets by using <strong>one</strong> the shortcode(s) below:', 'envira-albums' ); ?></p>
<div class="envira-code">
	<code id="envira_shortcode_id_<?php echo $data['post']->ID; ?>"><?php echo '[envira-album id="' . $data['post']->ID . '"]'; ?></code>
	<a href="#" title="<?php _e( 'Copy Shortcode to Clipboard', 'envira-albums' ); ?>" data-clipboard-target="#envira_shortcode_id_<?php echo $data['post']->ID; ?>" class="dashicons dashicons-clipboard envira-clipboard">
		<span><?php _e( 'Copy to Clipboard', 'envira-albums' ); ?></span>
	</a>
</div>

<?php
if ( ! empty( $data['album_data']['config']['slug'] ) ) {
	?>
	<div class="envira-code">
		<code id="envira_shortcode_slug_<?php echo $data['post']->ID; ?>"><?php echo '[envira-album slug="' . $data['album_data']['config']['slug'] . '"]'; ?></code>
		<a href="#" title="<?php _e( 'Copy Shortcode to Clipboard', 'envira-albums' ); ?>" data-clipboard-target="#envira_shortcode_slug_<?php echo $data['post']->ID; ?>" class="dashicons dashicons-clipboard envira-clipboard">
			<span><?php _e( 'Copy to Clipboard', 'envira-albums' ); ?></span>
		</a>
	</div>
	<?php
}
?>

<p><?php _e( 'You can also place this album into your template files by using <strong>one</strong> the template tag(s) below:', 'envira-albums' ); ?></p>
<div class="envira-code">
	<code id="envira_template_tag_id_<?php echo $data['post']->ID; ?>"><?php echo 'if ( function_exists( \'envira_album\' ) ) { envira_album( \'' . $data['post']->ID . '\' ); }'; ?></code>
	<a href="#" title="<?php _e( 'Copy Template Tag to Clipboard', 'envira-albums' ); ?>" data-clipboard-target="#envira_template_tag_id_<?php echo $data['post']->ID; ?>" class="dashicons dashicons-clipboard envira-clipboard">
		<span><?php _e( 'Copy to Clipboard', 'envira-albums' ); ?></span>
	</a>
</div>

<?php 
if ( ! empty( $data['album_data']['config']['slug'] ) ) {
	?>
	<div class="envira-code">
	    <code id="envira_template_tag_slug_<?php echo $data['post']->ID; ?>"><?php echo 'if ( function_exists( \'envira_album\' ) ) { envira_album( \'' . $data['album_data']['config']['slug'] . '\', \'slug\' ); }'; ?></code>
	    <a href="#" title="<?php _e( 'Copy Template Tag to Clipboard', 'envira-albums' ); ?>" data-clipboard-target="#envira_template_tag_slug_<?php echo $data['post']->ID; ?>" class="dashicons dashicons-clipboard envira-clipboard">
			<span><?php _e( 'Copy to Clipboard', 'envira-albums' ); ?></span>
		</a>
	</div>
    <?php
}