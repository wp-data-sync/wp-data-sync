<?php
/**
 * UserSync
 *
 * Process user data.
 *
 * @since   2.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UserSync {

	/**
	 * @var array
	 */

	private $primary_id;

	/**
	 * @var WP_User
	 */

	private $user;

	/**
	 * @var int
	 */

	private $user_id;

	/**
	 * @var array
	 */

	private $user_data;

	/**
	 * @var array
	 */

	private $user_meta;

	/**
	 * @var bool
	 */

	private $is_new = false;

	/**
	 * @var UserSync
	 */

	public static $instance;

	/**
	 * UserSync constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return UserSync
	 */

	public static function instance() {

		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Set Properties
	 *
	 * Set property values.
	 *
	 * @param $data
	 */

	public function set_properties( $data ) {

		if ( is_array( $data ) ) {

			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}

		}

    }

	/**
	 * Set User ID.
	 *
	 * @param bool|int $user_id
	 */

	public function set_user_id( $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = $this->user_id();
		}

		$this->user_id = $user_id;

	}

	/**
	 * Set User.
	 *
	 * @param bool $user_id
	 */

	public function set_user( $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = $this->user_id;
		}

		$this->user = new WP_User( $user_id );

	}

	/**
	 * Set User Data.
	 *
	 * @param array $user_data
	 */

	public function set_user_data( $user_data ) {
		$this->user_data = $user_data;
	}

	/**
	 * Set User Data.
	 *
	 * @param array $user_meta
	 */

	public function set_user_meta( $user_meta ) {
		$this->user_meta = $user_meta;
	}

	/**
	 * Process request data.
	 *
	 * @return mixed
	 */

	public function process() {

		// A primary ID is required!!
		if ( empty( $this->primary_id ) ) {
			return [ 'error' => 'Primary ID empty!!' ];
		}

		// Set the user_id.
		$this->set_user_id();

		if ( empty( $this->user_id ) ) {
			return [ 'error' => 'User ID failed!!' ];
		}

		$this->set_user();

		if ( ! empty( $this->user_data ) && is_array( $this->user_data ) ) {
			$this->user_data();
		}

		if ( ! empty( $this->user_meta ) && is_array( $this->user_meta ) ) {
			$this->user_meta();
		}

		do_action( 'wp_data_sync_after_process_user', $this->user_id, $this );

		return [ 'user_id' => $this->user_id ];

	}

	/**
	 * User ID.
	 *
	 * @return bool|int
	 */

	public function user_id() {

		switch ( $this->primary_id['search_in'] ) {

			case 'users' :
				return $this->search_in_users();

			case 'usermeta' :
				return $this->search_in_usermeta();

		}

		return false;

	}

	/**
	 * Search in Users.
	 *
	 * @return bool|int
	 */

	public function search_in_users() {

		global $wpdb;

		extract( $this->primary_id );

		if ( 'user_login' === $column ) {
			$value = sanitize_key( $value );
		}

		$user_id = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT ID 
    		FROM $wpdb->users
    		WHERE $column = %s 
      		ORDER BY ID DESC
			",
			esc_sql( $value )
		) );

		if ( empty( $user_id ) || is_wp_error( $user_id ) ) {

			if ( is_wp_error( $user_id ) ) {
				Log::write( 'wp-error-search-in-users', $user_id );
			}

			if ( $user_id = $this->check_current_users() ) {
				return $user_id;
			}

			$this->is_new = true;

			$user_id = $this->insert_placeholder();

		}

		return $user_id ? (int) $user_id : false;

	}

	/**
	 * Search in Usermeta.
	 *
	 * @return bool|int
	 */

	public function search_in_usermeta() {

		global $wpdb;

		extract( $this->primary_id );

		$user_id = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT user_id 
    		FROM $wpdb->usermeta
    		WHERE meta_key = %s 
      		AND meta_value = %s 
      		ORDER BY umeta_id DESC
			",
			esc_sql( $key ),
			esc_sql( $value )
		) );

		if ( empty( $user_id ) || is_wp_error( $user_id ) ) {

			if ( is_wp_error( $user_id ) ) {
				Log::write( 'wp-error-search-in-usermeta', $user_id );
			}

			if ( $user_id = $this->check_current_users() ) {
				return $user_id;
			}

			$this->is_new = true;

			$user_id = $this->insert_placeholder();

		}

		return $user_id ? (int) $user_id : false;

	}

	/**
	 * Check current usrs.
	 *
	 * Check our current users to see if they might already exist.
	 *
	 * @return bool|int
	 */

	public function check_current_users() {

		if ( ! empty( $this->user_data['user_email'] ) ) {

			if ( Settings::is_checked( 'wp_data_sync_check_current_user_email' ) ) {

				if ( $user = get_user_by( 'email', sanitize_email( $this->user_data['user_email'] ) ) ) {
					return (int) $user->ID;
				}

			}

		}

		if ( ! empty( $this->user_data['user_login'] ) ) {

			if ( Settings::is_checked( 'wp_data_sync_check_current_user_login' ) ) {

				if ( $user = get_user_by( 'login', $this->user_data['user_login'] ) ) {
					return (int) $user->ID;
				}

			}

		}

		return false;

	}

	/**
	 * Unique Username.
	 *
	 * @return string
	 */

	public function unique_username() {

		$i         = 2;
		$user_name = sanitize_key( $this->user_data['user_login'] );

		while( username_exists( $user_name ) ) {
			$user_name = $user_name . $i;
			$i++;
		}

		return $user_name;

	}

	/**
	 * Insert placeholder user.
	 *
	 * @return bool|int
	 */

	public function insert_placeholder() {

		$rand = 'placeholder' . rand();

		$user_id = wp_insert_user( [
			'user_login'    => $rand,
			'user_nicename' => $rand,
			'user_pass'     => null
		] ) ;

		// On success.
		if ( is_wp_error( $user_id ) ) {

			Log::write( 'wp-error-insert-user', $user_id );

			return false;
		}

		return (int) $user_id;

	}

	/**
	 * User Data.
	 */

	public function user_data() {

		global $wpdb;
		
		if ( ! empty( $this->user_data['user_login'] ) ) {

			$unique_username = $this->unique_username();

			$this->user_data['user_login']    = $unique_username;
			$this->user_data['user_nicename'] = $unique_username;

			if ( $this->is_new && empty( $this->user_data['display_name'] ) ) {
				$this->user_data['display_name'] = $unique_username;
			}

		}

		Log::write( 'user-data', $this->user_data );

		$result = $wpdb->update(
			$wpdb->users,
			$this->user_data,
			[ 'ID' => $this->user_id ]
		);

		if ( is_wp_error( $result ) ) {
			Log::write( 'wp-error-update-user', $result );
		}

	}

	/**
	 * User Meta.
	 */

	public function user_meta() {

		if ( $this->is_new ) {

			if ( $role = get_option( 'wp_data_sync_default_user_role' ) ) {
				$this->user->set_role( $role );
			}

			/**
			 * Unique Unsername has already been applied
			 *
			 * @see UserSync::unique_username()
			 */
			if ( ! empty( $this->user_data['user_login'] ) ) {
				update_user_meta( $this->user_id, 'nickname', $this->user_data['user_login'] );
			}

		}

		foreach ( $this->user_meta as $meta_key => $meta_value ) {
			update_user_meta( $this->user_id, $meta_key, $meta_value );
		}

	}

	/**
	 * Get User.
	 *
	 * @return WP_User
	 */

	function get_user() {
		return $this->user;
	}

	/**
	 * Get User ID.
	 *
	 * @return int
	 */

	function get_user_id() {
		return $this->user_id;
	}

	/**
	 * Get User Data.
	 *
	 * @return array
	 */

	function get_user_data() {
		return $this->user_data;
	}

	/**
	 * Get user Meta.
	 *
	 * @return array
	 */

	function get_user_meta() {
		return $this->user_meta;
	}

}