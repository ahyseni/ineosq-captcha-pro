<?php
/**
 * General functions for IneosQ plugins
 * @package INEOSQ Menu
 * @version 2.4.2
 * Main functions
 */

/**
 * General functions for IneosQ plugins
 */
require dirname( __FILE__ ) . '/deprecated.php';
require_once dirname( __FILE__ ) . '/deactivation-form.php';

/**
 * Function to add 'ineosq' slug for INEOSQ_Menu MO file if INEOSQ_Menu loaded from theme.
 *
 * @since 1.9.7
 */
if ( ! function_exists ( 'ineosq_get_mofile' ) ) {
	function ineosq_get_mofile( $mofile, $domain ) {
		if ( 'ineosq' === $domain ) {
			$locale = get_locale();
			return str_replace( $locale, "ineosq-{$locale}", $mofile );
		}

		return $mofile;
	}
}

/**
 * Internationalization, first(!)
 *
 * @since 1.9.7
 */
if ( isset( $ineosq_menu_source ) && 'themes' === $ineosq_menu_source ) {
	add_filter( 'load_textdomain_mofile', 'ineosq_get_mofile', 10, 2 );
	load_theme_textdomain( 'ineosq', get_stylesheet_directory() . '/inc/ineosq_menu/languages' );
	remove_filter( 'load_textdomain_mofile', 'ineosq_get_mofile' );
} else {
	load_plugin_textdomain( 'ineosq', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Function to getting url to current INEOSQ_Menu.
 *
 * @since 1.9.7
 */
if ( ! function_exists( 'ineosq_menu_url' ) ) {
	if ( ! isset( $ineosq_menu_source ) || 'plugins' === $ineosq_menu_source ) {
		function ineosq_menu_url( $path = '' ) {
			return plugins_url( $path, __FILE__ );
		}
	} else {
		function ineosq_menu_url( $path = '' ) {
			$ineosq_menu_current_dir = str_replace( '\\', '/', dirname( __FILE__ ) );
			$ineosq_menu_abspath     = str_replace( '\\', '/', ABSPATH );
			$ineosq_menu_current_url = site_url( str_replace( $ineosq_menu_abspath, '', $ineosq_menu_current_dir ) );

			return sprintf( '%s/%s', $ineosq_menu_current_url, $path );
		}
	}
}

/**
 * Function check if plugin is compatible with current WP version
 *
 * @return void
 */
if ( ! function_exists( 'ineosq_wp_min_version_check' ) ) {
	function ineosq_wp_min_version_check( $plugin_basename, $plugin_info, $require_wp, $min_wp = false ) {
		global $wp_version, $ineosq_versions_notice_array;
		if ( false === $min_wp ) {
			$min_wp = $require_wp;
		}
		if ( version_compare( $wp_version, $min_wp, '<' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( is_plugin_active( $plugin_basename ) ) {
				deactivate_plugins( $plugin_basename );
				$admin_url = ( function_exists( 'get_admin_url' ) ) ? get_admin_url( null, 'plugins.php' ) : esc_url( '/wp-admin/plugins.php' );
				wp_die(
					sprintf(
						"<strong>%s</strong> %s <strong>WordPress %s</strong> %s <br /><br />%s <a href='%s'>%s</a>.",
						esc_html( $plugin_info['Name'] ),
						esc_html__( 'requires', 'ineosq' ),
						esc_html( $require_wp ),
						esc_html__( 'or higher, that is why it has been deactivated! Please upgrade WordPress and try again.', 'ineosq' ),
						esc_html__( 'Back to the WordPress', 'ineosq' ),
						esc_url( $admin_url ),
						esc_html__( 'Plugins page', 'ineosq' )
					)
				);
			}
		} elseif ( version_compare( $wp_version, $require_wp, '<' ) ) {
			$ineosq_versions_notice_array[] = array(
				'name'    => $plugin_info['Name'],
				'version' => $require_wp,
			);
		}
	}
}

/**
 * Function display review block
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_plugin_reviews_block' ) ) {
	function ineosq_plugin_reviews_block( $plugin_name, $plugin_slug ) { ?>
		<div class="ineosq-plugin-reviews">
			<div class="ineosq-plugin-reviews-rate">
				<?php esc_html_e( 'Like the plugin?', 'ineosq' ); ?>
				<a href="https://wordpress.org/support/view/plugin-reviews/<?php echo esc_attr( $plugin_slug ); ?>?filter=5" target="_blank" title="<?php printf( esc_html__( '%s reviews', 'ineosq' ), esc_html( sanitize_text_field( $plugin_name ) ) ); ?>">
					<?php esc_html_e( 'Rate it', 'ineosq' ); ?>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
					<span class="dashicons dashicons-star-filled"></span>
				</a>
			</div>
			<div class="ineosq-plugin-reviews-support">
				<?php esc_html_e( 'Need help?', 'ineosq' ); ?>
				<a href="https://support.ineosq.com"><?php esc_html_e( 'Visit Help Center', 'ineosq' ); ?></a>
			</div>
			<div class="ineosq-plugin-reviews-donate">
				<?php esc_html_e( 'Want to support the plugin?', 'ineosq' ); ?>
				<a href="https://ineosq.com/donate/"><?php esc_html_e( 'Donate', 'ineosq' ); ?></a>
			</div>
		</div>
		<?php
	}
}

/**
 * Function display license notification
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_plugin_update_row' ) ) {
	function ineosq_plugin_update_row( $plugin_key, $link_slug = false, $free_plugin_name = false ) {
		global $bstwbsftwppdtplgns_options, $wp_version;
		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		if ( isset( $bstwbsftwppdtplgns_options['wrong_license_key'][ $plugin_key ] ) ) {
			$explode_plugin_key = explode( '/', $plugin_key );
			$class              = ( $wp_version >= 4.6 ) ? 'active' : '';
			$style              = ( $wp_version < 4.6 ) ? ' style="background-color: #FFEBE8;border-color: #CC0000;"' : '';
			$div_class          = ( $wp_version >= 4.6 ) ? ' notice inline notice-warning notice-alt' : '';
			echo '<tr class="ineosq-plugin-update-tr plugin-update-tr ' . esc_attr( $class ) . '" id="' . esc_attr( $explode_plugin_key[0] ) . '-update" data-slug="' . esc_attr( $explode_plugin_key[0] ) . '" data-plugin="' . esc_attr( $plugin_key ) . '">
					<td colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="plugin-update colspanchange">
						<div class="update-message' . esc_attr( $div_class ) . '"' . wp_kses_post( $style ) . '>';
						if ( $wp_version >= 4.6 ) {
							echo '<p>';
						}
						echo '<strong>' . esc_html__( 'WARNING: Illegal use notification', 'ineosq' ) . '.</strong> ' . esc_html__( 'You can use one license of the Pro plugin for one domain only. Please check and edit your license or domain if necessary using your personal Client Area. We strongly recommend you to solve the problem within 24 hours, otherwise the Pro plugin will be deactivated.', 'ineosq' ) . ' <a target="_blank" href="https://support.ineosq.com/hc/en-us/articles/204240089">' . esc_html__( 'Learn More', 'ineosq' ) . '</a>';
						if ( $wp_version >= 4.6 ) {
							echo '</p>';
						}
						echo '</div>
					</td>
				</tr>';
		} elseif ( isset( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) && strtotime( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) < strtotime( gmdate( 'm/d/Y' ) ) ) {
			$explode_plugin_key = explode( '/', $plugin_key );
			$class              = ( $wp_version >= 4.6 ) ? 'active' : '';
			$style              = ( $wp_version < 4.6 ) ? ' style="color: #8C0000;"' : '';
			$div_class          = ( $wp_version >= 4.6 ) ? ' notice inline notice-warning notice-alt' : '';
			echo '<tr class="ineosq-plugin-update-tr plugin-update-tr ' . esc_attr( $class ) . '" id="' . esc_attr( $explode_plugin_key[0] ) . '-update" data-slug="' . esc_attr( $explode_plugin_key[0] ) . '" data-plugin="' . esc_attr( $plugin_key ) . '">
					<td colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="plugin-update colspanchange">
						<div class="update-message' . esc_attr( $div_class ) . '"' . wp_kses_post( $style ) . '>';
							if ( $wp_version >= 4.6 ) {
								echo '<p>';
							}
							if ( isset( $bstwbsftwppdtplgns_options['trial'][ $plugin_key ] ) && false !== $link_slug ) {
								echo esc_html__( 'Notice: Your Pro Trial license has expired. To continue using the plugin, you should buy a Pro license', 'ineosq' ) . ' - <a href="https://ineosq.com/products/wordpress/plugins/' . esc_attr( $link_slug ) . '/">https://ineosq.com/products/wordpress/plugins/' . esc_attr( $link_slug ) . '/</a>';
							} else {
								echo esc_html__( 'Your license has expired. To continue getting top-priority support and plugin updates, you should extend it.', 'ineosq' ) . ' <a target="_new" href="https://support.ineosq.com/entries/53487136">' . esc_html__( 'Learn more', 'ineosq' ) . '</a>';
							}
							if ( $wp_version >= 4.6 ) {
								echo '</p>';
							}
						echo '</div>
					</td>
				</tr>';
		} elseif ( isset( $bstwbsftwppdtplgns_options['trial'][ $plugin_key ] ) ) {
			$explode_plugin_key = explode( '/', $plugin_key );
			$class              = ( $wp_version >= 4.6 ) ? 'active' : '';
			$style              = ( $wp_version < 4.6 ) ? ' style="color: #8C0000;"' : '';
			$div_class          = ( $wp_version >= 4.6 ) ? ' notice inline notice-warning notice-alt' : '';
			echo '<tr class="ineosq-plugin-update-tr plugin-update-tr ' . esc_attr( $class ) . '" id="' . esc_attr( $explode_plugin_key[0] ) . '-update" data-slug="' . esc_attr( $explode_plugin_key[0] ) . '" data-plugin="' . esc_attr( $plugin_key ) . '">
					<td colspan="' . esc_attr( $wp_list_table->get_column_count() ) . '" class="plugin-update colspanchange">
						<div class="update-message' . esc_attr( $div_class ) . '"' . wp_kses_post( $style ) . '>';
							if ( $wp_version >= 4.6 ) {
								echo '<p>';
							}
							if ( false !== $free_plugin_name ) {
				printf( esc_html__( 'Notice: You are using the Pro Trial license of %s plugin.', 'ineosq' ), esc_html( $free_plugin_name ) );
							} else {
								esc_html_e( 'Notice: You are using the Pro Trial license of plugin.', 'ineosq' );
							}
							if ( isset( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) ) {
								echo ' ' . esc_html__( 'The Pro Trial license will expire on', 'ineosq' ) . ' ' . esc_html( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) . '.';
							}
							if ( $wp_version >= 4.6 ) {
								echo '</p>';
							}
					echo '</div>
					</td>
				</tr>';
		}
	}
}

/**
 * Function display admin notices
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_admin_notices' ) ) {
	function ineosq_admin_notices() {
		global $ineosq_versions_notice_array, $ineosq_plugin_banner_to_settings, $bstwbsftwppdtplgns_options, $ineosq_plugin_banner_go_pro, $bstwbsftwppdtplgns_banner_array, $ineosq_plugin_banner_timeout, $ineosq_get_service_banner_array;

		/* ineosq_plugin_banner_go_pro */
		if ( ! empty( $ineosq_plugin_banner_go_pro ) ) {
			/* get $ineosq_plugins */
			require dirname( __FILE__ ) . '/product_list.php';

			foreach ( $bstwbsftwppdtplgns_banner_array as $value ) {
				if ( isset( $ineosq_plugin_banner_go_pro[ $value[0] ] ) && ! isset( $_COOKIE[ $value[0] ] ) ) {

					if ( isset( $ineosq_plugins[ $value[1] ]['pro_version'] ) && is_plugin_active( $ineosq_plugins[ $value[1] ]['pro_version'] ) ) {
						continue;
					}

					$single_banner_value = $ineosq_plugin_banner_go_pro[ $value[0] ];
					?>
					<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
						<div class="<?php echo esc_attr( $single_banner_value['prefix'] ); ?>_message ineosq_banner_on_plugin_page ineosq_go_pro_banner" style="display: none;">
							<button class="<?php echo esc_attr( $single_banner_value['prefix'] ); ?>_close_icon close_icon notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
							<div class="icon">
								<img title="" src="<?php echo esc_attr( $single_banner_value['banner_url'] ); ?>" alt="" />
							</div>
							<div class="text">
								<?php esc_html_e( 'It’s time to upgrade your', 'ineosq' ); ?> <strong><?php echo esc_html( $single_banner_value['plugin_info']['Name'] ); ?> plugin</strong> <?php esc_html_e( 'to', 'ineosq' ); ?> <strong>Pro</strong> <?php esc_html_e( 'version!', 'ineosq' ); ?><br />
								<span><?php esc_html_e( 'Extend standard plugin functionality with new great options.', 'ineosq' ); ?></span>
							</div>
							<div class="button_div">
								<a class="button" target="_blank" href="<?php echo esc_url( $single_banner_value['ineosq_link'] ); ?>"><?php esc_html_e( 'Learn More', 'ineosq' ); ?></a>
							</div>
						</div>
					</div>
					<?php
					break;
				}
			}
		}

		/* $ineosq_plugin_banner_timeout */
		if ( ! empty( $ineosq_plugin_banner_timeout ) ) {
			foreach ( $ineosq_plugin_banner_timeout as $banner_value ) { 
				if ( 0 == $banner_value['license_expired'] ) { ?>
					<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
						<div class="<?php echo esc_attr( $banner_value['prefix'] ); ?>_message_timeout ineosq_banner_on_plugin_page ineosq_banner_timeout" style="display:none;">
							<button class="<?php echo esc_attr( $banner_value['prefix'] ); ?>_close_icon close_icon notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
							<div class="icon">
								<img title="" src="<?php echo esc_url( $banner_value['banner_url'] ); ?>" alt="" />
							</div>
						<div class="text"><?php printf( esc_html__( "Your license key for %1\$s expires on %2\$s and you won't be granted TOP-PRIORITY SUPPORT or UPDATES.", 'ineosq' ), '<strong>' . esc_html__( $banner_value['plugin_name'] ) . '</strong>', esc_html__( $bstwbsftwppdtplgns_options['time_out'][ $banner_value['plugin_key'] ] ) ); ?> <a target="_new" href="https://support.ineosq.com/entries/53487136"><?php esc_html_e( 'Learn more', 'ineosq' ); ?></a></div>
						</div>
					</div>
				<?php } elseif ( 1 == $banner_value['license_expired'] ) { ?>
					<div class="error" style="padding: 0;">
						<div class="<?php echo $banner_value['prefix']; ?>_message_timeout ineosq_banner_on_plugin_page ineosq_banner_timeout" style="border: none; margin: 0;">
							<button class="<?php echo $banner_value['prefix']; ?>_close_icon close_icon notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
							<div class="text">
								<strong><?php esc_html_e( "Pro License Notice", 'ineosq' ); ?>:</strong>
								<?php printf( esc_html__( "Your license key for %s has expired. Please renew the license to continue getting plugin updates and top-priority support.", 'ineosq' ), '<strong>' . $banner_value['plugin_name'] . '</strong>' ); ?> <a target="_new" href="https://support.ineosq.com/entries/53487136"><?php esc_html_e( "Learn more", 'ineosq' ); ?></a>
							</div>
						</div>
					</div>
				<?php }
			}
		}

		/*  versions notice */
		if ( ! empty( $ineosq_versions_notice_array ) ) {
			foreach ( $ineosq_versions_notice_array as $key => $value ) {
				?>
				<div class="update-nag">
					<?php
					printf(
						'<strong>%s</strong> %s <strong>WordPress %s</strong> %s',
						esc_html__( $value['name'] ),
						esc_html__( 'requires', 'ineosq' ),
						esc_html__( $value['version'] ),
						esc_html__( 'or higher! We do not guarantee that our plugin will work correctly. Please upgrade to WordPress latest version.', 'ineosq' )
					);
					?>
				</div>
				<?php
			}
		}

		/*  banner_to_settings notice */
		if ( ! empty( $ineosq_plugin_banner_to_settings ) ) {
			if ( 1 === count( $ineosq_plugin_banner_to_settings ) ) {
				?>
				<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
					<div class="ineosq_banner_on_plugin_page ineosq_banner_to_settings">
						<div class="icon">
							<img title="" src="<?php echo esc_url( $ineosq_plugin_banner_to_settings[0]['banner_url'] ); ?>" alt="" />
						</div>
						<div class="text">
							<strong><?php printf( esc_html__( 'Thank you for installing %s plugin!', 'ineosq' ), esc_html( $ineosq_plugin_banner_to_settings[0]['plugin_info']['Name'] ) ); ?></strong>
							<br />
							<?php esc_html_e( "Let's get started", 'ineosq' ); ?>:
							<a href="<?php echo esc_url( self_admin_url( $ineosq_plugin_banner_to_settings[0]['settings_url'] ) ); ?>"><?php esc_html_e( 'Settings', 'ineosq' ); ?></a>
							<?php if ( false !== $ineosq_plugin_banner_to_settings[0]['post_type_url'] ) { ?>
								<?php esc_html_e( 'or', 'ineosq' ); ?>
								<a href="<?php echo esc_url( self_admin_url( $ineosq_plugin_banner_to_settings[0]['post_type_url'] ) ); ?>"><?php esc_html_e( 'Add New', 'ineosq' ); ?></a>
							<?php } ?>
						</div>
						<form action="" method="post">
							<button class="notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
							<input type="hidden" name="ineosq_hide_settings_notice_<?php echo esc_html( $ineosq_plugin_banner_to_settings[0]['plugin_options_name'] ); ?>" value="hide" />
							<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosq_settings_nonce_name' ); ?>
						</form>
					</div>
				</div>
			<?php } else { ?>
				<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
					<div class="ineosq_banner_on_plugin_page ineosq_banner_to_settings_joint">
						<form action="" method="post">
							<button class="notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
							<div class="ineosq-text">
								<div class="icon">
									<span class="dashicons dashicons-admin-plugins"></span>
								</div>
								<strong><?php esc_html_e( 'Thank you for installing plugins by IneosQ!', 'ineosq' ); ?></strong>
								<div class="hide-if-no-js ineosq-more-links">
									<a href="#" class="ineosq-more"><?php esc_html_e( 'More Details', 'ineosq' ); ?></a>
									<a href="#" class="ineosq-less hidden"><?php esc_html_e( 'Less Details', 'ineosq' ); ?></a>
								</div>
								<?php wp_nonce_field( plugin_basename( __FILE__ ), 'ineosq_settings_nonce_name' ); ?>
								<div class="clear"></div>
							</div>
							<div class="ineosq-details hide-if-js">
								<?php foreach ( $ineosq_plugin_banner_to_settings as $value ) { ?>
									<div>
										<strong><?php echo esc_html( str_replace( ' by IneosQ', '', $value['plugin_info']['Name'] ) ); ?></strong>&ensp;<a href="<?php echo esc_url( self_admin_url( $value['settings_url'] ) ); ?>"><?php esc_html_e( 'Settings', 'ineosq' ); ?></a>
										<?php if ( false !== $value['post_type_url'] ) { ?>
											&ensp;|&ensp;<a target="_blank" href="<?php echo esc_url( self_admin_url( $value['post_type_url'] ) ); ?>"><?php esc_html_e( 'Add New', 'ineosq' ); ?></a>
										<?php } ?>
										<input type="hidden" name="ineosq_hide_settings_notice_<?php echo esc_html( $value['plugin_options_name'] ); ?>" value="hide" />
									</div>
								<?php } ?>
							</div>
						</div>
					</form>
				</div>
				<?php
			}
		}

		/**
		 * Show notices about deprecated_function
		 *
		 * @since 1.9.8
		*/
		if ( ! empty( $bstwbsftwppdtplgns_options['deprecated_function'] ) ) {
			?>
			<div class="update-nag">
				<strong><?php esc_html_e( 'Deprecated function(-s) is used on the site here:', 'ineosq' ); ?></strong>
				<?php
				$i = 1;
				foreach ( $bstwbsftwppdtplgns_options['deprecated_function'] as $function_name => $attr ) {
					if ( 1 !== $i ) {
						echo ' ,';
					}
					if ( ! empty( $attr['product-name'] ) ) {
						echo esc_html( $attr['product-name'] );
					} elseif ( ! empty( $attr['file'] ) ) {
						echo esc_url( $attr['file'] );
					}
					unset( $bstwbsftwppdtplgns_options['deprecated_function'][ $function_name ] );
					$i++;
				}
				?>
				.
				<br/>
				<?php esc_html_e( 'This function(-s) will be removed over time. Please update the product(-s).', 'ineosq' ); ?>
			</div>
			<?php
			if ( is_multisite() ) {
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			} else {
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			}
		}

		if ( empty( $ineosq_get_service_banner_array ) && ( ( ! isset( $bstwbsftwppdtplgns_options['hide_services_banner'] ) && ! isset( $bstwbsftwppdtplgns_options['hide_services_banner_time'] ) ) || ( ! isset( $bstwbsftwppdtplgns_options['hide_services_banner'] ) && isset( $bstwbsftwppdtplgns_options['hide_services_banner_time'] ) && ( $bstwbsftwppdtplgns_options['hide_services_banner_time'] + ( 7 * 24 * 60 * 60 ) < time() ) ) ||  ( isset( $bstwbsftwppdtplgns_options['hide_services_banner'] ) && false === $bstwbsftwppdtplgns_options['hide_services_banner'] && ! isset( $bstwbsftwppdtplgns_options['hide_services_banner_time'] ) ) || ( isset( $bstwbsftwppdtplgns_options['hide_services_banner'] ) && false === $bstwbsftwppdtplgns_options['hide_services_banner'] && isset( $bstwbsftwppdtplgns_options['hide_services_banner_time'] ) && ( $bstwbsftwppdtplgns_options['hide_services_banner_time'] + ( 7 * 24 * 60 * 60 ) < time() ) ) ) ) {
			$ineosq_get_service_banner_array = get_option( 'ineosq_get_service_banner_array' );
			if ( empty( $ineosq_get_service_banner_array ) || ! isset( $ineosq_get_service_banner_array['date'] ) || $ineosq_get_service_banner_array['date'] + ( 7 * 24 * 60 * 60 ) < time() ) {
				ineosq_get_service_banner_array();
				update_option( 'ineosq_get_service_banner_array', $ineosq_get_service_banner_array );
			}
			if ( ! empty( $ineosq_get_service_banner_array ) && ! empty( $ineosq_get_service_banner_array['images']  ) && 0 < count( $ineosq_get_service_banner_array['images'] ) ) {
				$current_banner = rand( 0, count( $ineosq_get_service_banner_array['images'] ) - 1 );
				$image = wp_remote_get( 'https://ineosq.com/banners/' . $ineosq_get_service_banner_array['images'][ $current_banner ],
					array(
							'timeout'     => 120,
							'httpversion' => '1.1',
					) 
				);
				$image_mobile = wp_remote_get( 'https://ineosq.com/banners/' . ( str_replace( '.svg', '', $ineosq_get_service_banner_array['images'][ $current_banner ] ) . 'mob.svg' ),
					array(
							'timeout'     => 120,
							'httpversion' => '1.1',
					) 
				);
				if ( $image['response']['code'] !== '404' && $image['response']['code'] !== '403' ) {
					?>
					<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
						<style>
						/* Style for Services banner */
						.ineosq_banner_on_plugin_page svg {
							max-width: 100%;
							height: auto;
							display: inherit;
						}
						.ineosq_banner_on_plugin_page .ineosq_mobile {
							display: none;
						}
						.ineosq_banner_on_plugin_page a {
							display: block;
						}
						@media screen and (max-width: 767px) {
							.ineosq_banner_on_plugin_page .ineosq_mobile {
								display: block;
							}
							.ineosq_banner_on_plugin_page .ineosq_mobile svg {
								width: 100%;
							}
							.ineosq_banner_on_plugin_page .ineosq_full {
								display: none;
							}
						}
						</style>
						<form action="" method="post">
							<div class="ineosq_banner_on_plugin_page ineosq_go_pro_banner">
								<button class="close_icon notice-dismiss ineosq_hide_services_banner" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
								<div class="ineosq_full"><a href="https://ineosq.com/premium-wordpress-plugins-sale/" target="_blank"><?php echo $image['body']; ?></a></div>
								<?php if ( $image_mobile['response']['code'] !== '404' && $image_mobile['response']['code'] !== '403' ) { ?>
									<div class="ineosq_mobile"><a href="https://ineosq.com/premium-wordpress-plugins-sale/" target="_blank"><?php echo $image_mobile['body']; ?></a></div>
								<?php } ?>
								<input type="hidden" name="ineosq_hide_services_banner" value="hide" />
								<?php wp_nonce_field( 'ineosq_hide_services_banner_nonce_action', 'ineosq_hide_services_banner_nonce_name' ); ?>
							</div>
						</form>
					</div>
					<?php
				}
			}
		}
	}
}

/**
 * Function display banner
 *
 * @return array
 */
if ( ! function_exists( 'ineosq_plugin_banner_go_pro' ) ) {
	function ineosq_plugin_banner_go_pro( $plugin_options, $plugin_info, $this_banner_prefix, $ineosq_link_slug, $link_key, $link_pn, $banner_url_or_slug ) {
		global $ineosq_plugin_banner_go_pro, $wp_version, $bstwbsftwppdtplgns_banner_array;

		if ( ! isset( $plugin_options['first_install'] ) || strtotime( '-1 week' ) < $plugin_options['first_install'] ) {
			return;
		}

		$ineosq_link = esc_url( 'https://ineosq.com/products/wordpress/plugins/' . $ineosq_link_slug . '/?k=' . $link_key . '&pn=' . $link_pn . '&v=' . $plugin_info['Version'] . '&wp_v=' . $wp_version );

		if ( false === strrpos( $banner_url_or_slug, '/' ) ) {
			$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-256x256.png';
		}

		$ineosq_plugin_banner_go_pro[ $this_banner_prefix . '_hide_banner_on_plugin_page' ] = array(
			'plugin_info' => $plugin_info,
			'prefix'      => $this_banner_prefix,
			'ineosq_link'    => $ineosq_link,
			'banner_url'  => $banner_url_or_slug,
		);

		if ( empty( $bstwbsftwppdtplgns_banner_array ) ) {
			if ( ! function_exists( 'ineosq_get_banner_array' ) ) {
				require_once dirname(__FILE__) . '/ineosq_menu.php';
			}
			ineosq_get_banner_array();
		}
	}
}

/**
 * Function update banner params
 *
 * @return global array
 */
if ( ! function_exists( 'ineosq_add_plugin_banner_timeout' ) ) {
	function ineosq_add_plugin_banner_timeout( $plugin_key, $plugin_prefix, $plugin_name, $banner_url_or_slug ) {
		global $pagenow, $ineosq_plugin_banner_timeout, $bstwbsftwppdtplgns_options;

		if ( isset( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) && ( strtotime( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) < strtotime( gmdate( 'm/d/Y' ) . '+1 month' ) ) && ( strtotime( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) > strtotime( gmdate( 'm/d/Y' ) ) ) ) {

			if ( false === strrpos( $banner_url_or_slug, '/' ) ) {
				$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-256x256.png';
			}

			$ineosq_plugin_banner_timeout[] = array(
				'plugin_key'		=> $plugin_key,
				'prefix'			=> $plugin_prefix,
				'plugin_name'		=> $plugin_name,
				'banner_url'		=> $banner_url_or_slug,
				'license_expired' 	=> 0
			);
		} elseif ( isset( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) && ( strtotime( $bstwbsftwppdtplgns_options['time_out'][ $plugin_key ] ) < strtotime( date("m/d/Y") ) ) ) {
			if ( false === strrpos( $banner_url_or_slug, '/' ) ) {
				$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-256x256.png';
			}

			$ineosq_plugin_banner_timeout[] = array(
				'plugin_key'		=> $plugin_key,
				'prefix'			=> $plugin_prefix,
				'plugin_name'		=> $plugin_name,
				'banner_url'		=> $banner_url_or_slug,
				'license_expired' 	=> 1
			);
		}
	}
}

/**
 * Function settings for banner
 *
 * @return global array
 */
if ( ! function_exists( 'ineosq_plugin_banner_to_settings' ) ) {
	function ineosq_plugin_banner_to_settings( $plugin_info, $plugin_options_name, $banner_url_or_slug, $settings_url, $post_type_url = false ) {
		global $ineosq_plugin_banner_to_settings, $bstwbsftwppdtplgns_options;

		$is_network_admin = is_network_admin();

		$plugin_options = $is_network_admin ? get_site_option( $plugin_options_name ) : get_option( $plugin_options_name );

		if ( isset( $plugin_options['display_settings_notice'] ) && 0 === $plugin_options['display_settings_notice'] ) {
			return;
		}

		if ( isset( $_POST[ 'ineosq_hide_settings_notice_' . $plugin_options_name ] ) && check_admin_referer( plugin_basename( __FILE__ ), 'ineosq_settings_nonce_name' ) ) {
			$plugin_options['display_settings_notice'] = 0;
			if ( $is_network_admin ) {
				update_site_option( $plugin_options_name, $plugin_options );
			} else {
				update_option( $plugin_options_name, $plugin_options );
			}
			return;
		}

		if ( false === strrpos( $banner_url_or_slug, '/' ) ) {
			$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-256x256.png';
		}

		$ineosq_plugin_banner_to_settings[] = array(
			'plugin_info'         => $plugin_info,
			'plugin_options_name' => $plugin_options_name,
			'banner_url'          => $banner_url_or_slug,
			'settings_url'        => $settings_url,
			'post_type_url'       => $post_type_url,
		);
	}
}

/**
 * Function display for feature banner
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_plugin_suggest_feature_banner' ) ) {
	function ineosq_plugin_suggest_feature_banner( $plugin_info, $plugin_options_name, $banner_url_or_slug ) {
		$is_network_admin = is_network_admin();

		$plugin_options = $is_network_admin ? get_site_option( $plugin_options_name ) : get_option( $plugin_options_name );

		if ( isset( $plugin_options['display_suggest_feature_banner'] ) && 0 === $plugin_options['display_suggest_feature_banner'] ) {
			return;
		}

		if ( ! isset( $plugin_options['first_install'] ) ) {
			$plugin_options['first_install'] = strtotime( 'now' );
			$update_option                   = $return = true;
		} elseif ( strtotime( '-2 week' ) < $plugin_options['first_install'] ) {
			$return = true;
		}

		if ( ! isset( $plugin_options['go_settings_counter'] ) ) {
			$plugin_options['go_settings_counter'] = 1;
			$update_option                         = $return = true;
		} elseif ( 20 > $plugin_options['go_settings_counter'] ) {
			$plugin_options['go_settings_counter'] = $plugin_options['go_settings_counter'] + 1;
			$update_option                         = $return = true;
		}

		if ( isset( $update_option ) ) {
			if ( $is_network_admin ) {
				update_site_option( $plugin_options_name, $plugin_options );
			} else {
				update_option( $plugin_options_name, $plugin_options );
			}
		}

		if ( isset( $return ) ) {
			return;
		}

		if ( isset( $_POST[ 'ineosq_hide_suggest_feature_banner_' . $plugin_options_name ] ) && check_admin_referer( $plugin_info['Name'], 'ineosq_settings_nonce_name' ) ) {
			$plugin_options['display_suggest_feature_banner'] = 0;
			if ( $is_network_admin ) {
				update_site_option( $plugin_options_name, $plugin_options );
			} else {
				update_option( $plugin_options_name, $plugin_options );
			}
			return;
		}

		if ( false === strrpos( $banner_url_or_slug, '/' ) ) {
			$banner_url_or_slug = '//ps.w.org/' . $banner_url_or_slug . '/assets/icon-256x256.png';
		}
		?>
		<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
			<div class="ineosq_banner_on_plugin_page ineosq_suggest_feature_banner">
				<div class="icon">
					<img title="" src="<?php echo esc_url( $banner_url_or_slug ); ?>" alt="" />
				</div>
				<div class="text">
					<strong><?php printf( esc_html__( 'Thank you for choosing %s plugin!', 'ineosq' ), esc_html( $plugin_info['Name'] ) ); ?></strong><br />
					<?php esc_html_e( "If you have a feature, suggestion or idea you'd like to see in the plugin, we'd love to hear about it!", 'ineosq' ); ?>
					<a target="_blank" href="https://support.ineosq.com/hc/en-us/requests/new"><?php esc_html_e( 'Suggest a Feature', 'ineosq' ); ?></a>
				</div>
				<form action="" method="post">
					<button class="notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
					<input type="hidden" name="ineosq_hide_suggest_feature_banner_<?php echo esc_html( $plugin_options_name ); ?>" value="hide" />
					<?php wp_nonce_field( $plugin_info['Name'], 'ineosq_settings_nonce_name' ); ?>
				</form>
			</div>
		</div>
		<?php
	}
}

/**
 * Function display affiliate postbox
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_affiliate_postbox' ) ) {
	function ineosq_affiliate_postbox() {

		$dismissed = get_user_meta( get_current_user_id(), '_ineosq_affiliate_postbox_dismissed', true );

		if ( ! empty( $dismissed ) && strtotime( '-3 month' ) < $dismissed ) {
			return;
		}

		if ( isset( $_POST['ineosq_hide_affiliate_banner'] ) && check_admin_referer( 'ineosq_affiliate_postbox', 'ineosq_settings_nonce_name' ) ) {
			update_user_meta( get_current_user_id(), '_ineosq_affiliate_postbox_dismissed', strtotime( 'now' ) );
			return;
		}

		$ineosq_link = esc_url( 'https://ineosq.com/affiliate/?utm_source=plugin&utm_medium=settings&utm_campaign=affiliate_program' );
		?>
		<div id="ineosq-affiliate-postbox" class="postbox">
			<form action="" method="post">
				<button class="notice-dismiss ineosq_hide_settings_notice" title="<?php esc_html_e( 'Close notice', 'ineosq' ); ?>"></button>
				<input type="hidden" name="ineosq_hide_affiliate_banner" value="hide" />
				<?php wp_nonce_field( 'ineosq_affiliate_postbox', 'ineosq_settings_nonce_name' ); ?>
			</form>
			<p>BESTWEBSOFT</p>	
			<h3><?php esc_html_e( 'Affiliate Program', 'ineosq' ); ?></h3>
			<div class="ineosq-affiliate-get"><?php printf( esc_html__( 'Get %s', 'ineosq' ), '20%' ); ?></div>
			<div><?php esc_html_e( 'from each IneosQ plugin and theme sale you refer', 'ineosq' ); ?></div>
			<div class="ineosq-row">
				<div class="ineosq-cell">
					<img src="<?php echo esc_url( ineosq_menu_url( 'images/join-icon.svg' ) ); ?>" alt="" />
					<div><?php esc_html_e( 'Join affiliate program', 'ineosq' ); ?></div>
				</div>
				<div class="ineosq-cell">
					<img src="<?php echo esc_url( ineosq_menu_url( 'images/promote-icon.svg' ) ); ?>" alt="" />
					<div><?php esc_html_e( 'Promote and sell products', 'ineosq' ); ?></div>
				</div>
				<div class="ineosq-cell">
					<img src="<?php echo esc_url( ineosq_menu_url( 'images/earn-icon.svg' ) ); ?>" alt="" />
					<div><?php esc_html_e( 'Get commission!', 'ineosq' ); ?></div>
				</div>
			</div>
			<div class="clear"></div>
			<p>
				<a class="button" href="<?php echo esc_url( $ineosq_link ); ?>" target="_blank"><?php esc_html_e( 'Start Now', 'ineosq' ); ?></a>
			</p>
		</div>
		<?php
	}
}

/**
 * Function display settings notice
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_show_settings_notice' ) ) {
	function ineosq_show_settings_notice() {
		?>
		<div id="ineosq_save_settings_notice" class="updated fade below-h2" style="display:none;">
			<p>
				<strong><?php esc_html_e( 'Notice', 'ineosq' ); ?></strong>: <?php esc_html_e( "The plugin's settings have been changed.", 'ineosq' ); ?>
				<a class="ineosq_save_anchor" href="#ineosq-submit-button"><?php esc_html_e( 'Save Changes', 'ineosq' ); ?></a>
			</p>
		</div>
		<?php
	}
}

/**
 * Function for hide premium options
 *
 * @echo string
 */
if ( ! function_exists( 'ineosq_hide_premium_options' ) ) {
	function ineosq_hide_premium_options( $options ) {
		if ( ! isset( $options['hide_premium_options'] ) || ! is_array( $options['hide_premium_options'] ) ) {
			$options['hide_premium_options'] = array();
		}

		$options['hide_premium_options'][] = get_current_user_id();

		return array(
			'message' => esc_html__( 'You can always look at premium options by checking the "Pro Options" in the "Misc" tab.', 'ineosq' ),
			'options' => $options,
		);
	}
}

/**
 * Function for check checkbox for hide premium options
 *
 * @return bool
 */
if ( ! function_exists( 'ineosq_hide_premium_options_check' ) ) {
	function ineosq_hide_premium_options_check( $options ) {
		if ( ! empty( $options['hide_premium_options'] ) && in_array( get_current_user_id(), $options['hide_premium_options'] ) ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Function init fir dashboard
 */
if ( ! function_exists( 'ineosq_plugins_admin_init' ) ) {
	function ineosq_plugins_admin_init() {
		global $bstwbsftwppdtplgns_options;
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( isset( $_GET['ineosq_activate_plugin'] ) && check_admin_referer( 'ineosq_activate_plugin' . sanitize_text_field( wp_unslash( $_GET['ineosq_activate_plugin'] ) ) ) ) {

			$plugin = isset( $_GET['ineosq_activate_plugin'] ) ? sanitize_text_field( wp_unslash( $_GET['ineosq_activate_plugin'] ) ) : '';
			$result = activate_plugin( $plugin, '', is_network_admin() );
			if ( is_wp_error( $result ) ) {
				if ( 'unexpected_output' === $result->get_error_code() ) {
					$redirect = self_admin_url( 'admin.php?page=ineosq_panel&error=true&charsout=' . strlen( $result->get_error_data() ) . '&plugin=' . $plugin );
					wp_safe_redirect( add_query_arg( '_error_nonce', wp_create_nonce( 'plugin-activation-error_' . $plugin ), $redirect ) );
					exit();
				} else {
					wp_die( esc_html( $result ) );
				}
			}

			if ( ! is_network_admin() ) {
				$recent = (array) get_option( 'recently_activated' );
				unset( $recent[ $plugin ] );
				update_option( 'recently_activated', $recent );
			} else {
				$recent = (array) get_site_option( 'recently_activated' );
				unset( $recent[ $plugin ] );
				update_site_option( 'recently_activated', $recent );
			}
			/**
			* @deprecated 1.9.8 (15.12.2016)
			*/
			$is_main_page = in_array( $page, array( 'ineosq_panel', 'ineosq_themes', 'ineosq_system_status' ) );
			$tab          = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';

			if ( $is_main_page ) {
				$current_page = 'admin.php?page=' . $page;
			} else {
				$current_page = isset( $_GET['tab'] ) ? 'admin.php?page=' . $page . '&tab=' . $tab : 'admin.php?page=' . $page;
			}
			/*end deprecated */

			wp_safe_redirect( self_admin_url( esc_url( $current_page . '&activate=true' ) ) );
			exit();
		}

		if ( 'ineosq_panel' === $page || strpos( $page, '-ineosq-panel' ) ) {
			if ( ! session_id() ) {
				@session_start();
			}
		}

		ineosq_add_editor_buttons();

		if( empty( $bstwbsftwppdtplgns_options ) ) {
			if ( is_network_admin() ) {
				$bstwbsftwppdtplgns_options = get_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				$bstwbsftwppdtplgns_options = get_option( 'bstwbsftwppdtplgns_options' );
			}
		}

		if ( isset( $_POST['ineosq_hide_services_banner'] ) && isset( $_POST['ineosq_hide_services_banner_nonce_name'] ) && wp_verify_nonce( $_POST['ineosq_hide_services_banner_nonce_name'], 'ineosq_hide_services_banner_nonce_action' ) ) {
			$bstwbsftwppdtplgns_options['hide_services_banner_time'] = time();
			if ( is_network_admin() ) {
				update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			} else {
				update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
			}
		}

	}
}

/**
 * Function add scripts ans syles for dashboard
 */
if ( ! function_exists( 'ineosq_admin_enqueue_scripts' ) ) {
	function ineosq_admin_enqueue_scripts() {
		global $wp_scripts, $hook_suffix,
			$post_type,
			$ineosq_plugin_banner_go_pro, $ineosq_plugin_banner_timeout, $bstwbsftwppdtplgns_banner_array,
			$ineosq_shortcode_list,
			$wp_filesystem;

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		$jquery_ui_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.12.1';
		WP_Filesystem();
		if ( ! $wp_filesystem->exists( dirname( __FILE__ ) . '/css/jquery-ui-styles/' . $jquery_ui_version . '/' ) ) {
			$jquery_ui_version = '1.12.1';
		}
		if ( 'et_divi_options' !== $page ) {
			wp_enqueue_style( 'jquery-ui-style', ineosq_menu_url( 'css/jquery-ui-styles/' . $jquery_ui_version . '/jquery-ui.css', array(), $jquery_ui_version ) );
		}
		wp_enqueue_style( 'ineosq-admin-css', ineosq_menu_url( 'css/general_style.css' ), array(), '2.4.2' );
		wp_enqueue_script( 'ineosq-admin-scripts', ineosq_menu_url( 'js/general_script.js' ), array( 'jquery', 'jquery-ui-tooltip' ) );

		if ( in_array( $page, array( 'ineosq_panel', 'ineosq_themes', 'ineosq_system_status' ) ) || strpos( $page, '-ineosq-panel' ) ) {
			wp_enqueue_style( 'ineosq_menu_style', ineosq_menu_url( 'css/style.css' ), array(), '2.4.2' );
			wp_enqueue_script( 'ineosq_menu_script', ineosq_menu_url( 'js/ineosq_menu.js' ), array(), '2.4.2', true );
			wp_enqueue_script( 'theme-install' );
			add_thickbox();
			wp_enqueue_script( 'plugin-install' );
		}

		if ( 'plugins.php' === $hook_suffix || ! empty( $ineosq_plugin_banner_timeout ) ) {
			if ( ! empty( $ineosq_plugin_banner_go_pro ) || ! empty( $ineosq_plugin_banner_timeout ) ) {
				wp_enqueue_script( 'ineosq_menu_cookie', ineosq_menu_url( 'js/c_o_o_k_i_e.js' ) );

				if ( ! empty( $ineosq_plugin_banner_go_pro ) ) {

					foreach ( $bstwbsftwppdtplgns_banner_array as $value ) {
						if ( isset( $ineosq_plugin_banner_go_pro[ $value[0] ] ) && ! isset( $_COOKIE[ $value[0] ] ) ) {
							$prefix = $ineosq_plugin_banner_go_pro[ $value[0] ]['prefix'];

							$script = "(function($) {
								$(document).ready( function() {
									var hide_message = $.cookie( '" . $prefix . "_hide_banner_on_plugin_page' );
									if ( hide_message === 'true' ) {
										$( '." . $prefix . "_message' ).css( 'display', 'none' );
									} else {
										$( '." . $prefix . "_message' ).css( 'display', 'block' );
									};
									$( '." . $prefix . "_close_icon' ).click( function() {
										$( '." . $prefix . "_message' ).css( 'display', 'none' );
										$.cookie( '" . $prefix . "_hide_banner_on_plugin_page', 'true', { expires: 32, secure: true } );
									});
								});
							})(jQuery);";

							wp_register_script( $prefix . '_hide_banner_on_plugin_page', '' );
							wp_enqueue_script( $prefix . '_hide_banner_on_plugin_page' );
							wp_add_inline_script( $prefix . '_hide_banner_on_plugin_page', sprintf( $script ) );
							break;
						}
					}
				}

				if ( ! empty( $ineosq_plugin_banner_timeout ) ) {
					$script = '(function($) {
							$(document).ready( function() {';

					foreach ( $ineosq_plugin_banner_timeout as $banner_value ) {
						if ( 0 == $banner_value['license_expired'] ) { 
							$script .= "var hide_message = $.cookie( '" . $banner_value['prefix'] . "_timeout_hide_banner_on_plugin_page' );
							if ( hide_message === 'true' ) {
									$( '." . $banner_value['prefix'] . "_message_timeout' ).css( 'display', 'none' );
								} else {
									$( '." . $banner_value['prefix'] . "_message_timeout' ).css( 'display', 'block' );
								}
								$( '." . $banner_value['prefix'] . "_close_icon' ).click( function() {
									$( '." . $banner_value['prefix'] . "_message_timeout' ).css( 'display', 'none' );
									$.cookie( '" . $banner_value['prefix'] . "_timeout_hide_banner_on_plugin_page', 'true', { expires: 30, secure: true } );
								});";
						} elseif ( 1 == $banner_value['license_expired'] ) { 
							$script .= "$( '." . $banner_value['prefix'] . "_close_icon' ).click( function() {
									$( '." . $banner_value['prefix'] . "_message_timeout' ).parents( '.error' ).css( 'display', 'none' );
								});";
						}
					}

					$script .= '});
						})(jQuery);';

					wp_register_script( 'plugin_banner_timeout_hide', '' );
					wp_enqueue_script( 'plugin_banner_timeout_hide' );
					wp_add_inline_script( 'plugin_banner_timeout_hide', sprintf( $script ) );
				}
			}

			if ( ! defined( 'DOING_AJAX' ) ) {
				wp_enqueue_style( 'ineosq-modal-css', ineosq_menu_url( 'css/modal.css' ) );

				ineosq_add_deactivation_feedback_dialog_box();
			}
		}

		if ( ! empty( $ineosq_shortcode_list ) ) {
			/* TinyMCE Shortcode Plugin */
			$script = "var ineosq_shortcode_button = {
					'label': '" . esc_attr__( 'Add INEOSQ Shortcode', 'ineosq' ) . "',
					'title': '" . esc_attr__( 'Add INEOSQ Plugins Shortcode', 'ineosq' ) . "',
					'function_name': [";
			foreach ( $ineosq_shortcode_list as $value ) {
				if ( isset( $value['js_function'] ) ) {
					$script .= "'" . $value['js_function'] . "',";
				}
			}
			$script .= ']
				};';
			wp_register_script( 'ineosq_shortcode_button', '' );
			wp_enqueue_script( 'ineosq_shortcode_button' );
			wp_add_inline_script( 'ineosq_shortcode_button', sprintf( $script ) );

			/* TinyMCE Shortcode Plugin */
			if ( isset( $post_type ) && in_array( $post_type, array( 'post', 'page' ) ) ) {
				$tooltip_args = array(
					'tooltip_id'   => 'ineosq_shortcode_button_tooltip',
					'css_selector' => '.mce-ineosq_shortcode_button',
					'actions'      => array(
						'click'  => false,
						'onload' => true,
					),
					'content'      => '<h3>' . esc_html__( 'Add shortcode', 'ineosq' ) . '</h3><p>' . esc_html__( "Add IneosQ plugins' shortcodes using this button.", 'ineosq' ) . '</p>',
					'position'     => array(
						'edge' => 'right',
					),
					'set_timeout'  => 2000,
				);
				ineosq_add_tooltip_in_admin( $tooltip_args );
			}
		}
	}
}

