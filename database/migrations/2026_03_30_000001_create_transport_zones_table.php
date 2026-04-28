<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Geographic zones for transport pricing: cities list + optional GeoJSON polygon from admin map.
     */
    public function up(): void
    {
        Schema::create('transport_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('cities')->comment('City names included in this zone for quote matching');
            $table->json('polygon')->nullable()->comment('GeoJSON geometry object (Polygon or MultiPolygon)');
            $table->decimal('default_map_lat', 10, 7)->nullable();
            $table->decimal('default_map_lng', 10, 7)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_zones');
    }
};
