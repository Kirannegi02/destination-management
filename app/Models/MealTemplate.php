<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealTemplate extends Model
{
    protected $fillable = [
        'meal_type',
        'menu_description',
        'supplements',
        'status',
        'display_order',
    ];

    protected $casts = [
        'supplements' => 'array',
        'display_order' => 'integer',
    ];
}
