<div class="jck-wssv-display-options">

    <strong><?php _e('Display Options','jck-wssv'); ?></strong>

    <div class="form-row form-row-full">
        <?php woocommerce_wp_text_input( array(
            'id' => "jck_wssv_display_title[$loop]",
            'label' => __( 'Title', 'jck-wssv' ),
            'type' => 'text',
            'value' => $this->get_variation_title( $variation->ID )
        ) ); ?>
    </div>

</div>