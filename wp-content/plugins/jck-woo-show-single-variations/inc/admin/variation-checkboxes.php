<?php global $jck_wssv; ?>

<?php $checkboxes = $this->get_variation_checkboxes( $variation, $loop ); ?>

<?php if( !empty( $checkboxes ) ) { ?>
    <?php foreach( $checkboxes as $checkbox ) { ?>

        <label><input type="checkbox" class="checkbox <?php echo $checkbox['class']; ?>" id="<?php echo $checkbox['id']; ?>" name="<?php echo $checkbox['name']; ?>" <?php checked( $checkbox['checked'], true ); ?> /> <?php echo $checkbox['label']; ?></label>

    <?php } ?>
<?php } ?>