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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete(); // Nullable karena diisi nanti saat tamu self-service
            $table->string('purpose');
            $table->dateTime('visit_date');
            $table->string('token')->unique(); // Token unik untuk link self-service
            $table->enum('status', ['scheduled', 'checked-in', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
