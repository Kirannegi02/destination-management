<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow custom meal_type values (not only enum literals) for admin-defined types.
     */
    public function up(): void
    {
        if (! Schema::hasTable('meals')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE `meals` MODIFY `meal_type` VARCHAR(100) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE meals ALTER COLUMN meal_type TYPE VARCHAR(100)');
        }
        // sqlite: fresh migrate often recreates schema; skip or rely on manual conversion in dev
    }

    public function down(): void
    {
        // Intentionally empty: restoring ENUM would fail if custom meal_type values exist.
    }
};
