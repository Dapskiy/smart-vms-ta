<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update menus table
        Schema::table('menus', function (Blueprint $table) {
            if (!Schema::hasColumn('menus', 'name')) {
                $table->string('name')->after('id');
                $table->string('slug')->unique()->after('name');
                $table->string('icon')->nullable()->after('slug');
                $table->boolean('is_active')->default(true)->after('icon');
                $table->integer('sort_order')->default(0)->after('is_active');
            }
        });

        // Update permissions table
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('guard_name');
                $table->foreignId('menu_id')->nullable()->constrained('menus')->onDelete('cascade')->after('is_active');
                $table->text('description')->nullable()->after('menu_id');
            }
        });

        // Update roles table
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('guard_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['menu_id']);
            $table->dropColumn(['is_active', 'menu_id', 'description']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['name', 'slug', 'icon', 'is_active', 'sort_order']);
        });
    }
};
