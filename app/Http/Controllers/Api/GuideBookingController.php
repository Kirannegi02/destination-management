<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideBooking;
use App\Models\GuidePackage;
use App\Models\User;
use Carbon\Carbon;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuideBookingController extends Controller
{
    /**
     * Validation rules for one guide booking line (single or batch item).
     */
    private function bookingItemRules(string $prefix = ''): array
    {
        $p = $prefix ? $prefix . '.' : '';

        return [
            $p . 'guide_id' => 'required|exists:guides,id',
            $p . 'guide_package_id' => 'nullable|exists:guide_packages,id',
            $p . 'service_date' => 'required|date|after_or_equal:today',
            $p . 'start_time' => 'nullable|date_format:H:i',
            $p . 'duration_hours' => 'nullable|integer|min:1|max:72',
            $p . 'guests' => 'nullable|integer|min:1|max:50',
            $p . 'start_location' => 'nullable|string|max:255',
            $p . 'end_location' => 'nullable|string|max:255',
            $p . 'special_requests' => 'nullable|string',
            $p . 'contact_name' => 'nullable|string|max:255',
            $p . 'contact_phone' => 'nullable|string|max:25',
            $p . 'contact_email' => 'nullable|email|max:255',
        ];
    }

    public function store(Request $request)
    {
        Log::info('Guide booking store requested.', [
            'user_id' => auth('api')->id(),
            'has_items' => is_array($request->input('items')),
            'has_guide_package_ids' => is_array($request->input('guide_package_ids')),
        ]);

        // Shorthand payload support:
        // {
        //   "guide_id": 1,
        //   "guide_package_ids": [5,7,9],
        //   "service_date": "...",
        //   ...
        // }
        // Expands into batch items for the same guide with multiple packages.
        if ($request->filled('guide_id') && is_array($request->input('guide_package_ids'))) {
            $baseItem = $request->except(['guide_package_ids']);
            $items = [];
            foreach ($request->input('guide_package_ids', []) as $packageId) {
                $item = $baseItem;
                $item['guide_package_id'] = $packageId;
                $items[] = $item;
            }

            $request->merge([
                'items' => $items,
            ]);
        }

        // Single endpoint supports both:
        // - single booking payload (guide_id, service_date, ...)
        // - multi booking payload (items: [...])
        if (is_array($request->input('items'))) {
            Log::info('Guide booking store routed to batch mode.', [
                'user_id' => auth('api')->id(),
                'items_count' => count($request->input('items', [])),
            ]);
            return $this->storeBatch($request);
        }

        $user = $this->authenticateBookingUser();
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $validated = $request->validate($this->bookingItemRules());

        $pending = [];
        try {
            $booking = $this->createGuideBookingRecord($user, $validated, $pending);
        } catch (ValidationException $e) {
            Log::warning('Guide booking create failed validation.', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->errors()[array_key_first($e->errors())][0] ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        Log::info('Guide booking created.', [
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'guide_id' => $booking->guide_id,
            'guide_package_id' => $booking->guide_package_id,
            'service_date' => optional($booking->service_date)->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guide booking created successfully.',
            'data' => $this->transformBooking($booking->load(['guide', 'package'])),
        ], 201);
    }

    /**
     * Create multiple guide bookings in one request: different guides and/or multiple packages
     * for the same guide. Each item becomes one row in guide_bookings (same as separate POSTs).
     *
     * Body: { "items": [ { ...same fields as single booking... }, ... ], optional batch-level "contact_*" defaults }
     */
    public function storeBatch(Request $request)
    {
        Log::info('Guide booking batch requested.', [
            'user_id' => auth('api')->id(),
            'items_count' => count($request->input('items', [])),
        ]);

        $user = $this->authenticateBookingUser();
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        try {
            $base = $request->validate([
                'items' => 'required|array|min:1|max:50',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:25',
                'contact_email' => 'nullable|email|max:255',
            ]);

            $request->validate($this->bookingItemRules('items.*'));
        } catch (ValidationException $e) {
            Log::warning('Guide booking batch failed top-level validation.', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
                'payload_keys' => array_keys($request->all()),
            ]);
            throw $e;
        }

        Log::info('Guide booking batch request validated.', [
            'user_id' => $user->id,
            'items_count' => count($request->input('items', [])),
        ]);

        $defaults = [
            'contact_name' => $base['contact_name'] ?? null,
            'contact_phone' => $base['contact_phone'] ?? null,
            'contact_email' => $base['contact_email'] ?? null,
        ];

        $created = [];
        $pendingGuideDateCounts = [];

        try {
            DB::transaction(function () use ($user, $base, $request, $defaults, &$created, &$pendingGuideDateCounts) {
                foreach ($request->input('items', []) as $index => $row) {
                    Log::info('Guide booking batch item processing started.', [
                        'user_id' => $user->id,
                        'item_index' => $index,
                        'guide_id' => $row['guide_id'] ?? null,
                        'guide_package_id' => $row['guide_package_id'] ?? null,
                        'service_date' => $row['service_date'] ?? null,
                    ]);

                    $merged = array_merge($defaults, $row);
                    $merged['contact_name'] = $row['contact_name'] ?? $defaults['contact_name'];
                    $merged['contact_phone'] = $row['contact_phone'] ?? $defaults['contact_phone'];
                    $merged['contact_email'] = $row['contact_email'] ?? $defaults['contact_email'];

                    $meta = [
                        'batch' => [
                            'index' => (int) $index,
                            'total' => count($request->input('items', [])),
                        ],
                    ];

                    try {
                        $booking = $this->createGuideBookingRecord($user, $merged, $pendingGuideDateCounts, $meta);
                        $created[] = $booking;
                        Log::info('Guide booking batch item created.', [
                            'user_id' => $user->id,
                            'item_index' => $index,
                            'booking_id' => $booking->id,
                            'guide_id' => $booking->guide_id,
                            'guide_package_id' => $booking->guide_package_id,
                        ]);
                    } catch (ValidationException $e) {
                        Log::warning('Guide booking batch item failed validation.', [
                            'user_id' => $user->id,
                            'item_index' => $index,
                            'errors' => $e->errors(),
                        ]);
                        $prefixed = [];
                        foreach ($e->errors() as $field => $messages) {
                            $prefixed['items.' . $index . '.' . $field] = $messages;
                        }
                        throw ValidationException::withMessages($prefixed);
                    }
                }
            });
        } catch (ValidationException $e) {
            $firstKey = array_key_first($e->errors());
            Log::warning('Guide booking batch failed validation.', [
                'user_id' => $user->id,
                'first_error_key' => $firstKey,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->errors()[$firstKey][0] ?? 'Validation failed.',
                'errors' => $e->errors(),
                'failed_item_index' => preg_match('/^items\.(\d+)\./', (string) $firstKey, $m) ? (int) $m[1] : null,
            ], 422);
        } catch (Throwable $e) {
            Log::error('Guide booking batch failed with unexpected exception.', [
                'user_id' => $user->id,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not create guide bookings due to an internal error.',
            ], 500);
        }

        $loaded = GuideBooking::with(['guide', 'package'])
            ->whereIn('id', collect($created)->pluck('id'))
            ->orderBy('id')
            ->get();

        $combinedTotal = $loaded->sum(fn ($b) => (float) ($b->estimated_total ?? $b->price ?? 0));

        Log::info('Guide booking batch created.', [
            'user_id' => $user->id,
            'created_count' => $loaded->count(),
            'booking_ids' => $loaded->pluck('id')->values()->all(),
            'combined_estimated_total' => $combinedTotal > 0 ? round($combinedTotal, 2) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => count($created) . ' guide booking(s) created successfully.',
            'data' => [
                'bookings' => $loaded->map(fn ($b) => $this->transformBooking($b))->values()->all(),
                'count' => $loaded->count(),
                'combined_estimated_total' => $combinedTotal > 0 ? round($combinedTotal, 2) : null,
            ],
        ], 201);
    }

    /**
     * @return User|\Illuminate\Http\JsonResponse
     */
    private function authenticateBookingUser()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your agent account is pending verification. You cannot create guide bookings until you are approved by admin.',
            ], 403);
        }

        return $user;
    }

    /**
     * @param  array<string, int>  $pendingGuideDateCounts  keys: "{guideId}|{Y-m-d}" => bookings in this request so far
     * @param  array<string, mixed>|null  $extraMetadata  merged into metadata JSON
     */
    private function createGuideBookingRecord(User $user, array $validated, array &$pendingGuideDateCounts, ?array $extraMetadata = null): GuideBooking
    {
        $guide = Guide::with('packages')->where('status', 'active')->where('display_on_website', true)->find($validated['guide_id']);
        if (!$guide) {
            throw ValidationException::withMessages(['guide_id' => ['Guide not available for booking.']]);
        }

        $package = null;
        if (!empty($validated['guide_package_id'])) {
            $package = GuidePackage::where('id', $validated['guide_package_id'])
                ->where('guide_id', $guide->id)
                ->where('status', 'active')
                ->first();
            if (!$package) {
                throw ValidationException::withMessages(['guide_package_id' => ['Selected package is not available for this guide.']]);
            }
        }

        $serviceDate = Carbon::parse($validated['service_date']);

        if ($guide->available_from_date && $serviceDate->lt($guide->available_from_date)) {
            throw ValidationException::withMessages(['service_date' => ['Guide is not available on this date (before start).']]);
        }
        if ($guide->available_to_date && $serviceDate->gt($guide->available_to_date)) {
            throw ValidationException::withMessages(['service_date' => ['Guide is not available on this date (after end).']]);
        }
        if (!empty($guide->available_days)) {
            $dayKey = strtolower($serviceDate->format('D'));
            if (!in_array($dayKey, $guide->available_days, true)) {
                throw ValidationException::withMessages(['service_date' => ['Guide is not available on this weekday.']]);
            }
        }

        $dateKey = $guide->id . '|' . $serviceDate->toDateString();
        $pending = $pendingGuideDateCounts[$dateKey] ?? 0;

        if ($guide->max_bookings_per_day) {
            $count = GuideBooking::where('guide_id', $guide->id)
                ->whereDate('service_date', $serviceDate->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->count();
            if (($count + $pending) >= $guide->max_bookings_per_day) {
                throw ValidationException::withMessages([
                    'service_date' => ['Maximum bookings reached for this guide on this date.'],
                ]);
            }
        }

        $pendingGuideDateCounts[$dateKey] = $pending + 1;

        $duration = $validated['duration_hours']
            ?? $package?->duration_hours
            ?? 3;

        $startTime = $validated['start_time'] ?? $package?->start_time?->format('H:i');
        $calculatedEnd = null;
        if ($package?->end_time) {
            $calculatedEnd = $package->end_time->format('H:i');
        } elseif ($startTime) {
            $calculatedEnd = Carbon::createFromFormat('H:i', $startTime)->addHours($duration)->format('H:i');
        }

        $basePrice = $package?->standard_price;
        $includedHours = $package?->duration_hours ?? 8;
        $extraHours = max($duration - $includedHours, 0);
        $extraAmount = $extraHours > 0 ? (($package?->extra_hour_price ?? 0) * $extraHours) : 0;
        $price = $basePrice !== null ? ($basePrice + $extraAmount) : null;

        // Prevent double-booking for same guide when times overlap on same date.
        if ($startTime) {
            $newStart = Carbon::createFromFormat('H:i', $startTime);
            $newEnd = $calculatedEnd
                ? Carbon::createFromFormat('H:i', $calculatedEnd)
                : (clone $newStart)->addHours((int) $duration);

            $dayBookings = GuideBooking::where('guide_id', $guide->id)
                ->whereDate('service_date', $serviceDate->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->get();

            foreach ($dayBookings as $existing) {
                if (!$existing->start_time) {
                    throw ValidationException::withMessages([
                        'start_time' => ['Guide is unavailable for this date/time slot.'],
                    ]);
                }

                $existingStart = Carbon::createFromFormat('H:i', $existing->start_time->format('H:i'));
                $existingDuration = $existing->duration_hours ?? 1;
                $existingEnd = $existing->calculated_end_time
                    ? Carbon::createFromFormat('H:i', $existing->calculated_end_time->format('H:i'))
                    : (clone $existingStart)->addHours((int) $existingDuration);

                if ($newStart < $existingEnd && $newEnd > $existingStart) {
                    throw ValidationException::withMessages([
                        'start_time' => ['Guide is unavailable for this date/time slot.'],
                    ]);
                }
            }
        }

        return GuideBooking::create([
            'user_id' => $user->id,
            'guide_id' => $guide->id,
            'guide_package_id' => $package?->id,
            'service_date' => $serviceDate->toDateString(),
            'start_time' => $startTime,
            'calculated_end_time' => $calculatedEnd,
            'duration_hours' => $duration,
            'guests' => $validated['guests'] ?? null,
            'start_location' => $validated['start_location'] ?? $package?->default_start_location ?? $package?->start_point,
            'end_location' => $validated['end_location'] ?? $package?->default_end_location ?? $package?->end_point,
            'status' => 'pending',
            'special_requests' => $validated['special_requests'] ?? null,
            'price' => $price,
            'currency' => $package?->currency ?? 'INR',
            'estimated_total' => $price,
            'contact_name' => $validated['contact_name'] ?? $user->name,
            'contact_phone' => $validated['contact_phone'] ?? $user->phone,
            'contact_email' => $validated['contact_email'] ?? $user->email,
            'metadata' => $extraMetadata,
        ]);
    }

    public function cancel(Request $request, string $id)
    {
        Log::info('Guide booking cancel requested.', [
            'user_id' => auth('api')->id(),
            'booking_id' => $id,
        ]);

        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        $booking = GuideBooking::where('user_id', $user->id)->find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['success' => true, 'message' => 'Booking already cancelled.', 'data' => $this->transformBooking($booking)], 200);
        }

        if ($booking->status === 'confirmed') {
            return response()->json(['success' => false, 'message' => 'Confirmed bookings cannot be cancelled.'], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        Log::info('Guide booking cancelled.', [
            'user_id' => $user->id,
            'booking_id' => $booking->id,
            'guide_id' => $booking->guide_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $this->transformBooking($booking),
        ], 200);
    }

    public function index(Request $request)
    {
        Log::info('Guide booking list requested.', [
            'user_id' => auth('api')->id(),
            'status' => $request->input('status'),
            'per_page' => $request->input('per_page'),
        ]);

        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bookings = GuideBooking::with(['guide', 'package'])
            ->where('user_id', $user->id)
            ->when(isset($validated['status']) && $validated['status'] !== 'all', fn ($q) => $q->where('status', $validated['status']))
            ->orderBy('service_date', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $bookings->getCollection()->transform(fn ($booking) => $this->transformBooking($booking));

        Log::info('Guide booking list fetched.', [
            'user_id' => $user->id,
            'count' => count($bookings->items()),
            'total' => $bookings->total(),
            'current_page' => $bookings->currentPage(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guide bookings retrieved successfully.',
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    private function transformBooking(GuideBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'guide_id' => $booking->guide_id,
            'guide_name' => $booking->guide?->full_name ?? $booking->guide?->title,
            'guide_package_id' => $booking->guide_package_id,
            'package_name' => $booking->package?->service_name,
            'service_date' => $booking->service_date?->toDateString(),
            'start_time' => $booking->start_time?->format('H:i'),
            'end_time' => $booking->calculated_end_time?->format('H:i'),
            'duration_hours' => $booking->duration_hours,
            'guests' => $booking->guests,
            'start_location' => $booking->start_location,
            'end_location' => $booking->end_location,
            'special_requests' => $booking->special_requests,
            'price' => $booking->price ? (float) $booking->price : null,
            'currency' => $booking->currency,
            'estimated_total' => $booking->estimated_total ? (float) $booking->estimated_total : null,
            'status' => $booking->status,
            'metadata' => $booking->metadata,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
