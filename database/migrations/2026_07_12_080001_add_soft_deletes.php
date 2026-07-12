<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soft delete columns to all core tables.
     *
     * This ensures that deleting a vehicle, driver, trip, or log
     * hides it from active views but preserves it for historical
     * analytics, reports, and foreign key integrity.
     */
    public function up(): void
    {
        $tables = [
            'vehicles',
            'drivers',
            'trips',
            'fuel_logs',
            'expenses',
            'maintenance_logs',
            'vehicle_documents',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'vehicles',
            'drivers',
            'trips',
            'fuel_logs',
            'expenses',
            'maintenance_logs',
            'vehicle_documents',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
