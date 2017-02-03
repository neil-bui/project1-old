jQuery(document).ready(function($) {

	$('#fraudlabspro-woocommerce-notice').click(function() {

		data = {
			action: 'fraudlabspro_woocommerce_admin_notice',
			fraudlabspro_woocommerce_admin_nonce: fraudlabspro_woocommerce_admin.fraudlabspro_woocommerce_admin_nonce
		};

		$.post( ajaxurl, data );
		
		event.preventDefault();

		return false;
	});
	
});