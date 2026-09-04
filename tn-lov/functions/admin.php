<?php
/**
 * Native WordPress administration screen for LOV entries.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'tn_lov_register_admin_page');
add_action('admin_post_tn_lov_save_values', 'tn_lov_save_values');
add_action('admin_post_tn_lov_import_legacy', 'tn_lov_import_legacy_values');
add_action('wp_ajax_tn_lov_get_value', 'tn_lov_ajax_get_value');

function tn_lov_register_admin_page(): void {
    $hook_suffix = add_options_page(
        __('List of Values', 'tn-lov'),
        __('TN LOV', 'tn-lov'),
        'manage_options',
        'tn-lov',
        'tn_lov_render_admin_page'
    );

    add_action('load-' . $hook_suffix, 'tn_lov_register_help_tabs');
}

function tn_lov_register_help_tabs(): void {
    $screen = get_current_screen();
    if (!$screen || 'settings_page_tn-lov' !== $screen->id) {
        return;
    }

    $screen->add_help_tab(
        array(
            'id'      => 'tn_lov_functions',
            'title'   => __('LOV Functions', 'tn-lov'),
            'content' => '<p>' . esc_html__('Retrieve individual values or complete groups in theme and plugin code.', 'tn-lov') . '</p>'
                . '<ul>'
                . '<li><code>$value = get_lov(\'$key\');</code></li>'
                . '<li><code>$values = get_lov_group(\'$group\');</code></li>'
                . '<li><code>$value = tn_lov_get(\'$key\');</code></li>'
                . '<li><code>$values = tn_lov_get_group(\'$group\');</code></li>'
                . '</ul>'
                . '<p>' . esc_html__('Lookups use the current WPML language and fall back to the WPML default language.', 'tn-lov') . '</p>',
        )
    );

    $screen->add_help_tab(
        array(
            'id'      => 'tn_lov_lookup',
            'title'   => __('LOV Lookup', 'tn-lov'),
            'content' => '<div class="tn-lov-help-lookup">'
                . '<p><strong>' . esc_html__('Look up a TN LOV value', 'tn-lov') . '</strong></p>'
                . '<p>' . esc_html__('Enter an exact key to test the current TN LOV index.', 'tn-lov') . '</p>'
                . '<div class="tn-lov-help-lookup__controls">'
                . '<label class="screen-reader-text" for="tn-lov-help-input">' . esc_html__('LOV key', 'tn-lov') . '</label>'
                . '<input type="text" id="tn-lov-help-input" class="regular-text" placeholder="' . esc_attr__('e.g. copyright_message', 'tn-lov') . '">'
                . '<button type="button" class="button button-primary" id="tn-lov-help-get">' . esc_html__('Get value', 'tn-lov') . '</button>'
                . '</div>'
                . '<div class="tn-lov-help-result" id="tn-lov-help-result" role="status" aria-live="polite"></div>'
                . '<p class="description">' . esc_html__('This checks TN LOV directly, even while the legacy plugin still provides get_lov().', 'tn-lov') . '</p>'
                . '</div>',
        )
    );
}

function tn_lov_render_admin_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $language = tn_lov_current_language();
    $values = tn_lov_get_data(false);
    $legacy_source = tn_lov_get_legacy_source_for_current_site();
    $legacy_index = $legacy_source['index'];
    $native_index = get_option('tn_lov_index', array());
    $legacy_count = tn_lov_count_index_values($legacy_index);
    $native_count = tn_lov_count_index_values($native_index);
    $legacy_languages = is_array($legacy_index) ? count(array_filter($legacy_index, 'is_array')) : 0;
    $indexes_match = is_array($legacy_index) && tn_lov_indexes_match($legacy_index, $native_index);
    $saved_count = isset($_GET['tn_lov_saved']) ? absint($_GET['tn_lov_saved']) : null;
    $imported_count = isset($_GET['tn_lov_imported']) ? absint($_GET['tn_lov_imported']) : null;
    $import_failed = isset($_GET['tn_lov_import_failed']);

    if (!is_array($legacy_index)) {
        $migration_status = 'unavailable';
        $migration_label = __('No legacy source detected', 'tn-lov');
        $migration_message = __('TN LOV is ready to manage values independently.', 'tn-lov');
    } elseif ($indexes_match) {
        $migration_status = 'matched';
        $migration_label = __('Migration verified', 'tn-lov');
        $migration_message = __('The legacy and TN LOV indexes are an exact match.', 'tn-lov');
    } elseif (0 === $native_count && $legacy_count > 0) {
        $migration_status = 'pending';
        $migration_label = __('Import required', 'tn-lov');
        $migration_message = __('Legacy values were found, but TN LOV is currently empty.', 'tn-lov');
    } else {
        $migration_status = 'different';
        $migration_label = __('Indexes differ', 'tn-lov');
        $migration_message = __('Both indexes contain data, but their contents are not identical.', 'tn-lov');
    }
    ?>
    <div class="wrap tn-lov-admin">
        <header class="tn-lov-hero">
            <div>
                <p class="tn-lov-eyebrow"><?php esc_html_e('Techn · Native WordPress settings', 'tn-lov'); ?></p>
                <h1><?php esc_html_e('TN LOV', 'tn-lov'); ?></h1>
                <p class="tn-lov-lead"><?php esc_html_e('A dependable home for reusable site values—native, lightweight, and language-aware.', 'tn-lov'); ?></p>
            </div>
            <div class="tn-lov-badges" aria-label="<?php esc_attr_e('Plugin capabilities', 'tn-lov'); ?>">
                <span><?php esc_html_e('ACF-free', 'tn-lov'); ?></span>
                <span><?php esc_html_e('WPML-ready', 'tn-lov'); ?></span>
                <span><?php esc_html_e('Multisite-ready', 'tn-lov'); ?></span>
            </div>
        </header>

        <?php if (null !== $saved_count) : ?>
            <div class="notice notice-success is-dismissible tn-lov-notice"><p>
                <?php
                /* translators: %s: Number of saved LOV entries. */
                printf(
                    esc_html(_n('%s value saved.', '%s values saved.', $saved_count, 'tn-lov')),
                    esc_html(number_format_i18n($saved_count))
                );
                ?>
            </p></div>
        <?php endif; ?>

        <?php if (null !== $imported_count) : ?>
            <div class="notice notice-success is-dismissible tn-lov-notice"><p>
                <?php
                /* translators: %s: Number of imported LOV entries. */
                printf(
                    esc_html(_n('%s legacy value imported.', '%s legacy values imported.', $imported_count, 'tn-lov')),
                    esc_html(number_format_i18n($imported_count))
                );
                ?>
            </p></div>
        <?php endif; ?>

        <?php if ($import_failed) : ?>
            <div class="notice notice-error is-dismissible tn-lov-notice"><p><?php esc_html_e('No legacy LOV index was available to import.', 'tn-lov'); ?></p></div>
        <?php endif; ?>

        <section class="tn-lov-migration tn-lov-migration--<?php echo esc_attr($migration_status); ?>" aria-labelledby="tn-lov-migration-title">
            <div class="tn-lov-migration__summary">
                <span class="dashicons <?php echo 'matched' === $migration_status ? 'dashicons-yes-alt' : 'dashicons-database'; ?>" aria-hidden="true"></span>
                <div>
                    <p class="tn-lov-kicker">
                        <?php esc_html_e('Legacy migration', 'tn-lov'); ?>
                        <?php if ('acf' === $legacy_source['type']) : ?>
                            · <?php esc_html_e('Direct from ACF', 'tn-lov'); ?>
                        <?php elseif ('index' === $legacy_source['type']) : ?>
                            · <?php esc_html_e('Normalized index', 'tn-lov'); ?>
                        <?php endif; ?>
                    </p>
                    <h2 id="tn-lov-migration-title"><?php echo esc_html($migration_label); ?></h2>
                    <p><?php echo esc_html($migration_message); ?></p>
                </div>
            </div>

            <div class="tn-lov-migration__metrics">
                <div><strong><?php echo esc_html(number_format_i18n($legacy_count)); ?></strong><span><?php esc_html_e('Legacy values', 'tn-lov'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($native_count)); ?></strong><span><?php esc_html_e('TN LOV values', 'tn-lov'); ?></span></div>
                <div><strong><?php echo esc_html(number_format_i18n($legacy_languages)); ?></strong><span><?php esc_html_e('Languages found', 'tn-lov'); ?></span></div>
            </div>

            <?php if (is_array($legacy_index) && $legacy_count > 0) : ?>
                <form class="tn-lov-import" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-confirm-import="<?php esc_attr_e('Replace all TN LOV values with a fresh copy of the legacy index?', 'tn-lov'); ?>">
                    <input type="hidden" name="action" value="tn_lov_import_legacy">
                    <?php wp_nonce_field('tn_lov_import_legacy', 'tn_lov_import_nonce'); ?>
                    <button type="submit" class="button button-secondary">
                        <span class="dashicons dashicons-migrate" aria-hidden="true"></span>
                        <?php echo $indexes_match ? esc_html__('Re-import legacy values', 'tn-lov') : esc_html__('Import legacy values now', 'tn-lov'); ?>
                    </button>
                    <p><?php esc_html_e('This copies every language and replaces the current TN LOV index. The legacy index is never changed.', 'tn-lov'); ?></p>
                </form>
            <?php endif; ?>
        </section>

        <?php if (tn_lov_is_wpml_active()) : ?>
            <div class="tn-lov-language">
                <span class="dashicons dashicons-translation" aria-hidden="true"></span>
                <span><?php esc_html_e('Editing language', 'tn-lov'); ?></span>
                <strong><?php echo esc_html(strtoupper($language)); ?></strong>
                <span><?php esc_html_e('with default-language fallback', 'tn-lov'); ?></span>
            </div>
        <?php endif; ?>

        <form class="tn-lov-editor" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="tn_lov_save_values">
            <input type="hidden" name="tn_lov_language" value="<?php echo esc_attr($language); ?>">
            <?php wp_nonce_field('tn_lov_save_values', 'tn_lov_nonce'); ?>

            <div class="tn-lov-editor__header">
                <div>
                    <p class="tn-lov-kicker"><?php esc_html_e('Value library', 'tn-lov'); ?></p>
                    <h2><?php esc_html_e('Reusable values', 'tn-lov'); ?></h2>
                    <p><?php esc_html_e('Use clear, stable keys. Groups make related values easy to retrieve together.', 'tn-lov'); ?></p>
                </div>
                <span class="tn-lov-count">
                    <?php
                    printf(
                        /* translators: %s: Number of values in the current language. */
                        esc_html__('%s in this language', 'tn-lov'),
                        esc_html(number_format_i18n(count($values)))
                    );
                    ?>
                </span>
            </div>

            <div class="tn-lov-table-wrap">
                <table class="tn-lov-table">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Key', 'tn-lov'); ?> <span aria-hidden="true">*</span></th>
                            <th scope="col"><?php esc_html_e('Value', 'tn-lov'); ?> <span aria-hidden="true">*</span></th>
                            <th scope="col"><?php esc_html_e('Group', 'tn-lov'); ?></th>
                            <th scope="col"><?php esc_html_e('Notes', 'tn-lov'); ?></th>
                            <th scope="col" class="tn-lov-actions"><span class="screen-reader-text"><?php esc_html_e('Actions', 'tn-lov'); ?></span></th>
                        </tr>
                    </thead>
                    <tbody id="tn-lov-rows">
                        <?php
                        $row_index = 0;
                        foreach ($values as $key => $value) {
                            tn_lov_render_row($row_index, (string) $key, is_array($value) ? $value : array());
                            ++$row_index;
                        }

                        if (0 === $row_index) {
                            tn_lov_render_row(0, '', array());
                            $row_index = 1;
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="tn-lov-editor__footer">
                <button type="button" class="button button-secondary" id="tn-lov-add-row">
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <?php esc_html_e('Add value', 'tn-lov'); ?>
                </button>
                <?php submit_button(__('Save values', 'tn-lov'), 'primary', 'submit', false); ?>
            </div>
        </form>

        <script type="text/template" id="tn-lov-row-template">
            <?php tn_lov_render_row('__INDEX__', '', array()); ?>
        </script>
        <input type="hidden" id="tn-lov-next-index" value="<?php echo esc_attr((string) $row_index); ?>">
    </div>
    <?php
}

