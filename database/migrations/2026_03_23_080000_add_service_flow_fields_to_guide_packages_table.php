<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guide_packages', function (Blueprint $table) {
            if (!Schema::hasColumn('guide_packages', 'default_start_location')) {
                $table->string('default_start_location')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('guide_packages', 'default_end_location')) {
                $table->string('default_end_location')->nullable()->after('default_start_location');
            }
            if (!Schema::hasColumn('guide_packages', 'start_point')) {
                $table->string('start_point')->nullable()->after('default_end_location');
            }
            if (!Schema::hasColumn('guide_packages', 'end_point')) {
                $table->string('end_point')->nullable()->after('start_point');
            }
            if (!Schema::hasColumn('guide_packages', 'start_time')) {
                $table->time('start_time')->nullable()->after('end_point');
            }
            if (!Schema::hasColumn('guide_packages', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
            if (!Schema::hasColumn('guide_packages', 'notes')) {
                $table->text('notes')->nullable()->after('end_time');
            }
            if (!Schema::hasColumn('guide_packages', 'status')) {
                $table->string('status', 20)->default('active')->after('notes');
            }
        });
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }
};
