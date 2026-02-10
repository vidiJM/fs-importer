=== FS Importer Sprinter ===
Contributors: fs-importer-team
Tags: importer, async, cron, worker, execution
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

FS Importer Sprinter is the execution and worker layer of the FS Importer ecosystem.

== Description ==

This plugin is responsible for executing import jobs asynchronously.

It handles:
* Scheduling import jobs
* Executing workers via WP-Cron
* Managing locks and concurrency
* Handling retries and failures
* Calling external APIs
* Persisting execution results via the core

This plugin does NOT:
* Contain business or domain rules
* Render UI or shortcodes
* Decide what should be imported

== Architecture ==

FS Importer Sprinter executes commands created by FS Importer Core.

It receives normalized commands, applies locks, performs IO operations,
and reports results back to the core domain.

== Dependencies ==

This plugin must be used together with:
* FS Importer Core
* BFS Shortcode Suite

It is not intended to be used standalone.

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate FS Importer Core
3. Activate FS Importer Sprinter
4. Activate BFS Shortcode Suite

== Changelog ==

= 0.1.0 =
* Initial async execution and worker layer
