<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add price column if it doesn't exist yet
        if (!Schema::hasColumn('restaurants', 'price')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->decimal('price', 10, 2)->nullable()->after('star_rating');
            });
        }

        // Migrate existing enum data to numeric price and drop the old column
        if (Schema::hasColumn('restaurants', 'price_range')) {
            DB::statement("
                UPDATE restaurants SET price = CASE price_range
                    WHEN 'low' THEN 500
                    WHEN 'medium' THEN 1500
                    WHEN 'high' THEN 2500
                    WHEN 'premium' THEN 4000
                    ELSE price
                END
                WHERE price IS NULL
            ");

            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('price_range');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate price_range enum if missing
        if (!Schema::hasColumn('restaurants', 'price_range')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->enum('price_range', ['low', 'medium', 'high', 'premium'])->nullable()->after('star_rating');
            });
        }

        // Map numeric price back to approximate ranges
        if (Schema::hasColumn('restaurants', 'price_range') && Schema::hasColumn('restaurants', 'price')) {
            DB::statement("
                UPDATE restaurants SET price_range = CASE
                    WHEN price IS NULL THEN NULL
                    WHEN price < 1000 THEN 'low'
                    WHEN price < 2000 THEN 'medium'
                    WHEN price < 3000 THEN 'high'
                    ELSE 'premium'
                END
            ");
        }

        // Drop price column if present
        if (Schema::hasColumn('restaurants', 'price')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};

