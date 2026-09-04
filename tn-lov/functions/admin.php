<?php
/**
 * Native WordPress administration screen for LOV entries.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'tn_lov_register_admin_page');
add_action('admin_post_tn_lov_save_values', 'tn_lov_save_values');

function tn_lov_register_admin_page(): void {
    add_options_page(
        __('List of Values', 'tn-lov'),
        __('TN LOV', 'tn-lov'),
        'manage_options',
        'tn-lov',
        'tn_lov_render_admin_page'
    );
}

function tn_lov_render_admin_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    $language = tn_lov_current_language();
    $values = tn_lov_get_data(false);
    $saved_count = isset($_GET['tn_lov_saved']) ? absint($_GET['tn_lov_saved']) : null;
    ?>
    <div class="wrap tn-lov-admin">
        <h1><?php esc_html_e('List of Values', 'tn-lov'); ?></h1>

        <?php if (null !== $saved_count) : ?>
            <div class="notice notice-success is-dismissible"><p>
                <?php
                /* translators: %s: Number of saved LOV entries. */
                printf(
                    esc_html(_n('%s value saved.', '%s values saved.', $saved_count, 'tn-lov')),
                    esc_html(number_format_i18n($saved_count))
                );
                ?>
            </p></div>
        <?php endif; ?>

        <?php if (tn_lov_is_wpml_active()) : ?>
            <p class="description">
                <?php esc_html_e('Editing values for language:', 'tn-lov'); ?>
                <strong><?php echo esc_html(strtoupper($language)); ?></strong>.
                <?php esc_html_e('Empty lookups fall back to the WPML default language.', 'tn-lov'); ?>
            </p>
        <?php endif; ?>

        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="tn_lov_save_values">
            <input type="hidden" name="tn_lov_language" value="<?php echo esc_attr($language); ?>">
            <?php wp_nonce_field('tn_lov_save_values', 'tn_lov_nonce'); ?>

            <div class="tn-lov-table-wrap">
                <table class="widefat striped tn-lov-table">
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

            <p>
                <button type="button" class="button" id="tn-lov-add-row"><?php esc_html_e('Add row', 'tn-lov'); ?></button>
            </p>

            <?php submit_button(__('Save values', 'tn-lov')); ?>
        </form>

        <div class="postbox tn-lov-functions">
            <div class="postbox-header"><h2><?php esc_html_e('Related functions', 'tn-lov'); ?></h2></div>
            <div class="inside"><code>get_lov('$key')</code> &nbsp; <code>get_lov_group('$group')</code></div>
        </div>

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
            <button type="button" class="button-link-delete tn-lov-remove-row"><?php esc_html_e('Remove', 'tn-lov'); ?></button>
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
