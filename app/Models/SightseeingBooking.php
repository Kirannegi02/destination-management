<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SightseeingBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sightseeing_id',
        'sightseeing_option_id',
        'booking_date',
        'pax_count',
        'price',
        'currency',
        'guest_name',
        'guest_phone',
        'guests_details',
        'special_requests',
        'booking_conditions_snapshot',
        'status',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'pax_count' => 'integer',
        'price' => 'decimal:2',
        'guests_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sightseeing()
    {
        return $this->belongsTo(Sightseeing::class);
    }

    public function sightseeingOption()
    {
        return $this->belongsTo(SightseeingOption::class, 'sightseeing_option_id');
    }
}
