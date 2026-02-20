# Contributing to botasfutsal-wp-custom

Thanks for your interest in contributing 🎉

This repository contains the custom WordPress architecture for
BotasFutsal and follows strict structural and architectural rules.

------------------------------------------------------------------------

## 🧭 Philosophy

The project is built around:

-   Modular plugin architecture
-   Async-first importer
-   Domain isolation
-   Offer-driven model
-   Long-term maintainability

------------------------------------------------------------------------

## 🧩 Plugin Responsibilities

  Plugin                 Responsibility
  ---------------------- -------------------------------------
  fs-shortcode-suite     Frontend/UI (shortcodes, rendering)
  fs-importer-core       Domain logic, validation, commands
  fs-importer-sprinter   Async execution, cron, workers

Themes: - astra-child → Production frontend theme

Do not blur responsibilities between layers.

------------------------------------------------------------------------

## 🚫 Hard Rules

-   No business logic in frontend layer
-   No direct DB access outside repositories
-   No long-running operations in frontend requests
-   No external API calls outside workers
-   No vendor directories committed

------------------------------------------------------------------------

## 🛠 Setup

Clone:

git clone
https://github.com/`<your-org>`{=html}/botasfutsal-wp-custom.git

Copy into WordPress:

wp-content/plugins/ wp-content/themes/

Activate in correct order.

------------------------------------------------------------------------

## 📝 Commits

-   One logical change per commit
-   Clear commit messages
-   Respect architectural boundaries

------------------------------------------------------------------------

## 🏁 Final Note

This is not a generic WordPress project.

It is a structured backend architecture built on top of WordPress.
