<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->string('location', 255)->nullable()->after('id');
        });

        // Copy from_location into location (and append to_location for legacy rows that have both)
        $rows = DB::table('transports')->orderBy('id')->get();
        foreach ($rows as $row) {
            $location = $row->from_location ?? '';
            if (!empty($row->to_location)) {
                $location = trim(($row->from_location ?? '') . ', ' . $row->to_location);
            }
            DB::table('transports')->where('id', $row->id)->update(['location' => $location ?: null]);
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->dropIndex(['from_location', 'to_location']);
            $table->dropColumn(['from_location', 'to_location', 'currency']);
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropIndex(['location']);
            $table->string('from_location')->nullable()->after('id');
            $table->string('to_location')->nullable()->after('from_location');
            $table->string('currency', 10)->default('INR')->after('min_charge');
        });

        $rows = DB::table('transports')->orderBy('id')->get();
        foreach ($rows as $row) {
            DB::table('transports')->where('id', $row->id)->update([
                'from_location' => $row->location,
                'to_location' => null,
            ]);
        }

        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->index(['from_location', 'to_location']);
        });
    }
};
