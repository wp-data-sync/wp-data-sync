<?php
/**
 * Require
 *
 * Require plugin files.
 *
 * @since   3.0.0
 *
 * @package WP_Data_Sync
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstracts
 */
require WPDSYNC_PATH . 'includes/abstracts/abstract.Request.php';

/**
 * Classes
 */
require WPDSYNC_PATH . 'includes/classes/class.DataSync.php';
require WPDSYNC_PATH . 'includes/classes/class.Item.php';
require WPDSYNC_PATH . 'includes/classes/class.ItemInfoRequest.php';
require WPDSYNC_PATH . 'includes/classes/class.ItemRequest.php';
require WPDSYNC_PATH . 'includes/classes/class.KeyRequest.php';
require WPDSYNC_PATH . 'includes/classes/class.Log.php';
require WPDSYNC_PATH . 'includes/classes/class.LogRequest.php';
require WPDSYNC_PATH . 'includes/classes/class.ReportRequest.php';
require WPDSYNC_PATH . 'includes/classes/class.Settings.php';
require WPDSYNC_PATH . 'includes/classes/class.SyncRequest.php';
require WPDSYNC_PATH . 'includes/classes/class.VersionRequest.php';

/**
 * Functions
 */
require WPDSYNC_PATH . 'includes/functions/acf.php';
require WPDSYNC_PATH . 'includes/functions/auto-update.php';
require WPDSYNC_PATH . 'includes/functions/delet-log-file.php';
require WPDSYNC_PATH . 'includes/functions/image-replace.php';
require WPDSYNC_PATH . 'includes/functions/item-updated.php';
require WPDSYNC_PATH . 'includes/functions/message.php';
require WPDSYNC_PATH . 'includes/functions/plugin-action-links.php';
require WPDSYNC_PATH . 'includes/functions/plugin-disable.php';
require WPDSYNC_PATH . 'includes/functions/plugin-update.php';
require WPDSYNC_PATH . 'includes/functions/post-date-filter.php';
require WPDSYNC_PATH . 'includes/functions/post-sync-status.php';
require WPDSYNC_PATH . 'includes/functions/submit-metabox-synced-item.php';
require WPDSYNC_PATH . 'includes/functions/tooltip.php';
require WPDSYNC_PATH . 'includes/functions/update-notice.php';
require WPDSYNC_PATH . 'includes/functions/verify-invalid-image-urls.php';
require WPDSYNC_PATH . 'includes/functions/view.php';

/**
 * WooCommerce
 */
if ( class_exists( 'WooCommerce' ) ) {

    /**
     * WC
     */
    require WPDSYNC_PATH . 'woocommerce/wc-data-sync.php';

    /**
     * WC Classes
     */
    require 'woocommerce/includes/classes/class.WC_Product_DataSync.php';
    require 'woocommerce/includes/classes/class.WC_Product_Item.php';
    require 'woocommerce/includes/classes/class.WC_Product_Sells.php';

    /**
     * WC Functions
     */
    require 'woocommerce/includes/functions/order-hpos-compatibility.php';
    require 'woocommerce/includes/functions/product-allow-duplicate-sku.php';
    require 'woocommerce/includes/functions/product-attribute-clear-cache.php';
    require 'woocommerce/includes/functions/product-category-options-array.php';
    require 'woocommerce/includes/functions/product-dynamic-sells.php';
    require 'woocommerce/includes/functions/product-excluded-types.php';
    require 'woocommerce/includes/functions/product-fields.php';
    require 'woocommerce/includes/functions/product-format-price.php';
    require 'woocommerce/includes/functions/product-price-adjustment.php';
    require 'woocommerce/includes/functions/product-restricted-meta-keys.php';
    require 'woocommerce/includes/functions/product-sells.php';
    require 'woocommerce/includes/functions/product-stock-qty-for-backorder.php';
    require 'woocommerce/includes/functions/product-update-duplicate-fields.php';
    require 'woocommerce/includes/functions/product-weight-conversion.php';
    require 'woocommerce/includes/functions/wc-rest-api.php';
    require 'woocommerce/includes/functions/wc-settings.php';
    require 'woocommerce/includes/functions/wc-tabs.php';
    require 'woocommerce/includes/functions/wc-update.php';

}

/**
 * Tests
 */
if ( defined( 'WPDS_LOCAL_DEV' ) && WPDS_LOCAL_DEV ) {
    require 'tests/data-sync.php';
    require 'tests/get-order.php';
}
