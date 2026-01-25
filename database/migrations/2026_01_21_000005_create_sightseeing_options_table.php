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
        Schema::create('sightseeing_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sightseeing_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('currency', 8)->default('CHF');
            $table->unsignedInteger('default_pax')->nullable();
            $table->boolean('includes_lunch')->default(false);
            $table->boolean('includes_transport')->default(false);
            $table->text('availability_note')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sightseeing_options');
    }
};

