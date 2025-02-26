<?php
/**
 * Updating add reason for allow list via ajax
 * @uses   ajax
 * @return void
 */
if ( ! function_exists( 'cptch_update_reason' ) ) {
	function cptch_update_reason() {
		global $wpdb;
		if ( ! empty( $_POST['cptch_edit_id'] ) ) {
			check_ajax_referer( 'cptch_ajax_nonce_value', 'cptch_nonce' );
			$id			= esc_html( $_POST['cptch_edit_id'] );

			$message_list = array(
				'reason_update_success'	=> sprintf( __( 'The reason for %s has been updated successfully', 'ineosq-captcha-pro' ), $id ),
				'reason_update_error'	=> __( 'Error while updating reason for', 'ineosq-captcha-pro' ) . $id,
				'reason_untouched'		=> __( 'No changes was made', 'ineosq-captcha-pro' )
			);

			$add_reason	= ! empty( $_POST['cptch_reason'] ) ? stripslashes( esc_html( trim( $_POST['cptch_reason'], " \r\n\t,;" ) ) ) : '';
			$n = $wpdb->update(
				$wpdb->prefix . 'cptch_allowlist',
				array( 'add_reason'	=> $add_reason ),
				array( 'id'	=> $id )
			);
			if ( !! $n ) {
				/* if number of touched rows != 0/false */
				echo json_encode( array(
					'success'		=> $message_list['reason_update_success'],
					'reason'		=> $add_reason,
					'reason-html'	=> nl2br( $add_reason ),
				) );
				die();
			} else {
				if ( $n !== 0 ) {
					echo json_encode( array(
						'success'		=> '',
						'no_changes'	=> '',
						'error'			=> $message_list['reason_update_error']
					) );
					die();
				} else {
					echo json_encode( array(
						'success'		=> '',
						'no_changes'	=> $message_list['reason_untouched']
					) );
					die();
				}
			}
		} else {
			echo json_encode( array( 'error' => $message_list['reason_update_error'] ) );
			die();
		}
	}
}
add_action( 'wp_ajax_cptch_update_reason', 'cptch_update_reason' );