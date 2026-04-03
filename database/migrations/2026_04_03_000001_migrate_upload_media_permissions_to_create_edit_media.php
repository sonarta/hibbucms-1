<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Replace legacy "upload media" with "create media" and "edit media" (aligned with MediaController).
     */
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $upload = Permission::where('name', 'upload media')->first();
        if (! $upload) {
            return;
        }

        $create = Permission::firstOrCreate(
            ['name' => 'create media', 'guard_name' => 'web'],
            ['group' => 'Media Management']
        );
        $edit = Permission::firstOrCreate(
            ['name' => 'edit media', 'guard_name' => 'web'],
            ['group' => 'Media Management']
        );

        $roles = Role::whereHas('permissions', fn ($q) => $q->where('permissions.id', $upload->id))->get();

        foreach ($roles as $role) {
            $role->givePermissionTo([$create, $edit]);
            $role->revokePermissionTo($upload);
        }

        $upload->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $upload = Permission::firstOrCreate(
            ['name' => 'upload media', 'guard_name' => 'web'],
            ['group' => 'Media Management']
        );

        $create = Permission::where('name', 'create media')->first();
        $edit = Permission::where('name', 'edit media')->first();

        if ($create && $edit) {
            $roles = Role::whereHas('permissions', fn ($q) => $q->whereIn('permissions.id', [$create->id, $edit->id]))->get();
            foreach ($roles as $role) {
                if ($role->hasPermissionTo($create) && $role->hasPermissionTo($edit)) {
                    $role->givePermissionTo($upload);
                    $role->revokePermissionTo([$create, $edit]);
                }
            }
        }

        $create?->delete();
        $edit?->delete();
    }
};
