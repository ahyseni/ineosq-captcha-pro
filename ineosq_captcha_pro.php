<?php
/*
Plugin Name: Captcha Pro by IneosQ
Plugin URI: https://ineosq.com/products/wordpress/plugins/captcha/
Description: #1 super security anti-spam captcha plugin for WordPress forms.
Author: IneosQ
Text Domain: ineosq-captcha-pro
Domain Path: /languages
Version: 5.1.7
Author URI: https://ineosq.com/
License: Proprietary
*/

require_once( dirname( __FILE__ ) . '/includes/allowlist_reason_updater.php' );

if ( ! function_exists( 'cptch_pro_add_admin_menu' ) ) {
	function cptch_pro_add_admin_menu() {

		$is_network_admin = is_network_admin();

		$settings_page = add_menu_page(
            __( 'Captcha Pro Settings', 'ineosq-captcha-pro' ),
            'Captcha',
            'manage_options',
            'ineosq_captcha_pro.php',
            'cptch_page_router',
            'none'
        );

		add_submenu_page(
            'ineosq_captcha_pro.php',
            __( 'Captcha Pro Settings', 'ineosq-captcha-pro' ),
            __( 'Settings', 'ineosq-captcha-pro' ),
            'manage_options',
            'ineosq_captcha_pro.php',
            'cptch_page_router'
        );

		if ( ! is_multisite() || $is_network_admin ) {
			$packages_page = add_submenu_page(
                'ineosq_captcha_pro.php',
                __( 'Captcha Pro Packages', 'ineosq-captcha-pro' ),
                __( 'Packages', 'ineosq-captcha-pro' ),
                'manage_options',
                'captcha-packages.php',
                'cptch_page_router'
            );
			add_action( "load-{$packages_page}", 'cptch_add_tabs' );
		}

		if ( ! $is_network_admin ) {
			$allowlist_page = add_submenu_page(
                'ineosq_captcha_pro.php',
                __( 'Captcha Pro Allow List', 'ineosq-captcha-pro' ),
                __( 'Allow List', 'ineosq-captcha-pro' ),
                'manage_options',
                'captcha-allowlist.php',
                'cptch_page_router'
            );
			add_action( "load-{$allowlist_page}", 'cptch_add_tabs' );
		}

		add_submenu_page(
            'ineosq_captcha_pro.php',
            'INEOSQ Panel',
            'INEOSQ Panel',
            'manage_options',
            'cptch-ineosq-panel',
            'ineosq_add_menu_render'
        );

		add_action( "load-{$settings_page}", 'cptch_add_tabs' );
	}
}

/* add help tab */
if ( ! function_exists( 'cptch_add_tabs' ) ) {
	function cptch_add_tabs() {
		$args = array(
			'id'		=> 'cptch',
			'section'	=> '200538879'
		);
		ineosq_help_tab( get_current_screen(), $args );
	}
}

if ( ! function_exists( 'cptch_pro_include_plugins_files_before' ) ) {
	function cptch_pro_include_plugins_files_before() {

		/* Internationalization, first(!) */
		load_plugin_textdomain( 'ineosq-captcha-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		/* Detect Integrations */
		if ( class_exists( 'Ninja_Forms' ) ) {
			require_once( dirname( __FILE__ ) . '/includes/captcha_for_nf.php' );
		}

	}
}

if ( ! function_exists( 'cptch_pro_plugins_loaded' ) ) {
	function cptch_pro_plugins_loaded() {
		
		/* Compatibility with Gravity Forms */
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
			require_once( dirname( __FILE__ ) . '/includes/class-cptch-gf-field.php' );
		}

        if ( is_plugin_active( 'wpforms-lite/wpforms.php' ) || is_plugin_active( 'wpforms/wpforms.php' ) ) {
            require_once( dirname( __FILE__ ) . '/includes/class-wpforms-cptch.php' );
        }

        if ( is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
            require_once( dirname( __FILE__ ) . '/includes/class-elementor-cptch.php' );
        }
	}
}

if ( ! function_exists ( 'cptch_pro_init' ) ) {
	function cptch_pro_init() {
		global $cptch_plugin_info, $cptch_ip_in_allowlist, $cptch_options, $pagenow;

		require_once(dirname(__FILE__) . '/ineosq_menu/ineosq_include.php');
		ineosq_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $cptch_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$cptch_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version */
		ineosq_wp_min_version_check( 'ineosq-captcha-pro/ineosq_captcha_pro.php', $cptch_plugin_info, '4.5' );

		cptch_update_activate();

		$is_user_edit_page = isset( $pagenow ) && 'user-edit.php' == $pagenow;
		$is_admin = is_admin() && ( ! defined( 'DOING_AJAX' ) || ! $is_user_edit_page );
		$is_ajax_form = ( 
			( function_exists( 'is_wpforo_page' ) && is_wpforo_page() ) ||
			( function_exists( 'bp_is_current_component' ) && ( bp_is_current_component ( 'groups' ) || bp_is_current_component ( 'forums' ) ) )
			&& defined( 'DOING_AJAX' ) );
			$is_ajax_form = true;
		/* Call register settings function */
		$pages = array(
			'ineosq_captcha_pro.php',
			'captcha-packages.php',
			'captcha-allowlist.php'
		);
		if ( ! $is_admin || ( isset( $_GET['page'] ) && in_array( $_GET['page'], $pages ) ) ) {
			register_cptch_settings();
		}
		if ( $is_admin && ! $is_ajax_form ) {
			return;
		}
		$user_loggged_in		= is_user_logged_in();
		$cptch_ip_in_allowlist	= cptch_allowlisted_ip();

		if ( ! ( function_exists( 'is_wpforo_page' ) && is_wpforo_page() ) ) {
			/*
			 * Add the CAPTCHA to the WP login form
			 */
			if ( $cptch_options['forms']['wp_login']['enable'] ) {

				add_action( 'login_form', 'cptch_login_form' );
				add_action( 'bp_login_widget_form', 'cptch_buddypress_login_widget' );

				if ( ! $cptch_ip_in_allowlist ) {
					add_filter( 'authenticate', 'cptch_login_check', 21, 1 );
					/*add_filter( 'wp_authenticate_user', 'cptch_login_check', 21, 1 );*/
				}
			}

			/*
			 * Add the CAPTCHA to the WP register form
			 */
			if ( $cptch_options['forms']['wp_register']['enable'] ) {

				add_action( 'register_form', 'cptch_register_form' );
				add_action( 'signup_extra_fields', 'wpmu_cptch_register_form' );
				add_action( 'signup_blogform', 'wpmu_cptch_register_form' );

				if ( ! $cptch_ip_in_allowlist ) {
					add_filter( 'registration_errors', 'cptch_register_check', 9, 1 );
					if ( is_multisite() ) {
						add_filter( 'wpmu_validate_user_signup', 'cptch_register_validate' );
						add_filter( 'wpmu_validate_blog_signup', 'cptch_register_validate' );
					}
				}
			}
		}

		/*
		 * Add the CAPTCHA into the WP lost password form
		 */
		if ( $cptch_options['forms']['wp_lost_password']['enable'] ) {
			add_action( 'lostpassword_form', 'cptch_lostpassword_form' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'allow_password_reset', 'cptch_lostpassword_check' );
			}
		}

		/*
		 * Add the CAPTCHA to the WP comments form
		 */
		if ( cptch_captcha_is_needed( 'wp_comments', $user_loggged_in ) ) {
			/*
			 * Common hooks to add necessary actions for the WP comment form,
			 * but some themes don't contain these hooks in their comments form templates
			 */
			add_action( 'comment_form_after_fields', 'cptch_comment_form_wp3', 1 );
			add_action( 'comment_form_logged_in_after', 'cptch_comment_form_wp3', 1 );
			/*
			 * Try to display the CAPTCHA before the close tag </form>
			 * in case if hooks 'comment_form_after_fields' or 'comment_form_logged_in_after'
			 * are not included to the theme comments form template
			 */
			add_action( 'comment_form', 'cptch_comment_form' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'preprocess_comment', 'cptch_comment_post' );
			}
		}

		/*
		 * Add the CAPTCHA to the Contact Form by IneosQ plugin forms
		 */
		if ( cptch_captcha_is_needed( 'ineosq_contact', $user_loggged_in ) ) {
			add_filter( 'cntctfrmpr_display_captcha', 'cptch_cf_form', 10, 2 );
			add_filter( 'cntctfrm_display_captcha', 'cptch_cf_form', 10, 2 );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'cntctfrm_check_form', 'cptch_check_ineosq_contact_form' );
				add_filter( 'cntctfrmpr_check_form', 'cptch_check_ineosq_contact_form' );
			}
		}

		/*
		 * Add the CAPTCHA to the Subscriber by IneosQ form
		 */
		if ( cptch_captcha_is_needed( 'ineosq_subscriber', $user_loggged_in ) ) {
			add_filter( 'sbscrbr_add_field', 'cptch_custom_form', 10, 2 );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'sbscrbr_check', 'cptch_check_ineosq_subscriber_form' );
			}
		}

		/*
		 * Add the CAPTCHA to the Contact Form 7 plugin form
		 */
		if ( $cptch_options['forms']['cf7_contact']['enable'] ) {
			require_once( dirname( __FILE__ ) . '/includes/captcha_for_cf7.php' );

			/* add shortcode handler */
			wpcf7_add_shortcode_ineosq_captcha();
			if ( ! $cptch_ip_in_allowlist && cptch_captcha_is_needed( 'cf7_contact', $user_loggged_in ) ) {
				/* validation for captcha */
				add_filter( 'wpcf7_validate_ineosqcaptcha', 'wpcf7_ineosq_captcha_validation_filter', 10, 2 );
				/* add messages for Captha errors */
				add_filter( 'wpcf7_messages', 'wpcf7_ineosqcaptcha_messages' );
				/* add warning message */
				add_action( 'wpcf7_admin_notices', 'wpcf7_ineosqcaptcha_display_warning_message' );
			}
		}

		/*
		 * Add the CAPTCHA to the BuddyPress plugin registration form
		 */
		if ( $cptch_options['forms']['buddypress_register']['enable'] ) {
			add_action( 'bp_before_registration_submit_buttons', 'cptch_buddypress_registration' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_action( 'bp_signup_validate', 'cptch_buddypress_registration_validate' );
			}
		}

		/*
		 * Add the CAPTCHA to the BuddyPress plugin comments form
		 */
		if ( $cptch_options['forms']['buddypress_comments']['enable'] ) {
			cptch_add_scripts();
			add_action( 'bp_activity_entry_comments', 'cptch_buddypress_comment_form' );
			if ( ! $cptch_ip_in_allowlist ) {
				/**
				 * Verifies the CAPTCHA answer in the BuddyPress comment form
				 * @uses  if JS is disabled
				 * @see   bp_activity_action_post_comment(),
				 * @link  https://github.com/trishasalas/BuddyPress-cpt/blob/master/bp-activity/bp-activity-actions.php
				 * @since 4.2.3
				 */
				add_action( 'bp_activity_post_comment_activity_id', 'cptch_buddypress_comment_form_validate', 0 );
				/* validation when js is able */
				add_action( 'wp_head', 'cptch_buddypress_comment_wp_head' );
			}
		}

		/*
		 * Add the CAPTCHA to the BuddyPress plugin add group form
		 */
		if ( $cptch_options['forms']['buddypress_group']['enable'] ) {
			add_action( 'bp_after_group_details_creation_step', 'cptch_buddypress_group_form' );
			/**
			 * Verifies the CAPTCHA answer in the BuddyPress Create Group form
			 * @see   groups_action_create_group(),
			 * @link  https://github.com/buddypress/BuddyPress/blob/master/src/bp-groups/bp-groups-actions.php
			 * @since 4.2.3
			 */
			if ( ! $cptch_ip_in_allowlist ) {
				add_action( 'groups_group_before_save', 'cptch_buddypress_create_group_check' );
			}
		}

		/*
		 * Add the CAPTCHA to the WooCommerce plugin login form
		 */
		if ( $cptch_options['forms']['woocommerce_login']['enable'] ) {
			add_action( 'woocommerce_login_form', 'cptch_woocommerce_login' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'woocommerce_process_login_errors', 'cptch_woocommerce_login_check' );
			}
		}

		/*
		 * Add the CAPTCHA to the WooCommerce plugin register form
		 */
		add_action( 'woocommerce_register_form_start', 'cptch_woocommerce_remove_register_action' );
		if ( $cptch_options['forms']['woocommerce_register']['enable'] ) {
			add_action( 'woocommerce_register_form', 'cptch_woocommerce_register' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'woocommerce_process_registration_errors', 'cptch_woocommerce_register_check' );
			}
		}

		/*
		 * Add the CAPTCHA to the WooCommerce plugin lost password
		 */
		if ( $cptch_options['forms']['woocommerce_lost_password']['enable'] ) {
			add_action( 'woocommerce_lostpassword_form', 'cptch_woocommerce_lost_password' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'allow_password_reset', 'cptch_woocommerce_allow_password_reset', 9 );
			}
		}

		/*
		 * Add the CAPTCHA to the WooCommerce plugin checkout billing form
		 */
		if ( cptch_captcha_is_needed( 'woocommerce_checkout', $user_loggged_in ) ) {
			add_action( 'woocommerce_after_checkout_billing_form', 'cptch_woocommerce_checkout' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_action( 'woocommerce_checkout_process', 'cptch_woocommerce_checkout_process' );
			}
		}
		/*
		 * Add the CAPTCHA to bbPress New Topic, Reply to Topic forms
		 */
		if ( class_exists( 'bbPress' ) ) {
			if ( cptch_captcha_is_needed( 'bbpress_new_topic_form', $user_loggged_in ) ) {
				add_action( 'bbp_theme_after_topic_form_content', 'cptch_bbpress_new_topic_display', 10, 0 );
				if ( ! $cptch_ip_in_allowlist ) {
					add_action( 'bbp_new_topic_pre_extras', 'cptch_bbpress_new_topic_check' );
				}
			}
			if ( cptch_captcha_is_needed( 'bbpress_reply_form', $user_loggged_in ) ) {
				add_action( 'bbp_theme_after_reply_form_content', 'cptch_bbpress_reply_display', 10, 0 );
				if ( ! $cptch_ip_in_allowlist ) {
					add_action( 'bbp_new_reply_pre_extras', 'cptch_bbpress_reply_check' );
				}
			}
		}
		/*
		 * Add the CAPTCHA to wpForo forms
		 */
		if ( function_exists( 'is_wpforo_page' ) && is_wpforo_page() ) {
			if ( cptch_captcha_is_needed( 'wpforo_login_form', $user_loggged_in ) ) {
				add_action( 'login_form', 'cptch_login_form' );
				if ( ! $cptch_ip_in_allowlist ) {
					add_action( 'authenticate', 'cptch_wpforo_login_check', 21, 1 );
				}
			}
			if ( cptch_captcha_is_needed( 'wpforo_register_form', $user_loggged_in ) ) {
				if ( ! is_multisite() ) {
					add_action( 'register_form', 'cptch_login_form', 99 );
					if ( ! $cptch_ip_in_allowlist ) {
						add_action( 'registration_errors', 'cptch_wpforo_register_check', 10, 1 );
					}
				} else {
					add_action( 'signup_extra_fields', 'cptch_signup_display' );
					add_action( 'signup_blogform', 'cptch_signup_display' );
					if ( ! $cptch_ip_in_allowlist ) {
						add_filter( 'wpmu_validate_user_signup', 'cptch_register_validate', 10, 3 );
					}
				}
			}

			if ( cptch_captcha_is_needed( 'wpforo_new_topic_form', $user_loggged_in ) ) {
				cptch_add_scripts();
				add_action( 'wpforo_topic_form_buttons_hook', 'cptch_wpforo_new_topic_display', 99, 0 );
				if ( ! $cptch_ip_in_allowlist ) {
					add_filter( 'wpforo_add_topic_data_filter', 'cptch_wpfpro_topic_check', 10, 1 );
				}
			}

			if ( cptch_captcha_is_needed( 'wpforo_reply_form', $user_loggged_in ) ) {
				add_action( 'wpforo_reply_form_buttons_hook', 'cptch_wpforo_reply_display', 99, 0 );
				add_action( 'wpforo_portable_form_buttons_hook', 'cptch_wpforo_reply_display', 99, 0 );
				if ( ! $cptch_ip_in_allowlist ) {
					add_filter( 'wpforo_add_post_data_filter', 'cptch_wpfpro_reply_check', 10, 1 );
				}
			}
		}
		/*
		 * Add Captcha to Mailchimp for WordPress
		 */
		if ( cptch_captcha_is_needed( 'mailchimp', $user_loggged_in ) ) {
			add_filter( 'mc4wp_form_content', 'cptch_mailchimp_display', 20, 3 );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'mc4wp_form_messages', 'cptch_mailchimp_check_message', 10, 2 );
				add_filter( 'mc4wp_valid_form_request', 'cptch_mailchimp_check', 10, 2 );
			}
		}
		/*
		 * Add the CAPTCHA to Jetpack contact form
		 */
		if ( cptch_captcha_is_needed( 'jetpack_contact_form', $user_loggged_in ) ) {
			add_filter( 'the_content', 'cptch_jetpack_cf_display' );
			add_filter( 'widget_text', 'cptch_jetpack_cf_display', 0 );
			add_filter( 'widget_text', 'shortcode_unautop' );
			add_filter( 'widget_text', 'do_shortcode' );
			if ( ! $cptch_ip_in_allowlist ) {
				add_filter( 'jetpack_contact_form_is_spam', 'cptch_jetpack_cf_check' );
			}
		}

	}
}

