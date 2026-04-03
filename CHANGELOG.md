# Changelog

All significant changes to HibbuCMS will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Security & compliance:** two-factor authentication (TOTP + recovery codes), `/two-factor-challenge` flow, setup under **Admin → Settings → Security**; audit trail via `spatie/laravel-activitylog` (automatic login/logout logging, post/page model changes), **Admin → Audit log** page (permission `view audit log`); dependencies `pragmarx/google2fa`, `bacon/bacon-qr-code`; `tests/Feature/TwoFactorAndAuditTest.php`
- **Media:** responsive image variants (thumb / medium / large) via Intervention Image, `variants` JSON column on `media`, `MEDIA_DISK` / `MEDIA_URL` / `MEDIA_IMAGE_VARIANTS`, `league/flysystem-aws-s3-v3` for S3 disks; `tests/Feature/MediaVariantsTest.php` (requires PHP `gd` extension)
- `ThemeController` dispatches `theme.before_activate`, `theme.after_activate`, `theme.before_delete`, and `theme.after_delete`; `tests/Feature/Admin/ThemeLifecycleHooksTest.php`
- Documentation: `docs/hooks-and-templates.md` expanded with registration order, full hook tables matching core, real template hierarchy from `TemplateHierarchy`, Blade-level filters, and theme lifecycle hooks
- **Editor workflow & preview:** `pending_review` status for posts and pages; theme preview at `/preview/post/{post}` and `/preview/page/{page}` (`view content` permission or 72-hour signed link from admin); preview banner in the default theme; Preview / copy-link actions on post & page forms
- **i18n (foundation):** default locale `en`, `lang/id` & `lang/en` for `posts` and `common`, `SetLocale` middleware, EN/ID switcher in the admin header, `POST /locale/{locale}`, and `locale` + `translations` in Inertia
- `doctrine/dbal` for migrations altering the `status` column (including SQLite in tests)
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
- `RoleAndPermissionSeeder`: `view audit log` permission (Security group) granted to Admin and Super Admin roles
- Login flow: when 2FA is enabled, users who pass email/password are sent to the OTP challenge before the session is established
- `.env.example`: `APP_LOCALE=en` as the default application locale
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
