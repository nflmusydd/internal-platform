<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Exports\RolesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolePermissionController extends Controller
{
    public function index()
    {
        return view('sysadmin.role-permissions.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30|unique:roles,name',
            'guard_name' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => $validated['guard_name'],
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_created')),
                'data' => $role,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role Store Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => ucfirst(__('general.failed_to_save'))
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $role = Role::where('ulid', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:30|unique:roles,name,' . $id . ',ulid',
            'guard_name' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $role->update([
                'name' => $validated['name'],
                'guard_name' => $validated['guard_name'],
                'updated_by' => auth()->id() ?? 1,
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_updated')),
                'data' => $role,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role Update Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => ucfirst(__('general.failed_to_update'))
            ], 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        $role = Role::where('ulid', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            $role->permissions()->detach();
            $role->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_deleted')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role Delete Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => ucfirst(__('general.failed_to_delete'))
            ], 500);
        }
    }

    public function syncPermissions(Request $request, string $id)
    {
        $role = Role::where('ulid', $id)->firstOrFail();

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        DB::beginTransaction();
        try {
            $role->syncPermissions($validated['permissions']);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('sysadmin/role-permissions/index.permissions_synced')),
                'data' => [
                    'role' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role SyncPermissions Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => ucfirst(__('sysadmin/role-permissions/index.failed_to_sync'))
            ], 500);
        }
    }

    public function getRoles()
    {
        $roles = Role::withCount('permissions', 'users')->orderBy('name')->get();
        return response()->json(['data' => $roles]);
    }

    public function getRolePermissions(string $id)
    {
        $role = Role::with('permissions')->where('ulid', $id)->firstOrFail();
        return response()->json([
            'data' => [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function exportRoles(Request $request)
    {
        $roles = Role::withCount('permissions', 'users');

        if ($request->filled('name')) {
            $roles->where('name', 'LIKE', '%' . $request->name . '%');
        }
        if ($request->filled('guard')) {
            $roles->where('guard_name', $request->guard);
        }
        if ($request->filled('hasPermissions')) {
            if ($request->hasPermissions === '1') {
                $roles->has('permissions');
            } else {
                $roles->doesntHave('permissions');
            }
        }

        $roles = $roles->orderBy('name')->get();
        return (new RolesExport($roles))->download('roles');
    }
}
