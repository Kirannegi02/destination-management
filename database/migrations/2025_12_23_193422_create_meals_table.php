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
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            
            // Restaurant relationship
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            
            // Meal type (Standard Buffet Lunch, Standard Buffet Dinner, Premium Buffet Lunch, Premium Buffet Dinner, Cocktail Dinner without Liquor, Cocktail Dinner with Liquor)
            $table->enum('meal_type', [
                'standard_buffet_lunch',
                'standard_buffet_dinner',
                'premium_buffet_lunch',
                'premium_buffet_dinner',
                'cocktail_dinner_without_liquor',
                'cocktail_dinner_with_liquor'
            ]);
            
            // Menu description
            $table->text('menu_description');
            
            // Pricing (stored in INR, but can display in local currency)
            $table->decimal('price_inr', 10, 2)->nullable()->comment('Price in INR');
            $table->string('local_currency', 10)->nullable()->comment('Local currency code (e.g., USD, EUR)');
            $table->decimal('local_price', 10, 2)->nullable()->comment('Price in local currency');
            
            // Supplements (JSON array for starter, main course supplements)
            $table->json('supplements')->nullable()->comment('JSON: {"starter": {"available": true, "price": 200}, "main_course": {"available": true, "price": 300}}');
            
            // Status
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Order/Display order
            $table->integer('display_order')->default(0)->comment('Order for display');
            
            $table->timestamps();
            
            // Indexes
            $table->index('restaurant_id');
            $table->index('meal_type');
            $table->index('status');
            $table->index(['restaurant_id', 'meal_type']); // Composite index for unique meal types per restaurant
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
