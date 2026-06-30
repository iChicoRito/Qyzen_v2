<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionsRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

// F6: admin permission management — bulk create, edit, delete.
class PermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::orderBy('permission_string')->paginate(30);

        return view('admin.permissions.index', compact('permissions'));
    }

    public function store(StorePermissionsRequest $request): RedirectResponse
    {
        $this->authorize('create', Permission::class);

        foreach ($request->validated()['permissions'] as $p) {
            $string = $p['resource'].':'.$p['action'];
            Permission::create([
                'resource' => $p['resource'],
                'action'   => $p['action'],
                'permission_string' => $string,
                'name'     => $p['name'] ?? $string,
                'module'   => $p['module'] ?? $p['resource'],   // module/description are NOT NULL in schema
                'description' => $p['description'] ?? $string,
                'is_active' => $p['is_active'],
            ]);
        }

        return redirect()->route('admin.permissions.index')->with('status', 'Permissions created.');
    }

    public function show(Permission $permission): View
    {
        $this->authorize('view', $permission);

        return view('admin.permissions.show', compact('permission'));
    }

    public function edit(Permission $permission): View
    {
        $this->authorize('update', $permission);

        return view('admin.permissions.edit', compact('permission'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->authorize('update', $permission);

        $data = $request->validated();
        $data['permission_string'] = $data['resource'].':'.$data['action'];
        $data['module'] = $data['module'] ?? $data['resource'];        // both NOT NULL in schema
        $data['description'] = $data['description'] ?? $data['permission_string'];
        $data['name'] = $data['name'] ?? $data['permission_string'];
        $permission->update($data);

        return redirect()->route('admin.permissions.index')->with('status', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->authorize('delete', $permission);

        $permission->roles()->detach();
        $permission->delete();

        return redirect()->route('admin.permissions.index')->with('status', 'Permission deleted.');
    }
}
