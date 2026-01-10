<?php
/**
 * WC_Product_Sells
 *
 * Process WooCommerce cross sells and up sells
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

use WP_DataSync\App\Log;
use WP_DataSync\App\Settings;
use WC_Product;

class WC_Product_Sells {

    /**
     * @var string
     */

    private string  $table;

	/**
	 * @var string
	 */

	private string $type;

	/**
	 * @var array
	 */

	private array $sell_ids;

	/**
	 * @var string
	 */

	private string $relational_id;

	/**
	 * @var string
	 */

	private string $relational_key;

    /**
     * @var array
     */

    private array $product_ids = [];

    /**
     * @var WC_Product
     */

    private WC_Product $product;

	/**
	 * WC_Product_Sells constructor.
	 */

	public function __construct() {
        global $wpdb;

       $this->table =  $wpdb->prefix . 'data_sync_sell_actions';
    }

	/**
	 * Instance.
	 *
	 * @return WC_Product_Sells
	 */

	public static function instance(): WC_Product_Sells {
		return new self();
	}

	/**
	 * Set properties.
	 *
	 * @param $args array
     *
     * @return bool
	 */

	public function set_properties( array $args ): bool {

		foreach ( $args as $key => $value ) {
			$this->$key = $value;
		}

        $args['active'] = Settings::is_checked( "wp_data_sync_process__{$this->type}sell_ids" );

        Log::write( "product-{$this->type}-sells", $args, 'Properties' );

        return $args['active'];

	}

	/**
	 * Get product IDs.
	 *
	 * @return array
	 */

	public function get_product_ids(): array {

		global $wpdb;

        $placeholders = implode( ',', array_fill( 0, count( $this->sell_ids ), '%s' ) );

		$sql = $wpdb->prepare(
			"
			SELECT p.ID
			FROM $wpdb->posts p
			INNER JOIN $wpdb->postmeta pm
			    ON p.ID = pm.post_id 
		            AND pm.meta_key = %s 
			        AND pm.meta_value IN ($placeholders)
			WHERE p.post_type = 'product'
			    AND p.post_status = 'publish'
			",
            array_merge(
                [ esc_sql( $this->relational_key ) ],
			    array_map( 'esc_sql', $this->sell_ids )
            )
		);

        $product_ids = $wpdb->get_col( $sql );

        Log::write( "product-{$this->type}-sells", [
            'sql'         => $sql,
            'product_ids' => $product_ids
        ], 'Product IDs SQL' );

		if ( empty( $product_ids ) || is_wp_error( $product_ids ) ) {
			return [];
		}

		return array_map( 'intval', $product_ids );

	}

    /**
     * Get related rows.
     *
     * @param $sell_ids
     *
     * @return array|object|\stdClass[]
     */

    public function get_related_rows( $sell_ids ) {

        global $wpdb;

        $placeholders = implode( ',', array_fill( 0, count( $sell_ids ), '%s' ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "
			SELECT p.ID + 0 AS product_id, pm.meta_key
			FROM $wpdb->posts p
			INNER JOIN $wpdb->postmeta pm
			    ON p.ID = pm.post_id 
		            AND pm.meta_key IN ('_up_sells_args', '_cross_sells_args') 
			INNER JOIN $wpdb->postmeta pm2
			    ON p.ID = pm2.post_id 
			        AND pm2.meta_value IN ($placeholders)
			WHERE p.post_type = 'product'
			    AND p.post_status = 'publish'
			",
            array_map( 'esc_sql', $sell_ids )
        ), ARRAY_A );

        if ( empty( $results ) || is_wp_error( $results ) ) {
            return [];
        }

        return $results ;

    }

	/**
	 * Save the sell ids.
	 */

	public function save() {

        if( $this->product_ids = $this->get_product_ids() ) {

            if ( 'cross' === $this->type ) {
                $this->product->set_cross_sell_ids( $this->product_ids );
            }
            elseif ( 'up' === $this->type ) {
                $this->product->set_upsell_ids( $this->product_ids );
            }

        }

        $this->product->update_meta_data( "_{$this->type}_sells_args", [
            'type'           => $this->type,
            'relational_key' => $this->relational_key,
            'sell_ids'       => $this->sell_ids
        ] );

        $this->product->save();

	}

    /**
     * Save event.
     *
     * @return void
     */

    public function save_event(): void {
        global $wpdb;

        $wpdb->query( $wpdb->prepare(
            "
            INSERT IGNORE INTO $this->table (hash, sell_ids) 
            VALUES (%s, %s)
            ",
            wp_hash( json_encode( $this->sell_ids ) ),
            serialize( array_map( 'esc_sql', $this->sell_ids ) )
        ) );
    }

    /**
     * Get event.
     *
     * @return array
     */

    public function get_event(): array {
        global $wpdb;

        $result = $wpdb->get_row( $wpdb->prepare(
            "
            SELECT *
            FROM $this->table 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 1 HOUR)
            LIMIT 1
            "
        ), ARRAY_A );

        if ( empty( $result ) || is_wp_error( $result) ) {
            return [];
        }

        $result['sell_ids'] = maybe_unserialize( $result['sell_ids'] );

        return $result;

    }

    /**
     * Delete event.
     *
     * @param int $id
     *
     * @return void
     */

    public function delete_event( int $id ): void {
        global $wpdb;

        $wpdb->delete(
            $this->table,
            [ 'id' => $id ]
        );
    }

    /**
     * Create table.
     *
     * @return void
     */

    public function create_table(): void {

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
			CREATE TABLE IF NOT EXISTS $this->table (
  			id bigint(20) NOT NULL AUTO_INCREMENT,
  			hash varchar(25) NOT NULL,
  			sell_ids longtext NOT NULL,
  			timestamp datetime DEFAULT CURRENT_TIMESTAMP,
  			PRIMARY KEY (id),
  			UNIQUE KEY hash (hash)
			) $charset_collate;
        ";

        dbDelta( $sql );

    }

}