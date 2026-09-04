<?php
/**
 * Admin assets.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'tn_lov_enqueue_admin_assets');

function tn_lov_enqueue_admin_assets(string $hook_suffix): void {
    if ('settings_page_tn-lov' !== $hook_suffix) {
        return;
    }

    wp_enqueue_style(
        'tn-lov-admin',
        TN_LOV_PLUGIN_URL . 'styles/tn-lov.css',
        array(),
        TN_LOV_VERSION
    );
    wp_enqueue_script(
        'tn-lov-admin',
        TN_LOV_PLUGIN_URL . 'scripts/tn-lov.js',
        array(),
        TN_LOV_VERSION,
        true
    );

    wp_localize_script(
        'tn-lov-admin',
        'TN_LOV_ADMIN',
        array(
            'ajaxUrl'      => admin_url('admin-ajax.php'),
            'dismissNonce' => wp_create_nonce('tn_lov_dismiss_migration'),
            'testNonce'    => wp_create_nonce('tn_lov_test_value'),
            'enterKey'     => __('Enter a LOV key to test.', 'tn-lov'),
            'requestFail'  => __('The value could not be tested.', 'tn-lov'),
        )
    );
}