if ( ! function_exists ( 'cptch_pro_admin_init' ) ) {
	function cptch_pro_admin_init() {
		global $pagenow, $ineosq_plugin_info, $cptch_plugin_info, $cptch_options, $ineosq_shortcode_list;
		/* Add variable for ineosq_menu */
		if ( empty( $ineosq_plugin_info ) ) {
			$ineosq_plugin_info = array( 'id' => '72', 'version' => $cptch_plugin_info["Version"] );
		}

		/**
		 * add CAPTCHA to global $ineosq_shortcode_list
		 * @since 4.2.3
		 */
		$ineosq_shortcode_list['cptch'] = array( 'name' => 'Captcha' );

		if ( function_exists( 'ineosq_add_plugin_banner_timeout' ) )
			ineosq_add_plugin_banner_timeout( 'ineosq-captcha-pro/ineosq_captcha_pro.php', 'cptch', $cptch_plugin_info['Name'], 'captcha-ineosq' );
		
		/**
		 * Add INEOSQ CAPTCHA shortcode button in to the tool panel on the Contact Form 7 pages
		 * @since 4.2.3
		 */
		if ( ! isset( $_REQUEST['page'] ) || ! preg_match( '/wpcf7/', $_REQUEST['page'] ) )
			return;

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}

		if ( $cptch_options['forms']['cf7_contact']['enable'] ) {
			require_once( dirname( __FILE__ ) . '/includes/captcha_for_cf7.php' );
			wpcf7_add_tag_generator_ineosq_captcha();
		}
	}
}

if ( ! function_exists( 'cptch_create_pro_table' ) ) {
	function cptch_create_pro_table() {
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}cptch_allowlist` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`ip` CHAR(31) NOT NULL,
			`ip_from` CHAR(15) NOT NULL,
			`ip_to` CHAR(15) NOT NULL,
			`ip_from_int` BIGINT,
			`ip_to_int` BIGINT,
			`add_time` DATETIME,
			`add_reason` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci,
			PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}cptch_images` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` CHAR(100) NOT NULL,
			`package_id` INT NOT NULL,
			`number` INT NOT NULL,
			PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}cptch_packages` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` CHAR(100) NOT NULL,
			`folder` CHAR(100) NOT NULL,
			`settings` LONGTEXT NOT NULL,
			`user_settings` LONGTEXT NOT NULL,
			`add_time` DATETIME NOT NULL,
			PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->base_prefix}cptch_responses` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
			`response` CHAR(100) NOT NULL,
			`add_time` INT(20) NOT NULL,
			PRIMARY KEY (`id`)
			) DEFAULT CHARSET=utf8;";
		dbDelta( $sql );

		/* add new columns to the 'allowlist' table */
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `{$wpdb->prefix}cptch_allowlist` LIKE 'add_time'" );
		if ( 0 == $column_exists )
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}cptch_allowlist` ADD `add_time` DATETIME;" );
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `{$wpdb->prefix}cptch_allowlist` LIKE 'add_reason'" );
		if ( 0 == $column_exists )
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}cptch_allowlist` ADD `add_reason` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci;" );
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `{$wpdb->prefix}cptch_allowlist` LIKE 'ip_to'" );
		if ( 0 == $column_exists )
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}cptch_allowlist` ADD `ip_to` CHAR(15);" );
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `{$wpdb->prefix}cptch_allowlist` LIKE 'ip_from'" );
		if ( 0 == $column_exists )
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}cptch_allowlist` ADD `ip_from` CHAR(15);" );

		/* add unique key */
		if ( 0 == $wpdb->query( "SHOW KEYS FROM `{$wpdb->prefix}cptch_allowlist` WHERE Key_name='ip'" ) )
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}cptch_allowlist` ADD UNIQUE(`ip`);" );
		/* remove not necessary indexes */
		$indexes = $wpdb->get_results( "SHOW INDEX FROM `{$wpdb->prefix}cptch_allowlist` WHERE Key_name Like '%ip_%'" );
		if ( ! empty( $indexes ) ) {
			$query = "ALTER TABLE `{$wpdb->prefix}cptch_allowlist`";
			$drop = array();
			foreach( $indexes as $index )
				$drop[] = " DROP INDEX {$index->Key_name}";
			$query .= implode( ',', $drop );
			$wpdb->query( $query );
		}

		/**
		 * add new columns to the 'cptch_packages' table
		 * @since 1.6.9
		 */
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM `{$wpdb->base_prefix}cptch_packages` LIKE 'settings'" );
		if ( 0 == $column_exists ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->base_prefix}cptch_packages` ADD (`settings` LONGTEXT NOT NULL, `user_settings` LONGTEXT NOT NULL, `add_time` DATETIME NOT NULL );" );
			$wpdb->update(
				"{$wpdb->base_prefix}cptch_packages",
				array( 'add_time' => current_time( 'mysql' ) ),
				array( 'add_time' => '0000-00-00 00:00:00' )
			);
		}

	}
}

if ( ! function_exists( 'cptch_plugin_activation' ) ) {
	function cptch_plugin_activation() {
		global $wpdb;

		$all_plugins = get_plugins();

		/* download akismet */
		if ( ! array_key_exists( 'akismet/akismet.php', $all_plugins ) ) {
			$uploadDir = wp_upload_dir();
			if ( is_writable( $uploadDir["path"] ) ) {
				$url = 'http://downloads.wordpress.org/plugin/akismet.zip';
				$received_content = @file_get_contents( $url );
				$created_zip = $uploadDir["path"] . "/akismet.zip";
				if ( file_put_contents( $created_zip, $received_content ) ) {
					if ( class_exists( 'ZipArchive' ) ) {
						$zip = new ZipArchive();
						if ( $zip->open( $created_zip ) === TRUE ) {
							$zip->extractTo( WP_PLUGIN_DIR );
							$zip->close();
						}
					} elseif ( class_exists( 'Phar' ) ) {
						try {
							$phar = new PharData( $created_zip );
							$phar->extractTo( WP_PLUGIN_DIR );
						} catch ( Exception $e ) {
							/* handle errors */
						}
					} else {
						return;
					}
					@unlink( $created_zip );
				}
			}
		}

		/* Activation function for network, check if it is a network activation - if so, run the activation function for each blog id */
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				cptch_create_pro_table();
				register_cptch_settings();
			}

			switch_to_blog( 1 );
			register_uninstall_hook( __FILE__, 'cptch_delete_options' );
			switch_to_blog( $old_blog );
		} else {
			cptch_create_pro_table();
			register_cptch_settings();
			register_uninstall_hook( __FILE__, 'cptch_delete_options' );
		}

		/* add all scheduled hooks */
		cptch_add_scheduled_hook();
	}
}

/* register settings function */
if ( ! function_exists( 'register_cptch_settings' ) ) {
	function register_cptch_settings() {
		global $cptch_options, $cptch_plugin_info, $wpdb;

		if ( empty( $cptch_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$cptch_plugin_info = get_plugin_data( __FILE__ );
		}

		$is_network = is_network_admin();
		$need_update = false;
		$db_version = 'pro_1.8';
		/*
		 * Get plugin options from the database
		 */
		$cptch_options = $is_network ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );

		if ( empty( $cptch_options ) ) {
			if ( ! function_exists( 'cptch_get_default_options' ) ) {
				require_once( dirname( __FILE__ ) . '/includes/helpers.php' );
			}

			if ( $is_network ) {
				$cptch_options = cptch_get_default_options();
				add_site_option( 'cptch_options', $cptch_options );
			} else {
				if ( is_multisite() && $cptch_options = get_site_option( 'cptch_options' ) ) {
					unset(
						$cptch_options['network_apply'],
						$cptch_options['network_view'],
						$cptch_options['network_change']
					);
				} else {
					$cptch_options = cptch_get_default_options();
				}
				update_option( 'cptch_options', $cptch_options );
			}
		}
		/*
		 * Update old plugin options to the current version
		 */

        /* Update tables when update plugin and tables changes*/
        if (
            empty( $cptch_options['plugin_db_version'] ) ||
            $cptch_options['plugin_db_version'] != $db_version
        ) {

            /**
             * @deprecated since 5.1.0
             * @todo remove after 13.05.2021
             */
            if ( isset( $cptch_options['plugin_option_version'] ) &&  version_compare( str_replace( 'pro-', '', $cptch_options['plugin_option_version'] ) , '5.1.0', '<' ) )  {
                $prefix = $wpdb->prefix . 'cptch_';

                /* Update tables when update plugin and tables changes */
                $wpdb->query( "RENAME TABLE `" . $prefix . "whitelist` TO `" . $prefix . "allowlist`" );

                /*Update options_default when update plugin*/
                $cptch_options['allowlist_message'] = $cptch_options['whitelist_message'];
                $cptch_options['use_limit_attempts_allowlist'] = $cptch_options['use_limit_attempts_whitelist'];
            }

            /* end deprecated */

            $need_update = true;
            cptch_create_pro_table();

            if ( empty( $cptch_options['plugin_db_version'] ) ) {
                if ( ! class_exists( 'Cptch_Package_Loader' ) ) {
                    require_once( dirname( __FILE__ ) . '/includes/class-cptch-package-loader.php' );
                }
                $package_loader = new Cptch_Package_Loader();
                $package_loader->save_packages( dirname( __FILE__ ) . '/images/package', false );
            }

            $cptch_options['plugin_db_version'] = $db_version;
        }

		if (
			empty( $cptch_options['plugin_option_version'] ) ||
			$cptch_options['plugin_option_version'] != 'pro-' . $cptch_plugin_info["Version"]
		) {
			$need_update = true;
			if ( is_multisite() ) {
				switch_to_blog( 1 );
				register_uninstall_hook( __FILE__, 'cptch_delete_options' );
				restore_current_blog();
			} else {
				register_uninstall_hook( __FILE__, 'cptch_delete_options' );
			}

			if ( ! function_exists( 'cptch_get_default_options' ) ) {
				require_once( dirname( __FILE__ ) . '/includes/helpers.php' );
			}
			$default_options = cptch_get_default_options();

			$cptch_options = cptch_parse_options( $cptch_options, $default_options );
			/* Enabling notice about possible conflict with W3 Total Cache */
			if ( version_compare( preg_replace( '/pro-/', '', $cptch_options['plugin_option_version'] ), '4.2.7', '<=' ) ) {
				$cptch_options['w3tc_notice'] = 1;
			}
		}

		if ( $need_update ) {
			if ( $is_network ) {
				update_site_option( 'cptch_options', $cptch_options );
			} else {
				update_option( 'cptch_options', $cptch_options );
			}
		}
		/* activate akismet */
		if ( ! $cptch_options['activate_akismet'] ) {
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			$all_plugins = get_plugins();
			if ( array_key_exists( 'akismet/akismet.php', $all_plugins ) ) {
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				}
				if ( $is_network ) {
					if ( ! is_plugin_active_for_network( 'akismet/akismet.php' ) ) {
						$active_plugins = get_site_option( 'active_sitewide_plugins' );
						$active_plugins['akismet/akismet.php'] = time();
						update_site_option( 'active_sitewide_plugins', $active_plugins );
					}
				} else {
					$active_plugins = get_option( 'active_plugins' );
					if ( 0 >= count( preg_grep( '/akismet\/akismet.php/', $active_plugins ) ) && ! is_plugin_active_for_network( 'akismet/akismet.php' ) ) {
						array_push( $active_plugins, 'akismet/akismet.php' );
						update_option( 'active_plugins', $active_plugins );
					}
				}

				/* add key */
				if ( ! get_option( 'wordpress_api_key' ) ) {
					add_option( 'wordpress_api_key' );
				}
				$wpcom_api_key = get_option( 'wordpress_api_key' );
				if ( empty( $wpcom_api_key ) ) {
					global $wpcom_api_key;
					$wpcom_api_key = '2ca97eada1ae';
					update_option( 'wordpress_api_key', $wpcom_api_key );
				}
			}
			$cptch_options['activate_akismet'] = true;
			if ( $is_network ) {
				update_site_option( 'cptch_options', $cptch_options );
			} else {
				update_option( 'cptch_options', $cptch_options );
			}
		}
	}
}

/* generate key */
if ( ! function_exists( 'cptch_generate_key' ) ) {
	function cptch_generate_key( $lenght = 15 ) {
		global $cptch_options;
		/* Under the string $simbols you write all the characters you want to be used to randomly generate the code. */
		$simbols = get_bloginfo( "url" ) . time();
		$simbols_lenght = strlen( $simbols );
		$simbols_lenght--;
		$str_key = NULL;
		for ( $x = 1; $x <= $lenght; $x++ ) {
			$position = rand( 0, $simbols_lenght );
			$str_key .= substr( $simbols, $position, 1 );
		}

		$cptch_options['str_key']['key'] = md5( $str_key );
		$cptch_options['str_key']['time'] = time();
		update_option( 'cptch_options', $cptch_options );
	}
}

if ( ! function_exists( 'cptch_allowlisted_ip' ) ) {
	function cptch_allowlisted_ip() {
		global $wpdb, $cptch_options;
		$checked = false;
		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}
		$allowlist_exist = $column_exists = 0;
		$table = 1 == $cptch_options['use_limit_attempts_allowlist'] ? 'lmtttmpts_allowlist' : 'cptch_allowlist';
		if ( ! isset( $cptch_options['allowlist_exist'] ) ) {
			$allowlist_exist = $wpdb->query( "SHOW TABLES LIKE '{$wpdb->prefix}{$table}'" );
			$cptch_options['allowlist_exist'] = 1;
			update_option( 'cptch_options', $cptch_options );
		} else if ( 1 == $cptch_options['allowlist_exist'] ) {
			$allowlist_exist = 1;
		}
		if ( ! isset( $cptch_options['ip_from_exist'] ) ) {
			$column_exists = $wpdb->query( "SHOW COLUMNS FROM `{$wpdb->prefix}{$table}` LIKE 'ip_from_int'" );
			$cptch_options['ip_from_exist'] = 1;
			update_option( 'cptch_options', $cptch_options );
		} else if ( 1 == $cptch_options['ip_from_exist'] ) {
			$column_exists = 1;
		}
		if ( 1 === $allowlist_exist ) {
			$ip = cptch_get_ip();
			if ( ! empty( $ip ) ) {
				/* LimitAttempts Free hasn't `ip_from_int`, `ip_to_int` COLUMNS */
				if ( 0 == $column_exists ) {
					$result = $wpdb->get_var( $wpdb->prepare(
						"SELECT `id`
						FROM `{$wpdb->prefix}{$table}`
						WHERE `ip` = %s LIMIT 1;",
						$ip 
					) );
				} else {
					$ip_int = sprintf( '%u', ip2long( $ip ) );
					$result = $wpdb->get_var( $wpdb->prepare(
						"SELECT `id`
						FROM `{$wpdb->prefix}{$table}`
						WHERE ( `ip_from_int` <= %d AND `ip_to_int` >= %d ) OR `ip` LIKE %s LIMIT 1;", 
						$ip_int, $ip_int, $ip 
					) );
				}
				$checked = is_null( $result ) || false === $result ? false : true;
			} else {
				$checked = false;
			}
		}
		return $checked;
	}
}

/**
 * Function displays captcha admin-pages
 * @see   groups_action_create_group(),
 * @since 4.3.1
 * @return void
 */
if ( ! function_exists( 'cptch_page_router' ) ) {
	function cptch_page_router() {
		if ( 'ineosq_captcha_pro.php' == $_GET['page'] ) {
			if ( ! class_exists( 'Ineosq_Settings_Tabs' ) ) {
                require_once(dirname(__FILE__) . '/ineosq_menu/class-ineosq-settings.php');
			}
			require_once( dirname( __FILE__ ) . '/includes/class-cptch-settings-tabs.php' );

			$page = new Cptch_Settings_Tabs( plugin_basename( __FILE__ ) ); 
			if ( method_exists( $page, 'add_request_feature' ) ) {
            	$page->add_request_feature();
			} ?>

			<div class="wrap">		
				<h1><?php _e( 'Captcha Pro Settings', 'ineosq-captcha-pro' ); ?></h1>
                <noscript>
                    <div class="error below-h2">
                        <p><strong><?php _e( 'WARNING', 'ineosq-captcha-pro' ); ?>:</strong> <?php _e( 'The plugin works correctly only if JavaScript is enabled.', 'ineosq-captcha-pro' ); ?></p>
                    </div>
                </noscript>
				<?php $page->display_content(); ?>
			</div>
		<?php } else { ?>
			<div class="wrap">
				<?php require_once( dirname( __FILE__ ) . '/includes/helpers.php' );
				switch ( $_GET['page'] ) {
					case 'captcha-packages.php':
						if ( ! class_exists( 'Cptch_Package_Loader' ) ) {
							require_once( dirname( __FILE__ ) . '/includes/class-cptch-package-list.php' );
						}

						$page = new Cptch_Package_List();
						break;
					case 'captcha-allowlist.php':
						require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
						require_once( dirname( __FILE__ ) . '/includes/class-cptch-allowlist.php' );

						$limit_attempts_info = cptch_get_plugin_status( array( 'limit-attempts/limit-attempts.php', 'limit-attempts-pro/limit-attempts-pro.php' ), get_plugins(), is_network_admin() );
						$page = new Cptch_allowlist( plugin_basename( __FILE__ ), $limit_attempts_info );
						break;
					default:
						return;
				}
				$page->display_content(); ?>
			</div>
		<?php } ?>		
	<?php }
}

