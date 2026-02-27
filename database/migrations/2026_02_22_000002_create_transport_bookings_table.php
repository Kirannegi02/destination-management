<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Transport quote/booking: trip type, cities, days per city, quote breakdown, total.
     */
    public function up(): void
    {
        Schema::create('transport_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('trip_type', 32); // one_way, return, multicity
            $table->unsignedInteger('passengers');
            $table->json('cities'); // ['Dehradun', 'Mumbai'] or multicity ['Paris', 'Zurich', 'Munich']
            $table->json('days_per_city'); // [3, 2] = 3 days in first city, 2 in second
            $table->json('legs_by_train')->nullable(); // [false, true] = second leg by train (optional)
            $table->json('distances_km')->nullable(); // optional [250, 1400] per leg when not from DB
            $table->boolean('airport_transfer_supplement')->default(false);
            $table->string('itinerary_attachment')->nullable();
            $table->text('remarks')->nullable();
            $table->json('quote_breakdown')->nullable(); // line items and total snapshot
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('status', 32)->default('quote'); // quote, pending, confirmed, cancelled
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 50)->nullable();
            $table->string('guest_country', 100)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_bookings');
    }
};
