<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sightseeing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ImageService;

class SightseeingController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'featured' => 'nullable|boolean',
            'requires_date' => 'nullable|boolean',
            'requires_pax' => 'nullable|boolean',
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
        if (isset($validated['requires_date'])) {
            $query->where('requires_date', (bool) $validated['requires_date']);
        }
        if (isset($validated['requires_pax'])) {
            $query->where('requires_pax', (bool) $validated['requires_pax']);
        }

        $sightseeings = $query
            ->orderBy('display_order', 'asc')
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
            'standard_price' => $sightseeing->standard_price !== null ? (float) $sightseeing->standard_price : null,
            'currency' => $sightseeing->currency,
            'default_pax' => $sightseeing->default_pax,
            'standard_price_note' => $sightseeing->standard_price_note,
            'requires_date' => (bool) $sightseeing->requires_date,
            'requires_pax' => (bool) $sightseeing->requires_pax,
            'is_featured' => (bool) $sightseeing->is_featured,
            'status' => $sightseeing->status,
            'display_order' => $sightseeing->display_order,
            'image_url' => $sightseeing->image ? ImageService::getUrl($sightseeing->image) : null,
            'options' => $sightseeing->options->map(function ($opt) use ($sightseeing) {
                return [
                    'id' => $opt->id,
                    'name' => $opt->name,
                    'description' => $opt->description,
                    'duration_minutes' => $opt->duration_minutes,
                    'base_price' => $opt->base_price !== null ? (float) $opt->base_price : null,
                    'currency' => $opt->currency ?? $sightseeing->currency,
                    'default_pax' => $opt->default_pax ?? $sightseeing->default_pax,
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


