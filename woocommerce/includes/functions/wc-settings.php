<?php
/**
 * Settings
 *
 * Plugin settings
 *
 * @since   1.0.0
 *
 * @package WP_Data_Sync
 */

namespace WP_DataSync\Woo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_filter( 'wp_data_sync_settings', function ( $settings, $_settings ) {

    /**
     * WooCommerce Tab
     */

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_product_visibility',
        'label'    => __( 'Default Product Visibility', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'select',
            'selected'          => get_option( 'wp_data_sync_product_visibility' ),
            'name'              => 'wp_data_sync_product_visibility',
            'class'             => 'product-visibility widefat',
            'values'            => [
                'visible'                                  => __( 'Shop and search results', 'woocommerce' ),
                'exclude-from-search'                      => __( 'Shop only', 'woocommerce' ),
                'exclude-from-catalog'                     => __( 'Search results only', 'woocommerce' ),
                'exclude-from-catalog,exclude-from-search' => __( 'Hidden', 'woocommerce' ),
                'featured'                                 => __( 'Featured', 'woocommerce' )
            ]
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_use_current_product_visibility',
        'label'    => __( 'Use Current Product Visibility', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'Use the current product visibility if the product visibility is already set. This will prevent DataSync from updating the current product visibility on existing products.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_product_brand_taxonomy',
        'label'    => __( 'Product Brand Taxonomy', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'Activate brand taxonomy on WooCommerce products.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_process__crosssell_ids',
        'label'    => __( 'Defined Cross-Sells', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'This relates the IDs from your data source with the IDs from your website. Please note, if the related product does not exist, this system will relate the product when it is created in the data sync.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_process__upsell_ids',
        'label'    => __( 'Defined Up-Sells', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'This relates the IDs from your data source with the IDs from your website. Please note, if the related product does not exist, this system will relate the product when it is created in the data sync.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_dynamic_cross_sells_is_active',
        'label'    => __( 'Dynamic Product Cross-Sells', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'Dynamically display cross-sell products on the product pages. Please note: your theme must support cross-sells for products.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_dynamic_cross_sells_quantity',
        'label'    => __( 'Dynamic Product Cross-Sells Quantity', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'select',
            'selected'          => get_option( 'wp_data_sync_dynamic_cross_sells_quantity' ),
            'name'              => 'wp_data_sync_dynamic_cross_sells_quantity',
            'id'                => 'wp_data_sync_dynamic_cross_sells_quantity',
            'type'              => '',
            'class'             => 'regular-text',
            'placeholder'       => '',
            'info'              => __( '', 'wp-data-sync' ),
            'values' =>[
                'all' => 'All',
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
                '7' => '7',
                '8' => '8',
                '9' => '9',
                '10' => '10',
                '11' => '11',
                '12' => '12',
                '13' => '13',
                '14' => '14',
                '15' => '15',
                '16' => '16',
                '17' => '17',
                '18' => '18',
                '19' => '19',
                '20' => '20'
            ]
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_dynamic_cross_sells_sort_order',
        'label'    => __( 'Dynamic Product Cross-Sells Sort Order', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'select',
            'selected'          => get_option( 'wp_data_sync_dynamic_cross_sells_sort_order' ),
            'name'              => 'wp_data_sync_dynamic_cross_sells_sort_order',
            'id'                => 'wp_data_sync_dynamic_cross_sells_sort_order',
            'type'              => '',
            'class'             => 'regular-text',
            'placeholder'       => '',
            'info'              => __( '', 'wp-data-sync' ),
            'values' =>[
                'random' => __( 'Random', 'wp-daya-sync' ),
                'oldest' => __( 'Oldest', 'wp-daya-sync' ),
                'newest' => __( 'Newest', 'wp-daya-sync' )
            ]
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_dynamic_up_sells_is_active',
        'label'    => __( 'Dynamic Product Up-Sells', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'Dynamically display up-sell products on the product pages. Please note: your theme must support up-sells for products.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_dynamic_up_sells_quantity',
        'label'    => __( 'Dynamic Product Up-Sells Quantity', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'select',
            'selected'          => get_option( 'wp_data_sync_dynamic_up_sells_quantity' ),
            'name'              => 'wp_data_sync_dynamic_up_sells_quantity',
            'id'                => 'wp_data_sync_dynamic_up_sells_quantity',
            'type'              => '',
            'class'             => 'regular-text',
            'placeholder'       => '',
            'info'              => __( '', 'wp-data-sync' ),
            'values' =>[
                'all' => 'All',
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
                '5' => '5',
                '6' => '6',
                '7' => '7',
                '8' => '8',
                '9' => '9',
                '10' => '10',
                '11' => '11',
                '12' => '12',
                '13' => '13',
                '14' => '14',
                '15' => '15',
                '16' => '16',
                '17' => '17',
                '18' => '18',
                '19' => '19',
                '20' => '20'
            ]
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_dynamic_up_sells_sort_order',
        'label'    => __( 'Dynamic Product Up-Sells Sort Order', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'select',
            'selected'          => get_option( 'wp_data_sync_dynamic_up_sells_sort_order' ),
            'name'              => 'wp_data_sync_dynamic_up_sells_sort_order',
            'id'                => 'wp_data_sync_dynamic_up_sells_sort_order',
            'type'              => '',
            'class'             => 'regular-text',
            'placeholder'       => '',
            'info'              => __( '', 'wp-data-sync' ),
            'values' =>[
                'random' => __( 'Random', 'wp-daya-sync' ),
                'oldest' => __( 'Oldest', 'wp-daya-sync' ),
                'newest' => __( 'Newest', 'wp-daya-sync' )
            ]
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_allow_duplicate_sku',
        'label'    => __( 'Allow Duplicate SKU', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => 'allow-duplicate-sku',
            'placeholder'       => '',
            'info'              => __( 'Allow WooCommerce to use the same SKU for multiple products or variations. This can cause issues with some functionality. Proceed at your own risk.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_update_duplicate_fields',
        'label'    => __( 'Update Duplicate Fields', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => [ $_settings, 'sanitize_array' ],
            'basename'          => 'select-multiple',
            'name'              => 'wp_data_sync_update_duplicate_fields',
            'type'              => '',
            'class'             => 'update-duplicate-fields regular-text',
            'placeholder'       => '',
            'info'              => __( 'When multiple products use the same indetifier, update the selected fields for all products with a duplicate indetifier value.', 'wp-data-sync' ),
            'selected'          => get_option( 'wp_data_sync_update_duplicate_fields', [ 'none' ] ),
            'options'           => apply_filters( 'wp_data_sync_update_duplicate_field_options', [
                'none'              => __( 'None' ),
                '_manage_stock'     => __( 'Manage Stock', 'woocommerce' ),
                '_stock'            => __( 'Stock Quantity', 'woocommerce' ),
                '_backorders'       => __( 'Allow Backorders', 'woocommerce' ),
                '_low_stock_amount' => __( 'Low Stock Threshold', 'woocommerce' ),
                '_regular_price'    => __( 'Regular Price', 'woocommerce' ),
                '_sale_price'       => __( 'Sale Price', 'woocommerce' ),
                '_weight'           => __( 'Weight', 'woocommerce' ),
                '_length'           => __( 'Length', 'woocommerce' ),
                '_width'            => __( 'Width', 'woocommerce' ),
                '_height'           => __( 'Height', 'woocommerce' )
            ], $_settings )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_manage_backorder_status',
        'label'    => __( 'Manage Backorder Status', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'type'              => '',
            'class'             => '',
            'placeholder'       => '',
            'info'              => __( 'Set backorder status based on stock quantity. NOTE: Allow backorders must be set for each product.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_convert_product_weight',
        'label'    => __( 'Convert Product Weight', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'select',
            'selected'          => get_option( 'wp_data_sync_convert_product_weight' ),
            'name'              => 'wp_data_sync_convert_product_weight',
            'class'             => 'convert-product-weight widefat',
            'info'              => __( 'Convert the product weight. If you do not see the conversion you need. Please contact our support team to have it added.', 'wp-data-sync' ),
            'values'            => [
                '0'               => __( 'Do Not Convert Weight', 'wp-data-sync' ),
                'grams_kilograms' => __( 'Grams to Kilograms', 'wp-data-sync' ),
                'kilograms_grams' => __( 'Kilograms to Grams', 'wp-data-sync' ),
                'ounces_pounds'   => __( 'Pounds to Ounces', 'wp-data-sync' ),
                'pounds_ounces'   => __( 'Ounces to Pounds', 'wp-data-sync' ),
            ]
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_regular_price_adjustment',
        'label'    => __( 'Regular Price Adjustment (%)', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'floatval',
            'basename'          => 'text-input',
            'type'              => 'number',
            'class'             => 'regular-price-adjustment',
            'placeholder'       => '',
            'info'              => __( 'Multiply price to add/subtract price adjustment.', 'wp-data-sync' )
        ]
    ];

    $settings['woocommerce'][] = [
        'key'      => 'wp_data_sync_sale_price_adjustment',
        'label'    => __( 'Sale Price Adjustment (%)', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'floatval',
            'basename'          => 'text-input',
            'type'              => 'number',
            'class'             => 'sale-price-adjustment',
            'placeholder'       => '',
            'info'              => __( 'Multiply price to add/subtract price adjustment.', 'wp-data-sync' )
        ]
    ];

    /**
     * WooCommerce Orders Tab
     */

    $settings['orders'][] = [
        'key'      => 'wp_data_sync_order_sync_allowed',
        'label'    => __( 'Allow Order Sync', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'tyoe'              => '',
            'class'             => 'sync-orders',
            'placeholder'       => '',
            'info'              => __( 'Allow order details to sync with the WP Data Sync API.', 'wp-data-sync' )
        ]
    ];

    $settings['orders'][] = [
        'key'      => 'wp_data_sync_allowed_order_status',
        'label'    => __( 'Allowed Order Status', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => [ $_settings, 'sanitize_array' ],
            'basename'          => 'select-multiple',
            'name'              => 'wp_data_sync_allowed_order_status',
            'type'              => '',
            'class'             => 'wc-enhanced-select regular-text',
            'placeholder'       => '',
            'selected'          => get_option( 'wp_data_sync_allowed_order_status', [] ),
            'options'           => apply_filters( 'wp_data_sync_allowed_order_status', [
                'wc-pending'    => __( 'Pending', 'woocommerce' ),
                'wc-processing' => __( 'Processing', 'woocommerce' ),
                'wc-on-hold'    => __( 'On Hold', 'woocommerce' ),
                'wc-completed'  => __( 'Completed', 'woocommerce' ),
                'wc-refunded'   => __( 'Refunded', 'woocommerce' )
            ] )
        ]
    ];

    $settings['orders'][] = [
        'key'      => 'wp_data_sync_order_allowed_product_cats',
        'label'    => __( 'Allowed Product Categories', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => [ $_settings, 'sanitize_array' ],
            'basename'          => 'select-multiple',
            'name'              => 'wp_data_sync_order_allowed_product_cats',
            'type'              => '',
            'class'             => 'wc-enhanced-select regular-text',
            'placeholder'       => '',
            'selected'          => get_option( 'wp_data_sync_order_allowed_product_cats', [] ),
            'info'              => __( 'Include products with selected categories in order sync.', 'wp-data-sync' ),
            'options'           => product_get_category_options_array()
        ]
    ];

    $settings['orders'][] = [
        'key'      => 'wp_data_sync_order_require_valid_product',
        'label'    => __( 'Require Valid Product', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'tyoe'              => '',
            'class'             => 'sync-order-without-valid-product',
            'placeholder'       => '',
            'info'              => __( 'Require a valid poroduct in the order.', 'wp-data-sync' )
        ]
    ];

    $settings['orders'][] = [
        'key'      => 'wp_data_sync_show_order_sync_status_admin_column',
        'label'    => __( 'Show Order Sync Status Admin Column', 'wp-data-sync' ),
        'callback' => 'input',
        'args'     => [
            'sanitize_callback' => 'sanitize_text_field',
            'basename'          => 'checkbox',
            'tyoe'              => '',
            'class'             => 'show-admin-column',
            'placeholder'       => '',
            'info'              => __( 'Show admin column for order export status on Orders list.', 'wp-data-sync' )
        ]
    ];

    return $settings;

}, 1, 2 );
