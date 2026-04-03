<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('users without media permissions cannot view the media library', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.media.index', absolute: false))
        ->assertForbidden();
});

test('users with view media permission can open the media library', function () {
    $user = User::factory()->create();
    $user->assignRole('Editor');

    $this->actingAs($user)
        ->get(route('admin.media.index', absolute: false))
        ->assertOk();
});
