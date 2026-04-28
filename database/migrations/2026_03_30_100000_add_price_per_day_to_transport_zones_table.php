<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zone-wide daily rate (e.g. 12h disposal); per-vehicle rates stay on transports (price_per_km).
     */
    public function up(): void
    {
        Schema::table('transport_zones', function (Blueprint $table) {
            $table->decimal('price_per_day', 12, 2)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('transport_zones', function (Blueprint $table) {
            $table->dropColumn('price_per_day');
        });
    }
};
