<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
    * Create a new restaurant booking (no payment).
    */
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'required|integer|min:1',
            'guests' => 'nullable|integer|min:1',
            'guests_details' => 'required|array|min:1',
            'guests_details.*.name' => 'required|string|max:255',
            'guests_details.*.country' => 'required|string|max:100',
            'guests_details.*.phone' => 'nullable|string|max:25',
            'guest_name' => 'nullable|string|max:255', // legacy single guest fields (kept for compatibility)
            'guest_phone' => 'nullable|string|max:25', // legacy single guest fields (kept for compatibility)
            'special_requests' => 'nullable|string',
        ]);

        // Ensure restaurant exists and is active
        $restaurant = Restaurant::where('id', $validated['restaurant_id'])
            ->where('status', 'active')
            ->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant not available for booking.',
            ], 404);
        }

        $guestCount = $validated['guests'] ?? count($validated['guests_details']);
        $estimatedTotal = $restaurant->price ? $restaurant->price * $validated['rooms'] : null;

        $booking = Booking::create([
            'user_id' => $user->id,
            'restaurant_id' => $validated['restaurant_id'],
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'rooms' => $validated['rooms'],
            'guests' => $guestCount,
            'guest_name' => $validated['guest_name'] ?? null,
            'guest_phone' => $validated['guest_phone'] ?? null,
            'guests_details' => $validated['guests_details'],
            'special_requests' => $validated['special_requests'] ?? null,
            'estimated_total' => $estimatedTotal,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => $this->transformBooking($booking)
        ], 201);
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
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $booking = Booking::where('user_id', $user->id)->find($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found.'
            ], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => true,
                'message' => 'Booking already cancelled.',
                'data' => $this->transformBooking($booking)
            ], 200);
        }

        if ($booking->status === 'confirmed') {
            return response()->json([
                'success' => false,
                'message' => 'Confirmed bookings cannot be cancelled.'
            ], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $this->transformBooking($booking)
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
                'message' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $bookings = Booking::with('restaurant')
            ->where('user_id', $user->id)
            ->when(
                isset($validated['status']) && $validated['status'] !== 'all',
                fn($q) => $q->where('status', $validated['status'])
            )
            ->orderBy('check_in', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $bookings->getCollection()->transform(function ($booking) {
            return $this->transformBooking($booking);
        });

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully.',
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ], 200);
    }

    private function transformBooking(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'restaurant_id' => $booking->restaurant_id,
            'restaurant_name' => $booking->restaurant?->restaurant_name,
            'check_in' => $booking->check_in?->toISOString(),
            'check_out' => $booking->check_out?->toISOString(),
            'rooms' => $booking->rooms,
            'guests' => $booking->guests,
            'guests_details' => $booking->guests_details,
            'guest_name' => $booking->guest_name,
            'guest_phone' => $booking->guest_phone,
            'special_requests' => $booking->special_requests,
            'status' => $booking->status,
            'estimated_total' => $booking->estimated_total ? (float) $booking->estimated_total : null,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}

