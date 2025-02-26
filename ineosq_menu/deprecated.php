<?php
/*
* Deprecated functions for IneosQ plugins
*/

/**
* Function check if plugin is compatible with current WP version - for old plugin version
* 
* @deprecated 1.7.4
* @todo Remove function after 01.01.2018
*/
if ( ! function_exists( 'ineosq_wp_version_check' ) ) {
	function ineosq_wp_version_check( $plugin_basename, $plugin_info, $require_wp ) {
		global $bstwbsftwppdtplgns_options;
		if ( ! isset( $bstwbsftwppdtplgns_options ) )
			$bstwbsftwppdtplgns_options = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
		if ( ! isset( $bstwbsftwppdtplgns_options['deprecated_function']['ineosq_wp_version_check'] ) ) {
			$bstwbsftwppdtplgns_options['deprecated_function']['ineosq_wp_version_check'] = array(
				'product-name' => $plugin_info['Name']
			);
			if ( is_multisite() )
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			else
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
		}
	}
}
/**
* Function add INEOSQ Plugins page - for old plugin version
* 
* @deprecated 1.7.9
* @todo Remove function after 01.01.2018
*/
if ( ! function_exists( 'ineosq_add_general_menu' ) ) {
	function ineosq_add_general_menu() {
		global $bstwbsftwppdtplgns_options;
		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			$bstwbsftwppdtplgns_options = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
		}
		if ( ! isset( $bstwbsftwppdtplgns_options['deprecated_function']['ineosq_add_general_menu'] ) ) {
			$get_debug_backtrace = debug_backtrace();
			$file = ( ! empty( $get_debug_backtrace[0]['file'] ) ) ? $get_debug_backtrace[0]['file'] : '';
			$bstwbsftwppdtplgns_options['deprecated_function']['ineosq_add_general_menu'] = array(
				'file' => $file
			);
			if ( is_multisite() ) {
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			} else {
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			}
		}
	}
}
/**
* Function display GO PRO tab - for old plugin version
* @deprecated 1.7.6
* @todo Remove function after 01.01.2018
*/
if ( ! function_exists( 'ineosq_go_pro_tab' ) ) {
	function ineosq_go_pro_tab( $plugin_info, $plugin_basename, $page, $pro_page, $ineosq_license_plugin, $link_slug, $link_key, $link_pn, $pro_plugin_is_activated = false, $trial_days_number = false ) {
		global $bstwbsftwppdtplgns_options;
		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			$bstwbsftwppdtplgns_options = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
		}
		if ( ! isset( $bstwbsftwppdtplgns_options['deprecated_function']['ineosq_go_pro_tab'] ) ) {
			$bstwbsftwppdtplgns_options['deprecated_function']['ineosq_go_pro_tab'] = array(
				'product-name' => $plugin_info['Name']
			);
			if ( is_multisite() ) {
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			} else {
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			}
		}
	}
}
/**
* Function add INEOSQ Plugins page
* @deprecated 1.9.8 (15.12.2016)
* @return void
*/
if ( ! function_exists ( 'ineosq_general_menu' ) ) {
	function ineosq_general_menu() {
		global $menu, $ineosq_general_menu_exist;

		if ( ! $ineosq_general_menu_exist ) {
			/* we check also menu exist in global array as in old plugins $ineosq_general_menu_exist variable not exist */
			foreach ( $menu as $value_menu ) {
				if ( 'ineosq_panel' == $value_menu[2] ) {
					$ineosq_general_menu_exist = true;
					return;
				}
			}

			add_menu_page( 'INEOSQ Panel', 'INEOSQ Panel', 'manage_options', 'ineosq_panel', 'ineosq_add_menu_render', 'none', '1001' );

			add_submenu_page( 'ineosq_panel', esc_html__( 'Plugins', 'ineosq' ), esc_html__( 'Plugins', 'ineosq' ), 'manage_options', 'ineosq_panel', 'ineosq_add_menu_render' );
			add_submenu_page( 'ineosq_panel', esc_html__( 'Themes', 'ineosq' ), esc_html__( 'Themes', 'ineosq' ), 'manage_options', 'ineosq_themes', 'ineosq_add_menu_render' );
			add_submenu_page( 'ineosq_panel', esc_html__( 'System Status', 'ineosq' ), esc_html__( 'System Status', 'ineosq' ), 'manage_options', 'ineosq_system_status', 'ineosq_add_menu_render' );

			$ineosq_general_menu_exist = true;
		}
	}
}
/**
* Function check license key for Pro plugins version
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.01.2018. Remove function after 01.01.2019
*/
if ( ! function_exists( 'ineosq_check_pro_license' ) ) {
	function ineosq_check_pro_license( $plugin_basename, $trial_plugin = false ) {
		global $wp_version, $bstwbsftwppdtplgns_options;
		$result = array();

		if ( isset( $_POST['ineosq_license_submit'] ) && check_admin_referer( $plugin_basename, 'ineosq_license_nonce_name' ) ) {
			$license_key = isset( $_POST['ineosq_license_key'] ) ? sanitize_text_field( $_POST['ineosq_license_key'] ) : '';

			if ( '' != $license_key ) {

				delete_transient( 'ineosq_plugins_update' );

				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$plugins_all = get_plugins();
				$current = get_site_transient( 'update_plugins' );

				if ( is_array( $plugins_all ) && !empty( $plugins_all ) && isset( $current ) && is_array( $current->response ) ) {
					$to_send = array();
					$to_send["plugins"][ $plugin_basename ] = $plugins_all[ $plugin_basename ];
					$to_send["plugins"][ $plugin_basename ]["ineosq_license_key"] = $license_key;
					$to_send["plugins"][ $plugin_basename ]["ineosq_illegal_client"] = true;
					$options = array(
							'timeout' => ( ( defined('DOING_CRON') && DOING_CRON ) ? 30 : 3),
							'body' => array( 'plugins' => serialize( $to_send ) ),
							'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
						);
					$raw_response = wp_remote_post( 'https://ineosq.com/wp-content/plugins/paid-products/plugins/update-check/1.0/', $options );
					if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) ) {
						$result['error'] = esc_html__( 'Something went wrong. Please try again later. If the error appears again, please contact us', 'ineosq' ) . ' <a href=https://support.ineosq.com>IneosQ</a>. ' . esc_html__( 'We are sorry for inconvenience.', 'ineosq' );
					} else {
						$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );
						if ( is_array( $response ) && !empty( $response ) ) {
							foreach ( $response as $key => $value ) {
								if ( "wrong_license_key" == $value->package ) {
									$result['error'] = esc_html__( 'Wrong license key.', 'ineosq' );
								} else if ( "wrong_domain" == $value->package ) {
									$result['error'] = esc_html__( 'This license key is bound to another site.', 'ineosq' );
								} else if ( "time_out" == $value->package ) {
									$result['message'] = esc_html__( 'This license key is valid, but Your license has expired. If you want to update our plugin in future, you should extend the license.', 'ineosq' );
								} elseif ( "you_are_banned" == $value->package ) {
									$result['error'] = esc_html__( "Unfortunately, you have exceeded the number of available tries.", 'ineosq' );
								} elseif ( "duplicate_domen_for_trial" == $value->package ) {
									$result['error'] = esc_html__( "Unfortunately, the Pro Trial licence was already installed to this domain. The Pro Trial license can be installed only once.", 'ineosq' );
								}
								if ( empty( $result['message'] ) && empty( $result['error'] ) ) {
									if ( isset( $value->trial ) )
										$result['message'] = esc_html__( 'The Pro Trial license key is valid.', 'ineosq' );
									else
										$result['message'] = esc_html__( 'The license key is valid.', 'ineosq' );

									if ( ! empty( $value->time_out ) )
										$result['message'] .= ' ' . esc_html__( 'Your license will expire on', 'ineosq' ) . ' ' . $value->time_out . '.';

									if ( isset( $value->trial ) && $trial_plugin != false )
										$result['message'] .= ' ' . sprintf( esc_html__( 'In order to continue using the plugin it is necessary to buy a %s license.', 'ineosq' ), '<a href="https://ineosq.com/products/wordpress/plugins/' . $trial_plugin['link_slug'] . '/?k=' . $trial_plugin['link_key'] . '&pn=' . $trial_plugin['link_pn'] . '&v=' . $trial_plugin['plugin_info']['Version'] . '&wp_v=' . $wp_version . '" target="_blank" title="' . $trial_plugin['plugin_info']['Name'] . '">Pro</a>' );

									if ( isset( $value->trial ) ) {
										$bstwbsftwppdtplgns_options['trial'][ $plugin_basename ] = 1;
									} else {
										unset( $bstwbsftwppdtplgns_options['trial'][ $plugin_basename ] );
									}
								}
								if ( empty( $result['error'] ) ) {
									if ( isset( $value->nonprofit ) ) {
										$bstwbsftwppdtplgns_options['nonprofit'][ $plugin_basename ] = 1;
									} else {
										unset( $bstwbsftwppdtplgns_options['nonprofit'][ $plugin_basename ] );
									}
									
									if ( $bstwbsftwppdtplgns_options[ $plugin_basename ] != $license_key ) {
										$bstwbsftwppdtplgns_options[ $plugin_basename ] = $license_key;

										$file = @fopen( dirname( dirname( __FILE__ ) ) . "/license_key.txt" , "w+" );
										if ( $file ) {
											@fwrite( $file, $license_key );
											@fclose( $file );
										}
										$update_option = true;
									}

									if ( ! isset( $bstwbsftwppdtplgns_options['time_out'][ $plugin_basename ] ) || $bstwbsftwppdtplgns_options['time_out'][ $plugin_basename ] != $value->time_out ) {
										$bstwbsftwppdtplgns_options['time_out'][ $plugin_basename ] = $value->time_out;
										$update_option = true;
									}

									if ( isset( $update_option ) ) {
										if ( is_multisite() )
											update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
										else
											update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
									}
								}
							}
						} else {
							$result['error'] = esc_html__( 'Something went wrong. Please try again later. If the error appears again, please contact us', 'ineosq' ) . ' <a href=https://support.ineosq.com>IneosQ</a>. ' . esc_html__( 'We are sorry for inconvenience.', 'ineosq' );
						}
					}
				}
			} else {
				$result['error'] = esc_html__( 'Please, enter your license key', 'ineosq' );
			}
		}
		return $result;
	}
}


