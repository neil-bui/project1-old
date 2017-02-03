/**
* Creates and handles wp.media views for editing a Gallery within an Album
*
* @since 1.3.0
*/

/**
* View: Error
* - Renders a WordPress style error message when something goes wrong.
*
* @since 1.4.3.0
*/
wp.media.view.EnviraAlbumError = wp.Backbone.View.extend( {

    // The outer tag and class name to use. The item is wrapped in this
    tagName   : 'div',
    className : 'notice error envira-albums-error',

    render: function() {

        // Load the template to render
        // See envira-gallery/includes/admin/media-views.php
        this.template = wp.media.template( 'envira-albums-error' );

        // Define the HTML for the template
        this.$el.html( this.template( this.model ) );

        // Return the template
        return this;

    }

} );

/**
* View: Item
* - Renders an individual image within an unordered attachment list
*
* @since 1.3.0
*/
wp.media.view.EnviraAlbumItem = wp.Backbone.View.extend( {

    /**
    * The Tag Name and Tag's Class(es)
    */
    tagName   : 'li',
    className : 'attachment envira-gallery-item',

    /**
    * Template
    * - The template to load inside the above tagName element
    */
    template:   wp.template( 'envira-albums-item' ),

    /**
     * Render the image
     */
    render: function() {

        // Define the HTML for the template
        this.$el.html( this.template( this.model.toJSON() ) );

        // If this is the cover image, add the selected class
        if ( this.model.get( 'is_cover_image' ) ) {
            this.$el.addClass( 'selected' );
        }

        // Return the template
        return this;

    },

} );

/**
* Gallery Model
*/
var EnviraAlbumGallery = Backbone.Model.extend( {

    /**
    * Defaults
    * As we always populate this model with existing data, we
    * leave these blank to just show how this model is structured.
    */
    defaults: {
        'id':                   '',
        'title':                '',
        'caption':              '',
        'alt':                  '',
        'cover_image_id':       '',
        'cover_image_url':      '',
        'link_new_window':      '',
        'gallery_lightbox':     '1', // on by default
        'cover_image_url_thumb':'',
    },

} );

/**
* Image Model
*/
var EnviraAlbumGalleryImage = Backbone.Model.extend( {

    /**
    * Defaults
    * As we always populate this model with existing data, we
    * leave these blank to just show how this model is structured.
    */
    defaults: {
        'id':               '',
        'title':            '',
        'src':              '',
        'thumb':            '',
        'is_cover_image':   false,
    },

} );

/**
* Images Collection
* - Comprises of all chosen galleries in an Envira Album
* - Each image is represented by an EnviraAlbumGallery Model
*/
var EnviraAlbumGalleries = new Backbone.Collection;

/**
* Modal Window
*/
var EnviraAlbumModalWindow = new wp.media.view.Modal( {
    controller: {
        trigger: function() {

        }
    }
} );

