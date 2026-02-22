<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores distance (km) between two locations for transport quote calculation.
     */
    public function up(): void
    {
        Schema::create('location_distances', function (Blueprint $table) {
            $table->id();
            $table->string('from_location', 255);
            $table->string('to_location', 255);
            $table->decimal('distance_km', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['from_location', 'to_location']);
            $table->index('from_location');
            $table->index('to_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_distances');
    }
};