/**
* Function display block for checking license key for Pro plugins version
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.01.2018. Remove function after 01.01.2019
*/
if ( ! function_exists ( 'ineosq_check_pro_license_form' ) ) {
	function ineosq_check_pro_license_form( $plugin_basename ) {
		global $bstwbsftwppdtplgns_options;
		$license_key = ( isset( $bstwbsftwppdtplgns_options[ $plugin_basename ] ) ) ? $bstwbsftwppdtplgns_options[ $plugin_basename ] : ''; ?>
		<div class="clear"></div>
		<form method="post" action="">
			<p><?php echo esc_html__( 'If necessary, you can check if the license key is correct or reenter it in the field below. You can find your license key on your personal page - Client Area - on our website', 'ineosq' ) . ' <a href="https://ineosq.com/client-area">https://ineosq.com/client-area</a> ' . esc_html__( '(your username is the email address specified during the purchase). If necessary, please submit "Lost your password?" request.', 'ineosq' ); ?></p>
			<p>
				<input type="text" maxlength="100" name="ineosq_license_key" value="<?php echo esc_attr( $license_key ); ?>" />
				<input type="hidden" name="ineosq_license_submit" value="submit" />
				<input type="submit" class="button" value="<?php esc_html_e( 'Check license key', 'ineosq' ) ?>" />
				<?php wp_nonce_field( $plugin_basename, 'ineosq_license_nonce_name' ); ?>
			</p>
		</form>
	<?php }
}

