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
        Schema::create('transports', function (Blueprint $table) {
            $table->id();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->decimal('price_per_km', 12, 2);
            $table->decimal('min_charge', 12, 2)->nullable()->comment('Minimum charge for the trip');
            $table->string('currency', 10)->default('INR');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index(['from_location', 'to_location']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transports');
    }
};
