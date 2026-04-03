<?php

use App\Models\Theme;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('fires theme.before_activate and theme.after_activate when activating a theme', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    Theme::query()->delete();

    $previousActive = Theme::create([
        'name' => 'Old Active',
        'slug' => 'old-active',
        'folder_name' => 'old-active',
        'version' => '1.0.0',
        'is_active' => true,
    ]);

    $next = Theme::create([
        'name' => 'Next Theme',
        'slug' => 'next-theme',
        'folder_name' => 'next-theme',
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    $beforeId = null;
    $afterActive = null;

    add_action('theme.before_activate', function ($theme) use (&$beforeId, $next) {
        $beforeId = $theme->id;
        expect($theme->id)->toBe($next->id);
    });

    add_action('theme.after_activate', function ($theme) use (&$afterActive) {
        $afterActive = $theme->is_active;
    });

    $this->actingAs($user)
        ->post(route('admin.themes.activate', $next))
        ->assertRedirect();

    expect($beforeId)->toBe($next->id);
    expect($afterActive)->toBeTrue();
    expect($next->fresh()->is_active)->toBeTrue();
    expect($previousActive->fresh()->is_active)->toBeFalse();
});

it('fires theme.before_delete and theme.after_delete when deleting an inactive theme', function () {
    $user = User::factory()->create();
    $user->assignRole('Admin');

    Theme::query()->delete();

    $toDelete = Theme::create([
        'name' => 'Trash Theme',
        'slug' => 'trash-theme',
        'folder_name' => 'trash-theme',
        'version' => '1.0.0',
        'is_active' => false,
    ]);

    $beforeFolder = null;
    $afterSnapshot = null;

    add_action('theme.before_delete', function ($theme) use (&$beforeFolder, $toDelete) {
        $beforeFolder = $theme->folder_name;
        expect($theme->id)->toBe($toDelete->id);
    });

    add_action('theme.after_delete', function (array $snapshot) use (&$afterSnapshot) {
        $afterSnapshot = $snapshot;
    });

    $this->actingAs($user)
        ->delete(route('admin.themes.destroy', $toDelete))
        ->assertRedirect();

    expect($beforeFolder)->toBe('trash-theme');
    expect($afterSnapshot)->toMatchArray([
        'id' => $toDelete->id,
        'folder_name' => 'trash-theme',
    ]);
    expect(Theme::query()->whereKey($toDelete->id)->exists())->toBeFalse();
});
