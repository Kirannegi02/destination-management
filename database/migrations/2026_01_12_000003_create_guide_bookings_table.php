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
        Schema::create('guide_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('guide_id');
            $table->unsignedBigInteger('guide_package_id')->nullable();
            $table->date('service_date');
            $table->time('start_time')->nullable();
            $table->time('calculated_end_time')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->string('start_location')->nullable();
            $table->string('end_location')->nullable();
            $table->string('start_time_slot')->nullable();
            $table->string('status', 50)->default('pending');
            $table->text('special_requests')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->decimal('estimated_total', 12, 2)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('guide_id')->references('id')->on('guides')->cascadeOnDelete();
            $table->foreign('guide_package_id')->references('id')->on('guide_packages')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_bookings');
    }
};



