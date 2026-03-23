<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GuideBooking;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuideBookingController extends Controller
{
    /**
     * Display a listing of guide bookings.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'service_date' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = GuideBooking::with(['guide', 'user', 'package'])
            ->orderBy('service_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('contact_phone', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('guide', function ($guideQuery) use ($search) {
                        $guideQuery->where('full_name', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%");
                    });
            });
        }

        if (isset($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['service_date'])) {
            $query->whereDate('service_date', $validated['service_date']);
        }

        $bookings = $query->paginate($validated['per_page'] ?? 15)->appends($request->query());

        return view('admin.guide-bookings.index', [
            'bookings' => $bookings,
            'filters' => $validated,
        ]);
    }

    /**
     * Display a single guide booking.
     */
    public function show(string $id)
    {
        $booking = GuideBooking::with(['guide', 'user', 'package'])->findOrFail($id);

        return view('admin.guide-bookings.show', compact('booking'));
    }

    /**
     * Update booking status (pending, confirmed, cancelled).
     */
    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])],
        ]);

        $booking = GuideBooking::findOrFail($id);
        $booking->status = $validated['status'];
        $booking->save();

        return redirect()
            ->route('admin.guide-bookings.show', $id)
            ->with('success', 'Booking status updated to ' . ucfirst($validated['status']) . '.');
    }
}