if ( ! function_exists( 'cptch_get_ip' ) ) {
	function cptch_get_ip() {
		$ip = '';
		if ( isset( $_SERVER ) ) {
			$server_vars = array( 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
			foreach( $server_vars as $var ) {
				if ( isset( $_SERVER[ $var ] ) && ! empty( $_SERVER[ $var ] ) ) {
					if ( filter_var( $_SERVER[ $var ], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_IPV4 ) ) {
						$ip = $_SERVER[ $var ];
						break;
					} else { /* if proxy */
						$ip_array = explode( ',', $_SERVER[ $var ] );
						if ( is_array( $ip_array ) && ! empty( $ip_array ) && filter_var( $ip_array[0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_IPV4 ) ) {
							$ip = $ip_array[0];
							break;
						}
					}
				}
			}
		}
		return $ip;
	}
}

/************** WP LOGIN FORM HOOKS ********************/

/* this function adds captcha to the login form */
if ( ! function_exists( 'cptch_login_form' ) ) {
	function cptch_login_form() {
		global $cptch_ip_in_allowlist;
		if ( ! $cptch_ip_in_allowlist ) {
			if ( '' == session_id() ) {
				@session_start();
			}
			if ( isset( $_SESSION['cptch_login'] ) ) {
				unset( $_SESSION['cptch_login'] );
			}
		}

		echo cptch_display_captcha_custom( 'wp_login', 'cptch_wp_login' ) . '<br />';
		return true;
	}
}

/* this function checks the captcha posted with a login when login errors are absent */
if ( ! function_exists( 'cptch_login_check' ) ) {
	function cptch_login_check( $user ) {
		if (
			! isset( $_POST['wp-submit'] ) ||
			( isset( $_SESSION['cptch_login'] ) && true === $_SESSION["cptch_login"] ) ||
			cptch_is_limit_login_attempts_active()
		) {
			return $user;
		}

		if ( '' == session_id() ) {
			@session_start();
		}

		$user = cptch_check_custom_form( $user, 'wp_error', 'wp_login' );
		$_SESSION['cptch_login'] = is_wp_error( $user );

		return $user;
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_is_limit_login_attempts_active' ) ) {
	function cptch_is_limit_login_attempts_active() {
		if ( ! function_exists( 'is_plugin_active' ) )
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return
			is_plugin_active( 'limit-login-attempts/limit-login-attempts.php' ) &&
			isset( $_REQUEST['loggedout'] ) &&
			isset( $_REQUEST['cptch_number'] ) &&
			'' == $_REQUEST['cptch_number'];
	}
}

/************** WP REGISTER FORM HOOKS ********************/

/* this function adds the captcha to the register form */
if ( ! function_exists ( 'cptch_register_form' ) ) {
	function cptch_register_form() {
		echo cptch_display_captcha_custom( 'wp_register', 'cptch_wp_register' ) . '<br />';
		return true;
	}
}

/* this function adds the captcha to the register form on multisite */
if ( ! function_exists ( 'wpmu_cptch_register_form' ) ) {
	function wpmu_cptch_register_form( $errors ) {
		global $cptch_options, $cptch_ip_in_allowlist;
		/* the captcha html - register form */
		echo '<div class="cptch_block">';
		if ( '' != $cptch_options['title'] ) {
			echo '<span class="cptch_title">' . $cptch_options['title'] . '<span class="required"> ' . $cptch_options['required_symbol'] . '</span></span>';
		}
		if ( ! $cptch_ip_in_allowlist ) {
			if ( is_wp_error( $errors ) ) {
				$error_codes = $errors->get_error_codes();
				if ( is_array( $error_codes ) && ! empty( $error_codes ) ) {
					foreach ( $error_codes as $error_code ) {
						if ( preg_match( '/(captcha_)|(lmttmptspr_)/', $error_code ) ) {
							echo '<p class="error">' . $errors->get_error_message( $error_code ) . '</p>';
						}
					}
				}
			}

			echo cptch_display_captcha( 'wp_register' );
		} else
			echo '<label class="cptch_allowlist_message">' . $cptch_options['allowlist_message'] . '</label>';
		echo '</div><br />';
	}
}

if ( ! function_exists ( 'cptch_register_check' ) ) {
	function cptch_register_check( $error ) {
		return cptch_check_custom_form( $error, 'wp_error', 'wp_register' );

	}
}

if ( ! function_exists ( 'cptch_register_validate' ) ) {
	function cptch_register_validate( $results ) {
		$results['errors'] = cptch_check_custom_form( $results['errors'], 'wp_error', 'wp_register' );
		if ( $results['errors']->get_error_messages() ) {
			$cptch_error = $results['errors']->get_error_message( 'cptch_error' );
			$lmtttmpts_error = $results['errors']->get_error_message( 'cptch_error_lmttmpts' );
			$error_message = $cptch_error . '<br>' . $lmtttmpts_error;
			$results['errors']->add( 'generic', $error_message );
		}
		return $results;
	}
}

/************** WP LOST PASSWORD FORM HOOKS ********************/

/* this function adds the captcha to the lost password form */
if ( ! function_exists ( 'cptch_lostpassword_form' ) ) {
	function cptch_lostpassword_form() {
		echo cptch_display_captcha_custom( 'wp_lost_password', 'cptch_wp_lost_password' ) . '<br />';
		return true;
	}
}

/* this function checks the captcha posted with lostpassword form */
if ( ! function_exists ( 'cptch_lostpassword_check' ) ) {
	function cptch_lostpassword_check( $allow ) {
		/**
		 * prevent the repeated checking of the WooCommerce lost password form
		 * @since 1.6.9
		 */
		$woocommerce = cptch_is_woocommerce_page();
		if ( ( is_wp_error( $allow ) && array_key_exists( 'cptch_error', $allow->errors ) ) || $woocommerce ) {
			return $allow;
		}

		$allow = cptch_check_custom_form( $allow, 'wp_error', 'wp_lost_password' );
		if ( is_string( $allow ) ) {
			$error = new WP_Error();
			$error->add( 'cptch_la_error', $allow );
			return $error;
		}
		return $allow;
	}
}

/************** WP COMMENT FORM HOOKS ********************/

/* this function adds captcha to the comment form */
if ( ! function_exists ( 'cptch_comment_form' ) ) {
	function cptch_comment_form() {
		echo cptch_display_captcha_custom( 'wp_comments', 'cptch_wp_comments' );
		return true;
	}
}

/* this function adds captcha to the comment form */
if ( ! function_exists ( 'cptch_comment_form_wp3' ) ) {
	function cptch_comment_form_wp3() {
		remove_action( 'comment_form', 'cptch_comment_form' );
		echo cptch_display_captcha_custom( 'wp_comments', 'cptch_wp_comments' );
		return true;
	}
}

/* this function checks captcha posted with the comment */
if ( ! function_exists ( 'cptch_comment_post' ) ) {
	function cptch_comment_post( $comment ) {
		/*
		 * added for a compatibility with WP Wall plugin
		 * this does NOT add CAPTCHA to WP Wall plugin,
		 * it just prevents the "Error: You did not enter a Captcha phrase." when submitting a WP Wall comment
		 */
		if ( function_exists( 'WPWall_Widget' ) && isset( $_REQUEST['wpwall_comment'] ) ) {
			return $comment;
		}

		/* Skip the CAPTCHA for comment replies from the admin menu */
		if (
			isset( $_REQUEST['action'] ) &&
			'replyto-comment' == $_REQUEST['action'] &&
			(
				check_ajax_referer( 'replyto-comment', '_ajax_nonce', false ) ||
				check_ajax_referer( 'replyto-comment', '_ajax_nonce-replyto-comment', false )
			)
		)
			return $comment;

		/* Skip the CAPTCHA for trackback or pingback */
		if ( '' != $comment['comment_type'] && 'comment' != $comment['comment_type'] ) {
			return $comment;
		}
		$error = cptch_check_custom_form( true, 'string', 'wp_comments' );

		if ( is_string( $error ) ) {
			wp_die( $error . '<br />' . __( 'Click the BACK button on your browser, and try again.', 'ineosq-captcha-pro' ) );
		}
		return $comment;
	}
}

/************** INEOSQ CONTACT FORM HOOK ********************/
if ( ! function_exists ( 'cptch_cf_form' ) ) {
	function cptch_cf_form( $content = "", $form_slug = 'general' ) {
		return
			( is_string( $content ) ? $content : '' ) .
			cptch_display_captcha_custom( $form_slug );
	}
}

/************** INEOSQ SUBSCRIBER HOOK ********************/
if ( ! function_exists ( 'cptch_custom_form' ) ) {
	function cptch_custom_form( $content = "", $form_slug = 'general' ) {
		return
			( is_string( $content ) ? $content : '' ) .
			cptch_display_captcha_custom( $form_slug );
	}
}

/**
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_check_ineosq_contact_form' ) ) {
	function cptch_check_ineosq_contact_form( $allow ) {
		if ( true !== $allow ) {
			return $allow;
		}
		return cptch_check_custom_form( true, 'wp_error', 'ineosq_contact' );

	}
}

/**
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_check_ineosq_subscriber_form' ) ) {
	function cptch_check_ineosq_subscriber_form( $allow ) {
		if ( true !== $allow ) {
			return $allow;
		}
		$allow = cptch_check_custom_form( true, 'string', 'ineosq_subscriber' );
		return $allow;
	}
}

/************** BUDDYPRESS LOGIN WIDGET HOOKS ********************/

/* add captcha to buddypress login form in widget */
if ( ! function_exists ( 'cptch_buddypress_login_widget' ) ) {
	function cptch_buddypress_login_widget() {
		global $cptch_options;
		/* captcha html - buddypress registration form */
		echo '<div class="cptch-section">';
			if ( '' != $cptch_options['title'] ) {
				echo '<label class="cptch_title">' . $cptch_options['title'] . '<span class="required"> ' . $cptch_options['required_symbol'] . '</span></label>';
			}
			echo cptch_display_captcha_custom( 'buddypress_register' );
		echo '</div>';
	}
}

/************** BUDDYPRESS REGISTER FORM HOOKS ********************/

/* add captcha to buddypress registration form */
if ( ! function_exists ( 'cptch_buddypress_registration' ) ) {
	function cptch_buddypress_registration() {
		global $bp, $cptch_options;
		/* captcha html - buddypress registration form */
		echo '<div id="profile-details-section" class="register-section cptch-section">
			<div class="editfield">';
				if ( '' != $cptch_options['title'] ) {
					echo '<span class="cptch_title">' . $cptch_options['title'] . '<span class="required"> ' . $cptch_options['required_symbol'] . '</span></span>';
				} else {
					echo '<br />';
				}
				if ( ! empty( $bp->signup->errors['cptch_buddypress_registration'] ) ) {
					echo '<div class="error">' . $bp->signup->errors['cptch_buddypress_registration'] . '</div>';
				}
				echo cptch_display_captcha_custom( 'buddypress_register' );
		echo '</div>
		</div>';
	}
}

/* add captcha to buddypress registration form - validation */
if ( ! function_exists ( 'cptch_buddypress_registration_validate' ) ) {
	function cptch_buddypress_registration_validate( $errors ) {
		global $bp;
		$result = cptch_check_custom_form( true, 'string', 'buddypress_register' );
		if ( is_string( $result ) ) {
			$bp->signup->errors['cptch_buddypress_registration'] = $result;
		}
		return ! $errors;
	}
}

/************** BUDDYPRESS COMMENT FORM HOOKS ********************/

/* add captcha to buddypress comment form */
if ( ! function_exists ( 'cptch_buddypress_comment_form' ) ) {
	function cptch_buddypress_comment_form() {
		global $cptch_options;
		/* captcha html - buddypress registration form */
		echo '<div class="ac-reply-content ac-reply-content-captcha">';
			if ( '' != $cptch_options['title'] ) {
				echo '<br /><span class="cptch_title">' . $cptch_options['title'] .'<span class="required"> ' . $cptch_options['required_symbol'] . '</span></span>';
			} else {
				echo '<br />';
			}
			echo cptch_display_captcha_custom( 'buddypress_comments' );
		echo '</div>';
	}
}

/* verify the captcha in buddypress comment form  */
if ( ! function_exists ( 'cptch_buddypress_comment_form_validate' ) ) {
	function cptch_buddypress_comment_form_validate( $activity_id ) {

		$result = cptch_check_custom_form( true, 'string', 'buddypress_comments' );
		if ( is_string( $result ) ) {
			bp_core_add_message( $result, 'error' );
			bp_core_redirect( wp_get_referer() . '#ac-form-' . $activity_id );
		}
		return $activity_id ;
	}
}
/* add captcha to buddypress add comment form - validation js */
if ( ! function_exists ( 'cptch_buddypress_comment_wp_head' ) ) {
	function cptch_buddypress_comment_wp_head() {
		global $cptch_options;

		/*get bp version */
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$bp_plugin_info = get_plugin_data( dirname( dirname( __FILE__ ) ) . "/buddypress/bp-loader.php" );
		if ( isset( $bp_plugin_info ) && $bp_plugin_info["Version"] < '2.1' ) {
			wp_enqueue_script( 'cptch_buddypress', plugins_url( 'js/buddypress_script_before_2.1.js' , __FILE__ ), array(), $cptch_options['plugin_option_version'], true );
		} else {
			wp_enqueue_script( 'cptch_buddypress', plugins_url( 'js/buddypress_script.js' , __FILE__ ), array(), $cptch_options['plugin_option_version'], true );
		}
	}
}
/* add captcha to buddypress add comment form - validation js */
if ( ! function_exists ( 'cptch_buddypress_comment_ajax' ) ) {
	function cptch_buddypress_comment_ajax() {
		global $cptch_options;

		if ( empty( $cptch_options ) ) {
			$cptch_options = is_network_admin() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
		}

		if ( ! $cptch_options['forms']['buddypress_comments']['enable'] ) {
			echo 'valid';
			die();
		}

		$result = cptch_check_custom_form( true, 'string', 'buddypress_comments' );

		echo is_string( $result ) ? "<div id=\"message\" class=\"error\"><p>{$result}</p></div>" : 'valid';
		die();
	}
}

/**
 * Handle Captcha errors via Limit Attempts Pro functions
 * @uses for Buddypress Comment Form
 */
if ( ! function_exists( 'cptch_handle_errors_ajax' ) ) {
	function cptch_handle_errors_ajax() {
		check_ajax_referer( 'cptch', 'nonce' );
		$path = ABSPATH . 'wp-content/plugins/limit-attempts-pro/includes/front-end-functions.php';
		if ( file_exists( $path ) ) {
			require_once( $path );
			$error = lmtttmpts_failed_with_captcha( 'buddypress_comments_form_captcha_check' );
			$message = empty( $error ) ? $_POST['cptch_msg'] : preg_replace( "|</p></div>|", "<br/>{$error}</p></div>", $_POST['cptch_msg'] );
			echo stripslashes( $message );
		}
		die();
	}
}

/************** BUDDYPRESS GROUP FORM HOOKS ********************/

/* add captcha to buddypress add group form  */
if ( ! function_exists ( 'cptch_buddypress_group_form' ) ) {
	function cptch_buddypress_group_form() {
		global $cptch_options;
		/* captcha html - buddypress registration form */
		echo '<div class="cptch_buddypress_group_form">';
			if ( "" != $cptch_options['title'] ) {
				echo '<span class="cptch_title">'. $cptch_options['title'] .'<span class="required"> ' . $cptch_options['required_symbol'] . '</span></span>';
			} else {
				echo '<br />';
			}
			echo cptch_display_captcha_custom( 'buddypress_group' );
		echo '</div>';
	}
}

if ( ! function_exists( 'cptch_buddypress_create_group_check' ) ) {
	function cptch_buddypress_create_group_check() {

		if ( 'group-details' != bp_get_groups_current_create_step() ) {
			return;
		}
		$result = cptch_check_custom_form( true, 'string', 'buddypress_group' );
		if ( is_string( $result ) ) {
			bp_core_add_message( $result, 'error' );
			bp_core_redirect( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/create/step/group-details/' );
		}
	}
}

/************** WwOOCOMMERCE LOGIN FORM HOOKS ********************/

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_woocommerce_login' ) ) {
	function cptch_woocommerce_login() {
		echo cptch_display_captcha_custom( 'woocommerce_login', 'cptch_woocommerce' );
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_woocommerce_login_check' ) ) {
	function cptch_woocommerce_login_check( $allow ) {
		$result = cptch_check_custom_form( true, 'string', 'woocommerce_login' );

		if ( is_string( $result ) ) {
			$allow->add( 'cptch_error', $result );
		} elseif ( is_wp_error( $result ) ) {
			$allow = $result;
		}
		return $allow;
	}
}

/************** WwOOCOMMERCE REGISTER FORM HOOKS ********************/

/**
 * Prevent the duplicate displaying of the CAPTCHA in the WooCommerce register form
 * @since  1.6.9
 * @param  void
 * @return void
 */
if ( ! function_exists( 'cptch_woocommerce_remove_register_action' ) ) {
	function cptch_woocommerce_remove_register_action() {
		remove_action( 'register_form', 'cptch_register_form' );
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_woocommerce_register' ) ) {
	function cptch_woocommerce_register() {
		echo cptch_display_captcha_custom( 'woocommerce_register', 'cptch_woocommerce' );
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_woocommerce_register_check' ) ) {
	function cptch_woocommerce_register_check( $allow ) {
		$result = cptch_check_custom_form( true, 'string', 'woocommerce_register' );

		if ( is_string( $result ) ) {
			$allow->add( 'cptch_error', $result );
		} elseif ( is_wp_error( $result ) ) {
			$allow = $result;
		}
		return $allow;
	}
}

/************** WwOOCOMMERCE LOST PASSWORD FORM HOOKS ********************/

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_woocommerce_lost_password' ) ) {
	function cptch_woocommerce_lost_password() {
		echo cptch_display_captcha_custom( 'woocommerce_lost_password', 'cptch_woocommerce' );
	}
}

/**
 * Check the CAPTCHA answer in the WooCommerce lost password form
 * @since  1.6.9
 * @param  boolean      $allow    if 'false' - the password changing is not allowed
 * @return object/true  $allow    an instance of the class WP_ERROR  or boolean
 */
if ( ! function_exists( 'cptch_woocommerce_allow_password_reset' ) ) {
	function cptch_woocommerce_allow_password_reset( $allow ) {

		/* prevent the repeated checking of the WP lost password form */
		$woocommerce = cptch_is_woocommerce_page();
		if ( ! $woocommerce ) {
			return $allow;
		}

		$result = cptch_check_custom_form( true, 'string', 'woocommerce_lost_password' );

		if ( $allow ) {
			if ( is_string( $result ) ) {
				$allow = new WP_Error();
				$allow->add( 'cptch_error', $result );
			} elseif ( is_wp_error( $result ) ) {
				$allow = $result;
			}
		}
		return $allow;
	}
}

/************** WOOCOMMERCE CHECKOUT FORM HOOKS ********************/

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_woocommerce_checkout' ) ) {
	function cptch_woocommerce_checkout() {
		echo cptch_display_captcha_custom( 'woocommerce_checkout', 'cptch_woocommerce' );
	}
}

/**
 * Check the Captcha from the WooCommerce Checkout Billings page
 * @since  1.6.9
 * @param  void
 * @return void
 */
if ( ! function_exists( 'cptch_woocommerce_checkout_process' ) ) {
	function cptch_woocommerce_checkout_process() {

		$result = cptch_check_custom_form( true, 'string', 'woocommerce_checkout' );

		if ( is_string( $result ) ) {
			wc_add_notice( $result, 'error' );
		}
	}
}

if ( ! function_exists( 'cptch_is_woocommerce_page' ) ) {
	function cptch_is_woocommerce_page() {
		$traces = debug_backtrace();

		foreach( $traces as $trace ) {
			if ( isset( $trace['file'] ) && false !== strpos( $trace['file'], 'woocommerce' ) ) {
				return true;
			}
		}
		return false;
	}
}

/**
 * Check the Captcha for bbPress forms
 */

if ( ! function_exists( 'cptch_bbpress_new_topic_display' ) ) {
	function cptch_bbpress_new_topic_display() {
		echo cptch_display_captcha_custom( 'bbpress_new_topic_form', 'cptch_bbpress_new_topic_form' );
	}
}

if ( ! function_exists( 'cptch_bbpress_new_topic_check' ) ) {
	function cptch_bbpress_new_topic_check( $allow ) {
		$result = cptch_check_custom_form( true, 'string', 'bbpress_new_topic_form' );
		if ( is_string( $result ) && function_exists( 'bbp_add_error' ) ) {
			bbp_add_error( 'cptch_error', $result );
		}
		return $allow;
	}
}

if ( ! function_exists( 'cptch_bbpress_reply_display' ) ) {
	function cptch_bbpress_reply_display() {
		echo cptch_display_captcha_custom( 'bbpress_reply_form', 'cptch_bbpress_reply_form' );
	}
}

if ( ! function_exists( 'cptch_bbpress_reply_check' ) ) {
	function cptch_bbpress_reply_check( $allow ) {
		$result = cptch_check_custom_form( true, 'string', 'bbpress_reply_form' );
		if ( is_string( $result ) && function_exists( 'bbp_add_error' ) ) {
			bbp_add_error( 'cptch_error', $result );
		}
		return $allow;
	}
}

/**
 * Check Captcha for wpForo forms
 */

if ( ! function_exists( 'cptch_wpforo_login_check' ) ) {
	function cptch_wpforo_login_check( $user ) {
		$result = cptch_check_custom_form( true, 'wp_error', 'wpforo_login_form' );
		if ( is_wp_error( $result ) ) {
			$user = new WP_Error();
			$error_message = implode( '<br />', $result->get_error_messages() );
			$user->add( 'cptch_error', $error_message );
		}
		return $user;
	}
}

if ( ! function_exists( 'cptch_wpforo_register_check' ) ) {
	function cptch_wpforo_register_check( $allow ) {
		if ( cptch_is_woocommerce_page() ) return $allow;
		$result = cptch_check_custom_form( true, 'wp_error', 'wpforo_register_form' );
		if ( is_wp_error( $result ) && empty( $allow->errors ) ) {
			$error_message = implode( '<br />', $result->get_error_messages() );
			$allow->add( 'cptch_error', $error_message );
		}
		return $allow;
	}
}

if ( ! function_exists( 'cptch_wpforo_new_topic_display' ) ) {
	function cptch_wpforo_new_topic_display() {
		echo cptch_display_captcha_custom( 'wpforo_new_topic_form', 'cptch_wpforo_new_topic_form' );
	}
}

if ( ! function_exists( 'cptch_wpfpro_topic_check' ) ) {
	function cptch_wpfpro_topic_check( $data ) {
		global $wpforo;
		$result = cptch_check_custom_form( true, 'wp_error', 'wpforo_new_topic_form' );
		if ( is_wp_error( $result ) ) {
			$error_message = implode( '<br />', $result->get_error_messages() );
			$wpforo->notice->add( $error_message, 'error' );
			return false;
		}
		return $data;
	}
}

if ( ! function_exists( 'cptch_wpforo_reply_display' ) ) {
	function cptch_wpforo_reply_display() {
		echo cptch_display_captcha_custom( 'wpforo_reply_form', 'cptch_wpforo_reply_form' );
	}
}

if ( ! function_exists( 'cptch_wpfpro_reply_check' ) ) {
	function cptch_wpfpro_reply_check( $data ) {
		global $wpforo;
		$result = cptch_check_custom_form( true, 'wp_error', 'wpforo_reply_form' );
		if ( is_wp_error( $result ) ) {
			$error_message = implode( '<br />', $result->get_error_messages() );
			$wpforo->notice->add( $error_message, 'error' );
			return false;
		}
		return $data;
	}
}

/**
 * Add Captcha to the Jetpack Contact Form
 */

if ( ! function_exists( 'cptch_jetpack_cf_display' ) ) {
	function cptch_jetpack_cf_display( $content ) {
		if ( 1 === preg_match( "~(\[contact-form([\s\S]*)?\][\s\S]*)(\[\/contact-form\])~U", $content ) ){
			return preg_replace_callback( "~(\[contact-form([\s\S]*)?\][\s\S]*)(\[\/contact-form\])~U", "cptch_jetpack_cf_callback", $content );
		} else if ( 1 === preg_match( "~(<div\sclass\=\"wp-block-jetpack-button)~U", $content ) ) {
			return preg_replace_callback( "~(<div\sclass\=\"wp-block-jetpack-button.*\"\>)~U", "cptch_jetpack_cf_callback", $content );
		}
	}
}

if ( ! function_exists( 'cptch_jetpack_cf_callback' ) ) {
	function cptch_jetpack_cf_callback( $matches ) {
		if ( ! preg_match( "~\[ineosq_captcha\]~", $matches[0] ) ) {
			if ( 2 < count( $matches ) ) {
				return $matches[1] . "[ineosq_captcha]" . $matches[3];
			} else {
				return $matches[0] . "[ineosq_captcha]" . $matches[1];
			}
		}
		return $matches[0];
	}
}

if ( ! function_exists( 'cptch_jetpack_cf_check' ) ) {
	function cptch_jetpack_cf_check( $is_spam = false ) {
		global $cptch_result;
		$cptch_result = cptch_check_custom_form( true, 'wp_error', 'jetpack_contact_form' );
		if ( is_wp_error( $cptch_result ) ) {
			$is_spam = new WP_Error();
			$errors = implode( "<br>", $cptch_result->get_error_messages() );
			$is_spam->add( 'cptch_error', $errors );
			add_filter( 'cptch_captcha_content', 'cptch_error_message', 10, 1 );
		}
		return $is_spam;
	}
}

/**
 * Add Captcha to Mailchimp for WordPress
 */

if ( ! function_exists( 'cptch_mailchimp_display' ) ) {
	function cptch_mailchimp_display( $content = '', $form = '', $element = '' ) {
		$content = str_replace( '<input type="submit"', cptch_display_captcha_custom( 'mailchimp', 'cptch_mailchimp' ) . '<input type="submit"', $content );

		return $content;
	}
}

if ( ! function_exists( 'cptch_mailchimp_check' ) ) {
	function cptch_mailchimp_check( $errors ) {
		global $cptch_result;
		$cptch_result = cptch_check_custom_form( true, 'wp_error', 'mailchimp' );
		if ( is_wp_error( $cptch_result ) ) {
			return 'cptch_error';
		}
		return $errors;
	}
}

if ( ! function_exists( 'cptch_mailchimp_check_message' ) ) {
	function cptch_mailchimp_check_message( $message ) {
		global $cptch_result;

		$cptch_result = cptch_check_custom_form( true, 'wp_error', 'mailchimp' );

		if ( is_wp_error( $cptch_result ) ) {
			$cptch_result = implode( '<br />', $cptch_result->get_error_messages() );
			$message['cptch_error'] = $cptch_result;
		}
		return $message;
	}
}

/**
 * Add Captcha error message to various forms
 */
if ( ! function_exists( 'cptch_error_message' ) ) {
	function cptch_error_message( $captcha_content = '' ) {
		global $cptch_result;
		if ( is_wp_error( $cptch_result ) ) {
			$captcha_content = sprintf( '<p>%s</p>', implode( '<br>', $cptch_result->get_error_messages() ) ) . $captcha_content;
		}
		return $captcha_content;
	}
}

/************** DISPLAY CAPTCHA VIA SHORTCODE ********************/

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_display_captcha_shortcode' ) ) {
	function cptch_display_captcha_shortcode( $args ) {
		global $cptch_options;

		if ( ! is_array( $args ) || empty( $args ) ) {
			return cptch_display_captcha_custom( 'general', 'cptch_shortcode' );
		}

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}

		$form_slug	= empty( $args["form_slug"] ) ? 'general' : $args["form_slug"];
		$form_slug	= esc_attr( $form_slug );
		$form_slug	= empty( $form_slug ) || ! array_key_exists( $form_slug, $cptch_options['forms'] ) ? 'general' : $form_slug;
		$class_name	= empty( $args["class_name"] ) ? 'cptch_shortcode' : esc_attr( $args["class_name"] );

		return
				'general' == $form_slug ||
				$cptch_options['forms'][ $form_slug ]['enable']
			?
				cptch_display_captcha_custom( $form_slug, $class_name )
			:
				'';
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_shortcode_button_content' ) ) {
	function cptch_shortcode_button_content( $content ) { ?>
		<div id="cptch" style="display:none;">
			<input class="ineosq_default_shortcode" type="hidden" name="default" value="[ineosq_captcha]" />
		</div>
	<?php }
}

/************** DISPLAY CAPTCHA VIA FILTER HOOK ********************/
/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_display_filter' ) ) {
	function cptch_display_filter( $content = '', $form_slug = 'general', $class_name = "" ) {
		$args = array(
			'form_slug'		=> $form_slug,
			'class_name'	=> $class_name
		);
		if ( 'general' == $form_slug || cptch_captcha_is_needed( $form_slug, is_user_logged_in() ) ) {
			return $content . cptch_display_captcha_shortcode( $args );
		}
		return $content;
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_verify_filter' ) ) {
	function cptch_verify_filter( $allow = true, $return_format = 'string', $form_slug = 'general' ) {

		if ( true !== $allow ) {
			return $allow;
		}

		if ( ! in_array( $return_format, array( 'string', 'wp_error' ) ) ) {
			$return_format = 'string';
		}

		if ( 'general' == $form_slug || cptch_captcha_is_needed( $form_slug, is_user_logged_in() ) ) {

			$allow = cptch_check_custom_form( true, $return_format, 'subscriber_captcha_check' );
			return $allow;
		}

		return $allow;
	}
}

if ( ! function_exists ( 'cptch_display_captcha_custom' ) ) {
	function cptch_display_captcha_custom( $form_slug = 'general', $class_name = "", $input_name = 'cptch_number' ) {
		global $cptch_options, $cptch_ip_in_allowlist;

		if ( empty( $cptch_ip_in_allowlist ) ) {
			$cptch_ip_in_allowlist = cptch_allowlisted_ip();
		}

		if ( empty( $class_name ) ) {
			$label = $tag_open = $tag_close = '';
		} else {
			$label		=
					empty( $cptch_options['title'] )
				?
					''
				:
					'<span class="cptch_title cptch_to_remove">' . $cptch_options['title'] .'<span class="required"> ' . $cptch_options['required_symbol'] . '</span></span>';
			$tag_open	= '<p class="cptch_block">';
			$tag_close	= '</p>';
		}

		if ( $cptch_ip_in_allowlist ) {
			$content = '<label class="cptch_allowlist_message">' . $cptch_options['allowlist_message'] . '</label>';
		} else {
			if ( 'invisible' == $cptch_options['type'] && (  ( defined( 'DOING_AJAX' ) && DOING_AJAX )  || 'ninja_form' == $form_slug || 'gravity_forms' == $form_slug ) ) {

				require_once( dirname( __FILE__ ) . '/includes/invisible.php' );
				$captcha = new Cptch_Invisible();

				if ( ! $captcha->is_errors() ) {
					$object_content = $captcha->get_content();
					$content = '<span class="cptch_wrap cptch_ajax_wrap"
						data-cptch-form="' . $form_slug . '"
						data-cptch-class="' . $class_name . '">'
							. $object_content .
						'</span>';
				}
			} else {
				$content = cptch_display_captcha( $form_slug, $class_name, $input_name );
			}
		}

		$content = apply_filters( 'cptch_captcha_content', $content );
		return $tag_open . $label . $content . $tag_close;
	}
}

/**
 * Checks the answer for the CAPTCHA
 * @param  mixed   $allow          The result of the pevious checking
 * @param  string  $return_format  The type of the cheking result. Can be set as 'string' or 'wp_error'
 * @param  string  $form_slug
 * @return mixed                   boolean(true) - in case when the CAPTCHA answer is right, or user`s IP is in the allowlist,
 *                                 string or WP_Error object ( depending on the $return_format variable ) - in case when the CAPTCHA answer is wrong
 */
if ( ! function_exists( 'cptch_check_custom_form' ) ) {
	function cptch_check_custom_form( $allow = true, $return_format = 'string', $form_slug = 'general' ) {
		global $cptch_options, $cptch_ip_in_allowlist;

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}

		$form_slugs = array(
			'wp_register'				=> 'registration_form_captcha_check',
			'wp_lost_password'			=> 'reset_password_form_captcha_check',
			'wp_comments'				=> 'comments_form_captcha_check',
			'ineosq_contact'				=> 'contact_form_captcha_check',
			'ineosq_booking'               => 'booking_form_captcha_check',
			'ineosq_subscriber'			=> 'subscriber_captcha_check',
			'buddypress_register'		=> 'buddypress_registration_form_captcha_check',
			'buddypress_comments'		=> 'buddypress_comments_captcha_check',
			'buddypress_group'			=> 'buddypress_create_group_form_captcha_check',
			'contact_form_7'			=> 'contact_form_7_captcha_check',
			'woocommerce_login'			=> 'woocommerce_login_form_captcha_check',
			'woocommerce_register'		=> 'woocommerce_register_form_captcha_check',
			'woocommerce_lost_password'	=> 'woocommerce_lost_password_form_captcha_check',
			'woocommerce_checkout'		=> 'woocommerce_checkout_billing_form_captcha_check',
			'jetpack_contact_form'		=> 'jetpack_contact_form_captcha_check',
			'bbpress_new_topic_form'	=> 'bbpress_new_topic_form_captcha_check',
			'bbpress_reply_form'		=> 'bbpress_reply_form_captcha_check',
			'wpforo_login_form'			=> 'wpforo_login_form_captcha_check',
			'wpforo_register_form'		=> 'wpforo_register_form_captcha_check',
			'wpforo_new_topic_form'		=> 'wpforo_new_topic_form_captcha_check',
			'wpforo_reply_form'			=> 'wpforo_reply_form_captcha_check',
			'mailchimp'					=> 'mailchimp_form_captcha_check',
			'ninja_form'				=> 'ninja_form_captcha_check',
			'gravity_forms'				=> 'gravity_forms_captcha_check',
			'elementor_contact_form'	=> 'elementor_contact_form_check',
      'wpforms'				    => 'wforms_captcha_check'
		);

		if ( array_key_exists( $form_slug, $form_slugs ) ) {
			$la_form_slug = $form_slugs[ $form_slug ];
		} else {
			$la_form_slug = 'login_form';
		}

		/*
		 * Whether the user's IP is in the allowlist
		 */
		if ( is_null( $cptch_ip_in_allowlist ) ) {
			$cptch_ip_in_allowlist = cptch_allowlisted_ip();
		}

		if ( $cptch_ip_in_allowlist ) {
			return $allow;
		}

		$lmtttmpts_error = ( ! has_filter( 'lmtttmpts_check_ip' ) ) ? '' : apply_filters( 'lmtttmpts_wrong_captcha', $la_form_slug, '' );

		if ( 'invisible' == $cptch_options['type'] ) {
			require_once( dirname( __FILE__ ) . '/includes/invisible.php' );
			$captcha = new Cptch_Invisible();
			$captcha->check();

			if ( $captcha->is_errors() ) {
				$error = $captcha->get_errors();
				if ( 'string' == $return_format ) {
					$error_message = $error->get_error_message();
					if( ! empty( $lmtttmpts_error ) )
					$error_message .= "\n" . $lmtttmpts_error;
					return $error_message;
				} else {
					if ( ! is_wp_error( $error ) ) {
						$error = new WP_Error();
					}
					if ( ! empty( $lmtttmpts_error ) && $la_form_slug != $lmtttmpts_error ) {
						if ( is_wp_error( $error ) ) {
							$error->add( 'cptch_error_lmtttmpts', $lmtttmpts_error );
						} else {
							$error .= '<br />' . $lmtttmpts_error;
						}
					}
					return $error;
				}
			} else {
				return $allow;
			}
		} else {
			/*
			 * Escaping the form slug before using it in case when the form slug
			 * was not sended via function parameters or if we use the CAPTCHA in custom forms
			 */
			if ( empty( $form_slug ) ) {
				$form_slug = isset( $_REQUEST['cptch_form'] ) ? esc_attr( $_REQUEST['cptch_form'] ) : 'general';
			} else {
				$form_slug = esc_attr( $form_slug );
			}

			$form_slug = empty( $form_slug ) || ! array_key_exists( $form_slug, $cptch_options['forms'] ) ? 'general' : $form_slug;

			$error_code = '';

			if ( 'slide' == $cptch_options['type'] ) {
				/* Not enough data to verify the CAPTCHA answer */
				if ( empty( $_REQUEST['cptch_result'] ) ) {
					$error_code = 'no_answer';
					/* The CAPTCHA answer is wrong */
				} elseif ( ! cptch_check_slide_captcha_response( $_REQUEST['cptch_result'] ) ) {
					$error_code = 'wrong_answer';
				}
       } else {
				/* The time limit is exhausted */
				if ( cptch_limit_exhausted( $form_slug ) ) {
					$error_code = 'time_limit_off';
					/* Not enough data to verify the CAPTCHA answer */
				} elseif (
					empty( $_REQUEST['cptch_result'] ) ||
					! isset( $_REQUEST['cptch_number'] ) ||
					! isset( $_REQUEST['cptch_time'] ) ||
					empty( $_REQUEST['cptch_number'] )
				) {
					$error_code = 'no_answer';
					/* The CAPTCHA answer is wrong */
				} elseif ( 0 !== strcasecmp( trim( cptch_decode( $_REQUEST['cptch_result'], $cptch_options['str_key']['key'], $_REQUEST['cptch_time'] ) ), $_REQUEST['cptch_number'] ) ) {
					$error_code = 'wrong_answer';
				}
      }

			/* The CAPTCHA answer is right */
			if ( empty( $error_code ) ) {
				if ( ! has_filter( 'lmtttmpts_check_ip' ) ) {
					return $allow;
				} else {
					/* check for blocked IP in the Limit Attempts plugin lists */
					$errors = apply_filters( 'lmtttmpts_check_ip', true );
					/* if IP is blocked */
					if ( is_wp_error( $errors ) ) {
						$error_codes = $errors->get_error_codes();
						if ( is_array( $error_codes ) && ! empty( $error_codes ) ) {
							$allow = array();
							foreach ( $error_codes as $error_code )
								$allow[] = $errors->get_error_message( $error_code );
							$allow = implode( '<br />', $allow );
						}
					} else {
						do_action( 'lmtttmpts_success_captcha', $la_form_slug, cptch_get_ip() );
					}
				}
				return $allow;
			}

			/* Fetch the error message */
			if ( 'string' == $return_format ) {
				$allow = $cptch_options[ $error_code ];
				if( ! empty( $lmtttmpts_error ) )
				$allow .= "\n" . $lmtttmpts_error;
			} else {
				if ( ! is_wp_error( $allow ) ) {
					$allow = new WP_Error();
				}
				$allow->add( 'cptch_error', $cptch_options[ $error_code ] );
				if ( ! empty( $lmtttmpts_error ) && $la_form_slug != $lmtttmpts_error ) {
					if ( is_wp_error( $allow ) ) {
						$allow->add( 'cptch_error_lmtttmpts', $lmtttmpts_error );
					} else {
						$allow .= '<br />' . $lmtttmpts_error;
					}
				}
			}
		}
		return $allow;
	}
}

if ( ! function_exists( 'cptch_is_captcha_required' ) ) {
	function cptch_is_captcha_required( $form_slug = '', $is_user_logged_in = null ) {
		global $cptch_options;

		if ( is_null( $is_user_logged_in ) ) {
			$is_user_logged_in = is_user_logged_in();
		}

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}

		if ( $is_user_logged_in && isset( $cptch_options['forms'][$form_slug]['hide_from_registered'] ) &&
        $cptch_options['forms'][$form_slug]['hide_from_registered'] == 1 && ! is_admin() ) {
			return false;
        }
		
		return isset( $cptch_options['forms'][$form_slug]['enable'] ) && true == $cptch_options['forms'][$form_slug]['enable'];
	}
}

if ( ! function_exists( 'cptch_display_captcha' ) ) {
	function cptch_display_captcha( $form_slug = 'general', $class_name = "", $input_name = 'cptch_number' ) {
		global $cptch_options, $cptch_id_postfix;

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}

		/**
		 * Escaping the function parameters
		 * @since 4.2.3
		 */
		$form_slug	= esc_attr( $form_slug );
		$form_slug	= empty( $form_slug ) ? 'general' : $form_slug;
		$class_name	= esc_attr( $class_name );
		$input_name	= esc_attr( $input_name );
		$input_name	= empty( $input_name ) ? 'cptch_number' : $input_name;

		/**
		 * In case when the CAPTCHA uses in the custom form
		 * and there is no saved settings for this form
		 * making an attempt to get default settings
		 * @since 4.2.3
		 */
		if ( ! array_key_exists( $form_slug, $cptch_options['forms'] ) ) {
			if ( ! function_exists( 'cptch_get_default_options' ) ) {
				require_once( dirname( __FILE__ ) . '/includes/helpers.php' );
			}
			$default_options = cptch_get_default_options();
			/* prevent the need to get default settings on the next displaying of the CAPTCHA */
			if ( array_key_exists( $form_slug, $default_options['forms'] ) ) {
				$cptch_options['forms'][ $form_slug ] = $default_options['forms'][ $form_slug ];
				update_option( 'cptch_options', $cptch_options );
			} else {
				$form_slug = 'general';
			}
		}

		if ( ! $cptch_options['forms'][ $form_slug ]['enable'] || $cptch_options['forms'][ $form_slug ]['use_general'] ) {
			$form_slug = 'general';
		}
		/**
		 * Display only the CAPTCHA container to replace it with the CAPTCHA
		 * after the whole page loading via AJAX
		 * @since 4.2.3
		 */
		if ( ( $cptch_options['load_via_ajax'] && ! defined( 'CPTCH_RELOAD_AJAX' ) ) ||
            'invisible' == $cptch_options['type']
        ) {
			return cptch_add_scripts() .
				'<span 
				class="cptch_wrap cptch_ajax_wrap"
				data-cptch-form="' . $form_slug . '"
				data-cptch-input="' . $input_name . '"
				data-cptch-class="' . $class_name . '">' .
                __( 'Captcha loading...', 'ineosq-captcha-pro' ) .
				'<noscript>' .
				__( 'In order to pass the CAPTCHA please enable JavaScript.', 'ineosq-captcha-pro' ) .
				'</noscript>
				</span>';
		}

		if ( empty( $cptch_options['str_key']['key'] ) || $cptch_options['str_key']['time'] < time() - ( 24 * 60 * 60 ) ) {
			cptch_generate_key();
		}
		$str_key = $cptch_options['str_key']['key'];
		$cptch_id_postfix = rand( 0, 100 );
		$hidden_result_name = 'cptch_number' == $input_name ? 'cptch_result' : $input_name . '-cptch_result';
		$time = time();
		$time_limit_notice = cptch_add_time_limit_notice( $form_slug );

		if ( 'recognition' == $cptch_options['type'] ) {
			$string = '';
			$captcha_content = '<span class="cptch_images_wrap">';
			$count = $cptch_options['images_count'];
			$pack_ids =
					empty( $cptch_options['forms'][ $form_slug ]['used_packages'] )
							?
							$cptch_options['forms']['general']['used_packages']
							:
							$cptch_options['forms'][ $form_slug ]['used_packages'];
      $array_key = mt_rand( 0, abs( count( $pack_ids ) - 1 ) );
			while ( $count != 0 ) {
				/*
				 * get element
				 */
				$image = rand( 1, 9 );

               	if ( $cptch_options['use_several_packages'] ) {
					$operand = cptch_get_image( $image, '', $pack_ids, false );
				} else {
					$operand =
						empty( $cptch_options['forms'][ $form_slug ]['used_packages'][$array_key] )
						?
						cptch_generate_value( $image, true )
						:
						cptch_get_image( $image, '', $cptch_options['forms'][ $form_slug ]['used_packages'][ $array_key ], false) ;
				}

				$captcha_content .= '<span class="cptch_span">' . $operand . '</span>';
				$string .= $image;
				$count--;
			}
            $captcha_content .= '</span>
				<input id="cptch_input_' . $cptch_id_postfix . '" class="cptch_input ' . $class_name . '" type="text" autocomplete="off" name="' . $input_name . '" value="" maxlength="' . $cptch_options['images_count'] . '" size="' . $cptch_options['images_count'] . '" aria-required="true" required="required" style="margin-bottom:0;font-size: 12px;max-width:100%;max-width:' . $cptch_options['images_count'] * 40 . 'px;" />
				<input type="hidden" name="' . $hidden_result_name . '" value="' . cptch_encode( $string, $str_key, $time ) . '" />';
		} else if ( 'slide' == $cptch_options['type'] ) {
			$captcha_content = '<div id="cptch_slide_captcha_container"></div>';
			$time_limit_notice = '';
        } else {
			/*
			 * array of math actions
			 */
			$math_actions = array();
			if ( in_array( 'plus', $cptch_options['math_actions'] ) ) {
				$math_actions[] = '&#43;';
			}
			if ( in_array( 'minus', $cptch_options['math_actions'] ) ) {
				$math_actions[] = '&minus;';
			}
			if ( in_array( 'multiplications', $cptch_options['math_actions'] ) ) {
				$math_actions[] = '&times;';
			}
			/* get current math action */
			$rand_math_action = rand( 0, count( $math_actions ) - 1 );

			/*
			 * get elements of mathematical expression
			 */
			$array_math_expression = array();
			$array_math_expression[0] = rand( 1, 9 ); /* first part */
			$array_math_expression[1] = rand( 1, 9 ); /* second part */
			/* Calculation of the result */
			switch ( $math_actions[ $rand_math_action ] ) {
				case "&#43;":
					$array_math_expression[2] = $array_math_expression[0] + $array_math_expression[1];
					break;
				case "&minus;":
					/* Result must not be equal to the negative number */
					if ( $array_math_expression[0] < $array_math_expression[1] ) {
						$number = $array_math_expression[0];
						$array_math_expression[0] = $array_math_expression[1];
						$array_math_expression[1] = $number;
					}
					$array_math_expression[2] = $array_math_expression[0] - $array_math_expression[1];
					break;
				case "&times;":
					$array_math_expression[2] = $array_math_expression[0] * $array_math_expression[1];
					break;
			}

			/*
			 * array of allowed formats
			 */
			$allowed_formats = array();
			$use_words = $use_numbeers = false;
			if ( in_array( 'numbers', $cptch_options["operand_format"] ) ) {
				$allowed_formats[] = 'number';
				$use_words = true;
			}
			if ( in_array( 'words', $cptch_options["operand_format"] ) ) {
				$allowed_formats[] = 'word';
				$use_numbeers = true;
			}
			if ( in_array( 'images', $cptch_options["operand_format"] ) )
				$allowed_formats[] = 'image';
			$use_only_words = ( $use_words && !$use_numbeers ) || !$use_words;
			/* number of field, which will be as <input type="number"> */
			$rand_input = rand( 0, 2 );

			/*
			 * get current format for each operand
			 * for example array( 'word', 'input', 'number' )
			 */
			$operand_formats = array();
			$max_rand_value = count( $allowed_formats ) - 1;
			for ( $i = 0; $i < 3; $i++ )
				$operand_formats[] = $rand_input == $i ? 'input' : $allowed_formats[mt_rand( 0, $max_rand_value )];

			/*
			 * get value of each operand
			 */
			$operand = array();
			foreach ( $operand_formats as $key => $format ) {
				switch ( $format ) {
					case 'input':
						$operand[] = '<input id="cptch_input_' . $cptch_id_postfix . '" class="cptch_input ' . $class_name . '" type="text" autocomplete="off" name="' . $input_name . '" value="" maxlength="2" size="2" aria-required="true" required="required" style="margin-bottom:0;display:inline;font-size: 12px;width: 40px;padding:5px 10px;" />';
						break;
					case 'word':
						$operand[] = cptch_generate_value( $array_math_expression[ $key ] );
						break;
					case 'image':
						$pack_ids =
							empty( $cptch_options['forms'][ $form_slug ]['used_packages'] )
							?
							$cptch_options['forms']['general']['used_packages']
							:
							$cptch_options['forms'][ $form_slug ]['used_packages'];

						if ( $cptch_options['use_several_packages'] ) {
							$operand[] = cptch_get_image( $array_math_expression[ $key ], $key, $pack_ids, $use_only_words );
						} else {
							define( 'ARRAY_KEY', mt_rand( 0, abs( count( $pack_ids ) - 1 ) ) );
							$operand[] =
								empty( $cptch_options['forms'][ $form_slug ]['used_packages'][ ARRAY_KEY ] )
								?
								cptch_generate_value( $array_math_expression[ $key ], $use_only_words )
								:
								cptch_get_image( $array_math_expression[ $key ], $key, $cptch_options['forms'][ $form_slug ]['used_packages'][ ARRAY_KEY ], $use_only_words );
						}
						break;
					case 'number':
					default:
						$operand[] = $array_math_expression[ $key ];
						break;
				}
			}
			$captcha_content = '<span class="cptch_span">' . $operand[0] . '</span>
					<span class="cptch_span">&nbsp;' . $math_actions[$rand_math_action] . '&nbsp;</span>
					<span class="cptch_span">' . $operand[1] . '</span>
					<span class="cptch_span">&nbsp;=&nbsp;</span>
					<span class="cptch_span">' . $operand[2] . '</span>
					<input type="hidden" name="' . $hidden_result_name . '" value="' . cptch_encode($array_math_expression[$rand_input], $str_key, $time) . '" />';
		}

		$wp_theme_now = wp_get_theme();
		if ( 'Twenty Fifteen' == $wp_theme_now->get( 'Name' ) ){
			$cptch_style_reload = '<style>span.cptch_reload_button { width: 3rem; height: 3rem; font-size: 3rem;}</style>';
		} else {
			$cptch_style_reload =  '';
		}

		$cptch_reload_button = ( $cptch_options['type'] !== 'slide' ) ? cptch_add_reload_button( !!$cptch_options['display_reload_button'] ) : "" ;

		return
			$time_limit_notice .
			$cptch_style_reload .
			cptch_add_scripts() .
			'<span class="cptch_wrap cptch_' . $cptch_options['type'] . '">
				<label class="cptch_label" for="cptch_input_' . $cptch_id_postfix . '">' .
			$captcha_content . '<input type="hidden" name="cptch_time" value="' . $time . '" /><input type="hidden" name="cptch_form" value="' . $form_slug . '" />
				</label>' .
			$cptch_reload_button .
			'</span>';
	}
}
/**
 * Add necessary js scripts
 * @uses     for including necessary scripts on the pages witn the CAPTCHA only
 * @since    1.6.9
 * @param    void
 * @return   string   empty string - if the form has been loaded by PHP or the CAPTCHA has been reloaded, inline javascript - if the form has been loaded by AJAX
 */
