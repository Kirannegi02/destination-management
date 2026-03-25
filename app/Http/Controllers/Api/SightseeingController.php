<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sightseeing;
use App\Models\SightseeingOption;
use Illuminate\Http\Request;
use App\Services\ImageService;

class SightseeingController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'featured' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Sightseeing::with(['options' => function ($q) {
            $q->where('is_active', true)->orderBy('id');
        }])->where('status', 'active');

        if (!empty($validated['country'])) {
            $query->where('country', $validated['country']);
        }
        if (!empty($validated['city'])) {
            $query->where('city', $validated['city']);
        }
        if (isset($validated['featured'])) {
            $query->where('is_featured', (bool) $validated['featured']);
        }
        $sightseeings = $query
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $sightseeings->getCollection()->transform(fn($s) => $this->transform($s));

        return response()->json([
            'success' => true,
            'data' => $sightseeings->items(),
            'pagination' => [
                'current_page' => $sightseeings->currentPage(),
                'last_page' => $sightseeings->lastPage(),
                'per_page' => $sightseeings->perPage(),
                'total' => $sightseeings->total(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $sightseeing = Sightseeing::with(['options' => function ($q) {
            $q->where('is_active', true)->orderBy('id');
        }])->where('status', 'active')->find($id);

        if (!$sightseeing) {
            return response()->json([
                'success' => false,
                'message' => 'Sightseeing not found or inactive.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($sightseeing),
        ]);
    }

    /**
     * Get price and availability for a sightseeing (and optional option) for a given date and pax count.
     * Use this after the user selects date and pax to show exact pricing and booking conditions.
     */
    public function priceAvailability(Request $request, string $id)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'pax_count' => 'required|integer|min:1',
            'sightseeing_option_id' => 'required|integer|exists:sightseeing_options,id',
        ]);

        $sightseeing = Sightseeing::where('status', 'active')->find($id);
        if (!$sightseeing) {
            return response()->json([
                'success' => false,
                'message' => 'Sightseeing not found or inactive.',
            ], 404);
        }

        $option = SightseeingOption::where('sightseeing_id', $sightseeing->id)
            ->where('is_active', true)
            ->find($validated['sightseeing_option_id']);
        if (!$option) {
            return response()->json([
                'success' => false,
                'message' => 'Selected sightseeing option not found or inactive.',
            ], 404);
        }

        $paxCount = (int) $validated['pax_count'];
        $currency = $option->currency ?? $sightseeing->currency;
        $basePrice = $option->base_price;
        $pricePerPax = $basePrice !== null ? (float) $basePrice : null;
        $totalPrice = $pricePerPax !== null ? $pricePerPax * $paxCount : null;

        $data = [
            'sightseeing_id' => $sightseeing->id,
            'sightseeing_title' => $sightseeing->title,
            'sightseeing_option_id' => $option->id,
            'sightseeing_option_name' => $option->name,
            'date' => $validated['date'],
            'pax_count' => $paxCount,
            'price_per_pax' => $pricePerPax,
            'total_price' => $totalPrice,
            'currency' => $currency ?? 'CHF',
            'available' => true,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function transform(Sightseeing $sightseeing): array
    {
        return [
            'id' => $sightseeing->id,
            'title' => $sightseeing->title,
            'description' => $sightseeing->description,
            'country' => $sightseeing->country,
            'city' => $sightseeing->city,
            'start_location' => $sightseeing->start_location,
            'end_location' => $sightseeing->end_location,
            'is_featured' => (bool) $sightseeing->is_featured,
            'status' => $sightseeing->status,
            'image_url' => $sightseeing->image ? ImageService::getUrl($sightseeing->image) : null,
            'options' => $sightseeing->options->map(function ($opt) use ($sightseeing) {
                return [
                    'id' => $opt->id,
                    'name' => $opt->name,
                    'description' => $opt->description,
                    'duration_minutes' => $opt->duration_minutes,
                    'base_price' => $opt->base_price !== null ? (float) $opt->base_price : null,
                    'currency' => $opt->currency ?? $sightseeing->currency,
                    'includes_lunch' => (bool) $opt->includes_lunch,
                    'includes_transport' => (bool) $opt->includes_transport,
                    'availability_note' => $opt->availability_note,
                    'tags' => $opt->tags ?? [],
                    'is_active' => (bool) $opt->is_active,
                ];
            })->values(),
        ];
    }
}


