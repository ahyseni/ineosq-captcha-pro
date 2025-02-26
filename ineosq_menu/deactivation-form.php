<?php
/**
 * Displays the content of the dialog box when the user clicks on the "Deactivate" link on the plugin settings page
 *
 * @package IneosQ
 * @since 2.1.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a confirmation and feedback dialog box when the user clicks on the "Deactivate" link on the plugins
 * page.
 *
 * @since  2.1.3
 */
if ( ! function_exists( 'ineosq_add_deactivation_feedback_dialog_box' ) ) {
	function ineosq_add_deactivation_feedback_dialog_box() {
		global $bstwbsftwppdtplgns_active_plugins;
		if ( empty( $bstwbsftwppdtplgns_active_plugins ) ) {
			return;
		}

		$contact_support_template = __( 'Need help? We are ready to answer your questions.', 'ineosq' ) . ' <a href="https://support.ineosq.com/hc/en-us/requests/new" target="_blank">' . __( 'Contact Support', 'ineosq' ) . '</a>';

		$reasons = array(
			array(
				'id'                => 'NOT_WORKING',
				'text'              => __( 'The plugin is not working', 'ineosq' ),
				'input_type'        => 'textarea',
				'input_placeholder' => __( "Kindly share what didn't work so we can fix it in future updates...", 'ineosq' ),
			),
			array(
				'id'                => 'DIDNT_WORK_AS_EXPECTED',
				'text'              => __( "The plugin didn't work as expected", 'ineosq' ),
				'input_type'        => 'textarea',
				'input_placeholder' => __( 'What did you expect?', 'ineosq' ),
			),
			array(
				'id'                => 'SUDDENLY_STOPPED_WORKING',
				'text'              => __( 'The plugin suddenly stopped working', 'ineosq' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'internal_message'  => $contact_support_template,
			),
			array(
				'id'                => 'BROKE_MY_SITE',
				'text'              => __( 'The plugin broke my site', 'ineosq' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'internal_message'  => $contact_support_template,
			),
			array(
				'id'                => 'COULDNT_MAKE_IT_WORK',
				'text'              => __( "I couldn't understand how to get it work", 'ineosq' ),
				'input_type'        => '',
				'input_placeholder' => '',
				'internal_message'  => $contact_support_template,
			),
			array(
				'id'                => 'FOUND_A_BETTER_PLUGIN',
				'text'              => __( 'I found a better plugin', 'ineosq' ),
				'input_type'        => 'textfield',
				'input_placeholder' => __( "What's the plugin name?", 'ineosq' ),
			),
			array(
				'id'                => 'GREAT_BUT_NEED_SPECIFIC_FEATURE',
				'text'              => __( "The plugin is great, but I need specific feature that you don't support", 'ineosq' ),
				'input_type'        => 'textarea',
				'input_placeholder' => __( 'What feature?', 'ineosq' ),
			),
			array(
				'id'                => 'NO_LONGER_NEEDED',
				'text'              => __( 'I no longer need the plugin', 'ineosq' ),
				'input_type'        => '',
				'input_placeholder' => '',
			),
			array(
				'id'                => 'TEMPORARY_DEACTIVATION',
				'text'              => __( "It's a temporary deactivation, I'm just debugging an issue", 'ineosq' ),
				'input_type'        => '',
				'input_placeholder' => '',
			),
			array(
				'id'                => 'OTHER',
				'text'              => __( 'Other', 'ineosq' ),
				'input_type'        => 'textfield',
				'input_placeholder' => '',
			),
		);

		$modal_html = '<div class="ineosq-modal ineosq-modal-deactivation-feedback">
	    	<div class="ineosq-modal-dialog">
	    		<div class="ineosq-modal-body">
	    			<h2>' . __( 'Quick Feedback', 'ineosq' ) . '</h2>
	    			<div class="ineosq-modal-panel active">
	    				<p>' . __( 'If you have a moment, please let us know why you are deactivating', 'ineosq' ) . ':</p><ul>';

		foreach ( $reasons as $reason ) {
			$list_item_classes = 'ineosq-modal-reason' . ( ! empty( $reason['input_type'] ) ? ' has-input' : '' );

			if ( ! empty( $reason['internal_message'] ) ) {
				$list_item_classes      .= ' has-internal-message';
				$reason_internal_message = $reason['internal_message'];
			} else {
				$reason_internal_message = '';
			}

			$modal_html .= '<li class="' . $list_item_classes . '" data-input-type="' . $reason['input_type'] . '" data-input-placeholder="' . $reason['input_placeholder'] . '">
				<label>
					<span>
						<input type="radio" name="selected-reason" value="' . $reason['id'] . '"/>
					</span>
					<span>' . $reason['text'] . '</span>
				</label>
				<div class="ineosq-modal-internal-message">' . $reason_internal_message . '</div>
			</li>';
		}
		$modal_html .= '</ul>
		    				<label class="ineosq-modal-anonymous-label">
			    				<input type="checkbox" />' .
								__( 'Send website data and allow to contact me back', 'ineosq' ) .
							'</label>
						</div>
					</div>
					<div class="ineosq-modal-footer">
						<a href="#" class="button button-primary ineosq-modal-button-deactivate disabled">' . __( 'Submit and Deactivate', 'ineosq' ) . '</a>
						<a href="#" class="ineosq-modal-skip-link">' . __( 'Skip and Deactivate', 'ineosq' ) . '</a>
						<span class="ineosq-modal-processing hidden">' . __( 'Processing', 'ineosq' ) . '...</span>
						<div class="clear"></div>
					</div>
				</div>
			</div>';

		$script = '';

		foreach ( $bstwbsftwppdtplgns_active_plugins as $basename => $plugin_data ) {

			$slug      = dirname( $basename );
			$plugin_id = sanitize_title( $plugin_data['Name'] );

			$script .= "(function($) {
					var modalHtml = " . json_encode( $modal_html ) . ",
					    \$modal                = $( modalHtml ),
					    \$deactivateLink       = $( '#the-list .active[data-plugin=\"" . $basename . "\"] .deactivate a' ),
						\$anonymousFeedback    = \$modal.find( '.ineosq-modal-anonymous-label' ),
						selectedReasonID      = false;

					/* WP added data-plugin attr after 4.5 version/ In prev version was id attr */
					if ( 0 == \$deactivateLink.length )
						\$deactivateLink = $( '#the-list .active#" . $plugin_id . " .deactivate a' );

					\$modal.appendTo( $( 'body' ) );

					IneosqModalRegisterEventHandlers();
					
					function IneosqModalRegisterEventHandlers() {
						\$deactivateLink.click( function( evt ) {
							evt.preventDefault();

							/* Display the dialog box.*/
							IneosqModalReset();
							\$modal.addClass( 'active' );
							$( 'body' ).addClass( 'has-ineosq-modal' );
						});

						\$modal.on( 'input propertychange', '.ineosq-modal-reason-input input', function() {
							if ( ! IneosqModalIsReasonSelected( 'OTHER' ) ) {
								return;
							}

							var reason = $( this ).val().trim();

							/* If reason is not empty, remove the error-message class of the message container to change the message color back to default. */
							if ( reason.length > 0 ) {
								\$modal.find( '.message' ).removeClass( 'error-message' );
								IneosqModalEnableDeactivateButton();
							}
						});

						\$modal.on( 'blur', '.ineosq-modal-reason-input input', function() {
							var \$userReason = $( this );

							setTimeout( function() {
								if ( ! IneosqModalIsReasonSelected( 'OTHER' ) ) {
									return;
								}
							}, 150 );
						});

						\$modal.on( 'click', '.ineosq-modal-footer .ineosq-modal-skip-link', function( evt ) {
							evt.preventDefault();
							
							/* If no selected reason, just deactivate the plugin. */
							window.location.href = \$deactivateLink.attr( 'href' );
							return;
						});

						\$modal.on( 'click', '.ineosq-modal-footer .button', function( evt ) {
							evt.preventDefault();

							if ( $( this ).hasClass( 'disabled' ) ) {
								return;
							}

							var _parent = $( this ).parents( '.ineosq-modal:first' ),
								_this =  $( this );

							var \$radio = \$modal.find( 'input[type=\"radio\"]:checked' );

							if ( 0 === \$radio.length ) {
								/* If no selected reason */
								IneosqModalDisableDeactivateButton();
								return;
							}

							var \$selected_reason = \$radio.parents( 'li:first' ),
							    \$input = \$selected_reason.find( 'textarea, input[type=\"text\"]' ),
							    userReason = ( 0 !== \$input.length ) ? \$input.val().trim() : '';

							var is_anonymous = ( \$anonymousFeedback.find( 'input' ).is( ':checked' ) ) ? 0 : 1;

							$.ajax({
								url       : ajaxurl,
								method    : 'POST',
								data      : {
									'action'			: 'ineosq_submit_uninstall_reason_action',
									'plugin'			: '" . $basename . "',
									'reason_id'			: \$radio.val(),
									'reason_info'		: userReason,
									'is_anonymous'		: is_anonymous,
									'ineosq_ajax_nonce'	: '" . wp_create_nonce( 'ineosq_ajax_nonce' ) . "'
								},
								beforeSend: function() {
									_parent.find( '.ineosq-modal-footer .button' ).hide();
									_parent.find( '.ineosq-modal-footer .ineosq-modal-processing' ).show();
								},
								complete  : function( message ) {
									/* Do not show the dialog box, deactivate the plugin. */
									window.location.href = \$deactivateLink.attr( 'href' );
								}
							});
						});

						\$modal.on( 'click', 'input[type=\"radio\"]', function() {
							var \$selectedReasonOption = $( this );

							/* If the selection has not changed, do not proceed. */
							if ( selectedReasonID === \$selectedReasonOption.val() )
								return;

							selectedReasonID = \$selectedReasonOption.val();

							\$anonymousFeedback.show();

							var _parent = $( this ).parents( 'li:first' );

							\$modal.find( '.ineosq-modal-reason-input' ).remove();
							\$modal.find( '.ineosq-modal-internal-message' ).hide();

							IneosqModalEnableDeactivateButton();

							if ( _parent.hasClass( 'has-internal-message' ) ) {
								_parent.find( '.ineosq-modal-internal-message' ).show();
							}

							if (_parent.hasClass('has-input')) {
								var reasonInputHtml = '<div class=\"ineosq-modal-reason-input\"><span class=\"message\"></span>' + ( ( 'textfield' === _parent.data( 'input-type' ) ) ? '<input type=\"text\" />' : '<textarea rows=\"5\" maxlength=\"200\"></textarea>' ) + '</div>';

								_parent.append( $( reasonInputHtml ) );
								_parent.find( 'input, textarea' ).attr( 'placeholder', _parent.data( 'input-placeholder' ) ).focus();

								if ( IneosqModalIsReasonSelected( 'OTHER' ) ) {
									\$modal.find( '.message' ).text( '" . esc_html__( 'Please tell us the reason so we can improve it.', 'ineosq' ) . "' ).show();
								}
							}
						});

						/* If the user has clicked outside the window, cancel it. */
						\$modal.on( 'click', function( evt ) {
							var \$target = $( evt.target );

							/* If the user has clicked anywhere in the modal dialog, just return. */
							if ( \$target.hasClass( 'ineosq-modal-body' ) || \$target.hasClass( 'ineosq-modal-footer' ) ) {
								return;
							}

							/* If the user has not clicked the close button and the clicked element is inside the modal dialog, just return. */
							if ( ! \$target.hasClass( 'ineosq-modal-button-close' ) && ( \$target.parents( '.ineosq-modal-body' ).length > 0 || \$target.parents( '.ineosq-modal-footer' ).length > 0 ) ) {
								return;
							}

							/* Close the modal dialog */
							\$modal.removeClass( 'active' );
							$( 'body' ).removeClass( 'has-ineosq-modal' );

							return false;
						});
					}

					function IneosqModalIsReasonSelected( reasonID ) {
						/* Get the selected radio input element.*/
						return ( reasonID == \$modal.find('input[type=\"radio\"]:checked').val() );
					}

					function IneosqModalReset() {
						selectedReasonID = false;						

						/* Uncheck all radio buttons.*/
						\$modal.find( 'input[type=\"radio\"]' ).prop( 'checked', false );

						/* Remove all input fields ( textfield, textarea ).*/
						\$modal.find( '.ineosq-modal-reason-input' ).remove();

						\$modal.find( '.message' ).hide();

						/* Hide, since by default there is no selected reason.*/
						\$anonymousFeedback.hide();

						IneosqModalDisableDeactivateButton();

						IneosqModalShowPanel();
					}

					function IneosqModalEnableDeactivateButton() {
						\$modal.find( '.ineosq-modal-button-deactivate' ).removeClass( 'disabled' ).show();
						\$modal.find( '.ineosq-modal-processing' ).hide();
					}

					function IneosqModalDisableDeactivateButton() {
						\$modal.find( '.ineosq-modal-button-deactivate' ).addClass( 'disabled' );
					}

					function IneosqModalShowPanel() {
						\$modal.find( '.ineosq-modal-panel' ).addClass( 'active' );
					}
				})(jQuery);";
		}

		/* add script in FOOTER */
		wp_register_script( 'ineosq-deactivation-feedback-dialog-boxes', '', array( 'jquery' ), false, true );
		wp_enqueue_script( 'ineosq-deactivation-feedback-dialog-boxes' );
		wp_add_inline_script( 'ineosq-deactivation-feedback-dialog-boxes', $script );
	}
}

