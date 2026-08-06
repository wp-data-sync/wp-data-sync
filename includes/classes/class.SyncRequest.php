<?php
/**
 * SyncRequest
 *
 * Process the DataSync Request.
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\App;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SyncRequest extends Request {

	/**
	 * @var string
	 */

	protected string $access_token_key = 'wp_data_sync_access_token';

	/**
	 * @var string
	 */

	protected string $private_token_key = 'wp_data_sync_private_token';

	/**
	 * @var string
	 */

	protected string $permissions_key = 'wp_data_sync_allowed';

	/**
	 * @var string
	 */

	protected string $log_key = 'sync-request-data';

	/**
	 * SyncRequest constructor.
	 */

	public function __construct() {}

	/**
	 * Instance.
	 *
	 * @return SyncRequest
	 */

	public static function instance(): SyncRequest {
        return new self();
	}

	/**
	 * Register the route.
	 *
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
	 *
	 * @author Kevin Brent
	 */

	public function register_route(): void {

		register_rest_route(
            $this->namespace,
            "/$this->ep_version/sync/(?P<access_token>\S+)/(?P<cache_buster>\S+)/",
			[
				'methods' => WP_REST_Server::CREATABLE,
				'args'    => [
					'access_token' => [
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'access_token' ]
					],
					'cache_buster' => [
						'validate_callback' => function( $param ) {
							return is_string( $param );
						}
					]
				],
				'permission_callback' => [ $this, 'access' ],
				'callback'            => [ $this, 'process' ],
			]
		);

	}

	/**
	 * Process the request.
	 *
	 * @return WP_REST_Response
	 *
	 * @author Kevin Brent
	 */

	public function process(): WP_REST_Response {

		/**
		 * Disable revisions.
		 */

		add_filter( 'wp_revisions_to_keep', '__return_false' );

		$start_request = microtime( true );
		$data          = $this->request_data();
		$was_deferred  = wp_defer_term_counting();
		$completed     = [];

		if ( ! $was_deferred ) {
			wp_defer_term_counting( true );
		}

		try {
			if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
				foreach ( $data['items'] as $item ) {
					if ( is_array( $item ) ) {
						$data_sync = $this->process_item( $item );

						if ( $data_sync instanceof DataSync ) {
							$completed[] = $data_sync;
						}
					}
				}
			} elseif ( is_array( $data ) ) {
				$data_sync = $this->process_item( $data );

				if ( $data_sync instanceof DataSync ) {
					$completed[] = $data_sync;
				}
			}
		} finally {
			if ( ! $was_deferred ) {
				wp_defer_term_counting( false );
			}
		}

		foreach ( $completed as $data_sync ) {
			/**
			 * Fires after a top-level request item, its integrations, and deferred
			 * taxonomy counts are persisted.
			 *
			 * @param int      $post_id  Post ID.
			 * @param DataSync $data_sync Data sync instance.
			 *
			 * @author Kevin Brent
			 */
			do_action( 'wp_data_sync_completed', $data_sync->get_post_id(), $data_sync );
		}

		self::$response['request_time'] = microtime( true ) - $start_request;
		Log::set( 'sync-request-response', self::$response );
        Log::write();

		return rest_ensure_response( self::$response );

	}

	/**
	 * Process one item using an isolated data-sync instance.
	 *
	 * @param array $item Item data.
	 *
	 * @return DataSync|null Processed instance on success, or null on failure.
	 *
	 * @author Kevin Brent
	 */
	private function process_item( array $item ): ?DataSync {
		$data_sync = DataSync::instance();
		$data_sync->set_properties( $item );

		if ( $data_sync->process() ) {
			return $data_sync;
		}

		return null;
	}

}