if ( ! function_exists( 'cptch_add_scripts' ) ) {
	function cptch_add_scripts() {
		global $cptch_options;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		    if ( ! defined( 'CPTCH_RELOAD_AJAX' ) ) {
			    /* This script will be included if the from was loaded via AJAX only but not during the CAPTCHA reloading */
		        $script = '<script class="cptch_to_remove" type="text/javascript">
						( function( d, tag, id ) {
							var script = d.getElementById( id );
							if ( script )
								return;
							add_script( "", "", id );

							if ( typeof( cptch_vars ) == "undefined" ) {
								var local = {
									nonce:     "' . wp_create_nonce( 'cptch', 'cptch_nonce' ) . '",
									ajaxurl:   "' . admin_url( 'admin-ajax.php' ) . '",
									enlarge:   "' . $cptch_options['enlarge_images'] . '",
									is_la_pro: "' . function_exists( 'lmtttmptspr_handle_error' ) . '"
								};
								add_script( "", "/* <![CDATA[ */var cptch_vars=" + JSON.stringify( local ) + "/* ]]> */" );
							}

							d.addEventListener( "DOMContentLoaded", function() {
								var scripts         = d.getElementsByTagName( tag ),
									captcha_script  = /' . addcslashes( plugins_url( 'js/front_end_script.js', __FILE__ ), '/' ) . '/,
									include_captcha = true;
								if ( scripts ) {
									for ( var i = 0; i < scripts.length; i++ ) {
										if ( scripts[ i ].src.match( captcha_script ) ) {
											include_captcha = false;
											break;
										}
									}
								}
								if ( typeof jQuery == "undefined" ) {
									var siteurl = "' . get_option( 'siteurl' ) . '";
									add_script( siteurl + "/wp-includes/js/jquery/jquery.js" );
									add_script( siteurl + "/wp-includes/js/jquery/jquery-migrate.min.js" );
								}
								if ( include_captcha )
									add_script( "' . plugins_url( 'js/front_end_script.js', __FILE__ ) . '" );
							} );

							function add_script( url, content, js_id ) {
								url     = url     || "";
								content = content || "";
								js_id   = js_id   || "";
								var script = d.createElement( tag );
								if ( url )
									script.src = url;
								if ( content )
									script.appendChild( d.createTextNode( content ) );
								if ( js_id )
									script.id = js_id;
								script.setAttribute( "type", "text/javascript" );
								d.body.appendChild( script );
							}
						} )( document, "script", "cptch_script_loader" );
					</script>';

		        return $script;
		    }
		} elseif ( ! wp_script_is( 'cptch_front_end_script', 'registered' ) ) {
			wp_register_script( 'cptch_front_end_script', plugins_url( 'js/front_end_script.js', __FILE__ ), array( 'jquery' ), array(), $cptch_options['plugin_option_version'] );
			add_action( 'wp_footer', 'cptch_front_end_scripts' );
			if ( $cptch_options['forms']['wp_login']['enable'] ||
				$cptch_options['forms']['wp_register']['enable'] ||
				$cptch_options['forms']['wp_lost_password']['enable'] )
				add_action( 'login_footer', 'cptch_front_end_scripts' );
		}
		return '';
	}
}