/**
* Function process submit on the `Go Pro` tab for TRIAL
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.01.2018. Remove function after 01.01.2019
*/
if ( ! function_exists( 'ineosq_go_pro_from_trial_tab' ) ) {
	function ineosq_go_pro_from_trial_tab( $plugin_info, $plugin_basename, $page, $link_slug, $link_key, $link_pn, $trial_license_is_set = true ) {
		global $wp_version, $bstwbsftwppdtplgns_options;
		$ineosq_license_key = ( isset( $_POST['ineosq_license_key'] ) ) ? sanitize_text_field( $_POST['ineosq_license_key'] ) : "";
		if ( $trial_license_is_set ) { ?>
			<form method="post" action="">
				<p>
					<?php printf( esc_html__( 'In order to continue using the plugin it is necessary to buy a %s license.', 'ineosq' ), '<a href="https://ineosq.com/products/wordpress/plugins/' . $link_slug . '/?k=' . $link_key . '&amp;pn=' . $link_pn . '&amp;v=' . $plugin_info["Version"] . '&amp;wp_v=' . $wp_version .'" target="_blank" title="' . $plugin_info["Name"] . '">Pro</a>' ); ?> <?php esc_html_e( 'After that, you can activate it by entering your license key.', 'ineosq' ); ?>
					<br />
					<span class="ineosq_info">
						<?php esc_html_e( 'License key can be found in the', 'ineosq' ); ?>
						<a href="https://ineosq.com/wp-login.php">Client Area</a>
						<?php esc_html_e( '(your username is the email address specified during the purchase).', 'ineosq' ); ?>
					</span>
				</p>
				<?php if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $plugin_basename ]['count'] ) &&
					'5' < $bstwbsftwppdtplgns_options['go_pro'][ $plugin_basename ]['count'] &&
					$bstwbsftwppdtplgns_options['go_pro'][ $plugin_basename ]['time'] > ( time() - ( 24 * 60 * 60 ) ) ) { ?>
					<p>
						<input disabled="disabled" type="text" name="ineosq_license_key" value="" />
						<input disabled="disabled" type="submit" class="button-primary" value="<?php esc_html_e( 'Activate', 'ineosq' ); ?>" />
					</p>
					<p><?php esc_html_e( "Unfortunately, you have exceeded the number of available tries per day.", 'ineosq' ); ?></p>
				<?php } else { ?>
					<p>
						<input type="text" maxlength="100" name="ineosq_license_key" value="" />
						<input type="hidden" name="ineosq_license_plugin" value="<?php echo esc_attr( $plugin_basename ); ?>" />
						<input type="hidden" name="ineosq_license_submit" value="submit" />
						<input type="submit" class="button-primary" value="<?php esc_html_e( 'Activate', 'ineosq' ); ?>" />
						<?php wp_nonce_field( $plugin_basename, 'ineosq_license_nonce_name' ); ?>
					</p>
				<?php } ?>
			</form>
		<?php } else { 
			$page_url = esc_url( self_admin_url( 'admin.php?page=' . $page ) ); ?>
			<script type="text/javascript">
				window.setTimeout( function() {
					window.location.href = '<?php echo $page_url; ?>';
				}, 5000 );
			</script>
			<p><?php esc_html_e( "Congratulations! The Pro license of the plugin is activated successfully.", 'ineosq' ); ?></p>
			<p>
				<?php esc_html_e( "Please, go to", 'ineosq' ); ?> <a href="<?php echo esc_url( $page_url ); ?>"><?php esc_html_e( 'the setting page', 'ineosq' ); ?></a>
				(<?php esc_html_e( "You will be redirected automatically in 5 seconds.", 'ineosq' ); ?>)
			</p>
		<?php }
	}
}

