# Changelog

All significant changes to HibbuCMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Migration to replace legacy `upload media` permission with `create media` and `edit media` for alignment with admin media routes
- Bulk menu item endpoint (`POST admin/menus/{menu}/items/bulk`) to add many navigation items in one request
- `CategoryFactory` and `PostFactory` for testing
- Pest feature tests for media permissions, post bulk-action authorization, and `PageSeeder`
- README section on production task scheduling (`schedule:run` / cron) for scheduled posts
- Comment reminders in `.env.example` and `routes/console.php` about the Laravel scheduler

### Changed
- `RoleAndPermissionSeeder`: media permissions now use `create media` and `edit media` (replacing `upload media`); role assignments updated accordingly
- `MediaController`: permission middleware mapped to real actions (`download`, `storeFolder`, `move`, `bulkDestroy`, etc.)
- `PostController`: stricter middleware for `revisions`, `autoSave`, and `restoreRevision`; `bulkAction` now checks permissions per action (delete / publish / draft)
- `tests/Pest.php`: Feature tests run with `withoutVite()` so CI does not require a Vite build manifest
- `NestedSetTest`: uses `appendToNode()` and correct `countErrors()` assertions for Kalnoy Nested Set

### Fixed
- Duplicate changelog line under 1.0.0 (“Modular theme system”)
- `PageSeeder` querying the Admin role using the correct Spatie role name (`Admin` instead of `admin`)

## [1.0.0] - 2025-04-24

### Added
- Basic CMS features
- Post management
- Page management
- User management
- Media management
- Category & tag system
- Basic theme support
- SEO optimization
- Basic security features
- Modular theme system
- Menu builder
- Role-based access control
- Dark mode support

[1.0.0]: https://github.com/firdausriawan2/hibbucms/releases/tag/v1.0.0 
