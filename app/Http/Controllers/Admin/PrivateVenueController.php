<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrivateVenue;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PrivateVenueController extends Controller
{
    public function index(Request $request)
    {
        $query = PrivateVenue::query()->withCount('spaces');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('brand_chain', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('venue_type')) {
            $query->where('venue_type', $request->venue_type);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        $venues = $query->orderByDesc('is_featured')
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        $cities = PrivateVenue::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city');
        $allCount = PrivateVenue::count();
        $activeCount = PrivateVenue::where('status', 'active')->count();
        $inactiveCount = PrivateVenue::where('status', 'inactive')->count();
        $pendingCount = PrivateVenue::where('status', 'pending')->count();

        return view('admin.private-venues.index', compact(
            'venues',
            'cities',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    public function create()
    {
        $venue = null;
        $spaces = collect();

        return view('admin.private-venues.create', [
            'venue' => $venue,
            'spaces' => $spaces,
            'venueTypes' => PrivateVenue::venueTypes(),
            'eventTypesList' => PrivateVenue::eventTypesList(),
            'venueAmenitiesList' => PrivateVenue::venueAmenitiesList(),
            'setupTypes' => PrivateVenue::setupTypes(),
            'spaceAmenitiesList' => PrivateVenue::spaceAmenitiesList(),
        ]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $data = $this->validateVenue($request);
            $data = $this->mergeVenueMedia($request, $data, null);
            $data = $this->mergeCheckboxArrays($request, $data);

            $venue = PrivateVenue::create($data);
            $this->syncSpaces($venue, $request);
        });

        return redirect()
            ->route('admin.private-venues.index')
            ->with('success', 'Private venue created successfully.');
    }

    public function show(string $private_venue)
    {
        $venue = PrivateVenue::with('spaces')->findOrFail($private_venue);

        return view('admin.private-venues.show', [
            'venue' => $venue,
            'venueTypes' => PrivateVenue::venueTypes(),
            'eventTypesList' => PrivateVenue::eventTypesList(),
            'venueAmenitiesList' => PrivateVenue::venueAmenitiesList(),
            'setupTypes' => PrivateVenue::setupTypes(),
            'spaceAmenitiesList' => PrivateVenue::spaceAmenitiesList(),
        ]);
    }

    public function edit(string $private_venue)
    {
        $venue = PrivateVenue::with('spaces')->findOrFail($private_venue);

        return view('admin.private-venues.edit', [
            'venue' => $venue,
            'spaces' => $venue->spaces,
            'venueTypes' => PrivateVenue::venueTypes(),
            'eventTypesList' => PrivateVenue::eventTypesList(),
            'venueAmenitiesList' => PrivateVenue::venueAmenitiesList(),
            'setupTypes' => PrivateVenue::setupTypes(),
            'spaceAmenitiesList' => PrivateVenue::spaceAmenitiesList(),
        ]);
    }

    public function update(Request $request, string $private_venue)
    {
        $venue = PrivateVenue::findOrFail($private_venue);

        DB::transaction(function () use ($request, $venue) {
            $data = $this->validateVenue($request, $venue);
            $data = $this->mergeVenueMedia($request, $data, $venue);
            $data = $this->mergeCheckboxArrays($request, $data);

            if ($request->filled('name') && $request->name !== $venue->name) {
                $data['slug'] = PrivateVenue::uniqueSlug($request->name, $venue->id);
            }

            $venue->update($data);
            $this->syncSpaces($venue, $request);
        });

        return redirect()
            ->route('admin.private-venues.index')
            ->with('success', 'Private venue updated successfully.');
    }

    public function destroy(string $private_venue)
    {
        $venue = PrivateVenue::findOrFail($private_venue);

        if (is_array($venue->images)) {
            foreach ($venue->images as $path) {
                ImageService::delete($path);
            }
        }

        if ($venue->video) {
            ImageService::delete($venue->video);
        }

        $venue->delete();

        return redirect()
            ->route('admin.private-venues.index')
            ->with('success', 'Private venue deleted successfully.');
    }

    private function validateVenue(Request $request, ?PrivateVenue $venue = null): array
    {
        $venueTypes = array_keys(PrivateVenue::venueTypes());

        return $request->validate([
            'name' => 'required|string|max:255',
            'venue_type' => ['required', Rule::in($venueTypes)],
            'brand_chain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'highlights' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'contact_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'video' => 'nullable|string|max:500',
            'video_file' => 'nullable|file|mimes:mp4,webm|max:51200',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'total_meeting_space_sqm' => 'nullable|numeric|min:0',
            'largest_room_capacity' => 'nullable|integer|min:0',
            'number_of_meeting_rooms' => 'nullable|integer|min:0',
            'sleeping_rooms' => 'nullable|integer|min:0',
            'min_event_size' => 'nullable|integer|min:1',
            'max_event_size' => 'nullable|integer|min:1',
            'currency' => 'nullable|string|size:3',
            'starting_daily_rate' => 'nullable|numeric|min:0',
            'pricing_notes' => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive,pending',
            'internal_notes' => 'nullable|string',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);
    }

    private function mergeVenueMedia(Request $request, array $data, ?PrivateVenue $venue): array
    {
        $existing = is_array($venue?->images) ? $venue->images : [];

        if ($request->filled('remove_images')) {
            foreach ($request->remove_images as $path) {
                if (in_array($path, $existing, true)) {
                    ImageService::delete($path);
                    $existing = array_values(array_diff($existing, [$path]));
                }
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $upload = ImageService::upload($image, 'private-venues', null, 4096);
                    $existing[] = $upload['path'];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        $data['images'] = $existing !== [] ? $existing : null;

        if ($request->hasFile('video_file')) {
            if ($venue?->video) {
                ImageService::delete($venue->video);
            }
            $upload = ImageService::upload($request->file('video_file'), 'private-venues/videos', null, 51200);
            $data['video'] = $upload['path'];
        } elseif ($request->filled('video')) {
            $data['video'] = $request->video;
        } elseif (! $request->has('video')) {
            unset($data['video']);
        }

        $data['is_featured'] = $request->boolean('is_featured');
        $data['currency'] = strtoupper($data['currency'] ?? 'EUR');
        $data['display_order'] = (int) ($data['display_order'] ?? 0);

        return $data;
    }

    private function mergeCheckboxArrays(Request $request, array $data): array
    {
        $data['amenities'] = array_values($request->input('amenities', []));
        $data['event_types'] = array_values($request->input('event_types', []));

        return $data;
    }

    private function syncSpaces(PrivateVenue $venue, Request $request): void
    {
        $rows = collect($request->input('spaces', []))
            ->filter(fn ($row) => ! empty(trim($row['name'] ?? '')))
            ->values();

        $venue->spaces()->delete();

        foreach ($rows as $index => $row) {
            $setup = [];
            foreach (array_keys(PrivateVenue::setupTypes()) as $key) {
                $val = $row['setup_capacities'][$key] ?? null;
                if ($val !== null && $val !== '') {
                    $setup[$key] = (int) $val;
                }
            }

            $amenities = array_values($row['amenities'] ?? []);

            $venue->spaces()->create([
                'name' => trim($row['name']),
                'description' => $row['description'] ?? null,
                'total_space_sqm' => $row['total_space_sqm'] !== '' && $row['total_space_sqm'] !== null
                    ? (float) $row['total_space_sqm'] : null,
                'length_m' => $row['length_m'] !== '' && $row['length_m'] !== null ? (float) $row['length_m'] : null,
                'width_m' => $row['width_m'] !== '' && $row['width_m'] !== null ? (float) $row['width_m'] : null,
                'ceiling_height_m' => $row['ceiling_height_m'] !== '' && $row['ceiling_height_m'] !== null
                    ? (float) $row['ceiling_height_m'] : null,
                'setup_capacities' => $setup !== [] ? $setup : null,
                'amenities' => $amenities !== [] ? $amenities : null,
                'is_outdoor' => ! empty($row['is_outdoor']),
                'is_private' => array_key_exists('is_private', $row) ? ! empty($row['is_private']) : true,
                'is_semi_private' => ! empty($row['is_semi_private']),
                'wheelchair_accessible' => ! empty($row['wheelchair_accessible']),
                'sort_order' => isset($row['sort_order']) && $row['sort_order'] !== ''
                    ? (int) $row['sort_order']
                    : (1000 - $index),
                'status' => ($row['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            ]);
        }
    }
}
