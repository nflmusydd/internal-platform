<?php

namespace Database\Seeders;

use App\Support\DefaultRolePermissions;
use App\Support\RoutePermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        DB::beginTransaction();

        try {
            // ==========================
            //  SYNC PERMISSIONS
            // ==========================
            $sync = RoutePermission::sync();
            $this->command?->info(
                'Permissions synced from routes: '
                . count($sync['created']) . ' created, '
                . count($sync['existing']) . ' existing, '
                . count($sync['marked']) . ' marked as generated.'
            );

            // ==========================
            //  SYNC ROLES
            // ==========================
            DefaultRolePermissions::ensureRoles();

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            // ==========================
            //  ASSIGN PERMISSIONS TO ROLES
            // ==========================
            DefaultRolePermissions::applyBundles();

            // ==========================
            //  ASSIGN ROLES TO USERS
            // ==========================
            DefaultRolePermissions::ensureUsers();

            DB::commit();

            // agar view composer merender data baru
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $this->command?->info('=========== ROLE & PERMISSION SYNC COMPLETE ===========');
            $this->command?->newLine();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ROLE PERMISSION SYNC: Process Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->command?->error('An error occurred: ' . $e->getMessage());
            throw $e;
        }
    }
}
