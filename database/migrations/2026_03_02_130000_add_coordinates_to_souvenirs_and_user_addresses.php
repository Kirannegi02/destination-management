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
            if (!Schema::hasColumn('souvenirs', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('city');
            }
            if (!Schema::hasColumn('souvenirs', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_addresses', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('pincode');
            }
            if (!Schema::hasColumn('user_addresses', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('souvenirs', function (Blueprint $table) {
            if (Schema::hasColumn('souvenirs', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('souvenirs', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('user_addresses', 'longitude')) {
                $table->dropColumn('longitude');
            }
            if (Schema::hasColumn('user_addresses', 'latitude')) {
                $table->dropColumn('latitude');
            }
        });
    }
};

