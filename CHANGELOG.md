# Changelog

All notable changes to TN LOV are recorded here.

## 2.0.1 - 2026-09-04

- Added multisite-aware network activation that clones legacy LOV data for every existing site.
- Kept migration non-destructive by preserving any TN LOV data already stored on each site.

## 2.0.0 - 2026-09-04

- Replaced the ACF options page and repeater with a native WordPress editor.
- Added one-time activation cloning from the legacy `lov_index` option into independent TN LOV storage.
- Preserved `get_lov()`, `get_lov_group()`, `get_lov_data()`, and `is_wpml_active()` without redeclaring functions supplied by the legacy plugin.
- Preserved WPML-aware values and default-language fallback.
- Added GitHub release updates through the native WordPress plugin update interface.