/**
 * @param int|string $index Row index or JavaScript template placeholder.
 * @param array<string, mixed> $value Stored row data.
 */
function tn_lov_render_row($index, string $key, array $value): void {
    $name_prefix = 'tn_lov_rows[' . $index . ']';
    ?>
    <tr>
        <td data-label="<?php esc_attr_e('Key', 'tn-lov'); ?>">
            <input class="regular-text" type="text" name="<?php echo esc_attr($name_prefix . '[key]'); ?>" value="<?php echo esc_attr($key); ?>" required>
        </td>
        <td data-label="<?php esc_attr_e('Value', 'tn-lov'); ?>">
            <input class="regular-text" type="text" name="<?php echo esc_attr($name_prefix . '[value]'); ?>" value="<?php echo esc_attr((string) ($value['value'] ?? '')); ?>" required>
        </td>
        <td data-label="<?php esc_attr_e('Group', 'tn-lov'); ?>">
            <input class="regular-text" type="text" name="<?php echo esc_attr($name_prefix . '[group]'); ?>" value="<?php echo esc_attr((string) ($value['group'] ?? '')); ?>">
        </td>
        <td data-label="<?php esc_attr_e('Notes', 'tn-lov'); ?>">
            <input class="regular-text" type="text" name="<?php echo esc_attr($name_prefix . '[notes]'); ?>" value="<?php echo esc_attr((string) ($value['notes'] ?? '')); ?>">
        </td>
        <td class="tn-lov-actions">
            <button type="button" class="tn-lov-remove-row" aria-label="<?php esc_attr_e('Remove value', 'tn-lov'); ?>">
                <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                <span class="screen-reader-text"><?php esc_html_e('Remove', 'tn-lov'); ?></span>
            </button>
        </td>
    </tr>
    <?php
}

