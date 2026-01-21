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
        ]);

        $query = GuideBooking::with(['guide', 'user', 'package'])
            ->orderBy('service_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (isset($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        $bookings = $query->paginate($validated['per_page'] ?? 15);

        return view('admin.guide-bookings.index', [
            'bookings' => $bookings,
            'filters' => $validated,
        ]);
    }
}


