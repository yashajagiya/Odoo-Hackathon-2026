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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('type')->nullable(); // Bus, Truck, Van, etc.
            $table->decimal('max_load_capacity_kg', 10, 2)->default(0);
            $table->decimal('odometer_km', 12, 2)->default(0);
            $table->decimal('acquisition_cost', 12, 2)->default(0);
            $table->string('status')->default('Available'); // Available, On Trip, In Shop, Retired
            $table->string('region')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
