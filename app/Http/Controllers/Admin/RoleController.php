<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Support\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// F5: admin role management + permission assignment (all-or-nothing replace).
class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        $query = Role::withCount('permissions');
        TableQuery::search($query, $request->query('search'), ['name', 'description']);
        TableQuery::filters($query, $request, ['status' => 'is_active']);
        TableQuery::sort($query, $request, [
            'name' => 'name',
            'description' => 'description',
            'permissions' => 'permissions_count',
            'system' => 'is_system',
            'status' => 'is_active',
        ], 'name');

        $roles = $query->paginate(TableQuery::perPage($request))->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', ['permissions' => Permission::orderBy('permission_string')->get()]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $data = $request->validated();
        $role = Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'],
            'is_system' => $data['is_system'],
        ]);
        $role->permissions()->sync($data['permission_ids'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', "Role {$role->name} created.");
    }

    public function show(Role $role): View
    {
        $this->authorize('view', $role);

        $role->load('permissions');

        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('permissions:id');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => Permission::orderBy('permission_string')->get(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $data = $request->validated();
        $role->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'],
            'is_system' => $data['is_system'],
        ]);
        // All-or-nothing: sync replaces the whole role↔permission set.
        $role->permissions()->sync($data['permission_ids'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', "Role {$role->name} updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }
}
