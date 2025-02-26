<?php

if ( ! class_exists( 'GF_Field_Cptch' ) ) {
	class GF_Field_Cptch extends GF_Field {

		public $type = 'cptch';

		public function get_form_editor_field_title() {		
			return 'INEOSQ Captcha';
		}

		function get_form_editor_field_settings() {
			return array(
				'conditional_logic_field_setting',
				'label_setting',
				'label_placement_setting',
				'description_setting',
				'css_class_setting'
			);

		}

		public function validate( $value, $form ) {
			global $cptch_options;
			if ( empty( $cptch_options ) ) {
				register_cptch_settings();
			}
			$result = "";
 			if ( $cptch_options['forms']['gravity_forms']['enable'] == true && 'invisible' != $cptch_options['type'] ) {
				$cptch = cptch_check_custom_form( true, 'gravity_forms', 'gravity_forms' );
				if ( ! empty( $cptch->errors ) ) {               	
					foreach ( $cptch->errors as $arrors ) {
							$result .= implode( '<br>', $arrors );
					}
				} 
				elseif ( ! empty( $cptch ) && $cptch != true ) {
					$result = $cptch;
				} 
			} else {
				if ( ! isset( $_REQUEST['cptch_key'] ) || empty( $_REQUEST['cptch_key'] ) ) {
					$_REQUEST['cptch_key'] = $value['cptch_key'];
					$_REQUEST['cptch_code'] = $value['cptch_code'];
				}
				$cptch = cptch_check_custom_form( true, 'gravity_forms', 'gravity_forms' );
				if ( ! empty( $cptch->errors ) ) {
						$result = $cptch->errors['cptch_check_errors'][0];
				}
			}

			if ( ! empty( $result ) ) {
				$this->failed_validation  = true;
				$this->validation_message = $result;
			}
		}

		public function get_form_editor_inline_script_on_page_render() {
			cptch_pro_front_end_styles();
			return "gform.addFilter( 'gform_form_editor_can_field_be_added', function ( canFieldBeAdded, type ) {
		        if ( type == '{$this->type}' ) {
		            if ( GetFieldsByType( ['{$this->type}'] ).length > 0 ) {
		                alert( " . json_encode( __( 'Only one INEOSQ Captcha field can be added to the form', 'ineosq-captcha-pro' ) ) . " );
				 		return false;
		            } else if ( GetFieldsByType( ['captcha'] ).length > 0 ) {
		                alert( " . json_encode( __( 'The form already contains a CAPTCHA, first remove it to add INEOSQ Captcha', 'ineosq-captcha-pro' ) ) . " );
						return false;
		            }
		        }
		        return canFieldBeAdded;
		    } );";
		}

		public function get_field_input( $form, $value = '', $entry = null ) {
			return cptch_display_captcha_custom( 'gravity_forms', 'gravity_forms' );
		}
	}
}

if ( ! function_exists( 'cptch_set_default_value' ) ) {
	function cptch_set_default_value() {
		echo 'case "cptch" :
			field.label = "INEOSQ Captcha";
	    break;';
	}
}

$is_user_logged_in = is_user_logged_in();
if ( cptch_is_captcha_required( 'gravity_forms', $is_user_logged_in ) ) {
	GF_Fields::register( new GF_Field_Cptch() );
	add_action( 'gform_editor_js_set_default_values', 'cptch_set_default_value' );
}
