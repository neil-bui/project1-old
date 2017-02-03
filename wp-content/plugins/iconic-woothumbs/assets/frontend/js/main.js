(function($, document) {

    var iconic_woothumbs = {

        /**
         * Set up cache with common elements and vars
         */

        cache: function() {

            iconic_woothumbs.cache_run = false;

            if( iconic_woothumbs.cache_run ) { return; }

            iconic_woothumbs.els  = {};
            iconic_woothumbs.vars = {};
            iconic_woothumbs.tpl  = {};
            iconic_woothumbs.products = {};

            // common elements
            iconic_woothumbs.els.all_images_wrap              = $('.iconic-woothumbs-all-images-wrap');
            iconic_woothumbs.els.gallery                      = false;
            iconic_woothumbs.els.video_template               = $('#iconic-woothumbs-video-template');

            // common vars
            iconic_woothumbs.vars.zoom_setup                  = false;
            iconic_woothumbs.vars.window_resize_timeout       = false;
            iconic_woothumbs.vars.is_dragging_image_slide     = false;
            iconic_woothumbs.vars.loading_class               = "iconic-woothumbs-loading";
            iconic_woothumbs.vars.reset_class                 = "iconic-woothumbs-reset";
            iconic_woothumbs.vars.thumbnails_active_class     = "iconic-woothumbs-thumbnails__slide--active";
            iconic_woothumbs.vars.images_active_class         = "iconic-woothumbs-images__slide--active";
            iconic_woothumbs.vars.wishlist_added_class        = "iconic-woothumbs-wishlist-buttons--added";
            iconic_woothumbs.vars.is_zoom_enabled             = iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.zoom_general_enable);
            iconic_woothumbs.vars.is_fullscreen_enabled       = iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.fullscreen_general_enable);
            iconic_woothumbs.vars.show_variation_trigger      = "iconic_woothumbs_show_variation";
            iconic_woothumbs.vars.loading_variation_trigger   = "iconic_woothumbs_loading_variation";
            iconic_woothumbs.vars.fullscreen_trigger          = iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.fullscreen_general_click_anywhere ) ? ".iconic-woothumbs-fullscreen, img, .zm-handler" : ".iconic-woothumbs-fullscreen";
            iconic_woothumbs.vars.play_trigger                = ".iconic-woothumbs-play";


            // common templates
            iconic_woothumbs.tpl.fullscreen_button            = '<a href="javascript: void(0);" class="iconic-woothumbs-fullscreen" data-iconic-woothumbs-tooltip="'+iconic_woothumbs_vars.text.fullscreen+'"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-fullscreen"></i></a>';
            iconic_woothumbs.tpl.play_button                  = '<a href="javascript: void(0);" class="iconic-woothumbs-play" data-iconic-woothumbs-tooltip="'+iconic_woothumbs_vars.text.video+'"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-play"></i></a>';
            iconic_woothumbs.tpl.temp_images_container        = '<div class="iconic-woothumbs-temp"><div class="iconic-woothumbs-temp__images"/><div class="iconic-woothumbs-icon iconic-woothumbs-temp__thumbnails"/></div>';
            iconic_woothumbs.tpl.image_slide                  = '<div class="iconic-woothumbs-images__slide {{slide_class}}"><img class="iconic-woothumbs-images__image" src="{{image_src}}" data-srcset="{{image_src}}{{image_src_retina}}" data-large-image="{{large_image_src}}" data-large-image-width="{{large_image_width}}" data-large-image-height="{{large_image_height}}" width="{{image_width}}" height="{{image_height}}" title="{{title}}" alt="{{alt}}"></div>';
            iconic_woothumbs.tpl.thumbnail_slide              = '<div class="iconic-woothumbs-thumbnails__slide {{slide_class}}" data-index="{{index}}"><img class="iconic-woothumbs-thumbnails__image" src="{{image_src}}" data-srcset="{{image_src}}{{image_src_retina}}" title="{{title}}" alt="{{alt}}" width="{{image_width}}" height="{{image_height}}"></div>';
            iconic_woothumbs.tpl.retina_img_src               = ', {{image_src}} 2x';

            iconic_woothumbs.cache_run = true;

        },

        /**
         * Run on doc ready
         */

        on_load: function() {

            iconic_woothumbs.cache();

            iconic_woothumbs.prepare_products();
            iconic_woothumbs.init();

        },

        /**
         * Run on resize
         */

        on_resize: function() {

            iconic_woothumbs.cache();

            clearTimeout( iconic_woothumbs.vars.window_resize_timeout );

            iconic_woothumbs.vars.window_resize_timeout = setTimeout(function(){

                $(window).trigger('resize-end');

            }, 100);

        },

        /**
         * Helper: Check whether a settings value is true
         *
         * @param str val
         */

        is_true: function( val ) {

            return (parseInt(val) === 1) ? true : false;

        },

        /**
         * Helper: Check if a plugin or theme is active
         *
         * @param str name Name of the plugin or theme to check if is active
         */

        is_active: function( name ) {

            if( name === "woothemes_swatches" ) {

                return ($('#swatches-and-photos-css').length > 0) ? true : false;

            }

            return false;

        },

        /**
         * Get all products on page with WooThumbs
         * and assign to the iconic_woothumbs.products variable
         */
        prepare_products: function() {

            if( iconic_woothumbs.els.all_images_wrap.length <= 0 ) { return; }

            iconic_woothumbs.els.all_images_wrap.each(function( index, element ) {

                var $all_images_wrap = $(element),
                    $product = $all_images_wrap.closest('.product'),
                    is_variable = $product.hasClass('product-type-variable'),
                    $variations_form = is_variable ? $product.find('form.variations_form') : false,
                    variations_json = $variations_form ? $variations_form.attr('data-product_variations') : false;

                iconic_woothumbs.products[index] = {
                    'product': $product,
                    'all_images_wrap': $all_images_wrap,
                    'images': $all_images_wrap.find('.iconic-woothumbs-images'),
                    'images_wrap': $all_images_wrap.find('.iconic-woothumbs-images-wrap'),
                    'thumbnails': $all_images_wrap.find('.iconic-woothumbs-thumbnails'),
                    'thumbnails_wrap': $all_images_wrap.find('.iconic-woothumbs-thumbnails-wrap'),
                    'variations_form': $variations_form,
                    'variation_id_field': $product.find('input[name=variation_id]'),
                    'wishlist_buttons': $all_images_wrap.find('.iconic-woothumbs-wishlist-buttons'),
                    'wishlist_add_button': $all_images_wrap.find('.iconic-woothumbs-wishlist-buttons__add'),
                    'wishlist_browse_button': $all_images_wrap.find('.iconic-woothumbs-wishlist-buttons__browse'),
                    'variations_json': variations_json,
                    'maintain_slide_index': $all_images_wrap.attr('data-maintain-slide-index') === "yes",
                    'variations': variations_json ? $.parseJSON( variations_json ) : false,
                    'product_id': $variations_form ? $variations_form.data('product_id') : false,
                    'default_images': $.parseJSON( $all_images_wrap.attr('data-default') ),
                    'imagezoom': false,
                    'has_video': $all_images_wrap.data('has-video') === "yes"
                };

            });

        },

        /**
         * Init WooThumbs
         */
        init: function() {

            if( iconic_woothumbs.products.length <= 0 ) { return; }

            $.each( iconic_woothumbs.products, function( index, product_object ) {

                iconic_woothumbs.setup_sliders( product_object );
                iconic_woothumbs.watch_variations( product_object );
                iconic_woothumbs.setup_zoom( product_object );
                iconic_woothumbs.setup_fullscreen( product_object );
                iconic_woothumbs.setup_video( product_object );
                // iconic_woothumbs.setup_yith_wishlist( product_object );
                iconic_woothumbs.setup_tooltips();

            });

        },

        /**
         * Helper: Lazy load images to improve loading speed
         */

        lazy_load_images: function( product_object ) {

            var $images = product_object.images.find('img');

            if( $images.length > 0 ) {

                $images.each(function( index, el ){
                    var $image = $(el),
                        data_src = $image.attr('data-iconic-woothumbs-src');

                    if( typeof data_src !== "undefined" ) {

                        var $image_clone = $image.clone();

                        $image_clone.attr('src', data_src).css({paddingTop: "", height: ""});
                        $image.replaceWith( $image_clone );

                    }
                });

            }

        },


        /**
         * Images Slider Args
         *
         * Dynamic so the options are recalculated every time
         */

        images_slider_args: function( product_object, index ) {

            var image_count = product_object.images.children().length;

            index = typeof index !== 'undefined' && image_count > index ? index : 0;

            return {
                mode: iconic_woothumbs_vars.settings.carousel_general_mode,
                speed: parseInt(iconic_woothumbs_vars.settings.carousel_general_transition_speed),
                controls: ( image_count > 1 ) ? iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.navigation_general_controls) : false,
                infiniteLoop: ( image_count > 1 ) ? iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.carousel_general_infinite_loop) : false,
                touchEnabled: ( image_count > 1 ) ? true : false,
                adaptiveHeight: true,
                adaptiveHeightSpeed: 150,
                auto: iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.carousel_general_autoplay),
                pause: iconic_woothumbs_vars.settings.carousel_general_duration,
                pager: iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.navigation_bullets_enable ),
                prevText: '<i class="iconic-woothumbs-icon iconic-woothumbs-icon-prev"></i>',
                nextText: '<i class="iconic-woothumbs-icon iconic-woothumbs-icon-next"></i>',
                preventDefaultSwipeY: iconic_woothumbs_vars.settings.carousel_general_mode === "vertical" ? true : false,
                startSlide: index,
                onSliderLoad: function(){

                    iconic_woothumbs.go_to_thumbnail( index, product_object );
                    iconic_woothumbs.setup_srcset();

                    if( this.getSlideCount() <= 1 ) {
                        product_object.images_wrap.find('.bx-controls').hide();
                    }

                    iconic_woothumbs.init_zoom( product_object.images.find('.iconic-woothumbs-images__slide--active img'), product_object );

                    $(window).trigger('resize');

                },
                onSlideBefore: function($slide_element, old_index, new_index){

                    iconic_woothumbs.go_to_thumbnail( new_index, product_object );

                    // add active class
                    product_object.all_images_wrap
                        .find('.'+iconic_woothumbs.vars.images_active_class)
                        .removeClass( iconic_woothumbs.vars.images_active_class );

                    $slide_element.addClass( iconic_woothumbs.vars.images_active_class );

                    if( product_object.imagezoom ) {
                        product_object.imagezoom.destroy();
                    }

                },
                onSlideAfter: function($slide_element, old_index, new_index){

                    iconic_woothumbs.init_zoom( $slide_element.find('img'), product_object );

                }
            };

        },



        /**
         * Thumbnails Slider Args
         *
         * Dynamic so the options are recalculated every time
         *
         * @param obj product_object
         */

        thumbnails_slider_args: function( product_object ) {

            return {
                mode: ( iconic_woothumbs_vars.settings.navigation_thumbnails_position === "above" || iconic_woothumbs_vars.settings.navigation_thumbnails_position === "below" || iconic_woothumbs.is_below_breakpoint() && iconic_woothumbs.move_thumbnails_at_breakpoint() ) ? "horizontal" : "vertical",
                infiniteLoop: false,
                speed: parseInt(iconic_woothumbs_vars.settings.navigation_thumbnails_transition_speed),
                minSlides: iconic_woothumbs.is_below_breakpoint() ? parseInt(iconic_woothumbs_vars.settings.responsive_general_thumbnails_count) : parseInt(iconic_woothumbs_vars.settings.navigation_thumbnails_count),
                maxSlides: iconic_woothumbs.is_below_breakpoint() ? parseInt(iconic_woothumbs_vars.settings.responsive_general_thumbnails_count) : parseInt(iconic_woothumbs_vars.settings.navigation_thumbnails_count),
                slideWidth: 800,
                moveSlides: 1,
                pager: false,
                controls: false,
                slideMargin: parseInt(iconic_woothumbs_vars.settings.navigation_thumbnails_spacing),
                onSliderLoad: function() {

                    iconic_woothumbs.setup_srcset();

                    product_object.thumbnails_wrap.css({ opacity:1, height: 'auto' });
                    iconic_woothumbs.set_thumbnail_controls_visibility( this, product_object );

                },
                onSlideAfter: function($slide_element, old_index, new_index) {

                    iconic_woothumbs.set_thumbnail_controls_visibility( this, product_object );

                }
            };

        },


        /**
         * Setup sliders
         *
         * @param obj product_object
         */

        setup_sliders: function( product_object ) {

            // setup main images slider

            product_object.images.imagesLoaded( function() {

                product_object.images_slider_data = product_object.images.bxSlider( iconic_woothumbs.images_slider_args( product_object ) );
                iconic_woothumbs.lazy_load_images( product_object );

                // Stop auto on hover

                product_object.images.on('mouseover', function(){

                    if( !product_object.images_slider_data ) { return; }

                    product_object.images_slider_data.stopAuto();

                });

            });

            // setup thumbnails slider

            if( iconic_woothumbs.has_sliding_thumbnails( product_object ) ) {

                product_object.thumbnails.imagesLoaded( function() {

                    iconic_woothumbs.setup_sliding_thumbnails( product_object );

                });

                // setup click thumbnail control action

                product_object.all_images_wrap.on('click', ".iconic-woothumbs-thumbnails__control", function(){

                    if( !product_object.all_images_wrap.hasClass( iconic_woothumbs.vars.loading_class ) ) {

                        var dir = $(this).attr('data-direction');

                        if( dir === "next" ) {
                            product_object.thumbnails_slider_data.goToNextSlide();
                        } else {
                            product_object.thumbnails_slider_data.goToPrevSlide();
                        }

                    }

                });

            }

            // setup click thumbnail action

            product_object.all_images_wrap.on('click', ".iconic-woothumbs-thumbnails__slide", function(){

                if( product_object.all_images_wrap.hasClass( iconic_woothumbs.vars.loading_class ) ) { return; }

                if( !product_object ) { return; }

                var new_index = parseInt( $(this).attr('data-index') );

                iconic_woothumbs.set_active_thumbnail( product_object.thumbnails, new_index );
                product_object.images_slider_data.goToSlide( new_index );

            });

            // setup stop auto

            product_object.all_images_wrap.on('click', ".iconic-woothumbs-thumbnails__slide, .bx-next, .bx-prev, .iconic-woothumbs-zoom-prev, .iconic-woothumbs-zoom-next, .bx-pager-link", function(){

                product_object.images_slider_data.stopAuto();

            });

            // position thumbnails on load

            if( product_object.thumbnails.length > 0 ) {

                iconic_woothumbs.position_thumbnails( product_object );

            }

            // position thumbnails on resize

            $(window).on('resize', function(){

                if( iconic_woothumbs.has_sliding_thumbnails( product_object ) ) {

                    iconic_woothumbs.setup_sliding_thumbnails( product_object );

                }

                iconic_woothumbs.position_thumbnails( product_object );

            });

            return;

        },

        /**
         * Helper: Do we have sliding thumbnails?
         *
         * @param obj product_object
         */

        has_sliding_thumbnails: function( product_object ) {

            return product_object.thumbnails.length > 0 && iconic_woothumbs_vars.settings.navigation_thumbnails_type === "sliding";

        },

        /**
         * Helper: Do we have thumbnails at all?
         *
         * @param obj product_object
         */

        has_thumbnails: function( product_object ) {

            return product_object.thumbnails.length > 0 && ( iconic_woothumbs_vars.settings.navigation_thumbnails_type === "sliding" || iconic_woothumbs_vars.settings.navigation_thumbnails_type === "stacked" );

        },

        /**
         * Helper: Move thumbnails at breakpoint?
         */

        move_thumbnails_at_breakpoint: function(){

            return iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.responsive_general_thumbnails_below ) && iconic_woothumbs_vars.settings.navigation_thumbnails_position !== "below";

        },

        /**
         * Helper: Is the window width below our breakpoint limit
         */

        is_below_breakpoint: function(){

            return iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.responsive_general_breakpoint_enable ) && iconic_woothumbs.viewport().width <= parseInt( iconic_woothumbs_vars.settings.responsive_general_breakpoint, 10 );

        },

        /**
         * Helper: Get viewport dimensions
         */

        viewport: function(){

            var e = window, a = 'inner';

            if (!('innerWidth' in window )) {
                a = 'client';
                e = document.documentElement || document.body;
            }

            return { width : e[ a+'Width' ] , height : e[ a+'Height' ] };

        },

        /**
         * Helper: Setup thumbnails
         *
         * @param obj product_object
         */

        setup_sliding_thumbnails: function( product_object ) {

            if( product_object.thumbnails_slider_data ) {

                product_object.thumbnails.reloadSlider( iconic_woothumbs.thumbnails_slider_args( product_object ) );

            } else {

                product_object.thumbnails_slider_data = product_object.thumbnails.bxSlider( iconic_woothumbs.thumbnails_slider_args( product_object ) );

            }

        },

        /**
         * Helper: Position thumbnails
         *
         * @param obj product_object
         */

        position_thumbnails: function( product_object ){

            var $next_controls = product_object.all_images_wrap.find('.iconic-woothumbs-thumbnails__control--right, .iconic-woothumbs-thumbnails__control--down'),
                $prev_controls = product_object.all_images_wrap.find('.iconic-woothumbs-thumbnails__control--left, .iconic-woothumbs-thumbnails__control--up');

            if( iconic_woothumbs.is_below_breakpoint() && iconic_woothumbs.move_thumbnails_at_breakpoint() ) {

                product_object.all_images_wrap.removeClass('iconic-woothumbs-all-images-wrap--thumbnails-left iconic-woothumbs-all-images-wrap--thumbnails-right iconic-woothumbs-all-images-wrap--thumbnails-above').addClass('iconic-woothumbs-all-images-wrap--thumbnails-below');

                product_object.images_wrap.after( product_object.thumbnails_wrap );
                product_object.thumbnails_wrap.removeClass('iconic-woothumbs-thumbnails-wrap--vertical').addClass('iconic-woothumbs-thumbnails-wrap--horizontal');

                $next_controls.removeClass('iconic-woothumbs-thumbnails__control--down').addClass('iconic-woothumbs-thumbnails__control--right')
                .find('i').removeClass('iconic-woothumbs-icon-down-open-mini').addClass('iconic-woothumbs-icon-right-open-mini');
                $prev_controls.removeClass('iconic-woothumbs-thumbnails__control--up').addClass('iconic-woothumbs-thumbnails__control--left')
                .find('i').removeClass('iconic-woothumbs-icon-up-open-mini').addClass('iconic-woothumbs-icon-left-open-mini');

            } else {

                product_object.all_images_wrap.removeClass('iconic-woothumbs-all-images-wrap--thumbnails-below').addClass('iconic-woothumbs-all-images-wrap--thumbnails-'+iconic_woothumbs_vars.settings.navigation_thumbnails_position);

                if ( iconic_woothumbs_vars.settings.navigation_thumbnails_position === "left" || iconic_woothumbs_vars.settings.navigation_thumbnails_position === "above" ) {

                    product_object.images_wrap.before( product_object.thumbnails_wrap );

                }

                if ( iconic_woothumbs_vars.settings.navigation_thumbnails_position === "left" || iconic_woothumbs_vars.settings.navigation_thumbnails_position === "right" ) {

                    product_object.thumbnails_wrap.removeClass('iconic-woothumbs-thumbnails-wrap--horizontal').addClass('iconic-woothumbs-thumbnails-wrap--vertical');

                    $next_controls.removeClass('iconic-woothumbs-thumbnails__control--right').addClass('iconic-woothumbs-thumbnails__control--down')
                    .find('i').removeClass('iconic-woothumbs-icon-right-open-mini').addClass('iconic-woothumbs-icon-down-open-mini');
                    $prev_controls.removeClass('iconic-woothumbs-thumbnails__control--left').addClass('iconic-woothumbs-thumbnails__control--up')
                    .find('i').removeClass('iconic-woothumbs-icon-left-open-mini').addClass('iconic-woothumbs-icon-up-open-mini');

                }

            }

        },

        /**
         * Helper: Set visibility of thumbnail controls
         *
         * @param obj thumbnails_slider_data
         * @param obj product_object
         */

        set_thumbnail_controls_visibility: function( thumbnails_slider_data, product_object ) {

            var last_thumbnail_index = iconic_woothumbs.get_last_thumbnail_index( product_object ),
                current_thumbnail_index = thumbnails_slider_data.getCurrentSlide(),
                $next_controls = product_object.thumbnails_wrap.find('.iconic-woothumbs-thumbnails__control--right, .iconic-woothumbs-thumbnails__control--down'),
                $prev_controls = product_object.thumbnails_wrap.find('.iconic-woothumbs-thumbnails__control--left, .iconic-woothumbs-thumbnails__control--up');

            if( thumbnails_slider_data.getSlideCount() <= 1 || thumbnails_slider_data.getSlideCount() <= parseInt(iconic_woothumbs_vars.settings.navigation_thumbnails_count) ) {
                product_object.thumbnails_wrap.find('.iconic-woothumbs-thumbnails__control').hide();
                return;
            }

            if( current_thumbnail_index === 0 ) {
                $next_controls.show();
                $prev_controls.hide();
            } else if( current_thumbnail_index === last_thumbnail_index ) {
                $next_controls.hide();
                $prev_controls.show();
            } else {
                product_object.thumbnails_wrap.find('.iconic-woothumbs-thumbnails__control').show();
            }

        },

        /**
         * Helper: Set active thumbnail
         *
         * @param obj $thumbnails
         * @param int index
         */

        set_active_thumbnail: function( $thumbnails, index ) {

            $thumbnails.find(".iconic-woothumbs-thumbnails__slide").removeClass( iconic_woothumbs.vars.thumbnails_active_class );
            $thumbnails.find(".iconic-woothumbs-thumbnails__slide[data-index="+index+"]").addClass( iconic_woothumbs.vars.thumbnails_active_class );

        },

        /**
         * Helper: Go to thumbnail
         *
         * @param int index
         */

        go_to_thumbnail: function( index, product_object ) {

            if( product_object.thumbnails_slider_data ) {

                var thumbnail_index = iconic_woothumbs.get_thumbnail_index( index, product_object );

                product_object.thumbnails_slider_data.goToSlide( thumbnail_index );

            }

            iconic_woothumbs.set_active_thumbnail( product_object.thumbnails, index );

        },

        /**
         * Helper: Get thumbnail index
         *
         * @param int index
         */

        get_thumbnail_index: function( index, product_object ) {

            if( parseInt(iconic_woothumbs_vars.settings.navigation_thumbnails_count) === 1 ) {
                return index;
            }

            var last_thumbnail_index = iconic_woothumbs.get_last_thumbnail_index( product_object ),
                new_thumbnail_index = ( index > last_thumbnail_index ) ? last_thumbnail_index : ( index === 0 ) ? 0 : index - 1;

            return new_thumbnail_index;

        },

        /**
         * Helper: Get thumbnail index
         *
         * @param obj product_object
         */

        get_last_thumbnail_index: function( product_object ) {

            var thumbnail_count = product_object.thumbnails.children().length,
                last_slide_index = thumbnail_count - iconic_woothumbs_vars.settings.navigation_thumbnails_count;

            return last_slide_index;

        },

        /**
         * Watch for changes in variations
         *
         * @param obj product_object
         */

        watch_variations: function( product_object ) {

            if( !product_object.variations_form ) { return; }

            product_object.variation_id_field.on('change', function() {

                var variation_id = parseInt( $(this).val() ),
                    currently_showing = parseInt( product_object.all_images_wrap.attr('data-showing') );

                if( !isNaN(variation_id) && variation_id !== currently_showing ) {

                    iconic_woothumbs.get_variation_data( product_object, variation_id );

                }

            });

            // on reset data trigger

            product_object.variations_form.on('reset_data', function() {

                setTimeout(function(){

                    iconic_woothumbs.reset_images( product_object );

                }, 250);

            });

            // on loading variation trigger

            product_object.all_images_wrap.on(iconic_woothumbs.vars.loading_variation_trigger, function( event ){

                product_object.all_images_wrap.addClass( iconic_woothumbs.vars.loading_class );

            });

            // on show variation trigger

            product_object.all_images_wrap.on(iconic_woothumbs.vars.show_variation_trigger, function( event, variation ){

                iconic_woothumbs.load_images( product_object, variation );

            });

        },

        /**
         * Load Images for variation ID
         *
         * @param obj product_object
         * @param obj variation
         */

        load_images: function( product_object, variation ) {

            if( variation ) {

                product_object.all_images_wrap.attr('data-showing', variation.variation_id);

                if( variation.jck_additional_images ) {

                    var image_count = variation.jck_additional_images.length;

                    if( image_count > 0 ) {

                        product_object.all_images_wrap.removeClass( iconic_woothumbs.vars.reset_class );

                        iconic_woothumbs.replace_images( product_object, variation.jck_additional_images, function(){

                            product_object.all_images_wrap.trigger( 'iconic_woothumbs_images_loaded', [ variation ] );

                        });

                    }

                }

            } else {

                product_object.all_images_wrap.removeClass( iconic_woothumbs.vars.loading_class );

            }

        },

        /**
         * Replace slider images
         *
         * @param obj product_object
         * @param obj images
         */

        replace_images: function( product_object, images, callback ) {

            var temp_images = iconic_woothumbs.create_temporary_images( images, product_object );

            // once images have loaded, place them into the appropriate sliders

            temp_images.container.imagesLoaded( function() {

                // replace main images

                product_object.images.html( temp_images.images.html() );

                // reload main slider

                if( product_object.images_slider_data ) {
                    product_object.images.imagesLoaded(function(){

                        index = product_object.maintain_slide_index ? product_object.images_slider_data.getCurrentSlide() : 0;

                        product_object.images_slider_data.reloadSlider( iconic_woothumbs.images_slider_args( product_object, index ) );

                    });
                }

                // If thumbnails are enabled

                if( iconic_woothumbs.has_thumbnails( product_object ) ) {

                    // replace thumbnail images

                    product_object.thumbnails.html( temp_images.thumbnails.html() );

                    // If sliding thumbnails are enabled

                    if( iconic_woothumbs.has_sliding_thumbnails( product_object ) ) {

                        // reload thumbnail slider

                        product_object.thumbnails.imagesLoaded(function(){

                            product_object.thumbnails_slider_data.reloadSlider( iconic_woothumbs.thumbnails_slider_args( product_object ) );

                        });

                    }

                }

                // remove temp images

                temp_images.container.remove();

                // remove loading icon

                product_object.all_images_wrap.removeClass( iconic_woothumbs.vars.loading_class );

                // run a callback, if required

                if(callback !== undefined) {
                    callback();
                }

            });

        },

        /**
         * Helper: Prepare retina srcset
         *
         * @param str retina_src
         */

        prepare_retina_srcset: function( retina_src ) {

            return iconic_woothumbs.tpl.retina_img_src.replace('{{image_src}}', retina_src);

        },

        /**
         * Helper: Setup srcset so it doesn't interfere with imagesloaded
         */

        setup_srcset: function() {

            $('[data-srcset]').each(function(){

                $(this)
                    .attr('srcset', $(this).attr('data-srcset'))
                    .removeAttr('data-srcset');

            });

        },

        /**
         * Create temporary images
         *
         * @param obj images parsed JSON
         */

        create_temporary_images: function( images, product_object ) {

            // add temp images container
            $('body').append( iconic_woothumbs.tpl.temp_images_container );

            var image_count = images.length,
                temp_images = {
                    'container': $('.iconic-woothumbs-temp'),
                    'images': $('.iconic-woothumbs-temp__images'),
                    'thumbnails': $('.iconic-woothumbs-temp__thumbnails')
                };

            // loop through additional images
            $.each( images, function( index, image_data ){

                // add images to temp div

                var has_retina_single = typeof image_data.single.retina !== "undefined" ? true : false,
                    has_retina_thumb = typeof image_data.thumb.retina !== "undefined" ? true : false;

                var slide_html =
                        iconic_woothumbs.tpl.image_slide
                        .replace( /{{image_src}}/g, image_data.single[0] )
                        .replace( /{{image_src_retina}}/g, has_retina_single ? iconic_woothumbs.prepare_retina_srcset( image_data.single.retina[0] ) : "" )
                        .replace( "{{large_image_src}}", image_data.large[0] )
                        .replace( "{{large_image_width}}", image_data.large[1] )
                        .replace( "{{large_image_height}}", image_data.large[2] )
                        .replace( "{{image_width}}", image_data.single[1] )
                        .replace( "{{image_height}}", image_data.single[2] )
                        .replace( "{{alt}}", image_data.alt )
                        .replace( "{{title}}", image_data.title )
                        .replace( "{{slide_class}}", index === 0 ? iconic_woothumbs.vars.images_active_class  : "" );

                temp_images.images.append( slide_html );

                // add thumbnails to temp div if thumbnails are enabled

                if( image_count > 1 && iconic_woothumbs.has_thumbnails( product_object ) ) {

                    var thumbnail_html =
                            iconic_woothumbs.tpl.thumbnail_slide
                            .replace( /{{image_src}}/g, image_data.thumb[0] )
                            .replace( /{{image_src_retina}}/g, has_retina_thumb ? iconic_woothumbs.prepare_retina_srcset( image_data.thumb.retina[0] ) : "" )
                            .replace( "{{index}}", index )
                            .replace( "{{image_width}}", image_data.thumb[1] )
                            .replace( "{{image_height}}", image_data.thumb[2] )
                            .replace( "{{alt}}", image_data.alt )
                            .replace( "{{title}}", image_data.title )
                            .replace( "{{slide_class}}", index === 0 ? iconic_woothumbs.vars.thumbnails_active_class  : "" );

                    temp_images.thumbnails.append( thumbnail_html );

                }

            });

            // pad out the thumbnails if there is less than the
            // amount that are meant to be displayed.

            if( product_object.thumbnails_slider_data && image_count !== 1 && image_count < iconic_woothumbs_vars.settings.navigation_thumbnails_count ) {

                var empty_count = iconic_woothumbs_vars.settings.navigation_thumbnails_count - image_count;

                i = 0; while( i < empty_count ) {

                    temp_images.thumbnails.append( '<div/>' );

                    i++;

                }

            }

            return temp_images;

        },

        /**
         * Reset Images to defaults
         *
         * @param obj product_object
         */

        reset_images: function( product_object ){

            if( !product_object.all_images_wrap.hasClass( iconic_woothumbs.vars.reset_class ) && !product_object.all_images_wrap.hasClass( iconic_woothumbs.vars.loading_class ) && !iconic_woothumbs.found_variation( product_object ) ) {

                product_object.all_images_wrap.trigger( iconic_woothumbs.vars.loading_variation_trigger );

                product_object.all_images_wrap.attr('data-showing', product_object.product_id);

                // set reset class

                product_object.all_images_wrap.addClass( iconic_woothumbs.vars.reset_class );

                // replace images

                iconic_woothumbs.replace_images( product_object, product_object.default_images );

            }

        },

        /**
         * Helper: Check if final variation has been selected
         *
         * @param obj product_object
         */
        found_variation: function( product_object ){

            var variation_id = parseInt( product_object.variation_id_field.val() );

            return !isNaN(variation_id);

        },

        /**
         * Gat variation data from variation ID
         *
         * @param obj product_object
         * @param int variation_id
         */

        get_variation_data: function( product_object, variation_id ) {

            product_object.all_images_wrap.trigger( iconic_woothumbs.vars.loading_variation_trigger );

            var variation_data = false;

            // variation data available

            if( product_object.variations ) {

                $.each(product_object.variations, function( index, variation ){

                    if( variation.variation_id === variation_id ) {
                        variation_data = variation;
                    }

                });

                product_object.all_images_wrap.trigger( iconic_woothumbs.vars.show_variation_trigger, [ variation_data ] );

            // variation data not available, look it up via ajax

            } else {

                $.ajax({
                    type:        "GET",
                    url:         iconic_woothumbs_vars.ajaxurl,
                    cache:       false,
                    dataType:    "jsonp",
                    crossDomain: true,
                    data: {
                        'action': 'iconic_woothumbs_get_variation',
                        'variation_id': variation_id,
                        'product_id': product_object.product_id
                    },
                    success: function( response ) {

                        if( response.success ) {
                            if( response.variation ) {

                                variation_data = response.variation;

                                product_object.all_images_wrap.trigger( iconic_woothumbs.vars.show_variation_trigger, [ variation_data ] );

                            }
                        }

                    }
                });

            }

        },


        /**
         * Trigger Photoswipe
         *
         * @param bool last_slide
         */
        trigger_photoswipe: function( product_object, last_slide ) {

            var pswpElement = $('.iconic-woothumbs-pswp')[0];

            // build items array
            var items = iconic_woothumbs.get_gallery_items( product_object );

            // define options (if needed)
            var options = {
                // optionName: 'option value'
                // for example:
                index: typeof last_slide === "undefined" ? items.index : items.items.length-1, // start at first slide
                shareEl: false,
                closeOnScroll: false,
                history: false
            };

            // Initializes and opens PhotoSwipe
            iconic_woothumbs.els.gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items.items, options);
            iconic_woothumbs.els.gallery.init();

            iconic_woothumbs.els.gallery.listen('close', function() {

                iconic_woothumbs.stop_video();

            });

        },

        /**
         * Pause iframe video
         */
        stop_video: function() {

            var $iframe = $('.iconic-woothumbs-video-wrapper iframe');

            if( $iframe.length > 0 ) {

                var iframe_src = $iframe.attr('src');

                $iframe.attr('src', '');
                $iframe.attr('src', iframe_src);

            }

        },

        /**
         * Setup fullscreen
         *
         * @param obj product_object
         */
        setup_fullscreen: function( product_object ) {

            if( !iconic_woothumbs.vars.is_fullscreen_enabled ) { return; }

            product_object.images_wrap.on('click', iconic_woothumbs.vars.fullscreen_trigger, function(){

                iconic_woothumbs.trigger_photoswipe( product_object );

            });

        },

        /**
         * Setup video
         */
        setup_video: function( product_object ) {

            product_object.images_wrap.on('click', iconic_woothumbs.vars.play_trigger, function(){

                iconic_woothumbs.trigger_photoswipe( product_object, true );

            });

        },

        /**
         * Get Gallery Items
         *
         * @param obj product_object
         * @return obj index and items
         */

        get_gallery_items: function( product_object ) {

            var $slides = product_object.images.children().not('.bx-clone'),
                items = [],
                index = $slides.filter("."+iconic_woothumbs.vars.images_active_class).index();

            if( iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.carousel_general_infinite_loop ) && iconic_woothumbs_vars.settings.carousel_general_mode !== "fade" ) {
                index = index-1;
            }

            if( iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.fullscreen_general_enable) ) {

                if( $slides.length > 0 ) {
                    $slides.each(function( i, el ){

                        var img = $(el).find('img'),
                            large_image_src = img.attr('data-large-image'),
                            large_image_w = img.attr('data-large-image-width'),
                            large_image_h = img.attr('data-large-image-height'),
                            item = {
                                src: large_image_src,
                                w: large_image_w,
                                h: large_image_h
                            };

                        if( iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.fullscreen_general_image_title ) ) {

                            var title = img.attr('title');

                            item.title = title;

                        }

                        items.push( item );

                    });
                }

            }

            if( iconic_woothumbs.els.video_template.length > 0 ) {

                items.push({
                    html: iconic_woothumbs.els.video_template.html()
                });

            }

            return {
                index: index,
                items: items
            };

        },

        /**
         * Setup Zoom - actions that should only be run once
         *
         * @param obj product_object
         */

        setup_zoom: function( product_object ) {

            if( !iconic_woothumbs.vars.is_zoom_enabled ) { return; }

            // Disable the zoom if using a tocuh device

            product_object.all_images_wrap.on('touchmove', '.iconic-woothumbs-images__image', function(){
                iconic_woothumbs.vars.is_dragging_image_slide = true;
            });

            product_object.all_images_wrap.on('touchend', '.iconic-woothumbs-images__image', function(e){

                if( !iconic_woothumbs.vars.is_dragging_image_slide ) {
                    e.preventDefault();
                    $(this).click();
                }

            });

            product_object.all_images_wrap.on('touchstart', '.iconic-woothumbs-images__image', function(){
                iconic_woothumbs.vars.is_dragging_image_slide = false;
            });

            if( iconic_woothumbs.vars.zoom_setup ) { return; }

            // Reset zoom after resize

            $(window).on('resize-end', function(){

                var $active_img = product_object.images.find('.iconic-woothumbs-images__slide--active img');

                iconic_woothumbs.init_zoom( $active_img, product_object );

            });

            iconic_woothumbs.vars.zoom_setup = true;

        },

        /**
         * Init Hover Zoom
         *
         * @param obj image
         * @param obj product_object
         */

        init_zoom: function( $image, product_object ) {

            if( !iconic_woothumbs.vars.is_zoom_enabled ) { return; }

            var slide_image_width = $image.width(),
                large_image = $image.attr('data-large-image'),
                large_image_width = parseInt( $image.attr('data-large-image-width') );

            if( slide_image_width >= large_image_width ) { return; }

            if( product_object.imagezoom ) { product_object.imagezoom.destroy(); }

            $image.ImageZoom({
                type: iconic_woothumbs_vars.settings.zoom_general_zoom_type,
                bigImageSrc: large_image,
                zoomSize: [iconic_woothumbs_vars.settings.zoom_outside_follow_zoom_lens_width,iconic_woothumbs_vars.settings.zoom_outside_follow_zoom_lens_height],
                zoomViewerClass: ( iconic_woothumbs_vars.settings.zoom_general_zoom_type === "follow" ) ? 'shape'+iconic_woothumbs_vars.settings.zoom_follow_zoom_zoom_shape : "shapesquare",
                position: iconic_woothumbs_vars.settings.zoom_outside_zoom_zoom_position,
                preload: false,
                showDescription: false,
                onShow: function() {

                    iconic_woothumbs.add_zoom_controls( product_object );

                },
                onHide: function() {

                    $('.bx-controls--hidden').removeClass('bx-controls--hidden').show();

                }
            });

            product_object.imagezoom = $image.data('imagezoom');

        },

        /**
         * Destroy Hover Zoom
         *
         * @param obj product_object
         */

        destroy_zoom: function( product_object ) {

            var $current_zoom = product_object.images.find('.currZoom'),
                zoom = $current_zoom.data('imagezoom');

            if( zoom && typeof zoom !== "undefined" ){

                $current_zoom.removeClass('currZoom');
                zoom.destroy();

            }

            $('.zm-viewer').remove();
            $('.zm-handler').remove();

        },

        /**
         * Add Zoom Controls
         *
         * @param obj product_object
         */

        add_zoom_controls: function( product_object ) {

            var $viewer = product_object.imagezoom.$viewer;

            if( $viewer.find('.iconic-woothumbs-zoom-controls').length <= 0 && iconic_woothumbs_vars.settings.zoom_general_zoom_type === "inner" ) {

                if( iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.display_general_icons_tooltips) ) {
                    $viewer.addClass('iconic-woothumbs-tooltips-enabled');
                }

                $viewer.append('<div class="iconic-woothumbs-zoom-controls"></div>');

                var $zoom_controls = $viewer.find('.iconic-woothumbs-zoom-controls');

                if( product_object.wishlist_buttons.length > 0 ) {
                    $zoom_controls.append( product_object.wishlist_buttons.clone() );
                }

                if( iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.fullscreen_general_enable) ) {
                    $zoom_controls.append( iconic_woothumbs.tpl.fullscreen_button );

                    $viewer.on('click', iconic_woothumbs.vars.fullscreen_trigger, function(){
                        iconic_woothumbs.trigger_photoswipe( product_object );
                    });
                }

                if( product_object.has_video ) {
                    $zoom_controls.append( iconic_woothumbs.tpl.play_button );

                    $viewer.on('click', iconic_woothumbs.vars.play_trigger, function(){
                        iconic_woothumbs.trigger_photoswipe( product_object, true );
                    });
                }

                if( iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.navigation_general_controls) && product_object.images_slider_data.getSlideCount() > 1 ) {
                    $zoom_controls.append('<a class="iconic-woothumbs-zoom-prev" href="javascript: void(0);"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-prev"></i></a><a class="iconic-woothumbs-zoom-next" href="javascript: void(0);"><i class="iconic-woothumbs-icon iconic-woothumbs-icon-next"></i></a>');

                    // Arrow nav
                    $viewer.on('click', '.iconic-woothumbs-zoom-prev', function(){
                        product_object.images_slider_data.goToPrevSlide();
                    });

                    $viewer.on('click', '.iconic-woothumbs-zoom-next', function(){
                        product_object.images_slider_data.goToNextSlide();
                    });

                }

                if( iconic_woothumbs.is_true( iconic_woothumbs_vars.settings.navigation_bullets_enable ) ) {

                    var $bullets = product_object.all_images_wrap.find('.bx-pager');

                    if( $bullets.children().length > 1 ) {

                        var $bullets_clone = $bullets.clone();

                        $zoom_controls.append( $bullets_clone ).wrap( "<div class='iconic-woothumbs-zoom-bullets'></div>" );

                        // Bullet nav

                        $viewer.on('click', '.iconic-woothumbs-zoom-bullets a', function(){

                            var selected_index = parseInt($(this).attr('data-slide-index'));

                            // change main slide
                            product_object.images_slider_data.goToSlide( selected_index );

                            return false;

                        });

                    }

                }

                iconic_woothumbs.setup_tooltips();

            }

        },

        /**
         * Setup Yith Wishlist
         */

        setup_yith_wishlist: function() {

            $('body').on('added_to_wishlist', function(){
                $('.iconic-woothumbs-wishlist-buttons').addClass( iconic_woothumbs.vars.wishlist_added_class );
            });

        },

        /**
         * Setup Tooltips
         */

        setup_tooltips: function() {

            if( iconic_woothumbs.is_true(iconic_woothumbs_vars.settings.display_general_icons_tooltips) ) {

                $('[data-iconic-woothumbs-tooltip]').each(function(){

                    var tooltip = $(this).attr('data-iconic-woothumbs-tooltip');

                    $(this).tooltipster({
                        content: tooltip,
                        debug: false
                    });
                });

            }

        },

    };

	$(window).load( iconic_woothumbs.on_load );
	$('body').on( 'jckqv_open', iconic_woothumbs.on_load );
	$(window).resize( iconic_woothumbs.on_resize );

}(jQuery, document));
/*!
 * imagesLoaded PACKAGED v4.1.0
 * JavaScript is all like "You images are done yet or what?"
 * MIT License
 */

