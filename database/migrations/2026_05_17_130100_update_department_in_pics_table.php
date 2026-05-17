<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pics', function (Blueprint $table) {
            // Hapus kolom string lama
            $table->dropColumn('department');

            // Tambah foreign key ke tabel departments
            $table->foreignId('department_id')
                ->nullable()
                ->after('name')
                ->constrained('departments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pics', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
            $table->string('department')->nullable()->after('name');
        });
    }
};
