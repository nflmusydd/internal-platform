<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['name' => 'view_sysadmin', 'guard_name' => 'web'],
            ['name' => 'view_sysadmin_menus', 'guard_name' => 'web'],
            ['name' => 'view_sysadmin_users', 'guard_name' => 'web'],
            ['name' => 'view_sysadmin_role_permissions', 'guard_name' => 'web'],
            ['name' => 'view_sysadmin_user_permissions', 'guard_name' => 'web'],
            // ['name' => 'manage_sysadmin_user_permissions', 'guard_name' => 'web'], 
            ['name' => 'view_development', 'guard_name' => 'web'],
            ['name' => 'view_development_components', 'guard_name' => 'web'],
            ['name' => 'view_work_calendar', 'guard_name' => 'web'],
        ];

        $roles = [
            ['name' => 'SUPER ADMIN', 'guard_name' => 'web'],
            ['name' => 'DEVELOPER', 'guard_name' => 'web'],
            ['name' => 'MANAGER', 'guard_name' => 'web'],
            ['name' => 'TEAM LEADER', 'guard_name' => 'web'],
            ['name' => 'EMPLOYEE', 'guard_name' => 'web'],
        ];

        DB::beginTransaction();

        try {
            // SYNC PERMISSIONS
            $masterPermNames = [];
            $existingPerms = DB::table('permissions')->get()->keyBy('name');

            foreach ($permissions as $item) {
                $masterPermNames[] = $item['name'];
                $existing = $existingPerms[$item['name']] ?? null;

                if (!$existing) {
                    DB::table('permissions')->insert([
                        'ulid' => (string) Str::ulid(),
                        'name' => $item['name'],
                        'guard_name' => $item['guard_name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => 1, 
                        'updated_by' => 1,
                    ]);
                }
                // else {
                //     DB::table('permissions')
                //             ->where('name', $item['name'])
                //             ->update([
                //                 'guard_name' => $item['guard_name'],
                //                 'updated_at' => now(),
                //                 'updated_by' => 1,
                //             ]);
                // }
            }
            // NOTE: Tidak hapus permission yang tidak di master list karena bisa di-manage dari UI
            $this->command->info("Permissions synced successfully (insert-if-not-exists only).");

            // SYNC ROLES
            $masterRoleNames = [];
            $existingRoles = DB::table('roles')->get()->keyBy('name');

            foreach ($roles as $item) {
                $masterRoleNames[] = $item['name'];
                $existing = $existingRoles[$item['name']] ?? null;

                if (!$existing) {
                    DB::table('roles')->insert([
                        'ulid' => (string) Str::ulid(),
                        'name' => $item['name'],
                        'guard_name' => $item['guard_name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => 1,
                        'updated_by' => 1,
                    ]);
                }
            }
            $this->command->info("Roles synced successfully.");

            // ASSIGNMENT (User Permissions & Roles)
            $superAdmin = Role::where('name', 'SUPER ADMIN')->first();
            $superAdmin->syncPermissions(Permission::all());

            $developer = Role::where('name', 'DEVELOPER')->first();
            $developer->syncPermissions([
                'view_development',
                'view_development_components'
            ]);

            $manager = Role::where('name', 'MANAGER')->first();
            $manager->syncPermissions([
                'view_work_calendar',
                // 
            ]);
            

            // Setup User
            $adminUser = User::firstOrCreate(
                ['email' => 'otoriterman@internal.com'],
                ['name' => 'MANUSIA OTORITER', 'password' => bcrypt('test1234'), 'is_active' => 1, 'created_by' => 0, 'updated_by' => 0],
            );
            $adminUser->syncRoles(['SUPER ADMIN']);

            $managerUser = User::firstOrCreate(
                ['email' => 'manager@internal.com'],
                ['name' => 'MANAGER MAGER', 'password' => bcrypt('test1234'), 'is_active' => 1, 'created_by' => 0, 'updated_by' => 0],
            );
            $managerUser->syncRoles(['MANAGER']);

            DB::commit();
            
            // agar view composer merender data baru
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            
            $this->command->info("=========== ROLE & PERMISSION SYNC COMPLETE ===========");
            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ROLE PERMISSION SYNC: Process Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->command->error("An error occurred: " . $e->getMessage());
            throw $e;
        }
    }
}
