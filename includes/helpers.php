<?php
/**
 * Contains the functions which are use on plugin admin pages
 * @package Captcha Pro by IneosQ
 * @since   4.2.3
 */

/**
 * Fetch plugin default options
 * @param  void
 * @return array
 */
if ( ! function_exists( 'cptch_get_default_options' ) ) {
	function cptch_get_default_options() {
		global $cptch_plugin_info;

		$default_options = array(
			'plugin_option_version'			=> 'pro-' . $cptch_plugin_info["Version"],
			'str_key'						=> array( 'time' => '', 'key' => '' ),
			'type'							=> 'invisible',
			'math_actions'					=> array( 'plus', 'minus', 'multiplications' ),
			'operand_format'				=> array( 'numbers', 'words', 'images' ),
			'images_count'					=> 5,
			'title'							=> '',
			'required_symbol'				=> '*',
			'text_start_slide'              => __( 'Slide to verify', 'ineosq-captcha-pro' ),
			'text_end_slide'                => __( 'Verification passed', 'ineosq-captcha-pro' ),
			'color_start_slide'             => '#1888F8',
			'color_end_slide'               => '#43b309',
			'color_container_slide'         => '#E7E7E7',
			'color_text_slide'              => '#000000',
			'font_size_text_slide'			=> '14',
			'display_reload_button'			=> true,
			'use_several_packages'			=> true,
			'enlarge_images'				=> false,
			'enable_time_limit'				=> false,
			'time_limit'					=> 120,
			'no_answer'						=> __( 'Please complete the captcha.', 'ineosq-captcha-pro' ),
			'wrong_answer'					=> __( 'Please enter correct captcha value.', 'ineosq-captcha-pro' ),
			'time_limit_off'				=> __( 'Time limit exceeded. Please complete the captcha once again.', 'ineosq-captcha-pro' ),
			'time_limit_off_notice'			=> __( 'Time limit exceeded. Please complete the captcha once again.', 'ineosq-captcha-pro' ),
			'allowlist_message'			=> __( 'Your IP address is allow listed.', 'ineosq-captcha-pro' ),
			'load_via_ajax'					=> false,
			'activate_akismet'				=> false,
			'use_limit_attempts_allowlist'	=> false,
			'display_packages_notice'		=> true,
			'display_settings_notice'		=> 1,
			'suggest_feature_banner'		=> 1,
			'forms'							=> array()
		);

		$forms = cptch_get_default_forms();

		foreach ( $forms as $form ) {
			$default_options['forms'][ $form ] = array(
				'enable'				=> 'general' == $form ? true : in_array( $form, array( 'wp_login', 'wp_register', 'wp_lost_password', 'wp_comments' ) ),
				'hide_from_registered' => ! preg_match( '/(general)|(login)|(register)|(password)|(buddypress)/', $form ),
				'use_general'			=> true,
				'used_packages'			=> array(),
				'enable_time_limit'		=> false,
				'time_limit'			 => 120
			);
		}

		if ( is_network_admin() ) {
			$default_options['network_apply']	= 'default';
			$default_options['network_view']	= '1';
			$default_options['network_change']	= '1';
		}
		return $default_options;
	}
}

/**
 * Fetch the list of forms which are compatible with the plugin
 * @param  void
 * @return array
 */
if ( ! function_exists( 'cptch_get_default_forms' ) ) {
	function cptch_get_default_forms() {
		$defaults = array(
			'general', 'wp_login', 'wp_register', 'wp_lost_password', 'wp_comments',
			'ineosq_contact', 'ineosq_booking', 'ineosq_subscriber', 'buddypress_register', 'buddypress_comments',
			'buddypress_group', 'cf7_contact', 'woocommerce_login', 'woocommerce_register',
			'woocommerce_lost_password', 'woocommerce_checkout', 'jetpack_contact_form', 'bbpress_new_topic_form', 'bbpress_reply_form', 'wpforo_login_form', 'wpforo_register_form', 'wpforo_new_topic_form', 'wpforo_reply_form', 'mailchimp'
		);

		/*
		 * Add user forms to defaults
		 */
		$new_forms = apply_filters( 'cptch_add_form', array() );

		if ( ! is_array( $new_forms ) || empty( $new_forms ) )
			return $defaults;

		$new = array_filter( array_map( 'esc_attr', array_keys( $new_forms ) ) );

		return array_unique( array_merge( $defaults, $new ) );
	}
}

