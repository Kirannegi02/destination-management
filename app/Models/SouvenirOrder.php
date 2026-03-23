<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SouvenirOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_address_id',
        'requested_delivery_date',
        'delivery_location',
        'expected_delivery_at',
        'delivery_too_close',
        'subtotal',
        'shipping_cost',
        'total',
        'currency',
        'within_city',
        'status',
        'pending_restock',
        'partial_stock_summary',
        'notes',
    ];

    protected $casts = [
        'requested_delivery_date' => 'date',
        'expected_delivery_at' => 'datetime',
        'delivery_too_close' => 'boolean',
        'within_city' => 'boolean',
        'pending_restock' => 'boolean',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userAddress()
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function items()
    {
        return $this->hasMany(SouvenirOrderItem::class, 'souvenir_order_id');
    }
}
