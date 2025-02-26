<?php

/**
 * INEOSQ Recaptcha for WPForms
 *
 * @since 1.62
 */

if ( ! class_exists( 'WPForms_Cptch' ) && class_exists( 'WPForms_Field' ) ) {
	class WPForms_Cptch extends WPForms_Field {

		/**
		 * Primary class constructor.
		 */
		public function init() {

			// Define field type information.
			$this->name  = 'INEOSQ Captcha';
			$this->type  = 'cptch';
			$this->icon  = 'fa-filter';
		}

		/**
		 * Field options panel inside the builder.
		 *
		 * @param array $field Field settings.
		 */
		public function field_options( $field ) {
			/*
			 * Basic field options.
			 */

			// Options open markup.
			$this->field_option(
				'basic-options',
				$field,
				array(
					'markup' => 'open',
				)
			);

			// Label.
			$this->field_option( 'label', $field );

			// Options close markup.
			$this->field_option(
				'basic-options',
				$field,
				array(
					'markup' => 'close',
				)
			);

			/*
			 * Advanced field options.
			 */

			// Options open markup.
			$this->field_option(
				'advanced-options',
				$field,
				array(
					'markup' => 'open',
				)
			);

			// Hide label.
			$this->field_option( 'label_hide', $field );

			// Custom CSS classes.
			$this->field_option( 'css', $field );

			// Options close markup.
			$this->field_option(
				'advanced-options',
				$field,
				array(
					'markup' => 'close',
				)
			);
		}

		/**
		 * Field preview inside the builder.
		 *
		 * @param array $field Field settings.
		 */
		public function field_preview( $field ) {
			// Label.
			$this->field_preview_option( 'label', $field );
		}

		/**
		 * Field display on the form front-end.
		 *
		 * @param array $field      Field settings.
		 * @param array $deprecated Deprecated.
		 * @param array $form_data  Form data and settings.
		 */
		public function field_display( $field, $deprecated, $form_data ) {
			$content = cptch_display_captcha_custom('wpforms', 'wpforms');
			$content .= '<input type="hidden" name="wpforms[fields][' . $field['id'] . ']" value="" />';
			echo $content;
		}


		/**
		 * Validate field on form submit.
		 *
		 * @param int   $field_id     Field ID.
		 * @param mixed $field_submit Field value that was submitted.
		 * @param array $form_data    Form data and settings.
		 */
		public function validate( $field_id, $field_submit, $form_data ) {
			global $cptch_options;
			$is_user_logged_in = is_user_logged_in();
			if ( $is_user_logged_in && isset( $cptch_options['forms']['wpforms']['hide_from_registered'] ) && $cptch_options['forms']['wpforms']['hide_from_registered'] == 1 ) {
				return;
			}
			$cptch_check = cptch_check_custom_form( true, 'wpforms', 'wpforms' );

			if ( is_wp_error( $cptch_check ) ) {
				wpforms()->process->errors[ $form_data['id'] ][ $field_id ] = sprintf(
					$cptch_check->get_error_messages()[0] 
				);
			}
		}
	}
}

$is_user_logged_in = is_user_logged_in();
if ( cptch_is_captcha_required( 'wpforms', $is_user_logged_in ) ){
	new WPForms_Cptch();
}