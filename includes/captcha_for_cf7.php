<?php
/**
* This functions are used for adding captcha in Contact Form 7
**/

/* add shortcode handler */
if ( ! function_exists ( 'wpcf7_add_shortcode_ineosq_captcha' ) ) {
	function wpcf7_add_shortcode_ineosq_captcha() {
		if ( function_exists( 'wpcf7_add_form_tag' ) ) {
			wpcf7_add_form_tag( 'ineosqcaptcha', 'wpcf7_ineosq_captcha_shortcode_handler', TRUE );
		} elseif ( function_exists( 'wpcf7_add_shortcode' ) ) {
			wpcf7_add_shortcode( 'ineosqcaptcha', 'wpcf7_ineosq_captcha_shortcode_handler', TRUE );
		}
	}
}
/* display captcha */
if ( ! function_exists ( 'wpcf7_ineosq_captcha_shortcode_handler' ) ) {
	function wpcf7_ineosq_captcha_shortcode_handler( $tag ) {
		if ( class_exists( 'WPCF7_FormTag' ) || class_exists( 'WPCF7_Shortcode' ) ) {
			$tag = class_exists( 'WPCF7_FormTag' ) ? new WPCF7_FormTag( $tag ) : new WPCF7_Shortcode( $tag );

			if ( empty( $tag->name ) ) {
				return '';
			}

			$validation_error = wpcf7_get_validation_error( $tag->name );
			$class = wpcf7_form_controls_class( $tag->type );

			if ( $validation_error ) {
				$class .= ' wpcf7-not-valid';
			}

			$atts					= array();
			$atts['class']			= $tag->get_class_option( $class, $tag->name );
			$atts['id']				= $tag->get_option( 'id', 'id', true );
			$atts['tabindex']		= $tag->get_option( 'tabindex', 'int', true );
			$atts['aria-required']	= 'true';
			$atts['type']			= 'text';
			$atts['name']			= $tag->name;
			$atts['value']			= '';

			/**
			 * Replace INEOSQ CAPTCHA shortcode with an empty string
			 * if the CAPTCHA should be hidden from registered users
			 * @since 4.2.3
			 */
			$cptch = cptch_captcha_is_needed( 'cf7_contact', is_user_logged_in() ) ? cptch_display_captcha_custom( 'cf7_contact', $atts['class'], $tag->name ) : '';
			$atts  = wpcf7_format_atts( $atts );

			return sprintf( '<span class="wpcf7-form-control-wrap %1$s"><input style="display:none;" %2$s />'. $cptch .'%3$s</span>', $tag->name, $atts, $validation_error );
		}
	}
}

/* tag generator */
if ( ! function_exists ( 'wpcf7_add_tag_generator_ineosq_captcha' ) ) {
	function wpcf7_add_tag_generator_ineosq_captcha() {
		if ( ! function_exists( 'wpcf7_add_tag_generator' ) ) {
			return;
		}
		$cf7_plugin_info = get_plugin_data( dirname( dirname( dirname( __FILE__ ) ) ) . "/contact-form-7/wp-contact-form-7.php" );
		if ( isset( $cf7_plugin_info ) && $cf7_plugin_info["Version"] >= '4.2' ) {
			wpcf7_add_tag_generator( 'ineosqcaptcha', __( 'INEOSQ CAPTCHA', 'ineosq-captcha-pro' ), 'wpcf7-ineosqcaptcha', 'wpcf7_tg_pane_ineosq_captcha_after_4_2' );
		} elseif ( isset( $cf7_plugin_info ) && $cf7_plugin_info["Version"] >= '3.9' ) {
			wpcf7_add_tag_generator( 'ineosqcaptcha', __( 'INEOSQ CAPTCHA', 'ineosq-captcha-pro' ), 'wpcf7-ineosqcaptcha', 'wpcf7_tg_pane_ineosq_captcha_after_3_9' );
		} else {
			wpcf7_add_tag_generator( 'ineosqcaptcha', __( 'INEOSQ CAPTCHA', 'ineosq-captcha-pro' ), 'wpcf7-ineosqcaptcha', 'wpcf7_tg_pane_ineosq_captcha' );
		}
	}
}

if ( ! function_exists ( 'wpcf7_tg_pane_ineosq_captcha' ) ) {
	function wpcf7_tg_pane_ineosq_captcha( &$contact_form ) { ?>
		<div id="wpcf7-ineosqcaptcha" class="hidden">
			<form action="">
				<table>
					<tr>
						<td>
							<?php _e( 'Name', 'contact-form-7' ); ?><br />
							<input type="text" name="name" class="tg-name oneline" />
						</td>
					</tr>
				</table>
				<div class="tg-tag">
					<?php _e( 'Copy this code and paste it into the form left.', 'contact-form-7' ); ?><br />
					<input type="text" name="ineosqcaptcha" class="tag" readonly="readonly" onfocus="this.select()" />
				</div>
			</form>
		</div>
	<?php }
}

