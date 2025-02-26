<?php
/**
 * Function for displaying IneosQ menu
 * Version: 2.4.2-pro
 */

if ( ! function_exists( 'ineosq_admin_enqueue_scripts' ) ) {
	require_once dirname(__FILE__) . '/ineosq_functions.php';
}

if ( ! function_exists( 'ineosq_add_menu_render' ) ) {
	function ineosq_add_menu_render() {
		global $wpdb, $wp_version, $bstwbsftwppdtplgns_options;
		$error = $message = '';

		/**
		 * @deprecated 1.9.8 (15.12.2016)
		 */
		$is_main_page = isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'ineosq_panel', 'ineosq_themes', 'ineosq_system_status' ) );
		$page         = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

		if ( $is_main_page ) {
			$current_page = 'admin.php?page=' . $page;
		} else {
			$current_page = isset( $_GET['tab'] ) ? 'admin.php?page=' . $page . '&tab=' . $tab : 'admin.php?page=' . $page;
		}

		if ( 'ineosq_panel' === $page || ( ! $is_main_page && '' === $tab ) ) {

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			/* Get $ineosq_plugins */
			require dirname( __FILE__ ) . '/product_list.php';

			$all_plugins             = get_plugins();
			$active_plugins          = get_option( 'active_plugins' );
			$sitewide_active_plugins = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'active_sitewide_plugins' ) : array();
			$update_availible_all    = get_site_transient( 'update_plugins' );

			$plugin_category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : 'all';

			if ( ( isset( $_GET['sub'] ) && 'installed' === sanitize_text_field( wp_unslash( $_GET['sub'] ) ) ) || ! isset( $_GET['sub'] ) ) {
				$ineosq_plugins_update_availible = $ineosq_plugins_expired = array();
				foreach ( $ineosq_plugins as $key_plugin => $value_plugin ) {

					foreach ( $value_plugin['category'] as $category_key ) {
						$ineosq_plugins_category[ $category_key ]['count'] = isset( $ineosq_plugins_category[ $category_key ]['count'] ) ? $ineosq_plugins_category[ $category_key ]['count'] + 1 : 1;
					}

					$is_installed     = array_key_exists( $key_plugin, $all_plugins );
					$is_pro_installed = false;
					if ( isset( $value_plugin['pro_version'] ) ) {
						$is_pro_installed = array_key_exists( $value_plugin['pro_version'], $all_plugins );
					}
					/* Check update_availible */
					if ( ! empty( $update_availible_all ) && ! empty( $update_availible_all->response ) ) {
						if ( $is_pro_installed && array_key_exists( $value_plugin['pro_version'], $update_availible_all->response ) ) {
							unset( $ineosq_plugins[ $key_plugin ] );
							$value_plugin['update_availible']            = $value_plugin['pro_version'];
							$ineosq_plugins_update_availible[ $key_plugin ] = $value_plugin;
						} elseif ( $is_installed && array_key_exists( $key_plugin, $update_availible_all->response ) ) {
							unset( $ineosq_plugins[ $key_plugin ] );
							$value_plugin['update_availible']            = $key_plugin;
							$ineosq_plugins_update_availible[ $key_plugin ] = $value_plugin;
						}
					}
					/* Check expired */
					if ( $is_pro_installed && isset( $bstwbsftwppdtplgns_options['time_out'][ $value_plugin['pro_version'] ] ) &&
						 strtotime( $bstwbsftwppdtplgns_options['time_out'][ $value_plugin['pro_version'] ] ) < strtotime( gmdate( 'm/d/Y' ) ) ) {
						unset( $ineosq_plugins[ $key_plugin ] );
						$value_plugin['expired']            = $bstwbsftwppdtplgns_options['time_out'][ $value_plugin['pro_version'] ];
						$ineosq_plugins_expired[ $key_plugin ] = $value_plugin;
					}
				}
				$ineosq_plugins = $ineosq_plugins_update_availible + $ineosq_plugins_expired + $ineosq_plugins;
			} else {
				foreach ( $ineosq_plugins as $key_plugin => $value_plugin ) {
					foreach ( $value_plugin['category'] as $category_key ) {
						$ineosq_plugins_category[ $category_key ]['count'] = isset( $ineosq_plugins_category[ $category_key ]['count'] ) ? $ineosq_plugins_category[ $category_key ]['count'] + 1 : 1;
					}
				}
			}

			/*** Membership */
			$ineosq_license_plugin     = 'ineosq_get_list_for_membership';
			$ineosq_license_key        = isset( $bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] ) ? $bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] : '';
			$update_membership_list = true;

			if ( isset( $_POST['ineosq_license_key'] ) ) {
				$ineosq_license_key = sanitize_text_field( wp_unslash( $_POST['ineosq_license_key'] ) );
			}

			if ( isset( $_SESSION['ineosq_membership_time_check'] ) && isset( $_SESSION['ineosq_membership_list'] ) && $_SESSION['ineosq_membership_time_check'] < strtotime( '+12 hours' ) ) {
				$update_membership_list = false;
				$plugins_array          = $_SESSION['ineosq_membership_list'];
			}

			if ( ( $update_membership_list && ! empty( $ineosq_license_key ) ) || ( isset( $_POST['ineosq_license_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ineosq_license_nonce_name' ) ) ) {

				if ( '' !== $ineosq_license_key ) {
					if ( 18 !== strlen( $ineosq_license_key ) ) {
						$error = __( 'Wrong license key', 'ineosq' );
					} else {

						if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] ) && $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['time'] > ( time() - ( 24 * 60 * 60 ) ) ) {
							$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] = $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] + 1;
						} else {
							$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] = 1;
							$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['time']  = time();
						}

						/* get Pro list */
						$to_send                                   = array();
						$to_send['plugins'][ $ineosq_license_plugin ] = array();
						$to_send['plugins'][ $ineosq_license_plugin ]['ineosq_license_key'] = $ineosq_license_key;
						$options      = array(
							'timeout'    => ( ( defined( 'DOING_CRON' ) && DOING_CRON ) ? 30 : 3 ),
							'body'       => array( 'plugins' => serialize( $to_send ) ),
							'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
						);
						$raw_response = wp_remote_post( 'https://ineosq.com/wp-content/plugins/paid-products/plugins/pro-license-check/1.0/', $options );

						if ( is_wp_error( $raw_response ) || 200 !== wp_remote_retrieve_response_code( $raw_response ) ) {
							$error = __( 'Something went wrong. Please try again later. If the error appears again, please contact us', 'ineosq' ) . ' <a href="https://support.ineosq.com">IneosQ</a>. ' . __( 'We are sorry for inconvenience.', 'ineosq' );
						} else {
							$response = maybe_unserialize( wp_remote_retrieve_body( $raw_response ) );

							if ( is_array( $response ) && ! empty( $response ) ) {
								foreach ( $response as $key => $value ) {
									if ( 'wrong_license_key' === $value->package ) {
										$error = __( 'Wrong license key.', 'ineosq' );
									} elseif ( 'wrong_domain' === $value->package ) {
										$error = __( 'This license key is bound to another site. Change it via personal Client Area.', 'ineosq' ) . '<a target="_blank" href="https://ineosq.com/client-area">' . __( 'Log in', 'ineosq' ) . '</a>';
									} elseif ( 'you_are_banned' === $value->package ) {
										$error = __( 'Unfortunately, you have exceeded the number of available tries per day.', 'ineosq' );
									} elseif ( 'time_out' === $value->package ) {
										$error = sprintf( __( 'Unfortunately, Your license has expired. To continue getting top-priority support and plugin updates, you should extend it in your %s', 'ineosq' ), ' <a href="https://ineosq.com/client-area">Client Area</a>' );
									} elseif ( 'duplicate_domen_for_trial' === $value->package ) {
										$error = __( 'Unfortunately, the Pro licence was already installed to this domain. The Pro Trial license can be installed only once.', 'ineosq' );
									} elseif ( is_array( $value->package ) && ! empty( $value->package ) ) {
										$plugins_array                         = $_SESSION['ineosq_membership_list'] = $value->package;
										$_SESSION['ineosq_membership_time_check'] = strtotime( 'now' );

										if ( isset( $bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] ) && $ineosq_license_key === $bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] ) {
											$message = __( 'The license key is valid.', 'ineosq' );
											if ( isset( $value->time_out ) && '' !== $value->time_out ) {
												$message .= ' ' . __( 'Your license will expire on', 'ineosq' ) . ' ' . $value->time_out . '.';
											}
										} else {
											$message = __( 'Congratulations! Pro Membership license is activated successfully.', 'ineosq' );
										}

										$bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] = $ineosq_license_key;
									}
								}
							} else {
								$error = __( 'Something went wrong. Try again later or upload the plugin manually. We are sorry for inconvenience.', 'ineosq' );
							}
						}

						if ( is_multisite() ) {
							update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
						} else {
							update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
						}
					}
				} else {
					$error = __( 'Please enter your license key.', 'ineosq' );
				}
			}
		} elseif ( 'ineosq_system_status' === $page || 'system-status' === $tab ) {

			$all_plugins    = get_plugins();
			$active_plugins = get_option( 'active_plugins' );
			$mysql_info     = $wpdb->get_results( "SHOW VARIABLES LIKE 'sql_mode'" );
			if ( is_array( $mysql_info ) ) {
				$sql_mode = $mysql_info[0]->Value;
			}
			if ( empty( $sql_mode ) ) {
				$sql_mode = __( 'Not set', 'ineosq' );
			}

			$allow_url_fopen     = ( ini_get( 'allow_url_fopen' ) ) ? __( 'On', 'ineosq' ) : __( 'Off', 'ineosq' );
			$upload_max_filesize = ( ini_get( 'upload_max_filesize' ) ) ? ini_get( 'upload_max_filesize' ) : __( 'N/A', 'ineosq' );
			$post_max_size       = ( ini_get( 'post_max_size' ) ) ? ini_get( 'post_max_size' ) : __( 'N/A', 'ineosq' );
			$max_execution_time  = ( ini_get( 'max_execution_time' ) ) ? ini_get( 'max_execution_time' ) : __( 'N/A', 'ineosq' );
			$memory_limit        = ( ini_get( 'memory_limit' ) ) ? ini_get( 'memory_limit' ) : __( 'N/A', 'ineosq' );
			$wp_memory_limit     = ( defined( 'WP_MEMORY_LIMIT' ) ) ? WP_MEMORY_LIMIT : __( 'N/A', 'ineosq' );
			$memory_usage        = ( function_exists( 'memory_get_usage' ) ) ? round( memory_get_usage() / 1024 / 1024, 2 ) . ' ' . __( 'Mb', 'ineosq' ) : __( 'N/A', 'ineosq' );
			$exif_read_data      = ( is_callable( 'exif_read_data' ) ) ? __( 'Yes', 'ineosq' ) . ' ( V' . substr( phpversion( 'exif' ), 0, 4 ) . ')' : __( 'No', 'ineosq' );
			$iptcparse           = ( is_callable( 'iptcparse' ) ) ? __( 'Yes', 'ineosq' ) : __( 'No', 'ineosq' );
			$xml_parser_create   = ( is_callable( 'xml_parser_create' ) ) ? __( 'Yes', 'ineosq' ) : __( 'No', 'ineosq' );
			$theme               = ( function_exists( 'wp_get_theme' ) ) ? wp_get_theme() : get_theme( get_current_theme() );

			if ( function_exists( 'is_multisite' ) ) {
				$multisite = is_multisite() ? __( 'Yes', 'ineosq' ) : __( 'No', 'ineosq' );
			} else {
				$multisite = __( 'N/A', 'ineosq' );
			}

			$system_info = array(
				'wp_environment'     => array(
					'name' => __( 'WordPress Environment', 'ineosq' ),
					'data' => array(
						__( 'Home URL', 'ineosq' )         => home_url(),
						__( 'Website URL', 'ineosq' )      => get_option( 'siteurl' ),
						__( 'WP Version', 'ineosq' )       => $wp_version,
						__( 'WP Multisite', 'ineosq' )     => $multisite,
						__( 'WP Memory Limit', 'ineosq' )  => $wp_memory_limit,
						__( 'Active Theme', 'ineosq' )     => $theme['Name'] . ' ' . $theme['Version'] . ' (' . sprintf( __( 'by %s', 'ineosq' ), $theme['Author'] ) . ')',
					),
				),
				'server_environment' => array(
					'name' => __( 'Server Environment', 'ineosq' ),
					'data' => array(
						__( 'Operating System', 'ineosq' )      => PHP_OS,
						__( 'Server', 'ineosq' )                => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_email( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
						__( 'PHP Version', 'ineosq' )           => PHP_VERSION,
						__( 'PHP Allow URL fopen', 'ineosq' )   => $allow_url_fopen,
						__( 'PHP Memory Limit', 'ineosq' )      => $memory_limit,
						__( 'Memory Usage', 'ineosq' )          => $memory_usage,
						__( 'PHP Max Upload Size', 'ineosq' )   => $upload_max_filesize,
						__( 'PHP Max Post Size', 'ineosq' )     => $post_max_size,
						__( 'PHP Max Script Execute Time', 'ineosq' ) => $max_execution_time,
						__( 'PHP Exif support', 'ineosq' )      => $exif_read_data,
						__( 'PHP IPTC support', 'ineosq' )      => $iptcparse,
						__( 'PHP XML support', 'ineosq' )       => $xml_parser_create,
						'$_SERVER[HTTP_HOST]'                        => isset( $_SERVER['HTTP_HOST'] ) ? sanitize_email( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '',
						'$_SERVER[SERVER_NAME]'                      => isset( $_SERVER['SERVER_NAME'] ) ? sanitize_email( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
					),
				),
				'db'                 => array(
					'name' => __( 'Database', 'ineosq' ),
					'data' => array(
						__( 'WP DB version', 'ineosq' ) => get_option( 'db_version' ),
						__( 'MySQL version', 'ineosq' ) => $wpdb->get_var( 'SELECT VERSION() AS version' ),
						__( 'SQL Mode', 'ineosq' )      => $sql_mode,
					),
				),
				'active_plugins'     => array(
					'name'  => __( 'Active Plugins', 'ineosq' ),
					'data'  => array(),
					'count' => 0,
				),
				'inactive_plugins'   => array(
					'name'  => __( 'Inactive Plugins', 'ineosq' ),
					'data'  => array(),
					'count' => 0,
				),
			);

			foreach ( $all_plugins as $path => $plugin ) {
				$name = str_replace( 'by IneosQ', '', $plugin['Name'] );
				if ( is_plugin_active( $path ) ) {
					$system_info['active_plugins']['data'][ $name ] = sprintf( __( 'by %s', 'ineosq' ), $plugin['Author'] ) . ' - ' . $plugin['Version'];
					$system_info['active_plugins']['count']         = $system_info['active_plugins']['count'] + 1;
				} else {
					$system_info['inactive_plugins']['data'][ $name ] = sprintf( __( 'by %s', 'ineosq' ), $plugin['Author'] ) . ' - ' . $plugin['Version'];
					$system_info['inactive_plugins']['count']         = $system_info['inactive_plugins']['count'] + 1;
				}
			}

			if ( ( isset( $_REQUEST['ineosqmn_form_submit'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ineosqmn_nonce_submit' ) ) || ( isset( $_REQUEST['ineosqmn_form_submit_custom_email'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ineosqmn_nonce_submit_custom_email' ) ) ) {
				if ( isset( $_REQUEST['ineosqmn_form_email'] ) ) {
					$email = sanitize_email( wp_unslash( $_REQUEST['ineosqmn_form_email'] ) );
					if ( '' === $email ) {
						$error = __( 'Please enter a valid email address.', 'ineosq' );
					} else {
						$message = sprintf( __( 'Email with system info is sent to %s.', 'ineosq' ), $email );
					}
				} else {
					$email   = 'plugin_system_status@ineosq.com';
					$message = __( 'Thank you for contacting us.', 'ineosq' );
				}

				if ( '' === $error ) {
					$headers      = 'MIME-Version: 1.0' . "\n";
					$headers     .= 'Content-type: text/html; charset=utf-8' . "\n";
					$headers     .= 'From: ' . get_option( 'admin_email' );
					$message_text = '<html><head><title>System Info From ' . home_url() . '</title></head><body>';
					foreach ( $system_info as $info ) {
						if ( ! empty( $info['data'] ) ) {
							$message_text .= '<h4>' . $info['name'];
							if ( isset( $info['count'] ) ) {
								$message_text .= ' (' . $info['count'] . ')';
							}
							$message_text .= '</h4><table>';
							foreach ( $info['data'] as $key => $value ) {
								$message_text .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
							}
							$message_text .= '</table>';
						}
					}
					$message_text .= '</body></html>';
					$result        = wp_mail( $email, 'System Info From ' . esc_url( home_url() ), wp_kses( $message_text ), $headers );
					if ( true !== $result ) {
						$error = __( 'Sorry, email message could not be delivered.', 'ineosq' );
					}
				}
			}
		} ?>
		<div class="ineosq-wrap">
			<div class="ineosq-header">
				<div class="ineosq-title">
					<a href="<?php echo ( $is_main_page ) ? esc_url( self_admin_url( 'admin.php?page=ineosq_panel' ) ) : esc_url( self_admin_url( 'admin.php?page=' . $page ) ); ?>">
						<span class="ineosq-logo ineosqicons ineosqicons-ineosq-logo"></span>
						IneosQ
						<span>panel</span>
					</a>
				</div>
				<div class="ineosq-menu-item-icon">&#8226;&#8226;&#8226;</div>
				<div class="ineosq-nav-tab-wrapper">
					<?php if ( $is_main_page ) { ?>
						<a class="ineosq-nav-tab <?php
						if ( 'ineosq_panel' === $page ) {
							echo esc_attr( ' ineosq-nav-tab-active' );
						}
						?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=ineosq_panel' ) ); ?>"><?php esc_html_e( 'Plugins', 'ineosq' );
						?>
						</a>
						<!-- pls -->
						<a class="ineosq-nav-tab <?php
						if ( 'ineosq_themes' === $page ) {
							echo esc_attr( ' ineosq-nav-tab-active' );
						}
						?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=ineosq_themes' ) ); ?>"><?php esc_html_e( 'Themes', 'ineosq' ); ?></a>
						<a class="ineosq-nav-tab <?php
						if ( 'ineosq_system_status' === $page ) {
							echo esc_attr( ' ineosq-nav-tab-active' );
						} 
						?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=ineosq_system_status' ) ); ?>"><?php esc_html_e( 'System status', 'ineosq' );
						?>
						</a>
					<?php } else { ?>
						<a class="ineosq-nav-tab <?php
						if ( ! isset( $_GET['tab'] ) ) {
							echo esc_attr( ' ineosq-nav-tab-active' );
						} 
						?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=' . $page ) ); ?>"><?php esc_html_e( 'Plugins', 'ineosq' ); 
						?>
						</a>
						<!-- pls -->
						<a class="ineosq-nav-tab <?php
						if ( 'themes' === $tab ) {
							echo esc_attr( ' ineosq-nav-tab-active' );
						}
						?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=' . $page . '&tab=themes' ) ); ?>"><?php esc_html_e( 'Themes', 'ineosq' );
						?>
						</a>
						 <!-- end pls -->
						<a class="ineosq-nav-tab <?php
						if ( 'system-status' === $tab ) {
							echo esc_attr( ' ineosq-nav-tab-active' );
						}
						?>" href="<?php echo esc_url( self_admin_url( 'admin.php?page=' . $page . '&tab=system-status' ) ); ?>"><?php esc_html_e( 'System status', 'ineosq' );
						?>
						</a>
					<?php } ?>
				</div>
				<!-- pls -->
				<div class="ineosq-help-links-wrapper">
					<a href="https://support.ineosq.com" target="_blank"><?php esc_html_e( 'Support', 'ineosq' ); ?></a>
					<a href="https://ineosq.com/client-area" target="_blank" title="<?php esc_html_e( 'Manage purchased licenses & subscriptions', 'ineosq' ); ?>">Client Area</a>
				</div>
				<!-- end pls -->
				<div class="clear"></div>
			</div>
			<?php if ( ( 'ineosq_panel' === $page || ( ! isset( $_GET['tab'] ) && ! $is_main_page ) ) && ! isset( $_POST['ineosq_plugin_action_submit'] ) ) { ?>
				<div class="ineosq-membership-wrap">
					<div class="ineosq-membership-backround"></div>
					<div class="ineosq-membership">
						<div class="ineosq-membership-title"><?php printf( esc_html__( 'Get Access to %s+ Premium Plugins', 'ineosq' ), '30' ); ?></div>
						<form class="ineosq-membership-form" method="post" action="">
							<span class="ineosq-membership-link"><a target="_blank" href="https://ineosq.com/membership/"><?php esc_html_e( 'Subscribe to Pro Membership', 'ineosq' ); ?></a> <?php esc_html_e( 'or', 'ineosq' ); ?></span>
							<?php
							if ( isset( $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] ) &&
								'5' < $bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['count'] &&
								$bstwbsftwppdtplgns_options['go_pro'][ $ineosq_license_plugin ]['time'] > ( time() - ( 24 * 60 * 60 ) ) ) {
								?>
								<div class="ineosq_form_input_wrap">
									<input disabled="disabled" type="text" name="ineosq_license_key" value="<?php echo esc_attr( $ineosq_license_key ); ?>" />
									<div class="ineosq_error"><?php esc_html_e( 'Unfortunately, you have exceeded the number of available tries per day.', 'ineosq' ); ?></div>
								</div>
								<input disabled="disabled" type="submit" class="ineosq-button" value="<?php esc_html_e( 'Check license key', 'ineosq' ); ?>" />
							<?php } else { ?>
								<div class="ineosq_form_input_wrap">
									<input 
									<?php
									if ( '' !== $error ) {
										echo 'class="ineosq_input_error"';}
									?>
										type="text" placeholder="<?php esc_html_e( 'Enter your license key', 'ineosq' ); ?>" maxlength="100" name="ineosq_license_key" value="<?php echo esc_attr( $ineosq_license_key ); ?>" />
									<div class="ineosq_error" 
									<?php
									if ( '' === $error ) {
										echo 'style="display:none"';}
									?>
									><?php echo esc_html( $error ); ?></div>
								</div>
								<input type="hidden" name="ineosq_license_plugin" value="<?php echo esc_attr( $ineosq_license_plugin ); ?>" />
								<input type="hidden" name="ineosq_license_submit" value="submit" />
								<?php if ( empty( $plugins_array ) ) { ?>
									<input type="submit" class="ineosq-button" value="<?php esc_html_e( 'Activate', 'ineosq' ); ?>" />
								<?php } else { ?>
									<input type="submit" class="ineosq-button" value="<?php esc_html_e( 'Check license key', 'ineosq' ); ?>" />
								<?php } ?>
								<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosq_license_nonce_name' ); ?>
							<?php } ?>
						</form>
						<div class="clear"></div>
					</div>
				</div>
			<?php } ?>
			<!-- end pls -->
			<div class="ineosq-wrap-content wrap">
				<?php if ( 'ineosq_panel' === $page || ( ! isset( $_GET['tab'] ) && ! $is_main_page ) ) { ?>
					<div class="updated notice is-dismissible inline" 
					<?php
					if ( '' === $message || '' !== $error ) {
						echo 'style="display:none"';}
					?>
					><p><?php echo esc_html( $message ); ?></p></div>
					<h1>
						<?php esc_html_e( 'Plugins', 'ineosq' ); ?>
						<a href="<?php echo esc_url( self_admin_url( 'plugin-install.php?tab=upload' ) ); ?>" class="upload page-title-action add-new-h2"><?php esc_html_e( 'Upload Plugin', 'ineosq' ); ?></a>
					</h1>
					<?php
					if ( isset( $_GET['error'] ) ) {
						if ( isset( $_GET['charsout'] ) ) {
							$errmsg = sprintf( __( 'The plugin generated %d characters of <strong>unexpected output</strong> during activation. If you notice &#8220;headers already sent&#8221; messages, problems with syndication feeds or other issues, try deactivating or removing this plugin.' ), intval( $_GET['charsout'] ) );
						} else {
							$errmsg = __( 'Plugin could not be activated because it triggered a <strong>fatal error</strong>.' );
						}
						?>
						<div id="message" class="error is-dismissible"><p><?php echo wp_kses( $errmsg ); ?></p></div>
					<?php } elseif ( isset( $_GET['activate'] ) ) { ?>
						<div id="message" class="updated notice is-dismissible"><p><?php esc_html_e( 'Plugin <strong>activated</strong>.' ); ?></p></div>
						<?php
					}

					if ( isset( $_POST['ineosq_plugin_action_submit'] ) && isset( $_POST['ineosq_install_plugin'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ineosq_license_install_nonce_name' ) ) {

						$ineosq_license_plugin = sanitize_text_field( wp_unslash( $_POST['ineosq_install_plugin'] ) );

						echo '<h2>' . esc_html__( 'Installing Plugin', 'ineosq' ) . ': ' . $plugins_array[ $ineosq_license_plugin ]['name'] . '</h2>';

						$bstwbsftwppdtplgns_options[ $ineosq_license_plugin ] = $ineosq_license_key;

						$url = $plugins_array[ $ineosq_license_plugin ]['link'] . '&download_from=5';

						echo '<p>' . esc_html__( "Downloading install package from", 'ineosq' ) . ' ' . $url . '</p>';

						$uploadDir = wp_upload_dir();
						$zip_name = explode( '/', $ineosq_license_plugin );
						
						$args = array(
							'method' 	  => 'POST',
							'timeout'     => 100
						);
						$received_content = wp_remote_post( $url, $args );

						if ( ! $received_content['body'] ) {
							$error = esc_html__( "Failed to download the zip archive. Please, upload the plugin manually", 'ineosq' );
						} else {
							if ( is_writable( $uploadDir["path"] ) ) {
								$file_put_contents = $uploadDir["path"] . "/" . $zip_name[0] . ".zip";

								if ( file_put_contents( $file_put_contents, $received_content['body'] ) ) {
									@chmod( $file_put_contents, octdec( 755 ) );

									echo '<p>' . esc_html__( 'Unpacking the package', 'ineosq' ) . '...</p>';

									if ( class_exists( 'ZipArchive' ) ) {
										$zip = new ZipArchive();
										if ( $zip->open( $file_put_contents ) === TRUE ) {
											echo '<p>' . esc_html__( 'Installing the plugin', 'ineosq' ) . '...</p>';
											$zip->extractTo( WP_PLUGIN_DIR );
											$zip->close();
										} else {
											$error = esc_html__( "Failed to open the zip archive. Please, upload the plugin manually", 'ineosq' );
										}
									} elseif ( class_exists( 'Phar' ) ) {
										$phar = new PharData( $file_put_contents );
										echo '<p>' . esc_html__( 'Installing the plugin', 'ineosq' ) . '...</p>';
										$phar->extractTo( WP_PLUGIN_DIR );
									} else {
										$error = esc_html__( "Your server does not support either ZipArchive or Phar. Please, upload the plugin manually", 'ineosq' );
									}
									if ( empty( $error ) ) {
										echo '<p>' . sprintf( esc_html__( 'The plugin %s is successfully installed.', 'ineosq' ), '<strong>' . $plugins_array[ $ineosq_license_plugin ]['name'] . '</strong>' ) . '</p>';
									}

									@unlink( $file_put_contents );
								} else {
									$error = esc_html__( "Failed to download the zip archive. Please, upload the plugin manually", 'ineosq' );
								}
							} else {
								$error = esc_html__( "UploadDir is not writable. Please, upload the plugin manually", 'ineosq' );
							}
						}

						if ( file_exists( WP_PLUGIN_DIR . '/' . $zip_name[0] ) ) {
							echo '<p><a href="' . esc_url( wp_nonce_url( self_admin_url( $current_page . '&ineosq_activate_plugin=' . $ineosq_license_plugin ), 'ineosq_activate_plugin' . $ineosq_license_plugin ) ) . '" target="_parent">' . esc_html__( 'Activate Plugin', 'ineosq' ) . '</a> | <a href="' . esc_url( self_admin_url( $current_page ) ) . '" target="_parent">' . esc_html__( 'Return to IneosQ Panel', 'ineosq' ) . '</a></p>';
						} else {
							if ( empty( $error ) )
								$error = esc_html__( "Failed to download the zip archive. Please, upload the plugin manually", 'ineosq' );

							echo '<p class="error">' . $error . '</p>';
							echo '<p><a href="' . esc_url( self_admin_url( $current_page ) ) . '" target="_parent">' . esc_html__( 'Return to IneosQ Panel', 'ineosq' ) . '</a></p>';
						}
					} else {
						$category_href = $current_page;
						if ( 'all' !== $plugin_category ) {
							$category_href .= '&category=' . $plugin_category;
						}
						?>
						<ul class="subsubsub">
							<li>
								<a 
								<?php
								if ( ! isset( $_GET['sub'] ) ) {
									echo 'class="current" ';}
								?>
								href="<?php echo esc_url( self_admin_url( $category_href ) ); ?>"><?php esc_html_e( 'All', 'ineosq' ); ?></a>
							</li> |
							<li>
								<a 
								<?php
								if ( isset( $_GET['sub'] ) && 'installed' === sanitize_text_field( wp_unslash( $_GET['sub'] ) ) ) {
									echo 'class="current" ';}
								?>
								href="<?php echo esc_url( self_admin_url( $category_href . '&sub=installed' ) ); ?>"><?php esc_html_e( 'Installed', 'ineosq' ); ?></a>
							</li> |
							<li>
								<a 
								<?php
								if ( isset( $_GET['sub'] ) && 'not_installed' === sanitize_text_field( wp_unslash( $_GET['sub'] ) ) ) {
									echo 'class="current" ';}
								?>
								href="<?php echo esc_url( self_admin_url( $category_href . '&sub=not_installed' ) ); ?>"><?php esc_html_e( 'Not Installed', 'ineosq' ); ?></a>
							</li>
						</ul>
						<div class="clear"></div>
						<div class="ineosq-filter-top">
							<h2>
								<span class="ineosq-toggle-indicator"></span>
															<?php esc_html_e( 'Filter results', 'ineosq' ); ?>
							</h2>
							<div class="ineosq-filter-top-inside">
								<div class="ineosq-filter-title"><?php esc_html_e( 'Category', 'ineosq' ); ?></div>
								<ul class="ineosq-category">
									<li>
										<?php $sub_in_url = ( isset( $_GET['sub'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['sub'] ) ), array( 'installed', 'not_installed' ) ) ) ? '&sub=' . sanitize_text_field( wp_unslash( $_GET['sub'] ) ) : ''; ?>
										<a 
										<?php
										if ( 'all' === $plugin_category ) {
											echo ' class="ineosq-active"';}
										?>
											href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url ) ); ?>"><?php esc_html_e( 'All', 'ineosq' ); ?>
												<span>(<?php echo count( $ineosq_plugins ); ?>)</span>
										</a>
									</li>
									<?php foreach ( $ineosq_plugins_category as $category_key => $category_value ) { ?>
										<li>
											<a 
											<?php
											if ( $category_key === $plugin_category ) {
												echo ' class="ineosq-active"';}
											?>
												href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url . '&category=' . $category_key ) ); ?>"><?php echo esc_html( $category_value['name'] ); ?>
													<span>(<?php echo intval( $category_value['count'] ); ?>)</span>
											</a>
										</li>
									<?php } ?>
								</ul>
							</div>
						</div>
						<div class="ineosq-products">
							<?php
							$nothing_found = true;
							foreach ( $ineosq_plugins as $key_plugin => $value_plugin ) {

								if ( 'all' !== $plugin_category && isset( $ineosq_plugins_category[ $plugin_category ] ) && ! in_array( $plugin_category, $value_plugin['category'] ) ) {
									continue;
								}

								$key_plugin_explode = explode( '/', $key_plugin );

								$icon         = isset( $value_plugin['icon'] ) ? $value_plugin['icon'] : '//ps.w.org/' . $key_plugin_explode[0] . '/assets/icon-256x256.png';
								$is_pro_isset = isset( $value_plugin['pro_version'] );
								$is_installed = array_key_exists( $key_plugin, $all_plugins );
								$is_active    = in_array( $key_plugin, $active_plugins ) || isset( $sitewide_active_plugins[ $key_plugin ] );

								$is_pro_installed = $is_pro_active = false;
								if ( $is_pro_isset ) {
									$is_pro_installed = array_key_exists( $value_plugin['pro_version'], $all_plugins );
									$is_pro_active    = in_array( $value_plugin['pro_version'], $active_plugins ) || isset( $sitewide_active_plugins[ $value_plugin['pro_version'] ] );
								}

								if ( ( isset( $_GET['sub'] ) && 'installed' === sanitize_text_field( wp_unslash( $_GET['sub'] ) ) && ! $is_pro_installed && ! $is_installed ) ||
									( isset( $_GET['sub'] ) && 'not_installed' === sanitize_text_field( wp_unslash( $_GET['sub'] ) ) && ( $is_pro_installed || $is_installed ) ) ) {
									continue;
								}

								$link_attr = isset( $value_plugin['install_url'] ) ? 'href="' . esc_url( $value_plugin['install_url'] ) . '" target="_blank"' : 'href="' . esc_url( self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $key_plugin_explode[0] . '&from=import&TB_iframe=true&width=600&height=550' ) ) . '" class="thickbox open-plugin-details-modal"';

								$nothing_found = false;
								?>
									<div class="ineosq_product_box
									<?php
									if ( $is_active || $is_pro_active ) {
										echo esc_attr( ' ineosq_product_active' );}
									?>
									">
										<div class="ineosq_product_image">
											<a <?php echo wp_kses_data( $link_attr ); ?>><img src="<?php echo esc_url( $icon ); ?>"/></a>
										</div>
									<div class="ineosq_product_content">
										<div class="ineosq_product_title"><a <?php echo wp_kses_data( $link_attr ); ?>><?php echo esc_html( $value_plugin['name'] ); ?></a></div>
											<div class="ineosq-version">
												<?php
												if ( $is_pro_installed ) {
													echo '<span';
													if ( ! empty( $value_plugin['expired'] ) || ! empty( $value_plugin['update_availible'] ) ) {
														echo ' class="ineosq-update-available"';
													}
													echo '>v ' . esc_attr( $all_plugins[ $value_plugin['pro_version'] ]['Version'] ) . '</span>';
												} elseif ( $is_installed ) {
													echo '<span';
													if ( ! empty( $value_plugin['expired'] ) || ! empty( $value_plugin['update_availible'] ) ) {
														echo ' class="ineosq-update-available"';
													}
													echo '>v ' . esc_attr( $all_plugins[ $key_plugin ]['Version'] ) . '</span>';
												} else {
													echo '<span>' . esc_html__( 'Not installed', 'ineosq' ) . '</span>';
												}

												if ( ! empty( $value_plugin['expired'] ) ) {
													echo ' - <a class="ineosq-update-now" href="https://support.ineosq.com/hc/en-us/articles/202356359" target="_blank">' . esc_html__( 'Renew to get updates', 'ineosq' ) . '</a>';
												} elseif ( ! empty( $value_plugin['update_availible'] ) ) {
													$r = $update_availible_all->response[ $value_plugin['update_availible'] ];
													echo ' - <a class="ineosq-update-now" href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $value_plugin['update_availible'] ), 'upgrade-plugin_' . $value_plugin['update_availible'] ) ) . '" class="update-link" aria-label="' . sprintf( esc_html__( 'Update to v %s', 'ineosq' ), $r->new_version ) . '">' . sprintf( esc_html__( 'Update to v %s', 'ineosq' ), esc_html( $r->new_version ) ) . '</a>';
												}
												?>
											</div>
											<div class="ineosq_product_description">
												<?php echo ( strlen( $value_plugin['description'] ) > 100 ) ? esc_html( mb_substr( $value_plugin['description'], 0, 100 ) ) . '...' : esc_html( $value_plugin['description'] ); ?>
											</div>
											<div class="ineosq_product_links">
												<?php
												if ( $is_active || $is_pro_active ) {
													if ( $is_pro_isset ) {
														if ( ! $is_pro_installed ) {
															if ( ! empty( $plugins_array ) && array_key_exists( $value_plugin['pro_version'], $plugins_array ) ) {
																?>
																<form method="post" action="">
																		<input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Get Pro', 'ineosq' ); ?>" />
																		<input type="hidden" name="ineosq_plugin_action_submit" value="submit" />
																		<input type="hidden" name="ineosq_install_plugin" value="<?php echo esc_attr( $value_plugin['pro_version'] ); ?>" />
																			<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosq_license_install_nonce_name' ); ?>
																</form>
															<?php } else { ?>
																<a class="button button-secondary ineosq_upgrade_button" href="<?php echo esc_url( $ineosq_plugins[ $key_plugin ]['purchase'] ); ?>" target="_blank"><?php esc_html_e( 'Upgrade to Pro', 'ineosq' ); ?></a>
																<?php
															}
														}
													} else {
														?>
														<a class="ineosq_donate" href="https://ineosq.com/donate/" target="_blank"><?php esc_html_e( 'Donate', 'ineosq' ); ?></a> <span>|</span>
														<?php
													}

													if ( $is_pro_active ) {
														?>
														<a class="ineosq_settings" href="<?php echo esc_url( self_admin_url( $ineosq_plugins[ $key_plugin ]['pro_settings'] ) ); ?>"><?php esc_html_e( 'Settings', 'ineosq' ); ?></a>
													<?php } else { ?>
														<a class="ineosq_settings" href="<?php echo esc_url( self_admin_url( $ineosq_plugins[ $key_plugin ]['settings'] ) ); ?>"><?php esc_html_e( 'Settings', 'ineosq' ); ?></a>
														<?php
													}
												} else {
													if ( $is_pro_installed ) {
														?>
														<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( $current_page . '&ineosq_activate_plugin=' . $value_plugin['pro_version'] ), 'ineosq_activate_plugin' . $value_plugin['pro_version'] ) ); ?>" title="<?php esc_html_e( 'Activate this plugin', 'ineosq' ); ?>"><?php esc_html_e( 'Activate', 'ineosq' ); ?></a>
													<?php } elseif ( ! empty( $plugins_array ) && isset( $value_plugin['pro_version'] ) && array_key_exists( $value_plugin['pro_version'], $plugins_array ) ) { ?>
														<form method="post" action="">
															<input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Get Pro', 'ineosq' ); ?>" />
															<input type="hidden" name="ineosq_plugin_action_submit" value="submit" />
															<input type="hidden" name="ineosq_install_plugin" value="<?php echo esc_url( $value_plugin['pro_version'] ); ?>" />
															<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosq_license_install_nonce_name' ); ?>
														</form>
													<?php } elseif ( $is_installed ) { ?>
														<a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( $current_page . '&ineosq_activate_plugin=' . $key_plugin ), 'ineosq_activate_plugin' . $key_plugin ) ); ?>" title="<?php esc_html_e( 'Activate this plugin', 'ineosq' ); ?>"><?php esc_html_e( 'Activate', 'ineosq' ); ?></a>
														<?php
													} else {
														$install_url = isset( $value_plugin['install_url'] ) ? $value_plugin['install_url'] : network_admin_url( 'plugin-install.php?tab=search&type=term&s=' . str_replace( array( ' ', '-' ), '+', str_replace( '&', '', $value_plugin['name'] ) ) . '+IneosQ&plugin-search-input=Search+Plugins' );
														?>
														<a class="button button-secondary" href="<?php echo esc_url( $install_url ); ?>" title="<?php esc_html_e( 'Install this plugin', 'ineosq' ); ?>" target="_blank"><?php esc_html_e( 'Install Now', 'ineosq' ); ?></a>
														<?php
													}
												}
												?>
											</div>
										</div>
									<div class="clear"></div>
								</div>
								<?php
							}
							if ( $nothing_found ) {
								?>
								<p class="description"><?php esc_html_e( 'Nothing found. Try another criteria.', 'ineosq' ); ?></p>
							<?php } ?>
						</div>
						<div id="ineosq-filter-wrapper">
							<div class="ineosq-filter">
								<div class="ineosq-filter-title"><?php esc_html_e( 'Category', 'ineosq' ); ?></div>
								<ul class="ineosq-category">
									<li>
										<?php $sub_in_url = ( isset( $_GET['sub'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['sub'] ) ), array( 'installed', 'not_installed' ) ) ) ? '&sub=' . sanitize_text_field( wp_unslash( $_GET['sub'] ) ) : ''; ?>
										<a 
										<?php
										if ( 'all' === $plugin_category ) {
											echo ' class="ineosq-active"';}
										?>
											href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url ) ); ?>"><?php esc_html_e( 'All', 'ineosq' ); ?>
											<span>(<?php echo count( $ineosq_plugins ); ?>)</span>
										</a>
									</li>
									<?php foreach ( $ineosq_plugins_category as $category_key => $category_value ) { ?>
										<li>
											<a 
											<?php
											if ( $category_key === $plugin_category ) {
												echo ' class="ineosq-active"';}
											?>
												href="<?php echo esc_url( self_admin_url( $current_page . $sub_in_url . '&category=' . $category_key ) ); ?>"><?php echo esc_html( $category_value['name'] ); ?>
												<span>(<?php echo intval( $category_value['count'] ); ?>)</span>
											</a>
										</li>
									<?php } ?>
								</ul>
							</div>
						</div><!-- #ineosq-filter-wrapper -->
						<div class="clear"></div>
						<?php
					}
				} elseif ( 'ineosq_themes' === $page || 'themes' === $tab ) {
					require dirname( __FILE__ ) . '/product_list.php';
					?>
					<h1><?php esc_html_e( 'Themes', 'ineosq' ); ?></h1>
					<div id="availablethemes" class="ineosq-availablethemes">
						<div class="theme-browser content-filterable rendered">
							<div class="themes wp-clearfix">
								<?php
								foreach ( $themes as $key => $theme ) {
									$installed_theme = wp_get_theme( $theme->slug );
									?>
									<div class="theme" tabindex="0">
										<div class="theme-screenshot">
											<img src="<?php echo esc_url( ineosq_menu_url( 'icons/themes/' ) . $theme->slug . '.png' ); ?>" alt="" />
										</div>
										<div class="theme-author"><?php printf( esc_html__( 'By %s', 'ineosq' ), 'IneosQ' ); ?></div>
										<h3 class="theme-name"><?php echo esc_html( $theme->name ); ?></h3>
										<div class="theme-actions">
											<a class="button button-secondary preview install-theme-preview" href="<?php echo esc_url( $theme->href ); ?>" target="_blank"><?php esc_html_e( 'Learn More', 'ineosq' ); ?></a>
										</div>
										<?php
										if ( $installed_theme->exists() ) {
											if ( $wp_version < '4.6' ) {
												?>
												<div class="theme-installed"><?php esc_html_e( 'Already Installed', 'ineosq' ); ?></div>
											<?php } else { ?>
												<div class="notice notice-success notice-alt inline"><p><?php esc_html_e( 'Installed', 'ineosq' ); ?></p></div>
												<?php
											}
										}
										?>
									</div>
								<?php } ?>
								<br class="clear" />
							</div>
						</div>
						<p><a class="ineosq_browse_link" href="https://ineosq.com/products/wordpress/themes/" target="_blank"><?php esc_html_e( 'Browse More WordPress Themes', 'ineosq' ); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a></p>
					</div>
				<?php } elseif ( 'ineosq_system_status' === $page || 'system-status' === $tab ) { ?>
					<h1><?php esc_html_e( 'System status', 'ineosq' ); ?></h1>
					<div class="updated fade notice is-dismissible inline" 
					<?php
					if ( ! ( isset( $_REQUEST['ineosqmn_form_submit'] ) || isset( $_REQUEST['ineosqmn_form_submit_custom_email'] ) ) || '' !== $error ) {
						echo 'style="display:none"';}
					?>
					><p><strong><?php echo esc_html( $message ); ?></strong></p></div>
					<div class="error" 
					<?php
					if ( '' === $error ) {
						echo 'style="display:none"';}
					?>
					><p><strong><?php echo esc_html( $error ); ?></strong></p></div>
					<form method="post" action="">
						<p>
							<input type="hidden" name="ineosqmn_form_submit" value="submit" />
							<input type="submit" class="button-primary" value="<?php esc_html_e( 'Send to support', 'ineosq' ); ?>" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosqmn_nonce_submit' ); ?>
						</p>
					</form>
					<form method="post" action="">
						<p>
							<input type="hidden" name="ineosqmn_form_submit_custom_email" value="submit" />
							<input type="submit" class="button" value="<?php esc_html_e( 'Send to custom email &#187;', 'ineosq' ); ?>" />
							<input type="text" maxlength="250" value="" name="ineosqmn_form_email" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosqmn_nonce_submit_custom_email' ); ?>
						</p>
					</form>
					<?php foreach ( $system_info as $info ) { ?>
						<table class="widefat ineosq-system-info" cellspacing="0">
							<thead>
							<tr>
								<th colspan="2">
									<strong>
										<?php
										echo esc_html( $info['name'] );
										if ( isset( $info['count'] ) ) {
											echo ' (' . intval( $info['count'] ) . ')';
										}
										?>
									</strong>
								</th>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $info['data'] as $key => $value ) { ?>
								<tr>
									<td scope="row"><?php echo esc_attr( $key ); ?></td>
									<td scope="row"><?php echo esc_html( $value ); ?></td>
								</tr>
							<?php } ?>
							</tbody>
						</table>
						<?php
					}
				}
				?>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ineosq_get_banner_array' ) ) {
	function ineosq_get_banner_array() {
		global $bstwbsftwppdtplgns_banner_array;
		$bstwbsftwppdtplgns_banner_array = array(
			array( 'gglstpvrfctn_hide_banner_on_plugin_page', 'ineosq-google-2-step-verification/ineosq-google-2-step-verification.php', '1.0.0' ),
			array( 'sclbttns_hide_banner_on_plugin_page', 'social-buttons-pack/social-buttons-pack.php', '1.1.0' ),
			array( 'tmsht_hide_banner_on_plugin_page', 'timesheet/timesheet.php', '0.1.3' ),
			array( 'pgntn_hide_banner_on_plugin_page', 'pagination/pagination.php', '1.0.6' ),
			array( 'crrntl_hide_banner_on_plugin_page', 'car-rental/car-rental.php', '1.0.0' ),
			array( 'lnkdn_hide_banner_on_plugin_page', 'ineosq-linkedin/ineosq-linkedin.php', '1.0.1' ),
			array( 'pntrst_hide_banner_on_plugin_page', 'ineosq-pinterest/ineosq-pinterest.php', '1.0.1' ),
			array( 'zndskhc_hide_banner_on_plugin_page', 'zendesk-help-center/zendesk-help-center.php', '1.0.0' ),
			array( 'gglcptch_hide_banner_on_plugin_page', 'google-captcha/google-captcha.php', '1.18' ),
			array( 'mltlngg_hide_banner_on_plugin_page', 'multilanguage/multilanguage.php', '1.1.1' ),
			array( 'adsns_hide_banner_on_plugin_page', 'adsense-plugin/adsense-plugin.php', '1.36' ),
			array( 'vstrsnln_hide_banner_on_plugin_page', 'visitors-online/visitors-online.php', '0.2' ),
			array( 'cstmsrch_hide_banner_on_plugin_page', 'custom-search-plugin/custom-search-plugin.php', '1.28' ),
			array( 'prtfl_hide_banner_on_plugin_page', 'portfolio/portfolio.php', '2.33' ),
			array( 'rlt_hide_banner_on_plugin_page', 'realty/realty.php', '1.0.0' ),
			array( 'prmbr_hide_banner_on_plugin_page', 'promobar/promobar.php', '1.0.0' ),
			array( 'gglnltcs_hide_banner_on_plugin_page', 'ineosq-google-analytics/ineosq-google-analytics.php', '1.6.2' ),
			array( 'htccss_hide_banner_on_plugin_page', 'htaccess/htaccess.php', '1.6.3' ),
			array( 'sbscrbr_hide_banner_on_plugin_page', 'subscriber/subscriber.php', '1.1.8' ),
			array( 'lmtttmpts_hide_banner_on_plugin_page', 'limit-attempts/limit-attempts.php', '1.0.2' ),
			array( 'sndr_hide_banner_on_plugin_page', 'sender/sender.php', '0.5' ),
			array( 'srrl_hide_banner_on_plugin_page', 'user-role/user-role.php', '1.4' ),
			array( 'pdtr_hide_banner_on_plugin_page', 'updater/updater.php', '1.12' ),
			array( 'cntctfrmtdb_hide_banner_on_plugin_page', 'contact-form-to-db/contact_form_to_db.php', '1.2' ),
			array( 'cntctfrmmlt_hide_banner_on_plugin_page', 'contact-form-multi/contact-form-multi.php', '1.0.7' ),
			array( 'gglmps_hide_banner_on_plugin_page', 'ineosq-google-maps/ineosq-google-maps.php', '1.2' ),
			array( 'fcbkbttn_hide_banner_on_plugin_page', 'facebook-button-plugin/facebook-button-plugin.php', '2.29' ),
			array( 'twttr_hide_banner_on_plugin_page', 'twitter-plugin/twitter.php', '2.34' ),
			array( 'pdfprnt_hide_banner_on_plugin_page', 'pdf-print/pdf-print.php', '1.7.1' ),
			array( 'gglstmp_hide_banner_on_plugin_page', 'google-sitemap-plugin/google-sitemap-plugin.php', '2.8.4' ),
			array( 'cntctfrmpr_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-pro/contact_form_pro.php', '1.14' ),
			array( 'cntctfrm_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.47' ),
			array( 'cptch_hide_banner_on_plugin_page', 'captcha-ineosq/captcha-ineosq.php', '3.8.4' ),
			array( 'gllr_hide_banner_on_plugin_page', 'gallery-plugin/gallery-plugin.php', '3.9.1' ),
			array( 'cntctfrm_for_ctfrmtdb_hide_banner_on_plugin_page', 'contact-form-plugin/contact_form.php', '3.62' ),
			array( 'ineosqcrrntl_hide_banner_on_plugin_page', 'ineosq-car-rental/ineosq-car-rental.php', '0.0.1' ),
			array( 'rtng_hide_banner_on_plugin_page', 'rating-ineosq/rating-ineosq.php', '1.0.0' ),
			array( 'prflxtrflds_hide_banner_on_plugin_page', 'profile-extra-fields/profile-extra-fields.php', '1.1.3' ),
			array( 'psttcsv_hide_banner_on_plugin_page', 'post-to-csv/post-to-csv.php', '1.3.4' ),
			array( 'cstmdmnpg_hide_banner_on_plugin_page', 'custom-admin-page/custom-admin-page.php', '1.0.0' ),
		);
	}
}

if ( ! function_exists( 'ineosq_get_service_banner_array' ) ) {
	function ineosq_get_service_banner_array() {
		global $ineosq_get_service_banner_array;
		$response = wp_remote_get( 'https://ineosq.com/banners/banners.txt',
			array(
					'timeout'     => 120,
					'httpversion' => '1.1',
			)
		);
		$responseBody = wp_remote_retrieve_body( $response );
		$result = explode( ';', $responseBody );
		if ( ! is_wp_error( $result ) && is_array( $result ) && '' !== $result[0] ) {
				$ineosq_get_service_banner_array = array(
					'images' => $result,
					'date'   => time()
				);
		} else {
				$ineosq_get_service_banner_array = array(
					'date'   => time()
				);
		}
	}
}
