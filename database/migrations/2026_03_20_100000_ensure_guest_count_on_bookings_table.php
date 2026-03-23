<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restaurant bookings store the party size in `guests` (number of people).
     * Ensures the column exists on older or partial databases.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'guests')) {
                $table->unsignedInteger('guests')->default(1)->after('booking_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     * Does not drop `guests` — it may have existed before this migration.
     */
    public function down(): void
    {
        //
    }
};
