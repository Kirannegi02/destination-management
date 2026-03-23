<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SightseeingBooking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SightseeingBookingController extends Controller
{
    /**
     * Display a listing of sightseeing bookings.
     */
    public function index(Request $request)
    {
        $query = SightseeingBooking::with(['sightseeing', 'sightseeingOption', 'user'])
            ->orderBy('booking_date', 'desc');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('guest_name', 'like', "%{$search}%")
                    ->orWhere('guest_phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sightseeing', function ($sQuery) use ($search) {
                        $sQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('sightseeing_id')) {
            $query->where('sightseeing_id', $request->sightseeing_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('booking_date')) {
            $query->whereDate('booking_date', $request->booking_date);
        }

        $bookings = $query->paginate(15)->appends($request->query());

        return view('admin.sightseeing-bookings.index', compact('bookings'));
    }

    /**
     * Display the specified sightseeing booking.
     */
    public function show(string $id)
    {
        $booking = SightseeingBooking::with(['sightseeing', 'sightseeingOption', 'user'])->findOrFail($id);

        return view('admin.sightseeing-bookings.show', compact('booking'));
    }

    /**
     * Update booking status (pending, confirmed, cancelled).
     */
    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])],
        ]);

        $booking = SightseeingBooking::findOrFail($id);
        $booking->status = $validated['status'];
        $booking->save();

        return redirect()
            ->route('admin.sightseeing-bookings.show', $id)
            ->with('success', 'Booking status updated to ' . ucfirst($validated['status']) . '.');
    }
}
