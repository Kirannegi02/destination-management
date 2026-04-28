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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'image')) {
                $table->string('image')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'agency_name')) {
                $table->string('agency_name')->nullable()->after('image');
            }
            if (!Schema::hasColumn('users', 'gst_number')) {
                $table->string('gst_number', 15)->nullable()->after('agency_name');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('gst_number');
            }
            if (!Schema::hasColumn('users', 'state')) {
                $table->string('state')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'city')) {
                $table->string('city')->nullable()->after('state');
            }
            if (!Schema::hasColumn('users', 'pincode')) {
                $table->string('pincode', 10)->nullable()->after('city');
            }
            if (!Schema::hasColumn('users', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'pending'])->default('pending')->after('pincode');
            }
            if (!Schema::hasColumn('users', 'profile_completed_at')) {
                $table->timestamp('profile_completed_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'image',
                'agency_name',
                'gst_number',
                'address',
                'state',
                'city',
                'pincode',
                'alternate_phone',
                'status',
                'profile_completed_at',
            ]);
        });
    }
};
