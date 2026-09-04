# TN LOV

Author: Techn

Version: 2.0.6

Status: Production

## Purpose

TN LOV provides a native WordPress editor for reusable key/value settings, optional grouping, notes, and WPML-aware language storage. It replaces ACF Lov Table without requiring Advanced Custom Fields.

## Migration from ACF Lov Table

Activate TN LOV while ACF Lov Table is still active. On first activation, TN LOV clones the complete legacy `lov_index` option into its independent `tn_lov_index` option. The legacy plugin remains the owner of the old option, and TN LOV writes only to the new option.

While both plugins are active, ACF Lov Table continues to provide the legacy global functions. TN LOV waits until every plugin file has loaded and only registers functions that remain undefined, so coexistence is safe regardless of plugin load order. Confirm the copied values in Settings > TN LOV, then deactivate ACF Lov Table. On the following request, TN LOV provides the same public functions from its native data store.

The activation import runs when `tn_lov_index` does not yet exist. It can also repair an empty index left by a migration attempt from an earlier release. Non-empty values already managed by TN LOV are not overwritten automatically.

On multisite, network activation processes sites in batches and performs the same non-destructive clone within each site's own options table. Individual site activation clones only that site.

TN LOV 2.0.3 automatically repairs an empty native index left by an earlier migration attempt when legacy values are available. Settings > TN LOV shows legacy and native totals, the number of languages detected, and whether both indexes match exactly. Administrators can also perform a fresh nonce-protected import from the legacy index; this replaces TN LOV data but never changes the legacy source.

If the legacy plugin never created its normalized `lov_index`, TN LOV reads and normalizes the original ACF repeater directly. With WPML active, it switches through the available languages and restores the original language after collection.

## Public API

```php
$value = get_lov('copyright_message');
$values = get_lov_group('default_css');
```

Prefixed equivalents are also available:

```php
$value = tn_lov_get('copyright_message');
$values = tn_lov_get_group('default_css');
```

The Settings > TN LOV screen includes a developer reference at the bottom with the supported public lookup functions.

## Storage

Values are stored in the autoload-disabled `tn_lov_index` WordPress option. The top-level keys are WPML language codes, or `default` when WPML is not active.

## Releases

Run `scripts/build-plugin-zip.sh` from the repository root. The release asset must be named `tn-lov.zip` and the Git tag must match the plugin version with a leading `v`.
