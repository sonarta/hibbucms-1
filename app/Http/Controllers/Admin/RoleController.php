<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage roles');
    }

    public function index()
    {
        $roles = Role::with('permissions')
            ->withCount('users')
            ->when(request('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'filters' => request()->only('search'),
        ]);
    }

    public function create()
    {
        $permissions = Permission::orderBy('group')->get();

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
        ]);

        $role->permissions()->sync($validated['permissions']);

        return redirect()->route('admin.roles.index')
            ->with('message', 'Peran berhasil dibuat');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::orderBy('group')->get();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role->load('permissions'),
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
        ]);

        $role->permissions()->sync($validated['permissions']);

        return redirect()->route('admin.roles.index')
            ->with('message', 'Peran berhasil diperbarui');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('message', 'Peran berhasil dihapus');
    }
}
