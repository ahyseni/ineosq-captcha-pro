<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class INEOSQrecaptcha
 */
require_once( ABSPATH . 'wp-content/plugins/ninja-forms/includes/Abstracts/Field.php' );
class INEOSQcaptcha extends NF_Abstracts_Field
{
    protected $_name = 'ineosqcaptcha';

    protected $_type = 'ineosqcaptcha';

    protected $_section = 'common';

    protected $_icon = 'filter';

    protected $_templates = 'ineosqcaptcha';

    protected $_test_value = '';

    protected $_settings = array('label', 'classes');

    public function __construct()
    {
        parent::__construct();
        $this->_nicename = __('INEOSQ Captcha', 'ineosq-captcha-pro');
        $this->_settings = $this->load_settings(
            array( 'label', 'label_pos' )
        );
    }

    public function localize_settings( $settings, $form ) {
        global  $cptch_options;
         if ($cptch_options['forms']['ninja_form']['enable'] == true) {
            $settings['key'] = $cptch_options['str_key']['key'];

            $settings['content'] = cptch_display_captcha_custom('ninja_form', 'ninja_form');

            if ( 'invisible' == $cptch_options['type'] ) {
                cptch_add_scripts();
            }
        }


        return $settings;
    }
    /* Check Captcha in Ninja Forms */
    public function validate( $field, $data ) {
        global $cptch_options;
        $user = wp_get_current_user();
        $_POST['log'] = $user->get('user_login');
        $_POST['pwd'] = $user->get('user_pass');
				if ( $cptch_options['forms']['ninja_form']['enable'] == true && 'invisible' != $cptch_options['type'] ) {
					$is_user_logged_in = is_user_logged_in();
					if ( $is_user_logged_in && isset( $cptch_options['forms']['ninja_form']['hide_from_registered'] ) && $cptch_options['forms']['ninja_form']['hide_from_registered'] == 1 ) {
						return;
					}
					if ( isset( $field['cptch_number'] ) ) {
							$_REQUEST['cptch_number'] = $field['cptch_number'];
					}
					$_REQUEST['cptch_result'] = $field['cptch_result'];
					$_REQUEST['cptch_time'] = $field['cptch_time'];
					$_REQUEST['cptch_form'] = $field['cptch_form'];
					$cptch = cptch_check_custom_form( true, 'ninja_form', 'ninja_form' );
					if ( ! empty( $cptch->errors ) ) {
							$result = "";
							foreach ($cptch->errors as $arrors) {
									$result .= implode('.', $arrors);
							}
							return $result;
					} elseif ( ! empty( $cptch ) && $cptch != true ){
							return $cptch;
					}
        } else if ( $cptch_options['forms']['ninja_form']['enable'] == true ) {
					$is_user_logged_in = is_user_logged_in();
					if ( $is_user_logged_in && isset( $cptch_options['forms']['ninja_form']['hide_from_registered'] ) && $cptch_options['forms']['ninja_form']['hide_from_registered'] == 1 ) {
						return;
					}
					$_REQUEST['cptch_key'] = $field['cptch_key'];
					$_REQUEST['cptch_code'] = $field['cptch_code'];
					$cptch = cptch_check_custom_form( true, 'ninja_form', 'ninja_form' );
					if ( ! empty( $cptch->errors ) ) {
							return $cptch->errors['cptch_check_errors'][0];
					}
        }
    }
}


/* Include fields-ineosqcaptcha.html template */
add_filter( 'ninja_forms_field_template_file_paths', 'cptch_file_path' );
function cptch_file_path( $paths )
{
        $paths[] = dirname(__FILE__) . '/';

        return $paths;

}

/* Add Google Captcha in Ninja Forms Builder */
add_filter('ninja_forms_register_fields', 'ninja_forms_cptch_field' );
function ninja_forms_cptch_field( $fields ) {
    global  $cptch_options;
    if ( empty( $cptch_options ) ) {
       register_cptch_settings();
    }

    if ( isset($cptch_options['forms']['ninja_form']['enable']) &&  $cptch_options['forms']['ninja_form']['enable'] == true ){
       if ( is_user_logged_in() && isset( $cptch_options['forms']['ninja_form']['hide_from_registered'] ) &&
        $cptch_options['forms']['ninja_form']['hide_from_registered'] == 1 && ! is_admin() ) {

           return $fields;
       }
        $fields['ineosqcaptcha'] = new INEOSQcaptcha;
    }
    
    return $fields;
}
