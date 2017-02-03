// Setup vars
var envira_album_selected_galleries     = 'ul#envira-album-drag-drop-area',
    envira_album_available_galleries    = 'ul#envira-albums-output',
    envira_album_container              = '#envira-album-main',
    envira_album_meta_container         = '.envira-gallery-meta-container',
    envira_album_draggable_options = {
        helper: 'clone',    // Required for connectToSortable to work: http://api.jqueryui.com/draggable/#option-connectToSortable
        connectToSortable: '#envira-album-drag-drop-area',
        revert: 'invalid'
    };

/**
 * Handles Gallery sorting within an Album
 */
jQuery( document ).ready( function( $ ) {

	/**
	* Gallery Sorting
	*/
	$( envira_album_selected_galleries ).sortable( {

    	stop: function( event, ui ) {
	    	// Hide "Drop galleries here" description
	    	$( 'p.drag-drop-info' ).addClass( 'hidden' );
	    	
	        // Delete original element as we cloned it
        	var element    = ui.item,
                gallery_id = $( element ).data( 'envira-gallery' );

            // Set the ID of the element, as this seems to disappear when dragging & dropping
            $( element ).attr( 'id', 'envira-gallery-' + gallery_id );
        	
        	// Remove selected class on element
        	ui.item.removeClass( 'selected' );
        	
        	// Timeout by .1s to prevent JS error and let sortable finish the stop operation
        	setTimeout( function() {
        		$( "li[data-envira-gallery='" + gallery_id + "']", $( envira_album_available_galleries ) ).remove();	
        	}, 100);
    	},

    } );
	
	/**
	* Gallery Sorting - send AJAX request to save sort order on change
	*/
    $( envira_album_selected_galleries ).on( 'sortupdate', function() {

        // Repopulate the collection of Galleries in this Album
        EnviraAlbumGalleriesUpdate();

    	// Get ordered list of gallery IDs and store in a hidden field
		var gallery_ids = [];
        var galleries = [];
        EnviraAlbumGalleries.each( function( gallery ) {
            // Add to the gallery ID array
            gallery_ids.push( gallery.get( 'id' ) );
            galleries.push( gallery.attributes );
        } );

        // Populate the hidden field with our gallery IDs
        $( 'input[name=galleryIDs]' ).val( gallery_ids.join( "," ) );
        
        // Save gallery order and metadata using AJAX
        $.ajax( {
            url:      envira_albums_metabox.ajax,
            type:     'post',
            async:    true,
            cache:    false,
            dataType: 'json',
            data: {
                action:  		'envira_albums_sort_galleries',
                gallery_ids:    gallery_ids,
                galleries:      galleries,
                post_id: 		envira_albums_metabox.id,
                nonce:   		envira_albums_metabox.sort
            },
            success: function( response ) {

            	return;

            },
            error: function( xhr, textStatus, e ) {

            	// Inject the error message into the settings area
                $( envira_album_selected_galleries ).before( '<div class="error"><p>' + textStatus.responseText + '</p></div>' );

            }
        } );
    } );
    
} );