/**
* Function process submit on the `Go Pro` tab
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.01.2018. Remove function after 01.01.2019
*/
if ( ! function_exists( 'ineosq_go_pro_tab_check' ) ) {
	function ineosq_go_pro_tab_check( $plugin_basename, $plugin_options_name = false, $is_network_option = false ) {
		global $wp_version, $bstwbsftwppdtplgns_options;
		$result = array();

		$ineosq_license_key = ( isset( $_POST['ineosq_license_key'] ) ) ? sanitize_text_field( $_POST['ineosq_license_key'] ) : "";

		if ( ! empty( $plugin_options_name ) && isset( $_POST['ineosq_hide_premium_options_submit'] ) && check_admin_referer( $plugin_basename, 'ineosq_license_nonce_name' ) ) {

			$plugin_options = ( $is_network_option ) ? get_site_option( $plugin_options_name ) : get_option( $plugin_options_name );

			if ( !empty( $plugin_options['hide_premium_options'] ) ) {

				$key = array_search( get_current_user_id(), $plugin_options['hide_premium_options'] );
				if ( false !== $key ) {
					unset( $plugin_options['hide_premium_options'][ $key ] );
				}

				if ( $is_network_option ) {
					update_site_option( $plugin_options_name, $plugin_options );
				} else {
					update_option( $plugin_options_name, $plugin_options );
				}

				$result['message'] = esc_html__( 'Check premium options on the plugin settings page!', 'ineosq' );
			}
		}

		if ( isset( $_POST['ineosq_license_submit'] ) && check_admin_referer( $plugin_basename, 'ineosq_license_nonce_name' ) ) {
			if ( '' != $ineosq_license_key ) {
				if ( strlen( $ineosq_license_key ) != 18 ) {
					$result['error'] = esc_html__( "Wrong license key", 'ineosq' );
				} else {
					$ineosq_license_plugin = sanitize_text_field( $_POST['ineosq_license_plugin'] );
					if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] ) && $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['time'] > ( time() - (24 * 60 * 60) ) ) {
						$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] = $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] + 1;
					} else {
						$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] = 1;
						$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['time'] = time();
					}

					/* download Pro */
					if ( ! function_exists( 'get_plugins' ) )
						require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

					$all_plugins = get_plugins();

					if ( ! array_key_exists( $ineosq_license_plugin, $all_plugins ) ) {
						$current = get_site_transient( 'update_plugins' );
						if ( is_array( $all_plugins ) && !empty( $all_plugins ) && isset( $current ) && is_array( $current->response ) ) {
							$to_send = array();
							$to_send["plugins"][ $ineosq_license_plugin ] = array();
							$to_send["plugins"][ $ineosq_license_plugin ]["ineosq_license_key"] = $ineosq_license_key;
							$to_send["plugins"][ $ineosq_license_plugin ]["ineosq_illegal_client"] = true;
							$options = array(
								'timeout' => ( ( defined( 'DOING_CRON' ) && DOING_CRON ) ? 30 : 3 ),
								'body' => array( 'plugins' => serialize( $to_send ) ),
								'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );
							$raw_response = wp_remote_post( 'https://ineosq.com/wp-content/plugins/paid-products/plugins/update-check/1.0/', $options );

							if ( is_wp_error( $raw_response ) || 200 != wp_remote_retrieve_response_code( $raw_response ) ) {
								$result['error'] = esc_html__( "Something went wrong. Please try again later. If the error appears again, please contact us", 'ineosq' ) . ' <a href="https://support.ineosq.com">IneosQ</a>. ' . esc_html__( "We are sorry for inconvenience.", 'ineosq' );
							} else {
								$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );
								if ( is_array( $response ) && !empty( $response ) ) {
									foreach ( $response as $key => $value ) {
										if ( "wrong_license_key" == $value->package ) {
											$result['error'] = esc_html__( "Wrong license key.", 'ineosq' );
										} elseif ( "wrong_domain" == $value->package ) {
											$result['error'] = esc_html__( "This license key is bound to another site.", 'ineosq' );
										} elseif ( "you_are_banned" == $value->package ) {
											$result['error'] = esc_html__( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'ineosq' );
										} elseif ( "time_out" == $value->package ) {
											$result['error'] = sprintf( esc_html__( "Unfortunately, Your license has expired. To continue getting top-priority support and plugin updates, you should extend it in your %s", 'ineosq' ), ' <a href="https://ineosq.com/client-area">Client Area</a>' );
										} elseif ( "duplicate_domen_for_trial" == $value->package ) {
											$result['error'] = esc_html__( "Unfortunately, the Pro licence was already installed to this domain. The Pro Trial license can be installed only once.", 'ineosq' );
										}
									}
									if ( empty( $result['error'] ) ) {
										$bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] = $ineosq_license_key;

										$url = 'https://ineosq.com/wp-content/plugins/paid-products/plugins/downloads/?ineosq_first_download=' . $ineosq_license_plugin . '&ineosq_license_key=' . $ineosq_license_key . '&download_from=5';
										$uploadDir = wp_upload_dir();
										$zip_name = explode( '/', $ineosq_license_plugin );

										if ( !function_exists( 'curl_init' ) ) {
											$received_content = file_get_contents( $url );
										} else {
											$ch = curl_init();
											curl_setopt( $ch, CURLOPT_URL, $url );
											curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
											$received_content = curl_exec( $ch );
											curl_close( $ch );
										}

										if ( ! $received_content ) {
											$result['error'] = esc_html__( "Failed to download the zip archive. Please, upload the plugin manually", 'ineosq' );
										} else {
											if ( is_writable( $uploadDir["path"] ) ) {
												$file_put_contents = $uploadDir["path"] . "/" . $zip_name[0] . ".zip";
												if ( file_put_contents( $file_put_contents, $received_content ) ) {
													@chmod( $file_put_contents, octdec( 755 ) );
													if ( class_exists( 'ZipArchive' ) ) {
														$zip = new ZipArchive();
														if ( $zip->open( $file_put_contents ) === TRUE ) {
															$zip->extractTo( WP_PLUGIN_DIR );
															$zip->close();
														} else {
															$result['error'] = esc_html__( "Failed to open the zip archive. Please, upload the plugin manually", 'ineosq' );
														}
													} elseif ( class_exists( 'Phar' ) ) {
														$phar = new PharData( $file_put_contents );
														$phar->extractTo( WP_PLUGIN_DIR );
													} else {
														$result['error'] = esc_html__( "Your server does not support either ZipArchive or Phar. Please, upload the plugin manually", 'ineosq' );
													}
													@unlink( $file_put_contents );
												} else {
													$result['error'] = esc_html__( "Failed to download the zip archive. Please, upload the plugin manually", 'ineosq' );
												}
											} else {
												$result['error'] = esc_html__( "UploadDir is not writable. Please, upload the plugin manually", 'ineosq' );
											}
										}

										/* activate Pro */
										if ( file_exists( WP_PLUGIN_DIR . '/' . $zip_name[0] ) ) {
											if ( is_multisite() && is_plugin_active_for_network( $plugin_basename ) ) {
												/* if multisite and free plugin is network activated */
												$active_plugins = get_site_option( 'active_sitewide_plugins' );
												$active_plugins[ $ineosq_license_plugin ] = time();
												update_site_option( 'active_sitewide_plugins', $active_plugins );
											} else {
												/* activate on a single blog */
												$active_plugins = get_option( 'active_plugins' );
												array_push( $active_plugins, $ineosq_license_plugin );
												update_option( 'active_plugins', $active_plugins );
											}
											$result['pro_plugin_is_activated'] = true;
										} elseif ( empty( $result['error'] ) ) {
											$result['error'] = esc_html__( "Failed to download the zip archive. Please, upload the plugin manually", 'ineosq' );
										}
									}
								} else {
									$result['error'] = esc_html__( "Something went wrong. Try again later or upload the plugin manually. We are sorry for inconvenience.", 'ineosq' );
								}
							}
						}
					} else {
						$bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] = $ineosq_license_key;
						/* activate Pro */
						if ( ! is_plugin_active( $ineosq_license_plugin ) ) {
							if ( is_multisite() && is_plugin_active_for_network( $plugin_basename ) ) {
								/* if multisite and free plugin is network activated */
								$network_wide = true;
							} else {
								/* activate on a single blog */
								$network_wide = false;
							}
							activate_plugin( $ineosq_license_plugin, NULL, $network_wide );
							$result['pro_plugin_is_activated'] = true;
						}
					}
					if ( is_multisite() )
						update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					else
						update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );

					if ( ! empty( $result['pro_plugin_is_activated'] ) )
						delete_transient( 'ineosq_plugins_update' );
				}
			} else {
				$result['error'] = esc_html__( "Please, enter Your license key", 'ineosq' );
			}
		}
		return $result;
	}
}

