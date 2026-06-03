<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('private_venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('venue_type', 40)->default('conference_center');
            $table->string('brand_chain')->nullable();
            $table->text('description')->nullable();
            $table->text('highlights')->nullable();

            $table->text('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('pincode', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('website')->nullable();

            $table->json('images')->nullable();
            $table->string('video')->nullable();

            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->decimal('total_meeting_space_sqm', 12, 2)->nullable();
            $table->unsignedInteger('largest_room_capacity')->nullable();
            $table->unsignedSmallInteger('number_of_meeting_rooms')->nullable();
            $table->unsignedInteger('sleeping_rooms')->nullable();
            $table->unsignedInteger('min_event_size')->nullable();
            $table->unsignedInteger('max_event_size')->nullable();

            $table->string('currency', 3)->default('EUR');
            $table->decimal('starting_daily_rate', 12, 2)->nullable();
            $table->text('pricing_notes')->nullable();

            $table->json('amenities')->nullable();
            $table->json('event_types')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('city');
            $table->index('country');
            $table->index('venue_type');
        });

        Schema::create('private_venue_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('private_venue_id')->constrained('private_venues')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('total_space_sqm', 12, 2)->nullable();
            $table->decimal('length_m', 8, 2)->nullable();
            $table->decimal('width_m', 8, 2)->nullable();
            $table->decimal('ceiling_height_m', 8, 2)->nullable();
            $table->json('setup_capacities')->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('is_outdoor')->default(false);
            $table->boolean('is_private')->default(true);
            $table->boolean('is_semi_private')->default(false);
            $table->boolean('wheelchair_accessible')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['private_venue_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('private_venue_spaces');
        Schema::dropIfExists('private_venues');
    }
};
