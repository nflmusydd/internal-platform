<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Default roles, their permission bundles and the demo users.
 *
 * Single source of truth shared by RolePermissionSeeder and the permission:sync
 * command so the two can never drift apart. This is a real-world starter setup:
 * an operations role manages the work calendar, a manager/lead role also sees
 * reports, and developers get the development tools.
 */
class DefaultRolePermissions
{
    /**
     * Base roles (name + guard), derived from the bundle keys.
     *
     * @return array<int, array{name: string, guard_name: string}>
     */
    public static function roles(): array
    {
        return array_map(
            fn (string $name) => ['name' => $name, 'guard_name' => RoutePermission::guard()],
            array_keys(static::bundles())
        );
    }

    /**
     * Permission bundles per role.
     *
     * A prefix ending with `%` is matched with SQL LIKE; `*` means every
     * permission of the guard.
     *
     * @return array<string, array<int, string>>
     */
    public static function bundles(): array
    {
        return [
            'SUPER ADMIN' => ['*'],
            'DEVELOPER' => ['development.components.%', 'development.command.%'],
            'MANAGER' => ['work_calendar.%', 'reports.%'],
            'TEAM LEADER' => ['work_calendar.%', 'reports.%'],
            'EMPLOYEE' => ['work_calendar.%'],
        ];
    }

    /**
     * Demo users (one per role) created by the seeder.
     *
     * @return array<int, array{email: string, name: string, role: string}>
     */
    public static function users(): array
    {
        return [
            ['email' => 'otoriterman@internal.com', 'name' => 'MANUSIA OTORITER', 'role' => 'SUPER ADMIN'],
            ['email' => 'developer@internal.com', 'name' => 'DEVELOPER DEMO', 'role' => 'DEVELOPER'],
            ['email' => 'manager@internal.com', 'name' => 'MANAGER MAGER', 'role' => 'MANAGER'],
            ['email' => 'teamleader@internal.com', 'name' => 'TEAM LEADER DEMO', 'role' => 'TEAM LEADER'],
            ['email' => 'employee@internal.com', 'name' => 'EMPLOYEE DEMO', 'role' => 'EMPLOYEE'],
        ];
    }

    /**
     * Resolve a bundle (list of prefixes) into concrete permission names.
     *
     * @param  array<int, string>  $prefixes
     * @return array<int, string>
     */
    public static function resolve(array $prefixes): array
    {
        if ($prefixes === []) {
            return [];
        }

        $guard = RoutePermission::guard();

        if (in_array('*', $prefixes, true)) {
            return Permission::where('guard_name', $guard)->pluck('name')->all();
        }

        return Permission::where('guard_name', $guard)
            ->where(function ($query) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('name', 'LIKE', $prefix);
                }
            })
            ->pluck('name')
            ->all();
    }

    /**
     * Create any base role that does not exist yet.
     */
    public static function ensureRoles(): void
    {
        $existing = DB::table('roles')->pluck('id', 'name');

        foreach (static::roles() as $role) {
            if (isset($existing[$role['name']])) {
                continue;
            }

            DB::table('roles')->insert([
                'ulid' => (string) Str::ulid(),
                'name' => $role['name'],
                'guard_name' => $role['guard_name'],
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 1,
                'updated_by' => 1,
            ]);
        }
    }

    /**
     * Apply the default permission bundles to every role.
     */
    public static function applyBundles(): void
    {
        foreach (static::bundles() as $roleName => $prefixes) {
            Role::where('name', $roleName)
                ->first()
                ?->syncPermissions(static::resolve($prefixes));
        }
    }

    /**
     * Create any missing demo user and make sure it holds its role.
     */
    public static function ensureUsers(): void
    {
        foreach (static::users() as $user) {
            $model = User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => bcrypt('test1234'),
                    'is_active' => 1,
                    'created_by' => 1,
                    'updated_by' => 1,
                ],
            );

            $model->syncRoles([$user['role']]);
        }
    }
}
