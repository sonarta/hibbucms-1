# Hook System and Template Hierarchy in HibbuCMS

## Overview

The hook system lets themes and plugins extend the CMS without editing core files. It is inspired by WordPress: **actions** run side effects; **filters** transform a value and must **return** the new value.

Implementation: `App\Services\HookService`, global helpers in `app/Helpers/HookHelper.php` (loaded from `HookServiceProvider`).

---

## Registering hooks (where and when)

| Location | File | When it runs |
|----------|------|----------------|
| **Active theme** | `themes/{folder}/functions.php` | Loaded during `ThemeServiceProvider::boot()` (parent theme’s `functions.php` first, then child). |
| **Parent theme** | `themes/{parent}/functions.php` | Before the active theme’s `functions.php`. |
| **Plugin** | `plugins/{slug}/index.php` (or path returned by `Plugin::getIndexPath()`) | Each active plugin is `require_once` in `PluginServiceProvider::boot()`, **before** `plugins.loaded`. |

**Order of bootstrap (simplified):**

1. Service providers register; `HookHelper` defines `add_action`, `add_filter`, `do_action`, `apply_filters`, etc.
2. `HookServiceProvider` registers **default** filter/action callbacks (e.g. empty placeholders for some `theme.*` hooks).
3. Active plugins are loaded → `plugin.loading` / `plugin.loaded` per plugin → `plugins.loaded`.
4. Theme view namespaces and `functions.php` (parent then child) → `theme.loaded`.

Use **`add_action` / `add_filter`** from any of the files above. Use **`do_action` / `apply_filters`** only in core or in your own code; themes/plugins should normally only subscribe.

---

## Actions vs filters

### Action

```php
add_action('hook_name', function ($arg1, $arg2) {
    // no return; use for echo, logging, registering extra state
}, 10);

do_action('hook_name', $arg1, $arg2);
```

### Filter

```php
add_filter('hook_name', function ($value, $maybeExtra) {
    return $modifiedValue; // must return
}, 10);

$value = apply_filters('hook_name', $value, $maybeExtra);
```

### Priority

Lower number runs **earlier**. Default is `10`.

### Removing hooks

```php
remove_action('hook_name', $callable, 10);
remove_filter('hook_name', $callable, 10);
// Omitting $callable + priority can remove all callbacks for that hook (see HookService).
```

### Checking

```php
has_action('hook_name');
has_filter('hook_name');
```

---

## Template hierarchy (how paths work)

`TemplateHierarchy` builds a list of **relative view names** (dots, no `theme::` prefix), e.g. `pages.home`, `pages.blog`, `single`.

Resolution:

1. `apply_filters('template_hierarchy', $templates, $theme)` can alter the list.
2. For each candidate, the core checks `View::exists("themes.{folder}.views.{candidate}")` — i.e. file  
   `themes/{active_theme}/views/{path with dots as slashes}.blade.php`.
3. Child theme is tried first; then parent theme if the theme is a child.
4. The chosen view name is passed through `apply_filters('template_include', $templatePath, $template, $theme)` (or `$parentTheme`).

**Namespace in Blade:** use the `theme::` prefix, e.g. `theme::pages.home` → same file as above.

The lists below match `app/Services/TemplateHierarchy.php` (current core).

### Home

```text
pages.home
pages.index
index
```

### Blog / archive

```text
pages.blog
pages.archive
archive
index
```

### Category archive (`$categorySlug`)

```text
pages.category-{slug}
pages.category
pages.archive
archive
index
```

### Tag archive (`$tagSlug`)

```text
pages.tag-{slug}
pages.tag
pages.archive
archive
index
```

### Single post (`$postSlug`, optional `$postType` default `post`)

```text
pages.{postType}-{slug}
pages.{postType}
pages.single
single
index
```

### CMS page (`$pageSlug`)

```text
pages.page-{slug}
pages.page
page
index
```

### Search

```text
pages.search
search
index
```

### 404

```text
pages.404
404
index
```

### Static catch-all page (`FrontendController::staticPage`)

The initial list is built in the controller, then filtered:

- Filter: `static_page_templates` — receives `(array $templates, string $pageSlug)`.

---

## Hooks reference (core as of current codebase)

The following are **dispatched** from core unless noted.

### Plugins

| Hook | Type | Arguments | Emitted from |
|------|------|------------|--------------|
| `plugin.loading` | Action | `$plugin` (`App\Models\Plugin`) | `PluginServiceProvider` before `require` |
| `plugin.loaded` | Action | `$plugin` | After successful `require_once` |
| `plugins.loaded` | Action | `$plugins` (collection of active plugins) | End of plugin boot |

### Theme bootstrap

| Hook | Type | Arguments | Emitted from |
|------|------|------------|--------------|
| `theme.view_path_base` | Filter | `$path` (string), `$theme` | `ThemeServiceProvider` — filesystem path to active theme `views` |
| `theme.parent_view_path_base` | Filter | `$path`, `$parentTheme` | Parent theme `views` path |
| `theme.assets_path_base` | Filter | `$path`, `$theme` | Theme `assets` folder (source for publish) |
| `theme.public_assets_path` | Filter | `$path`, `$theme` | Public assets destination |
| `theme.loaded` | Action | `$theme` | After namespaces registered |