!function(t,e){"function"==typeof define&&define.amd?define("ev-emitter/ev-emitter",e):"object"==typeof module&&module.exports?module.exports=e():t.EvEmitter=e()}(this,function(){function t(){}var e=t.prototype;return e.on=function(t,e){if(t&&e){var i=this._events=this._events||{},n=i[t]=i[t]||[];return-1==n.indexOf(e)&&n.push(e),this}},e.once=function(t,e){if(t&&e){this.on(t,e);var i=this._onceEvents=this._onceEvents||{},n=i[t]=i[t]||[];return n[e]=!0,this}},e.off=function(t,e){var i=this._events&&this._events[t];if(i&&i.length){var n=i.indexOf(e);return-1!=n&&i.splice(n,1),this}},e.emitEvent=function(t,e){var i=this._events&&this._events[t];if(i&&i.length){var n=0,o=i[n];e=e||[];for(var r=this._onceEvents&&this._onceEvents[t];o;){var s=r&&r[o];s&&(this.off(t,o),delete r[o]),o.apply(this,e),n+=s?0:1,o=i[n]}return this}},t}),function(t,e){"use strict";"function"==typeof define&&define.amd?define(["ev-emitter/ev-emitter"],function(i){return e(t,i)}):"object"==typeof module&&module.exports?module.exports=e(t,require("ev-emitter")):t.imagesLoaded=e(t,t.EvEmitter)}(window,function(t,e){function i(t,e){for(var i in e)t[i]=e[i];return t}function n(t){var e=[];if(Array.isArray(t))e=t;else if("number"==typeof t.length)for(var i=0;i<t.length;i++)e.push(t[i]);else e.push(t);return e}function o(t,e,r){return this instanceof o?("string"==typeof t&&(t=document.querySelectorAll(t)),this.elements=n(t),this.options=i({},this.options),"function"==typeof e?r=e:i(this.options,e),r&&this.on("always",r),this.getImages(),h&&(this.jqDeferred=new h.Deferred),void setTimeout(function(){this.check()}.bind(this))):new o(t,e,r)}function r(t){this.img=t}function s(t,e){this.url=t,this.element=e,this.img=new Image}var h=t.jQuery,a=t.console;o.prototype=Object.create(e.prototype),o.prototype.options={},o.prototype.getImages=function(){this.images=[],this.elements.forEach(this.addElementImages,this)},o.prototype.addElementImages=function(t){"IMG"==t.nodeName&&this.addImage(t),this.options.background===!0&&this.addElementBackgroundImages(t);var e=t.nodeType;if(e&&d[e]){for(var i=t.querySelectorAll("img"),n=0;n<i.length;n++){var o=i[n];this.addImage(o)}if("string"==typeof this.options.background){var r=t.querySelectorAll(this.options.background);for(n=0;n<r.length;n++){var s=r[n];this.addElementBackgroundImages(s)}}}};var d={1:!0,9:!0,11:!0};return o.prototype.addElementBackgroundImages=function(t){var e=getComputedStyle(t);if(e)for(var i=/url\((['"])?(.*?)\1\)/gi,n=i.exec(e.backgroundImage);null!==n;){var o=n&&n[2];o&&this.addBackground(o,t),n=i.exec(e.backgroundImage)}},o.prototype.addImage=function(t){var e=new r(t);this.images.push(e)},o.prototype.addBackground=function(t,e){var i=new s(t,e);this.images.push(i)},o.prototype.check=function(){function t(t,i,n){setTimeout(function(){e.progress(t,i,n)})}var e=this;return this.progressedCount=0,this.hasAnyBroken=!1,this.images.length?void this.images.forEach(function(e){e.once("progress",t),e.check()}):void this.complete()},o.prototype.progress=function(t,e,i){this.progressedCount++,this.hasAnyBroken=this.hasAnyBroken||!t.isLoaded,this.emitEvent("progress",[this,t,e]),this.jqDeferred&&this.jqDeferred.notify&&this.jqDeferred.notify(this,t),this.progressedCount==this.images.length&&this.complete(),this.options.debug&&a&&a.log("progress: "+i,t,e)},o.prototype.complete=function(){var t=this.hasAnyBroken?"fail":"done";if(this.isComplete=!0,this.emitEvent(t,[this]),this.emitEvent("always",[this]),this.jqDeferred){var e=this.hasAnyBroken?"reject":"resolve";this.jqDeferred[e](this)}},r.prototype=Object.create(e.prototype),r.prototype.check=function(){var t=this.getIsImageComplete();return t?void this.confirm(0!==this.img.naturalWidth,"naturalWidth"):(this.proxyImage=new Image,this.proxyImage.addEventListener("load",this),this.proxyImage.addEventListener("error",this),this.img.addEventListener("load",this),this.img.addEventListener("error",this),void(this.proxyImage.src=this.img.src))},r.prototype.getIsImageComplete=function(){return this.img.complete&&void 0!==this.img.naturalWidth},r.prototype.confirm=function(t,e){this.isLoaded=t,this.emitEvent("progress",[this,this.img,e])},r.prototype.handleEvent=function(t){var e="on"+t.type;this[e]&&this[e](t)},r.prototype.onload=function(){this.confirm(!0,"onload"),this.unbindEvents()},r.prototype.onerror=function(){this.confirm(!1,"onerror"),this.unbindEvents()},r.prototype.unbindEvents=function(){this.proxyImage.removeEventListener("load",this),this.proxyImage.removeEventListener("error",this),this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},s.prototype=Object.create(r.prototype),s.prototype.check=function(){this.img.addEventListener("load",this),this.img.addEventListener("error",this),this.img.src=this.url;var t=this.getIsImageComplete();t&&(this.confirm(0!==this.img.naturalWidth,"naturalWidth"),this.unbindEvents())},s.prototype.unbindEvents=function(){this.img.removeEventListener("load",this),this.img.removeEventListener("error",this)},s.prototype.confirm=function(t,e){this.isLoaded=t,this.emitEvent("progress",[this,this.element,e])},o.makeJQueryPlugin=function(e){e=e||t.jQuery,e&&(h=e,h.fn.imagesLoaded=function(t,e){var i=new o(this,t,e);return i.jqDeferred.promise(h(this))})},o.makeJQueryPlugin(),o});
/**
 * bxSlider v4.2.5
 * Copyright 2013-2015 Steven Wanderski
 * Written while drinking Belgian ales and listening to jazz

 * Licensed under MIT (http://opensource.org/licenses/MIT)
 */

!function(a){var b={mode:"horizontal",slideSelector:"",infiniteLoop:!0,hideControlOnEnd:!1,speed:500,easing:null,slideMargin:0,startSlide:0,randomStart:!1,captions:!1,ticker:!1,tickerHover:!1,adaptiveHeight:!1,adaptiveHeightSpeed:500,video:!1,useCSS:!0,preloadImages:"visible",responsive:!0,slideZIndex:50,wrapperClass:"bx-wrapper",touchEnabled:!0,swipeThreshold:50,oneToOneTouch:!0,preventDefaultSwipeX:!0,preventDefaultSwipeY:!1,ariaLive:!0,ariaHidden:!0,keyboardEnabled:!1,pager:!0,pagerType:"full",pagerShortSeparator:" / ",pagerSelector:null,buildPager:null,pagerCustom:null,controls:!0,nextText:"Next",prevText:"Prev",nextSelector:null,prevSelector:null,autoControls:!1,startText:"Start",stopText:"Stop",autoControlsCombine:!1,autoControlsSelector:null,auto:!1,pause:4e3,autoStart:!0,autoDirection:"next",stopAutoOnClick:!1,autoHover:!1,autoDelay:0,autoSlideForOnePage:!1,minSlides:1,maxSlides:1,moveSlides:0,slideWidth:0,shrinkItems:!1,onSliderLoad:function(){return!0},onSlideBefore:function(){return!0},onSlideAfter:function(){return!0},onSlideNext:function(){return!0},onSlidePrev:function(){return!0},onSliderResize:function(){return!0}};a.fn.bxSlider=function(c){if(0===this.length)return this;if(this.length>1)return this.each(function(){a(this).bxSlider(c)}),this;var d={},e=this,f=a(window).width(),g=a(window).height();if(!a(e).data("bxSlider")){var h=function(){a(e).data("bxSlider")||(d.settings=a.extend({},b,c),d.settings.slideWidth=parseInt(d.settings.slideWidth),d.children=e.children(d.settings.slideSelector),d.children.length<d.settings.minSlides&&(d.settings.minSlides=d.children.length),d.children.length<d.settings.maxSlides&&(d.settings.maxSlides=d.children.length),d.settings.randomStart&&(d.settings.startSlide=Math.floor(Math.random()*d.children.length)),d.active={index:d.settings.startSlide},d.carousel=d.settings.minSlides>1||d.settings.maxSlides>1?!0:!1,d.carousel&&(d.settings.preloadImages="all"),d.minThreshold=d.settings.minSlides*d.settings.slideWidth+(d.settings.minSlides-1)*d.settings.slideMargin,d.maxThreshold=d.settings.maxSlides*d.settings.slideWidth+(d.settings.maxSlides-1)*d.settings.slideMargin,d.working=!1,d.controls={},d.interval=null,d.animProp="vertical"===d.settings.mode?"top":"left",d.usingCSS=d.settings.useCSS&&"fade"!==d.settings.mode&&function(){for(var a=document.createElement("div"),b=["WebkitPerspective","MozPerspective","OPerspective","msPerspective"],c=0;c<b.length;c++)if(void 0!==a.style[b[c]])return d.cssPrefix=b[c].replace("Perspective","").toLowerCase(),d.animProp="-"+d.cssPrefix+"-transform",!0;return!1}(),"vertical"===d.settings.mode&&(d.settings.maxSlides=d.settings.minSlides),e.data("origStyle",e.attr("style")),e.children(d.settings.slideSelector).each(function(){a(this).data("origStyle",a(this).attr("style"))}),j())},j=function(){var b=d.children.eq(d.settings.startSlide);e.wrap('<div class="'+d.settings.wrapperClass+'"><div class="bx-viewport"></div></div>'),d.viewport=e.parent(),d.settings.ariaLive&&!d.settings.ticker&&d.viewport.attr("aria-live","polite"),d.loader=a('<div class="bx-loading" />'),d.viewport.prepend(d.loader),e.css({width:"horizontal"===d.settings.mode?1e3*d.children.length+215+"%":"auto",position:"relative"}),d.usingCSS&&d.settings.easing?e.css("-"+d.cssPrefix+"-transition-timing-function",d.settings.easing):d.settings.easing||(d.settings.easing="swing"),d.viewport.css({width:"100%",overflow:"hidden",position:"relative"}),d.viewport.parent().css({maxWidth:n()}),d.settings.pager||d.settings.controls||d.viewport.parent().css({margin:"0 auto 0px"}),d.children.css({"float":"horizontal"===d.settings.mode?"left":"none",listStyle:"none",position:"relative"}),d.children.css("width",o()),"horizontal"===d.settings.mode&&d.settings.slideMargin>0&&d.children.css("marginRight",d.settings.slideMargin),"vertical"===d.settings.mode&&d.settings.slideMargin>0&&d.children.css("marginBottom",d.settings.slideMargin),"fade"===d.settings.mode&&(d.children.css({position:"absolute",zIndex:0,display:"none"}),d.children.eq(d.settings.startSlide).css({zIndex:d.settings.slideZIndex,display:"block"})),d.controls.el=a('<div class="bx-controls" />'),d.settings.captions&&y(),d.active.last=d.settings.startSlide===q()-1,d.settings.video&&e.fitVids(),("all"===d.settings.preloadImages||d.settings.ticker)&&(b=d.children),d.settings.ticker?d.settings.pager=!1:(d.settings.controls&&w(),d.settings.auto&&d.settings.autoControls&&x(),d.settings.pager&&v(),(d.settings.controls||d.settings.autoControls||d.settings.pager)&&d.viewport.after(d.controls.el)),k(b,l)},k=function(b,c){var d=b.find('img:not([src=""]), iframe').length,e=0;return 0===d?void c():void b.find('img:not([src=""]), iframe').each(function(){a(this).one("load error",function(){++e===d&&c()}).each(function(){this.complete&&a(this).load()})})},l=function(){if(d.settings.infiniteLoop&&"fade"!==d.settings.mode&&!d.settings.ticker){var b="vertical"===d.settings.mode?d.settings.minSlides:d.settings.maxSlides,c=d.children.slice(0,b).clone(!0).addClass("bx-clone"),f=d.children.slice(-b).clone(!0).addClass("bx-clone");d.settings.ariaHidden&&(c.attr("aria-hidden",!0),f.attr("aria-hidden",!0)),e.append(c).prepend(f)}d.loader.remove(),s(),"vertical"===d.settings.mode&&(d.settings.adaptiveHeight=!0),d.viewport.height(m()),e.redrawSlider(),d.settings.onSliderLoad.call(e,d.active.index),d.initialized=!0,d.settings.responsive&&a(window).bind("resize",S),d.settings.auto&&d.settings.autoStart&&(q()>1||d.settings.autoSlideForOnePage)&&I(),d.settings.ticker&&J(),d.settings.pager&&E(d.settings.startSlide),d.settings.controls&&H(),d.settings.touchEnabled&&!d.settings.ticker&&N(),d.settings.keyboardEnabled&&!d.settings.ticker&&a(document).keydown(M)},m=function(){var b=0,c=a();if("vertical"===d.settings.mode||d.settings.adaptiveHeight)if(d.carousel){var e=1===d.settings.moveSlides?d.active.index:d.active.index*r();for(c=d.children.eq(e),i=1;i<=d.settings.maxSlides-1;i++)c=e+i>=d.children.length?c.add(d.children.eq(i-1)):c.add(d.children.eq(e+i))}else c=d.children.eq(d.active.index);else c=d.children;return"vertical"===d.settings.mode?(c.each(function(c){b+=a(this).outerHeight()}),d.settings.slideMargin>0&&(b+=d.settings.slideMargin*(d.settings.minSlides-1))):b=Math.max.apply(Math,c.map(function(){return a(this).outerHeight(!1)}).get()),"border-box"===d.viewport.css("box-sizing")?b+=parseFloat(d.viewport.css("padding-top"))+parseFloat(d.viewport.css("padding-bottom"))+parseFloat(d.viewport.css("border-top-width"))+parseFloat(d.viewport.css("border-bottom-width")):"padding-box"===d.viewport.css("box-sizing")&&(b+=parseFloat(d.viewport.css("padding-top"))+parseFloat(d.viewport.css("padding-bottom"))),b},n=function(){var a="100%";return d.settings.slideWidth>0&&(a="horizontal"===d.settings.mode?d.settings.maxSlides*d.settings.slideWidth+(d.settings.maxSlides-1)*d.settings.slideMargin:d.settings.slideWidth),a},o=function(){var a=d.settings.slideWidth,b=d.viewport.width();if(0===d.settings.slideWidth||d.settings.slideWidth>b&&!d.carousel||"vertical"===d.settings.mode)a=b;else if(d.settings.maxSlides>1&&"horizontal"===d.settings.mode){if(b>d.maxThreshold)return a;b<d.minThreshold?a=(b-d.settings.slideMargin*(d.settings.minSlides-1))/d.settings.minSlides:d.settings.shrinkItems&&(a=Math.floor((b+d.settings.slideMargin)/Math.ceil((b+d.settings.slideMargin)/(a+d.settings.slideMargin))-d.settings.slideMargin))}return a},p=function(){var a=1,b=null;return"horizontal"===d.settings.mode&&d.settings.slideWidth>0?d.viewport.width()<d.minThreshold?a=d.settings.minSlides:d.viewport.width()>d.maxThreshold?a=d.settings.maxSlides:(b=d.children.first().width()+d.settings.slideMargin,a=Math.floor((d.viewport.width()+d.settings.slideMargin)/b)):"vertical"===d.settings.mode&&(a=d.settings.minSlides),a},q=function(){var a=0,b=0,c=0;if(d.settings.moveSlides>0)if(d.settings.infiniteLoop)a=Math.ceil(d.children.length/r());else for(;b<d.children.length;)++a,b=c+p(),c+=d.settings.moveSlides<=p()?d.settings.moveSlides:p();else a=Math.ceil(d.children.length/p());return a},r=function(){return d.settings.moveSlides>0&&d.settings.moveSlides<=p()?d.settings.moveSlides:p()},s=function(){var a,b,c;d.children.length>d.settings.maxSlides&&d.active.last&&!d.settings.infiniteLoop?"horizontal"===d.settings.mode?(b=d.children.last(),a=b.position(),t(-(a.left-(d.viewport.width()-b.outerWidth())),"reset",0)):"vertical"===d.settings.mode&&(c=d.children.length-d.settings.minSlides,a=d.children.eq(c).position(),t(-a.top,"reset",0)):(a=d.children.eq(d.active.index*r()).position(),d.active.index===q()-1&&(d.active.last=!0),void 0!==a&&("horizontal"===d.settings.mode?t(-a.left,"reset",0):"vertical"===d.settings.mode&&t(-a.top,"reset",0)))},t=function(b,c,f,g){var h,i;d.usingCSS?(i="vertical"===d.settings.mode?"translate3d(0, "+b+"px, 0)":"translate3d("+b+"px, 0, 0)",e.css("-"+d.cssPrefix+"-transition-duration",f/1e3+"s"),"slide"===c?(e.css(d.animProp,i),0!==f?e.bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd",function(b){a(b.target).is(e)&&(e.unbind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd"),F())}):F()):"reset"===c?e.css(d.animProp,i):"ticker"===c&&(e.css("-"+d.cssPrefix+"-transition-timing-function","linear"),e.css(d.animProp,i),0!==f?e.bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd",function(b){a(b.target).is(e)&&(e.unbind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd"),t(g.resetValue,"reset",0),K())}):(t(g.resetValue,"reset",0),K()))):(h={},h[d.animProp]=b,"slide"===c?e.animate(h,f,d.settings.easing,function(){F()}):"reset"===c?e.css(d.animProp,b):"ticker"===c&&e.animate(h,f,"linear",function(){t(g.resetValue,"reset",0),K()}))},u=function(){for(var b="",c="",e=q(),f=0;e>f;f++)c="",d.settings.buildPager&&a.isFunction(d.settings.buildPager)||d.settings.pagerCustom?(c=d.settings.buildPager(f),d.pagerEl.addClass("bx-custom-pager")):(c=f+1,d.pagerEl.addClass("bx-default-pager")),b+='<div class="bx-pager-item"><a href="" data-slide-index="'+f+'" class="bx-pager-link">'+c+"</a></div>";d.pagerEl.html(b)},v=function(){d.settings.pagerCustom?d.pagerEl=a(d.settings.pagerCustom):(d.pagerEl=a('<div class="bx-pager" />'),d.settings.pagerSelector?a(d.settings.pagerSelector).html(d.pagerEl):d.controls.el.addClass("bx-has-pager").append(d.pagerEl),u()),d.pagerEl.on("click touchend","a",D)},w=function(){d.controls.next=a('<a class="bx-next" href="">'+d.settings.nextText+"</a>"),d.controls.prev=a('<a class="bx-prev" href="">'+d.settings.prevText+"</a>"),d.controls.next.bind("click touchend",z),d.controls.prev.bind("click touchend",A),d.settings.nextSelector&&a(d.settings.nextSelector).append(d.controls.next),d.settings.prevSelector&&a(d.settings.prevSelector).append(d.controls.prev),d.settings.nextSelector||d.settings.prevSelector||(d.controls.directionEl=a('<div class="bx-controls-direction" />'),d.controls.directionEl.append(d.controls.prev).append(d.controls.next),d.controls.el.addClass("bx-has-controls-direction").append(d.controls.directionEl))},x=function(){d.controls.start=a('<div class="bx-controls-auto-item"><a class="bx-start" href="">'+d.settings.startText+"</a></div>"),d.controls.stop=a('<div class="bx-controls-auto-item"><a class="bx-stop" href="">'+d.settings.stopText+"</a></div>"),d.controls.autoEl=a('<div class="bx-controls-auto" />'),d.controls.autoEl.on("click",".bx-start",B),d.controls.autoEl.on("click",".bx-stop",C),d.settings.autoControlsCombine?d.controls.autoEl.append(d.controls.start):d.controls.autoEl.append(d.controls.start).append(d.controls.stop),d.settings.autoControlsSelector?a(d.settings.autoControlsSelector).html(d.controls.autoEl):d.controls.el.addClass("bx-has-controls-auto").append(d.controls.autoEl),G(d.settings.autoStart?"stop":"start")},y=function(){d.children.each(function(b){var c=a(this).find("img:first").attr("title");void 0!==c&&(""+c).length&&a(this).append('<div class="bx-caption"><span>'+c+"</span></div>")})},z=function(a){a.preventDefault(),d.controls.el.hasClass("disabled")||(d.settings.auto&&d.settings.stopAutoOnClick&&e.stopAuto(),e.goToNextSlide())},A=function(a){a.preventDefault(),d.controls.el.hasClass("disabled")||(d.settings.auto&&d.settings.stopAutoOnClick&&e.stopAuto(),e.goToPrevSlide())},B=function(a){e.startAuto(),a.preventDefault()},C=function(a){e.stopAuto(),a.preventDefault()},D=function(b){var c,f;b.preventDefault(),d.controls.el.hasClass("disabled")||(d.settings.auto&&d.settings.stopAutoOnClick&&e.stopAuto(),c=a(b.currentTarget),void 0!==c.attr("data-slide-index")&&(f=parseInt(c.attr("data-slide-index")),f!==d.active.index&&e.goToSlide(f)))},E=function(b){var c=d.children.length;return"short"===d.settings.pagerType?(d.settings.maxSlides>1&&(c=Math.ceil(d.children.length/d.settings.maxSlides)),void d.pagerEl.html(b+1+d.settings.pagerShortSeparator+c)):(d.pagerEl.find("a").removeClass("active"),void d.pagerEl.each(function(c,d){a(d).find("a").eq(b).addClass("active")}))},F=function(){if(d.settings.infiniteLoop){var a="";0===d.active.index?a=d.children.eq(0).position():d.active.index===q()-1&&d.carousel?a=d.children.eq((q()-1)*r()).position():d.active.index===d.children.length-1&&(a=d.children.eq(d.children.length-1).position()),a&&("horizontal"===d.settings.mode?t(-a.left,"reset",0):"vertical"===d.settings.mode&&t(-a.top,"reset",0))}d.working=!1,d.settings.onSlideAfter.call(e,d.children.eq(d.active.index),d.oldIndex,d.active.index)},G=function(a){d.settings.autoControlsCombine?d.controls.autoEl.html(d.controls[a]):(d.controls.autoEl.find("a").removeClass("active"),d.controls.autoEl.find("a:not(.bx-"+a+")").addClass("active"))},H=function(){1===q()?(d.controls.prev.addClass("disabled"),d.controls.next.addClass("disabled")):!d.settings.infiniteLoop&&d.settings.hideControlOnEnd&&(0===d.active.index?(d.controls.prev.addClass("disabled"),d.controls.next.removeClass("disabled")):d.active.index===q()-1?(d.controls.next.addClass("disabled"),d.controls.prev.removeClass("disabled")):(d.controls.prev.removeClass("disabled"),d.controls.next.removeClass("disabled")))},I=function(){if(d.settings.autoDelay>0){setTimeout(e.startAuto,d.settings.autoDelay)}else e.startAuto(),a(window).focus(function(){e.startAuto()}).blur(function(){e.stopAuto()});d.settings.autoHover&&e.hover(function(){d.interval&&(e.stopAuto(!0),d.autoPaused=!0)},function(){d.autoPaused&&(e.startAuto(!0),d.autoPaused=null)})},J=function(){var b,c,f,g,h,i,j,k,l=0;"next"===d.settings.autoDirection?e.append(d.children.clone().addClass("bx-clone")):(e.prepend(d.children.clone().addClass("bx-clone")),b=d.children.first().position(),l="horizontal"===d.settings.mode?-b.left:-b.top),t(l,"reset",0),d.settings.pager=!1,d.settings.controls=!1,d.settings.autoControls=!1,d.settings.tickerHover&&(d.usingCSS?(g="horizontal"===d.settings.mode?4:5,d.viewport.hover(function(){c=e.css("-"+d.cssPrefix+"-transform"),f=parseFloat(c.split(",")[g]),t(f,"reset",0)},function(){k=0,d.children.each(function(b){k+="horizontal"===d.settings.mode?a(this).outerWidth(!0):a(this).outerHeight(!0)}),h=d.settings.speed/k,i="horizontal"===d.settings.mode?"left":"top",j=h*(k-Math.abs(parseInt(f))),K(j)})):d.viewport.hover(function(){e.stop()},function(){k=0,d.children.each(function(b){k+="horizontal"===d.settings.mode?a(this).outerWidth(!0):a(this).outerHeight(!0)}),h=d.settings.speed/k,i="horizontal"===d.settings.mode?"left":"top",j=h*(k-Math.abs(parseInt(e.css(i)))),K(j)})),K()},K=function(a){var b,c,f,g=a?a:d.settings.speed,h={left:0,top:0},i={left:0,top:0};"next"===d.settings.autoDirection?h=e.find(".bx-clone").first().position():i=d.children.first().position(),b="horizontal"===d.settings.mode?-h.left:-h.top,c="horizontal"===d.settings.mode?-i.left:-i.top,f={resetValue:c},t(b,"ticker",g,f)},L=function(b){var c=a(window),d={top:c.scrollTop(),left:c.scrollLeft()},e=b.offset();return d.right=d.left+c.width(),d.bottom=d.top+c.height(),e.right=e.left+b.outerWidth(),e.bottom=e.top+b.outerHeight(),!(d.right<e.left||d.left>e.right||d.bottom<e.top||d.top>e.bottom)},M=function(a){var b=document.activeElement.tagName.toLowerCase(),c="input|textarea",d=new RegExp(b,["i"]),f=d.exec(c);if(null==f&&L(e)){if(39===a.keyCode)return z(a),!1;if(37===a.keyCode)return A(a),!1}},N=function(){d.touch={start:{x:0,y:0},end:{x:0,y:0}},d.viewport.bind("touchstart MSPointerDown pointerdown",O),d.viewport.on("click",".bxslider a",function(a){d.viewport.hasClass("click-disabled")&&(a.preventDefault(),d.viewport.removeClass("click-disabled"))})},O=function(a){if(d.controls.el.addClass("disabled"),d.working)a.preventDefault(),d.controls.el.removeClass("disabled");else{d.touch.originalPos=e.position();var b=a.originalEvent,c="undefined"!=typeof b.changedTouches?b.changedTouches:[b];d.touch.start.x=c[0].pageX,d.touch.start.y=c[0].pageY,d.viewport.get(0).setPointerCapture&&(d.pointerId=b.pointerId,d.viewport.get(0).setPointerCapture(d.pointerId)),d.viewport.bind("touchmove MSPointerMove pointermove",Q),d.viewport.bind("touchend MSPointerUp pointerup",R),d.viewport.bind("MSPointerCancel pointercancel",P)}},P=function(a){t(d.touch.originalPos.left,"reset",0),d.controls.el.removeClass("disabled"),d.viewport.unbind("MSPointerCancel pointercancel",P),d.viewport.unbind("touchmove MSPointerMove pointermove",Q),d.viewport.unbind("touchend MSPointerUp pointerup",R),d.viewport.get(0).releasePointerCapture&&d.viewport.get(0).releasePointerCapture(d.pointerId)},Q=function(a){var b=a.originalEvent,c="undefined"!=typeof b.changedTouches?b.changedTouches:[b],e=Math.abs(c[0].pageX-d.touch.start.x),f=Math.abs(c[0].pageY-d.touch.start.y),g=0,h=0;3*e>f&&d.settings.preventDefaultSwipeX?a.preventDefault():3*f>e&&d.settings.preventDefaultSwipeY&&a.preventDefault(),"fade"!==d.settings.mode&&d.settings.oneToOneTouch&&("horizontal"===d.settings.mode?(h=c[0].pageX-d.touch.start.x,g=d.touch.originalPos.left+h):(h=c[0].pageY-d.touch.start.y,g=d.touch.originalPos.top+h),t(g,"reset",0))},R=function(a){d.viewport.unbind("touchmove MSPointerMove pointermove",Q),d.controls.el.removeClass("disabled");var b=a.originalEvent,c="undefined"!=typeof b.changedTouches?b.changedTouches:[b],f=0,g=0;d.touch.end.x=c[0].pageX,d.touch.end.y=c[0].pageY,"fade"===d.settings.mode?(g=Math.abs(d.touch.start.x-d.touch.end.x),g>=d.settings.swipeThreshold&&(d.touch.start.x>d.touch.end.x?e.goToNextSlide():e.goToPrevSlide(),e.stopAuto())):("horizontal"===d.settings.mode?(g=d.touch.end.x-d.touch.start.x,f=d.touch.originalPos.left):(g=d.touch.end.y-d.touch.start.y,f=d.touch.originalPos.top),!d.settings.infiniteLoop&&(0===d.active.index&&g>0||d.active.last&&0>g)?t(f,"reset",200):Math.abs(g)>=d.settings.swipeThreshold?(0>g?e.goToNextSlide():e.goToPrevSlide(),e.stopAuto()):t(f,"reset",200)),d.viewport.unbind("touchend MSPointerUp pointerup",R),d.viewport.get(0).releasePointerCapture&&d.viewport.get(0).releasePointerCapture(d.pointerId)},S=function(b){if(d.initialized)if(d.working)window.setTimeout(S,10);else{var c=a(window).width(),h=a(window).height();(f!==c||g!==h)&&(f=c,g=h,e.redrawSlider(),d.settings.onSliderResize.call(e,d.active.index))}},T=function(a){var b=p();d.settings.ariaHidden&&!d.settings.ticker&&(d.children.attr("aria-hidden","true"),d.children.slice(a,a+b).attr("aria-hidden","false"))},U=function(a){return 0>a?d.settings.infiniteLoop?q()-1:d.active.index:a>=q()?d.settings.infiniteLoop?0:d.active.index:a};return e.goToSlide=function(b,c){var f,g,h,i,j=!0,k=0,l={left:0,top:0},n=null;if(d.oldIndex=d.active.index,d.active.index=U(b),!d.working&&d.active.index!==d.oldIndex){if(d.working=!0,j=d.settings.onSlideBefore.call(e,d.children.eq(d.active.index),d.oldIndex,d.active.index),"undefined"!=typeof j&&!j)return d.active.index=d.oldIndex,void(d.working=!1);"next"===c?d.settings.onSlideNext.call(e,d.children.eq(d.active.index),d.oldIndex,d.active.index)||(j=!1):"prev"===c&&(d.settings.onSlidePrev.call(e,d.children.eq(d.active.index),d.oldIndex,d.active.index)||(j=!1)),d.active.last=d.active.index>=q()-1,(d.settings.pager||d.settings.pagerCustom)&&E(d.active.index),d.settings.controls&&H(),"fade"===d.settings.mode?(d.settings.adaptiveHeight&&d.viewport.height()!==m()&&d.viewport.animate({height:m()},d.settings.adaptiveHeightSpeed),d.children.filter(":visible").fadeOut(d.settings.speed).css({zIndex:0}),d.children.eq(d.active.index).css("zIndex",d.settings.slideZIndex+1).fadeIn(d.settings.speed,function(){a(this).css("zIndex",d.settings.slideZIndex),F()})):(d.settings.adaptiveHeight&&d.viewport.height()!==m()&&d.viewport.animate({height:m()},d.settings.adaptiveHeightSpeed),!d.settings.infiniteLoop&&d.carousel&&d.active.last?"horizontal"===d.settings.mode?(n=d.children.eq(d.children.length-1),l=n.position(),k=d.viewport.width()-n.outerWidth()):(f=d.children.length-d.settings.minSlides,l=d.children.eq(f).position()):d.carousel&&d.active.last&&"prev"===c?(g=1===d.settings.moveSlides?d.settings.maxSlides-r():(q()-1)*r()-(d.children.length-d.settings.maxSlides),n=e.children(".bx-clone").eq(g),l=n.position()):"next"===c&&0===d.active.index?(l=e.find("> .bx-clone").eq(d.settings.maxSlides).position(),d.active.last=!1):b>=0&&(i=b*parseInt(r()),l=d.children.eq(i).position()),"undefined"!=typeof l?(h="horizontal"===d.settings.mode?-(l.left-k):-l.top,t(h,"slide",d.settings.speed)):d.working=!1),d.settings.ariaHidden&&T(d.active.index*r())}},e.goToNextSlide=function(){if(d.settings.infiniteLoop||!d.active.last){var a=parseInt(d.active.index)+1;e.goToSlide(a,"next")}},e.goToPrevSlide=function(){if(d.settings.infiniteLoop||0!==d.active.index){var a=parseInt(d.active.index)-1;e.goToSlide(a,"prev")}},e.startAuto=function(a){d.interval||(d.interval=setInterval(function(){"next"===d.settings.autoDirection?e.goToNextSlide():e.goToPrevSlide()},d.settings.pause),d.settings.autoControls&&a!==!0&&G("stop"))},e.stopAuto=function(a){d.interval&&(clearInterval(d.interval),d.interval=null,d.settings.autoControls&&a!==!0&&G("start"))},e.getCurrentSlide=function(){return d.active.index},e.getCurrentSlideElement=function(){return d.children.eq(d.active.index)},e.getSlideElement=function(a){return d.children.eq(a)},e.getSlideCount=function(){return d.children.length},e.isWorking=function(){return d.working},e.redrawSlider=function(){d.children.add(e.find(".bx-clone")).outerWidth(o()),d.viewport.css("height",m()),d.settings.ticker||s(),d.active.last&&(d.active.index=q()-1),d.active.index>=q()&&(d.active.last=!0),d.settings.pager&&!d.settings.pagerCustom&&(u(),E(d.active.index)),d.settings.ariaHidden&&T(d.active.index*r())},e.destroySlider=function(){d.initialized&&(d.initialized=!1,a(".bx-clone",this).remove(),d.children.each(function(){void 0!==a(this).data("origStyle")?a(this).attr("style",a(this).data("origStyle")):a(this).removeAttr("style")}),void 0!==a(this).data("origStyle")?this.attr("style",a(this).data("origStyle")):a(this).removeAttr("style"),a(this).unwrap().unwrap(),d.controls.el&&d.controls.el.remove(),d.controls.next&&d.controls.next.remove(),d.controls.prev&&d.controls.prev.remove(),d.pagerEl&&d.settings.controls&&!d.settings.pagerCustom&&d.pagerEl.remove(),a(".bx-caption",this).remove(),d.controls.autoEl&&d.controls.autoEl.remove(),clearInterval(d.interval),d.settings.responsive&&a(window).unbind("resize",S),d.settings.keyboardEnabled&&a(document).unbind("keydown",M),a(this).removeData("bxSlider"))},e.reloadSlider=function(b){void 0!==b&&(c=b),e.destroySlider(),h(),a(e).data("bxSlider",this)},h(),a(e).data("bxSlider",this),this}}}(jQuery);
/*
*	ImageZoom - Responsive jQuery Image Zoom Pluin
*   version: 1.1.0
*	by hkeyjun
*   http://codecanyon.net/user/hkeyjun
*/
;(function( $, window, undefined ) {
	$.ImageZoom = function(el,options){
		var base = this;
		base.$el = $(el);

		base.$el.data('imagezoom',base);

		base.init = function(options){
			base.options = $.extend({},$.ImageZoom.defaults,options);
			base.$viewer = $('<div class="zm-viewer '+base.options.zoomViewerClass+'"></div>').appendTo('body');
			base.$handler = $('<div class="zm-handler'+base.options.zoomHandlerClass+'"></div>').appendTo('body');
			base.isBigImageReady = -1;
			base.$largeImg = null;
			base.isActive = false;
			base.$handlerArea = null;
			base.isWebkit = /chrome/.test(navigator.userAgent.toLowerCase()) || /safari/.test(navigator.userAgent.toLowerCase());
			base.evt ={x:-1,y:-1};
			base.options.bigImageSrc =base.options.bigImageSrc ==''?base.$el.attr('src'):base.options.bigImageSrc;
			if(base.options.preload) (new Image()).src=this.options.bigImageSrc;
			base.callIndex = $.ImageZoom._calltimes +1;
			base.animateTimer = null;
			$.ImageZoom._calltimes +=1;
			//log('bind:'+'mousemove.imagezoom'+base.callIndex);
			$(document).bind('mousemove.imagezoom'+base.callIndex,function(e){
				if(base.isActive)
				{
					base.moveHandler(e.pageX,e.pageY);
				}
			});
			base.$el.bind('mouseover.imagezoom',function(e){
				base.isActive = true;
				base.showViewer(e);
			});

		};
		//Move
		base.moveHandler = function(x,y){


			var offset = base.$el.offset(),width=base.$el.outerWidth(false),height=base.$el.outerHeight(false);

			if(x>=offset.left && x<=offset.left+width && y>=offset.top && y<=offset.top+height)
			{
				offset.left = offset.left +toNum(base.$el.css('borderLeftWidth'))+toNum(base.$el.css('paddingLeft'));
				offset.top = offset.top + toNum(base.$el.css('borderTopWidth'))+toNum(base.$el.css('paddingTop'));
				width = base.$el.width();
				height = base.$el.height();
				if(x>=offset.left && x<=offset.left+width && y>=offset.top && y<=offset.top+height)
				{
					base.evt = {x:x,y:y};
				if(base.options.type=="follow")
				{
					base.$viewer.css({top:y-base.$viewer.outerHeight(false)/2,left:x-base.$viewer.outerWidth(false)/2});
				}
				if(base.isBigImageReady ==1)
				{
					var bigTop,bigLeft;
					var innerTop = y - offset.top,innerLeft = x-offset.left;
					if(base.options.type=='inner')
					{
						bigTop = -base.$largeImg.height()*innerTop/height + innerTop;
						bigLeft = -base.$largeImg.width()*innerLeft/width + innerLeft;
					}
					else if(base.options.type=="standard")
					{
						var hdLeft=innerLeft-base.$handlerArea.width()/2,hdTop=innerTop - base.$handlerArea.height()/2,
						hdWidth = base.$handlerArea.width(),hdHeight = base.$handlerArea.height();
						if(hdLeft <0)
						{
							hdLeft =0;
						}
						else if(hdLeft>width - hdWidth)
						{
							hdLeft = width - hdWidth;
						}
						if(hdTop<0)
						{
							hdTop =0;
						}
						else if(hdTop > height -hdHeight)
						{
							hdTop = height - hdHeight;
						}
						bigLeft = -hdLeft / base.scale;
						bigTop = -hdTop /base.scale;


						if(base.isWebkit)
						{
							base.$handlerArea.css({opacity:.99});
							setTimeout(function(){
									base.$handlerArea.css({top:hdTop,left:hdLeft,opacity:1});
							},0);
						}
						else
						{
							base.$handlerArea.css({top:hdTop,left:hdLeft});
						}
					}
					else if(base.options.type=="follow")
					{

						bigTop = -base.$largeImg.height()/height * innerTop +base.options.zoomSize[1]/2;
						bigLeft = -base.$largeImg.width()/width *  innerLeft +base.options.zoomSize[0]/2;

						if(-bigTop > base.$largeImg.height() -base.options.zoomSize[1])
						{
							bigTop = -(base.$largeImg.height()-base.options.zoomSize[1]);
						}
						else if(bigTop>0)
						{
							bigTop =0;
						}

						if(-bigLeft >base.$largeImg.width() -base.options.zoomSize[0])
						{
							bigLeft = -(base.$largeImg.width()-base.options.zoomSize[0]);
						}
						else if(bigLeft>0)
						{
							bigLeft =0;
						}
					}

					if(base.options.smoothMove)
					{
						window.clearTimeout(base.animateTimer);
						base.smoothMove(bigLeft,bigTop);
					}
					else
					{
						base.$viewer.find('img').css({top:bigTop,left:bigLeft});
					}
				}
				}

			}
			else
			{
				base.isActive = false;
				//hidden the viewer
				base.$viewer.hide();
				base.$handler.hide();
				base.options.onHide(base);
				window.clearTimeout(base.animateTimer);
				base.animateTimer =null;
			}
		};
		//Show the zoom view
		base.showViewer = function(e){

			var top = base.$el.offset().top,borderTopWidth = toNum(base.$el.css('borderTopWidth')),paddingTop = toNum(base.$el.css('paddingTop')),left = base.$el.offset().left,borderLeftWidth =toNum(base.$el.css('borderLeftWidth')),paddingLeft = toNum(base.$el.css('paddingLeft'));
			top = top + borderTopWidth+paddingTop;
			left = left +borderLeftWidth+paddingLeft;

			var width = base.$el.width();
			var height = base.$el.height();
			//log(base.isBigImageReady);
			if(base.isBigImageReady <1)
			{
				$('div',base.$viewer).remove();
			}



			if(base.options.type=='inner')
			{
				base.$viewer.css({top:top,left:left,width:width,height:height}).show();
			}
			else if(base.options.type=='standard')
			{
				var $alignTarget = base.options.alignTo == '' ? base.$el:$('#'+base.options.alignTo);
				var viewLeft,viewTop;
				if(base.options.position == 'left')
				{
					viewLeft = $alignTarget.offset().left - base.options.zoomSize[0] - base.options.offset[0];
					viewTop = $alignTarget.offset().top + base.options.offset[1];
				}
				else if(base.options.position == 'right')
				{
					viewLeft = $alignTarget.offset().left +$alignTarget.width() + base.options.offset[0];
					viewTop = $alignTarget.offset().top + base.options.offset[1];
				}

				base.$viewer.css({top:viewTop,left:viewLeft,width:base.options.zoomSize[0],height:base.options.zoomSize[1]}).show();
				//zoom handler ajust
				if(base.$handlerArea)
				{
					//been change
					 base.scale = width / base.$largeImg.width();
					base.$handlerArea.css({width:base.$viewer.width()*base.scale,height:base.$viewer.height()*base.scale});
				}
			}
			else if(base.options.type=="follow")
			{
				base.$viewer.css({width:base.options.zoomSize[0],height:base.options.zoomSize[1],top:e.pageY-(base.options.zoomSize[1]/2),left:e.pageX-(base.options.zoomSize[0]/2)}).show();
			}


			base.$handler.css({top:top,left:left,width:width,height:height}).show();

			base.options.onShow(base);

			if(base.isBigImageReady ==-1)
			{
				base.isBigImageReady =0;

				fastImg(base.options.bigImageSrc, function () {

					if($.trim($(this).attr('src')) == $.trim(base.options.bigImageSrc))
					{
						base.$viewer.append('<img src="'+base.$el.attr('src')+'" class="zm-fast" style="position:absolute;width:'+this.width+'px;height:'+this.height+'px"\>');
						base.isBigImageReady = 1;
						base.$largeImg = $('<img src="'+base.options.bigImageSrc+'" style="position:absolute;width:'+this.width+'px;height:'+this.height+'px"\>')
						base.$viewer.append(base.$largeImg);
						if(base.options.type=='standard')
						{
							var scale = width / this.width;
							base.$handlerArea = $('<div class="zm-handlerarea" style="width:'+base.$viewer.width()*scale+'px;height:'+base.$viewer.height()*scale+'px"></div>').appendTo(base.$handler);
base.scale = scale;

						}
						//if mouse is in the img before bind mouse move event we can not get x/y from base.evt
						if(base.evt.x ==-1 && base.evt.y ==-1)
						{
							base.moveHandler(e.pageX,e.pageY);
						}
						else
						{
							base.moveHandler(base.evt.x,base.evt.y);
						}

						//add description
						if(base.options.showDescription&&base.$el.attr('alt')&&$.trim(base.$el.attr('alt'))!='')
						{
							base.$viewer.append('<div class="'+base.options.descriptionClass+'">'+base.$el.attr('alt')+'</div>');
						}
					}
					else
					{
						//log('change onload');
					}

				},function(){
					//log('load complete');

				},function(){
					//log('error');
				});
			}
					};


		//Change Img

		base.changeImage = function(elementImgSrc,bigImgSrc)
		{
			//console.log(this.$el);
			this.$el.attr('src',elementImgSrc);
			this.isBigImageReady=-1;
			this.options.bigImageSrc = typeof bigImgSrc ==='string'?bigImgSrc:elementImgSrc;
			if(base.options.preload) (new Image()).src=this.options.bigImageSrc;
			this.$viewer.hide().empty();
			this.$handler.hide().empty();
			this.$handlerArea =null;
		};

		base.changeZoomSize = function(w,h){
			base.options.zoomSize = [w,h];
		};

		base.destroy = function(){
			$(document).unbind('mousemove.imagezoom'+base.callIndex);
			this.$el.unbind('.imagezoom');
			this.$viewer.remove();
			this.$handler.remove();
			this.$el.removeData('imagezoom');
		};
		base.smoothMove = function(left,top)
		{
			var times = 10;
			var oldTop = parseInt(base.$largeImg.css('top'));
			oldTop = isNaN(oldTop)? 0:oldTop;
			var oldLeft = parseInt(base.$largeImg.css('left'));
			oldLeft = isNaN(oldLeft)? 0:oldLeft;
			top = parseInt(top),left = parseInt(left);

			if(oldTop == top && oldLeft ==left)
			{
				window.clearTimeout(base.animateTimer);
				base.animateTimer = null;
				//console.log('clear timer');
				return;
			}
			else
			{
				var topStep = top-oldTop;
				var leftStep = left -oldLeft;

				var newTop = oldTop + topStep/Math.abs(topStep)* Math.ceil(Math.abs(topStep/times));
				var newLeft = oldLeft + leftStep/Math.abs(leftStep) *Math.ceil(Math.abs(leftStep/times));

				base.$viewer.find('img').css({top:newTop,left:newLeft});

				base.animateTimer = setTimeout(function(){
					base.smoothMove(left,top);
				},10);
			}
		};

		//tools
		function toNum(strVal)
		{
			var numVal = parseInt(strVal);
			numVal = isNaN(numVal)? 0:numVal;
			return numVal;
		}

		base.init(options);
	};
	//defaults
	$.ImageZoom.defaults = {
		bigImageSrc:'',
		preload:true,
		type:'inner',
		smoothMove: true,
		position:'right',
		offset:[10,0],
		alignTo:'',
		zoomSize:[100,100],
		descriptionClass:'zm-description',
		zoomViewerClass:'',
		zoomHandlerClass:'',
		showDescription:true,
		onShow:function(target){},
		onHide:function(target){}
	};

	$.ImageZoom._calltimes = 0;

	//$.fn
	$.fn.ImageZoom = function(options){
		return this.each(function(){
			new $.ImageZoom(this,options);
		});
	};

})(jQuery,window);



