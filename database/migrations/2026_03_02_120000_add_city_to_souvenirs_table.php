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
        Schema::table('souvenirs', function (Blueprint $table) {
            if (!Schema::hasColumn('souvenirs', 'city')) {
                $table->string('city', 100)
                    ->nullable()
                    ->after('min_order_quantity')
                    ->comment('City for matching delivery address');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('souvenirs', function (Blueprint $table) {
            if (Schema::hasColumn('souvenirs', 'city')) {
                $table->dropColumn('city');
            }
        });
    }
};

