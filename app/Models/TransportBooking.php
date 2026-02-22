<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportBooking extends Model
{
    public const TRIP_TYPE_ONE_WAY = 'one_way';
    public const TRIP_TYPE_RETURN = 'return';
    public const TRIP_TYPE_MULTICITY = 'multicity';

    public const STATUS_QUOTE = 'quote';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'trip_type',
        'passengers',
        'cities',
        'days_per_city',
        'legs_by_train',
        'distances_km',
        'airport_transfer_supplement',
        'itinerary_attachment',
        'remarks',
        'quote_breakdown',
        'total_amount',
        'currency',
        'status',
        'guest_name',
        'guest_email',
        'guest_phone',
        'guest_country',
    ];

    protected $casts = [
        'cities' => 'array',
        'days_per_city' => 'array',
        'legs_by_train' => 'array',
        'distances_km' => 'array',
        'airport_transfer_supplement' => 'boolean',
        'quote_breakdown' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
