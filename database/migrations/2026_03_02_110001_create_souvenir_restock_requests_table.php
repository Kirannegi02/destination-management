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
        Schema::create('souvenir_restock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('souvenir_id')->constrained('souvenirs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'handled'])->default('pending');
            $table->timestamps();

            $table->index(['souvenir_id', 'status']);
            $table->index(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souvenir_restock_requests');
    }
};