function tn_lov_save_values(): void {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to manage these values.', 'tn-lov'));
    }

    check_admin_referer('tn_lov_save_values', 'tn_lov_nonce');

    $requested_language = isset($_POST['tn_lov_language'])
        ? sanitize_key(wp_unslash($_POST['tn_lov_language']))
        : '';
    $language = '' !== $requested_language ? $requested_language : tn_lov_current_language();

    $submitted_rows = isset($_POST['tn_lov_rows']) && is_array($_POST['tn_lov_rows'])
        ? wp_unslash($_POST['tn_lov_rows'])
        : array();
    $values = array();

    foreach ($submitted_rows as $row) {
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

    $index = get_option('tn_lov_index', array());
    $index = is_array($index) ? $index : array();
    $index[$language] = $values;
    update_option('tn_lov_index', $index, false);
    update_option('tn_lov_migration_version', TN_LOV_MIGRATION_VERSION, false);

    $redirect_args = array(
        'page'         => 'tn-lov',
        'tn_lov_saved' => count($values),
    );
    if ('default' !== $language) {
        $redirect_args['lang'] = $language;
    }

    $redirect_url = add_query_arg($redirect_args, admin_url('options-general.php'));

    wp_safe_redirect($redirect_url);
    exit;
}

function tn_lov_import_legacy_values(): void {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to import these values.', 'tn-lov'));
    }

    check_admin_referer('tn_lov_import_legacy', 'tn_lov_import_nonce');

    $legacy_source = tn_lov_get_legacy_source_for_current_site();
    $legacy_index = $legacy_source['index'];
    $redirect_args = array('page' => 'tn-lov');

    if (!is_array($legacy_index)) {
        $redirect_args['tn_lov_import_failed'] = '1';
    } else {
        update_option('tn_lov_index', $legacy_index, false);
        update_option('tn_lov_migration_version', TN_LOV_MIGRATION_VERSION, false);
        $redirect_args['tn_lov_imported'] = tn_lov_count_index_values($legacy_index);
    }

    wp_safe_redirect(add_query_arg($redirect_args, admin_url('options-general.php')));
    exit;
}

