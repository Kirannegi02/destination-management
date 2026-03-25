<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sightseeing;
use App\Models\SightseeingBooking;
use App\Models\SightseeingOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SightseeingBookingController extends Controller
{
    private function itemRules(string $prefix = ''): array
    {
        $p = $prefix ? $prefix . '.' : '';

        return [
            $p . 'sightseeing_id' => 'required|exists:sightseeings,id',
            $p . 'sightseeing_option_id' => 'nullable|exists:sightseeing_options,id',
            $p . 'sightseeing_option_ids' => 'nullable|array|min:1',
            $p . 'sightseeing_option_ids.*' => 'required|integer|exists:sightseeing_options,id',
            $p . 'date' => 'required|date|after_or_equal:today',
            $p . 'pax_count' => 'required|integer|min:1',
            $p . 'guest_name' => 'nullable|string|max:255',
            $p . 'guest_phone' => 'nullable|string|max:25',
            $p . 'guests_details' => 'nullable|array',
            $p . 'guests_details.*.name' => 'required|string|max:255',
            $p . 'guests_details.*.country' => 'required|string|max:100',
            $p . 'guests_details.*.phone' => 'nullable|string|max:25',
            $p . 'special_requests' => 'nullable|string',
        ];
    }

    /**
     * Create a new sightseeing booking.
     * Request: sightseeing_id, sightseeing_option_id (optional), date, pax_count, guest_name, guest_phone, guests_details, special_requests.
     */
    public function store(Request $request)
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
                'message' => 'Your agent account is pending verification. You cannot create sightseeing bookings until you are approved by admin.',
            ], 403);
        }

        // Shorthand for one sightseeing with multiple options.
        // Expands into items[] for batch creation.
        if (
            $request->filled('sightseeing_id')
            && (
                is_array($request->input('sightseeing_option_ids'))
                || is_array($request->input('sightseeing_package_ids'))
                || is_array($request->input('option_ids'))
            )
        ) {
            $baseItem = $request->except(['sightseeing_option_ids', 'sightseeing_package_ids', 'option_ids']);
            $optionIds = $request->input('sightseeing_option_ids', $request->input('sightseeing_package_ids', $request->input('option_ids', [])));
            $items = [];
            foreach ($optionIds as $optionId) {
                $item = $baseItem;
                $item['sightseeing_option_id'] = $optionId;
                $items[] = $item;
            }
            $request->merge(['items' => $items]);
        }

        // Unified endpoint: single item OR batch items.
        if (is_array($request->input('items'))) {
            return $this->storeBatch($request, $user);
        }

        $validated = $request->validate($this->itemRules());

        $booking = $this->createBooking($user->id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Sightseeing booking created successfully.',
            'data' => $this->transform($booking),
        ], 201);
    }

    private function storeBatch(Request $request, $user)
    {
        $base = $request->validate([
            'items' => 'required|array|min:1|max:100',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:25',
        ]);
        $request->validate($this->itemRules('items.*'));

        $defaults = [
            'guest_name' => $base['guest_name'] ?? null,
            'guest_phone' => $base['guest_phone'] ?? null,
        ];

        $created = [];
        try {
            DB::transaction(function () use ($request, $defaults, $user, &$created) {
                foreach ($request->input('items', []) as $index => $item) {
                    // Per-item shorthand: one sightseeing with multiple options.
                    $expandedItems = [];
                    $itemOptionIds = [];
                    if (is_array($item['sightseeing_option_ids'] ?? null)) {
                        $itemOptionIds = $item['sightseeing_option_ids'];
                    } elseif (is_array($item['sightseeing_package_ids'] ?? null)) {
                        $itemOptionIds = $item['sightseeing_package_ids'];
                    } elseif (is_array($item['option_ids'] ?? null)) {
                        $itemOptionIds = $item['option_ids'];
                    }

                    if (!empty($item['sightseeing_id']) && !empty($itemOptionIds)) {
                        foreach ($itemOptionIds as $optionId) {
                            $row = $item;
                            $row['sightseeing_option_id'] = $optionId;
                            unset($row['sightseeing_option_ids']);
                            unset($row['sightseeing_package_ids']);
                            unset($row['option_ids']);
                            $expandedItems[] = $row;
                        }
                    } else {
                        $expandedItems[] = $item;
                    }

                    foreach ($expandedItems as $expanded) {
                        $payload = array_merge($defaults, $expanded);
                        try {
                            $booking = $this->createBooking((int) $user->id, $payload);
                            $created[] = $booking;
                        } catch (ValidationException $e) {
                            $prefixed = [];
                            foreach ($e->errors() as $field => $messages) {
                                $prefixed['items.' . $index . '.' . $field] = $messages;
                            }
                            throw ValidationException::withMessages($prefixed);
                        }
                    }
                }
            });
        } catch (ValidationException $e) {
            $first = array_key_first($e->errors());
            return response()->json([
                'success' => false,
                'message' => $e->errors()[$first][0] ?? 'Validation failed.',
                'errors' => $e->errors(),
                'failed_item_index' => preg_match('/^items\.(\d+)\./', (string) $first, $m) ? (int) $m[1] : null,
            ], 422);
        }

        $bookings = SightseeingBooking::with(['sightseeing', 'sightseeingOption'])
            ->whereIn('id', collect($created)->pluck('id'))
            ->orderBy('id')
            ->get();

        $hasMissingPrice = $bookings->contains(fn ($b) => $b->price === null);
        $combined = $hasMissingPrice ? null : round((float) $bookings->sum('price'), 2);

        return response()->json([
            'success' => true,
            'message' => $bookings->count() . ' sightseeing booking(s) created successfully.',
            'data' => [
                'bookings' => $bookings->map(fn ($b) => $this->transform($b))->values()->all(),
                'count' => $bookings->count(),
                'combined_total_price' => $combined,
            ],
        ], 201);
    }

    private function createBooking(int $userId, array $validated): SightseeingBooking
    {
        $sightseeing = Sightseeing::where('status', 'active')->find($validated['sightseeing_id']);
        if (!$sightseeing) {
            throw ValidationException::withMessages([
                'sightseeing_id' => ['Sightseeing not available for booking.'],
            ]);
        }

        $option = null;
        if (!empty($validated['sightseeing_option_id'])) {
            $option = SightseeingOption::where('sightseeing_id', $sightseeing->id)
                ->where('is_active', true)
                ->find($validated['sightseeing_option_id']);

            if (!$option) {
                throw ValidationException::withMessages([
                    'sightseeing_option_id' => ['Selected sightseeing option not found or inactive.'],
                ]);
            }
        }

        if (!$option) {
            throw ValidationException::withMessages([
                'sightseeing_option_id' => ['Please select a sightseeing package/option to continue booking.'],
            ]);
        }

        $paxCount = (int) $validated['pax_count'];
        $currency = $option->currency ?? $sightseeing->currency;
        $basePrice = $option->base_price;
        $totalPrice = $basePrice !== null ? (float) $basePrice * $paxCount : null;

        return SightseeingBooking::create([
            'user_id' => $userId,
            'sightseeing_id' => $sightseeing->id,
            'sightseeing_option_id' => $option?->id,
            'booking_date' => $validated['date'],
            'pax_count' => $paxCount,
            'price' => $totalPrice,
            'currency' => $currency ?? 'CHF',
            'guest_name' => $validated['guest_name'] ?? null,
            'guest_phone' => $validated['guest_phone'] ?? null,
            'guests_details' => $validated['guests_details'] ?? null,
            'special_requests' => $validated['special_requests'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * List current user's sightseeing bookings.
     */
    public function index(Request $request)
    {
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

        $bookings = SightseeingBooking::with(['sightseeing', 'sightseeingOption'])
            ->where('user_id', $user->id)
            ->when(
                isset($validated['status']) && $validated['status'] !== 'all',
                fn($q) => $q->where('status', $validated['status'])
            )
            ->orderBy('booking_date', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $bookings->getCollection()->transform(fn($b) => $this->transform($b));

        return response()->json([
            'success' => true,
            'message' => 'Sightseeing bookings retrieved successfully.',
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ], 200);
    }

    /**
     * Get a single sightseeing booking.
     */
    public function show(string $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        $booking = SightseeingBooking::with(['sightseeing', 'sightseeingOption'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($booking),
        ], 200);
    }

    /**
     * Cancel a sightseeing booking.
     */
    public function cancel(Request $request, string $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        $booking = SightseeingBooking::where('user_id', $user->id)->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.',
            ], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => true,
                'message' => 'Booking already cancelled.',
                'data' => $this->transform($booking),
            ], 200);
        }

        if ($booking->status === 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmed bookings cannot be cancelled.',
            ], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Sightseeing booking cancelled successfully.',
            'data' => $this->transform($booking),
        ], 200);
    }

    private function transform(SightseeingBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'sightseeing_id' => $booking->sightseeing_id,
            'sightseeing_title' => $booking->sightseeing?->title,
            'sightseeing_option_id' => $booking->sightseeing_option_id,
            'sightseeing_option_name' => $booking->sightseeingOption?->name,
            'date' => $booking->booking_date?->format('Y-m-d'),
            'pax_count' => $booking->pax_count,
            'price' => $booking->price ? (float) $booking->price : null,
            'currency' => $booking->currency,
            'price_formatted' => $booking->price && $booking->currency
                ? $booking->currency . ' ' . number_format((float) $booking->price, 2)
                : null,
            'guest_name' => $booking->guest_name,
            'guest_phone' => $booking->guest_phone,
            'guests_details' => $booking->guests_details,
            'special_requests' => $booking->special_requests,
            'status' => $booking->status,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
