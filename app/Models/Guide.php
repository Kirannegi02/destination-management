<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'full_name',
        'profile_photo',
        'gender',
        'date_of_birth',
        'phone_country_code',
        'phone_number',
        'email',
        'whatsapp_number',
        'emergency_contact_number',
        'nationality',
        'years_experience',
        'short_bio',
        'description',
        'country',
        'city',
        'language',
        'primary_language',
        'other_languages',
        'language_proficiency',
        'available_days',
        'available_from_date',
        'available_to_date',
        'daily_start_time',
        'daily_end_time',
        'status',
        'notes',
        'max_bookings_per_day',
        'id_proof_type',
        'id_proof_number',
        'id_proof_path',
        'license_path',
        'police_verification',
        'verification_status',
        'experience_indian_customers',
        'indian_tours_completed',
        'indian_language_support',
        'indian_special_notes',
        'display_on_website',
        'featured_guide',
        'created_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'available_from_date' => 'date',
        'available_to_date' => 'date',
        'daily_start_time' => 'datetime:H:i',
        'daily_end_time' => 'datetime:H:i',
        'other_languages' => 'array',
        'available_days' => 'array',
        'indian_language_support' => 'array',
        'display_on_website' => 'boolean',
        'featured_guide' => 'boolean',
        'police_verification' => 'boolean',
        'experience_indian_customers' => 'boolean',
    ];

    public function packages()
    {
        return $this->hasMany(GuidePackage::class);
    }

    public function bookings()
    {
        return $this->hasMany(GuideBooking::class);
    }
}


