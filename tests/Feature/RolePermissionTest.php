<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckRoutePermission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(CheckRoutePermission::class);
    }

    public function test_create_role_with_invalid_guard_returns_422(): void
    {
        $user = User::factory()->create(['created_by' => 1, 'updated_by' => 1]);
        $this->actingAs($user, 'web');

        $res = $this->postJson(route('sysadmin.role_permissions.ajax.store'), [
            'name' => 'testrole',
            'guard_name' => 'invalidguard',
        ]);
        $res->assertStatus(422);
    }

    public function test_crud_role_web_works(): void
    {
        $user = User::factory()->create(['created_by' => 1, 'updated_by' => 1]);
        $this->actingAs($user, 'web');

        $res = $this->postJson(route('sysadmin.role_permissions.ajax.store'), [
            'name' => 'webrole',
            'guard_name' => 'web',
        ]);
        $res->assertStatus(200);

        $role = Role::where('name', 'webrole')->firstOrFail();
        $res = $this->putJson(route('sysadmin.role_permissions.ajax.update', $role->ulid), [
            'name' => 'webrole2',
            'guard_name' => 'web',
        ]);
        $res->assertStatus(200);

        $res = $this->deleteJson(route('sysadmin.role_permissions.ajax.destroy', $role->ulid));
        $res->assertStatus(200);
    }
}
