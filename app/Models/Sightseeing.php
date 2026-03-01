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
        'image',
        'requires_date',
        'requires_pax',
        'is_featured',
        'status',
        'display_order',
        'created_by',
    ];

    protected $casts = [
        'standard_price' => 'decimal:2',
        'default_pax' => 'integer',
        'requires_date' => 'boolean',
        'requires_pax' => 'boolean',
        'is_featured' => 'boolean',
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