var fastImg = (function () {
	var list = [], intervalId = null,
	tick = function () {
		var i = 0;
		for (; i < list.length; i++) {
			list[i].end ? list.splice(i--, 1) : list[i]();
		};
		!list.length && stop();
	},
	stop = function () {
		clearInterval(intervalId);
		intervalId = null;
	};

	return function (url, ready, load, error) {
		var onready, width, height, newWidth, newHeight,
			img = new Image();
		img.src = url;
		if (img.complete) {
			ready.call(img);
			load && load.call(img);
			return;
		};
		width = img.width;
		height = img.height;
		img.onerror = function () {
			error && error.call(img);
			onready.end = true;
			img = img.onload = img.onerror = null;
		};
		onready = function () {
			newWidth = img.width;
			newHeight = img.height;
			if (newWidth !== width || newHeight !== height ||newWidth * newHeight > 1024) {
				ready.call(img);
				onready.end = true;
			};
		};
		onready();
		img.onload = function () {
			!onready.end && onready();
			load && load.call(img);
			img = img.onload = img.onerror = null;
		};
		if (!onready.end) {
			list.push(onready);
			if (intervalId === null) intervalId = setInterval(tick, 40);
		};
	};
})();
/**
 * @license jquery.panzoom.js v2.0.5
 * Updated: Thu Jul 03 2014
 * Add pan and zoom functionality to any element
 * Copyright (c) 2014 timmy willison
 * Released under the MIT license
 * https://github.com/timmywil/jquery.panzoom/blob/master/MIT-License.txt
 */
