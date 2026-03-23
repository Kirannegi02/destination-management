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
        Schema::table('souvenir_orders', function (Blueprint $table) {
            $table->boolean('pending_restock')->default(false)->after('status');
            $table->text('partial_stock_summary')->nullable()->after('pending_restock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('souvenir_orders', function (Blueprint $table) {
            $table->dropColumn(['pending_restock', 'partial_stock_summary']);
        });
    }
};
