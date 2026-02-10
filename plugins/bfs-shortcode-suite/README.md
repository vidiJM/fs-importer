# BFS Shortcode Suite

Frontend / UI layer for the FS Importer system.

---

## Overview

**BFS Shortcode Suite** is the frontend-facing plugin of the FS Importer ecosystem.

It provides shortcodes and rendering logic that allow users to interact with the importer without exposing internal complexity or heavy operations to frontend requests.

---

## Responsibilities

This plugin is responsible for:

- Registering WordPress shortcodes
- Parsing and normalizing shortcode attributes
- Building request DTOs
- Rendering import status and results

It explicitly does **not**:

- Access the database directly
- Contain domain or business logic
- Execute asynchronous jobs
- Call external APIs

---

## Architectural Role

BFS Shortcode Suite acts as a strict boundary between:

- Users / frontend requests
- The FS Importer Core domain

All decisions and validations are delegated to the core.
This keeps frontend requests fast, safe, and predictable.

---

## Dependencies

This plugin is designed to be used together with:

- FS Importer Core (domain layer)
- FS Importer Sprinter (execution layer)

It is not intended to be used standalone.

---

## Activation Order

1. FS Importer Core
2. FS Importer Sprinter
3. BFS Shortcode Suite

---

## License

GPL-2.0-or-later
