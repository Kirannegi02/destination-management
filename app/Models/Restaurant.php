<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_name',
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
        'price',
        'amenities',
        'cuisine_type',
        'opening_hours',
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
        'price' => 'decimal:2',
    ];

    /**
     * Get formatted price for display (e.g., ₹1,234.00)
     */
    public function getPriceFormattedAttribute()
    {
        if ($this->price === null) {
            return null;
        }

        return '₹' . number_format((float) $this->price, 2);
    }

    /**
     * Backward-compatible alias for legacy views expecting price_range_label
     */
    public function getPriceRangeLabelAttribute()
    {
        return $this->price_formatted;
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->status === 'active' ? 'badge-success' : 'badge-danger';
    }

    /**
     * Scope to filter by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the meals for the restaurant.
     */
    public function meals()
    {
        return $this->hasMany(Meal::class);
    }

    /**
     * Get active meals for the restaurant.
     */
    public function activeMeals()
    {
        return $this->hasMany(Meal::class)->where('status', 'active');
    }
}

