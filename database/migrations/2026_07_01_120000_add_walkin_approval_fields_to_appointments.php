<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('approval_token', 64)->nullable()->unique()->after('token');
            $table->timestamp('approved_at')->nullable()->after('status');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
        });

        // PostgreSQL: Laravel enum() menggunakan CHECK constraint.
        // Drop constraint lama, tambahkan ulang dengan 'rejected'.
        DB::statement("ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_status_check");
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'active'::text, 'completed'::text, 'cancelled'::text, 'rejected'::text]))");
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['approval_token', 'approved_at', 'rejected_at']);
        });

        // Kembalikan constraint enum ke semula (tanpa 'rejected')
        DB::statement("ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_status_check");
        DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status::text = ANY (ARRAY['pending'::text, 'active'::text, 'completed'::text, 'cancelled'::text]))");
    }
};