/**
* Function display block for restoring default product settings
* 
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.01.2018. Remove function after 01.01.2019
*/
if ( ! function_exists ( 'ineosq_form_restore_default_settings' ) ) {
	function ineosq_form_restore_default_settings( $plugin_basename, $change_permission_attr = '' ) { ?>
		<form method="post" action="">
			<p><?php esc_html_e( 'Restore all plugin settings to defaults', 'ineosq' ); ?></p>
			<p>
				<input <?php echo esc_html( $change_permission_attr ); ?> type="submit" class="button" value="<?php esc_html_e( 'Restore settings', 'ineosq' ); ?>" />
			</p>
			<input type="hidden" name="ineosq_restore_default" value="submit" />
			<?php wp_nonce_field( $plugin_basename, 'ineosq_settings_nonce_name' ); ?>
		</form>
	<?php }
}

/**
* Function display 'Custom code' tab
*
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.01.2018. Remove function after 01.01.2019
*/
if ( ! function_exists( 'ineosq_custom_code_tab' ) ) {
	function ineosq_custom_code_tab() {
		if ( ! current_user_can( 'edit_plugins' ) )
			wp_die( esc_html__( 'You do not have sufficient permissions to edit plugins for this site.', 'ineosq' ) );

		global $bstwbsftwppdtplgns_options;

		$message = $content = '';
		$is_css_active = $is_php_active = false;

		$upload_dir = wp_upload_dir();
		$folder = $upload_dir['basedir'] . '/ineosq-custom-code';
		if ( ! $upload_dir["error"] ) {
			if ( ! is_dir( $folder ) ) {
				wp_mkdir_p( $folder, 0755 );
			}

			$index_file = $upload_dir['basedir'] . '/ineosq-custom-code/index.php';
			if ( ! file_exists( $index_file ) ) {
				if ( $f = fopen( $index_file, 'w+' ) ) {
					fclose( $f );
				}
			}
		}

		$css_file = 'ineosq-custom-code.css';
		$real_css_file = $folder . '/' . $css_file;

		$php_file = 'ineosq-custom-code.php';
		$real_php_file = $folder . '/' . $php_file;

		$is_multisite = is_multisite();
		if ( $is_multisite ) {
			$blog_id = get_current_blog_id();
		}

		if ( isset( $_REQUEST['ineosq_update_custom_code'] ) && check_admin_referer( 'ineosq_update_' . $css_file ) ) {

			/* CSS */
			$newcontent_css = wp_kses( trim( wp_unslash( $_POST['ineosq_newcontent_css'] ) ), 'strip' );

			if ( ! empty( $newcontent_css ) && isset( $_REQUEST['ineosq_custom_css_active'] ) ) {
				if ( $is_multisite ) {
					$bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $css_file ] = $upload_dir['baseurl'] . '/ineosq-custom-code/' . $css_file;
				} else {
					$bstwbsftwppdtplgns_options['custom_code'][ $css_file ] = $upload_dir['baseurl'] . '/ineosq-custom-code/' . $css_file;
				}
			} else {
				if ( $is_multisite ) {
					if ( isset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $css_file ] ) ) {
						unset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $css_file ] );
					}
				} else {
					if ( isset( $bstwbsftwppdtplgns_options['custom_code'][ $css_file ] ) ) {
						unset( $bstwbsftwppdtplgns_options['custom_code'][ $css_file ] );
					}
				}
			}
			if ( $f = fopen( $real_css_file, 'w+' ) ) {
				fwrite( $f, $newcontent_css );
				fclose( $f );
				$message .= sprintf( esc_html__( 'File %s edited successfully.', 'ineosq' ), '<i>' . $css_file . '</i>' ) . ' ';
			} else {
				$error .= esc_html__( 'Not enough permissions to create or update the file', 'ineosq' ) . ' ' . $real_css_file . '. ';
			}

			/* PHP */
			$newcontent_php = trim( wp_unslash( $_POST['ineosq_newcontent_php'] ) );
			if ( file_exists( $index_file ) ) {
				if ( ! empty( $newcontent_php ) && isset( $_REQUEST['ineosq_custom_php_active'] ) ) {
					if ( $is_multisite ) {
						$bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $php_file ] = $real_php_file;
					} else {
						$bstwbsftwppdtplgns_options['custom_code'][ $php_file ] = $real_php_file;
					}
				} else {
					if ( $is_multisite ) {
						if ( isset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $php_file ] ) ) {
							unset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $php_file ] );
						}
					} else {
						if ( isset( $bstwbsftwppdtplgns_options['custom_code'][ $php_file ] ) ) {
							unset( $bstwbsftwppdtplgns_options['custom_code'][ $php_file ] );
						}
					}
				}

				if ( $f = fopen( $real_php_file, 'w+' ) ) {
					$newcontent_php = $newcontent_php;
					fwrite( $f, $newcontent_php );
					fclose( $f );
					$message .= sprintf( esc_html__( 'File %s edited successfully.', 'ineosq' ), '<i>' . $php_file . '</i>' );
				} else {
					$error .= esc_html__( 'Not enough permissions to create or update the file', 'ineosq' ) . ' ' . $real_php_file . '. ';
				}
			} else {
				$error .= esc_html__( 'Not enough permissions to create the file', 'ineosq' ) . ' ' . $index_file . '. ';
			}

			if ( ! empty( $error ) ) {
				$error .= ' <a href="https://codex.wordpress.org/Changing_File_Permissions" target="_blank">' . esc_html__( 'Learn more', 'ineosq' ) . '</a>';
			}

			if ( $is_multisite ) {
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			} else {
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			}
		}

		if ( file_exists( $real_css_file ) ) {
			update_recently_edited( $real_css_file );
			$content_css = file_get_contents( $real_css_file );
			if ( ( $is_multisite && isset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $css_file ] ) ) ||
				( ! $is_multisite && isset( $bstwbsftwppdtplgns_options['custom_code'][ $css_file ] ) ) ) {
				$is_css_active = true;
			}
		}
		if ( file_exists( $real_php_file ) ) {
			update_recently_edited( $real_php_file );
			$content_php = file_get_contents( $real_php_file );
			if ( ( $is_multisite && isset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ][ $php_file ] ) ) ||
				( ! $is_multisite && isset( $bstwbsftwppdtplgns_options['custom_code'][ $php_file ] ) ) ) {
				$is_php_active = true;
			}
		} else {
			$content_php = "<?php" . "\n" . "if ( ! defined( 'ABSPATH' ) ) exit;" . "\n" . "if ( ! defined( 'INEOSQ_GLOBAL' ) ) exit;" . "\n\n" . "/* Start your code here */" . "\n";
		}

		if ( ! empty( $message ) ) { ?>
			<div id="message" class="below-h2 updated notice is-dismissible"><p><?php echo $message; ?></p></div>
		<?php } ?>
		<form action="" method="post">
			<?php foreach ( array( 'css', 'php' ) as $extension ) { ?>
				<p>
					<?php if ( 'css' == $extension )
						esc_html_e( 'These styles will be added to the header on all pages of your site.', 'ineosq' );
					else
						printf( esc_html__( 'This PHP code will be hooked to the %s action and will be printed on front end only.', 'ineosq' ), '<a href="https://codex.wordpress.org/Plugin_API/Action_Reference/init" target="_blank"><code>init</code></a>' ); ?>
				</p>
				<p><big>
					<?php if ( ! file_exists( ${"real_{$extension}_file"} ) || ( is_writeable( ${"real_{$extension}_file"} ) ) ) {
						echo esc_html__( 'Editing', 'ineosq' ) . ' <strong>' . ${"{$extension}_file"} . '</strong>';
					} else {
						echo esc_html__( 'Browsing', 'ineosq' ) . ' <strong>' . ${"{$extension}_file"} . '</strong>';
					} ?>
				</big></p>
				<p><label><input type="checkbox" name="ineosq_custom_<?php echo $extension; ?>_active" value="1" <?php if ( ${"is_{$extension}_active"} ) echo "checked"; ?> />	<?php esc_html_e( 'Activate', 'ineosq' ); ?></label></p>
				<textarea cols="70" rows="25" name="ineosq_newcontent_<?php echo $extension; ?>" id="ineosq_newcontent_<?php echo $extension; ?>"><?php if ( isset( ${"content_{$extension}"} ) ) echo esc_textarea( ${"content_{$extension}"} ); ?></textarea>
				<p class="description">
					<a href="<?php echo ( 'css' == $extension ) ? 'https://developer.mozilla.org/en-US/docs/Web/Guide/CSS/Getting_started' : 'https://php.net/' ?>" target="_blank">
						<?php printf( esc_html__( 'Learn more about %s', 'ineosq' ), strtoupper( $extension ) ); ?>
					</a>
				</p>
			<?php }
			if ( ( ! file_exists( $real_css_file ) || is_writeable( $real_css_file ) ) && ( ! file_exists( $real_php_file ) || is_writeable( $real_php_file ) ) ) { ?>
				<p class="submit">
					<input type="hidden" name="ineosq_update_custom_code" value="submit" />
					<?php submit_button( esc_html__( 'Save Changes', 'ineosq' ), 'primary', 'submit', false );
					wp_nonce_field( 'ineosq_update_' . $css_file ); ?>
				</p>
			<?php } else { ?>
				<p><em><?php printf( esc_html__( 'You need to make this files writable before you can save your changes. See %s the Codex %s for more information.', 'ineosq' ),
				'<a href="https://codex.wordpress.org/Changing_File_Permissions" target="_blank">',
				'</a>' ); ?></em></p>
			<?php }	?>
		</form>
	<?php }
}

