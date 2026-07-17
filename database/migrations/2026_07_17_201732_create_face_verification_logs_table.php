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
        Schema::create('face_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitor_id')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('type'); // checkin, checkout, walkin-validation, returning-visitor
            $table->double('euclidean_distance', 8, 4)->nullable();
            $table->double('threshold', 8, 2)->default(0.5);
            $table->boolean('is_success');
            $table->string('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            // Foreign key to visitors (optional, cascade on delete)
            $table->foreign('visitor_id')->references('id')->on('visitors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_verification_logs');
    }
};
