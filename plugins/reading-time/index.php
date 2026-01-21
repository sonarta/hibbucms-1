<?php

/**
 * Reading Time Plugin
 *
 * Calculates and displays estimated reading time for posts.
 * Uses a configurable words per minute rate (default: 200 WPM).
 */

// Calculate reading time from content
function reading_time_calculate($content, $words_per_minute = 200)
{
    // Strip HTML tags and count words
    $text = strip_tags($content);
    $word_count = str_word_count($text);

    // Calculate minutes
    $minutes = ceil($word_count / $words_per_minute);

    return $minutes;
}

// Format reading time string
function reading_time_format($minutes)
{
    if ($minutes < 1) {
        return '< 1 min read';
    } elseif ($minutes == 1) {
        return '1 min read';
    } else {
        return $minutes . ' min read';
    }
}

// Add reading time to post meta (in header next to date)
add_filter('post.meta', function ($meta, $post = null) {
    if (!$post || empty($post->content)) {
        return $meta;
    }

    $minutes = reading_time_calculate($post->content);
    $time_string = reading_time_format($minutes);

    // Create the reading time badge for meta area
    $badge = sprintf(
        '<span class="me-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1" style="vertical-align: -2px;">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            %s
        </span>',
        $time_string
    );

    return $meta . $badge;
}, 10);

// Fire action when plugin loads
add_action('plugins.loaded', function ($plugins) {
    // Plugin initialized
});
