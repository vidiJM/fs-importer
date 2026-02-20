# Contributing to FS Importer

Thanks for your interest in contributing to **FS Importer** 🎉

This project follows strict architectural and quality rules to ensure scalability, performance, and long-term maintainability.  
Please read this document carefully before submitting any contribution.

---

## 🧭 Project Philosophy

FS Importer is built with the following principles:

- **Async-first architecture**
- **Clear separation of concerns**
- **Domain-driven core**
- **High-traffic safety**
- **Long-term maintainability over short-term convenience**

If a change violates these principles, it will not be accepted.

---

## 🧩 Plugin Responsibilities

This repository is a **monorepo** containing multiple plugins.  
Each plugin has a single responsibility:

| Plugin | Responsibility |
|------|---------------|
| fs-shortcode-suite | Frontend/UI (shortcodes, rendering) |
| fs-importer-core | Domain logic, validation, commands, repositories |
| fs-importer-sprinter | Async execution, cron, workers, API calls |

**Do not blur boundaries between plugins.**

---

## 🚫 Hard Rules (Non-Negotiable)

- ❌ No business logic in frontend plugins
- ❌ No database access outside repositories
- ❌ No external API calls outside workers
- ❌ No long-running or blocking operations in frontend requests
- ❌ No access to `$wpdb` outside repository classes
- ❌ No vendor directories committed to Git

If your change breaks any of these rules, it must be redesigned.

---

## 🛠 Development Setup

### Requirements

- PHP 8.0+
- WordPress 6.0+
- Git
- Local WordPress environment (LocalWP, Docker, etc.)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/<your-org>/fs-importer.git
   ```

2. Copy plugins into WordPress:
   ```
   wp-content/plugins/
     ├── fs-shortcode-suite
     ├── fs-importer-core
     └── fs-importer-sprinter
   ```

3. Activate plugins in this order:
   1. fs-importer-core
   2. fs-importer-sprinter
   3. fs-shortcode-suite

---

## 🧪 Testing

- New features should include tests where applicable
- Do not break existing functionality
- Prefer unit tests in the core domain
- Workers should be tested for failure and retry scenarios

---

## 📦 Dependencies

- `vendor/` directories must NOT be committed
- `composer.lock` is not tracked for plugins
- Keep dependencies minimal and justified

---

## 📝 Commit Guidelines

- Write clear, descriptive commit messages
- One logical change per commit
- Documentation-only changes should be clearly marked

Recommended format:
```
type: short description

Optional longer explanation
```

Examples:
- `docs: add contributing guidelines`
- `chore: remove vendor directory`
- `feat: add import retry mechanism`

---

## 🔍 Pull Requests

Before opening a PR:

- Ensure code follows existing architecture
- Ensure boundaries between plugins are respected
- Ensure no build artifacts are included
- Ensure documentation is updated if needed

PRs that do not respect the architecture will be rejected.

---

## 🏁 Final Note

FS Importer is designed as a **backend system built on top of WordPress**, not a typical plugin.

If you contribute with this mindset, your changes will fit naturally.  
If not, the architecture will push back.

Thanks for contributing 🚀
