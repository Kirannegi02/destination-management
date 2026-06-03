<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateVenue;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrivateVenueController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'venue_type' => ['nullable', Rule::in(array_keys(PrivateVenue::venueTypes()))],
            'event_type' => 'nullable|string|max:50',
            'min_guests' => 'nullable|integer|min:1',
            'max_guests' => 'nullable|integer|min:1',
            'featured' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PrivateVenue::query()
            ->with(['activeSpaces'])
            ->where('status', 'active');

        if (! empty($validated['city'])) {
            $query->where('city', 'like', '%'.$validated['city'].'%');
        }
        if (! empty($validated['country'])) {
            $query->where('country', 'like', '%'.$validated['country'].'%');
        }
        if (! empty($validated['venue_type'])) {
            $query->where('venue_type', $validated['venue_type']);
        }
        if (! empty($validated['event_type'])) {
            $query->whereJsonContains('event_types', $validated['event_type']);
        }
        if (isset($validated['min_guests'])) {
            $query->where(function ($q) use ($validated) {
                $q->whereNull('max_event_size')
                    ->orWhere('max_event_size', '>=', $validated['min_guests']);
            });
        }
        if (isset($validated['max_guests'])) {
            $query->where(function ($q) use ($validated) {
                $q->whereNull('min_event_size')
                    ->orWhere('min_event_size', '<=', $validated['max_guests']);
            });
        }
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }
        if (! empty($validated['search'])) {
            $s = $validated['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")
                    ->orWhere('brand_chain', 'like', "%{$s}%");
            });
        }

        $venues = $query->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 15);

        $venues->getCollection()->transform(fn ($v) => $this->transformVenue($v));

        return response()->json([
            'success' => true,
            'message' => 'Private venues retrieved successfully.',
            'data' => $venues->items(),
            'pagination' => [
                'current_page' => $venues->currentPage(),
                'last_page' => $venues->lastPage(),
                'per_page' => $venues->perPage(),
                'total' => $venues->total(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $venue = PrivateVenue::with(['spaces' => fn ($q) => $q->where('status', 'active')->orderByDesc('sort_order')])
            ->where('status', 'active')
            ->find($id);

        if (! $venue) {
            return response()->json([
                'success' => false,
                'message' => 'Venue not found or unavailable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformVenue($venue, true),
        ]);
    }

    public function filterOptions()
    {
        $base = PrivateVenue::query()->where('status', 'active');

        return response()->json([
            'success' => true,
            'data' => [
                'cities' => (clone $base)->whereNotNull('city')->distinct()->orderBy('city')->pluck('city')->values(),
                'countries' => (clone $base)->whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->values(),
                'venue_types' => PrivateVenue::venueTypes(),
                'event_types' => PrivateVenue::eventTypesList(),
                'setup_styles' => PrivateVenue::setupTypes(),
            ],
        ]);
    }

    private function transformVenue(PrivateVenue $venue, bool $detailed = false): array
    {
        $images = collect($venue->images ?? [])->map(fn ($p) => ImageService::getUrl($p))->filter()->values()->all();

        $data = [
            'id' => $venue->id,
            'name' => $venue->name,
            'slug' => $venue->slug,
            'venue_type' => $venue->venue_type,
            'venue_type_label' => $venue->venue_type_label,
            'brand_chain' => $venue->brand_chain,
            'description' => $venue->description,
            'highlights' => $venue->highlights,
            'city' => $venue->city,
            'state' => $venue->state,
            'country' => $venue->country,
            'location_label' => $venue->location_label,
            'address' => $venue->address,
            'star_rating' => $venue->star_rating,
            'min_event_size' => $venue->min_event_size,
            'max_event_size' => $venue->max_event_size,
            'total_meeting_space_sqm' => $venue->total_meeting_space_sqm ? (float) $venue->total_meeting_space_sqm : null,
            'largest_room_capacity' => $venue->largest_room_capacity,
            'number_of_meeting_rooms' => $venue->number_of_meeting_rooms,
            'sleeping_rooms' => $venue->sleeping_rooms,
            'starting_daily_rate' => $venue->starting_daily_rate ? (float) $venue->starting_daily_rate : null,
            'starting_rate_formatted' => $venue->starting_rate_formatted,
            'currency' => $venue->currency,
            'amenities' => $venue->amenities ?? [],
            'event_types' => $venue->event_types ?? [],
            'is_featured' => $venue->is_featured,
            'image' => $images[0] ?? null,
            'images' => $images,
            'meeting_rooms_count' => $venue->relationLoaded('activeSpaces')
                ? $venue->activeSpaces->count()
                : $venue->activeSpaces()->count(),
        ];

        if ($detailed) {
            $data['phone'] = $venue->phone;
            $data['email'] = $venue->email;
            $data['website'] = $venue->website;
            $data['pricing_notes'] = $venue->pricing_notes;
            $data['latitude'] = $venue->latitude;
            $data['longitude'] = $venue->longitude;
            $data['video'] = $venue->video && str_starts_with($venue->video, 'http')
                ? $venue->video
                : ($venue->video ? ImageService::getUrl($venue->video) : null);
            $data['meeting_spaces'] = $venue->spaces->map(fn ($s) => $this->transformSpace($s))->values()->all();
        }

        return $data;
    }

    private function transformSpace($space): array
    {
        return [
            'id' => $space->id,
            'name' => $space->name,
            'description' => $space->description,
            'total_space_sqm' => $space->total_space_sqm ? (float) $space->total_space_sqm : null,
            'dimensions_label' => $space->dimensions_label,
            'length_m' => $space->length_m ? (float) $space->length_m : null,
            'width_m' => $space->width_m ? (float) $space->width_m : null,
            'ceiling_height_m' => $space->ceiling_height_m ? (float) $space->ceiling_height_m : null,
            'setup_capacities' => $space->setup_capacities ?? [],
            'max_capacity' => $space->max_capacity,
            'amenities' => $space->amenities ?? [],
            'is_outdoor' => $space->is_outdoor,
            'is_private' => $space->is_private,
            'is_semi_private' => $space->is_semi_private,
            'wheelchair_accessible' => $space->wheelchair_accessible,
        ];
    }
}
