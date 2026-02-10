# FS Importer Sprinter

Asynchronous execution and worker layer for the FS Importer system.

---

## Overview

**FS Importer Sprinter** is the execution layer of the FS Importer ecosystem.

It is responsible for running import jobs asynchronously, managing concurrency,
and performing all external IO operations required by the system.

---

## Responsibilities

This plugin is responsible for:

- Scheduling import jobs
- Executing workers via WP-Cron
- Managing locks and preventing concurrent execution
- Handling retries and failures
- Calling external APIs
- Reporting execution results back to the core

It explicitly does **not**:

- Contain business or domain logic
- Render UI or shortcodes
- Decide what should be imported

---

## Architectural Role

FS Importer Sprinter:

- Executes commands created by the core
- Applies locking and idempotency
- Handles IO-heavy operations
- Keeps frontend requests fast and safe

All decisions and rules come from FS Importer Core.

---

## Dependencies

This plugin is designed to be used together with:

- FS Importer Core (domain layer)
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
