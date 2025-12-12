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
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            
            // Basic Information
            $table->string('restaurant_name');
            $table->text('description')->nullable();
            
            // Address Information
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('pincode', 10)->nullable();
            
            // Contact Information
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('website')->nullable();
            
            // Images (JSON array for multiple images)
            $table->json('images')->nullable();
            
            // Rating and Classification
            $table->tinyInteger('star_rating')->nullable()->comment('1-5 star rating');
            $table->decimal('price', 10, 2)->nullable()->comment('Average price');
            
            // Restaurant Details
            $table->string('cuisine_type')->nullable()->comment('e.g., Indian, Chinese, Italian, etc.');
            $table->integer('seating_capacity')->nullable();
            $table->json('opening_hours')->nullable()->comment('JSON: {"monday": "09:00-22:00", ...}');
            $table->json('amenities')->nullable()->comment('JSON array: ["WiFi", "Parking", "AC", ...]');
            
            // Business Information
            $table->string('gst_number', 15)->nullable();
            $table->string('license_number')->nullable();
            
            // Features (Boolean flags)
            $table->boolean('parking_available')->default(false);
            $table->boolean('wifi_available')->default(false);
            $table->boolean('accepts_reservations')->default(false);
            
            // Payment and Social
            $table->json('payment_methods')->nullable()->comment('JSON array: ["Cash", "Card", "UPI", ...]');
            $table->json('social_media_links')->nullable()->comment('JSON: {"facebook": "url", "instagram": "url", ...}');
            
            // Location (for maps)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('status');
            $table->index('city');
            $table->index('state');
            $table->index('cuisine_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
