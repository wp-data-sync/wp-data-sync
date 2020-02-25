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

class Settings {

	/**
	 * @var Settings
	 */

	public static $instance;

	/**
	 * @var string
	 */

	private $group = 'wp_data_sync_settings';

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

		add_action( 'admin_init', [ $this, 'register_settings' ], 2 );
		add_action( 'admin_menu', [ $this, 'add_pages' ], 5 );

	}

	/**
	 * Register the settings.
	 */

	public function register_settings() {

		$options = $this->get_settings();

		foreach ( $options as $option ) {

			// Add the key into the args array
			$option->args['key'] = $option->key;

			register_setting( $this->group, $option->key, $option->args );
			add_settings_field(
				$option->key,
				$option->label,
				[ $this, $option->callback ],
				WP_DATA_SYNC_CAP,
				'default',
				// Args must be an array for WP.
				$option->args
			);

		}

	}

	/**
	 * Get the plugin settings.
	 *
	 * @return array
	 */

	public function get_settings() {

		$settings = [
			0 => (object) [
				'key' 		=> 'wp_data_sync_allowed',
				'label'		=> __( 'Data Sync Allowed' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'type'		        => '',
					'class'		        => '',
					'placeholder'       => ''
				]
			],
			10 => (object) [
				'key' 		=> 'wp_data_sync_access_token',
				'label'		=> __( 'API Access Token' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_key',
					'basename'          => 'text-input',
					'type'		        => 'password',
					'class'		        => 'regular-text',
					'placeholder'       => ''
				]
			],
			20 => (object) [
				'key' 		=> 'wp_data_sync_access_secret',
				'label'		=> __( 'API Access Secret' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_key',
					'basename'          => 'text-input',
					'type'		        => 'password',
					'class'		        => 'regular-text',
					'placeholder'       => ''
				]
			],
			30 => (object) [
				'key' 		=> 'wp_data_sync_api_url',
				'label'		=> __( 'API URL' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'text-input',
					'type'		        => 'url',
					'class'		        => 'regular-text',
					'placeholder'       => 'https://domain.com'
				]
			],
			40 => (object) [
				'key' 		=> 'wp_data_sync_allow_logging',
				'label'		=> __( 'Allow Logging' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'type'		        => '',
					'class'		        => '',
					'placeholder'       => ''
				]
			],
			50 => (object) [
				'key' 		=> 'wp_data_sync_auto_update',
				'label'		=> __( 'Automatically Update Plugin' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'checkbox',
					'type'		        => '',
					'class'		        => '',
					'placeholder'       => ''
				]
			],
			60 => (object) [
				'key' 		=> 'wp_data_sync_post_title',
				'label'		=> __( 'Default Title' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'text-input',
					'type'		        => 'text',
					'class'		        => 'regular-text',
					'placeholder'       => ''
				]
			],
			70 => (object) [
				'key' 		=> 'wp_data_sync_post_author',
				'label'		=> __( 'Default Author' ),
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
			80 => (object) [
				'key' 		=> 'wp_data_sync_post_status',
				'label'		=> __( 'Default Status' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'select',
					'selected'          => get_option( 'wp_data_sync_post_status' ),
					'name'              => 'wp_data_sync_post_status',
					'class'             => 'default-status widefat',
					'values'            => [
						'publish' => 'Publish',
						'pending' => 'Pending',
						'draft'   => 'Draft',
						'future'  => 'Future',
						'private' => 'Private',
						'trash'   => 'Trash'
					]
				]
			],
			90 => (object) [
				'key' 		=> 'wp_data_sync_post_type',
				'label'		=> __( 'Default Type' ),
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
			100 => (object) [
				'key' 		=> 'wp_data_sync_append_terms',
				'label'		=> __( 'Append Terms' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'select',
					'selected'          => get_option( 'wp_data_sync_append_terms' ),
					'name'              => 'wp_data_sync_append_terms',
					'class'             => 'append-terms widefat',
					'values'            => [
						'true'  => __( 'Yes, I want mutiple terms per post' ),
						'false' => __( 'No, I only want 1 term per post' )
					]
				]
			],
			110 => (object) [
				'key' 		=> 'wp_data_sync_force_delete',
				'label'		=> __( 'Force Delete' ),
				'callback'  => 'input',
				'args'      => [
					'sanitize_callback' => 'sanitize_text_field',
					'basename'          => 'select',
					'selected'          => get_option( 'wp_data_sync_force_delete' ),
					'name'              => 'wp_data_sync_force_delete',
					'class'             => 'force-delete widefat',
					'values'            => [
						'false' => __( 'No, put item in the trash (Recommended)' ),
						'true'  => __( 'Yes, delete item and all associated data' )
					]
				]
			]
		];

		return apply_filters( 'wp_data_sync_settings', $settings );

	}

	/**
	 * Add settings page to Settings submenu.
	 */

	public function add_pages() {

		add_options_page(
			__( 'WP Data Sync', 'wp-data-sync' ),
			__( 'WP Data Sync', 'wp-data-sync' ),
			WP_DATA_SYNC_CAP,
			'wp-data-sync',
			[ $this, 'settings_page' ]
		);

	}

	/**
	 * Display the settings page.
	 */

	public function settings_page() {

		if ( ! current_user_can( WP_DATA_SYNC_CAP ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.') );
		}

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

		return apply_filters( 'wp_data_sync_view', $view );

	}

	/**
	 * Get the input file.
	 *
	 * @param $args
	 */

	public function input( $args ) {

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

}