/**
 * Adds a notice about the time expiration
 * @since     1.6.9
 * @param     string     $form_slug     the slug of the form in which is CAPTCHA
 * @return    string                    the message about the exhaustion of time limit and inline script for the displaying of this message
 */
if ( ! function_exists( 'cptch_add_time_limit_notice' ) ) {
	function cptch_add_time_limit_notice( $form_slug ) {
		global $cptch_options, $cptch_id_postfix;

		if ( ! $cptch_options['forms'][ $form_slug ]['enable_time_limit'] ) {
			return '';
		}

		$id = "cptch_time_limit_notice_{$cptch_id_postfix}";

		$script = '( function( timeout ) {
            setTimeout(
                function() {
                    var notice = document.getElementById( "' . $id . '" );
                    if ( notice )
                        notice.style.display = "block";
                },
                timeout
            );
        } )( ' . $cptch_options['forms'][ $form_slug ]['time_limit'] . '000 );';

		wp_register_script( 'cptch_time_limit_notice_script_' . $cptch_id_postfix, '//' );
		wp_enqueue_script( 'cptch_time_limit_notice_script_' . $cptch_id_postfix );
		wp_add_inline_script( 'cptch_time_limit_notice_script_' . $cptch_id_postfix, sprintf( $script ) );

		$html = '<span id="' . $id . '" class="cptch_time_limit_notice cptch_to_remove">' . $cptch_options['time_limit_off_notice'] . '</span>';

		return $html;
	}
}

