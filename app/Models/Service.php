<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'location',
        'city',
        'country',
        'price',
        'currency',
        'capacity',
        'images',
        'features',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'images' => 'array',
        'features' => 'array',
        'price' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($service) {
            if (empty($service->slug)) {
                $service->slug = Str::slug($service->name);
            }
        });
    }

    /**
     * Get service types with labels.
     */
    public static function getTypes()
    {
        return [
            'restaurant' => 'Restaurants',
            'guide' => 'Guides',
            'sightseeing' => 'Sightseeing',
            'transport' => 'Transport',
            'souvenir' => 'Souvenirs',
            'private_venue' => 'Private Venues',
            'catering' => 'Catering',
            'train' => 'Trains',
        ];
    }

    /**
     * Get type label.
     */
    public function getTypeLabelAttribute()
    {
        return self::getTypes()[$this->type] ?? ucfirst($this->type);
    }
}
