<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuideBooking;
use App\Models\GuidePackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuideBookingController extends Controller
{
    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        $validated = $request->validate([
            'guide_id' => 'required|exists:guides,id',
            'guide_package_id' => 'nullable|exists:guide_packages,id',
            'service_date' => 'required|date|after_or_equal:today',
            'start_time' => 'nullable|date_format:H:i',
            'duration_hours' => 'nullable|integer|min:1|max:72',
            'guests' => 'nullable|integer|min:1|max:50',
            'start_location' => 'nullable|string|max:255',
            'end_location' => 'nullable|string|max:255',
            'start_time_slot' => 'nullable|string|max:50',
            'special_requests' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:25',
            'contact_email' => 'nullable|email|max:255',
        ]);

        $guide = Guide::with('packages')->where('status', 'active')->where('display_on_website', true)->find($validated['guide_id']);
        if (!$guide) {
            return response()->json([
                'success' => false,
                'message' => 'Guide not available for booking.',
            ], 404);
        }

        $package = null;
        if (!empty($validated['guide_package_id'])) {
            $package = GuidePackage::where('id', $validated['guide_package_id'])
                ->where('guide_id', $guide->id)
                ->where('active', true)
                ->first();
            if (!$package) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected package is not available for this guide.',
                ], 422);
            }
        }

        $serviceDate = Carbon::parse($validated['service_date']);

        if ($guide->available_from_date && $serviceDate->lt($guide->available_from_date)) {
            return response()->json(['success' => false, 'message' => 'Guide is not available on this date (before start).'], 422);
        }
        if ($guide->available_to_date && $serviceDate->gt($guide->available_to_date)) {
            return response()->json(['success' => false, 'message' => 'Guide is not available on this date (after end).'], 422);
        }
        if (!empty($guide->blackout_dates) && in_array($serviceDate->toDateString(), $guide->blackout_dates, true)) {
            return response()->json(['success' => false, 'message' => 'This date is marked unavailable for the guide.'], 422);
        }
        if (!empty($guide->available_days)) {
            $dayKey = strtolower($serviceDate->format('D'));
            if (!in_array($dayKey, $guide->available_days, true)) {
                return response()->json(['success' => false, 'message' => 'Guide is not available on this weekday.'], 422);
            }
        }
        if ($guide->max_bookings_per_day) {
            $count = GuideBooking::where('guide_id', $guide->id)
                ->whereDate('service_date', $serviceDate->toDateString())
                ->whereNotIn('status', ['cancelled'])
                ->count();
            if ($count >= $guide->max_bookings_per_day) {
                return response()->json(['success' => false, 'message' => 'Maximum bookings reached for this date.'], 422);
            }
        }

        $duration = $validated['duration_hours']
            ?? $package->duration_hours
            ?? $guide->duration_hours
            ?? 3;

        $startTime = $validated['start_time'] ?? $guide->start_time?->format('H:i');
        $calculatedEnd = null;
        if ($startTime && $guide->end_time_auto_calculated) {
            $calculatedEnd = Carbon::createFromFormat('H:i', $startTime)->addHours($duration)->format('H:i');
        }

        $price = $package?->standard_price ?? $guide->price ?? $guide->base_price;

        $booking = GuideBooking::create([
            'user_id' => $user->id,
            'guide_id' => $guide->id,
            'guide_package_id' => $package?->id,
            'service_date' => $serviceDate->toDateString(),
            'start_time' => $startTime,
            'calculated_end_time' => $calculatedEnd,
            'duration_hours' => $duration,
            'guests' => $validated['guests'] ?? null,
            'start_location' => $validated['start_location'] ?? $guide->default_start_location,
            'end_location' => $validated['end_location'] ?? $guide->default_end_location,
            'start_time_slot' => $validated['start_time_slot'] ?? null,
            'status' => 'pending',
            'special_requests' => $validated['special_requests'] ?? null,
            'price' => $price,
            'currency' => $package?->currency ?? 'INR',
            'estimated_total' => $price,
            'contact_name' => $validated['contact_name'] ?? $user->name,
            'contact_phone' => $validated['contact_phone'] ?? $user->phone,
            'contact_email' => $validated['contact_email'] ?? $user->email,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Guide booking created successfully.',
            'data' => $this->transformBooking($booking),
        ], 201);
    }

    public function cancel(Request $request, string $id)
    {
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

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $this->transformBooking($booking),
        ], 200);
    }

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

        $bookings = GuideBooking::with(['guide', 'package'])
            ->where('user_id', $user->id)
            ->when(isset($validated['status']) && $validated['status'] !== 'all', fn($q) => $q->where('status', $validated['status']))
            ->orderBy('service_date', 'desc')
            ->paginate($validated['per_page'] ?? 15);

        $bookings->getCollection()->transform(fn($booking) => $this->transformBooking($booking));

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
            'start_time_slot' => $booking->start_time_slot,
            'special_requests' => $booking->special_requests,
            'price' => $booking->price ? (float) $booking->price : null,
            'currency' => $booking->currency,
            'estimated_total' => $booking->estimated_total ? (float) $booking->estimated_total : null,
            'status' => $booking->status,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}