/**
* add styles and scripts for Ineosq_Settings_Tabs
*
* @since 1.9.8
*/
if ( ! function_exists( 'ineosq_enqueue_settings_scripts' ) ) {
	function ineosq_enqueue_settings_scripts() {
		wp_enqueue_script( 'jquery-ui-resizable' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_style( 'ineosq-modal-css', ineosq_menu_url( 'css/modal.css' ), array(), '2.4.2' );
	}
}

/**
 * Function add syles into admin head
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_plugins_admin_head' ) ) {
	function ineosq_plugins_admin_head() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( $page === 'ineosq_panel' ) {
			?>
			<noscript>
				<style type="text/css">
					.ineosq_product_button {
						display: inline-block;
					}
				</style>
			</noscript>
			<?php
		}
	}
}

/**
 * Function add syles into admin footer
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_plugins_admin_footer' ) ) {
	function ineosq_plugins_admin_footer() {
		ineosq_shortcode_media_button_popup();
	}
}

/**
 * Function add style and scripts for older version
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_plugins_include_codemirror' ) ) {
	function ineosq_plugins_include_codemirror() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.9.0', '>=' ) ) {
			wp_enqueue_style( 'wp-codemirror' );
			wp_enqueue_script( 'wp-codemirror' );
		} else {
			wp_enqueue_style( 'codemirror.css', ineosq_menu_url( 'css/codemirror.css' ), array(), '2.4.2' );
			wp_enqueue_script( 'codemirror.js', ineosq_menu_url( 'js/codemirror.js' ), array( 'jquery' ), '2.4.2' );
		}

	}
}

/**
 * Tooltip block
 */
if ( ! function_exists( 'ineosq_add_tooltip_in_admin' ) ) {
	function ineosq_add_tooltip_in_admin( $tooltip_args = array() ) {
		new INEOSQ_Admin_Tooltip( $tooltip_args );
	}
}

/**
 * Class for Tooltip
 *
 * @since 1.9.8
 */
if ( ! class_exists( 'INEOSQ_Admin_Tooltip' ) ) {
	class INEOSQ_Admin_Tooltip {
		private $tooltip_args;

		public function __construct( $tooltip_args ) {
			global $bstwbsftwppdtplgns_tooltip_script_add;

			/* Default arguments */
			$tooltip_args_default = array(
				'tooltip_id'   => false,
				'css_selector' => false,
				'actions'      => array(
					'click'  => true,
					'onload' => false,
				),
				'buttons'      => array(
					'close' => array(
						'type' => 'dismiss',
						'text' => esc_html__( 'Close', 'ineosq' ),
					),
				),
				'position'     => array(
					'edge'     => 'top',
					'align'    => 'center',
					'pos-left' => 0,
					'pos-top'  => 0,
					'zindex'   => 10000,
				),
				'set_timeout'  => 0,
			);
			$tooltip_args         = array_merge( $tooltip_args_default, $tooltip_args );
			/* Check that our merged array has default values */
			foreach ( $tooltip_args_default as $arg_key => $arg_value ) {
				if ( is_array( $arg_value ) ) {
					foreach ( $arg_value as $key => $value ) {
						if ( ! isset( $tooltip_args[ $arg_key ][ $key ] ) ) {
							$tooltip_args[ $arg_key ][ $key ] = $tooltip_args_default[ $arg_key ][ $key ];
						}
					}
				}
			}
			/* Check if tooltip is dismissed */
			if ( true === $tooltip_args['actions']['onload'] ) {
				if ( in_array( $tooltip_args['tooltip_id'], array_filter( explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) ) ) ) ) {
					$tooltip_args['actions']['onload'] = false;
				}
			}
			/* Check entered data */
			if ( false === $tooltip_args['tooltip_id'] || false === $tooltip_args['css_selector'] || ( false === $tooltip_args['actions']['click'] && false === $tooltip_args['actions']['onload'] ) ) {
				/* if not enough data to output a tooltip or both actions (click, onload) are false */
				return;
			} else {
				/* check position */
				if ( ! in_array( $tooltip_args['position']['edge'], array( 'left', 'right', 'top', 'bottom' ) ) ) {
					$tooltip_args['position']['edge'] = 'top';
				}
				if ( ! in_array( $tooltip_args['position']['align'], array( 'top', 'bottom', 'left', 'right', 'center' ) ) ) {
					$tooltip_args['position']['align'] = 'center';
				}
			}
			/* fix position */
			switch ( $tooltip_args['position']['edge'] ) {
				case 'left':
				case 'right':
					switch ( $tooltip_args['position']['align'] ) {
						case 'top':
						case 'bottom':
							$tooltip_args['position']['align'] = 'center';
							break;
					}
					break;
				case 'top':
				case 'bottom':
					if ( 'left' === $tooltip_args['position']['align'] ) {
						$tooltip_args['position']['pos-left'] -= 65;
					}
					break;
			}
			$this->tooltip_args = $tooltip_args;
			/* add styles and scripts */
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			/* add script that displays our tooltip */
			if ( ! isset( $bstwbsftwppdtplgns_tooltip_script_add ) ) {
				wp_enqueue_script( 'ineosq-tooltip-script', ineosq_menu_url( 'js/ineosq_tooltip.js' ), array(), '2.4.2' );
				$bstwbsftwppdtplgns_tooltip_script_add = true;
			}
			$tooltip_args = $this->tooltip_args;

			$script = '(function($) {
					$(document).ready( function() {
						$.ineosqTooltip( ' . wp_json_encode( $tooltip_args ) . ' );
					})
				})(jQuery);';
			wp_register_script( 'ineosq-tooltip-script-single-' . $this->tooltip_args['tooltip_id'], '' );
			wp_enqueue_script( 'ineosq-tooltip-script-single-' . $this->tooltip_args['tooltip_id'] );
			wp_add_inline_script( 'ineosq-tooltip-script-single-' . $this->tooltip_args['tooltip_id'], sprintf( $script ) );
		}
	}
}

