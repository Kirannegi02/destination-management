<?php

namespace App\Services;

use App\Models\LocationDistance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistanceApiService
{
    /**
     * Get distance in km between two locations (addresses or place names).
     * Order: 1) Google (if key set), 2) OSRM+Nominatim (free, no key), 3) LocationDistance table, 4) test fallback distance.
     */
    public function getDistanceKm(string $origin, string $destination): ?float
    {
        $origin = trim($origin);
        $destination = trim($destination);
        if ($origin === '' || $destination === '') {
            return null;
        }
        if (strcasecmp($origin, $destination) === 0) {
            return 0.0;
        }

        $key = config('services.google.maps_api_key');
        if (!empty($key)) {
            $km = $this->fetchFromGoogle($origin, $destination, $key);
            if ($km !== null) {
                return $km;
            }
        }

        $km = $this->fetchFromOsm($origin, $destination);
        if ($km !== null) {
            return $km;
        }

        $km = $this->getFromLocationDistance($origin, $destination);
        if ($km !== null) {
            return $km;
        }

        return $this->getTestFallbackDistance();
    }

    /**
     * When no API key and no DB row: return a test distance so quotes work in development.
     */
    protected function getTestFallbackDistance(): float
    {
        $km = config('services.transport.fallback_distance_km', 100);
        return max(0.1, (float) $km);
    }

    /**
     * Call Google Distance Matrix API. Returns distance in km or null on failure.
     */
    protected function fetchFromGoogle(string $origin, string $destination, string $apiKey): ?float
    {
        $url = 'https://maps.googleapis.com/maps/api/distancematrix/json';
        $params = [
            'origins' => $origin,
            'destinations' => $destination,
            'key' => $apiKey,
            'units' => 'metric',
        ];

        try {
            $response = Http::timeout(10)->get($url, $params);
            $data = $response->json();
            if (!$response->successful() || empty($data)) {
                return null;
            }
            $status = $data['status'] ?? '';
            if ($status !== 'OK') {
                Log::debug('Distance API status not OK', ['status' => $status, 'origin' => $origin, 'destination' => $destination]);
                return null;
            }
            $rows = $data['rows'] ?? [];
            $element = $rows[0]['elements'][0] ?? null;
            if (!$element) {
                return null;
            }
            $elementStatus = $element['status'] ?? '';
            if ($elementStatus !== 'OK') {
                Log::debug('Distance API element status not OK', ['status' => $elementStatus]);
                return null;
            }
            $meters = (float) ($element['distance']['value'] ?? 0);
            return round($meters / 1000, 2);
        } catch (\Throwable $e) {
            Log::warning('Distance API request failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Free, no API key: geocode with Nominatim (OSM) then get driving distance with OSRM.
     */
    protected function fetchFromOsm(string $origin, string $destination): ?float
    {
        try {
            $coords1 = $this->geocodeNominatim($origin);
            $coords2 = $this->geocodeNominatim($destination);
            if ($coords1 === null || $coords2 === null) {
                return null;
            }
            return $this->getDistanceOsm($coords1[0], $coords1[1], $coords2[0], $coords2[1]);
        } catch (\Throwable $e) {
            Log::debug('OSM distance failed', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Geocode address to [lat, lon] using Nominatim (OpenStreetMap). No API key required.
     */
    protected function geocodeNominatim(string $address): ?array
    {
        $url = 'https://nominatim.openstreetmap.org/search';
        $response = Http::timeout(8)
            ->withHeaders(['User-Agent' => config('app.name', 'DMC') . ' Transport/1.0'])
            ->get($url, [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);
        $data = $response->json();
        if (!is_array($data) || empty($data)) {
            return null;
        }
        $lat = isset($data[0]['lat']) ? (float) $data[0]['lat'] : null;
        $lon = isset($data[0]['lon']) ? (float) $data[0]['lon'] : null;
        return ($lat !== null && $lon !== null) ? [$lat, $lon] : null;
    }

    /**
     * Get driving distance in km between two points using OSRM public demo. No API key required.
     */
    protected function getDistanceOsm(float $lat1, float $lon1, float $lat2, float $lon2): ?float
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%s,%s;%s,%s',
            $lon1,
            $lat1,
            $lon2,
            $lat2
        );
        $response = Http::timeout(8)->get($url, ['overview' => 'false']);
        $data = $response->json();
        $distance = $data['routes'][0]['distance'] ?? null;
        if ($distance === null || !is_numeric($distance)) {
            return null;
        }
        return round((float) $distance / 1000, 2);
    }

    protected function getFromLocationDistance(string $from, string $to): ?float
    {
        return LocationDistance::getDistance($from, $to);
    }
}
