=== FS Importer Core ===
Contributors: fs-importer-team
Tags: importer, async, domain, architecture
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

FS Importer Core is the domain layer of the FS Importer ecosystem.

== Description ==

This plugin contains the core business logic for the FS Importer system.

It is responsible for:
* Validating import requests
* Defining domain rules and invariants
* Creating and dispatching import commands
* Managing import state and persistence
* Acting as the single source of truth for the system

This plugin does NOT:
* Render UI
* Execute long-running tasks
* Call external APIs directly

== Architecture ==

FS Importer Core owns the domain.

All decisions, rules, and validations live here.
Other plugins must interact with the system exclusively through the Core.

== Dependencies ==

This plugin is designed to be used together with:
* FS Importer Sprinter
* BFS Shortcode Suite

It is not intended to be used standalone.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate FS Importer Core
3. Activate FS Importer Sprinter
4. Activate BFS Shortcode Suite

== Changelog ==

= 0.1.0 =
* Initial architecture release
