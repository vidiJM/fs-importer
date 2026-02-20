# BotasFutsal WP Custom

Custom WordPress architecture for BotasFutsal.

This repository contains all custom plugins and theme code used in
production, following a modular and scalable architecture.

------------------------------------------------------------------------

## 🚀 Overview

**botasfutsal-wp-custom** is a monorepo containing the full custom
WordPress layer for BotasFutsal.

It includes:

-   Custom importer architecture
-   Offer-driven product model
-   Query abstraction layer
-   Frontend shortcode suite
-   Astra child theme

This repository does NOT include WordPress core.

------------------------------------------------------------------------

## 🧩 Repository Structure

    plugins/
    ├── fs-shortcode-suite/
    ├── fs-importer-core/
    └── fs-importer-sprinter/

    themes/
    └── astra-child/

------------------------------------------------------------------------

## 🧠 Architectural Principles

-   Async-first importer design
-   Clear separation of concerns
-   Offer-driven data model (Product → Variant → Offer)
-   Domain logic isolated in core plugin
-   Frontend strictly rendering only
-   High-traffic safe

------------------------------------------------------------------------

## 🛠 Development Setup

### Requirements

-   PHP 8.0+
-   WordPress 6.0+
-   Git

### Usage

Clone repository:

git clone
https://github.com/`<your-org>`{=html}/botasfutsal-wp-custom.git

Copy plugins and theme into a WordPress installation:

wp-content/plugins/ wp-content/themes/

Activate plugins in order:

1.  fs-importer-core
2.  fs-importer-sprinter
3.  fs-shortcode-suite

------------------------------------------------------------------------

## 📜 License

GPL-2.0-or-later
