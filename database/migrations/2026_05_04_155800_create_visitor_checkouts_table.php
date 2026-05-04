<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->string('visitor_name');
            $table->time('checkout_time');
            $table->timestamps();

            $table->unique(['appointment_id', 'visitor_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_checkouts');
    }
};
