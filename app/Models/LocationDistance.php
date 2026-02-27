<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationDistance extends Model
{
    protected $fillable = [
        'from_location',
        'to_location',
        'distance_km',
        'notes',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
    ];

    /**
     * Get distance in km between two locations (order-agnostic).
     */
    public static function getDistance(string $from, string $to): ?float
    {
        $from = trim($from);
        $to = trim($to);
        if ($from === '' || $to === '') {
            return null;
        }
        $row = self::where('from_location', $from)->where('to_location', $to)->first();
        if ($row) {
            return (float) $row->distance_km;
        }
        $row = self::where('from_location', $to)->where('to_location', $from)->first();
        return $row ? (float) $row->distance_km : null;
    }
}
