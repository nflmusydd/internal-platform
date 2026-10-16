<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot tables from spatie/laravel-permission that get assignment tracking.
     */
    private array $tables = ['model_has_roles', 'model_has_permissions'];

    public function up(): void
    {
        $isSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $isSqlite) {
                $assignedBy = $blueprint->unsignedBigInteger('assigned_by');
                $assignedAt = $blueprint->dateTime('assigned_at');

                // MySQL strict mode refuses a NOT NULL DATETIME column on a populated
                // table without a default, so there it is added nullable and tightened
                // to NOT NULL below. SQLite (tests) migrates empty tables, so it can
                // take the NOT NULL column directly.
                if (! $isSqlite) {
                    $assignedBy->nullable();
                    $assignedAt->nullable();
                }

                $blueprint->index('assigned_by', $table.'_assigned_by_index');
            });

            // Backfill the existing pivot rows: both columns must never be NULL.
            DB::table($table)->update([
                'assigned_by' => 1,
                'assigned_at' => now(),
            ]);

            if (! $isSqlite) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('assigned_by')->nullable(false)->change();
                    $blueprint->dateTime('assigned_at')->nullable(false)->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropIndex($table.'_assigned_by_index');
                $blueprint->dropColumn(['assigned_by', 'assigned_at']);
            });
        }
    }
};
