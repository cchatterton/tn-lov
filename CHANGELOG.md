# Changelog

All notable changes to TN LOV are recorded here.

## 2.0.6 - 2026-09-04

- Moved WordPress and third-party admin notices above the branded TN LOV header.
- Added a persistent per-user dismissal control to the migration status panel.
- Makes the migration panel visible again automatically when its status changes.
- Removed the Techn wordmark from the header eyebrow.
- Hides WPML badges, metrics, and language wording when WPML is not active on the current site.

## 2.0.5 - 2026-09-04

- Restored the developer reference to the bottom of the TN LOV screen and removed the contextual Help tabs.
- Aligned the Add value and Re-import legacy values icons with their button labels.
- Updated the administration screen to the Techn navy-and-orange brand palette.
- Renamed the Settings menu item to "List of Value (LOV)".

## 2.0.4 - 2026-09-04

- Moved the public API reference from the page footer into a contextual WordPress Help tab.
- Added a live Help lookup for testing exact keys against TN LOV's native index.
- Protected lookup requests with administrator capability checks, a nonce, and sanitised input.
- Added direct ACF repeater migration when the legacy normalized index was never created.
- Re-runs the safe empty-index repair for sites that previously reported no legacy source.

## 2.0.3 - 2026-09-04

- Added a self-healing migration that repairs empty TN LOV indexes left by earlier activation attempts.
- Added a visible migration status panel comparing legacy and TN LOV totals and exact index contents.
- Added a nonce-protected manual legacy re-import for all languages.
- Redesigned the administration screen with a polished responsive interface and clearer editing controls.

## 2.0.2 - 2026-09-04

- Fixed a fatal function redeclaration when TN LOV loaded before ACF Lov Table.
- Deferred the backward-compatible global API until all plugin files have loaded, making coexistence independent of plugin load order.

## 2.0.1 - 2026-09-04

- Added multisite-aware network activation that clones legacy LOV data for every existing site.
- Kept migration non-destructive by preserving any TN LOV data already stored on each site.

## 2.0.0 - 2026-09-04

- Replaced the ACF options page and repeater with a native WordPress editor.
- Added one-time activation cloning from the legacy `lov_index` option into independent TN LOV storage.
- Preserved `get_lov()`, `get_lov_group()`, `get_lov_data()`, and `is_wpml_active()` without redeclaring functions supplied by the legacy plugin.
- Preserved WPML-aware values and default-language fallback.
- Added GitHub release updates through the native WordPress plugin update interface.
