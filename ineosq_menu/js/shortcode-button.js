(function($) {
	"use strict";
	if ( typeof ineosq_shortcode_button != 'undefined' ) {
		var win;

		tinymce.create(
			'tinymce.plugins.INEOSQButton',
			{
				/**
				 * Initializes the plugin, this will be executed after the plugin has been created.
				 * This call is done before the editor instance has finished it's initialization so use the onInit event
				 * of the editor instance to intercept that event.
				 *
				 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
				 * @param {string} url Absolute URL to where the plugin is located.
				 */
				init : function( ed, url ) {
					ed.addButton(
						'add_ineosq_shortcode',
						{
							title : ineosq_shortcode_button.title,
							classes: 'ineosq_shortcode_button widget btn',
							icon: 'icon ineosqicons ineosqicons-shortcode',
							text: ineosq_shortcode_button.label,
							onclick: function() {

								win = ed.windowManager.open(
									{
										width: 400,
										height: 400,
										inline: true,
										title: ineosq_shortcode_button.title,
										body: {
											id : 'ineosq-shortcode-content',
											type: 'container',
											classes: 'ineosq-shortcode',
											html: $( '#ineosq_shortcode_popup' ).html()
										},
										buttons: [{
											text: 'Insert',
											classes: 'button-primary primary ineosq_shortcode_insert',
											onclick: function( e ) {
												var shortcode = $( '.mce-container-body #ineosq_shortcode_display' ).text();
												if ( '' != shortcode ) {
													/* insert shortcode to tinymce */
													ed.insertContent( shortcode );
												}
												ed.windowManager.close();
											},
										},
										{
											text: 'Cancel',
											onclick: 'close'
										}],

									}
								);
								var current_object = '.mce-container-body';
								var select_count   = $( current_object + ' select#ineosq_shortcode_select option' ).length;
								if ( 1 == select_count ) {
									$( current_object + ' #ineosq_shortcode_select_plugin' ).hide();
								}

								var plugin = $( current_object + ' #ineosq_shortcode_select option:selected' ).val();
								$( current_object + ' #ineosq_shortcode_content > div' ).hide();
								$( current_object + ' #ineosq_shortcode_content > #' + plugin ).show();

								if ( $( current_object + ' #ineosq_shortcode_content > #' + plugin + ' .ineosq_default_shortcode' ).length > 0 ) {
									$( current_object + ' #ineosq_shortcode_display' ).text( $( current_object + ' #ineosq_shortcode_content > #' + plugin + ' .ineosq_default_shortcode' ).val() );
								}

								$( current_object + ' #ineosq_shortcode_select' ).on(
									'change',
									function() {
										var plugin = $( current_object + ' #ineosq_shortcode_select option:selected' ).val();
										$( current_object + ' #ineosq_shortcode_content > div' ).hide();
										$( current_object + ' #ineosq_shortcode_content > #' + plugin ).show();
										if ( $( current_object + ' #ineosq_shortcode_content > #' + plugin + ' .ineosq_default_shortcode' ).length > 0 ) {
											$( current_object + ' #ineosq_shortcode_display' ).text( $( current_object + ' #ineosq_shortcode_content > #' + plugin + ' .ineosq_default_shortcode' ).val() );
										} else {
											$( current_object + ' #ineosq_shortcode_display' ).text( '' );
										}
									}
								);

								$.each(
									ineosq_shortcode_button.function_name,
									function( index, value ) {
										eval( value + '();' );
									}
								);
							}
						}
					);
				},

				/**
				 * Creates control instances based in the incomming name. This method is normally not
				 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
				 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
				 * method can be used to create those.
				 *
				 * @param {String} n Name of the control to create.
				 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
				 * @return {tinymce.ui.Control} New control instance or null if no control was created.
				 */
				createControl : function(n, cm) {
					return null;
				},

				/**
				 * Returns information about the plugin as a name/value array.
				 * The current keys are longname, author, authorurl, infourl and version.
				 *
				 * @return {Object} Name/value array containing information about the plugin.
				 */
				getInfo : function() {
					return {
						longname : 'INEOSQ Shortcode Buttons',
						author : 'INEOSQ',
						authorurl : 'https://ineosq.com',
						infourl : '',
						version : "0.1"
					};
				}
			}
		);

		/* Register plugin */
		tinymce.PluginManager.add( 'add_ineosq_shortcode', tinymce.plugins.INEOSQButton );
	}
})( jQuery );
