<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuideBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'guide_id',
        'guide_package_id',
        'service_date',
        'start_time',
        'calculated_end_time',
        'duration_hours',
        'guests',
        'start_location',
        'end_location',
        'start_time_slot',
        'status',
        'special_requests',
        'price',
        'currency',
        'estimated_total',
        'contact_name',
        'contact_phone',
        'contact_email',
        'metadata',
    ];

    protected $casts = [
        'service_date' => 'date',
        'start_time' => 'datetime:H:i',
        'calculated_end_time' => 'datetime:H:i',
        'duration_hours' => 'integer',
        'guests' => 'integer',
        'price' => 'decimal:2',
        'estimated_total' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }

    public function package()
    {
        return $this->belongsTo(GuidePackage::class, 'guide_package_id');
    }
}



