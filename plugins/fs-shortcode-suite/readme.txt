=== FS Shortcode Suite ===
Contributors: botasfutsal
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Version: 1.0.0
License: Proprietary

Custom shortcode suite for BotasFutsal built on an offer-driven architecture.

== Description ==

FS Shortcode Suite provides optimized shortcodes for rendering products using a scalable CPT architecture:

- fs_producto (Product)
- fs_variante (Variant)
- fs_oferta (Offer)

Filtering supports:

- Brand (meta-based)
- Color (taxonomy)
- Surface (taxonomy)
- Gender / Age group
- Price (offer-level)

The plugin integrates with a custom importer and Astra child theme.

== Installation ==

1. Upload the `fs-shortcode-suite` folder to `/wp-content/plugins/`
2. Activate the plugin via the WordPress admin panel
3. Ensure CPTs and ACF fields are properly configured

== Shortcodes ==

[fs_grid]
Displays a responsive product grid with filtering support.

Example:

[fs_grid genero="hombre" superficie="indoor"]

== Changelog ==

= 1.0.0 =
* Initial production release

== Notes ==

Designed specifically for BotasFutsal architecture.
Not intended as a generic WooCommerce replacement.