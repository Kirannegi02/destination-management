<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity_seats',
        'description',
        'default_price_per_km',
        'currency',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'default_price_per_km' => 'decimal:2',
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }
}
