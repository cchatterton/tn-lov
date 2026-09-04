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

    $legacy_source = tn_lov_get_legacy_source_for_current_site();
    $legacy_index = $legacy_source['index'];
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
    $legacy_source = tn_lov_get_legacy_source_for_current_site();
    $legacy_index = $legacy_source['index'];
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
 * Return normalized legacy values from the old index or directly from ACF.
 *
 * @return array{index: array<mixed>|false, type: string}
 */
function tn_lov_get_legacy_source_for_current_site(): array {
    $legacy_index = get_option('lov_index', false);
    if (is_array($legacy_index) && tn_lov_count_index_values($legacy_index) > 0) {
        return array(
            'index' => $legacy_index,
            'type'  => 'index',
        );
    }

    $acf_index = tn_lov_read_legacy_acf_index();
    if (tn_lov_count_index_values($acf_index) > 0) {
        return array(
            'index' => $acf_index,
            'type'  => 'acf',
        );
    }

    return array(
        'index' => is_array($legacy_index) ? $legacy_index : false,
        'type'  => 'none',
    );
}

/**
 * Read the legacy ACF repeater directly when its normalized index is missing.
 *
 * @return array<string, array<string, array<string, string>>>
 */
function tn_lov_read_legacy_acf_index(): array {
    if (!function_exists('get_field') || tn_lov_legacy_acf_bridge_active()) {
        return array();
    }

    $languages = array('default');
    $original_language = 'default';

    if (tn_lov_is_wpml_active()) {
        $original_language = tn_lov_current_language();
        $languages = array($original_language, tn_lov_default_language());
        $active_languages = apply_filters(
            'wpml_active_languages',
            null,
            array('skip_missing' => 0)
        );

        if (is_array($active_languages)) {
            $languages = array_merge($languages, array_keys($active_languages));
        }

        $languages = array_values(array_unique(array_filter(array_map('sanitize_key', $languages))));
    }

    $index = array();

    try {
        foreach ($languages as $language) {
            if (tn_lov_is_wpml_active()) {
                do_action('wpml_switch_language', $language);
            }

            $rows = get_field('lov', 'options');
            $values = tn_lov_normalize_legacy_acf_rows($rows);
            if (!empty($values)) {
                $index[$language] = $values;
            }
        }
    } finally {
        if (tn_lov_is_wpml_active()) {
            do_action('wpml_switch_language', $original_language);
        }
    }

    return $index;
}

/**
 * @param mixed $rows ACF repeater rows.
 * @return array<string, array<string, string>>
 */
function tn_lov_normalize_legacy_acf_rows($rows): array {
    if (!is_array($rows)) {
        return array();
    }

    $values = array();

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $key = isset($row['key']) ? sanitize_text_field((string) $row['key']) : '';
        if ('' === $key) {
            continue;
        }

        $values[$key] = array(
            'value' => isset($row['value']) ? sanitize_text_field((string) $row['value']) : '',
            'group' => isset($row['group']) ? sanitize_text_field((string) $row['group']) : '',
            'notes' => isset($row['notes']) ? sanitize_text_field((string) $row['notes']) : '',
        );
    }

    return $values;
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
