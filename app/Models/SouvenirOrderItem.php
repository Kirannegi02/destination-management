<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SouvenirOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'souvenir_order_id',
        'souvenir_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function souvenirOrder()
    {
        return $this->belongsTo(SouvenirOrder::class, 'souvenir_order_id');
    }

    public function souvenir()
    {
        return $this->belongsTo(Souvenir::class);
    }
}
