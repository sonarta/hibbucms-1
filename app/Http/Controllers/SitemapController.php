<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;

class SitemapController extends Controller
{
    /**
     * Basic sitemap for published posts and pages.
     */
    public function index()
    {
        $urls = [];

        $urls[] = ['loc' => url('/'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => route('blog', absolute: true), 'changefreq' => 'daily', 'priority' => '0.9'];

        Post::query()
            ->published()
            ->select('slug', 'updated_at')
            ->orderBy('id')
            ->chunk(100, function ($posts) use (&$urls) {
                foreach ($posts as $post) {
                    $urls[] = [
                        'loc' => route('blog.post', ['slug' => $post->slug], absolute: true),
                        'lastmod' => $post->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

        Page::query()
            ->where('status', 'published')
            ->select('slug', 'updated_at')
            ->orderBy('id')
            ->chunk(100, function ($pages) use (&$urls) {
                foreach ($pages as $page) {
                    $urls[] = [
                        'loc' => route('page', ['slug' => $page->slug], absolute: true),
                        'lastmod' => $page->updated_at?->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.7',
                    ];
                }
            });

        return response()
            ->view('sitemap.xml', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
