<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Rename gst_number to tax_number in restaurants and users.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('restaurants') && Schema::hasColumn('restaurants', 'gst_number')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE restaurants CHANGE gst_number tax_number VARCHAR(15) NULL');
            } else {
                Schema::table('restaurants', function (Blueprint $table) {
                    $table->renameColumn('gst_number', 'tax_number');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'gst_number')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE users DROP INDEX users_gst_number_index');
                DB::statement('ALTER TABLE users CHANGE gst_number tax_number VARCHAR(15) NULL');
                DB::statement('ALTER TABLE users ADD INDEX users_tax_number_index (tax_number)');
            } else {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropIndex(['gst_number']);
                });
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('gst_number', 'tax_number');
                });
                Schema::table('users', function (Blueprint $table) {
                    $table->index('tax_number');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (Schema::hasTable('restaurants') && Schema::hasColumn('restaurants', 'tax_number')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE restaurants CHANGE tax_number gst_number VARCHAR(15) NULL');
            } else {
                Schema::table('restaurants', function (Blueprint $table) {
                    $table->renameColumn('tax_number', 'gst_number');
                });
            }
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tax_number')) {
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE users DROP INDEX users_tax_number_index');
                DB::statement('ALTER TABLE users CHANGE tax_number gst_number VARCHAR(15) NULL');
                DB::statement('ALTER TABLE users ADD INDEX users_gst_number_index (gst_number)');
            } else {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropIndex(['tax_number']);
                });
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('tax_number', 'gst_number');
                });
                Schema::table('users', function (Blueprint $table) {
                    $table->index('gst_number');
                });
            }
        }
    }
};
