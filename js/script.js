/*!
 * License: Proprietary
 * License URI: https://ineosq.com/end-user-license-agreement/
 */
( function( $ ) {
	$( document ).ready( function() {
		/* include color-picker */
		if ( $.fn.wpColorPicker ) {
			$( '.cptch_color_field' ).wpColorPicker();
		}
    	$( '.cptch-settings-accordion' ).accordion(
			{
				collapsible: true,
				heightStyle: "content"
			}
		);
		/*
		* Hide/show checkboxes for network settings on network settings page
		*/
		if ( $( 'input[name="cptch_network_apply"]' ).length ) {
			$( 'input[name="cptch_network_apply"]' ).on( 'change', function() {
				var providers = [
					'wp_login',
					'wp_register',
					'wp_lost_password',
					'wp_comments',
					'ineosq_contact',
					'ineosq_booking',
					'ineosq_subscriber',
					'cf7_contact',
					'buddypress_register',
					'buddypress_comments',
					'buddypress_group',
					'woocommerce_register',
					'woocommerce_login',
					'woocommerce_checkout',
					'woocommerce_lost_password',
					'bbpress_new_topic_form',
					'bbpress_reply_form',
					'wpforo_login_form',
					'wpforo_register_form',
					'wpforo_reply_form',
					'wpforo_new_topic_form',
					'jetpack_contact_form',
					'mailchimp'
				];
				if ( 'all' !== $( 'input[name="cptch_network_apply"]:checked' ).val() ) {
					$( '.ineosq_network_apply_all, #cptch_network_notice' ).hide();
					if ( 'off' === $( 'input[name="cptch_network_apply"]:checked' ).val() ) {
						$( '.cptch_settings_form, .cptch_for_math_actions, .cptch_for_recognition' ).hide();

						for ( var i = 0; i < providers.length; i++ ) {
							$( '.cptch_' + providers[i] + '_related_form' ).hide();
						}
					} else{
						$( '.cptch_settings_form, .cptch_for_math_actions, .cptch_for_recognition' ).show();
						for ( var i = 0; i < providers.length; i++ ) {
							$( '.cptch_' + providers[i] + '_related_form' ).show();
						}
						cptch_hide_unused();
					}

				} else {
					$( '#cptch_network_notice, .cptch_network_settings, .ineosq_network_apply_all, .cptch_settings_form, .cptch_for_math_actions, .cptch_for_recognition' ).show();
					for ( var i = 0; i < providers.length; i++ ) {
							$( '.cptch_' + providers[i] + '_related_form' ).show();
						}
						cptch_hide_unused();
				}
			} ).trigger( 'change' );
		}
		// function for hiding uncheked blocks
		function cptch_hide_unused() {
			$.each( $( 'input[name*="[enable]"]' ), function() {
				var formName = '.' + $( this ).attr( 'id' ).replace( 'enable', 'related_form' ),
					formBlock = $( formName );

				$( this ).is( ':checked' ) ? formBlock.show() : formBlock.hide();

				$( this ).click( function() {
					$( this ).is( ':checked' ) ? formBlock.show() : formBlock.hide();
				} );
			} );
		}
		//if not network run function
		if ( -1 === location.href.indexOf( "network" ) ) {
			cptch_hide_unused();
		}

		/**
		 * Handle the styling of the "Settings" page
		 * @since 4.2.3
		 */
		var imageFormat		= $( '#cptch_operand_format_images' ),
			packageLoader	= $( '.cptch_install_package_wrap' );

		/*
		* Hide "time limit thershold" fields under all unchecked "time limit" fields
		*/
		function cptch_time_limit() {
			$( 'input[name*="[enable_time_limit]"]' ).each( function() {
				if ( ! $( this ).is( ':checked' ) ) {
					$( this ).closest( 'tr' ).nextAll( '.cptch_time_limit' ).hide();
				} else {
					$( this ).closest( 'tr' ).nextAll( '.cptch_time_limit' ).show();
				}
			} );
		}

		cptch_time_limit();
		$( 'input[name*="[enable_time_limit]"]' ).click( function() {
			if ( ! $( this ).is( ':checked' ) ) {
				$( this ).closest( 'tr' ).nextAll( '.cptch_time_limit' ).hide();
			} else {
				$( this ).closest( 'tr' ).nextAll( '.cptch_time_limit' ).show();
			}
		} );

		/*
		 * Hide all related forms on settings page
		 */


		/*
		 * Hide/show settings of the forms related with captcha
		 * depending on "general settings" is checked or not
		 */

		$( "input[name*='[use_general]']" ).change( function() {
			var rows = $( this )
					.closest( 'tr' )
					.siblings( 'tr' )
					.not( '.cptch_use_time_limit, .cptch_time_limit, .cptch_form_option_hide_from_registered, .cptch_form_option_used_packages' );


			if ( $( this ).is( ':checked' ) ) {
				rows.hide();
				$( this ).closest( 'tr' ).siblings( 'tr.cptch_use_time_limit' ).find( 'input' ).removeAttr( 'checked' );
				$( this ).closest( 'tr' ).siblings( 'tr.cptch_form_option_used_packages, tr.cptch_time_limit' ).hide();
			} else {
				rows.show();
				if ( 'none' !== $( '.cptch_images_options' ).css( 'display' ) ) {
					$( this ).closest( 'tr' ).siblings( 'tr.cptch_form_option_used_packages' ).show();
				}
			}
		 } ).trigger( 'change' );

		/*
		* Handle the displaying of notice message above lists of image packages
		*/

		function cptch_type() {

			var cptchType = $( 'input[name="cptch_type"]:checked' ).val();

			if ( 'recognition' === cptchType ) {
				$( '.cptch_for_math_actions, .cptch_for_slide' ).hide();
				$( '.cptch_for_recognition' ).show();
				imageFormat.attr( 'checked', 'checked' );
				cptchImageOptions();
				cptch_time_limit();
			} else if ( 'slide' === cptchType ) {
				$( '.cptch_for_recognition, .cptch_for_math_actions, .cptch_time_limit' ).hide();
				$( '.cptch_for_slide' ).show();
				imageFormat.removeAttr('checked' );
				cptchImageOptions();
			} else if ( 'invisible' === cptchType ){
				$( '.cptch_for_recognition, .cptch_for_math_actions, .cptch_time_limit, .cptch_for_slide' ).hide();
				imageFormat.removeAttr( 'checked' );
				cptchImageOptions();
			} else {
				$( '.cptch_for_recognition, .cptch_for_slide' ).hide();
				$( '.cptch_for_math_actions' ).show();
				cptch_time_limit();
			}
		}

		cptch_type();
		$( 'input[name="cptch_type"]' ).click( function( e ) {
			cptch_type();
		} );

		function cptchImageOptions() {
			var isChecked = imageFormat.is( ':checked' );
			if ( isChecked ) {
				$( '.cptch_images_options, .cptch_enable_to_use_several_packages' ).show();

				$( "input[name*='[use_general]']:not(:checked)" ).each( function() {
					$( this ).closest( 'tr' ).siblings( 'tr.cptch_form_option_used_packages' ).show();
				} );
			} else {
				$( '.cptch_images_options, .cptch_enable_to_use_several_packages, .cptch_form_option_used_packages' ).hide();
			}

			$( ".cptch_tabs_package_list:not(.cptch_pro_pack_tab)" ).each( function() {
				var notice = imageFormat.prev( '.cptch_enable_images_notice' );
				if ( ! notice.length ) {
					return;
				}

				if ( imageFormat.find( 'input:checked' ).length && ! isChecked ) {
					notice.show();
				} else {
					notice.hide();
				}
			} );
		}
		cptchImageOptions();
		imageFormat.click( function() {
			cptchImageOptions();
		} );

		/**
		 * Hide/show whitelist "add new form"
		 */
		$( 'button[name="cptch_show_allowlist_form"]' ).click( function() {
			$( this ).parent( 'form' ).hide();
			$( '.cptch_allowlist_form' ).show();
			return false;
		});

		/*
		* add to whitelist my ip
		*/
		$( 'input[name="cptch_add_to_allowlist_my_ip"]' ).change(function() {
			if ( $( this ).is( ':checked' ) ) {
				var reason = $( this ).parent().text();
				var my_ip = $( 'input[name="cptch_add_to_allowlist_my_ip_value"]' ).val();
				$( 'textarea[name="cptch_add_to_allowlist"]' ).val( my_ip ).attr( 'readonly', 'readonly' );
				$( 'textarea[name="cptch_add_to_allowlist_reason"]' ).val( $.trim( reason ) );
			} else {
				$( 'textarea[name="cptch_add_to_allowlist_reason"]' ).val( '' );
				$( 'textarea[name="cptch_add_to_allowlist"]' ).val( '' ).removeAttr( 'readonly' );
			}
		} );

		/**
		 * Show/hide package loader form on the "Packages" tab on the plugin settings page
		 * @since 1.6.9
		 */
		if ( packageLoader.length ) {
			var disabled = $( '.cptch_install_disabled' );
			disabled.attr( 'disabled', true );
			$( '#cptch_install_package_input' ).change(function() {
				disabled.attr( 'disabled', false );
			} );
			$( '#cptch_show_loader' ).click(function( event ) {
				event = event || window.event;
				event.preventDefault();
				if ( packageLoader.is( ':visible' ) ) {
					packageLoader.hide();
				} else {
					packageLoader.show();
				}
			} );
		}

		/**
		 * Handle the "Whitelist" on the whitelist page
		 */
		$( 'button[name="cptch_show_allowlist_form"]' ).click( function() {
			$( this ).parent( 'form' ).hide();
			$( '.cptch_allowlist_form' ).show();
			return false;
		} );

		/* Putting initial value of each textarea into data 'default-value' attr */
		$( '.cptch-add-reason-textarea' ).each( function( e ) {
			$( this ).data( 'default-value', $( this ).val() );
		} );

		$( '.cptch-add-reason-textarea' ).css( {"overflow": "hidden"} );
		/* Hiding display and edit link and showing textarea field with buttons for edit add_reason for whitelist/blacklist by click on edit link */
		$( '.cptch_edit_reason_link' ).on( "click", function( event ) {
			event.preventDefault();
			parent = $( this ).closest( 'td' );
			parent.find( '.cptch-add-reason, .cptch_edit_reason_link' ).hide();
			parent.find( '.cptch-add-reason-button' ).show();
			parent.find( '.cptch-add-reason-textarea' ).show().removeClass( 'hidden' ).trigger( 'focus' );
		} );

		/* preparing arguments and calling cptch_update_reason() function */
		$( '.cptch-add-reason-button[name=cptch_reason_submit]' ).on( "click", function( event ) {
			event.preventDefault();
			parent = $( this ).parent();
			id = $( this ).closest( 'tr' ).find( '.check-column input' ).val();
			reason = parent.find( '.cptch-add-reason-textarea' ).val();
			cptch_update_reason( id, reason );
			parent.find( '.cptch-add-reason-button, .cptch-add-reason-textarea' ).hide();
			parent.find( '.cptch-add-reason, .cptch_edit_reason_link' ).show();
		} );

		/* restoring initial value of textarea from data 'default-value' by click on cancel button */
		$( '.cptch-add-reason-button[name=cptch_reason_cancel]' ).on( "click", function( event ) {
			event.preventDefault();
			parent = $( this ).parent();
			default_data = $( this ).parent().find( '.cptch-add-reason-textarea' ).data( 'default-value' );
			parent.find( '.cptch-add-reason-textarea' ).val( default_data );
			parent.find( '.cptch-add-reason-button, .cptch-add-reason-textarea' ).hide();
			parent.find( '.cptch-add-reason, .cptch_edit_reason_link' ).show();
		} );

		/* function to resize textarea according to the 'add_reason' content */
		$( '.cptch-autoexpand' ).on( "focus input", function() {
			var el = this;
			el.style.cssText = 'height:auto; padding:0; overflow:hidden';
			el.style.cssText = 'height:' + el.scrollHeight + 'px; overflow:hidden';
		} );

	} );
} )( jQuery );

