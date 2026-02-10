# FS Importer

Modular, async-first WordPress importer architecture designed for scalability, performance, and clean separation of concerns.

---

## 🚀 Overview

**FS Importer** is a modular WordPress importer ecosystem built for **high-traffic environments** and **complex data integrations**.

Instead of a monolithic plugin, FS Importer is composed of multiple WordPress plugins, each with a **single, well-defined responsibility**:

- Frontend orchestration (UI / shortcodes)
- Core domain logic (validation, rules, state)
- Asynchronous execution (workers, cron, external APIs)

This architecture allows the system to scale safely while remaining maintainable over time.

---

## 🧩 Plugin Ecosystem

This repository is a **monorepo** that contains all plugins required to run FS Importer as a single system.

```
plugins/
├── bfs-shortcode-suite/
├── fs-importer-core/
└── fs-importer-sprinter/
```

### Plugins and responsibilities

| Plugin | Responsibility |
|------|---------------|
| **bfs-shortcode-suite** | Frontend / UI layer (shortcodes, rendering) |
| **fs-importer-core** | Domain logic, validation, commands, repositories |
| **fs-importer-sprinter** | Async execution, cron, workers, API calls |

These plugins are designed to work together and are **not intended to be used independently**.

---

## 🧠 Architectural Principles

- Async-first: no heavy work in frontend requests
- Clear separation of concerns
- Core owns the domain (rules and decisions)
- Workers execute, they do not decide
- Frontend never accesses the database
- High-traffic safe by design

---

## 🔄 High-Level Flow

```
User
 → Shortcode (frontend)
 → ImportRequestDTO
 → Core (validate & decide)
 → ImportCommand
 → CommandBus
 → Scheduler
 → WP-Cron
 → Worker
 → External API
 → Repositories
 → ImportResult
 → Frontend renders status/result
```

---

## 📚 Documentation

Project documentation lives inside the `/docs` directory and includes onboarding guides, architecture explanations, async flow diagrams, PHP namespace maps, and the technical roadmap.

---

## 🛠 Development Setup

### Requirements

- PHP 8.0+
- WordPress 6.0+
- Git
- Local WordPress environment (LocalWP, Docker, etc.)

### Local usage

1. Clone the repository:
```
git clone https://github.com/your-org/fs-importer.git
```

2. Copy plugins into WordPress:
```
wp-content/plugins/
  ├── bfs-shortcode-suite
  ├── fs-importer-core
  └── fs-importer-sprinter
```

3. Activate plugins in this order:
1. fs-importer-core
2. fs-importer-sprinter
3. bfs-shortcode-suite

---

## 📜 License

GNU General Public License v2.0 or later (GPL-2.0-or-later).

---

## 🏁 Final Note

FS Importer is not a typical WordPress plugin.

It is designed as a **modular backend system built on top of WordPress**, focused on performance, scalability, maintainability, and long-term evolution.
