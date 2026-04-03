<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User management
            ['name' => 'view users', 'group' => 'User Management'],
            ['name' => 'create users', 'group' => 'User Management'],
            ['name' => 'edit users', 'group' => 'User Management'],
            ['name' => 'delete users', 'group' => 'User Management'],

            // Content management
            ['name' => 'view content', 'group' => 'Content Management'],
            ['name' => 'create content', 'group' => 'Content Management'],
            ['name' => 'edit content', 'group' => 'Content Management'],
            ['name' => 'delete content', 'group' => 'Content Management'],
            ['name' => 'publish content', 'group' => 'Content Management'],

            // Category management
            ['name' => 'view categories', 'group' => 'Category Management'],
            ['name' => 'create categories', 'group' => 'Category Management'],
            ['name' => 'edit categories', 'group' => 'Category Management'],
            ['name' => 'delete categories', 'group' => 'Category Management'],

            // Media management (aligned with Admin\MediaController middleware)
            ['name' => 'view media', 'group' => 'Media Management'],
            ['name' => 'create media', 'group' => 'Media Management'],
            ['name' => 'edit media', 'group' => 'Media Management'],
            ['name' => 'delete media', 'group' => 'Media Management'],

            // Settings
            ['name' => 'manage settings', 'group' => 'Settings'],

            // Administration (dashboard, menus, themes, plugins, roles)
            ['name' => 'view dashboard', 'group' => 'Administration'],
            ['name' => 'manage menus', 'group' => 'Administration'],
            ['name' => 'manage themes', 'group' => 'Administration'],
            ['name' => 'manage plugins', 'group' => 'Administration'],
            ['name' => 'manage roles', 'group' => 'Administration'],

            // Security & compliance
            ['name' => 'view audit log', 'group' => 'Security'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['group' => $permission['group'] ?? null]
            );
        }

        // Create roles and assign permissions
        $role = Role::create(['name' => 'Super Admin']);
        $role->givePermissionTo(Permission::all());

        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo([
            'view users', 'create users', 'edit users',
            'view content', 'create content', 'edit content', 'delete content', 'publish content',
            'view categories', 'create categories', 'edit categories', 'delete categories',
            'view media', 'create media', 'edit media', 'delete media',
            'manage settings',
            'view dashboard', 'manage menus', 'manage themes', 'manage plugins', 'manage roles',
            'view audit log',
        ]);

        $role = Role::create(['name' => 'Editor']);
        $role->givePermissionTo([
            'view content', 'create content', 'edit content', 'publish content',
            'view categories',
            'view media', 'create media', 'edit media',
            'view dashboard', 'manage menus',
        ]);

        $role = Role::create(['name' => 'Author']);
        $role->givePermissionTo([
            'view content', 'create content', 'edit content',
            'view categories',
            'view media', 'create media', 'edit media',
            'view dashboard',
        ]);
    }
}
