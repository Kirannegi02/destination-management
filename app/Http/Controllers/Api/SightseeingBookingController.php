<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sightseeing;
use App\Models\SightseeingBooking;
use App\Models\SightseeingOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SightseeingBookingController extends Controller
{
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

        $validated = $request->validate([
            'sightseeing_id' => 'required|exists:sightseeings,id',
            'sightseeing_option_id' => 'nullable|exists:sightseeing_options,id',
            'date' => 'required|date|after_or_equal:today',
            'pax_count' => 'required|integer|min:1',
            'guest_name' => 'nullable|string|max:255',
            'guest_phone' => 'nullable|string|max:25',
            'guests_details' => 'nullable|array',
            'guests_details.*.name' => 'required|string|max:255',
            'guests_details.*.country' => 'required|string|max:100',
            'guests_details.*.phone' => 'nullable|string|max:25',
            'special_requests' => 'nullable|string',
        ]);

        $sightseeing = Sightseeing::where('status', 'active')->find($validated['sightseeing_id']);
        if (!$sightseeing) {
            return response()->json([
                'success' => false,
                'message' => 'Sightseeing not available for booking.',
            ], 404);
        }

        $option = null;
        if (!empty($validated['sightseeing_option_id'])) {
            $option = SightseeingOption::where('sightseeing_id', $sightseeing->id)
                ->where('is_active', true)
                ->find($validated['sightseeing_option_id']);
            if (!$option) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected sightseeing option not found or inactive.',
                ], 404);
            }
        }

        $paxCount = (int) $validated['pax_count'];
        $currency = $option ? ($option->currency ?? $sightseeing->currency) : $sightseeing->currency;
        $basePrice = $option ? $option->base_price : $sightseeing->standard_price;
        $totalPrice = $basePrice !== null ? (float) $basePrice * $paxCount : 0;

        $booking = SightseeingBooking::create([
            'user_id' => $user->id,
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
            'booking_conditions_snapshot' => $sightseeing->booking_conditions,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sightseeing booking created successfully.',
            'data' => $this->transform($booking),
        ], 201);
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
            'booking_conditions' => $booking->booking_conditions_snapshot,
            'status' => $booking->status,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
