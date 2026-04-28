<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportZone;
use Illuminate\Http\JsonResponse;

class TransportZoneController extends Controller
{
    /**
     * Public list of active zones with polygons and per-vehicle pricing for the transport frontend.
     */
    public function index(): JsonResponse
    {
        $zones = TransportZone::query()
            ->where('status', 'active')
            ->with([
                'transports' => function ($q) {
                    $q->where('status', 'active')
                        ->whereHas('vehicle', fn ($vq) => $vq->where('status', 'active'))
                        ->with('vehicle:id,name,capacity_seats,vehicle_category,image,status');
                },
            ])
            ->orderBy('name')
            ->get();

        $data = $zones->map(fn (TransportZone $z) => $this->serializeZone($z));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Single zone configuration (pricing, polygon) for booking UIs that hold a zone id.
     */
    public function show(string $id): JsonResponse
    {
        $zone = TransportZone::query()
            ->where('status', 'active')
            ->with([
                'transports' => function ($q) {
                    $q->where('status', 'active')
                        ->whereHas('vehicle', fn ($vq) => $vq->where('status', 'active'))
                        ->with('vehicle:id,name,capacity_seats,vehicle_category,image,status');
                },
            ])
            ->find($id);

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Zone not found or inactive.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->serializeZone($zone),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeZone(TransportZone $z): array
    {
        $zoneCurrency = $z->currency ?? 'EUR';

        return [
            'id' => $z->id,
            'name' => $z->name,
            'cities' => $z->cities ?? [],
            'polygon' => $z->polygon,
            'center' => [
                'lat' => $z->default_map_lat !== null ? (float) $z->default_map_lat : null,
                'lng' => $z->default_map_lng !== null ? (float) $z->default_map_lng : null,
            ],
            'currency' => $zoneCurrency,
            'price_per_day' => $z->price_per_day !== null ? (float) $z->price_per_day : null,
            'vehicle_rates' => $z->transports->map(function ($t) use ($zoneCurrency) {
                return [
                    'transport_id' => $t->id,
                    'price_per_km' => $t->price_per_km !== null ? (float) $t->price_per_km : null,
                    'min_charge' => $t->min_charge !== null ? (float) $t->min_charge : null,
                    'currency' => $t->currency ?? $zoneCurrency,
                    'vehicle' => $t->vehicle ? [
                        'id' => $t->vehicle->id,
                        'name' => $t->vehicle->name,
                        'capacity_seats' => $t->vehicle->capacity_seats,
                        'vehicle_category' => $t->vehicle->vehicle_category,
                        'image' => $t->vehicle->image_url,
                    ] : null,
                ];
            })->values(),
        ];
    }
}
