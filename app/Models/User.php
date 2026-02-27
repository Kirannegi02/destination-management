<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Load JWTSubject interface stub if the package is not installed
if (!interface_exists('Tymon\JWTAuth\Contracts\JWTSubject')) {
    require_once __DIR__ . '/../Support/JWTSubjectStub.php';
}

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'country_code',
        'country',
        'otp',
        'otp_expires_at',
        'otp_type',
        // Agent profile fields
        'image',
        'agency_name',
        'tax_number',
        'address',
        'state',
        'city',
        'pincode',
        'alternate_phone',
        'status',
        'profile_completed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'profile_completed_at' => 'datetime',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     * This method is compatible with tymon/jwt-auth package when installed.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     * This method is compatible with tymon/jwt-auth package when installed.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Find user by email or phone.
     */
    public static function findByEmailOrPhone($identifier)
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return self::where('email', $identifier)->first();
        }
        
        // Remove any non-numeric characters for phone
        $phone = preg_replace('/[^0-9+]/', '', $identifier);
        return self::where('phone', $phone)
            ->orWhereRaw("CONCAT(country_code, phone) = ?", [$phone])
            ->first();
    }

    /**
     * Generate and store OTP.
     */
    public function generateOtp($type = 'email')
    {
        $otp = str_pad((string) rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $this->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_type' => $type,
        ]);

        return $otp;
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp($otp)
    {
        // Allow a configurable default OTP (useful for testing or backdoor login)
        $defaultOtp = env('DEFAULT_OTP');
        if ($defaultOtp && $otp === $defaultOtp) {
            $this->update([
                'otp' => null,
                'otp_expires_at' => null,
                'otp_type' => null,
            ]);
            return true;
        }

        if ($this->otp !== $otp) {
            return false;
        }

        if ($this->otp_expires_at && $this->otp_expires_at->isPast()) {
            return false;
        }

        // Clear OTP after successful verification
        $this->update([
            'otp' => null,
            'otp_expires_at' => null,
            'otp_type' => null,
        ]);

        return true;
    }
}
