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
            if (!Schema::hasColumn('souvenirs', 'stock')) {
                $table->unsignedInteger('stock')
                    ->default(0)
                    ->after('min_order_quantity')
                    ->comment('Current available stock');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('souvenirs', function (Blueprint $table) {
            if (Schema::hasColumn('souvenirs', 'stock')) {
                $table->dropColumn('stock');
            }
        });
    }
};

