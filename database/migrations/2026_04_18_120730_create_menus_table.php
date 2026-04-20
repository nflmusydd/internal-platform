<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->ulid('ulid')->unique();
            $table->string('slug', 50)->unique();
            
            $table->foreignId('parent_id')->nullable()->constrained('menus')->onDelete('cascade');
            
            $table->string('name_en', 50)->default('');
            $table->string('name_id', 50)->default('');
            $table->string('route_name')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order');
            $table->boolean('is_active')->default(true);
            $table->string('permission_name')->nullable();

            $table->dateTime('created_at');
            $table->unsignedBigInteger('created_by');
            $table->dateTime('updated_at');
            $table->unsignedBigInteger('updated_by');

            $table->unique(['parent_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
