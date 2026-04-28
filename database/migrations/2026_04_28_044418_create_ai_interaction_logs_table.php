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
        Schema::create('ai_interaction_logs', function (Blueprint $table) {
            $table->id();

            $table->string('session_id');
            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();

            $table->text('transcription_text')->nullable();
            $table->string('detected_intent')->nullable();
            $table->text('ai_response_text')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_interaction_logs');
    }
};
