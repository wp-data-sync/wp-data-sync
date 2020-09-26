<?php
/**
 * Settings
 *
 * Plugin Settings
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\App;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * @var Settings
	 */

	public static $instance;

	/**
	 * @var array
	 */

	public $group;

	/**
	 * @var string
	 */

	private $main_tab = 'dashboard';

	/**
	 * @var string
	 */

	private $active_tab = 'active_tab';

	/**
	 * Settings page slug.
	 */

	const SLUG = 'wp-data-sync';

	/**
	 * Settings constructor.
	 */

	public function __construct() {

		$this->group   = $this->group();

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

		add_action( 'admin_init', [ $this, 'register_settings' ], 2 );
		add_action( 'admin_menu', [ $this, 'add_pages' ], 5 );
		add_action( 'wp_data_sync_help_buttons', [ $this, 'help_buttons' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );

		// Delete log files on setting change.
		add_action( 'update_option_wp_data_sync_allow_logging', [ $this, 'delete_log_files' ], 10, 2 );

	}

	/**
	 * Scripts and styles.
	 */

	public function scripts() {
		wp_register_style( 'jquery-ui-min', WP_DATA_SYNC_ASSETS . 'css/jquery-ui.min.css' );
	}

	/**
	 * Tabs.
	 *
	 * @return mixed|void
	 */

	public function tabs() {

		$tabs = [
			$this->main_tab => [
				'label' => __( 'Dashboard', 'wp-data-sync' ),
				'id'    => $this->main_tab
			],
			'sync_settings' => [
				'label' => __( 'Sync Settings', 'wp-data-sync' ),
				'id'    => 'sync_settings'
			]
		];

		$tabs = apply_filters( 'wp_data_sync_admin_tabs', $tabs, $this );

		// Include logs as last tab.
		$logs_tab = [
			'logs' => [
				'label' => __( 'Logs', 'wp-data-sync' ),
				'id'    => 'logs'
			]
		];

		return array_merge( $tabs, $logs_tab );

	}

	/**
	 * Add settings page to Settings submenu.
	 */

	public function add_pages() {

		add_options_page(
			__( 'WP Data Sync' ),
			__( 'WP Data Sync' ),
			WP_DATA_SYNC_CAP,
			Settings::SLUG,
			[ $this, 'settings_page' ]
		);

	}

	/**
	 * Display the settings page.
	 */

	public function settings_page() {

		if ( ! current_user_can( WP_DATA_SYNC_CAP ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-data-sync' ) );
		}

		wp_enqueue_style( 'jquery-ui-min' );
		wp_enqueue_script( 'jquery-ui-tooltip' );

		if ( $view = $this->view( 'settings/page' ) ) {
			include $view;
		}

	}

	/**
	 * Get the view file.
	 *
	 * @param $name
	 *
	 * @return bool|string
	 */

	public function view( $name ) {

		$view = $this->get_view( $name );

		if ( file_exists( $view ) ) {
			return $view;
		}

		return FALSE;

	}

	/**
	 * Get path to view.
	 *
	 * @param $name
	 *
	 * @return mixed|void
	 */

	public function get_view( $name ) {

		$view = WP_DATA_SYNC_VIEWS . "{$name}.php";

		return apply_filters( 'wp_data_sync_view', $view, $name );

	}

	/**
	 * Get the input file.
	 *
	 * @param $args
	 */

	public function input( $args ) {

		if ( isset( $args['heading'] ) ) {

			if ( $view = $this->view( 'settings/heading' ) ) {
				include $view;
			}

		}

		$value = $this->value( $args );

		if ( $view = $this->view( "settings/{$args['basename']}" ) ) {
			include $view;
		}

	}

	/**
	 * Get the value for the input.
	 *
	 * @param $args
	 *
	 * @return mixed|string|void
	 */

	public function value( $args ) {

		return isset( $args['value'] ) ? $args['value'] :
			isset ( $args['key'] ) ? get_option( $args['key'], '' ) : '';

	}

	/**
	 * Delete log files on setting saved.
	 *
	 * @param $old_value
	 * @param $value
	 */

	public function delete_log_files( $old_value, $value ) {

		if ( 'checked' !== $value ) {

			foreach ( glob( WP_DATA_SYNC_LOG_DIR . '*.log' ) as $file ) {
				unlink( $file );
			}

		}

	}

	/**
	 * Help buttons.
	 */

	public function help_buttons() {

		if ( $view = $this->view( 'settings/help-buttons' ) ) {
			include $view;
		}

	}

	/**
	 * Admin tabs.
	 */

	public function admin_tabs() {

		$href = admin_url( "admin.php?page=" . Settings::SLUG . "&{$this->active_tab}=" );
		$tabs = $this->tabs();

		if ( $view = $this->view( 'settings/admin-tabs' ) ) {
			include $view;
		}

	}

	/**
	 * Group.
	 *
	 * @return string
	 */

	public function group() {

		return isset( $_GET[ $this->active_tab ] ) ? $_GET[ $this->active_tab ] : $this->main_tab;

	}

	/**
	 * Tab status.
	 *
	 * @param $tab
	 *
	 * @return string
	 */

	public function tab_status( $tab ) {

		if ( ! isset( $_GET[ $this->active_tab ] ) && $this->main_tab === $tab['id'] ) {
			return 'nav-tab-active';
		}

		if ( isset( $_GET[ $this->active_tab ] ) && $_GET[ $this->active_tab ] === $tab['id'] ) {
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
			$option->args['key'] = $option->key;

			register_setting( $this->group, $option->key, $option->args );

			if ( 'section' === $option->callback ) {

				add_settings_section(
					$option->key,
					$option->label,
					[ $this, $option->callback ],
					$this->group
				);

			}
			else {

				add_settings_field(
					$option->key,
					$option->label,
					[ $this, $option->callback ],
					WP_DATA_SYNC_CAP,
					$this->group,
					$option->args
				);

			}

		}

	}

	/**
	 * Get the plugin options.
	 *
	 * @return array|mixed
	 */

	public function get_options() {

		$options = $this->options();

		return isset( $options[ $this->group ] ) ? $options[ $this->group ] : [];

	}

	/**
	 * Plugin options.
	 *
	 * @return array
	 */

	public function options() {

		$options = [
			'dashboard' => [
				0 => (object) [
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
				1 => (object) [
					'key' 		=> 'wp_data_sync_access_key',
					'label'		=> __( 'API Access Key', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_key',
						'basename'          => 'text-input',
						'type'		        => 'password',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				2 => (object) [
					'key' 		=> 'wp_data_sync_private_key',
					'label'		=> __( 'API Private Key', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_key',
						'basename'          => 'text-input',
						'type'		        => 'password',
						'class'		        => 'regular-text',
						'placeholder'       => ''
					]
				],
				3 => (object) [
					'key' 		=> 'wp_data_sync_api_url',
					'label'		=> __( 'API URL', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'text-input',
						'type'		        => 'url',
						'class'		        => 'regular-text',
						'placeholder'       => __( 'https://domain.com', 'wp-data-sync' ),
						'info'              => __( 'The URL of your WP Data Sync API account.' )
					]
				],
				4 => (object) [
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
				0 => (object) [
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
				1 => (object) [
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
				2 => (object) [
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
				3 => (object) [
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
				4 => (object) [
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
				5 => (object) [
					'key' 		=> 'wp_data_sync_force_delete',
					'label'		=> __( 'Force Delete', 'wp-data-sync' ),
					'callback'  => 'input',
					'args'      => [
						'sanitize_callback' => 'sanitize_text_field',
						'basename'          => 'select',
						'selected'          => get_option( 'wp_data_sync_force_delete' ),
						'name'              => 'wp_data_sync_force_delete',
						'class'             => 'force-delete widefat',
						'values'            => [
							'false' => __( 'No, put item in the trash (Recommended)', 'wp-data-sync' ),
							'true'  => __( 'Yes, delete item and all associated data', 'wp-data-sync' )
						]
					]
				],
				6 => (object) [
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
				7 => (object) [
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
			'logs' => [
				0 => (object) [
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
				]
			]
		];

		return apply_filters( 'wp_data_sync_settings', $options, $this );

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
	 * Settings section.
	 *
	 * @param $args
	 */

	public function section( $args ) {
		return;
	}

}