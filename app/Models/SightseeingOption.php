<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SightseeingOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'sightseeing_id',
        'name',
        'description',
        'duration_minutes',
        'base_price',
        'currency',
        'default_pax',
        'includes_lunch',
        'includes_transport',
        'availability_note',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'base_price' => 'decimal:2',
        'default_pax' => 'integer',
        'includes_lunch' => 'boolean',
        'includes_transport' => 'boolean',
        'tags' => 'array',
        'is_active' => 'boolean',
    ];

    public function sightseeing()
    {
        return $this->belongsTo(Sightseeing::class);
    }

    public function bookings()
    {
        return $this->hasMany(SightseeingBooking::class, 'sightseeing_option_id');
    }
}

