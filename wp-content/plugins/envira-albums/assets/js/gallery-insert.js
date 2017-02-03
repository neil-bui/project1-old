/**
 * Handles insertion of Galleries into an Album
 */
jQuery( document ).ready( function( $ ) {

    /**
    * Drag & Drop Galleries into Container
    */
    $( 'li', $( envira_album_available_galleries ) ).draggable( envira_album_draggable_options );
    
	/**
    * Galleries: Click / Ctrl & Click
    */
    $( envira_album_available_galleries ).on( 'click', 'li.envira-gallery-image', function() {

        if ( $( this ).hasClass( 'selected' ) ) {
            $( this ).removeClass( 'selected' );
        } else {
            $( this ).addClass( 'selected' );
        }
        
        // Show/hide 'Add Selected Galleries to Album' nav bar depending on whether
        // any galleries have been selected
        if ( $( 'li.selected', $( envira_album_available_galleries ) ).length > 0 ) {
            $( 'a.envira-galleries-add' ).fadeIn();  
        } else {
            $( 'a.envira-galleries-add' ).fadeOut();  
        }
    } );
    
    /**
    * Galleries: Add Selected to Album
    */
    $( 'a.envira-galleries-add' ).on( 'click', function( e ) {

        // Prevent the default action.
        e.preventDefault();

        // Hide "Drop galleries here" description
        $( 'p.drag-drop-info' ).hide();

        // For each selected gallery, move it into the drag/drop area.
        $( 'li.selected', $( envira_album_available_galleries ) ).each( function() {

            // Remove selected class and move the gallery into the grid of Album Galleries
            $( this ).removeClass( 'selected' ).appendTo( $( envira_album_selected_galleries ) );
            
        } );

        // Hide nav bar
        $( 'a.envira-galleries-add' ).fadeOut();

        // Trigger the sortupdate action so that the new Galleries are saved in this Album.
        $( envira_album_selected_galleries ).trigger( 'sortupdate' );

    } );

} );