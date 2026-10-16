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
    private const DATETIME_FORMAT = 'd/m/Y, H:i';

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
     * Every assignable role for the modal, flagging the ones this user already
     * has and when/by whom each was assigned.
     */
    public function getRolesForUser(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        $assigned = $user->roles()
            ->withPivot(['assigned_by', 'assigned_at'])
            ->get()
            ->keyBy('id');
        $names = $this->assignerNames($assigned->pluck('pivot.assigned_by'));

        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(function ($role) use ($assigned, $names) {
                $pivot = $assigned->get($role->id)?->pivot;

                return [
                    'ulid' => $role->ulid,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'assigned' => $pivot !== null,
                    'assigned_by_name' => $pivot ? ($names[$pivot->assigned_by] ?? null) : null,
                    'assigned_at' => $pivot?->assigned_at?->format(self::DATETIME_FORMAT),
                ];
            });

        return response()->json([
            'data' => [
                'user' => ['ulid' => $user->ulid, 'name' => $user->name],
                'roles' => $roles,
            ],
        ]);
    }

    /**
     * Every web permission for the modal. Permissions the user already holds
     * through a role are returned too (flagged `via_role`) so the modal can show
     * them disabled with the reason instead of silently hiding them.
     */
    public function getPermissionsForUser(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        $direct = $user->permissions()
            ->withPivot(['assigned_by', 'assigned_at'])
            ->get()
            ->keyBy('id');
        $names = $this->assignerNames($direct->pluck('pivot.assigned_by'));

        $viaRoleMap = [];
        foreach ($user->roles()->with('permissions')->get() as $role) {
            foreach ($role->permissions as $permission) {
                $viaRoleMap[$permission->name][] = $role->name;
            }
        }

        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(function ($permission) use ($direct, $names, $viaRoleMap) {
                $pivot = $direct->get($permission->id)?->pivot;
                $viaRoles = array_values(array_unique($viaRoleMap[$permission->name] ?? []));

                return [
                    'ulid' => $permission->ulid,
                    'name' => $permission->name,
                    'guard_name' => $permission->guard_name,
                    'assigned' => $pivot !== null,
                    'via_role' => $viaRoles !== [],
                    'via_roles' => $viaRoles,
                    'assigned_by_name' => $pivot ? ($names[$pivot->assigned_by] ?? null) : null,
                    'assigned_at' => $pivot?->assigned_at?->format(self::DATETIME_FORMAT),
                ];
            })
            // Selectable rows first; role-inherited (disabled) ones grouped at the end.
            ->sortBy([['via_role', 'asc'], ['name', 'asc']])
            ->values();

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

        $roles = $user->roles()
            ->withPivot(['assigned_by', 'assigned_at'])
            ->orderBy('name')
            ->get();
        $names = $this->assignerNames($roles->pluck('pivot.assigned_by'));

        return response()->json([
            'data' => [
                'roles' => $roles->map(fn ($role) => [
                    'name' => $role->name,
                    'meta' => $this->assignmentMeta(
                        $names[$role->pivot->assigned_by] ?? null,
                        $role->pivot->assigned_at,
                    ),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Direct permission names the user currently holds — the badge list modal source.
     */
    public function getUserPermissions(string $id)
    {
        $user = User::where('ulid', $id)->firstOrFail();

        $permissions = $user->permissions()
            ->withPivot(['assigned_by', 'assigned_at'])
            ->orderBy('name')
            ->get();
        $names = $this->assignerNames($permissions->pluck('pivot.assigned_by'));

        return response()->json([
            'data' => [
                'permissions' => $permissions->map(fn ($permission) => [
                    'name' => $permission->name,
                    'meta' => $this->assignmentMeta(
                        $names[$permission->pivot->assigned_by] ?? null,
                        $permission->pivot->assigned_at,
                    ),
                ])->values()->all(),
            ],
        ]);
    }

    /**
     * Replace the user's roles with the submitted selection.
     *
     * Only the difference is attached/detached so rows that stay keep their
     * assignment metadata. Direct permissions already covered by the resulting
     * role set are revoked first, so no permission stays redundant.
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
            $currentNames = $user->roles()->pluck('name')->sort()->values()->all();
            $newNames = $roleModels->pluck('name')->sort()->values()->all();

            if ($currentNames === $newNames) {
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
                $redundantIds = Permission::where('guard_name', 'web')
                    ->whereIn('name', $redundant)
                    ->pluck('id')
                    ->all();

                $user->permissions()->detach($redundantIds);
            }

            $currentIds = $user->roles()->pluck('roles.id')->all();
            $newIds = $roleModels->pluck('id')->all();

            $removeIds = array_values(array_diff($currentIds, $newIds));
            $addIds = array_values(array_diff($newIds, $currentIds));

            if ($removeIds) {
                $user->roles()->detach($removeIds);
            }
            if ($addIds) {
                $user->roles()->attach($addIds);
            }

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

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
     * Replace the user's direct permissions with the submitted selection.
     *
     * Only the difference is attached/detached: rows that stay keep their
     * assignment metadata, only newly granted ones get a fresh `assigned_by`/`assigned_at`.
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
            ->get();
        $names = $permissions->pluck('name')->all();

        // Cannot add a permission the user already has through their roles. Permissions
        // that are already held directly (and happen to also be role-granted) are not a
        // conflict — they are simply kept, so re-saving does not fail.
        $viaRoles = $user->getPermissionsViaRoles()->pluck('name')->all();
        $currentDirect = $user->permissions()->pluck('name')->all();
        $conflict = array_values(array_diff(array_intersect($names, $viaRoles), $currentDirect));

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
            $current = $user->permissions()->get();
            $currentNames = $current->pluck('name')->sort()->values()->all();
            $newNames = collect($names)->sort()->values()->all();

            if ($currentNames === $newNames) {
                DB::rollBack();

                return response()->json([
                    'success' => true,
                    'message' => ucfirst(__('general.no_changes')),
                    'no_change' => true,
                ]);
            }

            $currentIds = $current->pluck('id')->all();
            $newIds = $permissions->pluck('id')->all();

            $removeIds = array_values(array_diff($currentIds, $newIds));
            $addIds = array_values(array_diff($newIds, $currentIds));

            if ($removeIds) {
                $user->permissions()->detach($removeIds);
            }
            if ($addIds) {
                $user->permissions()->attach($addIds);
            }

            $user->unsetRelation('permissions');

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

    /**
     * Resolve the distinct names of the users referenced by a set of assigner ids.
     *
     * @return array<int, string>
     */
    private function assignerNames(iterable $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values()->all();

        if ($ids === []) {
            return [];
        }

        return User::whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /**
     * Human readable "assigned by X at Y" line used by the badge list modal.
     */
    private function assignmentMeta(?string $assignedByName, ?\DateTimeInterface $assignedAt): ?string
    {
        if ($assignedAt === null) {
            return null;
        }

        return __('sysadmin/user-permissions/index.assignment_meta', [
            'name' => $assignedByName ?: __('sysadmin/user-permissions/index.assigned_unknown'),
            'date' => $assignedAt->format(self::DATETIME_FORMAT),
        ]);
    }
}