/**
 * Update add reason for whitelist
 * @param		string		id				reason of which id is edited
 * @param		string		reason			reason text
 * @return		void
 */
function cptch_update_reason( id, reason ) {
	(function( $ ) {
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'cptch_update_reason',
				cptch_edit_id:	id,
				cptch_reason:	reason,
				cptch_nonce:	cptch_vars.cptch_ajax_nonce
			},
			success: function( result ) {
				var parent_row	= $( '.check-column input[value="' + id + '"]' ).closest( 'tr' ),
					reason_display = parent_row.find( '.cptch-add-reason' ),
					reason_textarea = parent_row.find( '.cptch-add-reason-textarea' ),
					old_color = reason_display.css( 'color' );
				try {
					result		= $.parseJSON( result );
					if ( '' !== result['success'] ) {
						reason_textarea.val( result['reason'] );
						reason_textarea.data( 'default-value', result['reason'] );
						reason_display.html( result['reason-html'] );
						reason_display
							.animate(
								{ color: "#46b450" },
								250
							)
							.animate(
								{ color: old_color },
								250
							);
					} else {
						if ( '' !== result['no_changes'] ) {
						} else {
							var str = reason_display.html();
							reason_textarea.val( str.replace(/<br>/g, "") );
							reason_display
								.animate(
									{ color: "#dc3232" },
									250
								)
								.animate(
									{ color: old_color },
									250
								);
						}
					}
				} catch( e ) {
					var str = reason_display.html();
					reason_textarea.val( str.replace(/<br>/g, "") );
					reason_display
						.animate(
							{ color: "#dc3232" },
							250
						)
						.animate(
							{ color: old_color },
							250
						);
				}
			},
			error : function ( xhr, ajaxOptions, thrownError ) {
				alert( xhr.status );
				alert( thrownError );
			}
		} );
		return false;
	} )( jQuery );
}


