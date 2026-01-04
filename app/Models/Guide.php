<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'country',
        'city',
        'language',
        'service_date',
        'start_point',
        'end_point',
        'start_time',
        'end_time',
        'duration_hours',
        'price',
        'status',
        'notes',
    ];

    protected $casts = [
        'service_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'price' => 'decimal:2',
    ];
}