/**
* View
*/
var EnviraAlbumEditView = wp.Backbone.View.extend( {

    /**
    * The Tag Name and Tag's Class(es)
    */
    tagName:    'div',
    className:  'edit-attachment-frame mode-select hide-menu hide-router',

    /**
    * Template
    * - The template to load inside the above tagName element
    */
    template:   wp.template( 'envira-albums-meta-editor' ),

    /**
    * Events
    * - Functions to call when specific events occur
    */
    events: {
        'click .edit-media-header .left':               'loadPreviousItem',
        'click .edit-media-header .right':              'loadNextItem',

        'click li.attachment':                          'updateCoverImage',

        'keyup input':                                  'updateItem',
        'keyup textarea':                               'updateItem',
        'change input':                                 'updateItem',
        'change textarea':                              'updateItem',
        'blur textarea':                                'updateItem',
        'change select':                                'updateItem',

        'click .actions a.envira-gallery-meta-submit':  'saveItem',
    },

    /**
    * Initialize
    *
    * @param object model   EnviraGalleryImage Backbone Model
    */
    initialize: function( args ) {

        // Define loading and loaded events, which update the UI with what's happening.
        this.on( 'loading', this.loading, this );
        this.on( 'loaded',  this.loaded, this );

        // Set some flags
        this.is_loading = false;
        this.collection = args.collection;
        this.images_collection = new Backbone.Collection;
        this.child_views = args.child_views;
        this.gallery_id = args.gallery_id;
        this.gallery_index = 0;
        this.search_timer = '';

        // Get the model from the collection
        var count = 0;
        this.collection.each( function( model ) {
            // If this model's id matches the attachment id, this is the model we want
            if ( model.get( 'id' ) == this.gallery_id ) {
                this.model = model;
                this.gallery_index = count;
                return false;
            }

            // Increment the index count
            count++;
        }, this );

    },

    /**
    * Render
    * - Binds the model to the view, so we populate the view's fields and data
    */
    render: function() {

        this.trigger( 'loading' );

        // Get HTML
        this.$el.html( this.template( this.model.attributes ) );

        // If any child views exist, render them now
        if ( this.child_views.length > 0 ) {
            this.child_views.forEach( function( view ) {
                // Init with model
                var child_view = new view( {
                    model: this.model
                } );

                // Render view within our main view
                this.$el.find( 'div.envira-addons' ).append( child_view.render().el );
            }, this );
        }

        // Set caption
        this.$el.find( 'textarea[name=caption]' ).val( this.model.get( 'caption' ) );

        // Init QuickTags on the caption editor
        // Delay is required for the first load for some reason
        setTimeout( function() {
            quicktags( {
                id:     'caption',
                buttons:'strong,em,link,ul,ol,li,close'
            } );
            QTags._buttonsInit();
        }, 500 );

        // If the images collection is empty, this means we're rendering a different gallery
        // in the modal, so we need to run an AJAX query to get the gallery images.
        if ( this.images_collection.length == 0 ) {
            // Perform an AJAX request to retrieve all images belonging to the Gallery we're now editing
            wp.media.ajax( 'envira_albums_get_gallery_images', {
                context: this,
                data: {
                    nonce:      envira_albums_metabox.get_gallery_images_nonce,
                    gallery_id: this.model.get( 'id' )
                },
                success: function( items ) {

                    // Convert the items into an array
                    images = [];
                    for ( var image_id in items ) {
                        images.push( items[ image_id ] );
                    }

                    // Add the images to the collection
                    var collection = new Backbone.Collection( images )
                    this.images_collection.add( collection.models );

                    // Iterate through each item, adding it to the list of items
                    this.images_collection.each( function( image ) {
                        // Determine whether this image is the currently selected cover image.
                        // First, check the image ID, then the image src (there won't be an ID for
                        // External Galleries e.g. Instagram, hence we fallback to checking if the image URL
                        // matches the cover image URL).
                        if ( image.get( 'id' ) == this.model.get( 'cover_image_id' ) ) {
                            image.set( 'is_cover_image', true );
                        } else if ( image.get( 'src' ) == this.model.get( 'cover_image_url' ) ) {
                            image.set( 'is_cover_image', true );
                        } else {
                            image.set( 'is_cover_image', false );
                        }

                        // Append the rendered item to the container view
                        this.$el.find( 'ul.attachments' ).append( this.renderItem( image ) );
                    }, this );

                    // Tell wp.media we've finished loading items
                    this.trigger( 'loaded' );
                },
                error: function( error_message ) {
                    // Tell wp.media we've finished loading items, and send the error message
                    // for output
                    this.trigger( 'loaded', error_message );
                }
            } );
        } else {
            // Iterate through each item, adding it to the list of items
            this.images_collection.each( function( image ) {
                // If this image's ID matches the galleries' cover image ID, mark this as selected
                if ( image.get( 'id' ) == this.model.get( 'cover_image_id' ) ) {
                    image.set( 'is_cover_image', true );
                } else {
                    image.set( 'is_cover_image', false );
                }

                // Append the rendered item to the container view
                this.$el.find( 'ul.attachments' ).append( this.renderItem( image ) );
            }, this );
        }

        // Return
        return this;

    },

    /**
    * Renders an individual image using the
    * wp.media.view.EnviraGalleryItem view
    */
    renderItem: function( model ) {

        var view = new wp.media.view.EnviraAlbumItem( {
            model   : model,
        } );

        return view.render().el;

    },

    /**
    * Renders an error using
    * wp.media.view.EnviraAlbumError
    */
    renderError: function( error ) {

        // Define model
        var model = {};
        model.error = error;

        // Define view
        var view = new wp.media.view.EnviraAlbumError( {
            model: model
        } );

        // Return rendered view
        return view.render().el;

    },

    /**
    * Tells the view we're loading by displaying a spinner
    */
    loading: function() {

        // Set a flag so we know we're loading data
        this.is_loading = true;

        // Show the spinner
        this.$el.find( '.spinner' ).css( 'visibility', 'visible' );

    },

    /**
    * Hides the loading spinner
    */
    loaded: function( response ) {

        // Set a flag so we know we're not loading anything now
        this.is_loading = false;

        // Hide the spinner
        this.$el.find( '.spinner' ).css( 'visibility', 'hidden' );

        // Display the error message, if it's provided
        if ( typeof response !== 'undefined' ) {
            this.$el.find( 'ul.attachments' ).before( this.renderError( response ) );
        }

    },

    /**
    * Load the previous model in the collection
    */
    loadPreviousItem: function() {

        // Decrement the index
        this.gallery_index--;

        // Get the model at the new index from the collection
        this.model = this.collection.at( this.gallery_index );

        // Update the gallery id
        this.gallery_id = this.model.get( 'id' );

        // Clear the gallery images collection
        this.images_collection = new Backbone.Collection;

        // Re-render the view
        this.render();

    },

    /**
    * Load the next model in the collection
    */
    loadNextItem: function() {

        // Increment the index
        this.gallery_index++;

        // Get the model at the new index from the collection
        this.model = this.collection.at( this.gallery_index );

        // Update the gallery id
        this.gallery_id = this.model.get( 'id' );

        // Clear the gallery images collection
        this.images_collection = new Backbone.Collection;

        // Re-render the view
        this.render();

    },

    /**
     * Marks the clicked image as the cover image
     * Refreshes the UI to display the changes
     */
    updateCoverImage: function( event ) {

        // Get the element
        var target  = jQuery( event.currentTarget ),
            id      = jQuery( 'div.attachment-preview', target ).attr( 'data-id' );

        // If the image is already selected, don't do anything
        if ( target.hasClass( 'selected' ) ) {
            return;
        }

        // Get the image model from the image collection
        this.images_collection.each( function( image ) {
            // If this image matches the image the user selected...
            if ( image.get( 'id' ) == id ) {
                // Update the cover image ID for this Gallery
                this.model.set( 'cover_image_id', image.get( 'id' ) );

                // Update the cover image URL to the full size image src
                this.model.set( 'cover_image_url', image.get( 'src' ) );

                // Update the cover image URL thumbnail
                this.model.set( 'cover_image_url_thumb', image.get( 'thumb' ) );

                // Re-render the view
                this.render();
            }
        }, this );

        // Mark the item as selected in the media view
        target.addClass( 'selected details' );

    },

    /**
    * Updates the model based on the changed view data
    */
    updateItem: function( event ) {

        // Check if the target has a name. If not, it's not a model value we want to store
        if ( event.target.name == '' ) {
            return;
        }

        // Update the model's value, depending on the input type
        if ( event.target.type == 'checkbox' ) {
            value = ( event.target.checked ? 1 : 0 );
        } else {
            value = event.target.value;
        }

        // Update the model
        this.model.set( event.target.name, value );

    },

    /**
    * Saves the gallery metadata (title, caption, alt, cover image, link new window, etc)
    */
    saveItem: function() {

        // Tell the View we're loading
        this.trigger( 'loading' );

        // Make an AJAX request to save the image metadata
        wp.media.ajax( 'envira_albums_update_gallery', {
            context: this,
            data: {
                nonce:     envira_albums_metabox.save_nonce,
                post_id:   envira_albums_metabox.id,
                gallery_id:this.model.get( 'id' ),
                meta:      this.model.attributes,
            },
            success: function( response ) {

                // Tell the view we've finished successfully
                this.trigger( 'loaded loaded:success' );

                // Assign the model's JSON string back to the underlying item in the edit screen
                var item         = JSON.stringify( this.model.attributes ),
                    item_element = jQuery( 'ul#envira-album-drag-drop-area li#envira-gallery-' + this.model.get( 'id' ) );
                jQuery( item_element ).attr( 'data-envira-album-gallery-model', item );

                // Update the cover image in the underlying item in the edit screen
                jQuery( 'img', item_element ).attr( 'src', this.model.get( 'cover_image_url_thumb' ) );

                // Update the title
                jQuery( 'div.meta div.title', item_element ).text( this.model.get( 'title' ) );

                // Show the user the 'saved' notice for 1.5 seconds
                var saved = this.$el.find( '.saved' );
                saved.fadeIn();
                setTimeout( function() {
                    saved.fadeOut();
                }, 1500 );

            },
            error: function( error_message ) {

                // Tell wp.media we've finished, but there was an error
                this.trigger( 'loaded loaded:error', error_message );

            }
        } );

    },

} );