/**
 * Updates the plugin options if there was any changes in the new plugin version
 * @see    register_cptch_settings()
 * @param  array  $old_options
 * @param  array  $default_options
 * @return array
 */
if ( ! function_exists( 'cptch_parse_options' ) ) {
	function cptch_parse_options( $old_options, $default_options ) {
		global $cptch_plugin_info;

		$new_options = cptch_merge_recursive( $default_options, $old_options );

		/* Replace old option fields names by new */
		$args = array(
			'str_key'				=> array( 'cptchpr_str_key', 'cptch_str_key' ),
			'required_symbol'		=> array( 'cptchpr_required_symbol', 'cptch_required_symbol' ),
			'title'					=> array( 'cptchpr_label_form', 'cptch_label_form' ),
			'no_answer'				=> array( 'cptchpr_error_empty_value', 'cptch_error_empty_value' ),
			'wrong_answer'			=> array( 'cptchpr_error_incorrect_value', 'cptch_error_incorrect_value' ),
			'time_limit_off'		=> array( 'cptchpr_error_time_limit', 'cptch_error_time_limit' ),
			'activate_akismet'		=> array( 'cptchpr_activate_akismet' ),
			'time_limit_off_notice'	=> array( 'time_limit_notice' ),
			'network_apply'			=> array( 'cptchpr_network_apply' ),
			'network_view'			=> array( 'cptchpr_network_view' ),
			'network_change'		=> array( 'cptchpr_network_change' ),
			'enable_time_limit'		=> array( 'use_time_limit' ),
		);

		foreach ( $args as $new_field => $old_fields ) {
			foreach ( $old_fields as $old_field ) {
				if ( isset( $old_options[ $old_field ] ) ) {
					$new_options[ $new_field ] = $old_options[ $old_field ];

					if ( isset( $new_options[ $old_field ] ) )
						unset( $new_options[ $old_field ] );
					break;
				}
			}
		}

		/* Change the old settings structure by new one with the storing of the old values */
		$args = array(
			'math_actions'	=> array(
				'plus'				=> array( 'cptchpr_math_action_plus', 'cptch_math_action_plus' ),
				'minus'				=> array( 'cptchpr_math_action_minus', 'cptch_math_action_minus' ),
				'multiplications'	=> array( 'cptchpr_math_action_increase', 'cptch_math_action_increase' )
			),
			'operand_format'	=> array(
				'numbers'			=> array( 'cptchpr_difficulty_number', 'cptch_difficulty_number' ),
				'words'				=> array( 'cptchpr_difficulty_word', 'cptch_difficulty_word' ),
				'images'			=> array( 'cptchpr_difficulty_image', 'cptch_difficulty_image' )
			)
		);

		foreach ( $args as $new_field => $old_args ) {

			if ( empty( $new_options[ $new_field ] ) || ! is_array( $new_options[ $new_field ] ) )
				$new_options[ $new_field ] = array();

			foreach( $old_args as $new_field_value => $old_fields ) {
				foreach( $old_fields as $old_field ) {
					if ( ! isset( $old_options[ $old_field ] ) )
						continue;

					if (
						!! $old_options[ $old_field ] &&
						! in_array( $new_field_value, $new_options[ $new_field ] )
					)
						$new_options[ $new_field ][] = $new_field_value;

					if ( isset( $new_options[ $old_field ] ) )
						unset( $new_options[ $old_field ] );
				}
			}
		}

		/* Forming the options for the each of the form which are compatible with the plugin */
		$args = array(
			'general' => array(
				'enable_time_limit' => 'use_time_limit',
				'time_limit'        => 'custom',
				'used_packages'     => 'used_packages'
			),
			'wp_login'  => array(
				'enable'     => array( 'cptchpr_login_form', 'cptch_login_form' ),
				'time_limit' => 'wp_login'
			),
			'wp_register' => array(
				'enable'     => array( 'cptchpr_register_form', 'cptch_register_form' ),
				'time_limit' => 'wp_register'
			),
			'wp_lost_password' => array(
				'enable'     => array( 'cptchpr_lost_password_form', 'cptch_lost_password_form' ),
				'time_limit' => 'wp_lost_password'
			),
			'wp_comments' => array(
				'enable'               => array( 'cptchpr_comments_form', 'cptch_comments_form' ),
				'time_limit'           => 'wp_comments',
				'hide_from_registered' => array( 'cptchpr_hide_register', 'cptch_hide_register' )
			),
			'ineosq_contact' => array(
				'enable'     => array( 'cptchpr_contact_form', 'cptch_contact_form' ),
				'time_limit' => 'ineosq_contact'
			),
			'ineosq_booking' => array(
				'enable'     => array( 'cptchpr_booking_form', 'cptch_booking_form' ),
				'time_limit' => 'ineosq_booking'
			),
			'ineosq_subscriber' => array(
				'enable'     => 'cptchpr_subscriber',
				'time_limit' => 'ineosq_subscriber'
			),
			'buddypress_register' => array(
				'enable'     => 'cptchpr_buddypress_register_form',
				'time_limit' => 'buddypress_register'
			),
			'buddypress_comments' => array(
				'enable'     => 'cptchpr_buddypress_comment_form',
				'time_limit' => 'buddypress_comments'
			),
			'buddypress_group' => array(
				'enable'     => 'cptchpr_buddypress_group_form',
				'time_limit' => 'buddypress_group'
			),
			'cf7_contact' => array(
				'enable'     => 'cptchpr_cf7',
				'time_limit' => 'cf7_contact'
			),
			'woocommerce_login' => array(
				'enable'     =>  array( 'woocommerce_login_form', 'woocommerce' ),
				'time_limit' => 'woocommerce'
			),
			'woocommerce_register' => array(
				'enable'     => 'woocommerce_register_form',
				'time_limit' => 'woocommerce'
			),
			'woocommerce_lost_password' => array(
				'enable'     => 'woocommerce_lost_password_form',
				'time_limit' => 'woocommerce'
			),
			'woocommerce_checkout' => array(
				'enable'     => 'woocommerce_checkout_billing_form',
				'time_limit' => 'woocommerce'
			)
		);

		foreach ( $args as $form => $options ) {
			foreach ( $options as $new_fields => $old_fields ) {
				foreach ( (array)$old_fields as $old_field ) {
					if ( isset( $old_options[ $old_field ] ) ) {
						$new_options['forms'][ $form ][ $new_fields ] = $old_options[ $old_field ];

						if ( isset( $new_options[ $old_field ] ) )
							unset( $new_options[ $old_field ] );
					} elseif ( isset( $old_options['forms_time_limit'][ $old_field ] ) ) {
						$new_options['forms'][ $form ][ $new_fields ] = $old_options['forms_time_limit'][ $old_field ];
					}
				}
			}
		}

		/* Remove other old options */
		if ( isset( $new_options['forms_time_limit'] ) )
			unset( $new_options['forms_time_limit'] );

		/* Update plugin version */
		$new_options['plugin_option_version']   = 'pro-' . $cptch_plugin_info["Version"];
		$new_options['display_settings_notice'] = 0;

		return $new_options;
	}
}

