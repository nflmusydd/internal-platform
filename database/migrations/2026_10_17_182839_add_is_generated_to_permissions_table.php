<?php

use App\Support\RoutePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->boolean('is_generated')->default(false);
        });

        // Backfill: every permission that is derived from an application route
        // is marked as generated. On a fresh database (migrate:fresh --seed) the
        // table is empty and this is a no-op.
        $generated = RoutePermission::permissions();

        if ($generated !== []) {
            DB::table('permissions')
                ->whereIn('name', $generated)
                ->update(['is_generated' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('is_generated');
        });
    }
};
