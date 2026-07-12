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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('license_number')->unique();
            $table->string('license_category')->nullable(); // A, B, C, D, E
            $table->date('license_expiry_date')->nullable();
            $table->string('contact_number')->nullable();
            $table->decimal('safety_score', 5, 2)->default(100.00);
            $table->string('status')->default('Available'); // Available, On Trip, Off Duty, Suspended
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
