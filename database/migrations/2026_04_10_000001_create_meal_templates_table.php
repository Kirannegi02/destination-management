<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('meal_type', 64)->unique();
            $table->text('menu_description');
            $table->json('supplements')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('meal_templates')->insert([
            [
                'meal_type' => 'standard_buffet_lunch',
                'menu_description' => 'Standard buffet lunch — update description under Admin → Global menu.',
                'supplements' => null,
                'status' => 'active',
                'display_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'meal_type' => 'standard_buffet_dinner',
                'menu_description' => 'Standard buffet dinner — update description under Admin → Global menu.',
                'supplements' => null,
                'status' => 'active',
                'display_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'meal_type' => 'premium_buffet_lunch',
                'menu_description' => 'Premium buffet lunch — update description under Admin → Global menu.',
                'supplements' => null,
                'status' => 'active',
                'display_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_templates');
    }
};
