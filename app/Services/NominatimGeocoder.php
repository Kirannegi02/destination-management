<?php

namespace App\Services;

use App\Models\TransportZone;
use Illuminate\Support\Facades\Http;

class NominatimGeocoder
{
    protected static function userAgent(): string
    {
        return (config('app.name') ?: 'DMS') . ' DMS-Transport/1.0';
    }

    protected static function throttle(): void
    {
        usleep(1_100_000);
    }

    /**
     * @return list<array{lat: float, lng: float, label: string}>
     */
    public static function searchPlaces(string $query, int $limit = 6): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        self::throttle();

        $res = Http::withHeaders([
            'User-Agent' => self::userAgent(),
        ])->timeout(14)->get('https://nominatim.openstreetmap.org/search', [
            'format' => 'json',
            'q' => $query,
            'limit' => $limit,
            'addressdetails' => 1,
        ]);

        if (!$res->successful()) {
            return [];
        }

        $out = [];
        foreach ($res->json() ?? [] as $row) {
            if (!isset($row['lat'], $row['lon'])) {
                continue;
            }
            $out[] = [
                'lat' => (float) $row['lat'],
                'lng' => (float) $row['lon'],
                'label' => (string) ($row['display_name'] ?? ''),
            ];
        }

        return $out;
    }

    public static function reverseCityName(float $lat, float $lng): ?string
    {
        self::throttle();

        $res = Http::withHeaders([
            'User-Agent' => self::userAgent(),
        ])->timeout(14)->get('https://nominatim.openstreetmap.org/reverse', [
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lng,
            'accept-language' => 'en',
        ]);

        if (!$res->successful()) {
            return null;
        }

        $j = $res->json();
        $addr = $j['address'] ?? [];

        $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['municipality']
            ?? $addr['suburb'] ?? $addr['city_district'] ?? null;

        if ($city) {
            return trim((string) $city);
        }

        $name = $j['name'] ?? null;
        if ($name && is_string($name)) {
            return trim($name);
        }

        return null;
    }

    /**
     * Sample a GeoJSON Polygon / MultiPolygon and reverse-geocode up to $maxPoints distinct place names.
     *
     * @param  array<string, mixed>|null  $geometry  GeoJSON geometry (type + coordinates)
     * @return list<string>
     */
    public static function suggestedLocalityNamesFromPolygon(?array $geometry, int $maxPoints = 6): array
    {
        if (!$geometry || empty($geometry['type']) || empty($geometry['coordinates'])) {
            return [];
        }

        $ring = [];
        if ($geometry['type'] === 'Polygon') {
            $ring = $geometry['coordinates'][0] ?? [];
        } elseif ($geometry['type'] === 'MultiPolygon') {
            $ring = $geometry['coordinates'][0][0] ?? [];
        }
        if (count($ring) < 3) {
            return [];
        }

        if ((float) $ring[0][0] === (float) $ring[count($ring) - 1][0]
            && (float) $ring[0][1] === (float) $ring[count($ring) - 1][1]) {
            array_pop($ring);
        }

        $zone = new TransportZone(['polygon' => $geometry]);
        $samples = [];

        $n = count($ring);
        $sumLat = 0.0;
        $sumLng = 0.0;
        foreach ($ring as $pt) {
            $sumLng += (float) $pt[0];
            $sumLat += (float) $pt[1];
        }
        $cLat = $sumLat / $n;
        $cLng = $sumLng / $n;
        if ($zone->containsLatLng($cLat, $cLng)) {
            $samples[] = [$cLat, $cLng];
        }

        $step = max(1, (int) ceil($n / max(1, $maxPoints - 1)));
        for ($i = 0; $i < $n && count($samples) < $maxPoints; $i += $step) {
            $lng = (float) $ring[$i][0];
            $lat = (float) $ring[$i][1];
            $samples[] = [$lat, $lng];
        }

        $uniqCoordKey = [];
        $names = [];
        foreach ($samples as [$lat, $lng]) {
            $key = round($lat, 4) . ',' . round($lng, 4);
            if (isset($uniqCoordKey[$key])) {
                continue;
            }
            $uniqCoordKey[$key] = true;
            $nm = self::reverseCityName($lat, $lng);
            if ($nm && !in_array($nm, $names, true)) {
                $names[] = $nm;
            }
            if (count($names) >= $maxPoints) {
                break;
            }
        }

        return $names;
    }
}
