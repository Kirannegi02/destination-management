<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->dropColumn(['local_currency', 'local_price']);
        });
    }

    public function down(): void
    {
        Schema::table('meals', function (Blueprint $table) {
            $table->string('local_currency', 10)->nullable()->comment('Local currency code (e.g., USD, EUR)');
            $table->decimal('local_price', 10, 2)->nullable()->comment('Price in local currency');
        });
    }
};
