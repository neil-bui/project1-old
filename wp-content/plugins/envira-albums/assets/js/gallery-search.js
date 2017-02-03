// Setup vars
var envira_albums_gallery_search = 'input#envira-albums-gallery-search',
	envira_albums_gallery_search_timer = null;

/**
 * Handles searching Galleries within an Album
 */
jQuery( document ).ready( function( $ ) {

	//Dont submit the form when enter is pressed
	$(envira_albums_gallery_search).keypress( function( e ) {

	    if (e.keyCode == 13) {

	        e.preventDefault();

	    }

	});

    /**
    * Galleries: Search
    */
    $( envira_albums_gallery_search ).keyup( function( e ) {

	    // Set delayed search to begin
	    if ( envira_albums_gallery_search_timer ) {
		    window.clearTimeout( envira_albums_gallery_search_timer );
	    }

	    // Set a timeout before starting the search
	    envira_albums_gallery_search_timer = window.setTimeout( function() {

		    // Perform search
		    envira_album_gallery_search( $( envira_albums_gallery_search ).val() );

	    }, 500 );

    } );

    /**
    * Galleries: Cancel Search (cross button clicked on search field)
    */
    $( envira_albums_gallery_search ).on( 'search', function() {

	    envira_album_gallery_search( '' );

    } );

    /**
     * Performs an AJAX request, injecting matching Galleries into the DOM
     *
     * @since 1.3.0
     *
     * @param string 	search_terms 	Search Terms
     */
    function envira_album_gallery_search( search_terms ) {

    	// Don't perform a search if the user entered 1 or 2 characters
	    if ( search_terms.length < 3 && search_terms.length > 0 ) {
	    	return;
	    }

    	// Send an AJAX request to return the matching galleries
        $.ajax( {
            url:      envira_albums_metabox.ajax,
            type:     'post',
            async:    true,
            cache:    false,
            data: {
                action:  		'envira_albums_search_galleries',
                search_terms:   search_terms,
                post_id: 		envira_albums_metabox.id,
                nonce:   		envira_albums_metabox.search
            },
            success: function( response ) {
            	if ( response.length > 0 ) {
            		// Insert found Galleries
            		$( envira_album_available_galleries ).html( response );

            		// Setup draggables + multiselect
            		$( 'li', $( envira_album_available_galleries ) ).draggable( envira_album_draggable_options );
            	}

            	// Reset the search timer
            	envira_albums_gallery_search_timer = null;

            },
            error: function( xhr, textStatus, e) {

            	// Inject the error message into the settings area
                $( '#envira-albums-main' ).before( '<div class="error"><p>' + textStatus.responseText + '</p></div>' );

            	// Reset the search timer
            	envira_albums_gallery_search_timer = null;

            }
        } );

    }

});