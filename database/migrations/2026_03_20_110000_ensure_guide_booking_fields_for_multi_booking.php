<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure guide booking table has all fields used by single/batch booking payloads.
     */
    public function up(): void
    {
        Schema::table('guide_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('guide_bookings', 'duration_hours')) {
                $table->unsignedInteger('duration_hours')->nullable()->after('calculated_end_time');
            }
            if (!Schema::hasColumn('guide_bookings', 'guests')) {
                $table->unsignedInteger('guests')->nullable()->after('duration_hours');
            }
            if (!Schema::hasColumn('guide_bookings', 'start_location')) {
                $table->string('start_location')->nullable()->after('guests');
            }
            if (!Schema::hasColumn('guide_bookings', 'end_location')) {
                $table->string('end_location')->nullable()->after('start_location');
            }
            if (!Schema::hasColumn('guide_bookings', 'start_time_slot')) {
                $table->string('start_time_slot')->nullable()->after('end_location');
            }
            if (!Schema::hasColumn('guide_bookings', 'special_requests')) {
                $table->text('special_requests')->nullable()->after('status');
            }
            if (!Schema::hasColumn('guide_bookings', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('estimated_total');
            }
            if (!Schema::hasColumn('guide_bookings', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('guide_bookings', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_phone');
            }
            if (!Schema::hasColumn('guide_bookings', 'metadata')) {
                $table->json('metadata')->nullable()->after('contact_email');
            }
        });
    }

    public function down(): void
    {
        // Keep columns intact to avoid dropping pre-existing production data.
    }
};
