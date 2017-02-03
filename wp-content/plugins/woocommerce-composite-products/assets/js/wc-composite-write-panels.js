jQuery( function($) {

	function wc_cp_getEnhancedSelectFormatString() {
		var formatString = {
			formatMatches: function( matches ) {
				if ( 1 === matches ) {
					return wc_composite_admin_params.i18n_matches_1;
				}

				return wc_composite_admin_params.i18n_matches_n.replace( '%qty%', matches );
			},
			formatNoMatches: function() {
				return wc_composite_admin_params.i18n_no_matches;
			},
			formatAjaxError: function( jqXHR, textStatus, errorThrown ) {
				return wc_composite_admin_params.i18n_ajax_error;
			},
			formatInputTooShort: function( input, min ) {
				var number = min - input.length;

				if ( 1 === number ) {
					return wc_composite_admin_params.i18n_input_too_short_1;
				}

				return wc_composite_admin_params.i18n_input_too_short_n.replace( '%qty%', number );
			},
			formatInputTooLong: function( input, max ) {
				var number = input.length - max;

				if ( 1 === number ) {
					return wc_composite_admin_params.i18n_input_too_long_1;
				}

				return wc_composite_admin_params.i18n_input_too_long_n.replace( '%qty%', number );
			},
			formatSelectionTooBig: function( limit ) {
				if ( 1 === limit ) {
					return wc_composite_admin_params.i18n_selection_too_long_1;
				}

				return wc_composite_admin_params.i18n_selection_too_long_n.replace( '%qty%', limit );
			},
			formatLoadMore: function( pageNumber ) {
				return wc_composite_admin_params.i18n_load_more;
			},
			formatSearching: function() {
				return wc_composite_admin_params.i18n_searching;
			}
		};

		return formatString;
	}

	$.fn.wc_cp_chosen_select_component_options = function() {

		var component_id = $( this ).data( 'component_id' );
		var select       = $( this );
		var action       = $( this ).data( 'action' );

		if ( ! component_id ) {
			component_id = $( this ).closest( '.bto_group' ).find( 'input.group_id' ).val();
		}

		$( this ).ajaxChosen( {
			method: 	           'GET',
			url: 		           woocommerce_admin_meta_boxes.ajax_url,
			dataType: 	           'json',
			afterTypeDelay:        250,
			data:		           {
				action: 		       action,
				composite_id:          woocommerce_admin_meta_boxes.post_id,
				component_id:          component_id,
				security: 		       woocommerce_admin_meta_boxes.search_products_nonce
			}
		}, function ( data ) {
			var terms = {};

			if ( action === 'woocommerce_json_search_default_component_option' && select.val() > 0 ) {
				terms[ '0' ] = wc_composite_admin_params.i18n_no_default;
			}

			if ( action === 'woocommerce_json_search_component_options_in_scenario' ) {

				if ( select.data( 'component_optional' ) === 'yes' ) {
					if ( select.find( 'option[value="-1"]' ).length === 0 ) {
						terms[ '-1' ] = wc_composite_admin_params.i18n_none;
					}
				}

				if ( select.find( 'option[value="0"]' ).length === 0 ) {
					terms[ '0' ] = wc_composite_admin_params.i18n_all;
				}
			}

			$.each( data, function ( i, val ) {
				if ( select.find( 'option[value="' + i + '"]' ).length === 0 ) {
					terms[i] = val;
				}
			} );

			return terms;
		} );

		if ( action === 'woocommerce_json_search_component_options_in_scenario' ) {
			$( this ).on( 'chosen:showing_dropdown', function() {

				if ( select.data( 'component_optional' ) === 'yes' ) {
					if ( select.find( 'option[value="-1"]' ).length === 0 ) {
						$( '<option />' ).attr( 'value', '-1' ).html( wc_composite_admin_params.i18n_none ).prependTo( select );
					}
				}

				if ( select.find( 'option[value="0"]' ).length === 0 ) {
					$( '<option />' ).attr( 'value', '0' ).html( wc_composite_admin_params.i18n_all ).prependTo( select );
				}

				select.trigger( 'chosen:updated' );
			} );
		}
	};

	$.fn.wc_cp_chosen_select_products = function() {

		var select = $( this );

		$( this ).ajaxChosen( {
			method: 		'GET',
			url: 			woocommerce_admin_meta_boxes.ajax_url,
			dataType: 		'json',
			afterTypeDelay: 250,
			data: 			{
				action: 		'woocommerce_json_search_products',
				security: 		woocommerce_admin_meta_boxes.search_products_nonce
			}
		}, function ( data ) {

			var terms = {};

		    $.each( data, function ( i, val ) {
		    	if ( select.find( 'option[value="' + i + '"]' ).length === 0 ) {
		        	terms[i] = val;
		        }
		    } );

		    return terms;
		} );
	};

	$.fn.wc_cp_select2 = function() {

		$( this ).find( ':input.wc-enhanced-select' ).filter( ':not(.enhanced)' ).each( function() {
			var select2_args = $.extend({
				minimumResultsForSearch: 10,
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' )
			}, wc_cp_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );
	};

	$.fn.wc_cp_select2_products = function() {

		$( this ).find( ':input.wc-product-search' ).filter( ':not(.enhanced)' ).each( function() {
			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
			        url:         wc_enhanced_select_params.ajax_url,
			        dataType:    'json',
			        quietMillis: 250,
			        data: function( term, page ) {
			            return {
							term:     term,
							action:   $( this ).data( 'action' ) || 'woocommerce_json_search_products',
							security: wc_enhanced_select_params.search_products_nonce
			            };
			        },
			        results: function( data, page ) {
			        	var terms = [];
				        if ( data ) {
							$.each( data, function( id, text ) {
								terms.push( { id: id, text: text } );
							});
						}
			            return { results: terms };
			        },
			        cache: true
			    }
			};

			if ( $( this ).data( 'multiple' ) === true ) {
				select2_args.multiple = true;
				select2_args.initSelection = function( element, callback ) {
					var data     = $.parseJSON( element.attr( 'data-selected' ) );
					var selected = [];

					$( element.val().split( ',' ) ).each( function( i, val ) {
						selected.push( { id: val, text: data[ val ] } );
					});
					return callback( selected );
				};
				select2_args.formatSelection = function( data ) {
					return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
				};
			} else {
				select2_args.multiple = false;
				select2_args.initSelection = function( element, callback ) {
					var data = {id: element.val(), text: element.attr( 'data-selected' )};
					return callback( data );
				};
			}

			select2_args = $.extend( select2_args, wc_cp_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );
	};

	$.fn.wc_cp_select2_component_options = function() {

		$( this ).find( ':input.wc-component-options-search' ).filter( ':not(.enhanced)' ).each( function() {

			var component_id = $( this ).data( 'component_id' );
			var action       = $( this ).data( 'action' );
			var select       = $( this );

			if ( ! component_id ) {
				component_id = $( this ).closest( '.bto_group' ).find( 'input.group_id' ).val();
			}

			var select2_args = {
				allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
				placeholder: $( this ).data( 'placeholder' ),
				minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '3',
				escapeMarkup: function( m ) {
					return m;
				},
				ajax: {
			        url:         wc_enhanced_select_params.ajax_url,
			        dataType:    'json',
			        quietMillis: 250,
			        data: function( term, page ) {
			            return {
							term:           term,
							action:         action,
							composite_id:   woocommerce_admin_meta_boxes.post_id,
							component_id:   component_id,
							security:       woocommerce_admin_meta_boxes.search_products_nonce
			            };
			        },
			        results: function( data, page ) {
			        	var terms = [];

			        	if ( action === 'woocommerce_json_search_component_options_in_scenario' ) {

							if ( select.data( 'component_optional' ) === 'yes' ) {
								if ( select.find( 'option[value="-1"]' ).length === 0 ) {
									terms.push( { id: '-1', text: wc_composite_admin_params.i18n_none } );
								}
							}

							if ( select.find( 'option[value="0"]' ).length === 0 ) {
								terms.push( { id: '0', text: wc_composite_admin_params.i18n_all } );
							}
						}

				        if ( data ) {
							$.each( data, function( id, text ) {
								terms.push( { id: id, text: text } );
							} );
						}
			            return { results: terms };
			        },
			        cache: true
			    }
			};

			if ( $( this ).data( 'multiple' ) === true ) {
				select2_args.multiple = true;
				select2_args.initSelection = function( element, callback ) {
					var data     = $.parseJSON( element.attr( 'data-selected' ) );
					var selected = [];

					$( element.val().split( ',' ) ).each( function( i, val ) {
						selected.push( { id: val, text: data[ val ] } );
					});
					return callback( selected );
				};
				select2_args.formatSelection = function( data ) {
					return '<div class="selected-option" data-id="' + data.id + '">' + data.text + '</div>';
				};
			} else {
				select2_args.multiple = false;
				select2_args.initSelection = function( element, callback ) {
					var data = {id: element.val(), text: element.attr( 'data-selected' )};
					return callback( data );
				};
			}

			select2_args = $.extend( select2_args, wc_cp_getEnhancedSelectFormatString() );

			$( this ).select2( select2_args ).addClass( 'enhanced' );
		} );

	};

	var wc_cp_block_params = {};

	if ( wc_composite_admin_params.is_wc_version_gte_2_3 === 'yes' ) {
		wc_cp_block_params = {
			message:    null,
			overlayCSS: {
				background: '#fff',
				opacity:    0.6
			}
		};
	} else {
		wc_cp_block_params = {
			message:    null,
			overlayCSS: {
				background: '#fff url(' + woocommerce_admin_meta_boxes.plugin_url + '/assets/images/ajax-loader.gif) no-repeat center',
				opacity:    0.6
			}
		};
	}

	// Composite type move stock msg up.
	$( '.composite_stock_msg' ).appendTo( '._manage_stock_field .description' );

	// Hide the default "Sold Individually" field.
	$( '#_sold_individually' ).closest( '.form-field' ).addClass( 'hide_if_composite' );

	// Hide the "Grouping" field.
	$( '#linked_product_data .grouping.show_if_simple, #linked_product_data .form-field.show_if_grouped' ).addClass( 'hide_if_composite' );

	// Simple type options are valid for bundles.
	$( '.show_if_simple:not(.hide_if_composite)' ).addClass( 'show_if_composite' );

	if ( typeof woocommerce_admin_meta_boxes === 'undefined' ) {
		woocommerce_admin_meta_boxes = woocommerce_writepanel_params;
	}

	// Composite type specific options.
	$( 'body' ).on( 'woocommerce-product-type-change', function( event, select_val, select ) {

		if ( select_val === 'composite' ) {

			$( '.show_if_external' ).hide();
			$( '.show_if_composite' ).show();

			$( 'input#_manage_stock' ).change();

			$( '#_regular_price' ).val( $( '#_wc_cp_base_regular_price' ).val() ).change();
			$( '#_sale_price' ).val( $( '#_wc_cp_base_sale_price' ).val() ).change();
		}

	} );

	$( 'select#product-type' ).change();

	// Downloadable support.
	$( 'input#_downloadable' ).change( function() {
		$( 'select#product-type' ).change();
	});

	// Layout selection.
	$( '#bto_product_data .bundle_group .bto_layouts' ).on( 'click', '.bto_layout_label', function() {

		$( this ).closest( '.bto_layouts' ).find( '.selected' ).removeClass( 'selected' );
		$( this ).addClass( 'selected' );

	} );


	/*------------------------------------*/
	/*  Components                        */
	/*------------------------------------*/

	// Subsubsub navigation.

	$( '#bto_product_data .config_group' )

		.on( 'click', '.subsubsub a', function() {

			$( this ).closest( '.subsubsub' ).find( 'a' ).removeClass( 'current' );
			$( this ).addClass( 'current' );

			$( this ).closest( '.bto_group_data' ).find( '.tab_group' ).addClass( 'tab_group_hidden' );

			var tab = $( this ).data( 'tab' );

			$( this ).closest( '.bto_group_data' ).find( '.tab_group_' + tab ).removeClass( 'tab_group_hidden' );

			return false;

		} )

		// Component Remove.

		.on( 'click', 'button.remove_row', function() {

			var $parent = $( this ).parent().parent();

			$parent.find('*').off();
			$parent.remove();
			group_row_indexes();

		} )

		// Component Keyup.

		.on( 'keyup', 'input.group_title', function() {
			$( this ).closest( '.bto_group' ).find( 'h3 .group_name' ).text( $( this ).val() );
		} )

		// Component Expand.

		.on( 'click', '.expand_all', function() {
			$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_group_data' ).show();
			return false;
		} )

		// Component Close.

		.on( 'click', '.close_all', function() {
			$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_group_data' ).hide();
			return false;
		} )

		// Query type.

		.on( 'change', 'select.bto_query_type', function() {

			var query_type = $( this ).val();

			$( this ).closest( '.bto_group' ).find( '.bto_query_type_selector' ).hide();
			$( this ).closest( '.bto_group' ).find( '.bto_query_type_' + query_type ).show();

		} )

		// Priced individually.

		.on( 'change', '.group_priced_individually input', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( '.bto_group' ).find( '.group_discount' ).show();
			} else {
				$( this ).closest( '.bto_group' ).find( '.group_discount' ).hide();
			}
		} )

		// Filters.

		.on( 'change', '.group_show_filters input', function() {

			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( '.bto_group' ).find( '.group_filters' ).show();
			} else {
				$( this ).closest( '.bto_group' ).find( '.group_filters' ).hide();
			}

		} );

	$( '#bto_product_data .config_group select.bto_query_type' ).change();
	$( '#bto_product_data .config_group .group_priced_individually input' ).change();
	$( '#bto_product_data .config_group .group_show_filters input' ).change();

	// Ajax Chosen Single Product Selector.

	if ( wc_composite_admin_params.is_wc_version_gte_2_3 === 'yes' ) {

		$( '#bto_product_data .config_group, #bto_scenario_data .bto_scenarios' ).wc_cp_select2_component_options();

	} else {

		$( '#bto_product_data .config_group select.ajax_chosen_select_component_options, #bto_scenario_data select.ajax_chosen_select_component_options' ).each( function() {
			$( this ).wc_cp_chosen_select_component_options();
		} );
	}

	// Component Add.

	var bto_groups_metabox_count = $( '.bto_groups .bto_group' ).size();

	$( '#bto_product_data' ).on( 'click', 'button.add_bto_group', function() {

		$( '#bto_product_data' ).block( wc_cp_block_params );

		bto_groups_metabox_count++;

		var data = {
			action: 	'woocommerce_add_composite_component',
			post_id: 	woocommerce_admin_meta_boxes.post_id,
			id: 		bto_groups_metabox_count,
			security: 	wc_composite_admin_params.add_component_nonce
		};

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {

			$( '#bto_config_group_inner .bto_groups' ).append( response );

			var added = $( '#bto_config_group_inner .bto_groups .bto_group' ).last();

			if ( wc_composite_admin_params.is_wc_version_gte_2_3 === 'yes' ) {

				added.wc_cp_select2();
				added.wc_cp_select2_products();

			} else {

				added.find( '.ajax_chosen_select_products' ).wc_cp_chosen_select_products();
				added.find( '.chosen_select' ).chosen();
			}

			added.find( 'select.bto_query_type' ).change();

			added.find( '.group_show_filters input' ).change();
			added.find( '.group_priced_individually input' ).change();

			added.find( '.woocommerce-help-tip' ).tipTip( {
				'attribute' : 'data-tip',
				'fadeIn' : 50,
				'fadeOut' : 50,
				'delay' : 200
			} );

			$( '#bto_product_data' ).unblock();
			added.trigger( 'woocommerce_bto_component_added' );

		} );

		return false;

	} );

	// Set component image.

	var component_image_frame_data = {
		image_frame: false,
		$button:     false
	};

	$( '#bto_product_data' ).on( 'click', '.upload_component_image_button', function() {

		component_image_frame_data.$button = $( this );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( component_image_frame_data.image_frame ) {
			component_image_frame_data.image_frame.open();
			return;
		}

		// Create the media frame.
		component_image_frame_data.image_frame = wp.media( {

			// Set the title of the modal.
			title: wc_composite_admin_params.i18n_choose_component_image,
			button: {
				text: wc_composite_admin_params.i18n_set_component_image
			},
			states: [
				new wp.media.controller.Library( {
					title: wc_composite_admin_params.i18n_choose_component_image,
					filterable: 'all'
				} )
			]
		} );

		// When an image is selected, run a callback.
		component_image_frame_data.image_frame.on( 'select', function () {

			var attachment = component_image_frame_data.image_frame.state().get( 'selection' ).first().toJSON(),
				url        = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;

			component_image_frame_data.$button.addClass( 'has_image' );
			component_image_frame_data.$button.closest( '.component_image' ).find( '.remove_component_image_button' ).addClass( 'has_image' );
			component_image_frame_data.$button.find( 'input' ).val( attachment.id ).change();
			component_image_frame_data.$button.find( 'img' ).eq( 0 ).attr( 'src', url );
		} );

		// Finally, open the modal.
		component_image_frame_data.image_frame.open();
	} );

	// Unset component image.

	$( '#bto_product_data' ).on( 'click', '.remove_component_image_button', function() {

		var $button         = $( this ),
			$option_wrapper = $button.closest( '.component_image' ),
			$upload_button  = $option_wrapper.find( '.upload_component_image_button' );

		event.preventDefault();

		$upload_button.removeClass( 'has_image' );
		$button.removeClass( 'has_image' );
		$option_wrapper.find( 'input' ).val( '' ).change();
		$upload_button.find( 'img' ).eq( 0 ).attr( 'src', wc_composite_admin_params.wc_placeholder_img_src );
	} );

	/*------------------------------------*/
	/* Scenarios                          */
	/*------------------------------------*/

	// Scenario Remove.

	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'click', 'button.remove_row', function() {

		var $parent = $( this ).parent().parent();

		$parent.find('*').off();
		$parent.remove();
		scenario_row_indexes();

	} );

	// Scenario Keyup.

	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'keyup', 'input.scenario_title', function() {
		$( this ).closest( '.bto_scenario' ).find( 'h3 .scenario_name' ).text( $( this ).val() );
	} );

	// Scenario Expand.

	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'click', '.expand_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_scenario_data' ).show();
		return false;
	} );

	// Scenario Close.

	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'click', '.close_all', function() {
		$( this ).closest( '.wc-metaboxes-wrapper' ).find( '.wc-metabox > .bto_scenario_data' ).hide();
		return false;
	} );

	// Exclude option modifier.

	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'change', 'select.bto_scenario_exclude', function() {

		if ( $( this ).val() === 'masked' ) {
			$( this ).closest( '.bto_scenario_selector' ).find( '.bto_scenario_selector_inner' ).slideUp( 200 );
		} else {
			$( this ).closest( '.bto_scenario_selector' ).find( '.bto_scenario_selector_inner' ).slideDown( 200 );
		}

	} );

	// Scenario Add.

	var bto_scenarios_metabox_count = $( '.bto_scenarios .bto_scenario' ).size();

	$( '#bto_scenario_data' ).on( 'click', 'button.add_bto_scenario', function () {

		$( '#bto_scenario_data' ).block( wc_cp_block_params );

		bto_scenarios_metabox_count++;

		var data = {
			action: 	'woocommerce_add_composite_scenario',
			post_id: 	woocommerce_admin_meta_boxes.post_id,
			id: 		bto_scenarios_metabox_count,
			security: 	wc_composite_admin_params.add_scenario_nonce
		};

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function ( response ) {

			$( '#bto_scenarios_inner .bto_scenarios' ).append( response );

			var added = $( '.bto_scenario_' + bto_scenarios_metabox_count );

			if ( wc_composite_admin_params.is_wc_version_gte_2_3 === 'yes' ) {

				added.wc_cp_select2();
				added.wc_cp_select2_component_options();

			} else {

				added.find( '.bto_scenario_ids' ).each( function() {
					$( this ).chosen();
				} );

				added.find( '.ajax_chosen_select_component_options' ).each( function() {
					$( this ).wc_cp_chosen_select_component_options();
				} );
			}

			added.find( '.tips, .woocommerce-help-tip' ).tipTip( {
				'attribute' : 'data-tip',
				'fadeIn' : 50,
				'fadeOut' : 50,
				'delay' : 200
			} );

			$( '#bto_scenario_data' ).unblock();
			added.trigger( 'woocommerce_bto_scenario_added' );

		} );

		return false;
	} );

	// "Hide Components" scenario action.
	$( '#bto_scenario_data #bto_scenarios_inner' ).on( 'change', '.toggle_conditional_components input', function() {

		if ( $( this ).is( ':checked' ) ) {
			$( this ).closest( '.scenario_action_conditional_components_group' ).find( '.action_components' ).slideDown( 200 );
		} else {
			$( this ).closest( '.scenario_action_conditional_components_group' ).find( '.action_components' ).slideUp( 200 );
		}

	} );

	// Init metaboxes.

	init_bto_composition_metaboxes();
	init_bto_scenario_metaboxes();

	function group_row_indexes() {
		$( '.bto_groups .bto_group' ).each( function( index, el ){
			$( '.group_position', el ).val( parseInt( $(el).index( '.bto_groups .bto_group' ) ) );
		} );
	}

	function init_bto_composition_metaboxes() {

		// Initial order.
		var bto_groups = $( '.bto_groups' ).find( '.bto_group' ).get();

		bto_groups.sort( function(a, b) {
		   var compA = parseInt( $(a).attr( 'rel' ) );
		   var compB = parseInt( $(b).attr( 'rel' ) );
		   return ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
		} );

		$(bto_groups).each( function( idx, itm ) {
			$( '.bto_groups' ).append(itm);
		} );

		// Component ordering
		$( '.bto_groups' ).sortable( {
			items:'.bto_group',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css( 'background-color','#f6f6f6' );
			},
			stop:function(event,ui){
				ui.item.removeAttr( 'style' );
				group_row_indexes();
			}
		} );
	}

	function scenario_row_indexes() {
		$( '.bto_scenarios .bto_scenario' ).each( function( index, el ) {
			$( '.scenario_position', el ).val( parseInt( $(el).index( '.bto_scenarios .bto_scenario' ) ) );
		} );
	}

	function init_bto_scenario_metaboxes() {

		// Initial order.
		var bto_scenarios = $( '.bto_scenarios' ).find( '.bto_scenario' ).get();

		bto_scenarios.sort( function( a, b ) {
		   var compA = parseInt( $(a).attr('rel') );
		   var compB = parseInt( $(b).attr('rel') );
		   return ( compA < compB ) ? -1 : ( compA > compB ) ? 1 : 0;
		} );

		$(bto_scenarios).each( function( idx, itm ) {
			$( '.bto_scenarios' ).append( itm );
		} );

		// Scenario ordering.
		$( '.bto_scenarios' ).sortable( {
			items:'.bto_scenario',
			cursor:'move',
			axis:'y',
			handle: 'h3',
			scrollSensitivity:40,
			forcePlaceholderSize: true,
			helper: 'clone',
			opacity: 0.65,
			placeholder: 'wc-metabox-sortable-placeholder',
			start:function(event,ui){
				ui.item.css( 'background-color','#f6f6f6' );
			},
			stop:function(event,ui){
				ui.item.removeAttr( 'style' );
				scenario_row_indexes();
			}
		} );

	}

	// Save bundle data and update configuration options via ajax.

	$( '.save_composition' ).on( 'click', function() {

		$( '#bto_product_data, #bto_scenario_data' ).block( wc_cp_block_params );

		$( '.bto_groups .bto_group' ).find('*').off();

		var data = {
			post_id: 		woocommerce_admin_meta_boxes.post_id,
			data:			$( '#bto_product_data, #bto_scenario_data' ).find( 'input, select, textarea' ).serialize(),
			action: 		'woocommerce_bto_composite_save',
			security: 		wc_composite_admin_params.save_composite_nonce
		};

		$.post( woocommerce_admin_meta_boxes.ajax_url, data, function( post_response ) {

			var this_page = window.location.toString();

			this_page = this_page.replace( 'post-new.php?', 'post.php?post=' + woocommerce_admin_meta_boxes.post_id + '&action=edit&' );

			$.get( this_page, function( response ) {

				$( '#bto_product_data #bto_config_group_inner' ).html( $(response).find( '#bto_config_group_inner' ).html() );
				$( '#bto_scenario_data #bto_scenarios_inner' ).html( $(response).find( '#bto_scenarios_inner' ).html() );

				init_bto_composition_metaboxes();
				init_bto_scenario_metaboxes();

				$( '#bto_product_data .woocommerce-help-tip, #bto_scenario_data .woocommerce-help-tip, #bto_scenario_data .tips' ).tipTip( {
					'attribute' : 'data-tip',
					'fadeIn' : 50,
					'fadeOut' : 50,
					'delay' : 200
				} );

				$( '#bto_config_group_inner .bto_groups, #bto_scenarios_inner .bto_scenarios' ).find( '.wc-metabox-content' ).hide();

				$( '#bto_config_group_inner select.bto_query_type' ).change();

				$( '#bto_config_group_inner .group_priced_individually input' ).change();

				$( '#bto_config_group_inner .group_show_filters input' ).change();

				if ( wc_composite_admin_params.is_wc_version_gte_2_3 === 'yes' ) {

					$( '#bto_config_group_inner, #bto_scenarios_inner' ).wc_cp_select2();
					$( '#bto_config_group_inner' ).wc_cp_select2_products();
					$( '#bto_config_group_inner, #bto_scenarios_inner' ).wc_cp_select2_component_options();

				} else {

				    $( '#bto_config_group_inner select.chosen_select, #bto_scenarios_inner select.chosen_select' ).chosen();

				    $( '#bto_config_group_inner .bto_group_data .bto_selector select' ).each( function() {
				    	$( this ).wc_cp_chosen_select_products();
				    } );

					$( '#bto_config_group_inner .bto_group_data .default_selector select.ajax_chosen_select_component_options, #bto_scenario_data select.ajax_chosen_select_component_options' ).each( function() {
						$( this ).wc_cp_chosen_select_component_options();
					} );
				}

				if ( post_response.length > 0 ) {
					$.each( post_response, function( index, part ) {
						window.alert( part );
					} );
				}

				$( '#bto_product_data, #bto_scenario_data' ).unblock();
			} );

		}, 'json' );

	} );

});
