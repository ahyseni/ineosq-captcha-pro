function ineosq_show_settings_notice() {
	"use strict";
	(function($) {
		$( '.updated.fade:not(.ineosq_visible), .error:not(.ineosq_visible)' ).css( 'display', 'none' );
		$( '#ineosq_save_settings_notice' ).css( 'display', 'block' );
	})( jQuery );
}

(function($) {
	"use strict";
	$( document ).ready(
		function() {
			/**
			* add notice about changing on the settings page
			*/
			$( '.ineosq_form input, .ineosq_form textarea, .ineosq_form select' ).on(
				"change paste select",
				function() {
					if ( $( this ).attr( 'type' ) != 'submit' && ! $( this ).hasClass( 'ineosq_no_bind_notice' ) ) {
						ineosq_show_settings_notice();
					};
				}
			);
			$( '.ineosq_save_anchor' ).on(
				"click",
				function( event ) {
					event.preventDefault();
					$( '.ineosq_form #ineosq-submit-button' ).click();
				}
			);

			/* custom code */

			if ( 'function' == typeof wp.CodeMirror || 'function' == typeof CodeMirror ) {
				var CodeMirrorFunc = ( typeof wp.CodeMirror != 'undefined' ) ? wp.CodeMirror : CodeMirror;
				if ( $( '#ineosq_newcontent_css' ).length > 0 ) {
					var editor = CodeMirrorFunc.fromTextArea(
						document.getElementById( 'ineosq_newcontent_css' ),
						{
							mode: "css",
							theme: "default",
							styleActiveLine: true,
							matchBrackets: true,
							lineNumbers: true,
							addModeClass: 'ineosq_newcontent_css'
							}
					);
				}

				if ( $( '#ineosq_newcontent_php' ).length > 0 ) {
					var editor = CodeMirrorFunc.fromTextArea(
						document.getElementById( "ineosq_newcontent_php" ),
						{
							mode: 'text/x-php',
							styleActiveLine: true,
							matchBrackets: true,
							lineNumbers: true,
							}
					);
					/* disable lines */
					editor.markText( {ch:0,line:0}, {ch:0,line:5}, { readOnly: true, className: 'ineosq-readonly' } );
				}

				if ( $( '#ineosq_newcontent_js' ).length > 0 ) {
					var editor = CodeMirrorFunc.fromTextArea(
						document.getElementById( "ineosq_newcontent_js" ),
						{
							mode: 'javascript',
							styleActiveLine: true,
							matchBrackets: true,
							lineNumbers: true,
							}
					);
				}
			}

			/* banner to settings */
			$( '.ineosq_banner_to_settings_joint .ineosq-details' ).addClass( 'hidden' ).removeClass( 'hide-if-js' );
			$( '.ineosq_banner_to_settings_joint .ineosq-more-links' ).on(
				"click",
				function( event ) {
					event.preventDefault();
					if ( $( '.ineosq_banner_to_settings_joint .ineosq-less' ).hasClass( 'hidden' ) ) {
						$( '.ineosq_banner_to_settings_joint .ineosq-less, .ineosq_banner_to_settings_joint .ineosq-details' ).removeClass( 'hidden' );
						$( '.ineosq_banner_to_settings_joint .ineosq-more' ).addClass( 'hidden' );
					} else {
						$( '.ineosq_banner_to_settings_joint .ineosq-less, .ineosq_banner_to_settings_joint .ineosq-details' ).addClass( 'hidden' );
						$( '.ineosq_banner_to_settings_joint .ineosq-more' ).removeClass( 'hidden' );
					}
				}
			);

				/* help tooltips */
			if ( $( '.ineosq_help_box' ).length > 0 ) {
				if ( $( 'body' ).hasClass( 'rtl' ) ) {
					var current_position = { my: "right top+15", at: "right bottom" };
				} else {
					var current_position = { my: "left top+15", at: "left bottom" };
				}
				$( document ).tooltip(
					{
						items: $( '.ineosq_help_box' ),
						content: function() {
							return $( this ).find( '.ineosq_hidden_help_text' ).html()
						},
						show: null, /* show immediately */
						tooltipClass: "ineosq-tooltip-content",
						position: current_position,
						open: function( event, ui ) {
							if ( typeof( event.originalEvent ) === 'undefined' ) {
								return false;
							}
							if ( $( event.originalEvent.target ).hasClass( 'ineosq-auto-width' ) ) {
								ui.tooltip.css( "max-width", "inherit" );
							}
							var $id = $( ui.tooltip ).attr( 'id' );
							/* close any lingering tooltips */
							$( 'div.ui-tooltip' ).not( '#' + $id ).remove();
						},
						close: function( event, ui ) {
							ui.tooltip.hover(
								function() {
									$( this ).stop( true ).fadeTo( 200, 1 );
								},
								function() {
									$( this ).fadeOut(
										'200',
										function() {
											$( this ).remove();
										}
									);
								}
							);
						}
					}
				);
			}

			/**
			 * Handle the styling of the "Settings" tab on the plugin settings page
			 */
			var tabs = $( '#ineosq_settings_tabs_wrapper' );
			if ( tabs.length ) {
				var current_tab_field = $( 'input[name="ineosq_active_tab"]' ),
					prevent_tabs_change = false,
					active_tab = current_tab_field.val();
				if ( '' == active_tab ) {
					var active_tab_index = 0;
				} else {
					var active_tab_index = $( '#ineosq_settings_tabs li[data-slug=' + active_tab + ']' ).index();
				}
	
				$( '.ineosq_tab' ).css( 'min-height', $( '#ineosq_settings_tabs' ).css( 'height' ) );
	
				/* jQuery tabs initialization */
				tabs.tabs({
					active: active_tab_index
				}).on( "tabsactivate", function( event, ui ) {
					if ( ! prevent_tabs_change ) {
						active_tab = ui.newTab.data( 'slug' );
						current_tab_field.val( active_tab );
					}
					prevent_tabs_change = false;
				});
				$( '.ineosq_trigger_tab_click' ).on( 'click', function () {
					$( '#ineosq_settings_tabs a[href="' + $( this ).attr( 'href' ) + '"]' ).click();
				});
			}
			/**
			 * Hide content for options on the plugin settings page
			 */
			var options = $( '.ineosq_option_affect' );
			if ( options.length ) {
				options.each( function() {
					var element = $( this );
					if ( element.is( ':selected' ) || element.is( ':checked' ) ) {
						$( element.data( 'affect-show' ) ).show();
						$( element.data( 'affect-hide' ) ).hide();
					} else {
						$( element.data( 'affect-show' ) ).hide();
						$( element.data( 'affect-hide' ) ).show();
					}
					if ( element.is( 'option' ) ) {
						element.parent().on( 'change', function() {
							var affect_hide = element.data( 'affect-hide' ),
								affect_show = element.data( 'affect-show' );
							if ( element.is( ':selected' ) ) {
								$( affect_show ).show();
								$( affect_hide ).hide();
							} else {
								$( affect_show ).hide();
								$( affect_hide ).show();
							}
						});
					} else {
						element.on( 'change', function() {
							var affect_hide = element.data( 'affect-hide' ),
								affect_show = element.data( 'affect-show' );
							if ( element.is( ':selected' ) || element.is( ':checked' ) ) {
								$( affect_show ).show();
								$( affect_hide ).hide();
							} else {
								$( affect_show ).hide();
								$( affect_hide ).show();
							}
						});
					}
				});
			}
		}
	);
})(jQuery);