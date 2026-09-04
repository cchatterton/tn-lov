<?php
/**
 * GitHub release updater for TN LOV.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class TN_LOV_GitHub_Updater {
    private const OWNER = 'cchatterton';
    private const REPO = 'tn-lov';
    private const SLUG = 'tn-lov';
    private const ASSET_NAME = 'tn-lov.zip';
    private const RELEASE_TRANSIENT = 'tn_lov_github_latest_release';
    private const ERROR_TRANSIENT = 'tn_lov_github_latest_release_error';
    private const BACKOFF_TRANSIENT = 'tn_lov_github_latest_release_backoff';
    private const CHECK_QUERY_KEY = 'tn_lov_check_updates';
    private const RESULT_QUERY_KEY = 'tn_lov_update_check';

    private static bool $forced_cache_cleared = false;

    public static function init(): void {
        add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'inject_update'));
        add_filter('site_transient_update_plugins', array(__CLASS__, 'inject_update'));
        add_filter('plugins_api', array(__CLASS__, 'plugin_information'), 10, 3);
        add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);
        add_action('admin_init', array(__CLASS__, 'handle_manual_check'));
        add_action('admin_notices', array(__CLASS__, 'show_manual_check_notice'));
        add_action('network_admin_notices', array(__CLASS__, 'show_manual_check_notice'));
        add_action('upgrader_process_complete', array(__CLASS__, 'clear_cache_after_update'), 10, 2);
    }

    public static function inject_update($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        $release = self::get_latest_release();
        if (empty($release)) {
            return $transient;
        }

        $version = self::release_version($release);
        $download_url = self::release_asset_url($release);
        $plugin_file = plugin_basename(TN_LOV_PLUGIN_FILE);

        $transient->response = isset($transient->response) && is_array($transient->response) ? $transient->response : array();
        $transient->no_update = isset($transient->no_update) && is_array($transient->no_update) ? $transient->no_update : array();

        if (empty($version) || empty($download_url) || !version_compare($version, TN_LOV_VERSION, '>')) {
            unset($transient->response[$plugin_file], $transient->no_update[$plugin_file]);
            return $transient;
        }

        unset($transient->no_update[$plugin_file]);
        $transient->response[$plugin_file] = (object) array(
            'id'           => self::repository_url(),
            'slug'         => self::SLUG,
            'plugin'       => $plugin_file,
            'new_version'  => $version,
            'url'          => self::repository_url(),
            'package'      => $download_url,
            'requires'     => '6.0',
            'requires_php' => '8.1',
        );

        return $transient;
    }

    public static function plugin_information($result, $action, $args) {
        if ('plugin_information' !== $action || empty($args->slug) || self::SLUG !== $args->slug) {
            return $result;
        }

        $release = self::get_latest_release();
        if (empty($release)) {
            return $result;
        }

        $version = self::release_version($release);
        $download_url = self::release_asset_url($release);
        if (empty($version) || empty($download_url)) {
            return $result;
        }

        return (object) array(
            'name'           => 'TN LOV',
            'slug'           => self::SLUG,
            'version'        => $version,
            'author'         => 'Techn',
            'author_profile' => 'https://techn.com.au',
            'homepage'       => self::repository_url(),
            'download_link'  => $download_url,
            'requires'       => '6.0',
            'requires_php'   => '8.1',
            'sections'       => array(
                'description' => 'Manage reusable, optionally grouped values with native WordPress tools and optional WPML language support.',
                'changelog'   => wp_kses_post((string) ($release['body'] ?? '')),
            ),
        );
    }

    public static function plugin_row_meta($links, $file) {
        if (plugin_basename(TN_LOV_PLUGIN_FILE) !== $file) {
            return $links;
        }

        $links[] = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url(self::repository_url()),
            esc_html__('GitHub', 'tn-lov')
        );

        if (current_user_can('update_plugins')) {
            $plugins_url = is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
            $check_url = wp_nonce_url(
                add_query_arg(self::CHECK_QUERY_KEY, '1', $plugins_url),
                self::CHECK_QUERY_KEY
            );
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url($check_url),
                esc_html__('Check for updates', 'tn-lov')
            );
        }

        return $links;
    }

    public static function handle_manual_check(): void {
        if (!isset($_GET[self::CHECK_QUERY_KEY])) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            wp_die(esc_html__('You are not allowed to check for plugin updates.', 'tn-lov'));
        }

        check_admin_referer(self::CHECK_QUERY_KEY);
        self::clear_release_cache();
        delete_site_transient('update_plugins');
        wp_update_plugins();

        $transient = get_site_transient('update_plugins');
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        $transient = self::inject_update($transient);
        set_site_transient('update_plugins', $transient);

        $plugin_file = plugin_basename(TN_LOV_PLUGIN_FILE);
        if (get_site_transient(self::ERROR_TRANSIENT)) {
            $result = 'failed';
        } elseif (isset($transient->response[$plugin_file])) {
            $result = 'update_available';
        } else {
            $result = 'current';
        }

        $plugins_url = is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
        wp_safe_redirect(add_query_arg(self::RESULT_QUERY_KEY, $result, $plugins_url));
        exit;
    }

    public static function show_manual_check_notice(): void {
        if (!current_user_can('update_plugins') || empty($_GET[self::RESULT_QUERY_KEY])) {
            return;
        }

        $result = sanitize_key(wp_unslash($_GET[self::RESULT_QUERY_KEY]));
        $notices = array(
            'update_available' => array('success', __('A TN LOV update is available. Use the native update action below to install it.', 'tn-lov')),
            'current'          => array('success', __('TN LOV is up to date.', 'tn-lov')),
            'failed'           => array('error', __('TN LOV could not check GitHub for updates. Please try again later.', 'tn-lov')),
        );

        if (!isset($notices[$result])) {
            return;
        }

        printf(
            '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
            esc_attr($notices[$result][0]),
            esc_html($notices[$result][1])
        );
    }

    public static function clear_cache_after_update($upgrader, $options): void {
        if ('update' !== ($options['action'] ?? '') || 'plugin' !== ($options['type'] ?? '')) {
            return;
        }

        $plugins = isset($options['plugins']) && is_array($options['plugins']) ? $options['plugins'] : array();
        if (in_array(plugin_basename(TN_LOV_PLUGIN_FILE), $plugins, true)) {
            self::clear_release_cache();
        }
    }

    private static function get_latest_release(): array {
        if (self::is_forced_update_check() && !self::$forced_cache_cleared) {
            self::clear_release_cache();
            self::$forced_cache_cleared = true;
        }

        $release = get_site_transient(self::RELEASE_TRANSIENT);
        if (is_array($release)) {
            return $release;
        }

        if (get_site_transient(self::BACKOFF_TRANSIENT)) {
            return array();
        }

        $diagnostic = array();
        $release = self::fetch_manifest_release($diagnostic);
        if (empty($release)) {
            $release = self::fetch_redirect_release($diagnostic);
        }
        if (empty($release)) {
            $release = self::fetch_api_release($diagnostic);
        }

        if (empty($release)) {
            $diagnostic['checked_at'] = time();
            set_site_transient(self::ERROR_TRANSIENT, $diagnostic, 10 * MINUTE_IN_SECONDS);
            set_site_transient(self::BACKOFF_TRANSIENT, 1, 10 * MINUTE_IN_SECONDS);
            return array();
        }

        $cache_duration = version_compare(self::release_version($release), TN_LOV_VERSION, '>')
            ? 6 * HOUR_IN_SECONDS
            : 5 * MINUTE_IN_SECONDS;

        set_site_transient(self::RELEASE_TRANSIENT, $release, $cache_duration);
        delete_site_transient(self::ERROR_TRANSIENT);
        delete_site_transient(self::BACKOFF_TRANSIENT);

        return $release;
    }

    private static function fetch_manifest_release(array &$diagnostic): array {
        $response = wp_remote_get(
            'https://raw.githubusercontent.com/' . self::OWNER . '/' . self::REPO . '/main/update.json',
            array(
                'timeout' => 10,
                'headers' => array('User-Agent' => 'TN-LOV/' . TN_LOV_VERSION),
            )
        );

        if (is_wp_error($response)) {
            $diagnostic = array('type' => 'manifest_wp_error', 'message' => $response->get_error_message());
            return array();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $manifest = json_decode(wp_remote_retrieve_body($response), true);
        $version = is_array($manifest) ? self::normalise_version($manifest['version'] ?? '') : '';
        if (200 !== $response_code || empty($version)) {
            $diagnostic = array('type' => 'manifest_error', 'code' => $response_code);
            return array();
        }

        return self::normalised_release($version, (string) ($manifest['body'] ?? ''));
    }

    private static function fetch_redirect_release(array &$diagnostic): array {
        $response = wp_remote_get(
            self::repository_url() . '/releases/latest',
            array(
                'timeout'     => 10,
                'redirection' => 0,
                'headers'     => array('User-Agent' => 'TN-LOV/' . TN_LOV_VERSION),
            )
        );

        if (is_wp_error($response)) {
            $diagnostic = array('type' => 'redirect_wp_error', 'message' => $response->get_error_message());
            return array();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $location = wp_remote_retrieve_header($response, 'location');
        if (!in_array($response_code, array(301, 302, 303, 307, 308), true) || !is_string($location)) {
            $diagnostic = array('type' => 'redirect_error', 'code' => $response_code);
            return array();
        }

        $path = wp_parse_url($location, PHP_URL_PATH);
        if (!is_string($path) || !preg_match('#/releases/tag/([^/]+)$#', $path, $matches)) {
            $diagnostic = array('type' => 'redirect_tag_error', 'code' => $response_code);
            return array();
        }

        $version = self::normalise_version(rawurldecode($matches[1]));
        if (empty($version)) {
            $diagnostic = array('type' => 'redirect_version_error', 'code' => $response_code);
            return array();
        }

        return self::normalised_release($version, 'Release ' . $version . '.');
    }

    private static function fetch_api_release(array &$diagnostic): array {
        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'TN-LOV/' . TN_LOV_VERSION,
                ),
            )
        );

        if (is_wp_error($response)) {
            $diagnostic = array('type' => 'api_wp_error', 'message' => $response->get_error_message());
            return array();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (200 !== $response_code || !is_array($release)) {
            $diagnostic = array('type' => 'api_error', 'code' => $response_code);
            return array();
        }

        $version = self::release_version($release);
        if (empty($version) || empty(self::release_asset_url($release))) {
            $diagnostic = array('type' => 'api_release_error', 'code' => $response_code);
            return array();
        }

        return $release;
    }

    private static function normalised_release(string $version, string $body): array {
        $tag = 'v' . $version;
        $download_url = self::repository_url() . '/releases/download/' . rawurlencode($tag) . '/' . self::ASSET_NAME;

        return array(
            'tag_name' => $tag,
            'body'     => $body,
            'html_url' => self::repository_url() . '/releases/tag/' . rawurlencode($tag),
            'assets'   => array(
                array(
                    'name'                 => self::ASSET_NAME,
                    'browser_download_url' => $download_url,
                ),
            ),
        );
    }

    private static function is_forced_update_check(): bool {
        if (!current_user_can('update_plugins')) {
            return false;
        }

        if (isset($_REQUEST['force-check']) || isset($_REQUEST[self::CHECK_QUERY_KEY])) {
            return true;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key(wp_unslash($_REQUEST['action'])) : '';
        return in_array($action, array('update-selected', 'upgrade-plugin', 'do-plugin-upgrade'), true);
    }

    private static function normalise_version($version): string {
        $version = ltrim(trim((string) $version), 'vV');
        return preg_match('/^\d+(?:\.\d+){1,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version) ? $version : '';
    }

    private static function release_version($release): string {
        return self::normalise_version($release['tag_name'] ?? '');
    }

    private static function release_asset_url($release): string {
        if (empty($release['assets']) || !is_array($release['assets'])) {
            return '';
        }

        foreach ($release['assets'] as $asset) {
            if (self::ASSET_NAME === ($asset['name'] ?? '') && !empty($asset['browser_download_url'])) {
                return esc_url_raw((string) $asset['browser_download_url']);
            }
        }

        return '';
    }

    private static function clear_release_cache(): void {
        delete_site_transient(self::RELEASE_TRANSIENT);
        delete_site_transient(self::ERROR_TRANSIENT);
        delete_site_transient(self::BACKOFF_TRANSIENT);
    }

    private static function repository_url(): string {
        return 'https://github.com/' . self::OWNER . '/' . self::REPO;
    }
}

TN_LOV_GitHub_Updater::init();

