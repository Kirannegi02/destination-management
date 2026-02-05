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
        'currency',
        'status',
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }
}
