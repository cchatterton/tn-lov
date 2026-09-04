<?php
/**
 * Plugin activation and migration setup.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'tn_lov_maybe_repair_legacy_migration', 5);

function tn_lov_activate(bool $network_wide = false): void {
    if (!$network_wide || !is_multisite()) {
        tn_lov_clone_legacy_values_for_current_site();
        return;
    }

    tn_lov_migrate_network_sites(false);
    update_site_option('tn_lov_network_migration_version', TN_LOV_MIGRATION_VERSION);
}

function tn_lov_migrate_network_sites(bool $repair_empty): void {
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
                if ($repair_empty) {
                    tn_lov_repair_empty_legacy_migration_for_current_site();
                } else {
                    tn_lov_clone_legacy_values_for_current_site();
                }
            } finally {
                restore_current_blog();
            }
        }

        $offset += count($site_ids);
    } while (count($site_ids) === $batch_size);
}

function tn_lov_clone_legacy_values_for_current_site(): void {
    if (false !== get_option('tn_lov_index', false)) {
        if (TN_LOV_MIGRATION_VERSION !== get_option('tn_lov_migration_version', '')) {
            tn_lov_repair_empty_legacy_migration_for_current_site();
            return;
        }

        update_option('tn_lov_migration_version', TN_LOV_MIGRATION_VERSION, false);
        return;
    }

    $legacy_index = get_option('lov_index', array());
    update_option('tn_lov_index', is_array($legacy_index) ? $legacy_index : array(), false);
    update_option('tn_lov_migration_version', TN_LOV_MIGRATION_VERSION, false);
}

function tn_lov_maybe_repair_legacy_migration(): void {
    if (is_multisite() && is_network_admin()) {
        if (TN_LOV_MIGRATION_VERSION === get_site_option('tn_lov_network_migration_version', '')) {
            return;
        }

        tn_lov_migrate_network_sites(true);
        update_site_option('tn_lov_network_migration_version', TN_LOV_MIGRATION_VERSION);
        return;
    }

    if (TN_LOV_MIGRATION_VERSION === get_option('tn_lov_migration_version', '')) {
        return;
    }

    tn_lov_repair_empty_legacy_migration_for_current_site();
}

function tn_lov_repair_empty_legacy_migration_for_current_site(): bool {
    $legacy_index = get_option('lov_index', false);
    $native_index = get_option('tn_lov_index', false);
    $repaired = false;

    if (
        is_array($legacy_index)
        && tn_lov_count_index_values($legacy_index) > 0
        && (!is_array($native_index) || 0 === tn_lov_count_index_values($native_index))
    ) {
        update_option('tn_lov_index', $legacy_index, false);
        $repaired = true;
    }

    update_option('tn_lov_migration_version', TN_LOV_MIGRATION_VERSION, false);

    return $repaired;
}

/**
 * @param mixed $index LOV index.
 */
function tn_lov_count_index_values($index): int {
    if (!is_array($index)) {
        return 0;
    }

    $count = 0;
    foreach ($index as $values) {
        if (is_array($values)) {
            $count += count($values);
        }
    }

    return $count;
}
