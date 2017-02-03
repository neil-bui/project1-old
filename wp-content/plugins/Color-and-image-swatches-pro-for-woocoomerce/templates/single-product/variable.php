<?php
/**
 * Variable product add to cart
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.3.0
 */
if (!defined('ABSPATH')) {
    exit;
}

global $product, $post, $woocommerce;

$swatch_type_options = get_post_meta($post->ID, 'phoe_swatch_options');

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

do_action('woocommerce_before_add_to_cart_form');

wp_enqueue_style('phoen_font_awesome_lib112', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
?>

<form class="variations_form cart" method="post" style="display:none" enctype='multipart/form-data' data-product_id="<?php echo $post->ID; ?>" data-product_variations="<?php echo esc_attr(json_encode($available_variations)) ?>">

<?php //if ( ! empty( $available_variations ) ) :  ?>

    <div class="variations">

<?php
$loop = 0;
foreach ($attributes as $name => $options) : $loop++;

    $layout = $swatch_type_options[0][md5($name)]['layout'];

    if ($swatch_type_options[0][md5($name)]['type'] == 'default') {
        $display = 'block';
    } else {
        $display = 'none';
    }
    ?>

            <div id="variation_<?php echo sanitize_title($name); ?>" class="variation">

            <?php
            if ($layout == 'default') {
                ?>

                    <div class="label"><label for="<?php echo sanitize_title($name); ?>"><?php echo wc_attribute_label($name); ?></label></div>

                    <?php
                }
                ?>

                <div class="variation_name_value" style="display:none"><?php echo wc_attribute_label($name); ?></div>

                <div class="value">

                    <select data-type="<?php echo $swatch_type_options[0][md5($name)]['type']; ?>" style="display:<?php echo $display; ?>" id="<?php echo esc_attr(sanitize_title($name)); ?>" name="attribute_<?php echo sanitize_title($name); ?>" data-attribute_name="attribute_<?php echo sanitize_title($name); ?>">

                        <option data-type="" value=""><?php echo __('Choose an option', 'woocommerce') ?>&hellip;</option>

                <?php
                if (is_array($options)) {

                    if (isset($_REQUEST['attribute_' . sanitize_title($name)])) {
                        $selected_value = $_REQUEST['attribute_' . sanitize_title($name)];
                    } elseif (isset($selected_attributes[sanitize_title($name)])) {
                        $selected_value = $selected_attributes[sanitize_title($name)];
                    } else {
                        $selected_value = '';
                    }

                    // Get terms if this is a taxonomy - ordered
                    if (taxonomy_exists($name)) {

                        $terms = wc_get_product_terms($post->ID, $name, array('fields' => 'all'));

                        foreach ($terms as $term) {

                            if ($swatch_type_options[0][md5($name)]['type'] == 'term_options') {

                                $term_id = $term->term_id;

                                $thumbnail_meta = get_woocommerce_term_meta($term_id, '', 'phoen_color', true);

                                $type = $thumbnail_meta[sanitize_title($name) . '_swatches_id_type'][0];

                                $value = $thumbnail_meta[sanitize_title($name) . '_swatches_id_' . $type][0];
                            } else {

                                $type = $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['type'];

                                if ($type == 'icon') {
                                    $value = $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['icon'];
                                } else if ($type == 'color') {
                                    $value = $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['color'];
                                } else if ($type == 'image') {
                                    $value = $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['image'];
                                }
                            }

                            if (!in_array($term->slug, $options)) {
                                continue;
                            }
                            echo '<option data-type="' . $type . '" data-value="' . $value . '" value="' . $term->slug . '" ' . selected(sanitize_title($selected_value), sanitize_title($term->slug), false) . '>' . apply_filters('woocommerce_variation_option_name', $term->name) . '</option>';
                        }
                    } else {

                        foreach ($options as $option) {
                            echo '<option value="' . esc_attr(sanitize_title($option)) . '" ' . selected(sanitize_title($selected_value), sanitize_title($option), false) . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option)) . '</option>';
                        }
                    }
                }
                ?>

                    </select>

                        <?php
                        $terms = get_the_terms($post->ID, sanitize_title($name));
                        ?>
                    <div class="variation_descriptions_wrapper">

                        <div class="variation_descriptions" id="<?php echo sanitize_title($name); ?>_descriptions" style="display:none">

                            <div rel="<?php echo sanitize_title($name); ?>_buttons" class="var-notice header-font" style="opacity: 1; right: 0px;">

                                <div class="vertAlign" style="margin-top: 0px;">Please select</div>

                            </div>

                        <?php
                        foreach ($terms as $term) {
                            $value = '';

                            if ($swatches_term_label_show == 1) {

                                $phoen_term_label = "<span class='phoen_term_label'>" . $term->name . "</span>";
                            } else {
                                $phoen_term_label = '';
                            }
                            ?>

                                <div class="variation_description" id="<?php echo sanitize_title($name); ?>_<?php echo $term->slug; ?>_description" style="display:none">

                        <?php
                        $swatch_type_options[0][md5($name)]['type'];

                        if ($swatch_type_options[0][md5($name)]['type'] == 'term_options') {

                            $term_id = $term->term_id;

                            $thumbnail_meta = get_woocommerce_term_meta($term_id, '', 'phoen_color', true);

                            $type = $thumbnail_meta[sanitize_title($name) . '_swatches_id_type'][0];

                            $option_value = $thumbnail_meta[sanitize_title($name) . '_swatches_id_' . $type][0];

                            if ($type == 'phoen_icon') {

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $icon_image_size . "px; width:" . $icon_image_size1 . "px; display:table-cell; vertical-align:middle; text-align:center;'><i class='fa " . $option_value . "'></i></span>" . $phoen_term_label . "</div>";
                            } else if ($type == 'phoen_color') {

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $color_image_size . "px; width:" . $color_image_size1 . "px; display:block;background-color:" . $option_value . "'></span>" . $phoen_term_label . "</div>";
                            } else if ($type == 'phoen_image') {

                                if ($option_value == '') {

                                    $option_value = $woocommerce->plugin_url() . '/assets/images/placeholder.png';
                                }

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $image_size . "px; width:" . $image_size1 . "px; display:block;'><img src='" . $option_value . "'></span>" . $phoen_term_label . "</div>";
                            } else {

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $text_image_size . "px; min-width:" . $text_image_size1 . "px; vertical-align:middle;width:auto; display:table-cell;text-align: center; padding:0 5px; margin-bottom:0;'>" . $term->name . "</span></div>";
                            }
                        } else {

                            $type = $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['type'];

                            if ($type == 'icon') {

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $icon_image_size . "px; width:" . $icon_image_size1 . "px; display:table-cell; vertical-align:middle; text-align:center;'><i class='fa " . $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['icon'] . "'></i></span>" . $phoen_term_label . "</div>";
                            } else if ($type == 'color') {
                                $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['color'];

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $color_image_size . "px; width:" . $color_image_size1 . "px; display:block; background-color:" . $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['color'] . "'></span>" . $phoen_term_label . "</div>";
                            } else if ($type == 'image') {

                                if ($swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['image'] != '') {

                                    $phon_swatches_image = $swatch_type_options[0][md5($name)]['attributes'][md5($term->slug)]['image'];
                                } else {

                                    $phon_swatches_image = $woocommerce->plugin_url() . '/assets/images/placeholder.png';
                                }

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $image_size . "px; width:" . $image_size1 . "px; display:block;'><img src='" . $phon_swatches_image . "'></span>" . $phoen_term_label . "</div>";
                            } else {

                                $value = "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $text_image_size . "px; min-width:" . $text_image_size1 . "px; vertical-align:middle;width:auto; display:table-cell;text-align: center; padding:0 5px; margin-bottom:0;'>" . $term->name . "</span></div>";
                            }
                        }

                        if ($type) {

                            echo $value;
                        } else {

                            echo "<div class='" . $term->slug . "_image'><span class='phoen_swatches' style='height:" . $text_image_size . "px; min-width:" . $text_image_size1 . "px; vertical-align:middle;width:auto; display:table-cell;text-align: center; padding:0 5px; margin-bottom:0;'>" . $term->name . "</span></div>";
                        }
                        ?>

                                </div>

                                    <?php
                                }
                                ?>

                        </div>

                    </div>

                                <?php
                                if (sizeof($attributes) === $loop) {

                                    echo '<a class="reset_variations" href="#reset">' . __('Clear selection', 'woocommerce') . '</a>';
                                }
                                ?>

                </div>

                                <?php
                                if ($layout == 'label_above') {
                                    ?>

                    <div class="label"><label for="<?php echo sanitize_title($name); ?>"><?php echo wc_attribute_label($name); ?></label></div>

                                    <?php
                                }
                                ?>

            </div>

                            <?php endforeach; ?>

    </div>

                            <?php do_action('woocommerce_before_add_to_cart_button'); ?>

    <div class="single_variation_wrap" style="display:none;">

                            <?php do_action('woocommerce_before_single_variation'); ?>

        <div class="single_variation"></div>

        <div class="variations_button">

                            <?php
                            woocommerce_quantity_input(array(
                                'input_value' => ( isset($_POST['quantity']) ? wc_stock_amount($_POST['quantity']) : 1 )
                            ));
                            ?>

            <button type="submit" class="single_add_to_cart_button button alt"><?php echo $product->single_add_to_cart_text(); ?></button>

        </div>

        <input type="hidden" name="add-to-cart" value="<?php echo $product->id; ?>" />

        <input type="hidden" name="product_id" value="<?php echo esc_attr($post->ID); ?>" />

        <input type="hidden" name="variation_id" class="variation_id" value="" />