/**
* Function display GO PRO tab
* @deprecated 1.9.8 (15.12.2016)
* @todo add notice and remove functional after 01.12.2020. Remove function after 01.12.2021
*/
if ( ! function_exists( 'ineosq_go_pro_tab_show' ) ) {
	function ineosq_go_pro_tab_show( $ineosq_hide_premium_options_check, $plugin_info, $plugin_basename, $page, $pro_page, $ineosq_license_plugin, $link_slug, $link_key, $link_pn, $pro_plugin_is_activated = false, $trial_days_number = false ) {
		global $wp_version, $bstwbsftwppdtplgns_options;
		$ineosq_license_key = ( isset( $_POST['ineosq_license_key'] ) ) ? sanitize_text_field( $_POST['ineosq_license_key'] ) : "";
		if ( $pro_plugin_is_activated ) { 
			$page_url = esc_url( self_admin_url( 'admin.php?page=' . $pro_page ) ); ?>
			<script type="text/javascript">
				window.setTimeout( function() {
					window.location.href = '<?php echo $page_url; ?>';
				}, 5000 );
			</script>
			<p><?php esc_html_e( "Congratulations! Pro version of the plugin is  installed and activated successfully.", 'ineosq' ); ?></p>
			<p>
				<?php esc_html_e( "Please, go to", 'ineosq' ); ?> <a href="<?php echo $page_url; ?>"><?php esc_html_e( 'the setting page', 'ineosq' ); ?></a>
				(<?php esc_html_e( "You will be redirected automatically in 5 seconds.", 'ineosq' ); ?>)
			</p>
		<?php } else {
			if ( $ineosq_hide_premium_options_check ) { ?>
				<form method="post" action="">
					<p>
						<input type="hidden" name="ineosq_hide_premium_options_submit" value="submit" />
						<input type="submit" class="button" value="<?php esc_html_e( 'Show Pro features', 'ineosq' ); ?>" />
						<?php wp_nonce_field( $plugin_basename, 'ineosq_license_nonce_name' ); ?>
					</p>
				</form>
			<?php } ?>
			<form method="post" action="">
				<p>
					<?php esc_html_e( 'Enter your license key to install and activate', 'ineosq' ); ?>
					<a href="<?php echo esc_url( 'https://ineosq.com/products/wordpress/plugins/' . $link_slug . '/?k=' . $link_key . '&pn=' . $link_pn . '&v=' . $plugin_info["Version"] . '&wp_v=' . $wp_version ); ?>" target="_blank" title="<?php echo $plugin_info["Name"]; ?> Pro">Pro</a>
					<?php esc_html_e( 'version of the plugin.', 'ineosq' ); ?><br />
					<span class="ineosq_info">
						<?php esc_html_e( 'License key can be found in the', 'ineosq' ); ?>
						<a href="https://ineosq.com/wp-login.php">Client Area</a>
						<?php esc_html_e( '(your username is the email address specified during the purchase).', 'ineosq' ); ?>
					</span>
				</p>
				<?php if ( $trial_days_number !== false ) {
					$trial_days_number = esc_html__( 'or', 'ineosq' ) . ' <a href="https://ineosq.com/products/wordpress/plugins/' . $link_slug . '/trial/" target="_blank">' . sprintf( esc_html__( 'Start Your Free %s-Day Trial Now', 'ineosq' ), $trial_days_number ) . '</a>';
				}
				if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] ) &&
					'5' < $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] &&
					$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['time'] > ( time() - ( 24 * 60 * 60 ) ) ) { ?>
					<p>
						<input disabled="disabled" type="text" name="ineosq_license_key" value="<?php echo esc_attr( $ineosq_license_key ); ?>" />
						<input disabled="disabled" type="submit" class="button-primary" value="<?php esc_html_e( 'Activate', 'ineosq' ); ?>" />
						<?php if ( $trial_days_number !== false ) {
							echo esc_html( $trial_days_number );
						} ?>
					</p>
					<p><?php esc_html_e( "Unfortunately, you have exceeded the number of available tries per day. Please, upload the plugin manually.", 'ineosq' ); ?></p>
				<?php } else { ?>
					<p>
						<input type="text" maxlength="100" name="ineosq_license_key" value="<?php echo esc_attr( $ineosq_license_key ); ?>" />
						<input type="hidden" name="ineosq_license_plugin" value="<?php echo esc_attr( $ineosq_license_plugin ); ?>" />
						<input type="hidden" name="ineosq_license_submit" value="submit" />
						<input type="submit" class="button-primary" value="<?php esc_html_e( 'Activate', 'ineosq' ); ?>" />
						<?php if ( $trial_days_number !== false )
							echo esc_html( $trial_days_number );
						wp_nonce_field( $plugin_basename, 'ineosq_license_nonce_name' ); ?>
					</p>
				<?php } ?>
			</form>
		<?php }
	}
}

