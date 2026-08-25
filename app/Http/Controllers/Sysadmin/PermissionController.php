<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Exports\PermissionsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    public function index()
    {
        return view('sysadmin.role-permissions.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:30|unique:permissions,name',
            'guard_name' => 'required|string|max:20',
        ]);

        DB::beginTransaction();
        try {
            $permission = Permission::create([
                'name' => trim($validated['name']),
                'guard_name' => trim($validated['guard_name']),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_created')),
                'data' => $permission,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission Store Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => ucfirst(__('general.failed_to_save'))
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $permission = Permission::where('ulid', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:30|unique:permissions,name,' . $id . ',ulid',
            'guard_name' => 'required|string|max:20',
        ]);

        if ($permission->name === $validated['name'] && $permission->guard_name === $validated['guard_name']) {
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.no_changes')),
                'no_change' => true,
            ]);
        }

        DB::beginTransaction();
        try {
            $permission->update([
                'name' => trim($validated['name']),
                'guard_name' => trim($validated['guard_name']),
                'updated_by' => auth()->id(),
            ]);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_updated')),
                'data' => $permission,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission Update Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false, 
                'message' => ucfirst(__('general.failed_to_update'))
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        $permission = Permission::where('ulid', $id)->firstOrFail();

        DB::beginTransaction();
        try {
            $permission->roles()->detach();
            $permission->delete();

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => ucfirst(__('general.successfully_deleted')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission Delete Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('general.failed_to_delete'))
            ], 500);
        }
    }

    public function getAll()
    {
        $permissions = Permission::withCount('roles')->orderBy('name')->get();
        return response()->json(['data' => $permissions]);
    }

    public function exportPermissions(Request $request)
    {
        $permissions = Permission::withCount('roles');

        if ($request->filled('name')) {
            $permissions->where('name', 'LIKE', '%' . $request->name . '%');
        }
        if ($request->filled('guard')) {
            $permissions->where('guard_name', $request->guard);
        }
        if ($request->filled('inUse')) {
            if ($request->inUse === '1') {
                $permissions->has('roles');
            } else {
                $permissions->doesntHave('roles');
            }
        }

        $permissions = $permissions->orderBy('name')->get();
        return (new PermissionsExport($permissions))->download('permissions');
    }
}
