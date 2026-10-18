<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneInvalidGuards extends Command
{
    protected $signature = 'permissions:prune-invalid-guards {--dry-run : List the affected roles and permissions without deleting anything}';

    protected $description = 'Delete roles and permissions with guards not configured in auth.guards';

    public function handle(): int
    {
        $valid = array_keys(config('auth.guards'));

        $roles = Role::whereNotIn('guard_name', $valid)->get();
        $perms = Permission::whereNotIn('guard_name', $valid)->get();

        $this->info('Valid guards: '.implode(', ', $valid));
        $this->info('Invalid roles found: '.$roles->count());
        $this->info('Invalid permissions found: '.$perms->count());

        if ($this->option('dry-run')) {
            foreach ($roles as $r) {
                $this->line("  Would delete role: {$r->name} ({$r->guard_name})");
            }
            foreach ($perms as $p) {
                $this->line("  Would delete permission: {$p->name} ({$p->guard_name})");
            }

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($roles, $perms) {
            foreach ($roles as $r) {
                $this->line("  Deleting role: {$r->name} ({$r->guard_name})");
                $r->delete();
            }
            foreach ($perms as $p) {
                $this->line("  Deleting permission: {$p->name} ({$p->guard_name})");
                $p->delete();
            }
        });

        return Command::SUCCESS;
    }
}