function tn_lov_ajax_get_value(): void {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('You are not allowed to look up these values.', 'tn-lov')), 403);
    }

    check_ajax_referer('tn_lov_lookup', 'nonce');

    $key = isset($_POST['key']) ? sanitize_text_field(wp_unslash($_POST['key'])) : '';
    if ('' === $key) {
        wp_send_json_error(array('message' => __('Enter a key to look up.', 'tn-lov')), 400);
    }

    $value = tn_lov_get($key);
    if (null === $value) {
        wp_send_json_error(array('message' => __('No matching TN LOV value was found.', 'tn-lov')), 404);
    }

    wp_send_json_success(array('value' => $value));
}

/**
 * @param mixed $first_index First LOV index.
 * @param mixed $second_index Second LOV index.
 */
function tn_lov_indexes_match($first_index, $second_index): bool {
    if (!is_array($first_index) || !is_array($second_index)) {
        return false;
    }

    return tn_lov_sort_index($first_index) === tn_lov_sort_index($second_index);
}

/**
 * @param array<mixed> $index LOV index.
 * @return array<mixed>
 */
function tn_lov_sort_index(array $index): array {
    ksort($index);

    foreach ($index as &$value) {
        if (is_array($value)) {
            $value = tn_lov_sort_index($value);
        }
    }
    unset($value);

    return $index;
}
