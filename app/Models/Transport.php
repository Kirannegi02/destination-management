<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    use HasFactory;

    protected $fillable = [
        'location',
        'vehicle_id',
        'price_per_km',
        'min_charge',
        'notes',
        'status',
    ];

    protected $casts = [
        'price_per_km' => 'decimal:2',
        'min_charge' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
