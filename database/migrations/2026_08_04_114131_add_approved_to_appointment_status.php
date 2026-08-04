<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_status_check");
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'approved'::text, 'active'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_status', function (Blueprint $table) {
            //
        });
    }
};
