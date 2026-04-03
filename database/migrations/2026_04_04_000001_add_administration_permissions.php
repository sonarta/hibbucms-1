<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Administration permissions for dashboard, menus, themes, plugins, roles (align with RoleAndPermissionSeeder).
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $definitions = [
            ['name' => 'view dashboard', 'group' => 'Administration'],
            ['name' => 'manage menus', 'group' => 'Administration'],
            ['name' => 'manage themes', 'group' => 'Administration'],
            ['name' => 'manage plugins', 'group' => 'Administration'],
            ['name' => 'manage roles', 'group' => 'Administration'],
        ];

        foreach ($definitions as $def) {
            Permission::firstOrCreate(
                ['name' => $def['name'], 'guard_name' => 'web'],
                ['group' => $def['group']]
            );
        }

        $names = array_column($definitions, 'name');

        $super = Role::query()->where('name', 'Super Admin')->where('guard_name', 'web')->first();
        if ($super) {
            $super->givePermissionTo(Permission::whereIn('name', $names)->get());
        }

        $admin = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first();
        if ($admin) {
            $admin->givePermissionTo(Permission::whereIn('name', $names)->get());
        }

        $editor = Role::query()->where('name', 'Editor')->where('guard_name', 'web')->first();
        if ($editor) {
            $editor->givePermissionTo(Permission::whereIn('name', ['view dashboard', 'manage menus'])->get());
        }

        $author = Role::query()->where('name', 'Author')->where('guard_name', 'web')->first();
        if ($author) {
            $p = Permission::where('name', 'view dashboard')->first();
            if ($p) {
                $author->givePermissionTo($p);
            }
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $names = ['view dashboard', 'manage menus', 'manage themes', 'manage plugins', 'manage roles'];

        foreach (Role::all() as $role) {
            $role->revokePermissionTo($names);
        }

        Permission::whereIn('name', $names)->delete();
    }
};
