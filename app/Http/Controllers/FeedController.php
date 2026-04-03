<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;

class FeedController extends Controller
{
    /**
     * RSS 2.0 feed of published posts.
     */
    public function rss()
    {
        $posts = Post::query()
            ->published()
            ->latest('published_at')
            ->take(30)
            ->get();

        $siteName = Setting::get('site_name', config('app.name'));
        $siteDescription = Setting::get('site_description', '');

        return response()
            ->view('feed.rss', [
                'posts' => $posts,
                'siteName' => $siteName,
                'siteDescription' => $siteDescription,
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