!function(a,b){"function"==typeof define&&define.amd?define(["jquery"],function(c){return b(a,c)}):"object"==typeof exports?b(a,require("jquery")):b(a,a.jQuery)}("undefined"!=typeof window?window:this,function(a,b){"use strict";function c(a,b){for(var c=a.length;--c;)if(+a[c]!==+b[c])return!1;return!0}function d(a){var c={range:!0,animate:!0};return"boolean"==typeof a?c.animate=a:b.extend(c,a),c}function e(a,c,d,e,f,g,h,i,j){this.elements="array"===b.type(a)?[+a[0],+a[2],+a[4],+a[1],+a[3],+a[5],0,0,1]:[a,c,d,e,f,g,h||0,i||0,j||1]}function f(a,b,c){this.elements=[a,b,c]}function g(a,c){if(!(this instanceof g))return new g(a,c);1!==a.nodeType&&b.error("Panzoom called on non-Element node"),b.contains(l,a)||b.error("Panzoom element must be attached to the document");var d=b.data(a,m);if(d)return d;this.options=c=b.extend({},g.defaults,c),this.elem=a;var e=this.$elem=b(a);this.$set=c.$set&&c.$set.length?c.$set:e,this.$doc=b(a.ownerDocument||l),this.$parent=e.parent(),this.isSVG=r.test(a.namespaceURI)&&"svg"!==a.nodeName.toLowerCase(),this.panning=!1,this._buildTransform(),this._transform=!this.isSVG&&b.cssProps.transform.replace(q,"-$1").toLowerCase(),this._buildTransition(),this.resetDimensions();var f=b(),h=this;b.each(["$zoomIn","$zoomOut","$zoomRange","$reset"],function(a,b){h[b]=c[b]||f}),this.enable(),b.data(a,m,this)}var h="over out down up move enter leave cancel".split(" "),i=b.extend({},b.event.mouseHooks),j={};if(a.PointerEvent)b.each(h,function(a,c){b.event.fixHooks[j[c]="pointer"+c]=i});else{var k=i.props;i.props=k.concat(["touches","changedTouches","targetTouches","altKey","ctrlKey","metaKey","shiftKey"]),i.filter=function(a,b){var c,d=k.length;if(!b.pageX&&b.touches&&(c=b.touches[0]))for(;d--;)a[k[d]]=c[k[d]];return a},b.each(h,function(a,c){if(2>a)j[c]="mouse"+c;else{var d="touch"+("down"===c?"start":"up"===c?"end":c);b.event.fixHooks[d]=i,j[c]=d+" mouse"+c}})}b.pointertouch=j;var l=a.document,m="__pz__",n=Array.prototype.slice,o=!!a.PointerEvent,p=function(){var a=l.createElement("input");return a.setAttribute("oninput","return"),"function"==typeof a.oninput}(),q=/([A-Z])/g,r=/^http:[\w\.\/]+svg$/,s=/^inline/,t="(\\-?[\\d\\.e]+)",u="\\,?\\s*",v=new RegExp("^matrix\\("+t+u+t+u+t+u+t+u+t+u+t+"\\)$");return e.prototype={x:function(a){var b=a instanceof f,c=this.elements,d=a.elements;return b&&3===d.length?new f(c[0]*d[0]+c[1]*d[1]+c[2]*d[2],c[3]*d[0]+c[4]*d[1]+c[5]*d[2],c[6]*d[0]+c[7]*d[1]+c[8]*d[2]):d.length===c.length?new e(c[0]*d[0]+c[1]*d[3]+c[2]*d[6],c[0]*d[1]+c[1]*d[4]+c[2]*d[7],c[0]*d[2]+c[1]*d[5]+c[2]*d[8],c[3]*d[0]+c[4]*d[3]+c[5]*d[6],c[3]*d[1]+c[4]*d[4]+c[5]*d[7],c[3]*d[2]+c[4]*d[5]+c[5]*d[8],c[6]*d[0]+c[7]*d[3]+c[8]*d[6],c[6]*d[1]+c[7]*d[4]+c[8]*d[7],c[6]*d[2]+c[7]*d[5]+c[8]*d[8]):!1},inverse:function(){var a=1/this.determinant(),b=this.elements;return new e(a*(b[8]*b[4]-b[7]*b[5]),a*-(b[8]*b[1]-b[7]*b[2]),a*(b[5]*b[1]-b[4]*b[2]),a*-(b[8]*b[3]-b[6]*b[5]),a*(b[8]*b[0]-b[6]*b[2]),a*-(b[5]*b[0]-b[3]*b[2]),a*(b[7]*b[3]-b[6]*b[4]),a*-(b[7]*b[0]-b[6]*b[1]),a*(b[4]*b[0]-b[3]*b[1]))},determinant:function(){var a=this.elements;return a[0]*(a[8]*a[4]-a[7]*a[5])-a[3]*(a[8]*a[1]-a[7]*a[2])+a[6]*(a[5]*a[1]-a[4]*a[2])}},f.prototype.e=e.prototype.e=function(a){return this.elements[a]},g.rmatrix=v,g.events=b.pointertouch,g.defaults={eventNamespace:".panzoom",transition:!0,cursor:"move",disablePan:!1,disableZoom:!1,increment:.3,minScale:.4,maxScale:5,rangeStep:.05,duration:200,easing:"ease-in-out",contain:!1},g.prototype={constructor:g,instance:function(){return this},enable:function(){this._initStyle(),this._bind(),this.disabled=!1},disable:function(){this.disabled=!0,this._resetStyle(),this._unbind()},isDisabled:function(){return this.disabled},destroy:function(){this.disable(),b.removeData(this.elem,m)},resetDimensions:function(){var a=this.$parent;this.container={width:a.innerWidth(),height:a.innerHeight()};var c,d=a.offset(),e=this.elem,f=this.$elem;this.isSVG?(c=e.getBoundingClientRect(),c={left:c.left-d.left,top:c.top-d.top,width:c.width,height:c.height,margin:{left:0,top:0}}):c={left:b.css(e,"left",!0)||0,top:b.css(e,"top",!0)||0,width:f.innerWidth(),height:f.innerHeight(),margin:{top:b.css(e,"marginTop",!0)||0,left:b.css(e,"marginLeft",!0)||0}},c.widthBorder=b.css(e,"borderLeftWidth",!0)+b.css(e,"borderRightWidth",!0)||0,c.heightBorder=b.css(e,"borderTopWidth",!0)+b.css(e,"borderBottomWidth",!0)||0,this.dimensions=c},reset:function(a){a=d(a);var b=this.setMatrix(this._origTransform,a);a.silent||this._trigger("reset",b)},resetZoom:function(a){a=d(a);var b=this.getMatrix(this._origTransform);a.dValue=b[3],this.zoom(b[0],a)},resetPan:function(a){var b=this.getMatrix(this._origTransform);this.pan(b[4],b[5],d(a))},setTransform:function(a){for(var c=this.isSVG?"attr":"style",d=this.$set,e=d.length;e--;)b[c](d[e],"transform",a)},getTransform:function(a){var c=this.$set,d=c[0];return a?this.setTransform(a):a=b[this.isSVG?"attr":"style"](d,"transform"),"none"===a||v.test(a)||this.setTransform(a=b.css(d,"transform")),a||"none"},getMatrix:function(a){var b=v.exec(a||this.getTransform());return b&&b.shift(),b||[1,0,0,1,0,0]},setMatrix:function(a,c){if(!this.disabled){c||(c={}),"string"==typeof a&&(a=this.getMatrix(a));var d,e,f,g,h,i,j,k,l,m,n=+a[0],o=this.$parent,p="undefined"!=typeof c.contain?c.contain:this.options.contain;return p&&(d=this._checkDims(),e=this.container,l=d.width+d.widthBorder,m=d.height+d.heightBorder,f=(l*Math.abs(n)-e.width)/2,g=(m*Math.abs(n)-e.height)/2,j=d.left+d.margin.left,k=d.top+d.margin.top,"invert"===p?(h=l>e.width?l-e.width:0,i=m>e.height?m-e.height:0,f+=(e.width-l)/2,g+=(e.height-m)/2,a[4]=Math.max(Math.min(a[4],f-j),-f-j-h),a[5]=Math.max(Math.min(a[5],g-k),-g-k-i+d.heightBorder)):(g+=d.heightBorder/2,h=e.width>l?e.width-l:0,i=e.height>m?e.height-m:0,"center"===o.css("textAlign")&&s.test(b.css(this.elem,"display"))?h=0:f=g=0,a[4]=Math.min(Math.max(a[4],f-j),-f-j+h),a[5]=Math.min(Math.max(a[5],g-k),-g-k+i))),"skip"!==c.animate&&this.transition(!c.animate),c.range&&this.$zoomRange.val(n),this.setTransform("matrix("+a.join(",")+")"),c.silent||this._trigger("change",a),a}},isPanning:function(){return this.panning},transition:function(a){if(this._transition)for(var c=a||!this.options.transition?"none":this._transition,d=this.$set,e=d.length;e--;)b.style(d[e],"transition")!==c&&b.style(d[e],"transition",c)},pan:function(a,b,c){if(!this.options.disablePan){c||(c={});var d=c.matrix;d||(d=this.getMatrix()),c.relative&&(a+=+d[4],b+=+d[5]),d[4]=a,d[5]=b,this.setMatrix(d,c),c.silent||this._trigger("pan",d[4],d[5])}},zoom:function(a,c){"object"==typeof a?(c=a,a=null):c||(c={});var d=b.extend({},this.options,c);if(!d.disableZoom){var g=!1,h=d.matrix||this.getMatrix();"number"!=typeof a&&(a=+h[0]+d.increment*(a?-1:1),g=!0),a>d.maxScale?a=d.maxScale:a<d.minScale&&(a=d.minScale);var i=d.focal;if(i&&!d.disablePan){var j=this._checkDims(),k=i.clientX,l=i.clientY;this.isSVG||(k-=(j.width+j.widthBorder)/2,l-=(j.height+j.heightBorder)/2);var m=new f(k,l,1),n=new e(h),o=this.parentOffset||this.$parent.offset(),p=new e(1,0,o.left-this.$doc.scrollLeft(),0,1,o.top-this.$doc.scrollTop()),q=n.inverse().x(p.inverse().x(m)),r=a/h[0];n=n.x(new e([r,0,0,r,0,0])),m=p.x(n.x(q)),h[4]=+h[4]+(k-m.e(0)),h[5]=+h[5]+(l-m.e(1))}h[0]=a,h[3]="number"==typeof d.dValue?d.dValue:a,this.setMatrix(h,{animate:"boolean"==typeof d.animate?d.animate:g,range:!d.noSetRange}),d.silent||this._trigger("zoom",h[0],d)}},option:function(a,c){var d;if(!a)return b.extend({},this.options);if("string"==typeof a){if(1===arguments.length)return void 0!==this.options[a]?this.options[a]:null;d={},d[a]=c}else d=a;this._setOptions(d)},_setOptions:function(a){b.each(a,b.proxy(function(a,c){switch(a){case"disablePan":this._resetStyle();case"$zoomIn":case"$zoomOut":case"$zoomRange":case"$reset":case"disableZoom":case"onStart":case"onChange":case"onZoom":case"onPan":case"onEnd":case"onReset":case"eventNamespace":this._unbind()}switch(this.options[a]=c,a){case"disablePan":this._initStyle();case"$zoomIn":case"$zoomOut":case"$zoomRange":case"$reset":this[a]=c;case"disableZoom":case"onStart":case"onChange":case"onZoom":case"onPan":case"onEnd":case"onReset":case"eventNamespace":this._bind();break;case"cursor":b.style(this.elem,"cursor",c);break;case"minScale":this.$zoomRange.attr("min",c);break;case"maxScale":this.$zoomRange.attr("max",c);break;case"rangeStep":this.$zoomRange.attr("step",c);break;case"startTransform":this._buildTransform();break;case"duration":case"easing":this._buildTransition();case"transition":this.transition();break;case"$set":c instanceof b&&c.length&&(this.$set=c,this._initStyle(),this._buildTransform())}},this))},_initStyle:function(){var a={"backface-visibility":"hidden","transform-origin":this.isSVG?"0 0":"50% 50%"};this.options.disablePan||(a.cursor=this.options.cursor),this.$set.css(a);var c=this.$parent;c.length&&!b.nodeName(c[0],"body")&&(a={overflow:"hidden"},"static"===c.css("position")&&(a.position="relative"),c.css(a))},_resetStyle:function(){this.$elem.css({cursor:"",transition:""}),this.$parent.css({overflow:"",position:""})},_bind:function(){var a=this,c=this.options,d=c.eventNamespace,e=o?"pointerdown"+d:"touchstart"+d+" mousedown"+d,f=o?"pointerup"+d:"touchend"+d+" click"+d,h={},i=this.$reset,j=this.$zoomRange;if(b.each(["Start","Change","Zoom","Pan","End","Reset"],function(){var a=c["on"+this];b.isFunction(a)&&(h["panzoom"+this.toLowerCase()+d]=a)}),c.disablePan&&c.disableZoom||(h[e]=function(b){var d;("touchstart"===b.type?!(d=b.touches)||(1!==d.length||c.disablePan)&&2!==d.length:c.disablePan||1!==b.which)||(b.preventDefault(),b.stopPropagation(),a._startMove(b,d))}),this.$elem.on(h),i.length&&i.on(f,function(b){b.preventDefault(),a.reset()}),j.length&&j.attr({step:c.rangeStep===g.defaults.rangeStep&&j.attr("step")||c.rangeStep,min:c.minScale,max:c.maxScale}).prop({value:this.getMatrix()[0]}),!c.disableZoom){var k=this.$zoomIn,l=this.$zoomOut;k.length&&l.length&&(k.on(f,function(b){b.preventDefault(),a.zoom()}),l.on(f,function(b){b.preventDefault(),a.zoom(!0)})),j.length&&(h={},h[(o?"pointerdown":"mousedown")+d]=function(){a.transition(!0)},h[(p?"input":"change")+d]=function(){a.zoom(+this.value,{noSetRange:!0})},j.on(h))}},_unbind:function(){this.$elem.add(this.$zoomIn).add(this.$zoomOut).add(this.$reset).off(this.options.eventNamespace)},_buildTransform:function(){return this._origTransform=this.getTransform(this.options.startTransform)},_buildTransition:function(){if(this._transform){var a=this.options;this._transition=this._transform+" "+a.duration+"ms "+a.easing}},_checkDims:function(){var a=this.dimensions;return a.width&&a.height||this.resetDimensions(),this.dimensions},_getDistance:function(a){var b=a[0],c=a[1];return Math.sqrt(Math.pow(Math.abs(c.clientX-b.clientX),2)+Math.pow(Math.abs(c.clientY-b.clientY),2))},_getMiddle:function(a){var b=a[0],c=a[1];return{clientX:(c.clientX-b.clientX)/2+b.clientX,clientY:(c.clientY-b.clientY)/2+b.clientY}},_trigger:function(a){"string"==typeof a&&(a="panzoom"+a),this.$elem.triggerHandler(a,[this].concat(n.call(arguments,1)))},_startMove:function(a,d){var e,f,g,h,i,j,k,m,n=this,p=this.options,q=p.eventNamespace,r=this.getMatrix(),s=r.slice(0),t=+s[4],u=+s[5],v={matrix:r,animate:"skip"};o?(f="pointermove",g="pointerup"):"touchstart"===a.type?(f="touchmove",g="touchend"):(f="mousemove",g="mouseup"),f+=q,g+=q,this.transition(!0),this.panning=!0,this._trigger("start",a,d),d&&2===d.length?(h=this._getDistance(d),i=+r[0],j=this._getMiddle(d),e=function(a){a.preventDefault();var b=n._getMiddle(d=a.touches),c=n._getDistance(d)-h;n.zoom(c*(p.increment/100)+i,{focal:b,matrix:r,animate:!1}),n.pan(+r[4]+b.clientX-j.clientX,+r[5]+b.clientY-j.clientY,v),j=b}):(k=a.pageX,m=a.pageY,e=function(a){a.preventDefault(),n.pan(t+a.pageX-k,u+a.pageY-m,v)}),b(l).off(q).on(f,e).on(g,function(a){a.preventDefault(),b(this).off(q),n.panning=!1,a.type="panzoomend",n._trigger(a,r,!c(r,s))})}},b.Panzoom=g,b.fn.panzoom=function(a){var c,d,e,f;return"string"==typeof a?(f=[],d=n.call(arguments,1),this.each(function(){c=b.data(this,m),c?"_"!==a.charAt(0)&&"function"==typeof(e=c[a])&&void 0!==(e=e.apply(c,d))&&f.push(e):f.push(void 0)}),f.length?1===f.length?f[0]:f:this):this.each(function(){new g(this,a)})},g});