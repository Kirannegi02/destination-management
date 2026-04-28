<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cities',
        'polygon',
        'default_map_lat',
        'default_map_lng',
        'currency',
        'price_per_day',
        'notes',
        'status',
    ];

    protected $casts = [
        'cities' => 'array',
        'polygon' => 'array',
        'default_map_lat' => 'decimal:7',
        'default_map_lng' => 'decimal:7',
        'price_per_day' => 'decimal:2',
    ];

    public function transports(): HasMany
    {
        return $this->hasMany(Transport::class, 'transport_zone_id');
    }

    /**
     * Resolve zone from a free-text city / place name (matches zone cities list).
     */
    public static function findActiveForCity(string $city): ?self
    {
        $city = trim($city);
        if ($city === '') {
            return null;
        }

        $zones = static::where('status', 'active')->get();
        $exact = null;
        $fuzzy = null;
        $fuzzyLen = 0;

        foreach ($zones as $zone) {
            foreach ($zone->cities ?? [] as $zoneCity) {
                $zoneCity = trim((string) $zoneCity);
                if ($zoneCity === '') {
                    continue;
                }
                if (strcasecmp($city, $zoneCity) === 0) {
                    $exact = $zone;
                    break 2;
                }
                if (stripos($city, $zoneCity) !== false || stripos($zoneCity, $city) !== false) {
                    $len = strlen($zoneCity);
                    if ($len > $fuzzyLen) {
                        $fuzzyLen = $len;
                        $fuzzy = $zone;
                    }
                }
            }
        }

        return $exact ?? $fuzzy;
    }

    /**
     * Point-in-polygon test for GeoJSON Polygon / MultiPolygon (coordinates as [lng, lat]).
     */
    public function containsLatLng(float $lat, float $lng): bool
    {
        $poly = $this->polygon;
        if (empty($poly['type']) || empty($poly['coordinates'])) {
            return false;
        }

        if ($poly['type'] === 'Polygon') {
            return $this->ringContainsPoint($poly['coordinates'][0] ?? [], $lat, $lng);
        }

        if ($poly['type'] === 'MultiPolygon') {
            foreach ($poly['coordinates'] as $polygon) {
                $ring = $polygon[0] ?? [];
                if ($this->ringContainsPoint($ring, $lat, $lng)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $ring
     */
    protected function ringContainsPoint(array $ring, float $lat, float $lng): bool
    {
        if (count($ring) < 3) {
            return false;
        }

        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = (float) $ring[$i][0];
            $yi = (float) $ring[$i][1];
            $xj = (float) $ring[$j][0];
            $yj = (float) $ring[$j][1];
            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi + 1e-12) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }
}