/**
 * Called after the user has submitted his reason for deactivating the plugin.
 *
 * @since  2.1.3
 */
if ( ! function_exists( 'ineosq_submit_uninstall_reason_action' ) ) {
	function ineosq_submit_uninstall_reason_action() {
		global $bstwbsftwppdtplgns_options, $wp_version, $bstwbsftwppdtplgns_active_plugins, $current_user;

		if ( isset( $_REQUEST['ineosq_ajax_nonce'] ) ) {

			wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['ineosq_ajax_nonce'] ) ), 'ineosq_ajax_nonce' );

			$reason_id = isset( $_REQUEST['reason_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['reason_id'] ) ) : '';
			$basename  = isset( $_REQUEST['plugin'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) : '';

			if ( empty( $reason_id ) || empty( $basename ) ) {
				exit;
			}

			$reason_info = isset( $_REQUEST['reason_info'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['reason_info'] ) ) : '';
			if ( ! empty( $reason_info ) ) {
				$reason_info = substr( $reason_info, 0, 255 );
			}
			$is_anonymous = isset( $_REQUEST['is_anonymous'] ) && 1 === intval( $_REQUEST['is_anonymous'] );

			$options = array(
				'product'     => $basename,
				'reason_id'   => $reason_id,
				'reason_info' => $reason_info,
			);

			if ( ! $is_anonymous ) {
				if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
					$bstwbsftwppdtplgns_options = ( is_multisite() ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
				}

				if ( ! empty( $bstwbsftwppdtplgns_options['track_usage']['usage_id'] ) ) {
					$options['usage_id'] = $bstwbsftwppdtplgns_options['track_usage']['usage_id'];
				} else {
					$options['usage_id']   = false;
					$options['url']        = get_bloginfo( 'url' );
					$options['wp_version'] = $wp_version;
					$options['is_active']  = false;
					$options['version']    = $bstwbsftwppdtplgns_active_plugins[ $basename ]['Version'];
				}

				$options['email'] = $current_user->data->user_email;
			}

			/* send data */
			$raw_response = wp_remote_post(
				'https://ineosq.com/wp-content/plugins/products-statistics/deactivation-feedback/',
				array(
					'method'  => 'POST',
					'body'    => $options,
					'timeout' => 15,
				)
			);

			if ( ! is_wp_error( $raw_response ) && 200 === intval( wp_remote_retrieve_response_code( $raw_response ) ) ) {
				if ( ! $is_anonymous ) {
					$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );

					if ( is_array( $response ) && ! empty( $response['usage_id'] ) && $response['usage_id'] !== $options['usage_id'] ) {
						$bstwbsftwppdtplgns_options['track_usage']['usage_id'] = $response['usage_id'];

						if ( is_multisite() ) {
							update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
						} else {
							update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
						}
					}
				}

				echo 'done';
			} else {
				echo wp_kses_data( $response->get_error_code() ) . ': ' . wp_kses_data( $response->get_error_message() );
			}
		}
		exit;
	}
}

add_action( 'wp_ajax_ineosq_submit_uninstall_reason_action', 'ineosq_submit_uninstall_reason_action' );