### Theme model (`App\Models\Theme`)

| Hook | Type | Arguments |
|------|------|------------|
| `theme.view_path` | Filter | Dot view path string, `$theme`, `$view` name |
| `theme.asset_path` | Filter | Full asset URL string, `$theme`, `$path` |
| `theme.parent_view_path` | Filter | Parent view name for `parent_theme::`, `$parentTheme`, `$view` |

### Frontend (`App\Http\Controllers\FrontendController`)

| Hook | Type | Arguments |
|------|------|------------|
| `before_home_page` | Action | `$posts`, `$categories`, `$tags` |
| `home_page_data` | Filter | `$data` (array) |
| `blog_posts_query` | Filter | `$query` (Eloquent builder), `Request $request` |
| `blog_posts_per_page` | Filter | `int` (default `9`) |
| `before_blog_page` | Action | `$posts`, `$categories`, `$tags`, `Request $request` |
| `blog_page_data` | Filter | `$data` (array), `Request $request` |
| `post_found` | Action | `$post` |
| `related_posts_query` | Filter | `$query` (builder), `$post` |
| `related_posts_count` | Filter | `int` (default `3`) |
| `before_single_post` | Action | `$post`, `$relatedPosts` |
| `single_post_data` | Filter | `$data` (array) |
| `page_found` | Action | `$page` |
| `before_page` | Action | `$page` |
| `page_data` | Filter | `$data` (array) |
| `before_static_page` | Action | `$pageSlug` (string) |
| `static_page_data` | Filter | `$data` (array), `$pageSlug` (string) |
| `static_page_templates` | Filter | `array $templates`, `$pageSlug` |

Preview routes (`previewPost` / `previewPage`) reuse `before_single_post`, `single_post_data`, `page_found`, `before_page`, `page_data` as appropriate.

### Template resolution

| Hook | Type | Arguments |
|------|------|------------|
| `template_hierarchy` | Filter | `array $templates`, `$theme` |
| `template_include` | Filter | Resolved view name string, `$template` key, `$theme` or `$parentTheme` |

### Blade / theme layouts (used in bundled themes)

| Hook | Type | Notes |
|------|------|--------|
| `post.content` | Filter | `(string $html, $post)` — wrap or sanitize post body in templates. |
| `post.meta` | Filter | `(string $html, $post)` — extra HTML in post meta area. |
| `theme.styles` | Action | Used in some layouts; callbacks should **echo** HTML. `do_action` itself returns nothing — output only if callbacks print. |
| `theme.scripts` | Action | Same pattern as `theme.styles`. |

### Registered in `HookServiceProvider` but not used in core views yet

These filters have default pass-through callbacks; nothing in core calls `apply_filters('theme.content', …)` today:

- `theme.content`

### Theme lifecycle (`ThemeController`)

Dispatched when an admin activates or deletes a theme (requires `manage themes` permission).

| Hook | Type | Arguments | When |
|------|------|------------|------|
| `theme.before_activate` | Action | `Theme $theme` | Before other themes are deactivated and this one is saved as active. |
| `theme.after_activate` | Action | `Theme $theme` | After activation; use `fresh()` state (`is_active === true`). |
| `theme.before_delete` | Action | `Theme $theme` | Before filesystem folder removal and DB delete. Only runs for **non-active** themes. |
| `theme.after_delete` | Action | `array $snapshot` | After the theme row is removed. Contains at least `id`, `name`, `slug`, `folder_name` (for logging or cache invalidation). |

`HookServiceProvider` still registers empty default callbacks for these hooks so early subscribers can attach; core now **also** fires them from `ThemeController::activate` and `ThemeController::destroy`.

---

## Child themes

If `theme.json` / theme settings define a parent, view paths and `functions.php` load order are: **parent first**, then **child**. Template search in `TemplateHierarchy::locateTemplate` checks the child theme’s views first, then the parent’s.

---

## Usage examples

### Extra CSS on home (echo in action)

```php
add_action('before_home_page', function () {
    echo '<style>.home-banner { padding: 2rem 0; }</style>';
});
```

### Posts per page

```php
add_filter('blog_posts_per_page', fn () => 12);
```

### Extra data for blog page

```php
add_filter('blog_page_data', function (array $data, $request) {
    $data['site_notice'] = 'Welcome to our blog.';
    return $data;
}, 10, 2);
```

### Related posts by same category

`Post` uses a single `category` relationship (not `categories`):

```php
add_filter('related_posts_query', function ($query, $post) {
    if (! $post->category_id) {
        return $query;
    }
    return $query->where('category_id', $post->category_id);
}, 10, 2);
```

### Adjust template hierarchy

```php
add_filter('template_hierarchy', function (array $templates, $theme) {
    array_unshift($templates, 'pages.custom-home');
    return $templates;
}, 10, 2);
```

---

## See also

- `docs/theme-development-guide.md` — broader theme authoring.
- `app/Services/HookService.php` — hook storage and execution.
- `app/Helpers/HookHelper.php` — global API.
