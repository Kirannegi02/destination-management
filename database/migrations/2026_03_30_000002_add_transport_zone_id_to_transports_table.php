<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->foreignId('transport_zone_id')
                ->nullable()
                ->after('id')
                ->constrained('transport_zones')
                ->cascadeOnDelete();
        });

        Schema::table('transports', function (Blueprint $table) {
            $table->unique(['transport_zone_id', 'vehicle_id'], 'transports_zone_vehicle_unique');
        });
    }

    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropUnique('transports_zone_vehicle_unique');
            $table->dropConstrainedForeignId('transport_zone_id');
        });
    }
};
