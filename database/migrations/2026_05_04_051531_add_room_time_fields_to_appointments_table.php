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
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('should_book_room')->default(false)->after('room_id'); // Checkbox untuk pesan ruangan
            $table->time('room_start_time')->nullable()->after('should_book_room'); // Jam mulai ruangan
            $table->time('room_end_time')->nullable()->after('room_start_time'); // Jam selesai ruangan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['should_book_room', 'room_start_time', 'room_end_time']);
        });
    }
};
