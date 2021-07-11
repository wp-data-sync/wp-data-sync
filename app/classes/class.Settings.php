<?php
/**
 * Settings
 *
 * Plugin Settings
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * @var Settings
	 */

	private static $instance;

	/**
	 * @var string
	 */

	private $active_tab = 'dashboard';

	/**
	 * Settings page slug.
	 */

	const SLUG = 'wp-data-sync';

	/**
	 * Settings constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return Settings
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Register action hooks.
	 */

	public function actions() {

		add_action( 'admin_init', [ $this, 'set_active_tab' ], 1 );
		add_action( 'admin_init', [ $this, 'register_settings' ], 2 );
		add_action( 'admin_menu', [ $this, 'add_pages' ], 5 );
		add_action( 'wp_data_sync_help_buttons', [ $this, 'help_buttons' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );

		// Delete log files on setting change.
		add_action( 'update_option_wp_data_sync_allow_logging', [ $this, 'delete_log_files' ], 10, 2 );

	}

	/**
	 * Set active tab.
	 */

	public function set_active_tab() {

		if ( isset( $_GET['active_tab'] ) ) {
			$this->active_tab = sanitize_text_field( $_GET['active_tab'] );
		}

	}

	/**
	 * Scripts and styles.
	 */

	public function scripts() {
		wp_register_style( 'jquery-ui-min', WPDSYNC_ASSETS . 'css/jquery-ui.min.css', [], WPDSYNC_VERSION );
		wp_enqueue_style( 'wpds_admin', WPDSYNC_ASSETS . 'css/admin.css', [], WPDSYNC_VERSION );
	}

	/**
	 * Tabs.
	 *
	 * @return mixed|void
	 */

	public function tabs() {

		$tabs = [
			'dashboard' => [
				'label' => __( 'Dashboard', 'wp-data-sync' ),
			],
			'sync_settings' => [
				'label' => __( 'Sync Settings', 'wp-data-sync' ),
			],
			'item_request' => [
				'label' => __( 'Item Request', 'wp-data-sync' ),
			]
		];

		$tabs = apply_filters( 'wp_data_sync_admin_tabs', $tabs, $this );

		// Include logs as last tab.
		$tabs['logs'] = [
			'label' => __( 'Logs', 'wp-data-sync' ),
		];

		foreach ( $tabs as $key => $tab ) {
			$tabs[ $key ]['status'] = $this->tab_status( $key );
		}

		return $tabs;

	}

	/**
	 * Add settings page to Settings submenu.
	 */

	public function add_pages() {

		add_options_page(
			__( 'WP Data Sync' ),
			__( 'WP Data Sync' ),
			WPDSYNC_CAP,
			Settings::SLUG,
			[ $this, 'settings_page' ]
		);

	}

	/**
	 * Display the settings page.
	 */

	public function settings_page() {

		if ( ! current_user_can( WPDSYNC_CAP ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-data-sync' ) );
		}

		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );

		wp_enqueue_style( 'jquery-ui-min' );
		wp_enqueue_script( 'jquery-ui-tooltip' );

		$args = [
			'tabs'  => $this->tabs(),
			'group' => $this->active_tab,
			'href'  => admin_url( "admin.php?page=" . Settings::SLUG . "&active_tab=" )
		];

		view( 'settings/page', $args );

	}

	/**
	 * Get the input file.
	 *
	 * @param $args
	 */

	public function input( $args ) {

		if ( isset( $args['heading'] ) ) {
			view( 'settings/heading', $args );
		}

		$args['value'] = $this->value( $args );

		view( 'settings/' . $args['basename'], $args );

	}

	/**
	 * Get the value for the input.
	 *
	 * @param $args
	 *
	 * @return mixed|string|void
	 */

	public function value( $args ) {

		if ( isset( $args['value'] ) ) {
			return $args['value'];
		}

		if ( isset ( $args['key'] ) ) {
			return get_option( $args['key'], '' );
		}

		return '';

	}

	/**
	 * Delete log files on setting saved.
	 *
	 * @param $old_value
	 * @param $value
	 */

	public function delete_log_files( $old_value, $value ) {

		if ( 'checked' !== $value ) {

			foreach ( glob( WPDSYNC_LOG_DIR . '*.log' ) as $file ) {
				unlink( $file );
			}

		}

	}

	/**
	 * Help buttons.
	 */

	public function help_buttons() {
		view( 'settings/help-buttons' );
	}

	/**
	 * Tab status.
	 *
	 * @param $key
	 *
	 * @return string
	 */

	public function tab_status( $key ) {

		if ( $this->active_tab === $key ) {
			return 'nav-tab-active';
		}

		return '';

	}

	/**
	 * Register the settings.
	 */

	public function register_settings() {

		$options = $this->get_options();

		foreach ( $options as $option ) {

			// Add the key into the args array
			$option['args']['key'] = $option['key'];

			register_setting( $this->active_tab, $option['key'], $option['args'] );

			add_settings_field(
				$option['key'],
				$option['label'],
				[ $this, $option['callback'] ],
				WPDSYNC_CAP,
				$this->active_tab,
				$option['args']
			);

		}

	}

	/**
	 * Plugin options.
	 *
	 * @return array
	 */

	private function get_options() {

		$options = apply_filters( 'wp_data_sync_settings', [
			'dashboard' => [
				0 => [
					'key' 		=> 'wp_data_sync_allowed',
					'label'		=> __( 'Allow Data Sync API Access', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => ''
					]
				],
				1 => [
					'key' 		=> 'wp_data_sync_access_token',
					'label'		=> __( 'API Access Token', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_key',
						'basename'          => 'text-input',
						'type'		        => 'password',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				2 => [
					'key' 		=> 'wp_data_sync_private_token',
					'label'		=> __( 'API Private Token', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_key',
						'basename'          => 'text-input',
						'type'		        => 'password',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				3 => [
					'key' 		=> 'wp_data_sync_auto_update',
					'label'		=> __( 'Automatically Update Plugin', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'We reccommend keeping this activated to keep your website up to date with the Data Sync API.' )
					]
				]
			],
			'sync_settings' => [
				0 => [
					'key' 		=> 'wp_data_sync_post_title',
					'label'		=> __( 'Default Title', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'text-input',
						'type'		        => 'text',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				1 => [
					'key' 		=> 'wp_data_sync_post_author',
					'label'		=> __( 'Default Author', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'author',
						'show_option_none'  => 'Select One',
						'selected'          => get_option( 'wp_data_sync_post_author' ),
						'name'              => 'wp_data_sync_post_author',
						'class'             => 'default-author widefat'
					]
				],
				2 => [
					'key' 		=> 'wp_data_sync_post_status',
					'label'		=> __( 'Default Status', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_post_status' ),
						'name'              => 'wp_data_sync_post_status',
						'class'             => 'default-status widefat',
						'values'            => [
							'publish' => __( 'Publish', 'wp-data-sync' ),
							'pending' => __( 'Pending', 'wp-data-sync' ),
							'draft'   => __( 'Draft', 'wp-data-sync' ),
							'future'  => __( 'Future', 'wp-data-sync' ),
							'private' => __( 'Private', 'wp-data-sync' ),
							'trash'   => __( 'Trash', 'wp-data-sync' )
						]
					]
				],
				3 => [
					'key' 		=> 'wp_data_sync_post_type',
					'label'		=> __( 'Default Type', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_post_type' ),
						'name'              => 'wp_data_sync_post_type',
						'class'             => 'default-type widefat',
						'values'            => get_post_types()
					]
				],
				4 => [
					'key' 		=> 'wp_data_sync_append_terms',
					'label'		=> __( 'Append Terms', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_append_terms' ),
						'name'              => 'wp_data_sync_append_terms',
						'class'             => 'append-terms widefat',
						'values'            => [
							'false' => __( 'No, I want old terms to be replaced (Recommended)', 'wp-data-sync' ),
							'true'  => __( 'Yes, I want to keep the old terms', 'wp-data-sync' )
						]
					]
				],
				5 => [
					'key' 		=> 'wp_data_sync_sync_term_desc',
					'label'		=> __( 'Sync Term Description', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_sync_term_desc' ),
						'name'              => 'wp_data_sync_sync_term_desc',
						'class'             => 'sync-term-desc widefat',
						'values'            => [
							'true'  => __( 'Yes, I want to sync term descriptions', 'wp-data-sync' ),
							'false' => __( 'No, I do not want to sync term descriptions', 'wp-data-sync' )
						]
					]
				],
				6 => [
					'key' 		=> 'wp_data_sync_sync_term_thumb',
					'label'		=> __( 'Sync Term Thumbnail', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_sync_term_thumb' ),
						'name'              => 'wp_data_sync_sync_term_thumb',
						'class'             => 'sync-term-thumb widefat',
						'values'            => [
							'true'  => __( 'Yes, I want to sync term thumbnail', 'wp-data-sync' ),
							'false' => __( 'No, I do not want to sync term thumbnail', 'wp-data-sync' )
						]
					]
				],
				7 => [
					'key' 		=> 'wp_data_sync_sync_term_meta',
					'label'		=> __( 'Sync Term Meta', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_sync_term_meta' ),
						'name'              => 'wp_data_sync_sync_term_meta',
						'class'             => 'sync-term-meta widefat',
						'values'            => [
							'true'  => __( 'Yes, I want to sync term meta', 'wp-data-sync' ),
							'false' => __( 'No, I do not want to sync term meta', 'wp-data-sync' )
						]
					]
				],
				8 => [
					'key' 		=> 'wp_data_sync_force_delete',
					'label'		=> __( 'Force Delete', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'When synced items have the trash status permanently delete the items and all related data.' )
					]
				],
				9 => [
					'key' 		=> 'wp_data_sync_allow_unsecure_images',
					'label'		=> __( 'Allow Unsecure Images', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'Allow images without valid SSL certificates to be imported.' )
					]
				],
				10 => [
					'key' 		=> 'wp_data_sync_replace_post_content_images',
					'label'		=> __( 'Replace Images in Content', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'Replace all valid full image URLs. This will make a copy of the images in this websites media library and replace the image URLs in the content.' )
					]
				],
				11 => [
					'key' 		=> 'wp_data_sync_replace_post_excerpt_images',
					'label'		=> __( 'Replace Images in Excerpt', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'Replace all valid full image URLs. This will make a copy of the images in this websites media library and replace the image URLs in the content.' )
					]
				]
			],
			'item_request' => [
				0 => [
					'key' 		=> 'wp_data_sync_item_request_access_token',
					'label'		=> __( 'Item Request Access Token', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_key',
						'basename'          => 'text-input',
						'type'		        => 'password',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				1 => [
					'key' 		=> 'wp_data_sync_item_request_private_token',
					'label'		=> __( 'Item Request Private Token', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_key',
						'basename'          => 'text-input',
						'type'		        => 'password',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				2 => [
					'key' 		=> 'wp_data_sync_unset_post_author',
					'label'		=> __( 'Unset Post Author', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'Remove the post author ID from items. This will allow the default author to be assigned when syncing data.' )
					]
				],
				3 => [
					'key' 		=> 'wp_data_sync_item_request_status',
					'label'		=> __( 'Include items with status', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => [ $this, 'sanitize_array' ],
						'basename'          => 'select-multiple',
						'name'              => 'wp_data_sync_item_request_status',
						'type'		        => '',
						'class'		        => 'item-request-status regular-text',
						'placeholder'       => '',
						'selected'          => get_option( 'wp_data_sync_item_request_status', [] ),
						'options'           => [
							'publish' => __( 'Publish' ),
							'pending' => __( 'Pending' ),
							'draft'   => __( 'Draft' ),
							'future'  => __( 'Future' ),
							'private' => __( 'Private' ),
							'trash'   => __( 'Trash' )
						]
					]
				]
			],
			'logs' => [
				0 => [
					'key' 		=> 'wp_data_sync_allow_logging',
					'label'		=> __( 'Allow Logging', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'checkbox',
						'type'		        => '',
						'class'		        => '',
						'placeholder'       => '',
						'info'              => __( 'We reccommend keeping this off unless you are having an issue with the data sync. If you do have an issue, please activate this before contacting support. Please note when this is deactivated all log files will be deleted.' )
					]
				],
				1 => [
					'key' 		=> 'wp_data_sync_log_file',
					'label'		=> __( 'Log File', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'files'             => Log::log_files(),
						'log'               => Log::log_file(),
						'basename'          => 'log-file'
					]
				]
			]
		], $this );

		if ( isset( $options[ $this->active_tab ] ) ) {
			return $options[ $this->active_tab ];
		}

		return [];

	}

	/**
	 * Sanitize array.
	 *
	 * @param $input
	 *
	 * @return array
	 */

	public function sanitize_array( $input ) {

		$new_input = array();

		foreach ( $input as $key => $value ) {
			$new_input[ $key ] = sanitize_text_field( $value );
		}

		return $new_input;

	}

	/**
	 * Settings Heading.
	 *
	 * @param array $args
	 */

	public function heading( $args ) {
		return;
	}

	/**
	 * Is Checked.
	 *
	 * @param $option
	 *
	 * @return bool
	 */

	public static function is_checked( $option ) {
		return ( 'checked' === get_option( $option ) );
	}

	/**
	 * Is value true.
	 *
	 * @param $option
	 *
	 * @return bool
	 */

	public static function is_true( $option ) {
		return ( 'true' === get_option( $option ) );
	}

}