/**
 * Replaces values of the base array by values form appropriate fields of the replacement array or
 * joins not existed fields in base from the replacement recursively
 * @see    cptch_parse_options()
 * @param  array  $base          The initial array
 * @param  array  $replacement   The array to merge
 * @return array
 */
if ( ! function_exists( 'cptch_merge_recursive' ) ) {
	function cptch_merge_recursive( $base, $replacement ) {

		/* array_keys( $replacement ) == range( 0, count( $replacement ) - 1 ) - checking if array is numerical */
		if ( ! is_array( $base ) || empty( $replacement ) || array_keys( $replacement ) == range( 0, count( $replacement ) - 1 ) )
			return $replacement;

		foreach ( $replacement as $key => $value ) {
			if ( ! empty( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
				$base[ $key ] = cptch_merge_recursive( $base[ $key ], $value );
			} else {
				$base[ $key ] = $value;
			}
		}

		return $base;
	}
}

/**
 * Fethch the plugin data
 * @param  string|array  $plugins       The string or array of strings in the format {plugin_folder}/{plugin_file}
 * @param  array         $all_plugins   The list of all installed plugins
 * @param  boolean       $is_network    Whether the multisite is installed
 * @return array                        The plugins data
 */
if ( ! function_exists( 'cptch_get_plugin_status' ) ) {
	function cptch_get_plugin_status( $plugins, $all_plugins, $is_network ) {
		$result = array(
			'status'      => '',
			'plugin'      => $plugins,
			'plugin_info' => array(),
		);
		foreach ( (array)$plugins as $plugin ) {
			if ( array_key_exists( $plugin, $all_plugins ) ) {
				if (
					( $is_network && is_plugin_active_for_network( $plugin ) ) ||
					( ! $is_network && is_plugin_active( $plugin ) )
				) {
					$result['status']      = 'active';
					$result['compatible']  = cptch_is_compatible( $plugin, $all_plugins[ $plugin ]['Version'] );
					$result['plugin_info'] = $all_plugins[$plugin];
					break;
				} else {
					$result['status']      = 'deactivated';
					$result['compatible']  = cptch_is_compatible( $plugin, $all_plugins[ $plugin ]['Version'] );
					$result['plugin_info'] = $all_plugins[$plugin];
				}
			}
		}

		if ( empty( $result['status'] ) )
			$result['status'] = 'not_installed';

		$result['link'] = cptch_get_plugin_link( $plugin );

		return $result;
	}
}

/**
 * Checks whether the INEOSQ CAPTCHA is compatible with the specified plugin version
 * @param  string   $plugin     The string in the format {plugin_folder}/{plugin_file}
 * @param  string   $version    The plugin version that is checked
 * @return boolean
 */
if ( ! function_exists( 'cptch_is_compatible' ) ) {
	function cptch_is_compatible( $plugin, $version ) {
		switch ( $plugin ) {
			case 'contact-form-plugin/contact_form.php':
				$min_version = '3.95';
				break;
			case 'contact-form-pro/contact_form_pro.php':
				$min_version = '2.0.6';
				break;
			case 'subscriber/subscriber.php':
				$min_version = '1.2.4';
				break;
			case 'subscriber-pro/subscriber-pro.php':
				$min_version = '1.0.6';
				break;
			case 'contact-form-7/wp-contact-form-7.php':
				$min_version = '3.4';
				break;			
			default:
				$min_version = false;
				break;
		}
		return $min_version ? version_compare( $version, $min_version, '>' ) : true;
	}
}

/**
* Fetch the plugin slug by the specified form slug
* @param   string  $form_slug   The form slug
* @return  string               The plugin slug
*/
if ( ! function_exists( 'cptch_get_plugin' ) ) {
	function cptch_get_plugin( $form_slug ) {
		switch( $form_slug ) {
			case 'general':
			case 'wp_login':
			case 'wp_register':
			case 'wp_lost_password':
			case 'wp_comments':
			default:
				return '';
				break;
			case 'ineosq_contact':
			case 'ineosq_booking':
			case 'ineosq_subscriber':
			case 'cf7_contact':
			case 'mailchimp' :
			case 'jetpack_contact_form' :
				return $form_slug;
				break;
			case 'buddypress_register':
			case 'buddypress_comments':
			case 'buddypress_group':
				return 'buddypress';
			case 'woocommerce_login':
			case 'woocommerce_register':
			case 'woocommerce_lost_password':
			case 'woocommerce_checkout':
				return 'woocommerce';
			case 'bbpress_new_topic_form' :
			case 'bbpress_reply_form' :
				return 'bbpress';
			case 'wpforo_login_form' :
			case 'wpforo_register_form':
			case 'wpforo_new_topic_form':
			case 'wpforo_reply_form' :
				return 'wpforo';
		}
	}
}

/**
 * Fetch the plugin download link
 * @param  string   $plugin     The string in the format {plugin_folder}/{plugin_file}
 * @return string               The plugin download link
 */
if ( ! function_exists( 'cptch_get_plugin_link' ) ) {
	function cptch_get_plugin_link( $plugin ) {
		global $wp_version, $cptch_plugin_info;
		$ineosq_link = "https://ineosq.com/products/wordpress/plugins/%1s/?k=%2s&pn=72&v={$cptch_plugin_info["Version"]}&wp_v={$wp_version}/";
		$wp_link  = 'http://wordpress.org/plugins/%s/';
		switch ( $plugin ) {
			case 'contact-form-plugin/contact_form.php':
			case 'contact-form-pro/contact_form_pro.php':
				return sprintf( $ineosq_link, 'contact-form', '9ab9d358ad3a23b8a99a8328595ede2e' );
			case 'ineosq-car-rental-pro/ineosq-car-rental-pro.php':
				return sprintf( $ineosq_link, 'car-rental-v2', '3c0c792876c76187abe0381d85d907c1' );
			case 'subscriber/subscriber.php':
			case 'subscriber-pro/subscriber-pro.php':
				return sprintf( $ineosq_link, 'subscriber', 'ab0b2bd6c0198d41a445663c23051080' );
			case 'limit-attempts/limit-attempts.php':
			case 'limit-attempts-pro/limit-attempts-pro.php':
				return sprintf( $ineosq_link, 'limit-attempts', 'c5ba37f86ebfc2754a71c759a5907888' );
			case 'contact-form-7/wp-contact-form-7.php':
				return sprintf( $wp_link, 'contact-form-7' );
			case 'buddypress/bp-loader.php':
				return sprintf( $wp_link, 'buddypress' );
			case 'woocommerce/woocommerce.php':
				return sprintf( $wp_link, 'woocommerce' );
			case 'bbpress/bbpress.php':
				return sprintf( $wp_link, 'bbpress' );
			case 'wpforo/wpforo.php':
				return sprintf( $wp_link, 'wpforo' );
			case 'mailchimp-for-wp/mailchimp-for-wp.php':
				return sprintf( $wp_link, 'mailchimp-for-wp' );
			case 'jetpack/jetpack.php':
				return sprintf( $wp_link, 'jetpack' );
			case 'ninja-forms/ninja-forms.php':
				return sprintf( $wp_link, 'ninja-forms' );
			case 'wpforms/wpforms.php':
			case 'wpforms-lite/wpforms.php':
				return sprintf( $wp_link, 'wpforms-lite' );
			default:
				return '#';
		}
	}
}

/**
 * Fetch the plugin name
 * @param  string   $plugin_slu     The plugin slug
 * @return string                   The plugin name
 */
if ( ! function_exists( 'cptch_get_plugin_name' ) ) {
	function cptch_get_plugin_name( $plugin_slug ) {
		switch( $plugin_slug ) {
			case 'ineosq_contact':
				return 'Contact Form by BestwebSoft';
			case 'ineosq_booking':
				return 'Car Rental V2 by IneosQ';
			case 'ineosq_subscriber':
				return 'Subscriber by BestwebSoft';
			case 'cf7_contact':
				return 'Contact Form 7';
			case 'buddypress':
				return 'BuddyPress';
			case 'woocommerce':
				return 'WooCommerce';
			case 'bbpress':
				return 'Bbpress';
			case 'wpforo':
				return 'Forums - wpForo';
			case 'mailchimp':
				return 'Mailchimp for Wordpress';
			case 'jetpack_contact_form':
				return 'Jetpack Contact Form';
			default:
				return 'unknown';
		}
	}
}
?>
