<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'meal_id',
        'meal_type',
        'meal_price_inr',
        'booking_date',
        'booking_time',
        'guests',
        'guest_name',
        'guest_phone',
        'guests_details',
        'special_requests',
        'status',
        'estimated_total',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'guests' => 'integer',
        'guests_details' => 'array',
        'estimated_total' => 'decimal:2',
        'meal_price_inr' => 'decimal:2',
    ];

    /**
     * Alias for DB column `guests` — number of people for this reservation.
     */
    public function getNumberOfGuestsAttribute(): ?int
    {
        $g = $this->attributes['guests'] ?? null;

        return $g !== null ? (int) $g : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}