/**
 * Add a reload button to the CAPTCHA block
 * @since     1.6.9
 * @param     boolean     $add_button  if 'true' - the button will be added
 * @return    string                   the button`s HTML-content
 */
if ( ! function_exists( 'cptch_add_reload_button' ) ) {
	function cptch_add_reload_button( $add_button ) {

		return
			$add_button
			?
			'<span class="cptch_reload_button_wrap hide-if-no-js">
					<noscript>
						<style type="text/css">
							.hide-if-no-js {
								display: none !important;
							}
						</style>
					</noscript>
					
					<span class="cptch_reload_button dashicons dashicons-update"></span>
					
				</span>'
			:
			'';
	}
}

/**
 * Display image in CAPTCHA
 * @param    int     $value       value of element of mathematical expression
 * @param    int     $place       which is an element in the mathematical expression
 * @param    array   $package_id  what package to use in current CAPTCHA ( if it is '-1' then all )
 * @return   string               html-structure of the element
 */
if ( ! function_exists( 'cptch_get_image' ) ) {
	function cptch_get_image( $value, $place, $package_id, $use_only_words ) {
		global $wpdb, $cptch_options;

		$result = array();
		$where = ' IN ( ' . implode( ',', (array)$package_id) . ' )';
		$images = $wpdb->get_results( $wpdb->prepare(
			"SELECT
				`{$wpdb->base_prefix}cptch_images`.`name`,
				`{$wpdb->base_prefix}cptch_packages`.`folder`,
				`{$wpdb->base_prefix}cptch_packages`.`settings`
			FROM
				`{$wpdb->base_prefix}cptch_images`
			LEFT JOIN
				`{$wpdb->base_prefix}cptch_packages`
			ON
				`{$wpdb->base_prefix}cptch_packages`.`id`=`{$wpdb->base_prefix}cptch_images`.`package_id`
			WHERE
				`{$wpdb->base_prefix}cptch_images`.`package_id` IN ( %s )
				AND
				`{$wpdb->base_prefix}cptch_images`.`number`= %d;", implode( ',', ( array )$package_id ), $value ),
			ARRAY_N
		);

		if ( empty( $images ) ) {
			return cptch_generate_value( $value, $use_only_words );
		}

		if ( is_multisite() ) {
			switch_to_blog( 1 );
			$upload_dir = wp_upload_dir();
			restore_current_blog();
		} else {
			$upload_dir = wp_upload_dir();
		}
		$current_image = $images[mt_rand( 0, count( $images ) - 1 )];
		$src = $upload_dir['basedir'] . '/ineosq_captcha_images/' . $current_image[1] . '/' . $current_image[0];
		if ( file_exists( $src ) ) {
			if ( 1 == $cptch_options['enlarge_images'] ) {
				switch ( $place ) {
					case 0:
						$class = 'cptch_left';
						break;
					case 1:
						$class = 'cptch_center';
						break;
					case 2:
						$class = 'cptch_right';
						break;
					default:
						$class = '';
						break;
				}
			} else {
				$class = '';
			}
			$styles = '';
			if ( !empty( $current_image[2] ) ) {
				$settings = unserialize( $current_image[2] );
				if ( is_array( $settings ) ) {
					$styles = ' style="';
					foreach ( $settings as $propery => $value )
						$styles .= "{$propery}: {$value};";
					$styles .= '"';
				}
			}
			$image_data = getimagesize( $src );
			if ( is_array( $image_data ) && ! empty( $image_data['mime'] ) ) {
				return '<span class="cptch_img ' . $class . '"><img src="data: ' . $image_data['mime'] . ';base64,' . base64_encode( file_get_contents( $src ) ) . '"' . $styles . ' alt="image"/></span>';
			} elseif ( cptch_is_svg( $src ) ) {
				return '<span class="cptch_img ' . $class . '"><img src="data: image/svg+xml;base64,' . base64_encode( file_get_contents( $src ) ) . '"' . $styles . ' alt="image"/></span>';
			} else {
				return cptch_generate_value( $value, $use_only_words );
			}
		} else {
			return cptch_generate_value( $value, $use_only_words );
		}
	}
}
/**
 * Check for file type
 * @since    1.6.9
 * @param    string     $src    the absolute path to the file
 * @return   boolean            'true' - if the file type is SVG
 */
if ( !function_exists( 'cptch_is_svg' ) ) {
	function cptch_is_svg( $src ) {
		$data = wp_check_filetype( $src, array( 'svg' => 'font/svg' ) );
		return !!$data['ext'];
	}
}

if ( ! function_exists( 'cptch_generate_value' ) ) {
	function cptch_generate_value( $value, $use_only_words = true ) {
		$random = $use_only_words ? 1 : mt_rand( 0, 1 );
		if ( 1 == $random ) {
			$number_string = array(
				0 => __( 'zero', 'ineosq-captcha-pro' ),
				1 => __( 'one', 'ineosq-captcha-pro' ),
				2 => __( 'two', 'ineosq-captcha-pro' ),
				3 => __( 'three', 'ineosq-captcha-pro' ),
				4 => __( 'four', 'ineosq-captcha-pro' ),
				5 => __( 'five', 'ineosq-captcha-pro' ),
				6 => __( 'six', 'ineosq-captcha-pro' ),
				7 => __( 'seven', 'ineosq-captcha-pro' ),
				8 => __( 'eight', 'ineosq-captcha-pro' ),
				9 => __( 'nine', 'ineosq-captcha-pro' ),

				10 => __( 'ten', 'ineosq-captcha-pro' ),
				11 => __( 'eleven', 'ineosq-captcha-pro' ),
				12 => __( 'twelve', 'ineosq-captcha-pro' ),
				13 => __( 'thirteen', 'ineosq-captcha-pro' ),
				14 => __( 'fourteen', 'ineosq-captcha-pro' ),
				15 => __( 'fifteen', 'ineosq-captcha-pro' ),
				16 => __( 'sixteen', 'ineosq-captcha-pro' ),
				17 => __( 'seventeen', 'ineosq-captcha-pro' ),
				18 => __( 'eighteen', 'ineosq-captcha-pro' ),
				19 => __( 'nineteen', 'ineosq-captcha-pro' ),

				20 => __( 'twenty', 'ineosq-captcha-pro' ),
				21 => __( 'twenty one', 'ineosq-captcha-pro' ),
				22 => __( 'twenty two', 'ineosq-captcha-pro' ),
				23 => __( 'twenty three', 'ineosq-captcha-pro' ),
				24 => __( 'twenty four', 'ineosq-captcha-pro' ),
				25 => __( 'twenty five', 'ineosq-captcha-pro' ),
				26 => __( 'twenty six', 'ineosq-captcha-pro' ),
				27 => __( 'twenty seven', 'ineosq-captcha-pro' ),
				28 => __( 'twenty eight', 'ineosq-captcha-pro' ),
				29 => __( 'twenty nine', 'ineosq-captcha-pro' ),

				30 => __( 'thirty', 'ineosq-captcha-pro' ),
				31 => __( 'thirty one', 'ineosq-captcha-pro' ),
				32 => __( 'thirty two', 'ineosq-captcha-pro' ),
				33 => __( 'thirty three', 'ineosq-captcha-pro' ),
				34 => __( 'thirty four', 'ineosq-captcha-pro' ),
				35 => __( 'thirty five', 'ineosq-captcha-pro' ),
				36 => __( 'thirty six', 'ineosq-captcha-pro' ),
				37 => __( 'thirty seven', 'ineosq-captcha-pro' ),
				38 => __( 'thirty eight', 'ineosq-captcha-pro' ),
				39 => __( 'thirty nine', 'ineosq-captcha-pro' ),

				40 => __( 'forty', 'ineosq-captcha-pro' ),
				41 => __( 'forty one', 'ineosq-captcha-pro' ),
				42 => __( 'forty two', 'ineosq-captcha-pro' ),
				43 => __( 'forty three', 'ineosq-captcha-pro' ),
				44 => __( 'forty four', 'ineosq-captcha-pro' ),
				45 => __( 'forty five', 'ineosq-captcha-pro' ),
				46 => __( 'forty six', 'ineosq-captcha-pro' ),
				47 => __( 'forty seven', 'ineosq-captcha-pro' ),
				48 => __( 'forty eight', 'ineosq-captcha-pro' ),
				49 => __( 'forty nine', 'ineosq-captcha-pro' ),

				50 => __( 'fifty', 'ineosq-captcha-pro' ),
				51 => __( 'fifty one', 'ineosq-captcha-pro' ),
				52 => __( 'fifty two', 'ineosq-captcha-pro' ),
				53 => __( 'fifty three', 'ineosq-captcha-pro' ),
				54 => __( 'fifty four', 'ineosq-captcha-pro' ),
				55 => __( 'fifty five', 'ineosq-captcha-pro' ),
				56 => __( 'fifty six', 'ineosq-captcha-pro' ),
				57 => __( 'fifty seven', 'ineosq-captcha-pro' ),
				58 => __( 'fifty eight', 'ineosq-captcha-pro' ),
				59 => __( 'fifty nine', 'ineosq-captcha-pro' ),

				60 => __( 'sixty', 'ineosq-captcha-pro' ),
				61 => __( 'sixty one', 'ineosq-captcha-pro' ),
				62 => __( 'sixty two', 'ineosq-captcha-pro' ),
				63 => __( 'sixty three', 'ineosq-captcha-pro' ),
				64 => __( 'sixty four', 'ineosq-captcha-pro' ),
				65 => __( 'sixty five', 'ineosq-captcha-pro' ),
				66 => __( 'sixty six', 'ineosq-captcha-pro' ),
				67 => __( 'sixty seven', 'ineosq-captcha-pro' ),
				68 => __( 'sixty eight', 'ineosq-captcha-pro' ),
				69 => __( 'sixty nine', 'ineosq-captcha-pro' ),

				70 => __( 'seventy', 'ineosq-captcha-pro' ),
				71 => __( 'seventy one', 'ineosq-captcha-pro' ),
				72 => __( 'seventy two', 'ineosq-captcha-pro' ),
				73 => __( 'seventy three', 'ineosq-captcha-pro' ),
				74 => __( 'seventy four', 'ineosq-captcha-pro' ),
				75 => __( 'seventy five', 'ineosq-captcha-pro' ),
				76 => __( 'seventy six', 'ineosq-captcha-pro' ),
				77 => __( 'seventy seven', 'ineosq-captcha-pro' ),
				78 => __( 'seventy eight', 'ineosq-captcha-pro' ),
				79 => __( 'seventy nine', 'ineosq-captcha-pro' ),

				80 => __( 'eighty', 'ineosq-captcha-pro' ),
				81 => __( 'eighty one', 'ineosq-captcha-pro' ),
				82 => __( 'eighty two', 'ineosq-captcha-pro' ),
				83 => __( 'eighty three', 'ineosq-captcha-pro' ),
				84 => __( 'eighty four', 'ineosq-captcha-pro' ),
				85 => __( 'eighty five', 'ineosq-captcha-pro' ),
				86 => __( 'eighty six', 'ineosq-captcha-pro' ),
				87 => __( 'eighty seven', 'ineosq-captcha-pro' ),
				88 => __( 'eighty eight', 'ineosq-captcha-pro' ),
				89 => __( 'eighty nine', 'ineosq-captcha-pro' ),

				90 => __( 'ninety', 'ineosq-captcha-pro' ),
				91 => __( 'ninety one', 'ineosq-captcha-pro' ),
				92 => __( 'ninety two', 'ineosq-captcha-pro' ),
				93 => __( 'ninety three', 'ineosq-captcha-pro' ),
				94 => __( 'ninety four', 'ineosq-captcha-pro' ),
				95 => __( 'ninety five', 'ineosq-captcha-pro' ),
				96 => __( 'ninety six', 'ineosq-captcha-pro' ),
				97 => __( 'ninety seven', 'ineosq-captcha-pro' ),
				98 => __( 'ninety eight', 'ineosq-captcha-pro' ),
				99 => __( 'ninety nine', 'ineosq-captcha-pro' )
			);
			$value = cptch_converting( $number_string[ $value ] );
		}
		return $value;
	}
}

if ( ! function_exists( 'cptch_converting' ) ) {
	function cptch_converting( $number_string ) {
		global $cptch_options;

		if ( in_array( 'words', $cptch_options['operand_format'] ) && 'en-US' == get_bloginfo( 'language' ) ) {
			/* array of htmlspecialchars for numbers and english letters */
			$htmlspecialchars_array = array();
			$htmlspecialchars_array['a'] = '&#97;';
			$htmlspecialchars_array['b'] = '&#98;';
			$htmlspecialchars_array['c'] = '&#99;';
			$htmlspecialchars_array['d'] = '&#100;';
			$htmlspecialchars_array['e'] = '&#101;';
			$htmlspecialchars_array['f'] = '&#102;';
			$htmlspecialchars_array['g'] = '&#103;';
			$htmlspecialchars_array['h'] = '&#104;';
			$htmlspecialchars_array['i'] = '&#105;';
			$htmlspecialchars_array['j'] = '&#106;';
			$htmlspecialchars_array['k'] = '&#107;';
			$htmlspecialchars_array['l'] = '&#108;';
			$htmlspecialchars_array['m'] = '&#109;';
			$htmlspecialchars_array['n'] = '&#110;';
			$htmlspecialchars_array['o'] = '&#111;';
			$htmlspecialchars_array['p'] = '&#112;';
			$htmlspecialchars_array['q'] = '&#113;';
			$htmlspecialchars_array['r'] = '&#114;';
			$htmlspecialchars_array['s'] = '&#115;';
			$htmlspecialchars_array['t'] = '&#116;';
			$htmlspecialchars_array['u'] = '&#117;';
			$htmlspecialchars_array['v'] = '&#118;';
			$htmlspecialchars_array['w'] = '&#119;';
			$htmlspecialchars_array['x'] = '&#120;';
			$htmlspecialchars_array['y'] = '&#121;';
			$htmlspecialchars_array['z'] = '&#122;';

			$simbols_lenght = strlen( $number_string );
			$simbols_lenght--;
			$number_string_new = str_split( $number_string );
			$converting_letters = rand( 1, $simbols_lenght );
			while ( 0 != $converting_letters ) {
				$position = rand( 0, $simbols_lenght );
				$number_string_new[ $position ] = isset( $htmlspecialchars_array[ $number_string_new[ $position ] ] ) ? $htmlspecialchars_array[ $number_string_new[ $position ] ] : $number_string_new[ $position ];
				$converting_letters--;
			}
			$number_string = '';
			foreach ( $number_string_new as $key => $value ) {
				$number_string .= $value;
			}
			return $number_string;
		} else
			return $number_string;
	}
}

/**
 * Function for encoding number
 * @param       string      $String
 * @param       string      $Password
 * @param       longint     $timestamp    current unix-time
 */
if ( !function_exists( 'cptch_encode' ) ) {
	function cptch_encode( $String, $Password, $timestamp ) {
		/* Check if key for encoding is empty */
		if ( !$Password ) die( __( "Encryption password is not set", 'ineosq-captcha-pro' ) );

		$Salt = md5( $timestamp, true );
		$String = substr( pack( "H*", sha1( $String ) ), 0, 1 ) . $String;
		$StrLen = strlen( $String );
		$Seq = $Password;
		$Gamma = '';

		while ( strlen( $Gamma ) < $StrLen ) {
			$Seq = pack( "H*", sha1( $Seq . $Gamma . $Salt ) );
			$Gamma .= substr( $Seq, 0, 8 );
		}

		return base64_encode ( $String ^ $Gamma );
	}
}

/* Function for decoding number */
if ( ! function_exists( 'cptch_decode' ) ) {
	function cptch_decode( $String, $Key, $timestamp ) {
		/* Check if key for encoding is empty */
		if ( ! $Key ) die( __( "Decryption password is not set", 'ineosq-captcha-pro' ) );

		$Salt = md5( $timestamp, true );
		$StrLen = strlen( $String );
		$Seq = $Key;
		$Gamma = '';

		while ( strlen( $Gamma ) < $StrLen ) {
			$Seq = pack( "H*", sha1( $Seq . $Gamma . $Salt ) );
			$Gamma .= substr( $Seq, 0, 8 );
		}

		$String = base64_decode( $String );
		$String = $String ^ $Gamma;
		$DecodedString = substr($String, 1);
		$Error = ord(substr($String, 0, 1) ^ substr(pack("H*", sha1($DecodedString)), 0, 1));
		return $Error ? false : $DecodedString;
	}
}

/**
 * Check CAPTCHA life time
 * @param  string    $form_slug        ( @since 4.2.3 )
 * @return boolean
 */
if ( ! function_exists( 'cptch_limit_exhausted' ) ) {
	function cptch_limit_exhausted( $form_slug = 'general' ) {
		global $cptch_options;

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}
		if ( ! array_key_exists( $form_slug, $cptch_options['forms'] ) ||
			!$cptch_options['forms'][ $form_slug ]['enable'] ||
			$cptch_options['forms'][ $form_slug ]['use_general'] )
			$form_slug = 'general';

		if ( ! $cptch_options['forms'][ $form_slug ]['enable_time_limit'] ) {
			return false;
		}

		$time_limit = $cptch_options['forms'][ $form_slug ]['time_limit'];

		return empty( $_REQUEST['cptch_time'] ) || $time_limit < time() - absint( $_REQUEST['cptch_time'] );
	}
}