if ( ! function_exists ( 'wpcf7_tg_pane_ineosq_captcha_after_3_9' ) ) {
	function wpcf7_tg_pane_ineosq_captcha_after_3_9( $contact_form ) { ?>
		<div id="wpcf7-ineosqcaptcha" class="hidden">
			<form action="">
				<table>
					<tr>
						<td>
							<?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?><br />
							<input type="text" name="name" class="tg-name oneline" />
						</td>
					</tr>
				</table>
				<div class="tg-tag">
					<?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7' ) ); ?><br />
					<input type="text" name="ineosqcaptcha" class="tag" readonly="readonly" onfocus="this.select()" />
				</div>
			</form>
		</div>
	<?php }
}

if ( ! function_exists ( 'wpcf7_tg_pane_ineosq_captcha_after_4_2' ) ) {
	function wpcf7_tg_pane_ineosq_captcha_after_4_2( $contact_form, $args = '' ) {
		$args = wp_parse_args( $args, array() );
		$type = 'ineosqcaptcha'; ?>
		<div class="control-box">
			<fieldset>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label></th>
							<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
						</tr>
					</tbody>
				</table>
			</fieldset>
		</div>
		<div class="insert-box">
			<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />
			<div class="submitbox">
				<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
			</div>
		</div>
	<?php }
}

/* validation for captcha */
if ( ! function_exists ( 'wpcf7_ineosq_captcha_validation_filter' ) ) {
	function wpcf7_ineosq_captcha_validation_filter( $result, $tag ) {
		global $cptch_options, $lmtttmptspr_options, $wpdb;
		$str_key	= $cptch_options['str_key']['key'];
		$error		= '';

		if ( class_exists( 'WPCF7_FormTag' ) || class_exists( 'WPCF7_Shortcode' ) ) {
			$tag = class_exists( 'WPCF7_FormTag' ) ? new WPCF7_FormTag( $tag ) : new WPCF7_Shortcode( $tag );
			$name = $tag->name;

			if ( 'invisible' == $cptch_options['type'] ) {

				require_once( dirname( __FILE__ ) . '/invisible.php' );
				$captcha = new Cptch_Invisible();
				$captcha->check();

				if ( $captcha->is_errors() ) {
					$error = $captcha->get_errors();
					$result_reason = $error->get_error_message();
				}
			} elseif ( 'slide' == $cptch_options['type'] ) {
				$result_reason = cptch_check_custom_form( true, 'string', 'contact_form_7' );
				if ( true === $result_reason ) {
					unset( $result_reason );
				}
			} else {

				if ( cptch_limit_exhausted() ) {
					$result_reason = wpcf7_get_message( 'timeout_ineosqcaptcha' );
				} elseif (
					! empty( $_POST[ $name ] ) &&
					isset( $_POST[ $name . '-cptch_result'] ) &&
					isset( $_POST[ 'cptch_time'] ) &&
					0 != strcasecmp( trim( cptch_decode( $_POST[ $name . '-cptch_result'], $str_key, $_POST[ 'cptch_time'] ) ), $_POST[ $name ] )
				) {
					$result_reason = wpcf7_get_message( 'wrong_ineosqcaptcha' );
				} elseif( empty( $_POST[ $name ] ) ) {
					$result_reason = wpcf7_get_message( 'fill_ineosqcaptcha' );
				}

				if ( $result_reason ) {
					$lmtttmpts_error = ( ! has_filter( 'lmtttmpts_check_ip' ) ) ? '' : apply_filters( 'lmtttmpts_wrong_captcha', 'cf7_recaptcha_check', '' );
					$errors = array( $result_reason, $lmtttmpts_error );
					$result_reason = implode( "\n", $errors );
				}
			}

			if ( isset( $result_reason ) ) {
				if ( is_array( $result ) ) {
					$result['valid'] = FALSE;
					$result['reason'][ $name ] = $result_reason;
				} elseif ( is_object( $result ) ) {
					/* cf after v4.1 */
					$result->invalidate( $tag, $result_reason );
				}
			}
			return $result;
		}
	}
}

/* add messages for Captha errors */
if ( ! function_exists ( 'wpcf7_ineosqcaptcha_messages' ) ) {
	function wpcf7_ineosqcaptcha_messages( $messages ) {
		global $cptch_options;
		return array_merge(
			$messages,
			array(
				'wrong_ineosqcaptcha'	=> array(
					'description'	=> $cptch_options['wrong_answer'],
					'default'		=> $cptch_options['wrong_answer']
				),
				'fill_ineosqcaptcha'	=> array(
					'description'	=> $cptch_options['no_answer'],
					'default'		=> $cptch_options['no_answer']
				),
				'timeout_ineosqcaptcha' => array(
					'description'	=> $cptch_options['time_limit_off'],
					'default'		=> $cptch_options['time_limit_off']
				)
			)
		);
	}
}

/* add warning message */
if ( ! function_exists ( 'wpcf7_ineosqcaptcha_display_warning_message' ) ) {
	function wpcf7_ineosqcaptcha_display_warning_message() {
		if ( empty( $_GET['post'] ) || ! ( $contact_form = wpcf7_contact_form( $_GET['post'] ) ) ) {
			return;
		}

		$has_tags = (bool)$contact_form->form_scan_shortcode( array( 'type' => array( 'ineosqcaptcha' ) ) );

		if ( ! $has_tags ) {
			return;
		}
	}
} ?>
