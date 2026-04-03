<?php

use App\Models\Media;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('generates image variants on upload', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $file = UploadedFile::fake()->image('photo.jpg', 1200, 800);

    $media = Media::upload($file, 'uploads', 'public');

    expect($media->variants)->toBeArray();
    expect($media->variants)->toHaveKeys(['thumb', 'medium', 'large']);

    foreach (['thumb', 'medium', 'large'] as $preset) {
        Storage::disk('public')->assertExists($media->variants[$preset]['path']);
    }

    expect($media->variant_urls)->toHaveKeys(['thumb', 'medium', 'large']);
})->skip(fn () => ! extension_loaded('gd'), 'Ekstensi PHP gd diperlukan untuk tes varian media (Intervention Image).');

it('deletes variant files when media is deleted', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

    $media = Media::upload(UploadedFile::fake()->image('x.png', 500, 400), 'uploads', 'public');

    $paths = array_column($media->variants, 'path');
    $original = $media->path;

    $media->delete();

    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
    Storage::disk('public')->assertMissing($original);
})->skip(fn () => ! extension_loaded('gd'), 'Ekstensi PHP gd diperlukan untuk tes varian media (Intervention Image).');
