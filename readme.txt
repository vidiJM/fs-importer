=== BotasFutsal WP Custom === Contributors: botasfutsal Requires at
least: 6.0 Requires PHP: 8.0 License: GPL-2.0-or-later License URI:
https://www.gnu.org/licenses/gpl-2.0.html

Custom WordPress architecture for BotasFutsal.

== Description ==

BotasFutsal WP Custom is a modular WordPress codebase containing all
custom plugins and theme logic used in production.

This repository includes:

-   fs-importer-core (domain logic and validation layer)
-   fs-importer-sprinter (async execution, cron, workers)
-   fs-shortcode-suite (frontend rendering and UI shortcodes)
-   astra-child theme (production frontend theme)

The system is built using an offer-driven architecture: Product →
Variant → Offer.

WordPress core is NOT included in this repository.

== Installation ==

1.  Clone the repository.
2.  Copy the plugins into wp-content/plugins/.
3.  Copy the theme into wp-content/themes/.
4.  Activate plugins in the following order:
    1.  fs-importer-core
    2.  fs-importer-sprinter
    3.  fs-shortcode-suite

== Development ==

Requirements:

-   PHP 8.0+
-   WordPress 6.0+
-   Local development environment (LocalWP, Docker, etc.)

== Notes ==

This repository is intended for internal development and production
version control of custom WordPress code.
