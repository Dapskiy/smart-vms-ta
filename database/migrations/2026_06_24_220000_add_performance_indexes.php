<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan database index pada kolom-kolom yang paling sering
 * di-query untuk mempercepat operasi WHERE, JOIN, dan ORDER BY.
 *
 * Kolom yang di-index dipilih berdasarkan analisis query patterns:
 * - appointments: sering di-filter by status + visit_date (composite),
 *   dan di-JOIN via pic_id / visitor_id (foreign key index)
 * - visitors: sering di-search by name, di-filter by is_blacklisted
 * - pics: sering di-JOIN via department_id, di-filter by is_available
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Appointments ──────────────────────────────────────────
        Schema::table('appointments', function (Blueprint $table) {
            // Composite index: query paling umum = WHERE status = ? AND visit_date = ?
            $table->index(['status', 'visit_date'], 'idx_appointments_status_visit_date');

            // Foreign key indexes (PostgreSQL tidak auto-index FK)
            $table->index('pic_id', 'idx_appointments_pic_id');
            $table->index('visitor_id', 'idx_appointments_visitor_id');

            // Filter by type (walk-in vs appointment)
            $table->index('type', 'idx_appointments_type');
        });

        // ── Visitors ──────────────────────────────────────────────
        Schema::table('visitors', function (Blueprint $table) {
            // Searchable column
            $table->index('name', 'idx_visitors_name');
        });

        // ── Pics ──────────────────────────────────────────────────
        Schema::table('pics', function (Blueprint $table) {
            // Foreign key + filter
            $table->index('department_id', 'idx_pics_department_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_status_visit_date');
            $table->dropIndex('idx_appointments_pic_id');
            $table->dropIndex('idx_appointments_visitor_id');
            $table->dropIndex('idx_appointments_type');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex('idx_visitors_name');
        });

        Schema::table('pics', function (Blueprint $table) {
            $table->dropIndex('idx_pics_department_id');
        });
    }
};
