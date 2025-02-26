<?php
/**
 * Display content of "allow list" tab on settings page
 * @subpackage Captcha PRO
 * @since 1.6.4
 * @version 1.0.2
 */

if ( ! class_exists( 'Cptch_allowlist' ) ) {
	if ( ! class_exists( 'WP_List_Table' ) )
		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	class Cptch_allowlist extends WP_List_Table {
		private
			$disable_list,
			$basename,
			$order_by,
			$per_page,
			$la_info,
			$paged,
			$order,
			$s,
			$date_filter_options,
			$filter_by,
			$last_filtred_by;

		/**
		* Constructor of class
		*/
		function __construct( $plugin_basename, $limit_attempts_info ) {
			global $cptch_options;

			parent::__construct( array(
				'singular'	=> 'IP',
				'plural'	=> 'IP',
				'ajax'		=> true,
				)
			);
			$this->basename		= $plugin_basename;
			$this->la_info		= $limit_attempts_info;

			/**
			* options for filtring
			*/
			$this->date_filter_options = array(
				'all'	=> __( 'All dates', 'ineosq-captcha-pro' ),
				'day'	=> __( 'Last 24 hours', 'ineosq-captcha-pro' ),
				'week'	=> __( 'Last week', 'ineosq-captcha-pro' ),
				'month'	=> __( 'Last month', 'ineosq-captcha-pro' ),
				'year'	=> __( 'Last year', 'ineosq-captcha-pro' )
			);

			/**
			* keep in mind what was the last filtring option to compare it
			* with the new filtring options and choose the differnt one
			*/
			if (
				! empty( $_POST['cptch_last_filtred_by'] ) &&
				in_array( $_POST['cptch_last_filtred_by'], array_keys( $this->date_filter_options ) )
			) {
				$this->last_filtred_by = $_POST['cptch_last_filtred_by'];
			} else {
				$this->last_filtred_by = 'all';
			}

			if ( ! empty( $_POST['cptch_date_filter'] ) )
				$filter_array = array_filter( array_unique( $_POST['cptch_date_filter'] ), array( $this, 'get_date_filter_values' ) );

			/**
			* Due to the first element's key either be 0 or 1, $filter_array[ key( $filter_array ) ] should be used.
			* It gives the ability of taking the first element of the array
			*/
			$this->filter_by = ! empty( $filter_array ) ? $filter_array[ key( $filter_array ) ] : $this->last_filtred_by;

			$this->disable_list = ( 1 == $cptch_options['use_limit_attempts_allowlist'] ) && 'active' == $this->la_info['status'];
		}

		/**
		 * Display content
		 * @return void
		 */
		function display_content() { ?>
			<h1>
                <?php _e( 'Captcha Pro Allow List', 'ineosq-captcha-pro' ); ?>
                <?php if ( ! $this->disable_list ) { ?>
                    <form method="post" action="admin.php?page=captcha-allowlist.php" style="display: inline;">
                        <button class="page-title-action add-new-h2 hide-if-no-js" name="cptch_show_allowlist_form" value="on"<?php echo ( isset( $_POST['cptch_add_to_allowlist'] ) ) ? ' style="display: none;"' : ''; ?>><?php _e( 'Add New', 'ineosq-captcha-pro' ); ?></button>
                    </form>
                <?php } ?>
            </h1>
			<?php if ( isset( $_SERVER ) ) {
				$sever_vars = array( 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
				foreach ( $sever_vars as $var ) {
					if ( isset( $_SERVER[ $var ] ) && ! empty( $_SERVER[ $var ] ) ) {
						if ( filter_var( $_SERVER[ $var ], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_IPV4  ) ) {
							$my_ip = $_SERVER[ $var ];
							break;
						} else { /* if proxy */
							$ip_array = explode( ',', $_SERVER[ $var ] );
							if ( is_array( $ip_array ) && ! empty( $ip_array ) && filter_var( $ip_array[0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_IPV4  ) ) {
								$my_ip = $ip_array[0];
								break;
							}
						}
					}
				}
			}

			$this->display_notices();
			$this->prepare_items();
			$limit_attempts_info = $this->get_limit_attempts_info();
			$disabled = $limit_attempts_info['disabled'] ? ' disabled="disabled"' : '';

			if ( ! ( isset( $_REQUEST['cptch_show_allowlist_form'] ) || isset( $_REQUEST['cptch_add_to_allowlist'] ) ) ) { ?>
				<form method="post" action="admin.php?page=captcha-allowlist.php">
					<?php if ( $this->disable_list ) { ?>
						<p><?php echo __( 'Limit Attempts allow list is activated.', 'ineosq-captcha-pro' ) .
							' <a href="admin.php?page=limit-attempts-black-and-allowlist.php">' . __( 'View the allow list', 'ineosq-captcha-pro' ) . '</a> ' .
							__( 'or', 'ineosq-captcha-pro' ) .
							' <a href="admin.php?page=ineosq_captcha_pro.php">' . __( 'Enable default captcha allow list', 'ineosq-captcha-pro' ) . '</a>'; ?></p>
					<?php } else { ?>
						<div>
							<input type="submit" name="cptch_load_limit_attempts_allowlist" class="button" value="<?php _e( 'Load IP Address(-es)', 'ineosq-captcha-pro' ); ?>" style="float: left;" <?php echo $disabled; ?> />
							<noscript>
								<div class="alignleft" style="margin: 0 10px;">
									<input type="submit" name="cptch_save_add_ip_form_button" class="button-primary" value="<?php _e( 'Save changes', 'ineosq-captcha-pro' ); ?>" />
								</div>
							</noscript>
							<div class="clear"></div>
						</div>
						<div class="ineosq_info"><?php _e( 'Load IP addresses from the "Limit Attempts" plugin.', 'ineosq-captcha-pro' ); ?> <?php echo ! empty( $disabled ) ? $limit_attempts_info['link'] : ''; ?></div>
						<?php wp_nonce_field( $this->basename, 'cptch_nonce_name' ); ?>
						<input type="hidden" name="cptch_save_add_ip_form" value="1"/>
					<?php } ?>
				</form>
			<?php } ?>
			<form class="form-table cptch_allowlist_form" method="post" action="admin.php?page=captcha-allowlist.php" style="margin: 10px 0;<?php echo ! ( isset( $_REQUEST['cptch_show_allowlist_form'] ) || isset( $_REQUEST['cptch_add_to_allowlist'] ) ) ? 'display: none;': ''; ?>">
				<label><?php _e( 'IP to allow list', 'ineosq-captcha-pro' ) ?></label>
				<br />
				<textarea rows="2" cols="32" name="cptch_add_to_allowlist"></textarea>
				<?php if ( isset( $my_ip ) ) { ?>
					<br />
					<label>
						<input type="checkbox" name="cptch_add_to_allowlist_my_ip" value="1" />
						<?php _e( 'My IP', 'ineosq-captcha-pro' ); ?>
						<input type="hidden" name="cptch_add_to_allowlist_my_ip_value" value="<?php echo $my_ip; ?>" />
					</label>
				<?php } ?>
				<div class="ineosq_info">
					<div style="line-height: 2;">
						<?php _e( "Allowed formats", 'ineosq-captcha-pro' ); ?>:
						<code>
							192.168.0.1, 192.168.0., 192.168., 192., 192.168.0.1/8, 123.126.12.243-185.239.34.54
						</code>
					</div>
					<div style="line-height: 2;"><?php _e( "Allowed diapason", 'ineosq-captcha-pro' ); ?>:<code>0.0.0.0 - 255.255.255.255</code></div>
					<div style="line-height: 2;">
						<?php _e( "Allowed separators", 'ineosq-captcha-pro' ); ?>: <?php _e( "a comma", 'ineosq-captcha-pro' ); ?> (<code>,</code>), <?php _e( 'semicolon', 'ineosq-captcha-pro' ); ?> (<code>;</code>), <?php _e( 'ordinary space, tab, new line or carriage return.', 'ineosq-captcha-pro' ); ?>
					</div>
				</div>
				<br />
				<label><?php _e( 'Reason', 'ineosq-captcha-pro' ) ?></label>
				<br />
				<textarea rows="2" cols="32" name="cptch_add_to_allowlist_reason"></textarea>
				<div class="ineosq_info">
					<?php _e( "Allowed separators for reasons", 'ineosq-captcha-pro' ); ?>: <?php _e( "a comma", 'ineosq-captcha-pro' )?> (<code>,</code>), <?php _e( 'semicolon', 'ineosq-captcha-pro' ); ?> (<code>;</code>), <?php _e( 'tab, new line or carriage return.', 'ineosq-captcha-pro' ); ?>
				</div>
				<p>
					<input type="submit" class="button-secondary" value="<?php _e( 'Add IP to allow list', 'ineosq-captcha-pro' ); ?>" />
					<?php wp_nonce_field( $this->basename, 'cptch_nonce_name' ); ?>
				</p>
			</form>
			<?php if ( ! $this->disable_list ) { ?>
				<form id="cptch_allowlist_search" method="post" action="admin.php?page=captcha-allowlist.php">
					<?php $this->search_box( __( 'Search item', 'ineosq-captcha-pro' ), 'search_allowlisted_ip' );
					wp_nonce_field( $this->basename, 'cptch_nonce_name' ); ?>
				</form>
				<form id="cptch_allowlist" method="post" action="admin.php?page=captcha-allowlist.php">
					<?php $this->display();
					wp_nonce_field( $this->basename, 'cptch_nonce_name' ); ?>
				</form>
			<?php }
		}

		/**
		* Function to prepare data before display
		* @return void
		*/
		function prepare_items() {

			if ( isset( $_GET['orderby'] ) && in_array( $_GET['orderby'], array_keys( $this->get_sortable_columns() ) ) ) {
				switch ( $_GET['orderby'] ) {
					case 'ip':
						$this->order_by = 'ip_from_int';
						break;
					case 'ip_from':
						$this->order_by = 'ip_from_int';
						break;
					case 'ip_to':
						$this->order_by = 'ip_to_int';
						break;
					default:
						$this->order_by = esc_sql( $_GET['orderby'] );
						break;
				}
			} else {
				$this->order_by = 'add_time';
			}
			$this->order		= isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ) ) ? $_REQUEST['order'] : '';
			$this->paged		= isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : '';
			$this->s			= isset( $_REQUEST['s'] ) ? esc_html( trim( $_REQUEST['s'] ) ) : '';
			$this->per_page		= $this->get_items_per_page( 'cptch_per_page', 20 );

			$columns				= $this->get_columns();
			$hidden					= array();
			$sortable				= $this->get_sortable_columns();
			$primary				= 'ip';
			$this->_column_headers	= array( $columns, $hidden, $sortable, $primary );
			$this->items			= $this->get_content();
			$this->set_pagination_args( array(
					'total_items'	=> $this->get_items_number(),
					'per_page'		=> 20,
				)
			);
		}
		/**
		* Function to show message if empty list
		* @return void
		*/
		function no_items() {
			$label = isset( $_REQUEST['s'] ) ? __( 'Nothing found', 'ineosq-captcha-pro' ) : __( 'No IP in the allow list', 'ineosq-captcha-pro' ); ?>
			<p><?php echo $label; ?></p>
		<?php }

		function get_columns() {
			$disabled = $this->disable_list ? ' disabled="disabled"' : '';
			$columns = array(
				'cb'			=> '<input type="checkbox"' . $disabled . '/>',
				'ip'			=> __( 'IP Address', 'ineosq-captcha-pro' ),
				'ip_from_to'	=> __( 'Range from / to', 'ineosq-captcha-pro' ),
				'add_reason'	=> __( 'Reason', 'ineosq-captcha-pro' ),
				'add_time'		=> __( 'Date Added', 'ineosq-captcha-pro' )
			);
			return $columns;
		}
		/**
		 * Get a list of sortable columns.
		 * @return array list of sortable columns
		 */
		function get_sortable_columns() {
			$sortable_columns = array(
				'ip'			=> array( 'ip', true ),
				'ip_from_to'	=> array( 'ip_from_to', false ),
				'add_time'		=> array( 'add_time', false )
			);
			return $sortable_columns;
		}
		/**
		 * Fires when the default column output is displayed for a single row.
		 * @param      string    $column_name      The custom column's name.
		 * @param      array     $item             The cuurrent letter data.
		 * @return    void
		 */
		function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'ip':
				case 'add_time':
				case 'add_reason':
					return $item[ $column_name ];
				case 'ip_from_to':
					return $item['ip_from'] . ' - ' . $item['ip_to'];
				default:
					/* Show whole array for bugfix */
					return print_r( $item, true );
			}
		}
		/**
		 * Function to manage content of column with checboxes
		 * @param     array     $item        The cuurrent letter data.
		 * @return    string                  with html-structure of <input type=['checkbox']>
		 */
		function column_cb( $item ) {
			/* customize displaying cb collumn */
			return sprintf(
				'<input type="checkbox" name="id[]" value="%s"/>', $item['id']
			);
		}
		/**
		 * Function to manage content of column with IP-adresses
		 * @param     array     $item        The cuurrent letter data.
		 * @return    string                  with html-structure of <input type=['checkbox']>
		 */
		function column_ip( $item ) {
			$input_hidden   = sprintf( '<input type="hidden" name="cptch_ip_of_%s" value="%s">', $item['id'], $item['ip'] );
			$order_by	= empty( $this->order_by ) ? '' : "&orderby={$this->order_by}";
			$order		= empty( $this->order )    ? '' : "&order={$this->order}";
			$paged		= empty( $this->paged )    ? '' : "&paged={$this->paged}";
			$s			= empty( $this->s )        ? '' : "&s={$this->s}";
			$url		= "?page=captcha-allowlist.php&cptch_remove={$item['id']}{$order_by}{$order}{$paged}{$s}";
			$actions = array(
				'delete' => '<a href="' . wp_nonce_url( $url, "cptch_nonce_remove_{$item['id']}" ) . '">' . __( 'Delete', 'ineosq-captcha-pro' ) . '</a>'
			);
			return sprintf('%1$s%2$s %3$s', $input_hidden, $item['ip'], $this->row_actions( $actions ) );
		}

		function column_add_reason( $item ) {
			/* customize displaying add_reason collumn */
			$edit_link_class = $textarea_class = $button_class = $display_class = '';
			$edit_link_style = $textarea_style = '';

			if ( ! empty( $_REQUEST['cptch_edit_add_reason'] ) && $item['id'] == $_REQUEST['cptch_edit_add_reason'] ) {
				$display_class = 'hidden';
				$edit_link_style = 'display:none';
			} else {
				$textarea_class = $button_class = 'hidden';
				$textarea_style = 'display:none';
			}

			$nonce_field = wp_nonce_field( 'cptch_edit_' . $item['id'], 'cptch_edit_allowlist_reason-'. $item['id'], false, false );

			$display = sprintf(
				'<span class="cptch-add-reason %s">%s</span>',
				$display_class,
				nl2br( $item['add_reason'] )
			);

			$textarea = sprintf(
				'<textarea name="cptch-reason-%s" class="cptch-add-reason-textarea cptch-autoexpand %s" rows="3" data-min-rows="3" style="%s">%s</textarea>',
				$item['id'],
				$textarea_class,
				$textarea_style,
				$item['add_reason']
			);

			$button_cancel = sprintf(
				'<button type="submit" class="button button-small cptch-add-reason-button %s" name="cptch_reason_cancel" value="">%s</button>',
				$button_class,
				__( 'Cancel', 'ineosq-captcha-pro' )
			);

			$button_submit = sprintf(
				'<button type="submit" class="button button-small button-primary cptch-add-reason-button %s" name="cptch_reason_submit" value="%s">%s</button>',
				$button_class,
				$item['id'],
				__( 'Update', 'ineosq-captcha-pro' )
			);

			$edit_link_href = sprintf(
				'?page=%s&cptch_edit_add_reason=%s',
				$_REQUEST['page'],
				$item['id']
			);

			$edit_link = array(
				'edit_add_reason'	=> sprintf(
					'<a href="%s" class="cptch_edit_reason_link %s" style="%s">%s</a>',
					$edit_link_href,
					$edit_link_class,
					$edit_link_style,
					__( 'Edit reason', 'ineosq-captcha-pro' )
				)
			);
			return $nonce_field . $display . $textarea . $button_submit . $button_cancel . $this->row_actions( $edit_link );
		}
		/**
		 * List with bulk action for IP
		 * @return array   $actions
		 */
		function get_bulk_actions() {
			/* adding bulk action */
			return $this->disable_list ? array() : array( 'cptch_remove'=> __( 'Delete', 'ineosq-captcha-pro' ) );
		}
		/**
		 * Get content for table
		 * @return  array
		 */
		function get_content() {
			global $wpdb;

			if ( empty( $this->s ) ) {
				$where = '';
			} else {
				$ip_int = filter_var( $this->s, FILTER_VALIDATE_IP ) ? sprintf( '%u', ip2long( $this->s ) ) : 0;
				$where =
						0 == $ip_int
					?
						" WHERE `ip` LIKE '%{$this->s}%' OR `ip_to` LIKE '%{$this->s}%' OR `ip_from` LIKE '%{$this->s}%'"
					:
						" WHERE ( `ip_from_int` <= {$ip_int} AND `ip_to_int` >= {$ip_int} )";
			}

			/**
			* filter items by date if it is needed
			*/
			if ( 'all' != $this->filter_by ) {
				$now = time();

				/**
				* get the quantity of seconds in the day
				*/
				$day = 60 * 60 * 24;

				switch ( $this->filter_by ) {
					case 'day':
						$point = $now - $day;
						break;
					case 'week':
						$point = $now - $day * 7;
						break;
					case 'month':
						$point = $now - $day * 31;
						break;
					case 'year':
						$point = $now - $day * 365;
						break;
					default:
						break;
				}

				if ( ! empty( $point ) ) {
					$point = date( 'Y-m-d h:i:s', $point );

					$where .= ! empty( $where ) ? ' &&' : 'WHERE';
					$where .= ' `add_time` > "' . $point . '"';
				}
			}

			$order_by	= empty( $this->order_by ) ? '' : " ORDER BY `{$this->order_by}`";
			$order		= empty( $this->order )    ? '' : strtoupper( " {$this->order}" );
			$offset		= empty( $this->paged )    ? '' : " OFFSET " . ( $this->per_page * ( absint( $this->paged ) - 1 ) );


			return $wpdb->get_results( "SELECT * FROM `{$wpdb->prefix}cptch_allowlist`{$where}{$order_by}{$order} LIMIT {$this->per_page}{$offset}", ARRAY_A );
		}

		/**
		 * Get number of all IPs which were added to database
		 * @since  1.6.9
		 * @param  void
		 * @return int    the number of IPs
		 */
		private function get_items_number() {
			global $wpdb;
			if ( empty( $this->s ) ) {
				$where = '';
			} else {
				$ip_int = filter_var( $this->s, FILTER_VALIDATE_IP ) ? sprintf( '%u', ip2long( $this->s ) ) : 0;
				$where =
						0 == $ip_int
					?
						" WHERE `ip` LIKE '%{$this->s}%' OR `ip_to` LIKE '%{$this->s}%' OR `ip_from` LIKE '%{$this->s}%'"
					:
						" WHERE ( `ip_from_int` <= {$ip_int} AND `ip_to_int` >= {$ip_int} )";
			}
			return absint( $wpdb->get_var( "SELECT COUNT(`id`) FROM `{$wpdb->prefix}cptch_allowlist`{$where}" ) );
		}

		/**
		 * This function display's top- & bottom- filters
		 * @since 4.3.1
		 * @param string $which
		 * @return void
		 */
		function extra_tablenav( $which ) { ?>
			<select name="cptch_date_filter[]">
				<?php foreach ( $this->date_filter_options as $key => $value) { ?>
					<option value="<?php echo $key;?>" <?php echo ( $key == $this->filter_by ) ? 'selected' : ''; ?>>
						<?php echo $value; ?>
					</option>
				<?php } ?>

			</select>

			<input type="hidden" name="cptch_last_filtred_by" value="<?php echo $this->filter_by; ?>" />
			<input type="submit" class="button action" value="<?php _e( 'Filter', 'ineosq-captcha-pro' ); ?>" />
		<?php }

		/**
		 * Check if filtring option is valid and not same to last time used filtring option
		 * @see $this->extra_tablenav
		 * @param  string   filtring option
		 * @return array    filtred $_POST['cptch_date_filter']
		 */
		private function get_date_filter_values( $item ) {
			return ( in_array( $item, array_keys( $this->date_filter_options ) ) && $this->last_filtred_by != $item );
		}

		/**
		 * Handle necessary reqquests and display notices
		 * @return void
		 */
		function display_notices() {
			global $wpdb, $cptch_options;
			$error = $message = '';
			$bulk_action = isset( $_REQUEST['action'] ) && 'cptch_remove' == $_REQUEST['action'] ? true : false;
			if ( ! $bulk_action )
				$bulk_action = isset( $_REQUEST['action2'] ) && 'cptch_remove' == $_REQUEST['action2'] ? true : false;
			/* Add IP in to database */
			if ( isset( $_POST['cptch_add_to_allowlist'] ) && ( ! empty( $_POST['cptch_add_to_allowlist'] ) || isset( $_POST['cptch_add_to_allowlist_my_ip'] ) ) && check_admin_referer( $this->basename, 'cptch_nonce_name' ) ) {
				if ( isset( $_POST['cptch_add_to_allowlist_my_ip'] ) ) {
					$list_ip[] = $_POST['cptch_add_to_allowlist_my_ip_value'];
				} else
					$list_ip = preg_split( "/[\s,;]+/", stripslashes( esc_html( trim( $_POST['cptch_add_to_allowlist'], " \s\r\n\t,;" ) ) ) );

				if ( empty( $list_ip ) ) {
					$error = __( 'Invalid data. See allowed formats', 'ineosq-captcha-pro' );
				} else {
					$reasons_list	= isset( $_POST['cptch_add_to_allowlist_reason'] ) ? stripslashes( esc_html( trim( $_POST['cptch_add_to_allowlist_reason'], " \s\r\n\t,;" ) ) ) : '' ;
					$reasons		= preg_split( "/[\r\n\t,;]+/", $reasons_list );
					$time			= date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
					$i				= 0;
					$flag			= ( ! empty( $reasons ) ) && count( $reasons ) == count( $list_ip ) ? true : false;
					foreach ( $list_ip as $ip ) {
						$type_ip = $this->valid_ip( $ip );
						if ( $type_ip ) {
							$message_prefix	= 'single_ip' == $type_ip ? 'IP' : __( 'Range', 'ineosq-captcha-pro' );
							$ip_int			= sprintf( '%u', ip2long( $ip ) );
							$query_where	= 0 == $ip_int ? " `ip` LIKE '" . $ip . "'" : " ( `ip_from_int` <= " . $ip_int . " AND `ip_to_int` >= " . $ip_int . " ) ";
							$id				= $wpdb->get_var( "SELECT `id` FROM " . $wpdb->prefix . "cptch_allowlist WHERE" . $query_where . "LIMIT 1;" );
							/* check if IP already in database */
							if ( is_null( $id ) ) {
								$reason = $flag ? $reasons[ $i ] : $reasons_list;
								if ( $this->save_ip( $ip, $type_ip, $reason, $time ) )
									$message .= $message_prefix . '&nbsp;' . $ip . '&nbsp;' . __( 'added to the allow list successfully.', 'ineosq-captcha-pro' ) . '<br />';
								else
									$error .= $message_prefix . '&nbsp;' . $ip . '&nbsp;' . __( 'not saved to the allowl ist.', 'ineosq-captcha-pro' ) . '<br />';
							} else {
								$error .= $message_prefix . '&nbsp;' . $ip . '&nbsp;' . __( 'is already in the allow ist', 'ineosq-captcha-pro' ) . '<br />';
							}
						} else {
							$error .= $ip . '&nbsp;- ' . __( 'invalid format. See allowed formats.', 'ineosq-captcha-pro' ) . '<br />';
						}
						$i ++;
					}
				}
				if ( empty( $error ) ) {
					$cptch_options['allowlist_is_empty'] = false;
					update_option( 'cptch_options', $cptch_options );
				}
			/* Remove IP from database */
			} elseif ( $bulk_action && check_admin_referer( $this->basename, 'cptch_nonce_name' ) ) {
				if ( ! empty( $_REQUEST['id'] ) ) {
					$list	= implode( ',', $_REQUEST['id'] );
					$result	= $wpdb->query( $wpdb->prepare( "DELETE FROM `" . $wpdb->prefix . "cptch_allowlist` WHERE `id` IN (%s);", $list ) );
					if ( ! $wpdb->last_error ) {
						$message = sprintf( _n( "%s IP was deleted successfully", "%s IPs were deleted successfully", $result, 'ineosq-captcha-pro' ), $result );
						$cptch_options['allowlist_is_empty'] = is_null( $wpdb->get_var( "SELECT `id` FROM `{$wpdb->prefix}cptch_allowlist` LIMIT 1" ) ) ? true : false;
						update_option( 'cptch_options', $cptch_options );
					} else {
						$error = __( 'Some errors occurred.', 'ineosq-captcha-pro' );
					}
				}
			} elseif ( isset( $_GET['cptch_remove'] ) && check_admin_referer( 'cptch_nonce_remove_' . $_GET['cptch_remove'] ) ) {
				$wpdb->delete( $wpdb->prefix . "cptch_allowlist", array( 'id' => $_GET['cptch_remove'] ) );
				if ( ! $wpdb->last_error ) {
					$message = __( "One IP was deleted successfully", 'ineosq-captcha-pro' );
					$cptch_options['allowlist_is_empty'] = is_null( $wpdb->get_var( "SELECT `id` FROM `{$wpdb->prefix}cptch_allowlist` LIMIT 1" ) ) ? true : false;
					update_option( 'cptch_options', $cptch_options );
				} else {
					$error = __( 'Some errors occurred.', 'ineosq-captcha-pro' );
				}
			} elseif ( isset( $_POST['cptch_add_to_allowlist'] ) && empty( $_POST['cptch_add_to_allowlist'] ) ) {
				$error = __( 'You have not entered any IP', 'ineosq-captcha-pro' );
			} elseif ( isset( $_REQUEST['s'] ) ) {
				if ( '' == $_REQUEST['s'] ) {
					$error = __( 'You have not entered any IP in to the search form.', 'ineosq-captcha-pro' );
				} else {
					$message = __( 'Search results for', 'ineosq-captcha-pro' ) . '&nbsp;:&nbsp;' . esc_html( $_REQUEST['s'] );
				}
			} elseif ( isset( $_POST['cptch_load_limit_attempts_allowlist'] ) && check_admin_referer( $this->basename, 'cptch_nonce_name' ) ) {
				/* copy data from the allow list of LimitAttempts plugin */
				$time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

				$column_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "lmtttmpts_allowlist` LIKE 'add_reason'" );
				if ( 0 == $column_exists ) {
					$column_exists = $wpdb->query( "SHOW COLUMNS FROM `" . $wpdb->prefix . "lmtttmpts_allowlist` LIKE 'ip_from_int'" );
					/* LimitAttempts Free hasn't  `ip_from_int`, `ip_to_int` COLUMNS */
					if ( 0 == $column_exists ) {
						$result = 0;
						$all_ip = $wpdb->get_results( "SELECT `ip`, '{$time}' FROM `{$wpdb->prefix}lmtttmpts_allowlist`", ARRAY_A );
						foreach ( $all_ip as $ip_value ) {
							$ip_int = sprintf( '%u', ip2long( $ip_value['ip'] ) );
							$result_single = $wpdb->query( $wpdb->prepare(
								"INSERT IGNORE INTO `{$wpdb->prefix}cptch_allowlist`
								( `ip`, `ip_from`, `ip_to`, `ip_from_int`, `ip_to_int`, `add_time` ) VALUES ( %s, %s, %s, %s, %s, %s );",
								$ip_value['ip'], $ip_value['ip'], $ip_value['ip'], $ip_int, $ip_int, $time ) );
							$result = $result + $result_single;
						}
					} else {
						$result = $wpdb->query(
						"INSERT IGNORE INTO `{$wpdb->prefix}cptch_allowlist`
							( `ip`, `ip_from`, `ip_to`, `ip_from_int`, `ip_to_int`, `add_time` )
							( SELECT `ip`, `ip_from`, `ip_to`, `ip_from_int`, `ip_to_int`, '{$time}'
								FROM `{$wpdb->prefix}lmtttmpts_allowlist` );"
						);
					}
				} else {
					$result = $wpdb->query(
						"INSERT IGNORE INTO `{$wpdb->prefix}cptch_allowlist`
							( `ip`, `ip_from`, `ip_to`, `ip_from_int`, `ip_to_int`, `add_time`, `add_reason` )
							( SELECT `ip`, `ip_from`, `ip_to`, `ip_from_int`, `ip_to_int`, '{$time}', `add_reason`
								FROM `{$wpdb->prefix}lmtttmpts_allowlist` );"
					);
				}

				if ( $wpdb->last_error ) {
					$error = $wpdb->last_error;
				} else {
					$message = $result . '&nbsp;' . __( 'IP-address(es) successfully copied to the allow list.', 'ineosq-captcha-pro' );
					$cptch_options['allowlist_is_empty'] = false;
					update_option( 'cptch_options', $cptch_options );
				}
			} elseif ( isset( $_POST['cptch_save_add_ip_form'] ) && check_admin_referer( $this->basename, 'cptch_nonce_name' ) ) {
				$cptch_options['use_limit_attempts_allowlist'] = isset( $_POST['cptch_use_la_allowlist'] ) ? 1 : 0;
				update_option( 'cptch_options', $cptch_options );
			} elseif ( ! empty( $_REQUEST['cptch_reason_submit'] ) ) {
				$id = $_REQUEST['cptch_reason_submit'];
				$ip = stripslashes( esc_html( trim( $_REQUEST['cptch_ip_of_' . $id ], " \r\n\t,;" ) ) );
				check_admin_referer( 'cptch_edit_' . $id, 'cptch_edit_allowlist_reason-' . $id );
				$reason = isset( $_POST['cptch-reason-' . $id ] ) ? $_POST['cptch-reason-' . $id ] : '';
				switch ( $this->edit_reason( $id, $reason ) ) {
					case 'success':
						$message = sprintf( __( 'The reason for %s has been updated successfully.', 'ineosq-captcha-pro' ), $ip ) . '<br />';
						break;
					case 'no_change':
						$message = sprintf( __( 'No changes was made for %s.', 'ineosq-captcha-pro' ), $ip ) . "<br />";
						break;
					case 'error':
						$error = sprintf( __( 'Error while updating reason for', 'ineosq-captcha-pro' ), $ip ) . "<br />";
						break;
					default:
						break;
				}
			}
			if ( ! empty( $message ) ) { ?>
				<div class="updated fade inline"><p><strong><?php echo $message; ?></strong></p></div>
			<?php }
			if ( ! empty( $error ) ) { ?>
				<div class="error inline"><p><strong><?php echo $error; ?></strong></p></div>
			<?php }
		}

		/**
		 * Function to check if IP (mask/diapason) is valid
		 * @param $ip_to_check  string  IP, mask or diapason to check
		 * @return bool False - if it's not valid IP, mask or diapason | string with the type of entered value - if valid IP, mask or diapason
		 */
		function valid_ip( $ip_to_check = null ) {
			if ( empty( $ip_to_check ) ) {
				return false;
			} else {
				/* if IP (or mask/diapason) is not empty*/
				if ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip_to_check ) ) {
					/* single IP */
					return 'single_ip';
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}\/(3[0-2]|[1-2][0-9]|[0-9])$/', $ip_to_check ) ) {
					/* normal mask like 128.45.25.0/8 */
					return 'normal_mask';
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){0,2}\.$/', $ip_to_check ) ) {
					/* shorten mask like 192.168. or 128.45.25. */
					return 'shorten_mask';
				} elseif ( preg_match( '/^(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}\-(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])(\.(25[0-5]|2[0-4][0-9]|[1][0-9]{2}|[1-9][0-9]|[0-9])){3}$/', $ip_to_check ) ) {
					/* diapason like 128.45.25.0-188.5.5.5 */
					$ip_to_check = explode( '-', $ip_to_check ); /*$ips[0] - diapason from, $ips[1] - diapason to*/
					if ( sprintf( '%u', ip2long( $ip_to_check[0] ) ) <= sprintf( '%u', ip2long( $ip_to_check[1] ) ) ) {
						return 'diapason';
					} else {
						return false;
					}
				} else {
					return false;
				}
			}
		}
		/**
		 * Save IP in database
		 */
		function save_ip( $ip, $type_ip, $reason, $time ) {
			global $wpdb;
			switch ( $type_ip ) {
				case 'single_ip': /* if insert single ip address */
					$ip_from_int = $ip_to_int = sprintf( '%u', ip2long( $ip ) ); /*because adding a single address diapason will contain one address*/
					$ip_from     = $ip_to = $ip;
					break;
				case 'shorten_mask': /* if insert ip mask like 'xxx.' or 'xxx.xxx.' or 'xxx.xxx.xxx.' */
					$dot_entry = substr_count( $ip, '.' );
					switch ( $dot_entry ) {
						case 3: /* in case if mask like xxx.xxx.xxx. */
							$ip_from	= $ip . '0';
							$ip_to		= $ip . '255';
							break;
						case 2: /* in case if mask like xxx.xxx. */
							$ip_from	= $ip . '0.0';
							$ip_to		= $ip . '255.255';
							break;
						case 1: /*i n case if mask like xxx. */
							$ip_from	= $ip . '0.0.0';
							$ip_to		= $ip . '255.255.255';
							break;
						default: /* insurance */
							$ip_from	= '0.0.0.0';
							$ip_to		= '0.0.0.0';
							break;
					}
					$ip_from_int	= sprintf( '%u', ip2long( $ip_from ) );
					$ip_to_int		= sprintf( '%u', ip2long( $ip_to ) );
					break;
				case 'diapason': /* if insert diapason of ip addresses like xxx.xxx.xxx.xxx-yyy.yyy.yyy.yyy */
					$ips			= explode( '-', $ip ); /* $ips[0] - diapason from, $ips[1] - diapason to */
					$ip_from		= trim( $ips[0] );
					$ip_to			= trim( $ips[1] );
					$ip_from_int	= sprintf( '%u', ip2long( $ip_from ) );
					$ip_to_int		= sprintf( '%u', ip2long( $ip_to ) );
					break;
				case 'normal_mask': /* if insert ip mask like xxx.xxx.xxx.xxx/yy */
					$mask			= explode( '/' , $ip ); /* $mask[0] - is ip address, $mask[1] - is cidr mask */
					$nmask			= 4294967295 - ( pow( 2 , 32 - $mask[1] ) - 1 ); /* calculation netmask in decimal view from cidr mask */
					$ip_from_int	= ip2long( $mask[0] ) & $nmask; /* calculating network address signed (this is doing for correct worl with netmsk) */
					$ip_from_int	= sprintf( '%u', $ip_from_int ); /* and now unsigned */
					$ip_to_int		= $ip_from_int + ( pow( 2 , 32 - $mask[1] ) - 1 ); /* calculating broadcast */
					$ip_from		= long2ip( $ip_from_int );
					$ip_to			= long2ip( $ip_to_int );
				default:
					break;
			}
			/* add a new row to db */
			$result = $wpdb->insert(
				$wpdb->prefix . "cptch_allowlist",
				array(
					'ip'			=> $ip,
					'ip_from'		=> $ip_from,
					'ip_to'			=> $ip_to,
					'ip_from_int'	=> $ip_from_int,
					'ip_to_int'		=> $ip_to_int,
					'add_time'		=> $time,
					'add_reason'	=> $reason,
				),
				'%s' /* all '%s' because max value in '%d' is 2147483647 */
			);
			return $result;
		}

		/*
		 * Get info about plugins Limit Attempts ( Free or Pro ) by IneosQ
		 */
		function get_limit_attempts_info() {
			global $wp_version, $cptch_plugin_info;

			if ( 'active' == $this->la_info['status'] ) {
				$data = array(
					'active'			=> true,
					'name'				=> $this->la_info['plugin_info']["Name"],
					'label'				=> __( 'use', 'ineosq-captcha-pro' ) . '&nbsp;<a href="?page=' . $this->la_info['plugin_info']["TextDomain"] . '.php">' . __( 'the allow list of', 'ineosq-captcha-pro' ) . '&nbsp;' . $this->la_info['plugin_info']["Name"] . '</a>',
					'notice'			=> '',
					'disabled'			=> false,
				);
			} elseif ( 'deactivated' == $this->la_info['status'] ) {
				$data = array(
					'active'			=> false,
					'name'				=> $this->la_info['plugin_info']["Name"],
					'label'				=> sprintf( __( 'use the allow list of %s', 'ineosq-captcha-pro' ), $this->la_info['plugin_info']["Name"] ),
					'notice'			=> sprintf( __( 'you should %s to use this functionality', 'ineosq-captcha-pro' ), '<a href="plugins.php">' . __( 'activate', 'ineosq-captcha-pro' ) . '&nbsp;' . $this->la_info['plugin_info']["Name"] . '</a>' ),
					'disabled'			=> true,
					'link'				=> '<a href="plugins.php">' . __( 'Activate', 'captcha-plus' ) . '</a>'
				);
			} elseif ( 'not_installed' == $this->la_info['status'] ) {
				$data = array(
					'active'			=> false,
					'name'				=> 'Limit Attempts by IneosQ',
					'label'				=> sprintf( __( 'use the allow list of %s', 'ineosq-captcha-pro' ), 'Limit Attempts by IneosQ' ),
					'notice'			=> sprintf( __( 'you should install %s to use this functionality', 'ineosq-captcha-pro' ), '<a href="https://ineosq.com/products/wordpress/plugins/limit-attempts?k=9ab9d358ad3a23b8a99a8328595ede2e&pn=72&v=' . $cptch_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">Limit Attempts by IneosQ</a>' ),
					'disabled'			=> true,
					'link'				=> '<a href="https://ineosq.com/products/wordpress/plugins/limit-attempts?k=9ab9d358ad3a23b8a99a8328595ede2e&pn=72&v=' . $cptch_plugin_info["Version"] . '&wp_v=' . $wp_version . '" target="_blank">' . __( 'Install Now', 'captcha-plus' ) . '</a>'
				);
			}
			return $data;
		}

		function edit_reason( $id, $reason ) {
			global $wpdb;
			$db_reason = stripslashes( esc_html( trim( $reason, " \r\n\t,;" ) ) );
			$n = $wpdb->update(
				$wpdb->prefix . "cptch_allowlist",
				array( 'add_reason' => $db_reason ),
				array( 'id' => $id )
			);
			/* if number of touched rows != 0/false */
			if ( !! $n ) {
				return 'success';
			}
			if ( $n === 0) {
				return 'no_change';
			}
			if ( $wpdb->last_error ) {
				return 'error';
			}
		}
	}
}