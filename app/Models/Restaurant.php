<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_name',
        'agency_name',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'alternate_phone',
        'website',
        'images',
        'star_rating',
        'amenities',
        'cuisine_type',
        'opening_hours',
        'price_range',
        'seating_capacity',
        'description',
        'status',
        'gst_number',
        'license_number',
        'parking_available',
        'wifi_available',
        'accepts_reservations',
        'payment_methods',
        'latitude',
        'longitude',
        'social_media_links',
    ];

    protected $casts = [
        'images' => 'array',
        'amenities' => 'array',
        'opening_hours' => 'array',
        'payment_methods' => 'array',
        'social_media_links' => 'array',
        'parking_available' => 'boolean',
        'wifi_available' => 'boolean',
        'accepts_reservations' => 'boolean',
        'star_rating' => 'integer',
        'seating_capacity' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user (agent) associated with this restaurant via agency_name
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agency_name', 'agency_name');
    }

    /**
     * Get price range label
     */
    public function getPriceRangeLabelAttribute()
    {
        $labels = [
            'low' => '₹ (Budget)',
            'medium' => '₹₹ (Moderate)',
            'high' => '₹₹₹ (Expensive)',
            'premium' => '₹₹₹₹ (Premium)',
        ];

        return $labels[$this->price_range] ?? $this->price_range;
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->status === 'active' ? 'badge-success' : 'badge-danger';
    }

    /**
     * Scope to filter by agency name
     */
    public function scopeByAgency($query, $agencyName)
    {
        return $query->where('agency_name', $agencyName);
    }

    /**
     * Scope to filter by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

