<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('visitors')->cascadeOnDelete();
            
            // Kolom untuk Repeater Anggota Rombongan
            $table->json('companions')->nullable(); 
            
            // Menggunakan 'walk-in' sesuai standar bahasa Inggris
            $table->enum('type', ['appointment', 'walk-in'])->default('appointment');
            
            $table->string('purpose');
            $table->date('visit_date');
            $table->time('visit_time');
            $table->integer('pax')->default(1);
            $table->string('vehicle_number')->nullable();
            $table->string('token')->unique();
            
            // Menambahkan 'active' agar Walk-in tidak error saat created
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};