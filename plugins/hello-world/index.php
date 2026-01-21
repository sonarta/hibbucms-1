<?php

/**
 * Hello World Plugin
 *
 * This is a sample plugin demonstrating the HibbuCMS plugin system.
 * It shows how to use hooks to modify content and add functionality.
 */

// Add a custom filter to post content
add_filter('post.content', function ($content) {
    // This is just a demonstration - in real use, you might want condition checks
    // return $content . '<p class="text-sm text-muted-foreground mt-4">Hello from the Hello World plugin! 👋</p>';
    return $content;
});

// Add an action when the theme is loaded
add_action('theme.loaded', function ($theme) {
    // Log that the plugin is active
    // \Log::info('Hello World plugin is active with theme: ' . $theme->name);
});

// Add an action when all plugins are loaded
add_action('plugins.loaded', function ($plugins) {
    // You could perform initialization here that depends on other plugins
});
