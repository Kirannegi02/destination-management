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
        Schema::create('sightseeings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('start_location')->nullable();
            $table->string('end_location')->nullable();
            $table->decimal('standard_price', 10, 2)->nullable();
            $table->string('currency', 8)->default('CHF');
            $table->unsignedInteger('default_pax')->nullable();
            $table->string('standard_price_note')->nullable();
            $table->text('availability_notes')->nullable();
            $table->text('booking_conditions')->nullable();
            $table->text('detail_page_note')->nullable();
            $table->string('image')->nullable();
            $table->boolean('requires_date')->default(true);
            $table->boolean('requires_pax')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('active');
            $table->integer('display_order')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sightseeings');
    }
};