/**
* Function display GO PRO Banner (inline in 'admin_notices' action )
* 
* @deprecated 2.2.5 (29.11.2019)
* @todo add notice and remove functional after 01.12.2020. Remove function after 01.12.2021
*/
if ( ! function_exists( 'ineosq_plugin_banner' ) ) {
	function ineosq_plugin_banner( $plugin_info, $this_banner_prefix, $link_slug, $link_key, $link_pn, $banner_url_or_slug ) {
		global $wp_version, $bstwbsftwppdtplgns_cookie_add, $bstwbsftwppdtplgns_banner_array;

		if ( empty( $bstwbsftwppdtplgns_banner_array ) ) {
			if ( ! function_exists( 'ineosq_get_banner_array' ) ) {
				require_once dirname(__FILE__) . '/ineosq_menu.php';
			}
			ineosq_get_banner_array();
		}

		if ( false == strrpos( $banner_url_or_slug, '/' ) ) {
			$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-128x128.png';
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$all_plugins = get_plugins();

		$this_banner = $this_banner_prefix . '_hide_banner_on_plugin_page';
		foreach ( $bstwbsftwppdtplgns_banner_array as $key => $value ) {
			if ( $this_banner == $value[0] ) {
				if ( ! isset( $bstwbsftwppdtplgns_cookie_add ) ) {
					echo '<script type="text/javascript" src="' . ineosq_menu_url( 'js/c_o_o_k_i_e.js' ) . '"></script>';
					$bstwbsftwppdtplgns_cookie_add = true;
				} ?>
				<script type="text/javascript">
					(function($) {
                        "use strict"
						$(document).ready( function() {
							var hide_message = $.cookie( '<?php echo $this_banner_prefix; ?>_hide_banner_on_plugin_page' );
							if ( hide_message == "true" ) {
								$( ".<?php echo $this_banner_prefix; ?>_message" ).css( "display", "none" );
							} else {
								$( ".<?php echo $this_banner_prefix; ?>_message" ).css( "display", "block" );
							};
							$( ".<?php echo $this_banner_prefix; ?>_close_icon" ).click( function() {
								$( ".<?php echo $this_banner_prefix; ?>_message" ).css( "display", "none" );
								$.cookie( "<?php echo $this_banner_prefix; ?>_hide_banner_on_plugin_page", "true", { expires: 32 } );
							});
						});
					})(jQuery);
				</script>
				<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
					<div class="<?php echo $this_banner_prefix; ?>_message ineosq_banner_on_plugin_page ineosq_go_pro_banner" style="display: none;">
						<button class="<?php echo $this_banner_prefix; ?>_close_icon close_icon notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
						<div class="icon">
							<img title="" src="<?php echo esc_attr( $banner_url_or_slug ); ?>" alt="" />
						</div>
						<div class="text">
							<?php esc_html_e( 'It’s time to upgrade your', 'ineosq' ); ?> <strong><?php echo $plugin_info['Name']; ?> plugin</strong> <?php esc_html_e( 'to', 'ineosq' ); ?> <strong>Pro</strong> <?php esc_html_e( 'version!', 'ineosq' ); ?><br />
							<span><?php esc_html_e( 'Extend standard plugin functionality with new great options.', 'ineosq' ); ?></span>
						</div>
						<div class="button_div">
							<a class="button" target="_blank" href="<?php echo esc_url( 'https://ineosq.com/products/wordpress/plugins/' . $link_slug . '/?k=' . $link_key . '&pn=' . $link_pn . '&v=' . $plugin_info["Version"] . '&wp_v=' . $wp_version ); ?>"><?php esc_html_e( 'Learn More', 'ineosq' ); ?></a>
						</div>
					</div>
				</div>
				<?php break;
			}
			if ( isset( $all_plugins[ $value[1] ] ) && $all_plugins[ $value[1] ]["Version"] >= $value[2] && is_plugin_active( $value[1] ) && ! isset( $_COOKIE[ $value[0] ] ) ) {
				break;
			}
		}
	}
}

/**
* Function display timeout PRO Banner (inline in 'admin_notices' action )
* 
* @deprecated 2.2.5 (29.11.2019)
* @todo Remove notice after 01.12.2021
*/
if ( ! function_exists( 'ineosq_plugin_banner_timeout' ) ) {
	function ineosq_plugin_banner_timeout( $plugin_key, $plugin_prefix, $plugin_name, $banner_url_or_slug = false ) {
		global $bstwbsftwppdtplgns_options, $bstwbsftwppdtplgns_cookie_add;
		if ( isset( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) && ( strtotime( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) < strtotime( date("m/d/Y") . '+1 month' ) ) && ( strtotime( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) > strtotime( date("m/d/Y") ) ) ) {

			if ( $banner_url_or_slug && false == strrpos( $banner_url_or_slug, '/' ) ) {
				$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-128x128.png';
			}

			if ( ! isset( $bstwbsftwppdtplgns_cookie_add ) ) {
				echo '<script type="text/javascript" src="' . ineosq_menu_url( 'js/c_o_o_k_i_e.js' ) . '"></script>';
				$bstwbsftwppdtplgns_cookie_add = true;
			} ?>
			<script type="text/javascript">
				(function($) {
                    "use strict"
					$(document).ready( function() {
						var hide_message = $.cookie( "<?php echo $plugin_prefix; ?>_timeout_hide_banner_on_plugin_page" );
						if ( hide_message == "true" ) {
							$( ".<?php echo $plugin_prefix; ?>_message_timeout" ).css( "display", "none" );
						} else {
							$( ".<?php echo $plugin_prefix; ?>_message_timeout" ).css( "display", "block" );
						}
						$( ".<?php echo $plugin_prefix; ?>_close_icon" ).click( function() {
							$( ".<?php echo $plugin_prefix; ?>_message_timeout" ).css( "display", "none" );
							$.cookie( "<?php echo $plugin_prefix; ?>_timeout_hide_banner_on_plugin_page", "true", { expires: 30 } );
						});
					});
				})(jQuery);
			</script>
			<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
				<div class="<?php echo $plugin_prefix; ?>_message_timeout ineosq_banner_on_plugin_page ineosq_banner_timeout" style="display:none;">
					<button class="<?php echo $plugin_prefix; ?>_close_icon close_icon notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
					<div class="icon">
						<img title="" src="<?php echo esc_attr( $banner_url_or_slug ); ?>" alt="" />
					</div>
					<div class="text"><?php printf( esc_html__( "Your license key for %s expires on %s and you won't be granted TOP-PRIORITY SUPPORT or UPDATES.", 'ineosq' ), '<strong>' . $plugin_name . '</strong>', $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ); ?> <a target="_new" href="https://support.ineosq.com/entries/53487136"><?php esc_html_e( "Learn more", 'ineosq' ); ?></a></div>
				</div>
			</div>
		<?php }
	}
}