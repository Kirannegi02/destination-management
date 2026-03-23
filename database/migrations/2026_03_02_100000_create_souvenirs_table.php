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
        Schema::create('souvenirs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('EUR')->comment('EUR or CHF');
            $table->unsignedInteger('min_order_quantity')->default(1);
            $table->string('country', 100)->nullable()->comment('Country code or name for filtering');
            $table->json('images')->nullable()->comment('Array of image paths');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souvenirs');
    }
};
