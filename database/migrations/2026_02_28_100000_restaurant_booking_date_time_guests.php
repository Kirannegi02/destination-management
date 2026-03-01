<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restaurant booking: use booking_date + booking_time + guests instead of check_in, check_out, rooms.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('booking_date')->nullable()->after('meal_price_inr');
            $table->string('booking_time', 20)->nullable()->after('booking_date');
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'check_in')) {
                $table->dropIndex(['restaurant_id', 'check_in', 'check_out']);
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['check_in', 'check_out', 'rooms']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['restaurant_id', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'booking_date']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->unsignedInteger('rooms')->default(1);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['restaurant_id', 'check_in', 'check_out']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_date', 'booking_time']);
        });
    }
};
