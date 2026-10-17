<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use App\Support\MenuSynchronizer;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        MenuSynchronizer::sync();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_super_admin_can_open_every_module_page(): void
    {
        $admin = $this->user('otoriterman@internal.com');

        $this->actingAs($admin)->get('/sysadmin/users')->assertOk();
        $this->actingAs($admin)->get('/sysadmin/role-permissions')->assertOk();
        $this->actingAs($admin)->get('/sysadmin/user-permissions')->assertOk();
        $this->actingAs($admin)->get('/development/components')->assertOk();
    }

    public function test_developer_can_only_open_the_granted_module(): void
    {
        $developer = $this->user('developer@internal.com');

        $this->actingAs($developer)->get('/development/components')->assertOk();

        $this->actingAs($developer)->get('/sysadmin/users')->assertForbidden();
        $this->actingAs($developer)->get('/sysadmin/role-permissions')->assertForbidden();
        $this->actingAs($developer)->get('/sysadmin/user-permissions')->assertForbidden();
    }

    public function test_manually_typed_url_is_blocked(): void
    {
        $developer = $this->user('developer@internal.com');

        // Bypass the sidebar by typing the URL of a menu that is not shown.
        $this->actingAs($developer)->get('/sysadmin/menus')->assertForbidden();
    }

    public function test_sidebar_only_shows_menus_the_user_can_access(): void
    {
        $this->actingAs($this->user('developer@internal.com'))
            ->get('/')
            ->assertOk()
            ->assertSee('/development/components')
            ->assertDontSee('/sysadmin/users');

        $this->actingAs($this->user('otoriterman@internal.com'))
            ->get('/')
            ->assertOk()
            ->assertSee('/sysadmin/users');
    }

    public function test_allowlisted_route_is_reachable_without_a_permission(): void
    {
        // `locale` is on the allowlist, so it has no permission and stays reachable.
        $this->actingAs($this->user('developer@internal.com'))
            ->get('/locale/en')
            ->assertRedirect();
    }

    public function test_user_without_permissions_sees_no_module_menus(): void
    {
        $nobody = User::factory()->create([
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $this->actingAs($nobody)
            ->get('/')
            ->assertOk()
            ->assertDontSee('/sysadmin/users')
            ->assertDontSee('/development/components');
    }

    public function test_new_menus_are_visible_to_the_roles_that_hold_them(): void
    {
        // MANAGER holds work_calendar.% and reports.%
        $this->actingAs($this->user('manager@internal.com'))
            ->get('/')
            ->assertOk()
            ->assertSee('/work-calendar')
            ->assertSee('/reports');

        // EMPLOYEE holds work_calendar.% only
        $this->actingAs($this->user('employee@internal.com'))
            ->get('/')
            ->assertOk()
            ->assertSee('/work-calendar')
            ->assertDontSee('/reports');

        // DEVELOPER holds development.command.%
        $this->actingAs($this->user('developer@internal.com'))
            ->get('/')
            ->assertOk()
            ->assertSee('/development/command');
    }

    public function test_deleting_a_route_permission_closes_the_route_and_hides_the_menu(): void
    {
        $developer = $this->user('developer@internal.com');

        $this->actingAs($developer)->get('/development/components')->assertOk();

        Permission::where('name', 'development.components.view')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Fail-closed: the route is now closed...
        $this->actingAs($developer->fresh())->get('/development/components')->assertForbidden();

        // ...and its menu disappears from the sidebar.
        $this->actingAs($developer->fresh())
            ->get('/')
            ->assertOk()
            ->assertDontSee('/development/components');
    }

    public function test_permission_sync_recreates_a_missing_permission(): void
    {
        Permission::where('name', 'reports.view')->delete();
        $this->assertDatabaseMissing('permissions', ['name' => 'reports.view']);

        $this->artisan('permission:sync')->assertSuccessful();

        $this->assertDatabaseHas('permissions', ['name' => 'reports.view', 'guard_name' => 'web']);
    }

    public function test_seeded_route_permissions_are_flagged_as_generated(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => 'reports.view',
            'is_generated' => true,
        ]);
    }

    public function test_manually_created_permission_is_not_flagged_as_generated(): void
    {
        $admin = $this->user('otoriterman@internal.com');

        $this->actingAs($admin)
            ->postJson('/sysadmin/permissions/ajax', [
                'name' => 'custom.gate.check',
                'guard_name' => 'web',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'custom.gate.check',
            'is_generated' => false,
        ]);
    }

    public function test_permission_sync_self_heals_a_route_permission_flagged_manual(): void
    {
        // Simulate a route permission that exists but is flagged manual.
        Permission::where('name', 'reports.view')->update(['is_generated' => false]);

        $this->artisan('permission:sync')->assertSuccessful();

        $this->assertDatabaseHas('permissions', [
            'name' => 'reports.view',
            'is_generated' => true,
        ]);
    }

    public function test_permission_sync_does_not_report_a_manual_permission_as_orphan(): void
    {
        Permission::create([
            'name' => 'custom.gate.check',
            'guard_name' => 'web',
            'is_generated' => false,
            'created_by' => 1,
            'updated_by' => 1,
        ]);

        $this->artisan('permission:sync')
            ->doesntExpectOutputToContain('custom.gate.check')
            ->assertSuccessful();

        $this->assertDatabaseHas('permissions', [
            'name' => 'custom.gate.check',
            'is_generated' => false,
        ]);
    }

    public function test_route_mapped_permission_cannot_be_deleted_via_endpoint(): void
    {
        $admin = $this->user('otoriterman@internal.com');
        $permission = Permission::where('name', 'reports.view')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson('/sysadmin/permissions/ajax/' . $permission->ulid)
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('permissions', ['name' => 'reports.view']);
    }

    public function test_route_mapped_permission_cannot_be_renamed_via_endpoint(): void
    {
        $admin = $this->user('otoriterman@internal.com');
        $permission = Permission::where('name', 'reports.view')->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/sysadmin/permissions/ajax/' . $permission->ulid, [
                'name' => 'reports.view.renamed',
                'guard_name' => 'web',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('permissions', ['name' => 'reports.view']);
    }
}
