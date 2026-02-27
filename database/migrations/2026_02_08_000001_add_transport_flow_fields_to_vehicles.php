<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds fields for fleet display and quote flow: category, sort order, image.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('vehicle_category', 64)->nullable()->after('name')
                ->comment('VAN, 16 Seater, 19 Seater, Full Size Coach, Luxury Cars');
            $table->unsignedInteger('sort_order')->default(0)->after('status');
            $table->string('image', 512)->nullable()->after('description')
                ->comment('Indicative vehicle image path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['vehicle_category', 'sort_order', 'image']);
        });
    }
};
