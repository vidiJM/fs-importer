# FS Importer Core

Core domain logic for the FS Importer system.

---

## Overview

**FS Importer Core** is the central domain plugin of the FS Importer ecosystem.

It owns all business rules, validation logic, and system state, acting as the single source of truth for the entire importer architecture.

Other plugins interact with the system **through this plugin**, never around it.

---

## Responsibilities

FS Importer Core is responsible for:

- Validating import requests
- Applying domain rules and invariants
- Creating and dispatching import commands
- Managing import state and persistence
- Defining domain events and transitions

It explicitly does **not**:

- Render UI or shortcodes
- Execute long-running or blocking tasks
- Perform external API calls

---

## Architectural Role

FS Importer Core:

- Owns the domain
- Defines boundaries between plugins
- Prevents business logic leakage
- Ensures consistency and integrity

If a feature requires a rule or decision, it belongs here.

---

## Dependencies

This plugin is designed to be used together with:

- FS Importer Sprinter (execution layer)
- BFS Shortcode Suite (frontend layer)

It is not intended to be used standalone.

---

## Activation Order

1. FS Importer Core
2. FS Importer Sprinter
3. BFS Shortcode Suite

---

## License

GPL-2.0-or-later
