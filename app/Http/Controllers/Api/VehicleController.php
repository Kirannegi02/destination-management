<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * List active vehicles (for Get Quote / fleet display).
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|string|max:64',
            'min_capacity' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Vehicle::where('status', 'active')->orderBy('sort_order')->orderBy('capacity_seats');

        if (!empty($validated['category'])) {
            $query->where('vehicle_category', $validated['category']);
        }
        if (isset($validated['min_capacity'])) {
            $query->where('capacity_seats', '>=', $validated['min_capacity']);
        }

        $perPage = $validated['per_page'] ?? 50;
        $vehicles = $query->paginate($perPage);

        $items = $vehicles->getCollection()->map(function ($v) {
            return [
                'id' => $v->id,
                'name' => $v->name,
                'vehicle_category' => $v->vehicle_category,
                'category_label' => $v->vehicle_category ? (Vehicle::CATEGORIES[$v->vehicle_category] ?? $v->vehicle_category) : null,
                'capacity_seats' => $v->capacity_seats,
                'description' => $v->description,
                'image' => $v->image_url,
                'currency' => $v->currency ?? 'EUR',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Vehicles retrieved.',
            'data' => $items,
            'pagination' => [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ],
        ], 200);
    }

    /**
     * Single vehicle (for quote page).
     */
    public function show(string $id)
    {
        $vehicle = Vehicle::where('status', 'active')->find($id);
        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found.',
            ], 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'vehicle_category' => $vehicle->vehicle_category,
                'category_label' => $vehicle->vehicle_category ? (Vehicle::CATEGORIES[$vehicle->vehicle_category] ?? $vehicle->vehicle_category) : null,
                'capacity_seats' => $vehicle->capacity_seats,
                'description' => $vehicle->description,
                'image' => $vehicle->image_url,
                'currency' => $vehicle->currency ?? 'EUR',
            ],
        ], 200);
    }
}