/**
 * Function display confirm
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_form_restore_default_confirm' ) ) {
	function ineosq_form_restore_default_confirm( $plugin_basename ) {
		?>
		<div>
			<p><?php esc_html_e( 'Are you sure you want to restore default settings?', 'ineosq' ); ?></p>
			<form method="post" action="">
				<p>
					<button class="button button-primary" name="ineosq_restore_confirm"><?php esc_html_e( 'Yes, restore all settings', 'ineosq' ); ?></button>
					<button class="button" name="ineosq_restore_deny"><?php esc_html_e( 'No, go back to the settings page', 'ineosq' ); ?></button>
					<?php wp_nonce_field( $plugin_basename, 'ineosq_settings_nonce_name' ); ?>
				</p>
			</form>
		</div>
		<?php
	}
}

/**
 * Function for shortcode
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_add_editor_buttons' ) ) {
	function ineosq_add_editor_buttons() {
		global $ineosq_shortcode_list;
		if ( ! empty( $ineosq_shortcode_list ) && current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
			add_filter( 'mce_external_plugins', 'ineosq_add_buttons' );
			add_filter( 'mce_buttons', 'ineosq_register_buttons' );
		}
	}
}

/**
 * Function add button for editor
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_add_buttons' ) ) {
	function ineosq_add_buttons( $plugin_array ) {
		$plugin_array['add_ineosq_shortcode'] = ineosq_menu_url( 'js/shortcode-button.js' );
		return $plugin_array;
	}
}

/**
 * Function register button for editor
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_register_buttons' ) ) {
	function ineosq_register_buttons( $buttons ) {
		array_push( $buttons, 'add_ineosq_shortcode' ); /* dropcap', 'recentposts */
		return $buttons;
	}
}

