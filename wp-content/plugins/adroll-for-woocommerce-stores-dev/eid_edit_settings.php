<h2>AdRoll Plugin for WordPress</h2>

<div>
<p>AdRoll ID's should load automatically. If not, you can load/edit your id's on this page.</p>
</div>

<form action="options.php" method="POST">
	
	<?php settings_fields('adrl_setting') ?>
	<?php do_settings_sections( 'wp_adroll' ) ?>
	
	<input type="submit" value="Update AdRoll Settings" />
</form>
