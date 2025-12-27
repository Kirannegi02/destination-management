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
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'meal_id')) {
                $table->foreignId('meal_id')
                    ->nullable()
                    ->after('restaurant_id')
                    ->constrained('meals')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('bookings', 'meal_type')) {
                $table->string('meal_type', 100)->nullable()->after('meal_id');
            }

            if (!Schema::hasColumn('bookings', 'meal_price_inr')) {
                $table->decimal('meal_price_inr', 10, 2)->nullable()->after('meal_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'meal_price_inr')) {
                $table->dropColumn('meal_price_inr');
            }

            if (Schema::hasColumn('bookings', 'meal_type')) {
                $table->dropColumn('meal_type');
            }

            if (Schema::hasColumn('bookings', 'meal_id')) {
                $table->dropForeign(['meal_id']);
                $table->dropColumn('meal_id');
            }
        });
    }
};
