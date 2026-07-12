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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('destination');
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->decimal('cargo_weight_kg', 10, 2)->default(0);
            $table->decimal('planned_distance_km', 10, 2)->default(0);
            $table->decimal('actual_distance_km', 10, 2)->nullable();
            $table->decimal('fuel_consumed_liters', 10, 2)->nullable();
            $table->decimal('revenue', 12, 2)->default(0);
            $table->string('status')->default('Draft'); // Draft, Dispatched, Completed, Cancelled
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
