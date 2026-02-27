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
        Schema::create('guide_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('guide_id');
            $table->string('service_type', 50)->nullable(); // 3H/6H/8H/12H
            $table->string('service_name')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->boolean('includes_lunch')->default(false);
            $table->boolean('includes_dinner')->default(false);
            $table->text('description')->nullable();
            $table->decimal('standard_price', 12, 2)->nullable();
            $table->decimal('extra_hour_price', 12, 2)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('guide_id')->references('id')->on('guides')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guide_packages');
    }
};



