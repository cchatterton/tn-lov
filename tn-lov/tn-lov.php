<?php
/**
 * Plugin Name: TN LOV
 * Description: Manage reusable, optionally grouped values with native WordPress tools and optional WPML language support.
 * Version: 2.0.7
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/tn-lov
 * Author: Techn
 * Author URI: https://techn.com.au
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tn-lov
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TN_LOV_VERSION', '2.0.7');
define('TN_LOV_MIGRATION_VERSION', '2');
define('TN_LOV_PLUGIN_FILE', __FILE__);
define('TN_LOV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TN_LOV_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once TN_LOV_PLUGIN_DIR . 'functions/setup.php';
require_once TN_LOV_PLUGIN_DIR . 'functions/data.php';
require_once TN_LOV_PLUGIN_DIR . 'functions/admin.php';
require_once TN_LOV_PLUGIN_DIR . 'functions/assets.php';
require_once TN_LOV_PLUGIN_DIR . 'functions/github-updater.php';

register_activation_hook(TN_LOV_PLUGIN_FILE, 'tn_lov_activate');
