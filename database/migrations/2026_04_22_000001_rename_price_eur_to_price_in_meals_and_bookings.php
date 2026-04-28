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

        if (Schema::hasTable('meals') && Schema::hasColumn('meals', 'price_eur')) {
            DB::statement('ALTER TABLE `meals` CHANGE `price_eur` `price` DECIMAL(10,2) NULL');
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'meal_price_eur')) {
            DB::statement('ALTER TABLE `bookings` CHANGE `meal_price_eur` `meal_price` DECIMAL(10,2) NULL');
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        if (Schema::hasTable('meals') && Schema::hasColumn('meals', 'price')) {
            DB::statement('ALTER TABLE `meals` CHANGE `price` `price_eur` DECIMAL(10,2) NULL');
        }

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'meal_price')) {
            DB::statement('ALTER TABLE `bookings` CHANGE `meal_price` `meal_price_eur` DECIMAL(10,2) NULL');
        }
    }
};
