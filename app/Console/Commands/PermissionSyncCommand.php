<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Support\DefaultRolePermissions;
use App\Support\RoutePermission;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class PermissionSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync
                            {--restore-roles : Re-apply the default permission bundles to every role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create any permission missing for a route and flag route-derived permissions as generated.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $result = RoutePermission::sync();

        $this->info('Permissions synced from routes.');
        $this->line('Created: ' . count($result['created']));
        foreach ($result['created'] as $name) {
            $this->line('  + ' . $name);
        }
        $this->line('Existing: ' . count($result['existing']));

        if ($result['marked'] !== []) {
            $this->line('Marked as generated: ' . count($result['marked']));
            foreach ($result['marked'] as $name) {
                $this->line('  ~ ' . $name);
            }
        }

        // Manual permissions are intentional and therefore not reported here.
        // Only route-derived rows that are still flagged as generated but no
        // longer match a route are true orphans.
        $orphans = Permission::where('guard_name', RoutePermission::guard())
            ->where('is_generated', true)
            ->whereNotIn('name', RoutePermission::permissions())
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($orphans) {
            $this->newLine();
            $this->warn('Generated permissions without a matching route (left untouched): ' . implode(', ', $orphans));
        }

        if ($this->option('restore-roles')) {
            DefaultRolePermissions::ensureRoles();
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            DefaultRolePermissions::applyBundles();
            $this->newLine();
            $this->info('Default role permission bundles re-applied.');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $this->newLine();

        return self::SUCCESS;
    }
}
