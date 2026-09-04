<?php
/**
 * LOV storage and public lookup functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

function tn_lov_is_wpml_active(): bool {
    return defined('ICL_SITEPRESS_VERSION');
}

function tn_lov_current_language(): string {
    if (!tn_lov_is_wpml_active()) {
        return 'default';
    }

    $language = apply_filters('wpml_current_language', null);

    return is_string($language) && '' !== $language ? sanitize_key($language) : 'default';
}

function tn_lov_default_language(): string {
    if (!tn_lov_is_wpml_active()) {
        return 'default';
    }

    $language = apply_filters('wpml_default_language', null);

    return is_string($language) && '' !== $language ? sanitize_key($language) : 'default';
}

/**
 * Return the stored LOV index for the current or default language.
 *
 * @return array<string, array{value: string, group: string, notes: string}>
 */
function tn_lov_get_data(bool $use_default_language = false): array {
    $index = get_option('tn_lov_index', array());
    if (!is_array($index)) {
        return array();
    }

    $language = $use_default_language ? tn_lov_default_language() : tn_lov_current_language();
    $values = $index[$language] ?? array();

    return is_array($values) ? $values : array();
}

/**
 * Get a value by its exact key, falling back to the WPML default language.
 *
 * @return mixed|null
 */
function tn_lov_get(string $key) {
    $value = tn_lov_find_value($key, tn_lov_get_data(false));
    if (null !== $value) {
        return $value;
    }

    return tn_lov_find_value($key, tn_lov_get_data(true));
}

/**
 * Return all values in a group, falling back to the WPML default language.
 *
 * @return array<string, mixed>|null
 */
function tn_lov_get_group(string $group): ?array {
    $values = tn_lov_find_group($group, tn_lov_get_data(false));
    if (!empty($values)) {
        return $values;
    }

    $values = tn_lov_find_group($group, tn_lov_get_data(true));

    return !empty($values) ? $values : null;
}

/**
 * @param array<string, array<string, mixed>> $values
 * @return mixed|null
 */
function tn_lov_find_value(string $key, array $values) {
    return array_key_exists($key, $values) && is_array($values[$key])
        ? ($values[$key]['value'] ?? null)
        : null;
}

/**
 * @param array<string, array<string, mixed>> $values
 * @return array<string, mixed>
 */
function tn_lov_find_group(string $group, array $values): array {
    $matches = array();

    foreach ($values as $key => $value) {
        if (is_array($value) && isset($value['group']) && $group === $value['group']) {
            $matches[$key] = $value['value'] ?? null;
        }
    }

    return $matches;
}

add_action('plugins_loaded', 'tn_lov_register_legacy_acf_bridge', 5);
add_action('plugins_loaded', 'tn_lov_register_legacy_api');
add_action('admin_notices', 'tn_lov_render_legacy_acf_bridge_notice');

/**
 * Keep the legacy plugin operational long enough for it to be deactivated.
 *
 * ACF Lov Table owns get_lov() while both plugins are active. If ACF itself is
 * removed first, that function fatals when it calls get_field(). This narrowly
 * scoped bridge exposes only the old `lov` repeater from TN LOV's native data.
 */
function tn_lov_register_legacy_acf_bridge(): void {
    if (function_exists('get_field') || !tn_lov_legacy_plugin_owns_function('get_lov')) {
        return;
    }

    if (!defined('TN_LOV_LEGACY_ACF_BRIDGE_ACTIVE')) {
        define('TN_LOV_LEGACY_ACF_BRIDGE_ACTIVE', true);
    }

    function get_field($selector, $post_id = false, $format_value = true, $escape_html = false) {
        unset($post_id, $format_value, $escape_html);

        if ('lov' !== $selector) {
            return false;
        }

        $rows = array();
        foreach (tn_lov_get_data(false) as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            $rows[] = array(
                'key'   => (string) $key,
                'value' => $value['value'] ?? '',
                'group' => $value['group'] ?? '',
                'notes' => $value['notes'] ?? '',
            );
        }

        return $rows;
    }
}

function tn_lov_legacy_plugin_owns_function(string $function_name): bool {
    if (!function_exists($function_name)) {
        return false;
    }

    try {
        $reflection = new ReflectionFunction($function_name);
        $filename = $reflection->getFileName();
    } catch (ReflectionException $exception) {
        return false;
    }

    return is_string($filename)
        && false !== strpos(wp_normalize_path($filename), '/acf-lov-table/');
}

function tn_lov_legacy_acf_bridge_active(): bool {
    return defined('TN_LOV_LEGACY_ACF_BRIDGE_ACTIVE') && TN_LOV_LEGACY_ACF_BRIDGE_ACTIVE;
}

function tn_lov_render_legacy_acf_bridge_notice(): void {
    if (!tn_lov_legacy_acf_bridge_active() || !current_user_can('activate_plugins')) {
        return;
    }
    ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('TN LOV prevented a legacy plugin error.', 'tn-lov'); ?></strong>
            <?php esc_html_e('ACF Lov Table is still active but Advanced Custom Fields is unavailable. TN LOV is temporarily serving its LOV requests from native data. Deactivate ACF Lov Table to complete the migration.', 'tn-lov'); ?>
            <a href="<?php echo esc_url(admin_url('plugins.php')); ?>"><?php esc_html_e('Open Plugins', 'tn-lov'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Register the old public API after all plugin files have loaded.
 *
 * Waiting until plugins_loaded prevents a fatal redeclaration when the legacy
 * plugin loads after TN LOV and declares the same functions unconditionally.
 */
function tn_lov_register_legacy_api(): void {
    if (!function_exists('is_wpml_active')) {
        function is_wpml_active(): bool {
            return tn_lov_is_wpml_active();
        }
    }

    if (!function_exists('get_lov_data')) {
        function get_lov_data($use_default_language = false): array {
            return tn_lov_get_data((bool) $use_default_language);
        }
    }

    if (!function_exists('get_lov')) {
        function get_lov($key) {
            return tn_lov_get((string) $key);
        }
    }

    if (!function_exists('get_lov_group')) {
        function get_lov_group($group): ?array {
            return tn_lov_get_group((string) $group);
        }
    }
}
