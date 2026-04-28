<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Stores multiple meals per booking as JSON array.
            // Each item: { meal_id, meal_type, meal_type_label, price_per_person, guests, subtotal }
            $table->json('meals_data')->nullable()->after('meal_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('meals_data');
        });
    }
};
