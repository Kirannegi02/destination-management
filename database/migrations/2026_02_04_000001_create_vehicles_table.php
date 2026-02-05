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
            $table->string('name'); // e.g. Sedan, SUV, Tempo Traveller
            $table->integer('capacity_seats')->nullable()->comment('Passenger capacity');
            $table->text('description')->nullable();
            $table->decimal('default_price_per_km', 12, 2)->nullable()->comment('Default price per km if not set in transport');
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
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
