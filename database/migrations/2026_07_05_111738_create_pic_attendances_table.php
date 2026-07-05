<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pic_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pic_id')->constrained('pics')->cascadeOnDelete();
            $table->string('type', 20); // checkin, checkout
            $table->string('method', 20)->default('kiosk'); // kiosk, manual, admin
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['pic_id', 'checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pic_attendances');
    }
};
