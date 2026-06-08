<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pastikan kolom face_photo dan face_features ada di tabel visitors.
     * Menggunakan pengecekan hasColumn agar aman dijalankan berkali-kali (idempotent).
     */
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (!Schema::hasColumn('visitors', 'face_photo')) {
                $table->longText('face_photo')->nullable();
            }
            if (!Schema::hasColumn('visitors', 'face_features')) {
                $table->longText('face_features')->nullable();
            }
        });
    }

    /**
     * Reverse: hapus kolom jika rollback diperlukan.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (Schema::hasColumn('visitors', 'face_photo')) {
                $table->dropColumn('face_photo');
            }
            if (Schema::hasColumn('visitors', 'face_features')) {
                $table->dropColumn('face_features');
            }
        });
    }
};
