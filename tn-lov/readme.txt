=== TN LOV ===
Contributors: techn
Tags: settings, options, values, wpml
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 2.0.8
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage reusable key/value settings natively, with optional grouping, notes, and WPML-aware language fallback.

== Description ==

TN LOV provides a native WordPress editor for reusable values without requiring Advanced Custom Fields. Values may include a key, value, group, and administrative notes.

The existing get_lov() and get_lov_group() functions remain available after the legacy ACF Lov Table plugin is deactivated. When both plugins are active, TN LOV waits until all plugin files have loaded and does not redeclare global functions already provided by the legacy plugin.

On first activation, TN LOV clones the existing legacy lov_index option into its own independent storage. Review the copied values under Settings > TN LOV before deactivating the legacy plugin.

On multisite, network activation clones the legacy values for every existing site. Existing TN LOV values are preserved independently on each site.

The TN LOV screen visibly compares legacy and native record totals, language totals, and exact index contents. A protected manual re-import is available while legacy data remains present.

When the old normalized index is absent, TN LOV imports directly from the ACF repeater and collects available WPML languages before restoring the original language.

If Advanced Custom Fields is removed before ACF Lov Table, TN LOV temporarily supplies the old plugin's LOV repeater calls from native data. Administrators are prompted to deactivate ACF Lov Table and complete the migration.

The TN LOV screen includes a developer reference at the bottom with the supported public lookup functions.

The developer reference also includes a protected key tester that displays the matching native value in a browser alert.

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

= 2.0.8 =

* Reworked Developer Reference into a compact single row with the LOV tester centred.

= 2.0.7 =

* Prevented legacy get_lov() calls from failing if ACF is removed before ACF Lov Table.
* Added a narrowly scoped bridge from the old LOV repeater call to native TN LOV data.
* Added an administrator warning to deactivate the obsolete plugin.
* Standardised page, hero, and menu naming to "List of Values".
* Added a protected native LOV key tester to the developer reference.
* Hides developer-reference WPML wording when WPML is inactive.
* Updated the primary Save values action to navy with white text.

= 2.0.6 =

* Moved WordPress and third-party notices above the branded header.
* Added persistent per-user dismissal of the migration status panel.
* Restores the panel automatically when its migration status changes.
* Hides language-specific interface elements unless WPML is active on the current site.
* Simplified the branded header eyebrow.
* Renamed the page and hero title to "List of Values".

= 2.0.5 =

* Restored the developer reference to the bottom of the TN LOV screen and removed the Help tabs.
* Aligned action icons with their button labels.
* Updated the administration screen to the Techn navy-and-orange brand palette.
* Renamed the Settings menu item to "List of Values (LOV)".

= 2.0.4 =

* Moved developer documentation into contextual WordPress Help tabs.
* Added a protected live lookup for testing exact TN LOV keys.
* Added direct ACF repeater migration when the old normalized index is unavailable.

= 2.0.3 =

* Repaired empty native indexes left by earlier migration attempts.
* Added visible migration verification and manual legacy re-import.
* Added a polished, responsive TN LOV administration interface.

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

= 2.0.8 =

Keeps Developer Reference narrow by placing the LOV tester in the middle of one balanced row.

= 2.0.7 =

Prevents a fatal error when Advanced Custom Fields is removed before the legacy ACF Lov Table plugin.

= 2.0.6 =

Keeps admin notices outside the TN LOV header and lets each administrator dismiss the migration panel.

= 2.0.5 =

Restores the on-page developer reference and applies Techn navy-and-orange branding.

= 2.0.4 =

Developer documentation and live key testing are now available from the Help drawer.

= 2.0.3 =

Repairs missing legacy data and adds visible migration verification under Settings > TN LOV.

= 2.0.2 =

Fixes activation alongside ACF Lov Table when TN LOV is earlier in the plugin load order.

= 2.0.1 =

Network activation now clones each existing site's legacy LOV values into that site's TN LOV storage.

= 2.0.0 =

Activate TN LOV alongside ACF Lov Table, verify the copied values, and only then deactivate the legacy plugin.

== External services ==

This plugin connects to GitHub during WordPress plugin update checks to retrieve public release metadata and the release ZIP. The request includes standard HTTP metadata and the installed plugin version in the user-agent. This service is provided by GitHub and is subject to GitHub's terms and privacy statement: https://docs.github.com/en/site-policy and https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement.
