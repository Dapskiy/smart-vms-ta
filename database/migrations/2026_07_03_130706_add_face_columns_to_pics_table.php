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
        Schema::table('pics', function (Blueprint $table) {
            $table->json('face_photo')->nullable()->after('is_available');
            $table->json('face_features')->nullable()->after('face_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pics', function (Blueprint $table) {
            $table->dropColumn(['face_photo', 'face_features']);
        });
    }
};
