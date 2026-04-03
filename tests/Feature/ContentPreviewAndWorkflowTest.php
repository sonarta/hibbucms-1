<?php

use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('allows authenticated users with view content to preview a draft post', function () {
    $user = User::factory()->create();
    $user->assignRole('Author');

    $post = Post::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
        'published_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('preview.post', $post))
        ->assertOk()
        ->assertSee(__('posts.preview_banner'), false);
});

it('allows preview via temporary signed URL without authentication', function () {
    $user = User::factory()->create();

    $post = Post::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $url = URL::temporarySignedRoute('preview.post', now()->addHour(), ['post' => $post->id]);

    $this->get($url)
        ->assertOk()
        ->assertSee(__('posts.preview_banner'), false);
});

it('forbids preview without auth or valid signature', function () {
    $post = Post::factory()->create(['status' => 'draft']);

    $this->get(route('preview.post', $post))->assertForbidden();
});

it('accepts pending_review status when updating a post', function () {
    $user = User::factory()->create();
    $user->assignRole('Editor');

    $post = Post::factory()->create([
        'user_id' => $user->id,
        'status' => 'draft',
    ]);

    $this->actingAs($user)
        ->put(route('admin.posts.update', $post), [
            'title' => 'Judul Review',
            'content' => '<p>Konten</p>',
            'excerpt' => '',
            'category_id' => $post->category_id,
            'status' => 'pending_review',
            'tags' => [],
        ])
        ->assertRedirect();

    expect($post->fresh()->status)->toBe('pending_review');
});
