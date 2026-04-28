<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    use HasFactory;

    protected $fillable = [
        'transport_zone_id',
        'location',
        'vehicle_id',
        'price_per_km',
        'min_charge',
        'price_per_day',
        'driver_accommodation_per_day',
        'one_way_transfer_price',
        'airport_transfer_supplement',
        'currency',
        'notes',
        'status',
    ];

    protected $casts = [
        'price_per_km' => 'decimal:2',
        'min_charge' => 'decimal:2',
        'price_per_day' => 'decimal:2',
        'driver_accommodation_per_day' => 'decimal:2',
        'one_way_transfer_price' => 'decimal:2',
        'airport_transfer_supplement' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function zone()
    {
        return $this->belongsTo(TransportZone::class, 'transport_zone_id');
    }
}
