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
        'operating_areas',
        'meeting_points',
        'dropoff_points',
        'language',
        'primary_language',
        'other_languages',
        'language_proficiency',
        'service_date',
        'available_days',
        'available_from_date',
        'available_to_date',
        'start_point',
        'default_start_location',
        'end_point',
        'default_end_location',
        'start_time',
        'daily_start_time',
        'end_time',
        'daily_end_time',
        'start_time_slots',
        'end_time_auto_calculated',
        'duration_hours',
        'blackout_dates',
        'price',
        'base_price',
        'peak_season_price',
        'off_season_price',
        'weekend_price',
        'festival_surcharge',
        'child_discount',
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
        'average_rating',
        'total_bookings_completed',
        'cancellation_count',
        'customer_feedback',
        'admin_notes',
        'display_on_website',
        'featured_guide',
        'profile_priority_order',
        'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'price' => 'decimal:2',
        'date_of_birth' => 'date',
        'available_from_date' => 'date',
        'available_to_date' => 'date',
        'daily_start_time' => 'datetime:H:i',
        'daily_end_time' => 'datetime:H:i',
        'base_price' => 'decimal:2',
        'peak_season_price' => 'decimal:2',
        'off_season_price' => 'decimal:2',
        'weekend_price' => 'decimal:2',
        'festival_surcharge' => 'decimal:2',
        'child_discount' => 'decimal:2',
        'average_rating' => 'decimal:2',
        'operating_areas' => 'array',
        'meeting_points' => 'array',
        'dropoff_points' => 'array',
        'other_languages' => 'array',
        'available_days' => 'array',
        'blackout_dates' => 'array',
        'start_time_slots' => 'array',
        'indian_language_support' => 'array',
        'end_time_auto_calculated' => 'boolean',
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