/**
 * Function Generate inline content for the popup window when the "ineosq shortcode" button is clicked
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_shortcode_media_button_popup' ) ) {
	function ineosq_shortcode_media_button_popup() {
		global $ineosq_shortcode_list;

		if ( ! empty( $ineosq_shortcode_list ) ) {
			?>
			<div id="ineosq_shortcode_popup" style="display:none;">
				<div id="ineosq_shortcode_popup_block">
					<div id="ineosq_shortcode_select_plugin">
						<h4><?php esc_html_e( 'Plugin', 'ineosq' ); ?></h4>
						<select name="ineosq_shortcode_select" id="ineosq_shortcode_select">
							<?php foreach ( $ineosq_shortcode_list as $key => $value ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['name'] ); ?></option>
							<?php } ?>
						</select>
					</div>
					<div class="clear"></div>
					<div id="ineosq_shortcode_content">
						<h4><?php esc_html_e( 'Shortcode settings', 'ineosq' ); ?></h4>
						<?php echo wp_kses_post( apply_filters( 'ineosq_shortcode_button_content', '' ) ); ?>
					</div>
					<div class="clear"></div>
					<div id="ineosq_shortcode_content_bottom">
						<p><?php esc_html_e( 'The shortcode will be inserted', 'ineosq' ); ?></p>
						<div id="ineosq_shortcode_block"><div id="ineosq_shortcode_display"></div></div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}

/**
 * Output shortcode in a special block
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_shortcode_output' ) ) {
	function ineosq_shortcode_output( $shortcode ) {
		?>
		<span class="ineosq_shortcode_output"><input type="text" onfocus="this.select();" readonly="readonly" value="<?php echo esc_attr( $shortcode ); ?>" class="large-text ineosq_no_bind_notice"></span>
		<?php
	}
}

/**
 * Output tooltip
 *
 * @since 1.9.8
 * @param   string   $content  - HTML content for the tooltip
 * @param   string   $class  - Can be standart "ineosq-hide-for-mobile" (tooltip will be hidden in 782px) and "ineosq-auto-width" (need for img) or some custom class.
 */
