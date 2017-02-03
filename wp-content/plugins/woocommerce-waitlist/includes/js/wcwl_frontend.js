// JS required for the front end
jQuery( document ).ready( function( $ ){

    //Grab href of join waitlist button
    var href       = 'undefined' != typeof($( "a.woocommerce_waitlist" ).attr( "href" ) ) ?$( "a.woocommerce_waitlist" ).attr( "href" ): '' ;
    var a_href     = href.split('&wcwl_email');
    var email      = '';
    var product_id = $( '.wcwl_control a' ).data( 'id' );

    // When email input is changed update the buttons href attribute to include the email
    $( "#wcwl_email" ).on( "input", function( e ) {
        email = $( "#wcwl_email" ).val();
        $( "a.woocommerce_waitlist" ).prop( "href", a_href+"&wcwl_email="+email );

    });

    // Create arrays for the checkboxes
    var checked_array =  $( "input:checkbox:checked.wcwl_checkbox" ).map( function() {
        return $( this ).attr( "id" );
    }).get();
    var unchecked_array = $( "input:checkbox:not(:checked).wcwl_checkbox" ).map( function() {
        return $( this ).attr( "id" );
    }).get();
    var changed = [];

    // When a checkbox is clicked, retrieve the product id for that checkbox and add/remove it from the 'changed' array
    $( ".wcwl_checkbox" ).change( function() {
        if( this.checked ) {
            var checked = $( this ).attr( "id" );
            if( $.inArray( checked, changed ) !== -1 ) {
                changed.splice( $.inArray( checked, changed ), 1 );
            }
            else {
                if( $.inArray( checked, checked_array ) == -1 ) {
                    changed.push( checked );
                }
            }
        }
        if( !this.checked ) {
            var unchecked =  $( this ).attr( "id" );
            if( $.inArray( unchecked, changed ) !== -1 ) {
                changed.splice( $.inArray(unchecked, changed ), 1 );
            }
            else {
                if( $.inArray( unchecked, unchecked_array ) == -1 ) {
                    changed.push( unchecked );
                }
            }
        }
    });

    // Modify the buttons href attribute to include the updated array of checkboxes and user email
    $( '#wcwl-product-'+product_id ).on( 'click', function(e) {
        $( "a.woocommerce_waitlist" ).prop( "href", a_href+"&wcwl_email="+email+"&wcwl_changed="+changed );
    });

    // Hide the add to cart button if the "Join Waitlist" button is visible
    // This needs to fire on page load and each time a variation attribute is changed
    var hide_cart_button = function() {
        if ( $( 'p.out-of-stock' ).length > 0 ) {
            $( '.woocommerce-variation-add-to-cart' ).hide();
        } else {
            $( '.woocommerce-variation-add-to-cart' ).show();
        }
    };
    hide_cart_button();
    $( '.variations' ).on( 'change', 'select', function() {
        window.setTimeout( hide_cart_button, 0 );
    });
});