<?php do_action('woocommerce_after_single_variation'); ?>

    </div>


                <?php do_action('woocommerce_after_add_to_cart_button'); ?>

                <?php /* else : ?>

                  <p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>

                  <?php endif; */ ?>

</form>

<script type="text/javascript">

    (function ($) {

        jQuery(document).ready(function () {

            if (jQuery('.variations_form').length) {

                makealloptions();

                var chocesMade = true;

                jQuery('.variations_form .variation select').each(function (index, element) {

                    if (jQuery(this).val() == '') {

                        chocesMade = false;
                    }
                });

                if (chocesMade) {

                    makealloptions();

                }
            }

            jQuery(document).on('click', '.reset_variations', function () {

                jQuery('.selected').each(function (index, element) {

                    //console.log(element);

                    jQuery(this).removeClass('selected').addClass('unselected');

                });

            });

            jQuery(document).on('click', '.variation_button', function () {

                if (jQuery('#' + jQuery(this).attr('rel')).val() == jQuery(this).attr('id')) {

                    jQuery('#' + jQuery(this).attr('rel')).val('');

                    jQuery(this).removeClass('selected').addClass('unselected');

                } else {

                    jQuery('#' + jQuery(this).attr('rel')).val(jQuery(this).attr('id'));

                    jQuery('#' + jQuery(this).attr('rel') + '_buttons .variation_button').removeClass('selected').addClass('unselected');

                    jQuery(this).removeClass('unselected').addClass('selected');


                    var notTarget = jQuery(this).attr('rel') + '_' + jQuery(this).attr('id') + '_description';

                    jQuery('#' + jQuery(this).attr('rel') + '_descriptions .variation_description').each(function () {

                        if (jQuery(this).attr('id') != notTarget) {

                            jQuery(this).hide();
                        }

                    });


                }

                jQuery('#' + jQuery(this).attr('rel')).change();

            });

            jQuery('.variation_descriptions_wrapper:first-child').append('');

            jQuery(document).on('change', '.variations_form select', function () {

                makealloptions();

            });

            function makealloptions() {

                jQuery('.variations_form select').each(function (index, element) {

                    var curr_select = jQuery(this).attr('id');

                    //console.log(curr_select);

                    if (jQuery(this).attr('data-type') == 'default')
                    {

                        var type = 'none';

                    } else
                    {

                        var type = 'block';

                    }

                    if (jQuery('#' + curr_select + '_buttons').length) {

                        if (!jQuery('#' + curr_select + '_buttons').find('.selected').length) {

                            jQuery('#' + curr_select + '_buttons').html('');

                            jQuery('#' + curr_select + '_descriptions .variation_description').stop(true, true).slideUp("fast");

                        } else {

                            jQuery('#' + curr_select + '_buttons .unselected').hide();

                        }

                    } else {

                        jQuery('<div class="variation_buttons_wrapper"><div style="display:' + type + '" id="' + curr_select + '_buttons" class="variation_buttons"></div></div><div class="variation_descriptions_wrapper"><div id="' + curr_select + '_descriptions" class="variation_descriptions"></div></div>').insertBefore(jQuery(this));

                    }

                    jQuery('#' + jQuery(this).attr('id') + ' option').each(function (index, element) {

                        if (jQuery(this).val() != '') {

                            //console.log( jQuery(this).val() );

                            // Get Image
                            var image = jQuery('#' + curr_select + '_' + jQuery(this).val() + '_description .image img');

                            if (jQuery('#' + jQuery(this).val()).length && jQuery('#' + jQuery(this).val()).hasClass('variation_button')) {

                                jQuery('#' + jQuery(this).val()).show();

                            } else
                            {

                                jQuery("#" + curr_select + '_buttons').append('<a href="javascript:void(0);" class="variation_button' + ((jQuery('#' + curr_select).val() == jQuery(this).val()) ? ' selected' : ' unselected') + '" id="' + jQuery(this).val() + '" title="' + jQuery(this).text() + '" rel="' + curr_select + '">' + jQuery('.' + jQuery(this).val() + '_image').html() + '</a>');

                                if (jQuery('#' + curr_select).val() == jQuery(this).val()) {

                                    jQuery('#' + curr_select + '_' + jQuery(this).val() + '_description .var-notice').stop(true, true).hide();

                                    jQuery('#' + curr_select + '_' + jQuery(this).val() + '_description').stop(true, true).slideDown("fast");

                                }
                            }
                        } else {

                            if (jQuery('#' + curr_select + ' option').length == 1 && jQuery('#' + curr_select + ' option[value=""]').length) {

                                jQuery("#" + curr_select + '_buttons').append('Combination Not Avalable <a href="javascript:void(0);" class="variation_reset">Reset</a>');

                            }
                        }

                    });

                });

                /* if(jQuery('.single_variation .price .amount').is(':visible') || jQuery('.single_add_to_cart_button').is(':visible')){
                 
                 jQuery('p.lead-time').show();
                 
                 jQuery('p.price-prompt').hide();
                 
                 if(jQuery('.single_variation .price .amount').is(':visible')){
                 
                 jQuery('div [itemprop="offers"] .price').hide();
                 
                 }else{
                 
                 jQuery('div [itemprop="offers"] .price').clone().appendTo( jQuery( ".single_variation" ) );
                 
                 }
                 } */

                jQuery('form.variations_form').fadeIn();

                jQuery('.product_meta').fadeIn();

            }

        });

    })(jQuery)

</script>

<style type="text/css">

    .variation_buttons_wrapper .variation_button { display:inline-block; vertical-align:top; margin-right:5px; } 

    .variations .variation_buttons_wrapper a{text-decoration:none;text-align:center;}

    .select-wrapper{ display:none!important; }

    .variation_buttons .variation_button.selected span.phoen_swatches { border: 1px solid <?php echo $active_bordercolor; ?>; }	

    .variations .variation_buttons_wrapper a span.phoen_swatches{ border:solid 1px <?php echo $default_bordercolor; ?>; }

    .variations .variation_buttons_wrapper a span.phoen_swatches{ color:<?php echo $swatch_color; ?>;}

    .variations .variation_buttons_wrapper a:hover span.phoen_swatches{ color:<?php echo $swatch_hover_color; ?>; }

<?php
if ($swatches_style == 2) {
    ?>
        .variations .variation_buttons_wrapper a span.phoen_swatches{ border-radius:50%; }

        .variations .variation_buttons_wrapper a .phoen_swatches > img{ border-radius:50%;}

    <?php
}
?>

</style>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>