if ( ! function_exists( 'ineosq_add_help_box' ) ) {
	function ineosq_add_help_box( $content, $class = '' ) {
		return '<span class="ineosq_help_box dashicons dashicons-editor-help ' . $class . ' hide-if-no-js">
			<span class="ineosq_hidden_help_text">' . $content . '</span>
		</span>';
	}
}

/**
 * Function add help tab
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_help_tab' ) ) {
	function ineosq_help_tab( $screen, $args ) {
		$url = ( ! empty( $args['section'] ) ) ? 'https://support.ineosq.com/hc/en-us/sections/' . $args['section'] : 'https://support.ineosq.com/';

		$content = '<p><a href="' . esc_url( $url ) . '" target="_blank">' . __( 'Visit Help Center', 'ineosq' ) . '</a></p>';

		$screen->add_help_tab(
			array(
				'id'      => $args['id'] . '_help_tab',
				'title'   => esc_html__( 'FAQ', 'ineosq' ),
				'content' => wp_kses_post( $content ),
			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'ineosq' ) . '</strong></p>' .
			'<p><a href="https://ineosq.com/documentation/" target="_blank">' . esc_html__( 'Documentation', 'ineosq' ) . '</a></p>' .
			'<p><a href="https://www.youtube.com/user/ineosq/playlists?flow=grid&sort=da&view=1" target="_blank">' . esc_html__( 'Video Instructions', 'ineosq' ) . '</a></p>' .
			'<p><a href="https://support.ineosq.com/hc/en-us/requests/new" target="_blank">' . esc_html__( 'Submit a Request', 'ineosq' ) . '</a></p>'
		);
	}
}

/**
 * Function add css and js
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_enqueue_custom_code_css_js' ) ) {
	function ineosq_enqueue_custom_code_css_js() {
		global $bstwbsftwppdtplgns_options;

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			$bstwbsftwppdtplgns_options = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
		}

		if ( ! empty( $bstwbsftwppdtplgns_options['custom_code'] ) ) {
			$is_multisite = is_multisite();
			if ( $is_multisite ) {
				$blog_id = get_current_blog_id();
			}

			if ( ! $is_multisite && ! empty( $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.css'] ) ) {
				wp_enqueue_style( 'ineosq-custom-style', $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.css'], array(), '2.4.2' );
			} elseif ( $is_multisite && ! empty( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.css'] ) ) {
				wp_enqueue_style( 'ineosq-custom-style', $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.css'], array(), '2.4.2' );
			}

			if ( ! $is_multisite && ! empty( $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.js'] ) ) {
				wp_enqueue_script( 'ineosq-custom-style', $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.js'], array(), '2.4.2' );
			} elseif ( $is_multisite && ! empty( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.js'] ) ) {
				wp_enqueue_script( 'ineosq-custom-style', $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.js'], array(), '2.4.2' );
			}
		}
	}
}

/**
 * Function add custom php code
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_enqueue_custom_code_php' ) ) {
	function ineosq_enqueue_custom_code_php() {
		if ( is_admin() ) {
			return;
		}

		global $bstwbsftwppdtplgns_options;

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			$bstwbsftwppdtplgns_options = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
		}

		if ( ! empty( $bstwbsftwppdtplgns_options['custom_code'] ) ) {

			$is_multisite = is_multisite();
			if ( $is_multisite ) {
				$blog_id = get_current_blog_id();
			}

			if ( ! $is_multisite && ! empty( $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.php'] ) ) {
				if ( file_exists( $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.php'] ) ) {
					if ( ! defined( 'INEOSQ_GLOBAL' ) ) {
						define( 'INEOSQ_GLOBAL', true );
					}
					require_once $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.php'];
				} else {
					unset( $bstwbsftwppdtplgns_options['custom_code']['ineosq-custom-code.php'] );
					if ( $is_multisite ) {
						update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					} else {
						update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					}
				}
			} elseif ( $is_multisite && ! empty( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.php'] ) ) {
				if ( file_exists( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.php'] ) ) {
					if ( ! defined( 'INEOSQ_GLOBAL' ) ) {
						define( 'INEOSQ_GLOBAL', true );
					}
					require_once $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.php'];
				} else {
					unset( $bstwbsftwppdtplgns_options['custom_code'][ $blog_id ]['ineosq-custom-code.php'] );
					if ( $is_multisite ) {
						update_site_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					} else {
						update_option( 'bstwbsftwppdtplgns_options', $bstwbsftwppdtplgns_options );
					}
				}
			}
		}
	}
}

/**
 * Function delete plugin
 *
 * @since 1.9.8
 */
