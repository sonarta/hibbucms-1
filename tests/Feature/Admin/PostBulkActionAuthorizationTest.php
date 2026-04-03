<?php

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('author cannot bulk delete posts without delete content permission', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    $posts = Post::factory()->count(2)->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->post(route('admin.posts.bulk-action', absolute: false), [
            'ids' => $posts->pluck('id')->all(),
            'action' => 'delete',
        ])
        ->assertForbidden();

    foreach ($posts as $post) {
        expect(Post::withTrashed()->find($post->id))->not->toBeNull();
        expect($post->fresh())->not->toBeNull();
    }
});

test('author can bulk move posts to draft without delete permission', function () {
    $author = User::factory()->create();
    $author->assignRole('Author');

    $post = Post::factory()->create([
        'user_id' => $author->id,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->actingAs($author)
        ->post(route('admin.posts.bulk-action', absolute: false), [
            'ids' => [$post->id],
            'action' => 'draft',
        ])
        ->assertRedirect();

    expect($post->fresh()->status)->toBe('draft');
});

test('admin can bulk delete posts', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $posts = Post::factory()->count(2)->create(['user_id' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('admin.posts.bulk-action', absolute: false), [
            'ids' => $posts->pluck('id')->all(),
            'action' => 'delete',
        ])
        ->assertRedirect();

    foreach ($posts as $post) {
        expect(Post::withTrashed()->find($post->id)->trashed())->toBeTrue();
    }
});
