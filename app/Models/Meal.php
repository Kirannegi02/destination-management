<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'meal_type',
        'menu_description',
        'price_inr',
        'local_currency',
        'local_price',
        'supplements',
        'status',
        'display_order',
    ];

    protected $casts = [
        'supplements' => 'array',
        'price_inr' => 'decimal:2',
        'local_price' => 'decimal:2',
        'display_order' => 'integer',
    ];

    /**
     * Get the restaurant that owns the meal.
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Get meal type label
     */
    public function getMealTypeLabelAttribute()
    {
        $labels = [
            'standard_buffet_lunch' => 'Standard Buffet Lunch',
            'standard_buffet_dinner' => 'Standard Buffet Dinner',
            'premium_buffet_lunch' => 'Premium Buffet Lunch',
            'premium_buffet_dinner' => 'Premium Buffet Dinner',
            'cocktail_dinner_without_liquor' => 'Cocktail Dinner without Liquor',
            'cocktail_dinner_with_liquor' => 'Cocktail Dinner with Liquor',
        ];

        return $labels[$this->meal_type] ?? $this->meal_type;
    }

    /**
     * Get formatted price in INR
     */
    public function getPriceInrFormattedAttribute()
    {
        if ($this->price_inr === null) {
            return null;
        }

        return '₹' . number_format((float) $this->price_inr, 2);
    }

    /**
     * Get formatted local price
     */
    public function getLocalPriceFormattedAttribute()
    {
        if ($this->local_price === null || $this->local_currency === null) {
            return null;
        }

        return $this->local_currency . ' ' . number_format((float) $this->local_price, 2);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        return $this->status === 'active' ? 'badge-success' : 'badge-danger';
    }

    /**
     * Scope to filter by status
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by restaurant
     */
    public function scopeForRestaurant($query, $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    /**
     * Get all meal types
     */
    public static function getMealTypes()
    {
        return [
            'standard_buffet_lunch' => 'Standard Buffet Lunch',
            'standard_buffet_dinner' => 'Standard Buffet Dinner',
            'premium_buffet_lunch' => 'Premium Buffet Lunch',
            'premium_buffet_dinner' => 'Premium Buffet Dinner',
            'cocktail_dinner_without_liquor' => 'Cocktail Dinner without Liquor',
            'cocktail_dinner_with_liquor' => 'Cocktail Dinner with Liquor',
        ];
    }
}
