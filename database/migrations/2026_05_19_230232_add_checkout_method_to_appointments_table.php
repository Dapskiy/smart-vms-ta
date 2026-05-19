<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom checkout_method untuk mencatat mekanisme check-out visitor.
     * Nilai yang mungkin:
     *   - 'self'   : Visitor melakukan check-out mandiri via kiosk (face recognition)
     *   - 'system' : Auto-checkout oleh scheduler di akhir hari
     *   - 'manual' : Check-out dilakukan oleh petugas/admin via dashboard
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('checkout_method')->nullable()->after('checkout_time')
                  ->comment('self | system | manual');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('checkout_method');
        });
    }
};
