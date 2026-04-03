# Changelog

All significant changes to HibbuCMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Editor workflow & preview:** status `pending_review` untuk post dan halaman; pratinjau tema di `/preview/post/{post}` dan `/preview/page/{page}` (login + permission `view content`, atau tautan bertanda tangan 72 jam dari admin); banner pratinjau di tema default; tombol Pratinjau / salin tautan di form post & halaman
- **i18n (fondasi):** locale default `en`, file bahasa `lang/id` & `lang/en` untuk `posts` dan `common`, middleware `SetLocale`, pemilih bahasa (EN/ID) di header admin, `POST /locale/{locale}`, serta `locale` + `translations` di Inertia
- `doctrine/dbal` untuk migrasi yang mengubah kolom `status` (termasuk dukungan SQLite di pengujian)
- `tests/Feature/ContentPreviewAndWorkflowTest.php`
- Laravel Sanctum (`personal_access_tokens`), `HasApiTokens` on `User`, and public read-only JSON API: `GET /api/v1/posts`, `GET /api/v1/posts/{slug}` (optional `include_content=1` for full HTML body)
- RSS 2.0 feed at `/feed.xml` and XML sitemap at `/sitemap.xml` (home, blog index, published posts and pages)
- `tests/Feature/PublicApiAndFeedsTest.php` for API, feed, and sitemap
- Migration to replace legacy `upload media` permission with `create media` and `edit media` for alignment with admin media routes
- Bulk menu item endpoint (`POST admin/menus/{menu}/items/bulk`) to add many navigation items in one request
- `CategoryFactory` and `PostFactory` for testing
- Pest feature tests for media permissions, post bulk-action authorization, and `PageSeeder`
- README section on production task scheduling (`schedule:run` / cron) for scheduled posts
- Comment reminders in `.env.example` and `routes/console.php` about the Laravel scheduler

### Changed
- `.env.example`: `APP_LOCALE=en` sebagai default aplikasi
- `SettingsSeeder`: removed unused `comments_enabled` key (no backend comments feature); default theme single post template no longer renders an empty comments placeholder
- `RoleAndPermissionSeeder`: media permissions now use `create media` and `edit media` (replacing `upload media`); role assignments updated accordingly
- `MediaController`: permission middleware mapped to real actions (`download`, `storeFolder`, `move`, `bulkDestroy`, etc.)
- `PostController`: stricter middleware for `revisions`, `autoSave`, and `restoreRevision`; `bulkAction` now checks permissions per action (delete / publish / draft)
- `tests/Pest.php`: Feature tests run with `withoutVite()` so CI does not require a Vite build manifest
- `NestedSetTest`: uses `appendToNode()` and correct `countErrors()` assertions for Kalnoy Nested Set

### Notes
- Upgrade to Laravel 13 is deferred: current `inertiajs/inertia-laravel` and `pestphp/pest-plugin-laravel` constraints do not yet support Laravel 13; project remains on Laravel 12 until those packages allow `^13`.

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