if ( ! function_exists( 'cptch_pro_front_end_styles' ) ) {
	function cptch_pro_front_end_styles() {
		if ( ! is_admin() ) {
			global $cptch_options;
			if ( empty( $cptch_options ) )
				register_cptch_settings();
			wp_enqueue_style( 'cptch_stylesheet', plugins_url( 'css/front_end_style.css', __FILE__ ), array(), $cptch_options['plugin_option_version'] );
			wp_enqueue_style( 'dashicons' );

			$device_type = isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Windows Phone|Opera Mini/i', $_SERVER['HTTP_USER_AGENT'] ) ? 'mobile' : 'desktop';
			wp_enqueue_style( "cptch_{$device_type}_style", plugins_url( "css/{$device_type}_style.css", __FILE__ ), array(), $cptch_options['plugin_option_version'] );
		}
	}
}

if ( !function_exists( 'cptch_front_end_scripts' ) ) {
	function cptch_front_end_scripts() {
		global $cptch_options;

		if ( empty( $cptch_options ) ) {
			register_cptch_settings();
		}

		if ( wp_script_is( 'cptch_front_end_script', 'registered' ) &&
			!wp_script_is( 'cptch_front_end_script', 'enqueued' ) ) {
			wp_register_script( 'cptch_front_end_script', plugins_url( 'js/front_end_script.js', __FILE__ ), array( 'jquery' ), array(), $cptch_options['plugin_option_version'] );
			wp_enqueue_script( 'cptch_front_end_script' );
			$args = array(
				'nonce' => wp_create_nonce( 'cptch' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'enlarge' => $cptch_options['enlarge_images'],
				'is_la_pro' => function_exists( 'lmtttmptspr_handle_error' ),
				'time_limit' => $cptch_options['forms']['general']['time_limit']
			);
			wp_localize_script( 'cptch_front_end_script', 'cptch_vars', $args );

			if ( 'slide' == $cptch_options['type'] ) {
				wp_enqueue_script( 'slide-captcha-react', plugins_url( 'js/slide_captcha/dist/index-bundle.js', __FILE__ ), array( 'jquery' ), false, true );
				wp_localize_script( 'slide-captcha-react', 'wpSlideCaptcha', array(
					'ajax_url'               => admin_url( 'admin-ajax.php' ),
					'text_start_slide'       => $cptch_options['text_start_slide'],
					'text_end_slide'         => $cptch_options['text_end_slide'],
					'color_end_slide'        => $cptch_options['color_end_slide'],
					'color_start_slide'      => $cptch_options['color_start_slide'],
					'color_container_slide'  => $cptch_options['color_container_slide'],
					'color_text_slide'       => $cptch_options['color_text_slide'],
					'font_size_text_slide'   => $cptch_options['font_size_text_slide']
				) );
			}
		}
	}
}

if ( ! function_exists( 'cptch_pro_admin_head' ) ) {
	function cptch_pro_admin_head() {
		global $cptch_options;

		/* css for displaing an icon */
		wp_enqueue_style( 'cptch_icon', plugins_url( 'css/admin_page.css', __FILE__ ) );

		$pages = array(
			'ineosq_captcha_pro.php',
			'captcha-packages.php',
			'captcha-allowlist.php'
		);

		if ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $pages ) ) {
			wp_enqueue_style( 'cptch_stylesheet', plugins_url( 'css/style.css', __FILE__ ), array(), $cptch_options['plugin_option_version'] );
			wp_enqueue_script( 'cptch_script', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery', 'jquery-ui-accordion', 'jquery-ui-resizable', 'jquery-ui-tabs', 'wp-color-picker' ), $cptch_options['plugin_option_version'] );
			wp_enqueue_style( 'wp-color-picker' );
			$args = array(
				'cptch_ajax_nonce' => wp_create_nonce( 'cptch_ajax_nonce_value' )
			);
			wp_localize_script( 'cptch_script', 'cptch_vars', $args );

			if ( 'ineosq_captcha_pro.php' == $_GET['page'] ) {
				ineosq_enqueue_settings_scripts();
				ineosq_plugins_include_codemirror();
			}
		}
	}
}

if ( ! function_exists( 'cptch_reload' ) ) {
	function cptch_reload() {
		global $cptch_options, $cptch_id_postfix;
		check_ajax_referer( 'cptch', 'cptch_nonce' );

		if ( ! defined( 'CPTCH_RELOAD_AJAX' ) ) {
			define( 'CPTCH_RELOAD_AJAX', true );
		}

		if ( empty( $cptch_options ) ) {
			$cptch_options = is_network_admin() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
		}

		$form_slug  = isset( $_REQUEST['cptch_form_slug'] ) ? esc_attr( $_REQUEST['cptch_form_slug'] ) : 'general';
		$class      = isset( $_REQUEST['cptch_input_class'] ) ? esc_attr( $_REQUEST['cptch_input_class'] ) : '';
		$input_name = isset( $_REQUEST['cptch_input_name'] ) ? esc_attr( $_REQUEST['cptch_input_name'] ) : '';

		$return_array = array (
			'display' => cptch_display_captcha_custom( $form_slug, $class, $input_name ),
			'limit_time' => $cptch_options['forms'][ $form_slug ]['time_limit'],
			'id_postfix' => $cptch_id_postfix
		);
		echo json_encode( $return_array );

		die();
	}
}

if ( ! function_exists( 'cptch_pro_plugin_action_links' ) ) {
	function cptch_pro_plugin_action_links( $links, $file ) {
		static $this_plugin;
		if ( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=ineosq_captcha_pro.php">' . __( 'Settings', 'ineosq-captcha-pro' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
}

if ( ! function_exists( 'cptch_pro_register_plugin_links' ) ) {
	function cptch_pro_register_plugin_links( $links, $file ) {
		$base = "ineosq-captcha-pro/ineosq_captcha_pro.php";
		if ( $file == $base ) {
			$links[] = '<a href="admin.php?page=ineosq_captcha_pro.php">' . __( 'Settings', 'ineosq-captcha-pro' ) . '</a>';
			$links[] = '<a href="https://support.ineosq.com/hc/en-us/sections/200538879" target="_blank">' . __( 'FAQ', 'ineosq-captcha-pro' ) . '</a>';
			$links[] = '<a href="https://support.ineosq.com">' . __( 'Support', 'ineosq-captcha-pro' ) . '</a>';
		}
		return $links;
	}
}

if ( ! function_exists( 'cptch_pro_plugin_banner' ) ) {
	function cptch_pro_plugin_banner() {
		global $hook_suffix, $cptch_plugin_info, $cptch_options;
		$is_network = is_network_admin();
		$cptch_options = $is_network ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );

		/* Displays notice about possible conflict with W3 Total Cache plugin */
		echo cptch_w3tc_notice();

		if ( 'ineosq-plugins_page_captcha_pro' === $hook_suffix ) {
			ineosq_plugin_suggest_feature_banner( $cptch_plugin_info, 'cptch_options', 'captcha-ineosq' );
		}

		if ( 'plugins.php' === $hook_suffix ) {
			ineosq_plugin_banner_to_settings( $cptch_plugin_info, 'cptch_options', 'captcha-ineosq', 'admin.php?page=ineosq_captcha_pro.php' );
		}
		/**
		 * display the baner about CAPTCHA`s new abilities
		 * @since 1.6.9
		 */
		if ( in_array( $hook_suffix, array( 'ineosq-plugins_page_captcha_pro', 'plugins.php', 'update-core.php' ) ) && ( !isset( $cptch_options['display_packages_notice'] ) || $cptch_options['display_packages_notice'] ) ) {
			$cptch_options['display_packages_notice'] = false;

			if ( $is_network ) {
				update_site_option( 'cptch_options', $cptch_options );
			} else {
				update_option( 'cptch_options', $cptch_options );
			} ?>
			<div class="updated" style="padding: 0; margin: 0; border: none; background: none; box-shadow: none; ">
				<div class="ineosq_banner_on_plugin_page ineosq_banner_to_settings">
					<div class="icon"><img title="" src="//ps.w.org/captcha-ineosq/assets/icon-128x128.png" alt="" /></div>
					<div class="text">
						<strong><?php _e( 'New: Custom Image Packs for Captcha', 'ineosq-captcha-pro' ); ?></strong><br />
						<?php _e( 'Stand out, brand yourself and enhance forms protection with custom captcha images', 'ineosq-captcha-pro' ); ?>.
						<a href="<?php echo self_admin_url( 'admin.php?page=captcha-packages.php' ); ?>"><?php _e( 'Try Now', 'ineosq-captcha-pro' ); ?></a>
					</div>
					<form method="post" action="<?php echo remove_query_arg( 'cptch_display_packages_notice', ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ); ?>">
						<button class="notice-dismiss ineosq_hide_settings_notice" title="<?php _e( 'Close notice', 'ineosq-captcha-pro' ); ?>"></button>
					</form>
				</div>
			</div>
		<?php
		}
	}
}

/* Notice on the settings page about possible conflict with W3 Total Cache plugin */
if (!function_exists( 'cptch_w3tc_notice' )) {
	function cptch_w3tc_notice() {
		global $cptch_options, $cptch_plugin_info;
		if ( ! is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
			return;
		}

		if ( empty( $cptch_options ) ) {
			$cptch_options = is_network_admin() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
		}

		if ( empty( $cptch_options['w3tc_notice'] ) ) {
			return '';
		}

		if ( isset( $_GET['cptch_nonce'] ) && wp_verify_nonce( $_GET['cptch_nonce'], 'cptch_clean_w3tc_notice' ) ) {
			unset( $cptch_options['w3tc_notice'] );
			if ( is_network_admin() ) {
				update_site_option( 'cptch_options', $cptch_options );
			} else {
				update_option( 'cptch_options', $cptch_options );
			}
			return '';
		}

		$url = add_query_arg(
			array(
				'cptch_clean_w3tc_notice' => '1',
				'cptch_nonce' => wp_create_nonce( 'cptch_clean_w3tc_notice' )
			),
			( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
		);
		$close_link = "<a href=\"{$url}\" class=\"close_icon notice-dismiss\"></a>";
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			admin_url( 'admin.php?page=ineosq_captcha_pro.php#cptch_load_via_ajax' ),
			__( 'settings page', 'ineosq-captcha-pro' )
		);
		$message = sprintf(
			__( 'You\'re using W3 Total Cache plugin. If %1$s doesn\'t work properly, please clear the cache in W3 Total Cache plugin and turn on \'%2$s\' option on the plugin %3$s.', 'ineosq-captcha-pro' ),
			$cptch_plugin_info['Name'],
			__( 'Show CAPTCHA after the end of the page loading', 'ineosq-captcha-pro' ),
			$settings_link
		);
		return
			"<style>
				.cptch_w3tc_notice {
					position: relative;
				}
				.cptch_w3tc_notice a {
					text-decoration: none;
				}
			</style>
			<div class=\"cptch_w3tc_notice error\"><p>{$message}</p>{$close_link}</div>";
	}
}

/* Add Captcha to the multisite login form */
if ( !function_exists( 'cptch_signup_display' ) ) {
	function cptch_signup_display( $errors ) {
		if ( $error_message = $errors->get_error_message( 'cptch_error' ) ) {
			printf( '<p class="error cptch_error">%s</p>', $error_message );
		}
		if ( $error_message = $errors->get_error_message( 'cptch_error_lmtttmpts' ) ) {
			printf( '<p class="error cptch_error_lmtttmpts">%s</p>', $error_message );
		}
		echo cptch_cf_form();
	}
}

/**
 *
 * @since 4.2.3
 */
if ( ! function_exists( 'cptch_captcha_is_needed' ) ) {
	function cptch_captcha_is_needed( $form_slug, $user_loggged_in ) {
		global $cptch_options;

		return
			$cptch_options['forms'][ $form_slug ]['enable'] && ( ! $user_loggged_in ||
			                                                     empty( $cptch_options['forms'][ $form_slug ]['hide_from_registered'] ) );
	}
}

/*======= Functions for adding all functionality for updating ====*/
if ( ! function_exists( 'cptch_update_activate' ) ) {
	function cptch_update_activate() {
		global $bstwbsftwppdtplgns_options, $ineosq_wp_update_plugins;

		$pro  = 'ineosq-captcha-pro/ineosq_captcha_pro.php';
		$free = 'captcha-ineosq/captcha-ineosq.php';
		$plus = 'captcha-plus/captcha-plus.php';
		$flag = false;

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( ! is_plugin_active_for_network( $pro ) ) {
				$deactivate_not_for_all_network = true;
			}
		}

		if ( isset( $deactivate_not_for_all_network ) && is_plugin_active_for_network( $free ) ) {
			global $wpdb;
			deactivate_plugins( $free );

			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				activate_plugin( $free );
			}
			switch_to_blog( $old_blog );
		} else {
			deactivate_plugins( $free );
		}
		if ( isset( $deactivate_not_for_all_network ) && is_plugin_active_for_network( $plus ) ) {
			global $wpdb;
			deactivate_plugins( $plus );

			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				activate_plugin( $plus );
			}
			switch_to_blog( $old_blog );
		} else {
			deactivate_plugins( $plus );
		}

		/* api for update ineosq-plugins */
		if ( ! function_exists( 'ineosq_wp_update_plugins' ) && ! $ineosq_wp_update_plugins ) {
			$ineosq_wp_update_plugins = true;
			require_once(dirname(__FILE__) . '/ineosq_update.php');
		}

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			if ( is_multisite() ) {
				if ( ! get_site_option( 'bstwbsftwppdtplgns_options' ) ) {
					add_site_option( 'bstwbsftwppdtplgns_options', array() );
				}
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				if ( ! get_option( 'bstwbsftwppdtplgns_options' ) ) {
					add_option( 'bstwbsftwppdtplgns_options', array() );
				}
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}

		if ( $bstwbsftwppdtplgns_options && ! file_exists( dirname( __FILE__ ) . '/license_key.txt' ) ) {
			if ( isset( $bstwbsftwppdtplgns_options['ineosq-captcha-pro/ineosq_captcha_pro.php'] ) ) {
				$ineosq_license_key = $bstwbsftwppdtplgns_options['ineosq-captcha-pro/ineosq_captcha_pro.php'];
				$file            = @fopen( dirname( __FILE__ ) . "/license_key.txt", "w+" );
				if ( $file ) {
					@fwrite( $file, $ineosq_license_key );
					@fclose( $file );
				}
			}
		}
	}
}

if ( ! function_exists( 'cptch_license_cron_task' ) ) {
	function cptch_license_cron_task() {
		/* check if we solve the problem */
		if ( !function_exists( 'ineosq_license_cron_task' ) )
			require_once(dirname(__FILE__) . '/ineosq_update.php');
		ineosq_license_cron_task( 'ineosq-captcha-pro/ineosq_captcha_pro.php', 'captcha-ineosq/captcha-ineosq.php' );
	}
}

if ( ! function_exists( 'cptch_plugin_update_row' ) ) {
	function cptch_plugin_update_row( $file, $plugin_data ) {
		ineosq_plugin_update_row( 'ineosq-captcha-pro/ineosq_captcha_pro.php' );
	}
}

if ( ! function_exists( 'cptch_inject_info' ) ) {
	function cptch_inject_info( $result, $action = null, $args = null ) {
		if ( ! function_exists( 'ineosq_inject_info' ) ) {
			require_once(dirname(__FILE__) . '/ineosq_update.php');
		}

		$native_slug = 'ineosq-captcha-pro';

		return ineosq_inject_info( $result, $action, $args, $native_slug );
	}
}

if ( !function_exists( 'cptch_plugin_deactivation' ) ) {
	function cptch_plugin_deactivation() {
		global $cptch_options;
		if ( empty( $cptch_options ) ) {
			$cptch_options = is_network_admin() ? get_site_option( 'cptch_options' ) : get_option( 'cptch_options' );
		}
		$cptch_options['activate_akismet'] = false;
		update_option( 'cptch_options', $cptch_options );
	}
}

/* Function for delete options */
if ( !function_exists( 'cptch_delete_options' ) ) {
	function cptch_delete_options() {
		global $wpdb;

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$all_plugins = get_plugins();
		$is_another_captcha = array_key_exists( 'captcha-ineosq/captcha-ineosq.php', $all_plugins ) || array_key_exists( 'captcha-plus/captcha-plus.php', $all_plugins );

		if ( is_multisite() ) {
			delete_site_option( 'cptch_options' );
		}

		/* do nothing if Plus\Free CAPTCHA are installed */
		if ( $is_another_captcha ) {
			return;
		}
		if ( is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'cptch_options' );
				$prefix = 1 == $blog_id ? $wpdb->base_prefix : $wpdb->base_prefix . $blog_id . '_';
				$wpdb->query( "DROP TABLE `{$prefix}cptch_allowlist`;" );
			}
			switch_to_blog( 1 );
			$upload_dir = wp_upload_dir();
			switch_to_blog( $old_blog );
		} else {
			delete_option( 'cptch_options' );
			$wpdb->query( "DROP TABLE `{$wpdb->prefix}cptch_allowlist`;" );
			$upload_dir = wp_upload_dir();
		}

		/* clear all scheduled hooks */
		cptch_clear_scheduled_hook();

		/* delete images */
		$wpdb->query( "DROP TABLE `{$wpdb->base_prefix}cptch_images`, `{$wpdb->base_prefix}cptch_packages`;" );
		$images_dir = $upload_dir['basedir'] . '/ineosq_captcha_images';
		$packages = scandir( $images_dir );
		if ( is_array( $packages ) ) {
			foreach ( $packages as $package ) {
				if ( ! in_array( $package, array( '.', '..' ) ) ) {
					/* remove all files from package */
					array_map( 'unlink', glob("{$images_dir}/{$package}/*.*") );
					/* remove package */
					rmdir( "{$images_dir}/{$package}" );
				}
			}
		}
		rmdir( $images_dir );

		require_once(dirname(__FILE__) . '/ineosq_menu/ineosq_include.php');
		ineosq_include_init( plugin_basename(__FILE__) );
		ineosq_delete_plugin( plugin_basename(__FILE__) );
	}
}

