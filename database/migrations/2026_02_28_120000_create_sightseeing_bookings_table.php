<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sightseeing bookings: date, pax count, price (CHF), optional sightseeing option (variation).
     */
    public function up(): void
    {
        Schema::create('sightseeing_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sightseeing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sightseeing_option_id')->nullable()->constrained()->nullOnDelete();
            $table->date('booking_date');
            $table->unsignedInteger('pax_count');
            $table->decimal('price', 12, 2);
            $table->string('currency', 8)->default('CHF');
            $table->string('guest_name')->nullable();
            $table->string('guest_phone')->nullable();
            $table->json('guests_details')->nullable();
            $table->text('special_requests')->nullable();
            $table->text('booking_conditions_snapshot')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'booking_date']);
            $table->index(['sightseeing_id', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sightseeing_bookings');
    }
};
