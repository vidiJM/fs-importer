=== BFS Shortcode Suite ===
Contributors: fs-importer-team
Tags: shortcode, frontend, importer, ui
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

BFS Shortcode Suite is the frontend/UI layer of the FS Importer ecosystem.

== Description ==

This plugin provides the presentation layer for the FS Importer system.

It is responsible for:
* Registering and rendering shortcodes
* Parsing and normalizing user input (attributes)
* Displaying import status and results
* Acting as the boundary between users and the core domain

This plugin does NOT:
* Access the database directly
* Contain business or domain logic
* Execute long-running or blocking operations
* Call external APIs

== Architecture ==

BFS Shortcode Suite is a thin frontend layer.

All rules, validation, and decisions are delegated to FS Importer Core.
This ensures high performance, security, and maintainability.

== Dependencies ==

This plugin must be used together with:
* FS Importer Core
* FS Importer Sprinter

It is not intended to be used standalone.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate FS Importer Core
3. Activate FS Importer Sprinter
4. Activate BFS Shortcode Suite

== Changelog ==

= 0.1.0 =
* Initial frontend/shortcode layer
