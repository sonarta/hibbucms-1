# HibbuCMS Theme Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Theme Structure](#theme-structure)
3. [Theme Development](#theme-development)
4. [Theme Configuration](#theme-configuration)
5. [Template System](#template-system)
6. [Theme Hooks](#theme-hooks)
7. [Asset Management](#asset-management)
8. [Child Themes](#child-themes)
9. [Best Practices](#best-practices)

## Introduction

HibbuCMS themes control the visual appearance and layout of your website. This documentation provides comprehensive information about creating, customizing, and managing themes in HibbuCMS.

## Theme Structure

### Basic Structure

A HibbuCMS theme requires at minimum the following structure:

```
themes/
  └── your-theme/
      ├── assets/
      │   ├── css/
      │   │   └── style.css
      │   ├── js/
      │   │   └── main.js
      │   └── images/
      │       └── preview.svg (or preview.png)
      ├── views/
      │   ├── layouts/
      │   │   └── app.blade.php
      │   ├── partials/
      │   │   ├── header.blade.php
      │   │   ├── footer.blade.php
      │   │   └── sidebar.blade.php
      │   └── pages/
      │       ├── home.blade.php
      │       ├── blog.blade.php
      │       ├── post.blade.php
      │       ├── page.blade.php
      │       ├── category.blade.php
      │       ├── tag.blade.php
      │       ├── search.blade.php
      │       └── 404.blade.php
      ├── functions.php
      └── theme.json
```

### Required Files

- **theme.json**: Contains theme metadata and configuration
- **functions.php**: Contains theme functions, hooks, and filters
- **views/layouts/app.blade.php**: Main layout template
- **assets/css/style.css**: Main stylesheet

## Theme Development

### Creating a New Theme

To create a new theme:

1. Create a new directory in the `themes` folder with your theme name
2. Create the basic structure as outlined above
3. Create a `theme.json` file with theme metadata
4. Create a `functions.php` file to register hooks and functions
5. Create the necessary template files in the `views` directory

### Theme Metadata (theme.json)

The `theme.json` file contains metadata about your theme:

```json
{
    "name": "Your Theme Name",
    "slug": "your-theme",
    "version": "1.0.0",
    "description": "Description of your theme",
    "author": "Your Name",
    "preview": "assets/images/preview.svg",
    "requires": {
        "cms_version": "^1.0.0"
    },
    "settings": {
        "colors": {
            "primary": "#0d6efd",
            "secondary": "#6c757d"
        },
        "typography": {
            "font-family": "Arial, sans-serif",
            "font-size": "16px"
        },
        "layout": {
            "container-width": "1200px",
            "sidebar": "right"
        }
    }
}
```

## Theme Configuration

### Theme Functions (functions.php)

The `functions.php` file is where you register hooks, filters, menus, sidebars, and assets:

```php
<?php

/**
 * Theme Functions
 *
 * Register hooks, filters, menus, sidebars, and assets for your theme.
 */

// Hook that runs after theme is loaded
add_action('theme.loaded', function($theme) {
    // Register menus
    register_theme_menus();
    
    // Register sidebars
    register_theme_sidebars();
    
    // Register assets
    enqueue_theme_assets();
});

/**
 * Function to register theme menus
 */
function register_theme_menus() {
    // Register theme menus
    add_action('register_menus', function() {
        return [
            'main-menu' => 'Main Menu',
            'footer-menu' => 'Footer Menu'
        ];
    });
}

/**
 * Function to register theme sidebars
 */
function register_theme_sidebars() {
    // Register theme sidebars
    add_action('register_sidebars', function() {
        return [
            'sidebar' => [
                'name' => 'Sidebar',
                'description' => 'Main sidebar that appears on the right.'
            ],
            'footer' => [
                'name' => 'Footer',
                'description' => 'Footer widget area.'
            ]
        ];
    });
}

/**
 * Function to register theme assets (CSS, JavaScript)
 */
function enqueue_theme_assets() {
    // Register and enqueue theme stylesheet
    add_action('theme.styles', function() {
        echo '<link rel="stylesheet" href="' . theme_asset('css/style.css') . '">';
    });
    
    // Register and enqueue theme JavaScript
    add_action('theme.scripts', function() {
        echo '<script src="' . theme_asset('js/main.js') . '"></script>';
    });
}
```

## Template System

### Template Hierarchy

HibbuCMS uses a template hierarchy system to determine which template file to use for a given page. The system searches for templates in the following order:

#### Home Page
```
theme::templates.home
theme::home
theme::index
```

#### Blog Page
```
theme::templates.blog
theme::blog
theme::archive
theme::index
```

#### Single Post
```
theme::templates.post-{slug}
theme::post-{slug}
theme::templates.post
theme::post
theme::single
theme::index
```

#### Page
```
theme::templates.page-{slug}
theme::page-{slug}
theme::templates.page
theme::page
theme::index
```

#### Category Archive
```
theme::templates.category-{slug}
theme::category-{slug}
theme::category
theme::archive
theme::index
```

#### Tag Archive
```
theme::templates.tag-{slug}
theme::tag-{slug}
theme::tag
theme::archive
theme::index
```

#### Search Results
```
theme::templates.search
theme::search
theme::archive
theme::index
```

#### 404 Page
```
theme::templates.404
theme::404
theme::index
```

### Creating Templates

Templates are created using Laravel Blade templating engine. Here's an example of a basic template:

```php
@extends('theme::layouts.app')

@section('title', 'Page Title')

@section('content')
    <div class="container">
        <h1>Page Title</h1>
        <p>Page content goes here.</p>
    </div>
@endsection
```

## Theme Hooks

HibbuCMS provides various hooks that allow you to customize theme behavior:

### Theme Hooks

- `theme.loaded` - Triggered after theme is loaded
- `theme.view_path_base` - Filter to modify theme view path
- `theme.parent_view_path_base` - Filter to modify parent theme view path
- `theme.assets_path_base` - Filter to modify theme assets path
- `theme.public_assets_path` - Filter to modify theme public assets path
- `theme.before_activate` - Triggered before theme activation (admin: `ThemeController::activate`)
- `theme.after_activate` - Triggered after theme activation
- `theme.before_delete` - Triggered before theme deletion (only if theme is not active; admin: `ThemeController::destroy`)
- `theme.after_delete` - Triggered after DB delete; receives an array snapshot (`id`, `name`, `slug`, `folder_name`)
- `theme.styles` - Action to output theme styles
- `theme.scripts` - Action to output theme scripts

### Template Hooks

- `template_hierarchy` - Filter to modify template hierarchy
- `template_include` - Filter to modify template to be used

### Content Hooks

#### Home Page
- `before_home_page` - Triggered before home page render
- `home_page_data` - Filter to modify home page data

#### Blog Page
- `blog_posts_query` - Filter to modify blog posts query
- `blog_posts_per_page` - Filter to modify posts per page count
- `before_blog_page` - Triggered before blog page render
- `blog_page_data` - Filter to modify blog page data

#### Single Post
- `post_found` - Triggered after post is found
- `before_post` - Triggered before post render
- `post_data` - Filter to modify post data
- `related_posts_query` - Filter to modify related posts query
- `related_posts_count` - Filter to modify related posts count

#### Page
- `page_found` - Triggered after page is found
- `before_page` - Triggered before page render
- `page_data` - Filter to modify page data

## Asset Management

### Registering Assets

Assets are registered in the `functions.php` file using the `enqueue_theme_assets` function:

```php
function enqueue_theme_assets() {
    // Register and enqueue theme stylesheet
    add_action('theme.styles', function() {
        echo '<link rel="stylesheet" href="' . theme_asset('css/style.css') . '">';
    });
    
    // Register and enqueue theme JavaScript
    add_action('theme.scripts', function() {
        echo '<script src="' . theme_asset('js/main.js') . '"></script>';
    });
}
```

### Using Assets in Templates

To use assets in templates, you need to call the appropriate hooks in your layout file:

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('app.name') }}</title>
    
    <!-- Theme Styles -->
    {!! do_action('theme.styles') !!}
    
    @stack('styles')
</head>
<body>
    <!-- Content -->
    @yield('content')
    
    <!-- Theme Scripts -->
    {!! do_action('theme.scripts') !!}
    
    @stack('scripts')
</body>
</html>
```

### Publishing Assets

Theme assets need to be published to the public directory to be accessible. This is done automatically when a theme is activated, but you can also publish assets manually using the following Artisan command:

```bash
php artisan vendor:publish --tag=theme-assets --force
```

## Child Themes

### Creating a Child Theme

Child themes allow you to modify a parent theme without changing its files. To create a child theme:

1. Create a new directory in the `themes` folder for your child theme
2. Create a `theme.json` file with a reference to the parent theme
3. Create a `functions.php` file to add or override functionality

### Child Theme Structure

```
themes/
  ├── parent-theme/
  │   ├── assets/
  │   ├── views/
  │   └── functions.php
  └── child-theme/
      ├── assets/
      ├── views/
      ├── functions.php
      └── theme.json
```

### Child Theme Configuration

The `theme.json` file for a child theme should include a reference to the parent theme:

```json
{
    "name": "Child Theme",
    "slug": "child-theme",
    "version": "1.0.0",
    "description": "A child theme of Parent Theme",
    "author": "Your Name",
    "preview": "assets/images/preview.svg",
    "requires": {
        "cms_version": "^1.0.0"
    },
    "settings": {
        "parent": "parent-theme"
    }
}
```

### Overriding Parent Theme

Child themes can override parent theme files by creating files with the same name in the same relative path. For example, to override the `views/pages/home.blade.php` file from the parent theme, create a file at `views/pages/home.blade.php` in your child theme.

Child themes can also override parent theme functions by using the same hook with a higher priority (lower number):

```php
// In child theme's functions.php

// Override parent theme's blog_posts_per_page filter
add_filter('blog_posts_per_page', function($perPage) {
    return 9; // Show 9 posts per page instead of parent theme's value
}, 5); // Higher priority (lower number) than parent theme's filter (default 10)
```

## Best Practices

### Theme Development

1. **Follow the Structure**: Maintain the recommended theme structure for consistency
2. **Use Hooks**: Use hooks and filters instead of modifying core files
3. **Responsive Design**: Ensure your theme is responsive and works on all devices
4. **Accessibility**: Follow accessibility best practices
5. **Validation**: Validate user input and sanitize output
6. **Documentation**: Document your theme code and provide usage instructions

### Performance

1. **Optimize Assets**: Minimize and combine CSS and JavaScript files
2. **Lazy Loading**: Implement lazy loading for images
3. **Caching**: Implement browser caching for static assets
4. **Efficient Queries**: Optimize database queries

### Security

1. **Sanitize Input**: Always sanitize user input
2. **Escape Output**: Always escape output to prevent XSS attacks
3. **Validate Data**: Validate data before processing
4. **Use Prepared Statements**: Use prepared statements for database queries

### Compatibility

1. **Browser Compatibility**: Test your theme on different browsers
2. **Device Compatibility**: Test your theme on different devices
3. **Plugin Compatibility**: Ensure your theme works with common plugins
4. **Future Compatibility**: Follow best practices to ensure future compatibility