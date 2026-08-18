<?php

namespace App\Http\Controllers\Sysadmin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
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
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $permission = Permission::create([
                'name' => $validated['name'],
                'guard_name' => $validated['guard_name'],
                'created_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('general.successfully_created'),
                'data' => $permission,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission Store Error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('general.failed_to_save')], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id . ',id',
            'guard_name' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $permission->update([
                'name' => $validated['name'],
                'guard_name' => $validated['guard_name'],
                'updated_by' => auth()->id() ?? 1,
            ]);

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('general.successfully_updated'),
                'data' => $permission,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission Update Error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('general.failed_to_update')], 500);
        }
    }

    public function destroy(string $id)
    {
        $permission = Permission::findOrFail($id);

        DB::beginTransaction();
        try {
            $permission->roles()->detach();
            $permission->delete();

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => __('general.successfully_deleted'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Permission Delete Error', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => __('general.failed_to_delete')], 500);
        }
    }

    public function getAll()
    {
        $permissions = Permission::orderBy('name')->get();
        return response()->json(['data' => $permissions]);
    }
}
