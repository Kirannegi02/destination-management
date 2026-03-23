<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Souvenir extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'currency',
        'min_order_quantity',
        'city',
        'latitude',
        'longitude',
        'stock',
        'country',
        'images',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'price' => 'decimal:2',
        'min_order_quantity' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'stock' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'badge-success',
            'pending' => 'badge-warning',
            default => 'badge-danger',
        };
    }

    public function orderItems()
    {
        return $this->hasMany(SouvenirOrderItem::class, 'souvenir_id');
    }

    /**
     * Global minimum purchase (config) vs per-product minimum — whichever is higher.
     */
    public function effectiveMinOrderQuantity(): int
    {
        return max(
            (int) config('souvenir.min_purchase_quantity', 10),
            (int) $this->min_order_quantity
        );
    }

    /**
     * True when stock is tracked and there are enough units for at least one minimum order.
     */
    public function canFulfillMinimumOrder(): bool
    {
        if ($this->stock === null) {
            return true;
        }

        return $this->stock >= $this->effectiveMinOrderQuantity();
    }

    /**
     * True when stock is tracked but below the minimum order size (admin should restock).
     */
    public function stockBelowMinimumOrder(): bool
    {
        if ($this->stock === null) {
            return false;
        }

        return $this->stock < $this->effectiveMinOrderQuantity();
    }
}
