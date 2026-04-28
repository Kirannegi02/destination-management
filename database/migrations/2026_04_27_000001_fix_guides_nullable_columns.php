<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Give safe defaults to guides columns that cannot be NULL in the database
     * so saving a guide with optional fields left blank never throws a DB error.
     */
    public function up(): void
    {
        if (!Schema::hasTable('guides')) {
            return;
        }

        // verification_status: default 'pending'
        if (Schema::hasColumn('guides', 'verification_status')) {
            DB::statement("ALTER TABLE `guides` MODIFY `verification_status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            // Backfill any existing NULL rows
            DB::table('guides')->whereNull('verification_status')->update(['verification_status' => 'pending']);
        }

        // indian_tours_completed: default 0
        if (Schema::hasColumn('guides', 'indian_tours_completed')) {
            DB::statement("ALTER TABLE `guides` MODIFY `indian_tours_completed` INT NOT NULL DEFAULT 0");
            DB::table('guides')->whereNull('indian_tours_completed')->update(['indian_tours_completed' => 0]);
        }

        // Boolean flags: default 0 (false)
        foreach (['police_verification', 'experience_indian_customers', 'display_on_website', 'featured_guide'] as $col) {
            if (Schema::hasColumn('guides', $col)) {
                DB::statement("ALTER TABLE `guides` MODIFY `{$col}` TINYINT(1) NOT NULL DEFAULT 0");
            }
        }
    }

    public function down(): void
    {
        // No destructive rollback; original defaults are unknown
    }
};
