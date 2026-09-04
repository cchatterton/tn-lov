=== TN LOV ===
Contributors: techn
Tags: settings, options, values, wpml
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 2.0.2
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage reusable key/value settings natively, with optional grouping, notes, and WPML-aware language fallback.

== Description ==

TN LOV provides a native WordPress editor for reusable values without requiring Advanced Custom Fields. Values may include a key, value, group, and administrative notes.

The existing get_lov() and get_lov_group() functions remain available after the legacy ACF Lov Table plugin is deactivated. When both plugins are active, TN LOV waits until all plugin files have loaded and does not redeclare global functions already provided by the legacy plugin.

On first activation, TN LOV clones the existing legacy lov_index option into its own independent storage. Review the copied values under Settings > TN LOV before deactivating the legacy plugin.

On multisite, network activation clones the legacy values for every existing site. Existing TN LOV values are preserved independently on each site.

When WPML is active, values are managed for the current language and lookups fall back to the WPML default language.

TN LOV checks a public GitHub repository for release metadata when WordPress performs plugin update checks. It sends the plugin version and standard HTTP request metadata to GitHub. GitHub terms and privacy information are available at https://docs.github.com/en/site-policy and https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement.

== Installation ==

1. Keep ACF Lov Table active during migration.
2. Upload the `tn-lov` folder to `/wp-content/plugins/`, or install `tn-lov.zip` through Plugins > Add New > Upload Plugin.
3. Activate TN LOV. Existing normalized LOV data is cloned once into TN LOV storage.
4. Open Settings > TN LOV and confirm the copied values.
5. Deactivate ACF Lov Table. Advanced Custom Fields may then be removed if nothing else requires it.

== Frequently Asked Questions ==

= Will activating TN LOV change or delete ACF Lov Table data? =

No. TN LOV copies the legacy index into a separate option and does not alter the original option.

= Can both plugins be active during migration? =

Yes. TN LOV checks whether each legacy global function already exists and does not redeclare it when ACF Lov Table is active.

= Does activation overwrite existing TN LOV values? =

No. The legacy import only runs when TN LOV's own data option does not exist.

== Changelog ==

= 2.0.2 =

* Fixed fatal function conflicts when TN LOV loads before ACF Lov Table.
* Made legacy-plugin coexistence independent of plugin load order.

= 2.0.1 =

* Added safe, batched migration of every existing site during multisite network activation.
* Preserved TN LOV data already stored on individual sites.

= 2.0.0 =

* Replaced the ACF-dependent editor with a native WordPress editor.
* Added safe one-time cloning of legacy values on activation.
* Preserved legacy lookup functions and WPML fallback behavior.
* Added WordPress-native updates from GitHub releases.

== Upgrade Notice ==

= 2.0.2 =

Fixes activation alongside ACF Lov Table when TN LOV is earlier in the plugin load order.

= 2.0.1 =

Network activation now clones each existing site's legacy LOV values into that site's TN LOV storage.

= 2.0.0 =

Activate TN LOV alongside ACF Lov Table, verify the copied values, and only then deactivate the legacy plugin.

== External services ==

This plugin connects to GitHub during WordPress plugin update checks to retrieve public release metadata and the release ZIP. The request includes standard HTTP metadata and the installed plugin version in the user-agent. This service is provided by GitHub and is subject to GitHub's terms and privacy statement: https://docs.github.com/en/site-policy and https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement.
