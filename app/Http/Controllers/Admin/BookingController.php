<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings.
     */
    public function index(Request $request)
    {
        $query = Booking::with(['restaurant', 'user', 'meal'])->orderBy('created_at', 'desc');

        // Free-text search: booking ID, guest name/phone, user name/email, restaurant name
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
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search) {
                        $restaurantQuery->where('restaurant_name', 'like', "%{$search}%");
                    });
            });
        }

        // Optional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by single booking date
        if ($request->filled('booking_date')) {
            $query->whereDate('booking_date', $request->booking_date);
        }

        $bookings = $query->paginate(15)->appends($request->query());

        return view('admin.bookings.index', compact('bookings'));
    }

    /**
     * Display the specified booking.
     */
    public function show(string $id)
    {
        $booking = Booking::with(['restaurant', 'user', 'meal'])->findOrFail($id);

        return view('admin.bookings.show', compact('booking'));
    }

    /**
     * Update booking status (pending, confirmed, cancelled).
     */
    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])],
        ]);

        $booking = Booking::findOrFail($id);
        $booking->status = $validated['status'];
        $booking->save();

        return redirect()
            ->route('admin.bookings.show', $id)
            ->with('success', 'Booking status updated to ' . ucfirst($validated['status']) . '.');
    }
}