if ( ! function_exists( 'ineosq_delete_plugin' ) ) {
	function ineosq_delete_plugin( $basename ) {
		global $bstwbsftwppdtplgns_options;

		$is_multisite = is_multisite();
		if ( $is_multisite ) {
			$blog_id = get_current_blog_id();
		}

		if ( ! isset( $bstwbsftwppdtplgns_options ) ) {
			$bstwbsftwppdtplgns_options = ( $is_multisite ) ? get_site_option( 'bstwbsftwppdtplgns_options' ) : get_option( 'bstwbsftwppdtplgns_options' );
		}

		/* remove ineosq_menu versions */
		unset( $bstwbsftwppdtplgns_options['ineosq_menu']['version'][ $basename ] );
		/* remove track usage data */
		if ( isset( $bstwbsftwppdtplgns_options['ineosq_menu']['track_usage']['products'][ $basename ] ) ) {
			unset( $bstwbsftwppdtplgns_options['ineosq_menu']['track_usage']['products'][ $basename ] );
		}
		/* if empty ['ineosq_menu']['version'] - there is no other ineosq plugins - delete all */
		if ( empty( $bstwbsftwppdtplgns_options['ineosq_menu']['version'] ) ) {
			/* remove options */
			if ( $is_multisite ) {
				delete_site_option( 'bstwbsftwppdtplgns_options' );
			} else {
				delete_option( 'bstwbsftwppdtplgns_options' );
			}

			/* remove custom_code */
			if ( $is_multisite ) {
				global $wpdb;
				$old_blog = $wpdb->blogid;
				/* Get all blog ids */
				$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blogids as $blog_id ) {
					switch_to_blog( $blog_id );
					$upload_dir = wp_upload_dir();
					$folder     = $upload_dir['basedir'] . '/ineosq-custom-code';
					if ( file_exists( $folder ) && is_dir( $folder ) ) {
						array_map( 'unlink', glob( "$folder/*" ) );
						rmdir( $folder );
					}
				}
				switch_to_blog( $old_blog );
			} else {
				$upload_dir = wp_upload_dir();
				$folder     = $upload_dir['basedir'] . '/ineosq-custom-code';
				if ( file_exists( $folder ) && is_dir( $folder ) ) {
					array_map( 'unlink', glob( "$folder/*" ) );
					rmdir( $folder );
				}
			}
		}
	}
}

add_action( 'admin_init', 'ineosq_plugins_admin_init' );
add_action( 'admin_enqueue_scripts', 'ineosq_admin_enqueue_scripts' );
add_action( 'admin_head', 'ineosq_plugins_admin_head' );
add_action( 'admin_footer', 'ineosq_plugins_admin_footer' );

add_action( 'admin_notices', 'ineosq_admin_notices', 30 );

add_action( 'wp_enqueue_scripts', 'ineosq_enqueue_custom_code_css_js', 20 );

ineosq_enqueue_custom_code_php();
