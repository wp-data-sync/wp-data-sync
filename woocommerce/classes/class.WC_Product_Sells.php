<?php
/**
 * WC_Product_Sells
 *
 * Process WooCommerce cross sells and up sells
 *
 * @since   1.0.0
 *
 * @package WP_DataSync
 */

namespace WP_DataSync\Woo;

use WC_Product;
use WC_Product_Variable;

class WC_Product_Sells {

	/**
	 * @var string
	 */

	private $sell_type;

	/**
	 * @var string
	 */

	private $sell_id;

	/**
	 * @var array
	 */

	private $sell_ids;

	/**
	 * @var string
	 */

	private $relational_key;

	/**
	 * @var WC_Product|WC_Product_Variable
	 */

	private $product;

	/**
	 * @var WC_Product_Sells
	 */

	public static $instance;

	/**
	 * WC_Product_Sells constructor.
	 */

	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Instance.
	 *
	 * @return WC_Product_Sells
	 */

	public static function instance() {

		if ( self::$instance === NULL ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Set properties.
	 *
	 * @param $product
	 * @param $sells
	 * @param $sells_type
	 */

	public function set_properties( $product, $sells, $sell_type ) {

		$this->product        = $product;
		$this->sell_id        = $sells['sell_id'];
		$this->sell_ids       = $sells['sell_ids'];
		$this->relational_key = $sells['relational_key'];
		$this->sell_type      = $sell_type;

	}

	/**
	 * Set sell IDs.
	 */

	public function set_sell_ids() {

		if ( is_array( $this->sell_ids ) ) {

			foreach ( $this->sell_ids as $sell_id ) {

				if ( $product_id = $this->relation_exists( $sell_id ) ) {

					$this->set_sell_id( $product_id, $sell_id );

				}
				elseif ( ! $this->is_sell_id_staged( $sell_id ) ) {

					$this->stage_sell_id( $sell_id );
				}

			}

		}

	}

	/**
	 * Set the sell ID relationship.
	 */

	public function set_relation() {

		$this->product->update_meta_data( $this->relational_key, $this->sell_id );

	}

	/**
	 * Relation exists.
	 *
	 * @param $sell_id
	 *
	 * @return bool|int
	 */

	public function relation_exists( $sell_id ) {

		global $wpdb;

		$product_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT post_id
				FROM $wpdb->postmeta
				WHERE meta_key = %s
				AND meta_value = %s
				",
				$this->relational_key,
				$sell_id
			)
		);

		if ( null === $product_id || is_wp_error( $product_id ) ) {
			return FALSE;
		}

		return (int) $product_id;

	}

	/**
	 * Set sell ID.
	 *
	 * @param $product_id
	 * @param $sell_id
	 */

	public function set_sell_id( $product_id, $sell_id ) {

		$product  = new WC_Product( $product_id );
		$sell_ids = $this->get_sell_ids( $product, $sell_id );

		switch( $this->sell_type ) {

			case 'cross_sells':
				$product->set_cross_sell_ids( $sell_ids );
				break;
			case 'up_sells':
				$product->set_upsell_ids( $sell_ids );
				break;

		}

		$product->save();

	}

	/**
	 * Get the sell ids.
	 *
	 * @param $product
	 * @param $sell_id
	 *
	 * @return array
	 */

	public function get_sell_ids( $product, $sell_id ) {

		switch( $this->sell_type ) {

			case 'cross_sells':
				$sell_ids = $product->get_cross_sell_ids();
				break;
			case 'up_sells':
				$sell_ids = $product->get_upsell_ids();
				break;
			default:
				$sell_ids = [];

		}

		array_push( $sell_ids, $sell_id );

		return $sell_ids;

	}

	/**
	 * Get staged sell ids.
	 */

	public function set_staged_sell_ids() {

		if ( $product_ids = $this->get_staged_sell_ids() ) {

			switch( $this->sell_type ) {

				case 'cross_sells':
					$this->product->set_cross_sell_ids( $product_ids );
					break;
				case 'up_sells':
					$this->product->set_upsell_ids( $product_ids );
					break;

			}

			$this->process_staged_sell_ids( $product_ids );

		}

	}

	/**
	 * Get staged sell ids.
	 *
	 * @return array|bool
	 */

	public function get_staged_sell_ids() {

		global $wpdb;

		$table = self::table();

		$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT product_id
				FROM $table
				WHERE relational_key = %s
				AND sell_type = %s
				AND sell_id = %s
				AND processed = 0
				",
				$this->relational_key,
				$this->sell_type,
				$this->sell_id
			)
		);

		if ( null === $product_ids || is_wp_error( $product_ids ) ) {
			return FALSE;
		}

		return $product_ids;

	}

	/**
	 * Process staged sell ids.
	 *
	 * @param $product_ids
	 */

	public function process_staged_sell_ids( $product_ids ) {

		global $wpdb;

		foreach ( $product_ids as $product_id ) {

			$wpdb->update(
				self::table(),
				[ 'processed' => 1 ],
				[
					'product_id'     => $product_id,
					'sell_type'      => $this->sell_type,
					'sell_id'        => $this->sell_id,
					'relational_key' => $this->relational_key,
				]
			);

		}

	}

	/**
	 * Stage sell id.
	 *
	 * @param $sell_id
	 */

	public function stage_sell_id( $sell_id ) {

		global $wpdb;

		$wpdb->insert( self::table(), [
			'product_id'     => $this->product->get_id(),
			'sell_type'      => $this->sell_type,
			'sell_id'        => $sell_id,
			'relational_key' => $this->relational_key,
		] );

	}

	/**
	 * Is sell id staged.
	 *
	 * @param $sell_id
	 *
	 * @return bool
	 */

	public function is_sell_id_staged( $sell_id ) {

		global $wpdb;

		$table = self::table();

		$staged = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT id
				FROM $table
				WHERE product_id = %d
				AND relational_key = %s
				AND sell_type = %s
				AND sell_id = %s
				",
				$this->product->get_id(),
				$this->relational_key,
				$this->sell_type,
				$sell_id
			)
		);

		if ( null === $staged || is_wp_error( $exists ) ) {
			return FALSE;
		}

		return TRUE;

	}

	/**
	 * Database tabke name.
	 *
	 * @return string
	 */

	public static function table() {

		global $wpdb;

		return $wpdb->prefix . 'data_sync_sells';

	}

	/**
	 * Create the sells database table.
	 */

	public static function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$charset_collate = $wpdb->get_charset_collate();
		$table           = self::table();

		$sql = "
			CREATE TABLE IF NOT EXISTS $table (
  			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  			product_id bigint(20) NOT NULL,
  			sell_type varchar(40) NOT NULL,
  			sell_id varchar(200) NOT NULL,
  			relational_key varchar(200) NOT NULL,
  			processed tinyint(4) NOT NULL DEFAULT 0,
  			PRIMARY KEY (id),
			KEY product_id (product_id),
			KEY sell_type (sell_type),
			KEY relational_key (relational_key),
			KEY processed (processed)
			) $charset_collate;
        ";

		dbDelta( $sql );

	}

	/**
	 * Save the sell ids.
	 */

	public function save() {
		$this->set_sell_ids();
		$this->set_relation();
		$this->set_staged_sell_ids();
		$this->product->save();
	}

}