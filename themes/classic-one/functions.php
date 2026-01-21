<?php

/**
 * File functions.php for Classic One theme
 *
 * This file is used to register hooks, filters, and other functions needed by the theme.
 * This file will be loaded automatically by the theme system.
 */

// Hook that runs after theme is loaded
add_action('theme.loaded', function ($theme) {
    // Register theme assets, menus, and sidebars
    register_theme_menus();
    register_theme_sidebars();
    enqueue_theme_assets();
});

// Filter to modify home page data
add_filter('home_page_data', function ($data) {
    // Add featured posts to home page data
    if (!isset($data['featured_posts'])) {
        $data['featured_posts'] = \App\Models\Post::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
    }

    // Add recent posts for sidebar
    if (!isset($data['recentPosts'])) {
        $data['recentPosts'] = \App\Models\Post::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
    }

    return $data;
});

// Filter to modify number of posts per page on blog page
add_filter('blog_posts_per_page', function ($perPage) {
    // Change number of posts per page to 10 (classic blog style)
    return 10;
});

// Filter to modify query on blog page
add_filter('blog_posts_query', function ($query, $request) {
    // Modify query if needed
    return $query;
}, 10);

// Filter to modify blog page data
add_filter('blog_page_data', function ($data, $request) {
    // Add popular posts to sidebar
    if (!isset($data['popular_posts'])) {
        $data['popular_posts'] = \App\Models\Post::where('status', 'published')
            ->orderBy('views', 'desc')
            ->limit(5)
            ->get();
    }

    // Add recent posts for sidebar
    if (!isset($data['recentPosts'])) {
        $data['recentPosts'] = \App\Models\Post::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
    }

    return $data;
}, 10);

// Action that runs after post is found
add_action('post_found', function ($post) {
    // Increment view count
    if (isset($post->views)) {
        $post->increment('views');
    }
});

// Filter to modify related posts query
add_filter('related_posts_query', function ($query, $post) {
    // Get category ID from post
    $categoryId = $post->category_id ?? null;

    // If post has category, filter by same category
    if ($categoryId) {
        return $query->where('category_id', $categoryId);
    }

    return $query;
}, 10);

// Filter to modify number of related posts
add_filter('related_posts_count', function ($count) {
    // Show 3 related posts
    return 3;
});

// Filter to modify single post data
add_filter('single_post_data', function ($data) {
    // Add author info to single post data
    if (isset($data['post']) && isset($data['post']->author)) {
        $data['author_posts_count'] = \App\Models\Post::where('author_id', $data['post']->author_id)
            ->where('status', 'published')
            ->count();
    }

    // Add recent posts for sidebar
    if (!isset($data['recentPosts'])) {
        $data['recentPosts'] = \App\Models\Post::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
    }

    // Add categories for sidebar
    if (!isset($data['categories'])) {
        $data['categories'] = \App\Models\Category::withCount([
            'posts' => function ($query) {
                $query->where('status', 'published');
            }
        ])->get();
    }

    // Add tags for sidebar
    if (!isset($data['tags'])) {
        $data['tags'] = \App\Models\Tag::withCount([
            'posts' => function ($query) {
                $query->where('status', 'published');
            }
        ])->get();
    }

    return $data;
});

// Action that runs after page is found
add_action('page_found', function ($page) {
    // Do something after page is found
});

// Filter to modify page data
add_filter('page_data', function ($data) {
    // Add categories for sidebar
    if (!isset($data['categories'])) {
        $data['categories'] = \App\Models\Category::withCount([
            'posts' => function ($query) {
                $query->where('status', 'published');
            }
        ])->get();
    }

    // Add tags for sidebar
    if (!isset($data['tags'])) {
        $data['tags'] = \App\Models\Tag::withCount([
            'posts' => function ($query) {
                $query->where('status', 'published');
            }
        ])->get();
    }

    // Add recent posts for sidebar
    if (!isset($data['recentPosts'])) {
        $data['recentPosts'] = \App\Models\Post::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(5)
            ->get();
    }

    return $data;
});

// Filter to modify template hierarchy
add_filter('template_hierarchy', function ($templates, $theme) {
    // Add templates directory to hierarchy
    if (is_array($templates)) {
        foreach ($templates as $key => $template) {
            // Check if template starts with 'templates.'
            if (strpos($template, 'templates.') !== 0) {
                // Add templates directory
                $templates[$key] = 'templates.' . $template;
            }
        }
    }

    return $templates;
}, 10);

/**
 * Function to register theme menus
 */
function register_theme_menus()
{
    // Register theme menus here
}

/**
 * Function to register theme sidebars
 */
function register_theme_sidebars()
{
    // Register theme sidebars here
}

/**
 * Function to register theme assets (CSS, JavaScript)
 */
function enqueue_theme_assets()
{
    // Register and enqueue theme stylesheet
    add_action('theme.styles', function () {
        echo '<link rel="stylesheet" href="' . theme_asset('css/style.css') . '">';
    });

    // Register and enqueue theme JavaScript
    add_action('theme.scripts', function () {
        echo '<script src="' . theme_asset('js/main.js') . '"></script>';
    });
}
