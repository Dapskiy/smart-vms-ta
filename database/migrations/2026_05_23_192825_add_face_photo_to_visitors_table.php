<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            // Menyimpan foto wajah dalam format AES-256 terenkripsi (base64 ciphertext)
            // Hanya bisa dibuka oleh sistem dengan APP_KEY yang benar
            $table->longText('face_photo')->nullable()->after('face_features')
                  ->comment('Encrypted face photo (AES-256-CBC via Laravel Crypt)');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('face_photo');
        });
    }
};
