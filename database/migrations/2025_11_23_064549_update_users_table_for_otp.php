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
            // Remove password field
            $table->dropColumn('password');
            $table->dropColumn('remember_token');
            
            // Add phone number (nullable, can login with email or phone)
            $table->string('phone')->nullable()->after('email');
            $table->index('phone');
            
            // Add OTP fields
            $table->string('otp')->nullable()->after('phone');
            $table->timestamp('otp_expires_at')->nullable()->after('otp');
            $table->enum('otp_type', ['email', 'sms'])->nullable()->after('otp_expires_at');
            
            // Make email nullable (can login with phone too)
            $table->string('email')->nullable()->change();
            
            // Add additional user fields
            $table->string('country_code')->nullable()->after('phone');
            $table->string('country')->nullable()->after('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->after('email');
            $table->rememberToken()->after('password');
            $table->string('email')->nullable(false)->change();
            
            $table->dropColumn(['phone', 'otp', 'otp_expires_at', 'otp_type', 'country_code', 'country']);
        });
    }
};
