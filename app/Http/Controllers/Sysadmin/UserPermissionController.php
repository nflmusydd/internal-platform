<?php

namespace App\Http\Controllers\Sysadmin;

use App\Exports\UserPermissionsExport;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionController extends Controller
{
    public function index()
    {
        return view('sysadmin.user-permissions.index');
    }

    /**
     * Users with role & direct permission counts — the data source of the table.
     */
    public function getUsers()
    {
        $users = User::withCount(['roles', 'permissions'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $users]);
    }

    /**
     * Every assignable role for the modal, flagging the ones this user already has.
     */
    public function getRolesForUser(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();
        $assigned = $user->roles()->pluck('roles.id')->all();

        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(fn ($role) => [
                'ulid' => $role->ulid,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'assigned' => in_array($role->id, $assigned, true),
            ]);

        return response()->json([
            'data' => [
                'user' => ['ulid' => $user->ulid, 'name' => $user->name],
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Permissions the user may hold directly, i.e. every web permission that is NOT
     * already granted through one of their roles (adding those would be redundant).
     */
    public function getPermissionsForUser(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        $assigned = $user->getDirectPermissions()->pluck('id')->all();
        $viaRoles = $user->getPermissionsViaRoles()->pluck('id')->all();

        $permissions = Permission::where('guard_name', 'web')
            ->whereNotIn('id', $viaRoles)
            ->orderBy('name')
            ->get()
            ->map(fn ($permission) => [
                'ulid' => $permission->ulid,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'assigned' => in_array($permission->id, $assigned, true),
            ]);

        return response()->json([
            'data' => [
                'user' => ['ulid' => $user->ulid, 'name' => $user->name],
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Role names the user currently holds — the data source of the badge list modal.
     */
    public function getUserRoles(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        return response()->json([
            'data' => [
                'roles' => $user->roles()->orderBy('name')->pluck('name')->values()->all(),
            ],
        ]);
    }

    /**
     * Direct permission names the user currently holds — the badge list modal source.
     */
    public function getUserPermissions(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        return response()->json([
            'data' => [
                'permissions' => $user->getDirectPermissions()->sortBy('name')->pluck('name')->values()->all(),
            ],
        ]);
    }

    /**
     * Replace the user's roles with the submitted selection.
     *
     * Direct permissions that are already covered by the resulting role set are
     * revoked first, then the roles are assigned, so no permission stays redundant.
     */
    public function syncRoles(Request $request, string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        $validated = $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        // Only the web guard is assignable; keeps role names unambiguous across guards.
        $roleModels = Role::where('guard_name', 'web')
            ->whereIn('name', $validated['roles'] ?? [])
            ->get();

        DB::beginTransaction();
        try {
            $current = $user->roles()->pluck('name')->sort()->values()->all();
            $new = $roleModels->pluck('name')->sort()->values()->all();

            if ($current === $new) {
                DB::rollBack();

                return response()->json([
                    'success' => true,
                    'message' => ucfirst(__('general.no_changes')),
                    'no_change' => true,
                ]);
            }

            // Direct permissions already covered by the resulting role set are
            // dropped first, then the roles are assigned (avoids redundancy).
            $roleModels->load('permissions');
            $redundant = $user->getDirectPermissions()
                ->pluck('name')
                ->intersect($roleModels->flatMap(fn ($role) => $role->permissions->pluck('name')))
                ->values()
                ->all();

            if ($redundant) {
                $user->revokePermissionTo($redundant);
            }

            $user->syncRoles($new);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst(__('sysadmin/user-permissions/index.roles_synced', ['name' => e($user->name)])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserPermission SyncRoles Error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => ucfirst(__('sysadmin/user-permissions/index.failed_to_sync')),
            ], 500);
        }
    }

    /**
     * Replace the user's direct permissions with the submitted selection
     * (inserts the new ones, deletes the ones unchecked).
     */
    public function syncPermissions(Request $request, string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $permissions = Permission::where('guard_name', 'web')
            ->whereIn('name', collect($validated['permissions'] ?? [])->unique()->values()->all())
            ->pluck('name')
            ->all();

        // Cannot add a permission the user already has through their roles.
        $viaRoles = $user->getPermissionsViaRoles()->pluck('name')->all();
        $conflict = array_values(array_intersect($permissions, $viaRoles));

        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => ucfirst(__('sysadmin/user-permissions/index.permission_in_role', [
                    'name' => e($user->name),
                    'permissions' => implode(', ', $conflict),
                ])),
                'errors' => ['permissions' => $conflict],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $current = $user->getDirectPermissions()->pluck('name')->sort()->values()->all();
            $new = collect($permissions)->sort()->values()->all();

            if ($current === $new) {
                DB::rollBack();

                return response()->json([
                    'success' => true,
                    'message' => ucfirst(__('general.no_changes')),
                    'no_change' => true,
                ]);
            }

            $user->syncPermissions($permissions);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst(__('sysadmin/user-permissions/index.permissions_synced', ['name' => e($user->name)])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('UserPermission SyncPermissions Error', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => ucfirst(__('sysadmin/user-permissions/index.failed_to_sync_permissions')),
            ], 500);
        }
    }

    /**
     * Export the filtered user list as XLSX.
     */
    public function exportUsers(Request $request)
    {
        $users = User::withCount(['roles', 'permissions']);

        if ($request->filled('name')) {
            $users->where('name', 'LIKE', '%' . $request->name . '%');
        }
        if ($request->filled('email')) {
            $users->where('email', 'LIKE', '%' . $request->email . '%');
        }
        if ($request->filled('status')) {
            $users->where('is_active', $request->status);
        }
        if ($request->filled('hasRoles')) {
            if ($request->hasRoles === '1') {
                $users->has('roles');
            } else {
                $users->doesntHave('roles');
            }
        }
        if ($request->filled('hasPermissions')) {
            if ($request->hasPermissions === '1') {
                $users->has('permissions');
            } else {
                $users->doesntHave('permissions');
            }
        }

        $users = $users->orderBy('name')->get();

        return (new UserPermissionsExport($users))->download('user-permissions');
    }
}
