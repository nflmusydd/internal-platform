<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Support\RoutePermission;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoutePermissionCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_names_are_non_empty_and_exclude_the_allowlist(): void
    {
        $names = RoutePermission::routeNames();

        $this->assertNotEmpty($names);

        foreach (RoutePermission::excludedNames() as $excluded) {
            $this->assertNotContains($excluded, $names);
        }
    }

    public function test_vendor_routes_are_never_turned_into_permissions(): void
    {
        $this->assertNotContains('storage.local', RoutePermission::routeNames());
        $this->assertNotContains('storage.local.upload', RoutePermission::routeNames());
    }

    public function test_seeder_creates_a_permission_for_every_derived_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $missing = array_values(array_filter(
            RoutePermission::permissions(),
            fn (string $name) => ! Permission::where('name', $name)
                ->where('guard_name', 'web')
                ->exists()
        ));

        $this->assertSame([], $missing, 'Derived permissions without a row: ' . implode(', ', $missing));
    }

    public function test_super_admin_owns_every_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = Role::where('name', 'SUPER ADMIN')->firstOrFail();

        $this->assertSame(
            Permission::where('guard_name', 'web')->count(),
            $superAdmin->permissions()->count()
        );
    }

    public function test_every_route_action_is_explicitly_classified(): void
    {
        $ignore = RoutePermission::ignoreSegments();
        $unclassified = [];

        foreach (RoutePermission::routeNames() as $routeName) {
            $segments = array_values(array_filter(
                explode('.', $routeName),
                fn (string $segment) => ! in_array($segment, $ignore, true)
            ));

            if ($segments === []) {
                continue;
            }

            $action = (string) array_pop($segments);

            if (! RoutePermission::isActionClassified($action)) {
                $unclassified[$routeName] = $action;
            }
        }

        $this->assertSame(
            [],
            $unclassified,
            'Routes with an unclassified action: ' . json_encode($unclassified)
        );
    }

    #[DataProvider('conventionProvider')]
    public function test_naming_convention_maps_route_to_permission(?string $routeName, ?string $expected): void
    {
        $this->assertSame($expected, RoutePermission::permissionFor($routeName));
    }

    public static function conventionProvider(): array
    {
        return [
            'anonymous route' => [null, null],
            'empty route' => ['', null],
            'index -> view' => ['sysadmin.users.index', 'sysadmin.users.view'],
            'show -> view' => ['sysadmin.users.show', 'sysadmin.users.view'],
            'create -> create' => ['sysadmin.users.create', 'sysadmin.users.create'],
            'store -> create' => ['sysadmin.users.store', 'sysadmin.users.create'],
            'edit -> edit' => ['sysadmin.users.edit', 'sysadmin.users.edit'],
            'update -> edit' => ['sysadmin.users.update', 'sysadmin.users.edit'],
            'destroy -> delete' => ['sysadmin.users.destroy', 'sysadmin.users.delete'],
            'ajax read (noun) -> view' => ['sysadmin.users.ajax.users', 'sysadmin.users.view'],
            'ajax read (all) -> view' => ['sysadmin.menus.ajax.all', 'sysadmin.menus.view'],
            'ajax store -> create' => ['sysadmin.users.ajax.store', 'sysadmin.users.create'],
            'ajax update -> edit' => ['sysadmin.users.ajax.update', 'sysadmin.users.edit'],
            'ajax destroy -> delete' => ['sysadmin.users.ajax.destroy', 'sysadmin.users.delete'],
            'export -> export' => ['sysadmin.users.ajax.export_users', 'sysadmin.users.export'],
            'sync -> edit' => ['sysadmin.role_permissions.ajax.sync_permissions', 'sysadmin.role_permissions.edit'],
            'sync roles -> edit' => ['sysadmin.user_permissions.ajax.sync_roles', 'sysadmin.user_permissions.edit'],
            'permission users -> view' => ['sysadmin.permissions.ajax.permission_users', 'sysadmin.permissions.view'],
            'single segment' => ['locale', 'view'],
            'custom action falls back' => ['reports.download', 'reports.view'],
        ];
    }

    public function test_permission_set_is_deduplicated_and_sorted(): void
    {
        $permissions = RoutePermission::permissions();

        $this->assertSame(array_values(array_unique($permissions)), $permissions);
        $sorted = $permissions;
        sort($sorted);
        $this->assertSame($sorted, $permissions);

        // Page and ajax list of the same module must collapse into one permission.
        $this->assertContains('sysadmin.users.view', $permissions);
        $this->assertNotContains('sysadmin.users.index', $permissions);
        $this->assertNotContains('sysadmin.users.ajax.users', $permissions);
    }
}
