<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_venue_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('private_venue_id')->constrained('private_venues')->cascadeOnDelete();
            $table->foreignId('private_venue_space_id')->nullable()->constrained('private_venue_spaces')->nullOnDelete();

            $table->string('event_name')->nullable();
            $table->string('event_type', 50)->nullable();
            $table->date('event_date_start');
            $table->date('event_date_end')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('guests');
            $table->string('setup_style', 40)->nullable();

            $table->decimal('estimated_total', 12, 2)->nullable();
            $table->string('currency', 3)->default('EUR');

            $table->string('status', 50)->default('pending');
            $table->text('special_requests')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['private_venue_id', 'event_date_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_venue_bookings');
    }
};
