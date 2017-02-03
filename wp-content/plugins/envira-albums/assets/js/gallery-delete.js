/**
 * Handles deletion (removal) of Galleries from an Album
 */
jQuery( document ).ready( function( $ ) {

	/**
    * Delete Multiple Images
    */
    $( 'a.envira-album-galleries-delete' ).click( function( e ) {

        e.preventDefault();

        // Bail out if the user does not actually want to remove the galleries.
        var confirm_delete = confirm( envira_albums_metabox.remove_multiple );
        if ( ! confirm_delete ) {
            return false;
        }

        // Build array of image attachment IDs
        var attach_ids = [];
        $( 'ul#envira-album-drag-drop-area > li.selected' ).each( function() {
            // Restore original element back to the available galleries section, and make it draggable
            $( this ).parent().appendTo( $ ( envira_album_available_galleries ) ).draggable( envira_album_draggable_options ); 
        } );

        // Trigger sortable update to save new gallery IDs in this album
        $( envira_album_selected_galleries ).trigger( 'sortupdate' );

    } );

    /**
    * Delete Single Gallery
    */
    $( '#envira-albums' ).on( 'click', '.envira-gallery-remove-image', function( e ) {
        
        e.preventDefault();

        // Bail out if the user does not actually want to remove the image.
        var confirm_delete = confirm( envira_albums_metabox.remove );
        if ( ! confirm_delete ) {
            return;
        }

        // Restore original element back to the available galleries section, and make it draggable
        $( this ).parent().appendTo( $ ( envira_album_available_galleries ) ).draggable( envira_album_draggable_options ); 

        // Trigger sortable update to save new gallery IDs in this album
        $( envira_album_selected_galleries ).trigger( 'sortupdate' );

    } );

} );