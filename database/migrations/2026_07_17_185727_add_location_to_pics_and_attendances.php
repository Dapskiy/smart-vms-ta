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
            $table->string('current_location', 50)->nullable()->after('is_available');
        });

        Schema::table('pic_attendances', function (Blueprint $table) {
            $table->string('location', 50)->nullable()->after('method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pics', function (Blueprint $table) {
            $table->dropColumn('current_location');
        });

        Schema::table('pic_attendances', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
