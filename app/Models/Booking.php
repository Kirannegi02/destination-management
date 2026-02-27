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
        'check_in',
        'check_out',
        'rooms',
        'guests',
        'guest_name',
        'guest_phone',
        'guests_details',
        'special_requests',
        'status',
        'estimated_total',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'rooms' => 'integer',
        'guests' => 'integer',
        'guests_details' => 'array',
        'estimated_total' => 'decimal:2',
        'meal_price_inr' => 'decimal:2',
    ];

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

