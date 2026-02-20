# FS Shortcode Suite

Custom shortcode suite for BotasFutsal built on a scalable offer-driven
architecture.

## Overview

FS Shortcode Suite provides a high-performance product rendering system
based on a three-layer CPT model:

-   `fs_producto` (Product)
-   `fs_variante` (Variant)
-   `fs_oferta` (Offer)

The plugin integrates with a custom importer and exposes optimized
frontend shortcodes powered by a dedicated `ProductQuery` abstraction
layer.

## Architecture

The system follows an offer-driven model:

-   Stock and price are validated at Offer level.
-   Variant attributes (color, surface, age_group) are handled via
    taxonomies.
-   Brand filtering is handled via product meta (`fs_brand_raw`).
-   Queries are optimized to avoid unnecessary nested loops.

## Features

-   Custom grid and carousel shortcodes
-   Offer-aware filtering (price + stock)
-   Brand filtering via meta
-   Variant-level taxonomy filtering
-   Optimized image handling
-   Mobile-first frontend behavior
-   Designed to integrate with Astra child theme

## Example Usage

    [fs_grid genero="mujer" superficie="indoor" per_page="8"]

## Requirements

-   WordPress 6.0+
-   PHP 8.0+
-   Custom CPT structure already registered
-   ACF fields configured

## Folder Structure

    fs-shortcode-suite/
    ├── src/
    ├── views/
    ├── assets/
    ├── uninstall.php
    ├── readme.txt
    └── README.md

## License

Proprietary -- Internal project.
