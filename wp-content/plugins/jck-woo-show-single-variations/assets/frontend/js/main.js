(function($, document) {

    var jck_wssv = {

        cache: function() {
            jck_wssv.els = {};
            jck_wssv.vars = {};

            // common vars
            jck_wssv.vars.add_to_cart_class = ".jck_wssv_add_to_cart";

            // common elements
            jck_wssv.els.document = $(document);
            jck_wssv.els.add_to_cart = $(jck_wssv.vars.add_to_cart_class);

        },

        on_ready: function() {

            // on ready stuff here
            jck_wssv.cache();
            jck_wssv.setup_add_to_cart();

        },

        setup_add_to_cart: function() {

            jck_wssv.els.document.on('click', jck_wssv.vars.add_to_cart_class, function(){

                var $thisbutton = $( this );

                if ( ! $thisbutton.attr( 'data-variation_id' ) ) {
    				return true;
    			}

    			$thisbutton.removeClass( 'added' );
    			$thisbutton.addClass( 'loading' );

                var data = {
                    'action': 'jck_wssv_add_to_cart'
                };

    			$.each( $thisbutton.data(), function( key, value ) {
    				data[key] = value;
    			});

                // Trigger event
                $( document.body ).trigger( 'adding_to_cart', [ $thisbutton, data ] );

                // Ajax action
                $.post( jck_wssv_vars.ajaxurl, data, function( response ) {

    			    if ( ! response ) {
    					return;
    				}

    				var this_page = window.location.toString();

    				this_page = this_page.replace( 'add-to-cart', 'added-to-cart' );

    				if ( response.error && response.product_url ) {
    					window.location = response.product_url;
    					return;
    				}

    				// Redirect to cart option
    				if ( wc_add_to_cart_params.cart_redirect_after_add === 'yes' ) {

    					window.location = wc_add_to_cart_params.cart_url;
    					return;

    				} else {

    					$thisbutton.removeClass( 'loading' );

    					var fragments = response.fragments;
    					var cart_hash = response.cart_hash;

    					// Block fragments class
    					if ( fragments ) {
    						$.each( fragments, function( key ) {
    							$( key ).addClass( 'updating' );
    						});
    					}

    					// Block widgets and fragments
    					$( '.shop_table.cart, .updating, .cart_totals' ).fadeTo( '400', '0.6' ).block({
    						message: null,
    						overlayCSS: {
    							opacity: 0.6
    						}
    					});

    					// Changes button classes
    					$thisbutton.addClass( 'added' );

    					// View cart text
    					if ( ! wc_add_to_cart_params.is_cart && $thisbutton.parent().find( '.added_to_cart' ).size() === 0 ) {
    						$thisbutton.after( ' <a href="' + wc_add_to_cart_params.cart_url + '" class="added_to_cart wc-forward" title="' +
    							wc_add_to_cart_params.i18n_view_cart + '">' + wc_add_to_cart_params.i18n_view_cart + '</a>' );
    					}

    					// Replace fragments
    					if ( fragments ) {
    						$.each( fragments, function( key, value ) {
    							$( key ).replaceWith( value );
    						});
    					}

    					// Unblock
    					$( '.widget_shopping_cart, .updating' ).stop( true ).css( 'opacity', '1' ).unblock();

    					// Cart page elements
    					$( '.shop_table.cart' ).load( this_page + ' .shop_table.cart:eq(0) > *', function() {

    						$( '.shop_table.cart' ).stop( true ).css( 'opacity', '1' ).unblock();

    						$( document.body ).trigger( 'cart_page_refreshed' );
    					});

    					$( '.cart_totals' ).load( this_page + ' .cart_totals:eq(0) > *', function() {
    						$( '.cart_totals' ).stop( true ).css( 'opacity', '1' ).unblock();
    					});

    					// Trigger event so themes can refresh other areas
    					$( document.body ).trigger( 'added_to_cart', [ fragments, cart_hash, $thisbutton ] );
    				}

    			});

                return false;

            });

        }

    };

	$(document).ready( jck_wssv.on_ready() );

}(jQuery, document));