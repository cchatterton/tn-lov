# TN LOV

TN LOV provides a native WordPress editor for reusable key/value settings with optional grouping, notes, and WPML language support.

## Requirements

- WordPress 6.0 or later
- PHP 8.1 or later
- WPML is optional
- Advanced Custom Fields is not required

## Migration

Activate TN LOV while ACF Lov Table is active. TN LOV clones the legacy data once into its own option and avoids redeclaring the legacy plugin's global functions. After confirming the values under Settings > TN LOV, deactivate ACF Lov Table.

See the repository README for public API and release details.

