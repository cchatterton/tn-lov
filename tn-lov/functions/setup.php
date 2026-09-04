<?php
/**
 * Plugin activation and migration setup.
 */

if (!defined('ABSPATH')) {
    exit;
}

function tn_lov_activate(bool $network_wide = false): void {
    if (!$network_wide || !is_multisite()) {
        tn_lov_clone_legacy_values_for_current_site();
        return;
    }

    $offset = 0;
    $batch_size = 100;

    do {
        $site_ids = get_sites(
            array(
                'fields' => 'ids',
                'number' => $batch_size,
                'offset' => $offset,
            )
        );

        foreach ($site_ids as $site_id) {
            switch_to_blog((int) $site_id);

            try {
                tn_lov_clone_legacy_values_for_current_site();
            } finally {
                restore_current_blog();
            }
        }

        $offset += count($site_ids);
    } while (count($site_ids) === $batch_size);
}

function tn_lov_clone_legacy_values_for_current_site(): void {
    if (false !== get_option('tn_lov_index', false)) {
        return;
    }

    $legacy_index = get_option('lov_index', array());
    update_option('tn_lov_index', is_array($legacy_index) ? $legacy_index : array(), false);
}
