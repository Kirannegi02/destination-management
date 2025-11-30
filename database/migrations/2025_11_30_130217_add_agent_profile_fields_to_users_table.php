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
            // Agent profile fields
            $table->string('image')->nullable()->after('name');
            $table->string('agency_name')->nullable()->after('image');
            $table->string('gst_number', 15)->nullable()->after('agency_name');
            $table->text('address')->nullable()->after('gst_number');
            $table->string('state')->nullable()->after('address');
            $table->string('city')->nullable()->after('state');
            $table->string('pincode', 10)->nullable()->after('city');
            
            // Additional useful fields
            $table->string('alternate_phone')->nullable()->after('phone');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending')->after('pincode');
            $table->timestamp('profile_completed_at')->nullable()->after('status');
            
            // Indexes
            $table->index('gst_number');
            $table->index('status');
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
