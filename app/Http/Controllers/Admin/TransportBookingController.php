<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportBooking;
use Illuminate\Http\Request;

class TransportBookingController extends Controller
{
    public function index(Request $request)
    {
        $query = TransportBooking::with('vehicle')->orderBy('created_at', 'desc');
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('guest_name', 'like', "%{$s}%")
                    ->orWhere('guest_email', 'like', "%{$s}%")
                    ->orWhere('guest_phone', 'like', "%{$s}%")
                    ->orWhere('id', $s);
            });
        }
        $bookings = $query->paginate(15);
        $counts = [
            'all' => TransportBooking::count(),
            'pending' => TransportBooking::where('status', 'pending')->count(),
            'confirmed' => TransportBooking::where('status', 'confirmed')->count(),
            'cancelled' => TransportBooking::where('status', 'cancelled')->count(),
        ];
        return view('admin.transport-bookings.index', compact('bookings', 'counts'));
    }

    public function show(string $id)
    {
        $booking = TransportBooking::with('vehicle', 'user')->findOrFail($id);
        return view('admin.transport-bookings.show', compact('booking'));
    }

    public function updateStatus(Request $request, string $id)
    {
        $booking = TransportBooking::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,cancelled',
        ]);
        $booking->update(['status' => $validated['status']]);
        return redirect()->route('admin.transport-bookings.show', $id)->with('success', 'Status updated.');
    }
}
