<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guides', function (Blueprint $table) {
            $dropColumns = [
                'operating_areas',
                'meeting_points',
                'dropoff_points',
                'service_date',
                'start_point',
                'default_start_location',
                'default_end_location',
                'start_time_slots',
                'end_time_auto_calculated',
                'end_point',
                'start_time',
                'end_time',
                'duration_hours',
                'blackout_dates',
                'half_day_price',
                'full_day_price',
                'extra_hour_price',
                'price',
                'base_price',
                'peak_season_price',
                'off_season_price',
                'weekend_price',
                'festival_surcharge',
                'child_discount',
                'average_rating',
                'total_bookings_completed',
                'cancellation_count',
                'customer_feedback',
                'admin_notes',
                'profile_priority_order',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('guides', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('guide_packages', function (Blueprint $table) {
            $dropColumns = [
                'includes_lunch',
                'includes_dinner',
                'description',
                'active',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('guide_packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('guide_bookings', function (Blueprint $table) {
            $dropColumns = [
                'start_time_slot',
            ];

            foreach ($dropColumns as $column) {
                if (Schema::hasColumn('guide_bookings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
