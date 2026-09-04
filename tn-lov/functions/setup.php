<?php
/**
 * Plugin activation and migration setup.
 */

if (!defined('ABSPATH')) {
    exit;
}

function tn_lov_activate(): void {
    if (false !== get_option('tn_lov_index', false)) {
        return;
    }

    $legacy_index = get_option('lov_index', array());
    update_option('tn_lov_index', is_array($legacy_index) ? $legacy_index : array(), false);
}
