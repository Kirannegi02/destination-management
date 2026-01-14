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
        'includes_lunch',
        'includes_dinner',
        'description',
        'standard_price',
        'extra_hour_price',
        'currency',
        'active',
    ];

    protected $casts = [
        'includes_lunch' => 'boolean',
        'includes_dinner' => 'boolean',
        'active' => 'boolean',
        'standard_price' => 'decimal:2',
        'extra_hour_price' => 'decimal:2',
    ];

    public function guide()
    {
        return $this->belongsTo(Guide::class);
    }
}



