<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        if (Schema::hasTable('meals') && Schema::hasColumn('meals', 'price_inr')) {
            DB::statement('ALTER TABLE `meals` CHANGE `price_inr` `price_eur` DECIMAL(10,2) NULL COMMENT \'Price in EUR\'');
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'meal_price_inr')) {
            DB::statement('ALTER TABLE `bookings` CHANGE `meal_price_inr` `meal_price_eur` DECIMAL(10,2) NULL');
        }

        if (Schema::hasTable('transport_zones')) {
            DB::table('transport_zones')->where('currency', 'INR')->update(['currency' => 'EUR']);
        }

        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'currency')) {
            DB::table('vehicles')->where('currency', 'INR')->update(['currency' => 'EUR']);
        }

        if (Schema::hasTable('souvenirs') && Schema::hasColumn('souvenirs', 'currency')) {
            DB::table('souvenirs')->where('currency', 'INR')->update(['currency' => 'EUR']);
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        if (Schema::hasTable('meals') && Schema::hasColumn('meals', 'price_eur')) {
            DB::statement('ALTER TABLE `meals` CHANGE `price_eur` `price_inr` DECIMAL(10,2) NULL');
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'meal_price_eur')) {
            DB::statement('ALTER TABLE `bookings` CHANGE `meal_price_eur` `meal_price_inr` DECIMAL(10,2) NULL');
        }
    }
};
