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
        'image',
        'is_featured',
        'status',
        'created_by',
    ];

    protected $casts = [
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