/**
* Sub Views
* - Addons must populate this array with their own Backbone Views, which will be appended
* to the settings region
*/
var EnviraAlbumChildViews = [];

/**
* DOM
*/
jQuery( document ).ready( function( $ ) {

    // Setup the collection
    EnviraAlbumGalleriesUpdate();

    // Edit Gallery
    $( '#envira-albums' ).on( 'click', 'a.envira-gallery-modify-image', function( e ) {

        // Prevent default action
        e.preventDefault();

        // (Re)populate the collection
        // The collection can change based on whether the user previously selected specific galleries
        EnviraAlbumGalleriesUpdate();

        // Get the selected gallery
        var gallery_id = $( this ).parent().data( 'envira-gallery' );

        // Pass the collection of galleries for this album to the modal view, as well
        // as the selected attachment
        EnviraAlbumModalWindow.content( new EnviraAlbumEditView( {
            collection:     EnviraAlbumGalleries,
            child_views:    EnviraAlbumChildViews,
            gallery_id:     gallery_id,
        } ) );

        // Open the modal window
        EnviraAlbumModalWindow.open();

    } );

} );

/**
* Populates the EnviraAlbumGalleries Backbone collection, which comprises of a set of Envira Galleries
*
* Called when galleries are added, deleted, reordered or selected to an Album
*
* @global           EnviraAlbumGalleries    The backbone collection of galleries
*/
function EnviraAlbumGalleriesUpdate() {

    // Clear the collection
    EnviraAlbumGalleries.reset();

    // Iterate through the gallery images in the DOM, adding them to the collection
    var selector = 'ul#envira-album-drag-drop-area li.envira-gallery-image';

    jQuery( selector ).each( function() {
        // Build an EnviraAlbumGallery Backbone Model from the JSON supplied in the element
        var envira_album_gallery = jQuery.parseJSON( jQuery( this ).attr( 'data-envira-album-gallery-model' ) );

        // Strip slashes from some fields
        envira_album_gallery.alt = EnviraAlbumStripslashes( envira_album_gallery.alt );

        // Add the model to the collection
        EnviraAlbumGalleries.add( new EnviraAlbumGallery( envira_album_gallery ) );
    } );

}

/**
* Strips slashes from the given string, which may have been added to escape certain characters
*
* @since 1.3.0
*
* @param    string  str     String
* @return   string          String without slashes
*/
function EnviraAlbumStripslashes( str ) {

    return (str + '').replace(/\\(.?)/g, function(s, n1) {
        switch (n1) {
            case '\\':
                return '\\';
            case '0':
                return '\u0000';
            case '':
                return '';
            default:
                return n1;
        }
    });

}