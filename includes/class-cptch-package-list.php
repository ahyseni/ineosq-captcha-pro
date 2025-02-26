<?php
/**
 * Manage list of upladed images packages
 * @package Captcha Pro by IneosQ
 * @since 1.6.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

if ( ! class_exists( 'Cptch_Package_List' ) ) {
	class Cptch_Package_List extends WP_List_Table {

		private
			$date_format,	/* string, the date format, which is configured on the Admin Panel -> Settings -> General -> Date Format */
			$upload_dir,	/* string, the absolute path to the folder with images for CAPTCHA */
			$upload_url,	/* string, the URL of the folder with images for CAPTCHA */
			$to_delete,		/* array,  the list of packaeges, wich need to delete */
			$order_by,		/* srting, the column name, according to which to sort the data */
			$defaults,		/* array,  the list of default packages */
			$per_page,		/* int,    the number of packages on the page */
			$message,		/* string, the service message */
			$loader,		/* object, an instance of the Cptch_Package_Loader class */
			$paged,			/* int,    the number of the current page */
			$order,			/* string, 'ASC' or 'DESC' */
			$s; 			/* string, the content of the search request */

		/**
		 * Constructor of class
		 * @param  void
		 * @return void
		 */
		function __construct() {
			parent::__construct( array(
				'singular'	=> __( 'package', 'ineosq-captcha-pro' ),
				'plural'	=> __( 'packages', 'ineosq-captcha-pro' ),
				'ajax'		=> false,
				)
			);
		}

		/**
		 * Prepare data before the displaying
		 * @param  void
		 * @return void
		 */
		function prepare_items() {
			if ( is_multisite() ) {
				switch_to_blog( 1 );
				$upload_dir = wp_upload_dir();
				restore_current_blog();
			} else {
				$upload_dir = wp_upload_dir();
			}
			$this->upload_dir	= $upload_dir['basedir'] . '/ineosq_captcha_images';
			$this->upload_url	= $upload_dir['baseurl'] . '/ineosq_captcha_images';
			$this->to_delete	= isset( $_GET['package_id'] ) ? $_GET['package_id'] : '';
			$this->order_by		= isset( $_REQUEST['orderby'] ) && in_array( $_REQUEST['orderby'], array_keys( $this->get_sortable_columns() ) ) ? $_REQUEST['orderby'] : '';
			$this->order		= isset( $_REQUEST['order'] ) && in_array( strtoupper( $_REQUEST['order'] ), array( 'ASC', 'DESC' ) ) ? $_REQUEST['order'] : '';
			$this->paged		= isset( $_REQUEST['paged'] ) && is_numeric( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : '';
			$this->s			= isset( $_REQUEST['s'] ) ? esc_html( trim( $_REQUEST['s'] ) ) : '';
			$this->per_page		= $this->get_items_per_page( 'cptch_per_page', 20 );
			$this->date_format	= get_option( 'date_format' );
			$this->defaults		= array(
				'arabic_bt', 'arabic_bw', 'arabic_wb', 'arabic_wt',
				'dots_bt', 'dots_bw', 'dots_wb', 'dots_wt',
				'roman_bt', 'roman_bw', 'roman_wb', 'roman_wt'
			);

			if ( ! class_exists( 'Cptch_Package_Loader' ) ) {
				require_once( dirname( __FILE__ ) . '/class-cptch-package-loader.php' );
			}
			$this->loader = new Cptch_Package_Loader();

			if ( $this->to_delete ) {
				$this->delete_packages();
			}

			$columns				= $this->get_columns();
			$hidden					= array();
			$sortable				= $this->get_sortable_columns();
			$primary				= 'name';
			$this->_column_headers	= array( $columns, $hidden, $sortable, $primary );
			$this->items			= $this->get_packages();
			$this->set_pagination_args( array(
					'total_items'	=> $this->get_items_number(),
					'per_page'		=> 20,
				)
			);
		}

		/**
		 * Display the content of the page
		 * @param  void
		 * @return void
		 */
		function display_content() { ?>
			<h1><?php _e( 'Captcha Pro Packages', 'ineosq-captcha-pro' ); ?><a href="#" class="page-title-action add-new-h2 hide-if-no-js" id="cptch_show_loader" ><?php _e( 'Add New', 'ineosq-captcha-pro' ); ?></a></h1>
            <hr class="wp-header-end">
			<?php $this->prepare_items();
			if ( $this->message ) { ?>
				<div class="updated fade below-h2"><p><?php echo $this->message; ?></p></div>
			<?php }
			$this->loader->display(); ?>
			<form method="get" action="<?php echo self_admin_url( "admin.php" ); ?>" style="margin: 10px 0;">
				<?php $this->search_box( __( 'Search', 'ineosq-captcha-pro' ), 'cptch_packages_search' );
				$this->display(); ?>
				<input type="hidden" name="page" value="captcha-packages.php" />
			</form>
		<?php }

		/**
		 * Add necessary classes for the packages list
		 * @param  void
		 * @return array
		 */
		function get_table_classes() {
			return array( 'widefat', 'striped', 'cptch_package_list' );
		}

		/**
		 * Show message the list is empty
		 * @param  void
		 * @return void
		 */
		function no_items() { ?>
			<p><?php _e( 'No packages found', 'ineosq-captcha-pro' ); ?></p>
		<?php }


		/**
		 * Get the list of table columns.
		 * @param  void
		 * @return array list of columns labels
		 */
		function get_columns() {
			return array(
				'cb'	=> '<input type="checkbox" />',
				'name'	=> __( 'Package', 'ineosq-captcha-pro' ),
				'date'	=> __( 'Date Added', 'ineosq-captcha-pro' )
			);
		}

		/**
		 * Get the list of sortable columns.
		 * @param  void
		 * @return array list of sortable columns
		 */
		function get_sortable_columns() {
			return array(
				'name'	=> array( 'name', false ),
				'date'	=> array( 'add_time', false )
			);
		}

		/**
		 * Manage the content of thecolumn with checboxes
		 * @param     array     $item        The current package data.
		 * @return    string                 with the column content
		 */
		function column_cb( $item ) {
			return in_array( $item['folder'], $this->defaults ) ? '' : "<input type=\"checkbox\" name=\"package_id[]\" value=\"{$item['id']}\"/>";
		}

		/**
		 * Manage the content of the column "Package"
		 * @param     array     $item        The current package data.
		 * @return    string                 with the column content
		 */
		function column_name( $item ) {
			$styles = '';
			if ( ! empty( $item['settings'] ) ) {
				$settings = unserialize( $item['settings'] );
				if ( is_array( $settings ) ) {
					$styles = ' style="';
					foreach ( $settings as $propery => $value )
						$styles .= "{$propery}: {$value};";
					$styles .= '"';
				}
			}
			$title =
				"<div class=\"has-media-icon\">
					<span class=\"media-icon image-icon\">
						<img src=\"{$this->upload_url}/{$item['folder']}/{$item['image']}\" alt=\"{$item['name']}\"{$styles} />
					</span>
					{$item['name']}
				</div>";

			if ( in_array( $item['folder'], $this->defaults ) ) {
				return $title;
			}

			$actions	= array();
			$args		= array( 'delete' => __( 'Delete', 'ineosq-captcha-pro' ) );
			$order_by	= empty( $this->order_by ) ? '' : "&orderby={$this->order_by}";
			$order		= empty( $this->order )    ? '' : "&order={$this->order}";
			$paged		= empty( $this->paged )    ? '' : "&paged={$this->paged}";
			$s			= empty( $this->s )        ? '' : "&s={$this->s}";
			foreach ( $args as $key => $label ) {
				$params	= 'edit' == $key ? '&action=edit_package' : "&cptch_action={$key}{$order_by}{$order}{$paged}{$s}";
				$url	= wp_nonce_url(
					"?page=captcha-packages.php{$params}&package_id={$item['id']}",
					"cptch_nonce_{$key}_{$item['id']}"
				);
				$actions[ $key ] = "<a href=\"{$url}\">{$label}</a>";
			}
			return sprintf('%1$s %2$s', $title, $this->row_actions( $actions ) );
		}

		/**
		 * Manage the content of the column 'Date'
		 * @param     array     $item        The cuurrent package data.
		 * @return    string                 with the column content
		 */
		function column_date( $item ) {
			return '0000-00-00 00:00:00' == $item['add_time'] ? '' : date_i18n( $this->date_format, strtotime( $item['add_time'] ) );
		}

		/**
		 * List with bulk action
		 * @param  void
		 * @return array   $actions
		 */
		function get_bulk_actions() {
			return array( 'delete' => __( 'Delete', 'ineosq-captcha-pro' ) );
		}

		/**
		 * Get the list of loaded packages
		 * @param  void
		 * @return array  $items   the list with packages data
		 */
		private function get_packages() {
			global $wpdb;
			$where		= empty( $this->s )        ? '' : " WHERE `{$wpdb->base_prefix}cptch_packages`.`name` LIKE '%{$this->s}%'";
			$order_by	= empty( $this->order_by ) ? ' ORDER BY `add_time`' : " ORDER BY `{$this->order_by}`";
			$order		= empty( $this->order )    ? ' DESC' : strtoupper( " {$this->order}" );
			$offset		= empty( $this->paged )    ? '' : " OFFSET " . ( $this->per_page * ( absint( $this->paged ) - 1 ) );

			$items = $wpdb->get_results(
				"SELECT
					`{$wpdb->base_prefix}cptch_packages`.`id`,
					`{$wpdb->base_prefix}cptch_packages`.`name`,
					`{$wpdb->base_prefix}cptch_packages`.`folder`,
					`{$wpdb->base_prefix}cptch_packages`.`add_time`,
					`{$wpdb->base_prefix}cptch_packages`.`settings`,
					`{$wpdb->base_prefix}cptch_images`.`name` AS `image`
				FROM
					`{$wpdb->base_prefix}cptch_packages`
				LEFT JOIN
					`{$wpdb->base_prefix}cptch_images`
				ON
					`{$wpdb->base_prefix}cptch_images`.`package_id`=`{$wpdb->base_prefix}cptch_packages`.`id`
				{$where}
				GROUP BY `{$wpdb->base_prefix}cptch_packages`.`id`
				{$order_by}
				{$order}
				LIMIT {$this->per_page}{$offset};",
				ARRAY_A
			);
			return $items;
		}

		/**
		 * Remove data of selected packages
		 * @param  void
		 * @return boolean
		 */
		private function delete_packages() {
			global $wpdb;

			if ( is_array( $_GET['package_id'] ) ) {
				check_admin_referer( "bulk-{$this->_args['plural']}" );
			} elseif ( ! wp_verify_nonce( $_GET['_wpnonce'], "cptch_nonce_delete_{$_GET['package_id']}" ) ) {
				return false;
			}

			$this->to_delete	= array_map( 'absint', (array)$this->to_delete );
			$to_delete			= implode( ',', $this->to_delete );

			/**
			 * get a list of folders, which we have to delete
			 * excluding those folders that are used in other packages
			 */
			$folders = $wpdb->get_col( $wpdb->prepare( 
				"SELECT
					`folder`
				FROM
					`{$wpdb->base_prefix}cptch_packages`
				WHERE
					`id` IN (%s)
				AND
					`folder` NOT IN
					(SELECT
						`folder`
					 FROM
					 	`{$wpdb->base_prefix}cptch_packages`
					 WHERE
					 	`id` NOT IN (%s)
					 GROUP BY
					 	`folder`)
				GROUP BY
					`folder`;", $to_delete, $to_delete
			) );

			if ( $folders ) {
				foreach ( $folders as $folder ) {
					/* remove all files from directory */
					array_map( 'unlink', glob( "{$this->upload_dir}/{$folder}/*.*" ) );
					/* remove empty directory */
					rmdir( "{$this->upload_dir}/{$folder}" );
				}
			}

			if ( is_multisite() ) {
				$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
				foreach ( $blog_ids as $blog_id )
					$this->update_option( $blog_id );
				$this->update_option( false, true );
			} else {
				$this->update_option();
			}

			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->base_prefix}cptch_packages` WHERE `id` IN (%s);", $to_delete ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM `{$wpdb->base_prefix}cptch_images` WHERE `package_id` IN (%s);", $to_delete ) );

			$this->message = __( 'Selected packages have been removed successfully.', 'ineosq-captcha-pro' );
			return true;
		}

		/**
		 * Update plugin options
		 * @param  int|boolean   $blog_id
		 * @param  boolean       $update_network
		 * @return boolean
		 */
		private function update_option( $blog_id = false, $update_network = false ) {

			if ( $update_network ) {
				$option = get_site_option( 'cptch_options' );
			} elseif ( $blog_id ) {
				$option = get_blog_option( $blog_id, 'cptch_options' );
			} else {
				$option = get_option( 'cptch_options' );
			}

			if ( ! $option ) {
				return false;
			}

			$default_forms = cptch_get_default_forms();

			foreach ( $default_forms as $form_slug )
				$option['forms'][ $form_slug ]['used_packages'] = array_diff( $option['forms'][ $form_slug ]['used_packages'], $this->to_delete );

			if ( ! $option['forms']['general']['used_packages'] ) {
				$key = array_search( 'images', $option['operand_format'] );
				if ( $key ) {
					unset( $option['operand_format'][ $key ] );
				}

				if ( empty( $option['operand_format'] ) ) {
					$option['operand_format'] = array( 'words' );
				}
			}

			if ( $update_network ) {
				update_site_option( 'cptch_options', $option );
			} elseif( $blog_id ) {
				update_blog_option( $blog_id, 'cptch_options', $option );
			} else {
				update_option( 'cptch_options', $option );
			}
			return true;
		}

		/**
		 * Get number of all packages which were added to database
		 * @param  void
		 * @return int   the number of packages
		 */
		private function get_items_number() {
			global $wpdb;
			$where = empty( $this->s ) ? '' : " WHERE `name` LIKE '%{$this->s}%'";
			return absint( $wpdb->get_var( "SELECT COUNT(`id`) FROM `{$wpdb->prefix}cptch_packages`{$where}" ) );
		}
	}
}