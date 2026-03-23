<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransportBooking;
use App\Services\TransportQuoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransportQuoteController extends Controller
{
    protected TransportQuoteService $quoteService;

    public function __construct(TransportQuoteService $quoteService)
    {
        $this->quoteService = $quoteService;
    }

    /**
     * Get quote (no auth required). Distance is calculated via API from pick to drop; user does not send distance_km.
     *
     * Form should ask:
     * - trip_type: one_way | return (round trip) | multicity
     * - passengers: number (vehicle selected by capacity)
     * - cities: [start, ... end]
     * - days_per_city: [n1, n2, ...] days staying in each city
     * - legs_by_train: for each leg, true = travel between cities by other vehicle (e.g. public/train), false = by your vehicle (intercity)
     * - legs (optional): for each leg, pick/drop used for distance API:
     *   - By your vehicle: legs[i].pickup, legs[i].drop (addresses or city names)
     *   - By other vehicle: legs[i].pickup_city_a, legs[i].drop_city_a (within first city), legs[i].pickup_city_b, legs[i].drop_city_b (within second city)
     */
    public function quote(Request $request)
    {
        $validated = $request->validate([
            'trip_type' => 'required|in:one_way,return,multicity',
            'passengers' => 'required|integer|min:1|max:200',
            'cities' => 'required|array|min:2',
            'cities.*' => 'required|string|max:255',
            'days_per_city' => 'required|array|min:1',
            'days_per_city.*' => 'integer|min:0',
            'legs_by_train' => 'nullable|array',
            'legs_by_train.*' => 'boolean',
            'legs' => 'nullable|array',
            'legs.*.by_our_vehicle' => 'nullable|boolean',
            'legs.*.pickup' => 'nullable|string|max:500',
            'legs.*.drop' => 'nullable|string|max:500',
            'legs.*.pickup_city_a' => 'nullable|string|max:500',
            'legs.*.drop_city_a' => 'nullable|string|max:500',
            'legs.*.pickup_city_b' => 'nullable|string|max:500',
            'legs.*.drop_city_b' => 'nullable|string|max:500',
        ]);

        $result = $this->quoteService->buildQuote($validated);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'vehicle' => $result['vehicle'] ?? null,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => [
                'vehicle' => $result['vehicle'],
                'line_items' => $result['line_items'],
                'total_amount' => $result['total_amount'],
                'currency' => $result['currency'],
            ],
        ], 200);
    }

    /**
     * Submit a quote request: save the enquiry and return the quotation in the response.
     * No booking is made – user gets the quotation only. Admin can follow up from Quote Requests.
     */
    public function store(Request $request)
    {
        $rules = [
            'trip_type' => 'required|in:one_way,return,multicity',
            'passengers' => 'required|integer|min:1|max:200',
            'cities' => 'required|array|min:2',
            'cities.*' => 'required|string|max:255',
            'days_per_city' => 'required|array|min:1',
            'days_per_city.*' => 'integer|min:0',
            'legs_by_train' => 'nullable|array',
            'legs_by_train.*' => 'boolean',
            'legs' => 'nullable|array',
            'legs.*.by_our_vehicle' => 'nullable|boolean',
            'legs.*.pickup' => 'nullable|string|max:500',
            'legs.*.drop' => 'nullable|string|max:500',
            'legs.*.pickup_city_a' => 'nullable|string|max:500',
            'legs.*.drop_city_a' => 'nullable|string|max:500',
            'legs.*.pickup_city_b' => 'nullable|string|max:500',
            'legs.*.drop_city_b' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:2000',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email',
            'guest_phone' => 'nullable|string|max:50',
            'guest_country' => 'nullable|string|max:100',
        ];

        $user = auth('api')->user();
        if (!$user) {
            $rules['guest_name'] = 'required|string|max:255';
            $rules['guest_email'] = 'required|email';
            $rules['guest_phone'] = 'required|string|max:50';
        } elseif ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your agent account is pending verification. You cannot submit transport quote requests until you are approved by admin.',
            ], 403);
        }

        $validated = $request->validate($rules);

        $quoteInput = [
            'trip_type' => $validated['trip_type'],
            'passengers' => $validated['passengers'],
            'cities' => $validated['cities'],
            'days_per_city' => $validated['days_per_city'],
            'legs_by_train' => $validated['legs_by_train'] ?? [],
            'legs' => $validated['legs'] ?? [],
        ];

        $result = $this->quoteService->buildQuote($quoteInput);
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        $vehicle = $result['vehicle'] ? \App\Models\Vehicle::find($result['vehicle']['id']) : null;

        $quoteRequest = TransportBooking::create([
            'user_id' => $user?->id,
            'vehicle_id' => $vehicle?->id,
            'trip_type' => $validated['trip_type'],
            'passengers' => $validated['passengers'],
            'cities' => $validated['cities'],
            'days_per_city' => $validated['days_per_city'],
            'legs_by_train' => $validated['legs_by_train'] ?? [],
            'legs' => $validated['legs'] ?? [],
            'remarks' => $validated['remarks'] ?? null,
            'quote_breakdown' => [
                'line_items' => $result['line_items'],
                'total_amount' => $result['total_amount'],
                'currency' => $result['currency'],
                'vehicle' => $result['vehicle'],
            ],
            'total_amount' => $result['total_amount'],
            'currency' => $result['currency'],
            'status' => TransportBooking::STATUS_PENDING,
            'guest_name' => $validated['guest_name'] ?? $user?->name,
            'guest_email' => $validated['guest_email'] ?? $user?->email,
            'guest_phone' => $validated['guest_phone'] ?? $user?->phone,
            'guest_country' => $validated['guest_country'] ?? $user?->country,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quote request received. Your quotation is below. No booking has been made.',
            'data' => $this->transformBooking($quoteRequest),
        ], 201);
    }

    /**
     * List quote requests submitted by the authenticated user (with quotations).
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'status' => 'nullable|in:all,quote,pending,confirmed,cancelled',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = TransportBooking::with('vehicle')
            ->where('user_id', $user->id)
            ->when(
                isset($validated['status']) && $validated['status'] !== 'all',
                fn ($q) => $q->where('status', $validated['status'])
            )
            ->orderBy('created_at', 'desc');

        $bookings = $query->paginate($validated['per_page'] ?? 15);
        $items = $bookings->getCollection()->map(fn ($b) => $this->transformBooking($b));

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ], 200);
    }

    /**
     * Get single quote request and quotation (own only).
     */
    public function show(string $id)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }
        $booking = TransportBooking::with('vehicle')->where('user_id', $user->id)->find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }
        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($booking),
        ], 200);
    }

    protected function transformBooking(TransportBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'trip_type' => $booking->trip_type,
            'passengers' => $booking->passengers,
            'cities' => $booking->cities,
            'days_per_city' => $booking->days_per_city,
            'quote_breakdown' => $booking->quote_breakdown,
            'total_amount' => $booking->total_amount ? (float) $booking->total_amount : null,
            'currency' => $booking->currency,
            'status' => $booking->status,
            'vehicle' => $booking->vehicle ? [
                'id' => $booking->vehicle->id,
                'name' => $booking->vehicle->name,
                'capacity_seats' => $booking->vehicle->capacity_seats,
            ] : null,
            'guest_name' => $booking->guest_name,
            'guest_email' => $booking->guest_email,
            'guest_phone' => $booking->guest_phone,
            'guest_country' => $booking->guest_country,
            'remarks' => $booking->remarks,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
