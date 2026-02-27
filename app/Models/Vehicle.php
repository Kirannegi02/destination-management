<?php

namespace App\Models;

use App\Services\ImageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'vehicle_category',
        'capacity_seats',
        'description',
        'image',
        'currency',
        'status',
        'sort_order',
    ];

    /**
     * Full URL for the vehicle image (for API and views). Storage path is served via /api/images/{path}.
     */
    public function getImageUrlAttribute(): ?string
    {
        return ImageService::getUrl($this->image);
    }

    /** Fleet categories for admin dropdown (VAN, 16 Seater, 19 Seater, Full Size Coach, Luxury Cars) */
    public const CATEGORIES = [
        'van' => 'VAN',
        '16_seater' => '16 Seater',
        '19_seater' => '19 Seater',
        'full_size_coach' => 'Full Size Coach',
        'luxury_cars' => 'Luxury Cars',
    ];

    public function transports()
    {
        return $this->hasMany(Transport::class);
    }
}
