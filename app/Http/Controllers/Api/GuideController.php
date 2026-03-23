<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class GuideController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:100',
            'availability_date' => 'nullable|date',
            'service_type' => ['nullable', Rule::in(['3h', '6h', '8h', '12h'])],
            'featured' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Guide::with('packages')
            ->where('status', 'active')
            ->where('display_on_website', true);

        if (!empty($validated['country'])) {
            $query->where('country', $validated['country']);
        }
        if (!empty($validated['city'])) {
            $query->where('city', $validated['city']);
        }
        if (isset($validated['featured'])) {
            $query->where('featured_guide', (bool) $validated['featured']);
        }
        if (!empty($validated['language'])) {
            $lang = $validated['language'];
            $query->where(function ($q) use ($lang) {
                $q->where('language', $lang)
                    ->orWhere('primary_language', $lang)
                    ->orWhereJsonContains('other_languages', $lang);
            });
        }
        if (!empty($validated['service_type'])) {
            $type = $validated['service_type'];
            $query->whereHas('packages', fn($p) => $p->where('service_type', $type)->where('status', 'active'));
        }
        if (!empty($validated['availability_date'])) {
            $date = Carbon::parse($validated['availability_date'])->toDateString();
            $dayKey = strtolower(Carbon::parse($validated['availability_date'])->format('D'));
            $query->where(function ($q) use ($date) {
                $q->whereNull('available_from_date')
                    ->orWhereDate('available_from_date', '<=', $date);
            })->where(function ($q) use ($date) {
                $q->whereNull('available_to_date')
                    ->orWhereDate('available_to_date', '>=', $date);
            })->where(function ($q) use ($dayKey) {
                $q->whereNull('available_days')
                    ->orWhereRaw("JSON_CONTAINS(COALESCE(available_days,'[]'), '\"{$dayKey}\"')");
            });
        }

        $guides = $query->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $guides->getCollection()->transform(fn($guide) => $this->transformGuide($guide));

        return response()->json([
            'success' => true,
            'data' => $guides->items(),
            'pagination' => [
                'current_page' => $guides->currentPage(),
                'last_page' => $guides->lastPage(),
                'per_page' => $guides->perPage(),
                'total' => $guides->total(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $guide = Guide::with('packages')->find($id);
        if (!$guide || $guide->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Guide not found or inactive.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformGuide($guide, true),
        ]);
    }

    /**
     * Expose available filter options for guides (public website)
     */
    public function filterOptions()
    {
        $guideQuery = Guide::query()
            ->where('status', 'active')
            ->where('display_on_website', true);

        $options = [
            'countries' => (clone $guideQuery)->whereNotNull('country')
                ->distinct()
                ->pluck('country')
                ->sort()
                ->values()
                ->toArray(),
            'cities' => (clone $guideQuery)->whereNotNull('city')
                ->distinct()
                ->pluck('city')
                ->sort()
                ->values()
                ->toArray(),
            'service_types' => DB::table('guide_packages')
                ->where('status', 'active')
                ->distinct()
                ->pluck('service_type')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray(),
        ];

        $languages = [];
        (clone $guideQuery)->get(['language', 'primary_language', 'other_languages'])
            ->each(function ($guide) use (&$languages) {
                if ($guide->language) {
                    $languages[] = $guide->language;
                }
                if ($guide->primary_language) {
                    $languages[] = $guide->primary_language;
                }
                if (is_array($guide->other_languages)) {
                    $languages = array_merge($languages, $guide->other_languages);
                }
            });

        $options['languages'] = collect($languages)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'message' => 'Filter options retrieved successfully',
            'data' => $options,
        ]);
    }

    private function transformGuide(Guide $guide, bool $includeNotes = false): array
    {
        $profilePhotoUrl = null;
        if ($guide->profile_photo) {
            $profilePhotoUrl = url('storage/app/public/' . ltrim($guide->profile_photo, '/'));
        }

        return [
            'id' => $guide->id,
            'title' => $guide->title,
            'full_name' => $guide->full_name,
            'profile_photo_url' => $profilePhotoUrl,
            'city' => $guide->city,
            'country' => $guide->country,
            'languages' => [
                'primary' => $guide->primary_language ?? $guide->language,
                'others' => $guide->other_languages ?? [],
                'proficiency' => $guide->language_proficiency,
            ],
            'availability' => [
                'from' => optional($guide->available_from_date)->toDateString(),
                'to' => optional($guide->available_to_date)->toDateString(),
                'days' => $guide->available_days,
                'daily_start_time' => $guide->daily_start_time?->format('H:i'),
                'daily_end_time' => $guide->daily_end_time?->format('H:i'),
            ],
            'experience' => [
                'years' => $guide->years_experience,
                'indian_customers' => $guide->experience_indian_customers,
                'indian_tours_completed' => $guide->indian_tours_completed,
                'indian_language_support' => $guide->indian_language_support,
            ],
            'verification' => [
                'status' => $guide->verification_status,
                'police_verified' => (bool) $guide->police_verification,
            ],
            'packages' => $guide->packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'service_type' => $package->service_type,
                    'service_name' => $package->service_name,
                    'duration_hours' => $package->duration_hours,
                    'standard_price' => $package->standard_price ? (float) $package->standard_price : null,
                    'extra_hour_price' => $package->extra_hour_price ? (float) $package->extra_hour_price : null,
                    'currency' => $package->currency,
                    'default_start_location' => $package->default_start_location,
                    'default_end_location' => $package->default_end_location,
                    'start_point' => $package->start_point,
                    'end_point' => $package->end_point,
                    'start_time' => $package->start_time?->format('H:i'),
                    'end_time' => $package->end_time?->format('H:i'),
                    'notes' => $package->notes,
                    'status' => $package->status,
                ];
            })->values(),
            'description' => $guide->description,
            'notes' => $includeNotes ? $guide->notes : null,
        ];
    }
}



