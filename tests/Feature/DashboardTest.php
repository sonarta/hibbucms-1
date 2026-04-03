<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

test('guests are redirected to the login page', function () {
    $this->get(route('admin.dashboard', absolute: false))->assertRedirect(route('login', absolute: false));
});

test('authenticated users can visit the dashboard', function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Author');

    $this->actingAs($user);

    $this->get(route('admin.dashboard', absolute: false))->assertOk();
});