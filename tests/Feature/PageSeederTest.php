<?php

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\UserSeeder;

test('page seeder runs when admin role exists', function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(UserSeeder::class);
    $this->seed(PageSeeder::class);

    expect(Page::query()->count())->toBe(3);
});
