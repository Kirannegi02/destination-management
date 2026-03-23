<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            if (!Schema::hasColumn('guides', 'half_day_price')) {
                $table->decimal('half_day_price', 10, 2)->nullable()->after('duration_hours');
            }
            if (!Schema::hasColumn('guides', 'full_day_price')) {
                $table->decimal('full_day_price', 10, 2)->nullable()->after('half_day_price');
            }
            if (!Schema::hasColumn('guides', 'extra_hour_price')) {
                $table->decimal('extra_hour_price', 10, 2)->nullable()->after('full_day_price');
            }
        });

        // Backfill new pricing columns from legacy price where available.
        if (Schema::hasColumn('guides', 'price')) {
            DB::table('guides')
                ->whereNull('half_day_price')
                ->whereNotNull('price')
                ->update(['half_day_price' => DB::raw('price')]);

            DB::table('guides')
                ->whereNull('full_day_price')
                ->whereNotNull('price')
                ->update(['full_day_price' => DB::raw('price')]);
        }
    }

    public function down(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('guides', 'half_day_price') ? 'half_day_price' : null,
                Schema::hasColumn('guides', 'full_day_price') ? 'full_day_price' : null,
                Schema::hasColumn('guides', 'extra_hour_price') ? 'extra_hour_price' : null,
            ]);

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
