<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds pricing fields for quote flow: per-day, driver accommodation, one-way, airport supplement.
     */
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->decimal('price_per_day', 12, 2)->nullable()->after('min_charge')
                ->comment('12 hour disposal price per day (e.g. 950 EUR)');
            $table->decimal('driver_accommodation_per_day', 12, 2)->nullable()->after('price_per_day')
                ->comment('Driver accommodation per day (e.g. 100 EUR)');
            $table->decimal('one_way_transfer_price', 12, 2)->nullable()->after('driver_accommodation_per_day')
                ->comment('One-way transfer e.g. to train station (e.g. 350 EUR)');
            $table->decimal('airport_transfer_supplement', 12, 2)->nullable()->after('one_way_transfer_price')
                ->comment('Airport transfer supplement (e.g. 350 CHF)');
            $table->string('currency', 10)->nullable()->after('airport_transfer_supplement')
                ->comment('EUR, CHF, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn([
                'price_per_day',
                'driver_accommodation_per_day',
                'one_way_transfer_price',
                'airport_transfer_supplement',
                'currency',
            ]);
        });
    }
};
