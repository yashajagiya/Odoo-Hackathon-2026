<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes to columns frequently used in
     * WHERE clauses, ORDER BY, and search/filter operations.
     *
     * Foreign key columns (vehicle_id, driver_id, trip_id, user_id)
     * are already indexed by Laravel's ->constrained() method.
     */
    public function up(): void
    {
        // Vehicles: status, type, region are filtered in dashboard, listings, and reports
        Schema::table('vehicles', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
            $table->index('region');
        });

        // Drivers: status filtered in dispatch validation, dashboard, listings
        // license_expiry_date queried in scheduled compliance checks
        Schema::table('drivers', function (Blueprint $table) {
            $table->index('status');
            $table->index('license_expiry_date');
        });

        // Trips: status is the most heavily filtered column across the app
        // completed_at and dispatched_at are used in date-range report filters
        Schema::table('trips', function (Blueprint $table) {
            $table->index('status');
            $table->index('completed_at');
            $table->index('dispatched_at');
        });

        // Maintenance logs: status filtered in listings and service logic
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->index('status');
        });

        // Fuel logs: date is sorted/filtered in listings
        Schema::table('fuel_logs', function (Blueprint $table) {
            $table->index('date');
        });

        // Expenses: type and date are sorted/filtered in listings
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('type');
            $table->index('date');
        });

        // Vehicle documents: expiry_date is filtered for compliance checks
        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['region']);
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['license_expiry_date']);
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['dispatched_at']);
        });

        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('fuel_logs', function (Blueprint $table) {
            $table->dropIndex(['date']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['date']);
        });

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->dropIndex(['expiry_date']);
        });
    }
};
