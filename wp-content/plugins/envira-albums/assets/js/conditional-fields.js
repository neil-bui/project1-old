/**
* Handles showing and hiding fields conditionally
*/
jQuery( document ).ready( function( $ ) {

	// Show/hide elements as necessary when a conditional field is changed
	$( '#envira-albums-settings input:not([type=hidden]), #envira-albums-settings select' ).conditions( 
		[

			{	// Main Theme Elements
				conditions: {
					element: '[name="_eg_album_data[config][lightbox_theme]"]',
					type: 'value',
					operator: 'array',
					condition: [ 'base', 'captioned', 'polaroid', 'showcase', 'sleek', 'subtle' ]
				},
				actions: {
					if: [
						{
							element: '#envira-config-lightbox-title-display-box, #envira-config-lightbox-arrows-box, #envira-config-lightbox-toolbar-box, #envira-config-supersize-box',
							action: 'show'
						}
					]
				}
			},
			{
				conditions: {
					element: '[name="_eg_album_data[config][lightbox_theme]"]',
					type: 'value',
					operator: 'array',
					condition: [ 'base_dark' ]
				},
				actions: {
					if: [
						{
							element: '#envira-config-lightbox-title-display-box, #envira-config-lightbox-arrows-box, #envira-config-lightbox-toolbar-box, #envira-config-supersize-box',
							action: 'hide'
						}
					]
				}
			},
			{	// Mobile Elements Dependant on Theme
				conditions: [
					{
						element: '[name="_eg_album_data[config][lightbox_theme]"]',
						type: 'value',
						operator: 'array',
						condition: [ 'base', 'captioned', 'polaroid', 'showcase', 'sleek', 'subtle' ]
					},
					{
						element: '[name="_eg_album_data[config][mobile_lightbox]"]',
						type: 'checked',
						operator: 'is'
					}
				],
				actions: {
					if: {
						element: '#envira-config-mobile-arrows-box, #envira-config-mobile-toolbar-box',
						action: 'show'
					},
					else: {
						element: '#envira-config-mobile-arrows-box, #envira-config-mobile-toolbar-box',
						action: 'hide'
					}
				}
			},
			{	// Mobile Elements Independant of Theme
				conditions: {
					element: '[name="_eg_album_data[config][mobile_lightbox]"]',
					type: 'checked',
					operator: 'is'
				},
				actions: {
					if: {
						element: '#envira-config-mobile-touchwipe-box, #envira-config-mobile-touchwipe-close-box, #envira-config-mobile-thumbnails-box',
						action: 'show'
					},
					else: {
						element: '#envira-config-mobile-touchwipe-box, #envira-config-mobile-touchwipe-close-box, #envira-config-mobile-thumbnails-box',
						action: 'hide'
					}
				}
			},
			{	// Gallery CSS animations
				conditions: {
					element: '[name="_eg_album_data[config][css_animations]"]',
					type: 'checked',
					operator: 'is'
				},
				actions: {
					if: [
						{
							element: '#envira-config-css-opacity-box',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-config-css-opacity-box',
							action: 'hide'
						}
					]
				}
			},
			{	// Mobile Elements Independant of Theme
				conditions: [
					{
						element: '[name="_eg_album_data[config][mobile_lightbox]"]',
						type: 'checked',
						operator: 'is'
					},
					{
						element: '[name="_eg_album_data[config][mobile_thumbnails]"]',
						type: 'checked',
						operator: 'is'
					}
				],
				actions: {
					if: {
						element: '#envira-config-mobile-thumbnails-width-box, #envira-config-mobile-thumbnails-height-box',
						action: 'show'
					},
					else: {
						element: '#envira-config-mobile-thumbnails-width-box, #envira-config-mobile-thumbnails-height-box',
						action: 'hide'
					}
				}
			},
			{	// Thumbnail Elements Dependant on Theme
				conditions: [
					{
						element: '[name="_eg_album_data[config][lightbox_theme]"]',
						type: 'value',
						operator: 'array',
						condition: [ 'base', 'captioned', 'polaroid', 'showcase', 'sleek', 'subtle' ]
					},
					{
						element: '[name="_eg_album_data[config][thumbnails]"]',
						type: 'checked',
						operator: 'is'
					}
				],
				actions: {
					if: {
						element: '#envira-config-thumbnails-position-box',
						action: 'show'
					},
					else: {
						element: '#envira-config-thumbnails-position-box',
						action: 'hide'
					}
				}
			},
			{	// Thumbnail Elements Independant of Theme
				conditions: [
					{
						element: '[name="_eg_album_data[config][thumbnails]"]',
						type: 'checked',
						operator: 'is'
					}
				],
				actions: {
					if: {
						element: '#envira-config-thumbnails-height-box, #envira-config-thumbnails-width-box',
						action: 'show'
					},
					else: {
						element: '#envira-config-thumbnails-height-box, #envira-config-thumbnails-width-box',
						action: 'hide'
					}
				}
			},
			{	// Justified Gallery
				conditions: {
					element: '[name="_eg_album_data[config][columns]"]',
					type: 'value',
					operator: 'array',
					condition: [ '0' ]
				},
				actions: {
					if: [
						{
							element: '#envira-config-standard-settings-box, #envira-config-album-theme-box',
							action: 'hide'
						},
						{
							element: '#envira-config-justified-settings-box, #envira-config-title-automattic-box',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-config-standard-settings-box, #envira-config-album-theme-box',
							action: 'show'
						},
						{
							element: '#envira-config-justified-settings-box, #envira-config-title-automattic-box',
							action: 'hide'
						}
					]
				}
			},
			{	// Album Label
				conditions: {
					element: '[name="_eg_album_data[config][back]"]',
					type: 'checked',
					operator: 'is'
				},
				actions: {
					if: [
						{
							element: '#envira-config-back-label-box',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-config-back-label-box',
							action: 'hide'
						}
					]
				}
			},
			{	// Album Description
				conditions: {
					element: '[name="_eg_album_data[config][description_position]"]',
					type: 'value',
					operator: 'array',
					condition: [ '0' ]
				},
				actions: {
					if: [
						{
							element: '#envira-config-description-box',
							action: 'hide'
						}
					],
					else: [
						{
							element: '#envira-config-description-box',
							action: 'show'
						}
					]
				}
			},
			{	// Album Sorting
				conditions: {
					element: '[name="_eg_album_data[config][sorting]"]',
					type: 'value',
					operator: 'array',
					condition: [ '0' ]
				},
				actions: {
					if: [
						{
							element: '#envira-config-sorting-direction-box',
							action: 'hide'
						}
					],
					else: [
						{
							element: '#envira-config-sorting-direction-box',
							action: 'show'
						}
					]
				}
			},
			{	// Album Gallery Lightbox
				conditions: {
					element: '[name="_eg_album_data[config][lightbox]"]',
					type: 'checked',
					operator: 'is'
				},
				actions: {
					if: [
						{
							element: '#envira-lightbox-settings, #envira-thumbnails-settings',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-lightbox-settings, #envira-thumbnails-settings',
							action: 'hide'
						}
					]
				}
			},
			{	// Album Gallery Arrows
				conditions: [
					{
						element: '[name="_eg_album_data[config][arrows]"]',
						type: 'checked',
						operator: 'is'
					},
					{
						element: '[name="_eg_album_data[config][lightbox_theme]"]',
						type: 'value',
						operator: 'array',
						condition: [ 'base', 'captioned', 'polaroid', 'showcase', 'sleek', 'subtle' ]
					}
				],
				actions: {
					if: [
						{
							element: '#envira-config-lightbox-arrows-position-box',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-config-lightbox-arrows-position-box',
							action: 'hide'
						}
					]
				}
			},
			{	// Album Gallery Toolbar
				conditions: [
					{
						element: '[name="_eg_album_data[config][toolbar]"]',
						type: 'checked',
						operator: 'is'
					},
					{
						element: '[name="_eg_album_data[config][lightbox_theme]"]',
						type: 'value',
						operator: 'array',
						condition: [ 'base', 'captioned', 'polaroid', 'showcase', 'sleek', 'subtle' ]
					}
				],
				actions: {
					if: [
						{
							element: '#envira-config-lightbox-toolbar-title-box, #envira-config-lightbox-toolbar-position-box',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-config-lightbox-toolbar-title-box, #envira-config-lightbox-toolbar-position-box',
							action: 'hide'
						}
					]
				}
			},
			{	// Album Mobile Images
				conditions: {
					element: '[name="_eg_album_data[config][mobile]"]',
					type: 'checked',
					operator: 'is'
				},
				actions: {
					if: [
						{
							element: '#envira-config-mobile-size-box',
							action: 'show'
						}
					],
					else: [
						{
							element: '#envira-config-mobile-size-box',
							action: 'hide'
						}
					]
				}
			},

		]
	);

} );