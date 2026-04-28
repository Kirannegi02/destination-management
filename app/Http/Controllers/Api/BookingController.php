<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Support\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Create a new restaurant booking (no payment).
     *
     * Supports two modes:
     *
     * (A) MULTI-MEAL  — recommended
     * ----------------------------------
     * Pass a `meals` array where each item has a `meal_id` and optionally `guests`
     * (defaults to the top-level `guests` count).
     *
     *   meals: [
     *     { "meal_id": 1, "guests": 6 },
     *     { "meal_id": 3, "guests": 4 }
     *   ]
     *
     * (B) SINGLE-MEAL — backward compatible
     * ----------------------------------
     * Pass `meal_id` directly (legacy behaviour).
     *
     * Required fields  : restaurant_id, date, time, guests (total headcount)
     * Optional fields  : meals[], meal_id (legacy), meal_type, meal_price (legacy),
     *                    guest_name, guest_phone, guests_details[], special_requests
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
                'message' => 'Your agent account is pending verification. You cannot create restaurant bookings until you are approved by admin.',
            ], 403);
        }

        // Accept number_of_guests / guest_count as aliases for guests
        if (!$request->filled('guests') && $request->filled('number_of_guests')) {
            $request->merge(['guests' => $request->input('number_of_guests')]);
        }
        if (!$request->filled('guests') && $request->filled('guest_count')) {
            $request->merge(['guests' => $request->input('guest_count')]);
        }

        $validated = $request->validate([
            'restaurant_id'                       => 'required|exists:restaurants,id',
            // Multi-meal array
            'meals'                               => 'nullable|array|min:1',
            'meals.*.meal_id'                     => 'required_with:meals|integer|exists:meals,id',
            'meals.*.guests'                      => 'nullable|integer|min:1',
            // Optional supplement selection per meal
            'meals.*.supplements'                 => 'nullable|array',
            'meals.*.supplements.starter'         => 'nullable|boolean',
            'meals.*.supplements.main_course'     => 'nullable|boolean',
            // Legacy single-meal fields (kept for backward compatibility)
            'meal_id'                             => 'nullable|exists:meals,id',
            'meal_type'                           => 'nullable|string|max:100',
            'meal_price'                          => 'nullable|numeric|min:0',
            'meal_price_eur'                      => 'nullable|numeric|min:0',
            // Supplement selection for single-meal mode
            'supplements'                         => 'nullable|array',
            'supplements.starter'                 => 'nullable|boolean',
            'supplements.main_course'             => 'nullable|boolean',
            // Booking details
            'date'                                => 'required|date|after_or_equal:today',
            'time'                                => 'required|string|max:20',
            'guests'                              => 'required|integer|min:1',
            'guests_details'                      => 'nullable|array',
            'guests_details.*.name'               => 'required|string|max:255',
            'guests_details.*.country'            => 'required|string|max:100',
            'guests_details.*.email'              => 'nullable|email|max:255',
            'guests_details.*.phone'              => 'nullable|string|max:25',
            'guest_name'                          => 'nullable|string|max:255',
            'guest_phone'                         => 'nullable|string|max:25',
            'special_requests'                    => 'nullable|string',
        ]);

        // Ensure restaurant is active
        $restaurant = Restaurant::where('id', $validated['restaurant_id'])
            ->where('status', 'active')
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not available for booking.',
            ], 404);
        }

        $totalGuests = (int) $validated['guests'];

        // ── MULTI-MEAL mode ──────────────────────────────────────────────────
        if (!empty($validated['meals'])) {
            return $this->storeMultiMeal($request, $user, $restaurant, $validated, $totalGuests);
        }

        // ── SINGLE-MEAL (legacy) mode ────────────────────────────────────────
        return $this->storeSingleMeal($request, $user, $restaurant, $validated, $totalGuests);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function storeMultiMeal(Request $request, $user, Restaurant $restaurant, array $validated, int $totalGuests)
    {
        $mealsData    = [];
        $estimatedTotal = 0.0;

        // Primary meal_id = first meal in the array (kept for backward compat on the DB row)
        $primaryMealId   = null;
        $primaryMealType = null;
        $primaryMealPrice = null;

        foreach ($validated['meals'] as $index => $item) {
            $mealId     = (int) $item['meal_id'];
            $mealGuests = isset($item['guests']) ? (int) $item['guests'] : $totalGuests;

            $meal = Meal::where('id', $mealId)
                ->where('restaurant_id', $restaurant->id)
                ->where('status', 'active')
                ->first();

            if (!$meal) {
                return response()->json([
                    'success' => false,
                    'message' => "Meal ID {$mealId} is not available for this restaurant.",
                ], 404);
            }

            $pricePerPerson = $meal->price !== null ? (float) $meal->price : null;

            // Supplement surcharges (optional, sent by frontend)
            $selectedSupplements = $item['supplements'] ?? [];
            $supplementCost      = 0.0;
            $supplementsBreakdown = [];
            $mealSupplements     = is_array($meal->supplements) ? $meal->supplements : [];

            foreach (['starter', 'main_course'] as $supKey) {
                $chosen = !empty($selectedSupplements[$supKey]);
                $sup    = $mealSupplements[$supKey] ?? null;

                if ($chosen && !empty($sup['available']) && isset($sup['price'])) {
                    $supPrice         = (float) $sup['price'];
                    $supplementCost  += $supPrice * $mealGuests;
                    $supplementsBreakdown[$supKey] = [
                        'selected'       => true,
                        'price'          => $supPrice,
                        'price_formatted' => Currency::format($supPrice),
                    ];
                } elseif ($chosen) {
                    $supplementsBreakdown[$supKey] = ['selected' => true, 'price' => null];
                }
            }

            $subtotal = $pricePerPerson !== null
                ? ($pricePerPerson * $mealGuests + $supplementCost)
                : ($supplementCost > 0 ? $supplementCost : null);

            if ($subtotal !== null) {
                $estimatedTotal += $subtotal;
            }

            $mealEntry = [
                'meal_id'             => $meal->id,
                'meal_type'           => $meal->meal_type,
                'meal_type_label'     => $meal->meal_type_label,
                'menu_description'    => $meal->menu_description,
                'price_per_person'    => $pricePerPerson,
                'price_formatted'     => $meal->price_eur_formatted,
                'guests'              => $mealGuests,
                'subtotal'            => $subtotal,
                'subtotal_formatted'  => $subtotal !== null ? Currency::format($subtotal) : null,
            ];

            if (!empty($supplementsBreakdown)) {
                $mealEntry['supplements_selected'] = $supplementsBreakdown;
            }

            $mealsData[] = $mealEntry;

            if ($index === 0) {
                $primaryMealId    = $meal->id;
                $primaryMealType  = $meal->meal_type_label;
                $primaryMealPrice = $pricePerPerson;
            }
        }

        $booking = Booking::create([
            'user_id'          => $user->id,
            'restaurant_id'    => $restaurant->id,
            'meal_id'          => $primaryMealId,
            'meals_data'       => $mealsData,
            'meal_type'        => count($mealsData) === 1 ? $primaryMealType : null,
            'meal_price'       => count($mealsData) === 1 ? $primaryMealPrice : null,
            'booking_date'     => $validated['date'],
            'booking_time'     => $validated['time'],
            'guests'           => $totalGuests,
            'guest_name'       => $validated['guest_name'] ?? null,
            'guest_phone'      => $validated['guest_phone'] ?? null,
            'guests_details'   => $validated['guests_details'] ?? [],
            'special_requests' => $validated['special_requests'] ?? null,
            'estimated_total'  => $estimatedTotal > 0 ? $estimatedTotal : null,
            'status'           => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data'    => $this->transformBooking($booking),
        ], 201);
    }

    private function storeSingleMeal(Request $request, $user, Restaurant $restaurant, array $validated, int $totalGuests)
    {
        if (empty($validated['meal_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide either a meals array or a meal_id.',
            ], 422);
        }

        $meal = Meal::where('id', $validated['meal_id'])
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'active')
            ->first();

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Selected meal is not available for this restaurant.',
            ], 404);
        }

        $mealPrice = $validated['meal_price']
            ?? $validated['meal_price_eur']   // legacy alias
            ?? $meal->price;

        // Supplement surcharges
        $selectedSupplements  = $validated['supplements'] ?? [];
        $supplementCost       = 0.0;
        $supplementsBreakdown = [];
        $mealSupplements      = is_array($meal->supplements) ? $meal->supplements : [];

        foreach (['starter', 'main_course'] as $supKey) {
            $chosen = !empty($selectedSupplements[$supKey]);
            $sup    = $mealSupplements[$supKey] ?? null;

            if ($chosen && !empty($sup['available']) && isset($sup['price'])) {
                $supPrice         = (float) $sup['price'];
                $supplementCost  += $supPrice * $totalGuests;
                $supplementsBreakdown[$supKey] = [
                    'selected'        => true,
                    'price'           => $supPrice,
                    'price_formatted' => Currency::format($supPrice),
                ];
            } elseif ($chosen) {
                $supplementsBreakdown[$supKey] = ['selected' => true, 'price' => null];
            }
        }

        $baseTotal      = $mealPrice ? (float) $mealPrice * $totalGuests : 0.0;
        $estimatedTotal = ($baseTotal + $supplementCost) > 0
            ? $baseTotal + $supplementCost
            : null;

        // Build meals_data entry
        $mealEntry = [
            'meal_id'             => $meal->id,
            'meal_type'           => $meal->meal_type,
            'meal_type_label'     => $meal->meal_type_label,
            'menu_description'    => $meal->menu_description,
            'price_per_person'    => $mealPrice !== null ? (float) $mealPrice : null,
            'price_formatted'     => $mealPrice !== null ? Currency::format((float) $mealPrice) : null,
            'guests'              => $totalGuests,
            'subtotal'            => $estimatedTotal,
            'subtotal_formatted'  => $estimatedTotal !== null ? Currency::format($estimatedTotal) : null,
        ];
        if (!empty($supplementsBreakdown)) {
            $mealEntry['supplements_selected'] = $supplementsBreakdown;
        }

        $mealsData = [$mealEntry];

        $booking = Booking::create([
            'user_id'          => $user->id,
            'restaurant_id'    => $restaurant->id,
            'meal_id'          => $meal->id,
            'meals_data'       => $mealsData,
            'meal_type'        => $validated['meal_type'] ?? $meal->meal_type_label,
            'meal_price'       => $mealPrice,
            'booking_date'     => $validated['date'],
            'booking_time'     => $validated['time'],
            'guests'           => $totalGuests,
            'guest_name'       => $validated['guest_name'] ?? null,
            'guest_phone'      => $validated['guest_phone'] ?? null,
            'guests_details'   => $validated['guests_details'] ?? [],
            'special_requests' => $validated['special_requests'] ?? null,
            'estimated_total'  => $estimatedTotal,
            'status'           => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data'    => $this->transformBooking($booking),
        ], 201);
    }

    /**
     * Get a single booking by ID (must belong to the authenticated user).
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

        $booking = Booking::with(['restaurant', 'meal'])
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
            'message' => 'Booking retrieved successfully.',
            'data'    => $this->transformBooking($booking),
        ], 200);
    }

    /**
     * Cancel a booking belonging to the authenticated user.
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

        $booking = Booking::where('user_id', $user->id)->find($id);

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
                'data'    => $this->transformBooking($booking),
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
            'message' => 'Booking cancelled successfully.',
            'data'    => $this->transformBooking($booking),
        ], 200);
    }

    /**
     * List current user's bookings.
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
            'status'   => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bookings = Booking::with(['restaurant', 'meal'])
            ->where('user_id', $user->id)
            ->when(
                isset($validated['status']) && $validated['status'] !== 'all',
                fn ($q) => $q->where('status', $validated['status'])
            )
            ->orderBy('booking_date', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $bookings->getCollection()->transform(fn ($booking) => $this->transformBooking($booking));

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully.',
            'data'    => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page'    => $bookings->lastPage(),
                'per_page'     => $bookings->perPage(),
                'total'        => $bookings->total(),
            ],
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Transform
    // ─────────────────────────────────────────────────────────────────────────

    private function transformBooking(Booking $booking): array
    {
        // Resolve meals list: prefer stored meals_data, fall back to legacy single meal
        $meals = $booking->meals_data ?? [];

        if (empty($meals) && $booking->meal_id) {
            // Legacy booking — synthesise a meals array from scalar columns
            $price    = $booking->meal_price ? (float) $booking->meal_price : null;
            $subtotal = $price && $booking->guests ? $price * $booking->guests : null;

            $meals = [[
                'meal_id'          => $booking->meal_id,
                'meal_type'        => $booking->meal?->meal_type,
                'meal_type_label'  => $booking->meal_type ?? $booking->meal?->meal_type_label,
                'menu_description' => $booking->meal?->menu_description,
                'price_per_person' => $price,
                'price_formatted'  => $price ? Currency::format($price) : null,
                'guests'           => $booking->guests,
                'subtotal'         => $subtotal,
                'subtotal_formatted' => $subtotal ? Currency::format($subtotal) : null,
            ]];
        }

        return [
            'id'                      => $booking->id,
            'restaurant_id'           => $booking->restaurant_id,
            'restaurant_name'         => $booking->restaurant?->restaurant_name,

            // Multi-meal breakdown
            'meals'                   => $meals,
            'meals_count'             => count($meals),

            // Legacy single-meal keys (kept for backward compatibility)
            'meal_id'                 => $booking->meal_id,
            'meal_type'               => $booking->meal_type ?? $booking->meal?->meal_type_label,
            'meal_type_key'           => $booking->meal?->meal_type,
            'meal_price'              => $booking->meal_price ? (float) $booking->meal_price : null,
            'meal_price_formatted'    => $booking->meal_price ? Currency::format((float) $booking->meal_price) : null,

            'currency'                => 'EUR',
            'date'                    => $booking->booking_date?->format('Y-m-d'),
            'time'                    => $booking->booking_time,
            'guests'                  => $booking->guests,
            'number_of_guests'        => $booking->guests,
            'guests_details'          => $booking->guests_details,
            'guest_name'              => $booking->guest_name,
            'guest_phone'             => $booking->guest_phone,
            'special_requests'        => $booking->special_requests,
            'status'                  => $booking->status,
            'estimated_total'         => $booking->estimated_total ? (float) $booking->estimated_total : null,
            'estimated_total_formatted' => $booking->estimated_total
                ? Currency::format((float) $booking->estimated_total) : null,
            'created_at'              => $booking->created_at?->toISOString(),
        ];
    }
}
