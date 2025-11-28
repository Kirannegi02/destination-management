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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', [
                'restaurant',
                'guide',
                'sightseeing',
                'transport',
                'souvenir',
                'private_venue',
                'catering',
                'train'
            ]);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->integer('capacity')->nullable();
            $table->json('images')->nullable();
            $table->json('features')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
