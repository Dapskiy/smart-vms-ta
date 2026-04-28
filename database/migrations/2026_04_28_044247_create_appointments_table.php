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

            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['appointment', 'walkin'])->default('appointment'); // 🔥 penting

            $table->text('purpose');
            $table->date('visit_date');

            $table->timestamp('expected_arrival_time')->nullable();
            $table->timestamp('expected_departure_time')->nullable();

            $table->integer('pax')->default(1);
            $table->string('vehicle_number')->nullable();
            $table->string('token')->unique()->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'checked_in',
                'checked_out'
            ])->default('pending');

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
