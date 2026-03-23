<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuidePackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'service_type',
        'service_name',
        'duration_hours',
        'standard_price',
        'extra_hour_price',
        'currency',
        'default_start_location',
        'default_end_location',
        'start_point',
        'end_point',
        'start_time',
        'end_time',
        'notes',
        'status',
    ];

    protected $casts = [
        'standard_price' => 'decimal:2',
        'extra_hour_price' => 'decimal:2',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }
}



