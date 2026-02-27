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
        if (Schema::hasColumn('restaurants', 'agency_name')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->dropColumn('agency_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('restaurants', 'agency_name')) {
            Schema::table('restaurants', function (Blueprint $table) {
                $table->string('agency_name')->nullable()->after('restaurant_name');
                $table->index('agency_name');
            });
        }
    }
};

