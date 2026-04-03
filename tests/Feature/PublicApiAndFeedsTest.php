<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\User;

it('lists published posts on the public JSON API', function () {
    $user = User::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'API Visible',
        'slug' => 'api-visible',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);
    Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Draft Hidden',
        'slug' => 'draft-hidden',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertOk();
    $response->assertJsonPath('data.0.slug', 'api-visible');
    $response->assertJsonMissingPath('data.1');
});

it('returns a single published post by slug with optional content', function () {
    $user = User::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Single Post',
        'slug' => 'single-post',
        'content' => '<p>Secret body</p>',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $this->getJson('/api/v1/posts/single-post')
        ->assertOk()
        ->assertJsonMissingPath('data.content');

    $this->getJson('/api/v1/posts/single-post?include_content=1')
        ->assertOk()
        ->assertJsonPath('data.content', '<p>Secret body</p>');
});

it('serves RSS feed with published posts', function () {
    $user = User::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'RSS Item',
        'slug' => 'rss-item',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $this->get('/feed.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
        ->assertSee('RSS Item', false)
        ->assertSee('rss-item', false);
});

it('serves sitemap including home and published post URL', function () {
    $user = User::factory()->create();
    Post::factory()->create([
        'user_id' => $user->id,
        'title' => 'Map Post',
        'slug' => 'map-post',
        'status' => 'published',
        'published_at' => now()->subHour(),
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<urlset', false)
        ->assertSee('/blog/map-post', false);
});

it('includes published pages in sitemap', function () {
    $user = User::factory()->create();
    Page::query()->create([
        'user_id' => $user->id,
        'title' => 'About',
        'slug' => 'about-us',
        'content' => '<p>Hi</p>',
        'status' => 'published',
        'order' => 0,
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('/pages/about-us', false);
});