/* Add Captcha forms to the Limit Attempts plugin */
if ( !function_exists( 'cptch_add_lmtttmpts_forms' ) ) {
	function cptch_add_lmtttmpts_forms( $forms = array() ) {
		if ( ! is_array( $forms ) ) {
			$forms = array();
		}

		$forms['cptch'] = array(
			'name' => 'Captcha Plugin',
			'forms' => array(),
		);
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$captcha_forms = cptch_get_forms();

		foreach ( $captcha_forms as $form_slug => $form_data ) {
			$forms["cptch"]["forms"]["{$form_slug}_captcha_check"] = $form_data;
			if ( empty( $form_data['form_notice'] ) ) {
				$forms["cptch"]["forms"]["{$form_slug}_captcha_check"]['form_notice'] = cptch_get_section_notice( substr( $form_slug, 0, strpos( $form_slug, '_' ) ) );
			}
		}
		return $forms;
	}
}

if ( ! function_exists( 'cptch_get_forms' ) ) {
	function cptch_get_forms() {
		global $cptch_forms;

		$default_forms = array(
			'login_form' => array( 'form_name' => __( 'Login form', 'сaptcha-pro' ) ),
			'registration_form' => array( 'form_name' => __( 'Registration form', 'сaptcha-pro' ) ),
			'reset_password_form' => array( 'form_name' => __( 'Reset password form', 'сaptcha-pro' ) ),
			'comments_form' => array( 'form_name' => __( 'Comments form', 'сaptcha-pro' ) ),
			'contact_form' => array( 'form_name' => 'Contact Form' ),
			'booking_form' => array( 'form_name' => 'Car Rental V2 Pro' ),
			'contact_form_7' => array( 'form_name' => 'Contact Form 7' ),
			'jetpack_contact_form' => array( 'form_name' => __( 'Jetpack Contact Form', 'сaptcha-pro' ) ),
			'mailchimp_form' => array( 'form_name' => 'MailChimp for WordPress' ),
			'ninja_form' => array( 'form_name' => 'Ninja Form ' ),
			'wpforms' => array( 'form_name' => 'WPForms' ),
			'subscriber' => array( 'form_name' => 'Subscriber' ),
			'bbpress_new_topic_form' => array( 'form_name' => __( 'bbPress New Topic form', 'сaptcha-pro' ) ),
			'bbpress_reply_form' => array( 'form_name' => __( 'bbPress Reply form', 'сaptcha-pro' ) ),
			'buddypress_registration_form' => array( 'form_name' => __( 'BuddyPress Registration form', 'сaptcha-pro' ) ),
			'buddypress_comments_form' => array( 'form_name' => __( 'BuddyPress Comments form', 'сaptcha-pro' ) ),
			'buddypress_create_group_form' => array( 'form_name' => __( 'BuddyPress Add New Group form', 'сaptcha-pro' ) ),
			'woocommerce_login_form' => array( 'form_name' => __( 'WooCommerce Login form', 'сaptcha-pro' ) ),
			'woocommerce_register_form' => array( 'form_name' => __( 'WooCommerce Registration form', 'сaptcha-pro' ) ),
			'woocommerce_lost_password_form' => array( 'form_name' => __( 'WooCommerce Reset password form', 'сaptcha-pro' ) ),
			'woocommerce_checkout_billing_form' => array( 'form_name' => __( 'WooCommerce Checkout form', 'сaptcha-pro' ) ),
			'wpforo_login_form' => array( 'form_name' => __( 'wpForo Login form', 'сaptcha-pro' ) ),
			'wpforo_register_form' => array( 'form_name' => __( 'wpForo Registration form', 'ineosq-captcha-pro' ) ),
			'wpforo_new_topic_form' => array( 'form_name' => __( 'wpForo New Topic form', 'сaptcha-pro' ) ),
			'wpforo_reply_form' => array( 'form_name' => __( 'wpForo Reply form', 'ineosq-captcha-pro' ) )
		);

		$custom_forms = apply_filters( 'cptch_add_custom_form', array() );
		$cptch_forms = array_merge( $default_forms, $custom_forms );

		foreach ( $cptch_forms as $form_slug => $form_data ) {
			$cptch_forms[ $form_slug ]['form_notice'] = cptch_get_form_notice( $form_slug );
		}

		$cptch_forms = apply_filters( 'cptch_forms', $cptch_forms );

		return $cptch_forms;
	}
}

if ( ! function_exists( 'cptch_get_form_notice' ) ) {
	function cptch_get_form_notice( $form_slug = '' ) {
		global $wp_version, $cptch_plugin_info;
		$form_notice = "";
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$plugins = array(
			'contact_form' => array( 'contact-form-plugin/contact_form.php', 'contact-form-pro/contact_form_pro.php' ),
			'booking_form' => 'ineosq-car-rental-pro/ineosq-car-rental-pro.php',
			'subscriber' => array( 'subscriber/subscriber.php', 'subscriber-pro/subscriber-pro.php' ),
			'contact_form_7' => 'contact-form-7/wp-contact-form-7.php',
			'jetpack_contact_form' => 'jetpack/jetpack.php',
			'mailchimp_form' => 'mailchimp-for-wp/mailchimp-for-wp.php',
			'ninja_form'	=> 'ninja-forms/ninja-forms.php',
			'gravity_forms'			=> 'gravityforms/gravityforms.php',
			'elementor_contact_form' => 'elementor-pro/elementor-pro.php',
            'wpforms'               => array( 'wpforms-lite/wpforms.php', 'wpforms/wpforms.php' )
		);

		if ( isset( $plugins[ $form_slug ] ) ) {
			$plugin_info = cptch_plugin_status( $plugins[ $form_slug ], get_plugins(), is_network_admin() );

			if ( 'activated' == $plugin_info['status'] ) {
				/* check required conditions */
				if ( 'contact_form_7' == $form_slug ) {
					if ( version_compare( $plugin_info['plugin_info']['Version'], '3.4', '<' ) ) {
						$form_notice = '<a href="' . self_admin_url( 'plugins.php' ) . '">' . sprintf(__( 'Update %s at least up to %s', 'ineosq-captcha-pro' ), 'Contact Form 7', 'v3.4' ) . '</a>';
					} elseif ( defined( 'WPCF7_VERSION' ) &&
						defined( 'WPCF7_REQUIRED_WP_VERSION' ) &&
						version_compare( $wp_version, WPCF7_REQUIRED_WP_VERSION, '<' ) ) {
						$form_notice = sprintf(
							__( '%1$s %2$s requires WordPress %3$s or higher.', 'ineosq-captcha-pro' ),
							'Contact Form 7',
							WPCF7_VERSION,
							WPCF7_REQUIRED_WP_VERSION
						);
					}
				}
			} elseif ( 'deactivated' == $plugin_info['status'] ) {
				$form_notice = '<a href="' . self_admin_url( 'plugins.php' ) . '">' . __( 'Activate', 'ineosq-captcha-pro' ) . '</a>';
			} elseif ( 'not_installed' == $plugin_info['status'] ) {
				if ( 'contact_form' == $form_slug ) {
					$form_notice = '<a href="https://ineosq.com/products/wordpress/plugins/contact-form/?k=fa26df3911ebcd90c3e85117d6dd0ce0&pn=281&v=' . $cptch_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'Install Now', 'ineosq-captcha-pro' ) . '</a>';
				} elseif ( 'subscriber' == $form_slug ) {
					$form_notice = '<a href="https://ineosq.com/products/wordpress/plugins/subscriber/?k=c5c7708922e53ab2c3e5c1137d44e3a2&pn=281&v=' . $cptch_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'Install Now', 'ineosq-captcha-pro' ) . '</a>';
				} elseif ( 'gravity_forms' == $form_slug ) {
					$form_notice = '<a href="https://www.gravityforms.com/" target="_blank">' . __( 'Install Now', 'ineosq-captcha-pro' ) . '</a>';
                } elseif ( 'elementor_contact_form' == $form_slug ) {
					$form_notice = '<a href="https://elementor.com/" target="_blank">' . __( 'Install Now', 'ineosq-captcha-pro' ) . '</a>';
                } else {
					$slug = explode( '/', $plugins[ $form_slug ] );
					$slug = $slug[0];
					$form_notice = sprintf( '<a href="http://wordpress.org/plugins/%s/" target="_blank">%s</a>', $slug, __( 'Install Now', 'ineosq-captcha-pro' ) );
				}
			}
		}
		return apply_filters( 'cptch_form_notice', $form_notice, $form_slug );
	}
}

/**
 * Display section notice
 * @access public
 * @param  $section_slug	string
 * @return array    The action results
 */
if ( ! function_exists( 'cptch_get_section_notice' ) ) {
	function cptch_get_section_notice( $section_slug = '' ) {
		$section_notice = "";
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		$plugins = array(
			'bbpress' => 'bbpress/bbpress.php',
			'buddypress' => 'buddypress/bp-loader.php',
			'woocommerce' => 'woocommerce/woocommerce.php',
			'wpforo' => 'wpforo/wpforo.php'
		);

		$is_network_admin = is_network_admin();

		if ( isset( $plugins[ $section_slug ] ) ) {
			$slug = explode( '/', $plugins[ $section_slug ] );
			$slug = $slug[0];
			$plugin_info = cptch_plugin_status( $plugins[ $section_slug ], get_plugins(), $is_network_admin );
			if ( 'activated' == $plugin_info['status'] ) {
				/* check required conditions */

				/* BuddyPress works only with single site or main domain */
				if ( 'buddypress' == $section_slug && ! ( is_main_site() || $is_network_admin ) ) {
					$section_notice = __( 'BuddyPress works only with single site or main domain', 'ineosq-captcha-pro' );
				}
			} elseif ( 'deactivated' == $plugin_info['status'] ) {
				$section_notice = '<a href="' . self_admin_url( 'plugins.php' ) . '">' . __( 'Activate', 'ineosq-captcha-pro' ) . '</a>';
			} elseif ( 'not_installed' == $plugin_info['status'] ) {
				$section_notice = sprintf( '<a href="http://wordpress.org/plugins/%s/" target="_blank">%s</a>', $slug, __( 'Install Now', 'ineosq-captcha-pro' ) );
			}
		}

		return apply_filters( 'cptch_section_notice', $section_notice, $section_slug );
	}
}

if ( ! function_exists( 'cptch_plugin_status' ) ) {
	function cptch_plugin_status ( $plugins, $all_plugins, $is_network ) {
		$result = array(
			'status' => '',
			'plugin' => '',
			'plugin_info' => array(),
		);
		foreach ( (array)$plugins as $plugin ) {
			if ( array_key_exists( $plugin, $all_plugins ) ) {
				if ( ( $is_network && is_plugin_active_for_network( $plugin ) ) || ( !$is_network && is_plugin_active( $plugin ) ) ) {
					$result['status'] = 'activated';
					$result['plugin'] = $plugin;
					$result['plugin_info'] = $all_plugins[ $plugin ];
					break;
				} else {
					$result['status'] = 'deactivated';
					$result['plugin'] = $plugin;
					$result['plugin_info'] = $all_plugins[ $plugin ];
				}

			}
		}
		if ( empty( $result['status'] ) )
			$result['status'] = 'not_installed';
		return $result;
	}
}

/* create slide captcha response, g-recaptcha-response analogue */
if ( ! function_exists( 'cptch_set_slide_captcha_response' ) ) {
	function cptch_set_slide_captcha_response() {
	    global $wp_hasher, $wpdb;

		// Generate something random for a response key.
		$key = wp_generate_password( 20, false );

		// Now insert the response, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}

		$hashed    = time() . ':' . $wp_hasher->HashPassword( $key );
		$time = time();

		$key_saved = $wpdb->insert( $wpdb->base_prefix . 'cptch_responses', array( 'response' => $hashed, 'add_time' => $time ) );

		if ( false === $key_saved ) {
			return new WP_Error( 'no_response_key_update', __( 'Could not save captcha response to database.', 'ineosq-captcha-pro' ) );
		}

		return $hashed;
	}
}

if ( ! function_exists( 'cptch_check_slide_captcha_response' ) ) {
	function cptch_check_slide_captcha_response( $response ) {
        global $wpdb;

	    $allow = false;
		$expiration_duration  = 55;

	    /* sanitize the response */
		$response = trim( esc_attr( $response ));

    /* check that the key exists and is not expired */
		if( is_multisite() ) {
			$db_response = $wpdb->get_row( $wpdb->prepare( "SELECT `response`, `add_time` FROM `{$wpdb->get_blog_prefix(0)}cptch_responses` WHERE `response` = %s;", $response ) );
		} else {
			$db_response = $wpdb->get_row( $wpdb->prepare( "SELECT `response`, `add_time` FROM `{$wpdb->prefix}cptch_responses` WHERE `response` = %s;", $response ) );
		}
		$response_request_time = $db_response->add_time;

		$expiration_time = $response_request_time + $expiration_duration;
        $is_expired = ( ! $expiration_time || time() > $expiration_time  ) ? true : false;

		if ( $db_response->response && ! $is_expired ) {
			/* allow to submit form  */
			$allow = true;
        }

		return $allow;
	}
}

if ( ! function_exists( 'cptch_validate_slide_captcha' ) ) {
	function cptch_validate_slide_captcha() {

		$result = array();

		/* if slide captcha passed - set response to db */
		if ( ! empty( $_POST['is_touch_end'] ) ) {
			$slide_captcha_response = cptch_set_slide_captcha_response();

			if ( is_wp_error( $slide_captcha_response ) ) {
				$result = array(
                            'error' => array(
                                'code' => '3848',
                                'message' => __( 'Error', 'ineosq-captcha-pro' )
                            )
                );
            } else {
				$result = array (
					'slide_captcha_response'   => $slide_captcha_response
				);
            }
        }

		echo json_encode( $result );
		wp_die();
	}
}

if ( ! function_exists( 'cptch_delete_expired_responses' ) ) {
	function cptch_delete_expired_responses() {
		global $wpdb;

		$expiration_duration = MINUTE_IN_SECONDS; // captcha life time
		$expire = ( time() - $expiration_duration );

		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->prefix}cptch_responses` WHERE add_time <= %s", $expire ) );
	}
}

/* activation scheduled hook for delete expired response */
if ( ! function_exists( 'cptch_add_scheduled_hook' ) ) {
	function cptch_add_scheduled_hook () {
		if ( ! wp_next_scheduled ('delete_expired_responses' ) ) {
			wp_schedule_event (time () + HOUR_IN_SECONDS, 'hourly', 'delete_expired_responses');
		}
	}
}

/* deactivation scheduled hook for delete expired response */
if ( ! function_exists( 'cptch_clear_scheduled_hook' ) ) {
	function cptch_clear_scheduled_hook() {
		wp_clear_scheduled_hook ( 'delete_expired_responses');
	}
}

/* activate plugin */
register_activation_hook("ineosq-captcha-pro/ineosq_captcha_pro.php", 'cptch_plugin_activation' );

add_action( 'admin_menu', 'cptch_pro_add_admin_menu' );
add_action( 'network_admin_menu', 'cptch_pro_add_admin_menu' );

add_action( 'init', 'cptch_pro_init' );
add_action( 'admin_init', 'cptch_pro_admin_init', 45 );

add_action( 'plugins_loaded', 'cptch_pro_plugins_loaded', 12 );
add_action( 'plugins_loaded', 'cptch_pro_include_plugins_files_before', 9 );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'cptch_pro_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'cptch_pro_register_plugin_links', 10, 2 );

add_filter( 'lmtttmpts_plugin_forms', 'cptch_add_lmtttmpts_forms', 10, 1 );

add_action( 'admin_notices', 'cptch_pro_plugin_banner' );
add_action( 'network_admin_notices', 'cptch_pro_plugin_banner' );

add_action( 'admin_enqueue_scripts', 'cptch_pro_admin_head' );
add_action( 'wp_enqueue_scripts', 'cptch_pro_front_end_styles' );
add_action( 'login_enqueue_scripts', 'cptch_pro_front_end_styles' );

/* Function for updating plugin */
/* Function update cron */
add_action( 'captcha_pro_license_cron', 'cptch_license_cron_task' );
/* add notice about plugin license timeout */
add_action( "after_plugin_row_ineosq-captcha-pro/ineosq_captcha_pro.php", 'cptch_plugin_update_row', 10, 2 );
add_filter( 'plugins_api', 'cptch_inject_info', 20, 3 );

add_action( 'wp_ajax_cptch_reload', 'cptch_reload' );
add_action( 'wp_ajax_nopriv_cptch_reload', 'cptch_reload' );

/* AJAX hooks to verify the CAPTCHA in the BuddyPress comments form */
add_action( 'wp_ajax_cptch_buddypress_comment_validate', 'cptch_buddypress_comment_ajax' );
add_action( 'wp_ajax_cptch_handle_errors', 'cptch_handle_errors_ajax' );

add_filter( 'cptch_display', 'cptch_display_filter', 10, 3 );
add_filter( 'cptch_verify', 'cptch_verify_filter', 10, 3 );

add_shortcode( 'ineosq_captcha', 'cptch_display_captcha_shortcode' );
add_filter( 'ineosq_shortcode_button_content', 'cptch_shortcode_button_content' );

//display captcha for the custom login form which used hook 'wp_login_form'
add_filter( 'login_form_middle', 'cptch_cf_form' );

add_action( 'wp_ajax_nopriv_validate_slide_captcha', 'cptch_validate_slide_captcha' );
add_action( 'wp_ajax_validate_slide_captcha', 'cptch_validate_slide_captcha' );

/* scheduled hook for delete expired response */
add_action ('delete_expired_responses', 'cptch_delete_expired_responses');
