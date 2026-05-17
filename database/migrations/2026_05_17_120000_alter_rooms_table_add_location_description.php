<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ubah skema rooms: hapus capacity & is_active, tambah location & description.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Hapus kolom lama yang tidak dipakai
            $table->dropColumn(['capacity', 'is_active']);

            // Tambah kolom baru
            $table->string('location')->nullable()->after('name');
            $table->text('description')->nullable()->after('location');

            // Tambah unique constraint pada name
            $table->unique('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropColumn(['location', 'description']);
            $table->integer('capacity')->default(10)->after('name');
            $table->boolean('is_active')->default(true)->after('capacity');
        });
    }
};
