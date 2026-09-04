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
}
