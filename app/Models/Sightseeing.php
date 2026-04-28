<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sightseeing extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'country',
        'city',
        'start_location',
        'end_location',
        'standard_price',
        'currency',
        'default_pax',
        'standard_price_note',
        'availability_notes',
        'booking_conditions',
        'detail_page_note',
        'image',
        'requires_date',
        'requires_pax',
        'is_featured',
        'display_order',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_featured'      => 'boolean',
        'requires_date'    => 'boolean',
        'requires_pax'     => 'boolean',
    ];

    public function options()
    {
        return $this->hasMany(SightseeingOption::class);
    }

    public function bookings()
    {
        return $this->hasMany(SightseeingBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

