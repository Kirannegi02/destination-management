<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivateVenue;
use App\Models\PrivateVenueBooking;
use App\Models\PrivateVenueSpace;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PrivateVenueBookingController extends Controller
{
    private function itemRules(string $prefix = ''): array
    {
        $p = $prefix ? $prefix.'.' : '';
        $eventTypes = array_keys(PrivateVenue::eventTypesList());
        $setups = array_keys(PrivateVenue::setupTypes());

        return [
            $p.'private_venue_id' => 'required|exists:private_venues,id',
            $p.'private_venue_space_id' => 'nullable|exists:private_venue_spaces,id',
            $p.'event_name' => 'nullable|string|max:255',
            $p.'event_type' => ['nullable', Rule::in($eventTypes)],
            $p.'event_date_start' => 'required|date|after_or_equal:today',
            $p.'event_date_end' => 'nullable|date',
            $p.'start_time' => 'nullable|date_format:H:i',
            $p.'end_time' => 'nullable|date_format:H:i',
            $p.'guests' => 'required|integer|min:1|max:50000',
            $p.'setup_style' => ['nullable', Rule::in($setups)],
            $p.'special_requests' => 'nullable|string',
            $p.'contact_name' => 'nullable|string|max:255',
            $p.'contact_phone' => 'nullable|string|max:25',
            $p.'contact_email' => 'nullable|email|max:255',
        ];
    }

    public function store(Request $request)
    {
        if ($request->filled('private_venue_id') && is_array($request->input('private_venue_ids'))) {
            $base = $request->except(['private_venue_ids']);
            $items = [];
            foreach ($request->input('private_venue_ids', []) as $vid) {
                $items[] = array_merge($base, ['private_venue_id' => $vid]);
            }
            $request->merge(['items' => $items]);
        }

        if (is_array($request->input('items'))) {
            return $this->storeBatch($request);
        }

        $user = $this->authenticateBookingUser();
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $validated = $request->validate($this->itemRules());

        try {
            $booking = $this->createBookingRecord($user, $validated);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()[array_key_first($e->errors())][0] ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Venue booking request submitted successfully.',
            'data' => $this->transformBooking($booking->load(['venue', 'space'])),
        ], 201);
    }

    public function storeBatch(Request $request)
    {
        $user = $this->authenticateBookingUser();
        if ($user instanceof \Illuminate\Http\JsonResponse) {
            return $user;
        }

        $base = $request->validate([
            'items' => 'required|array|min:1|max:50',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:25',
            'contact_email' => 'nullable|email|max:255',
        ]);

        $request->validate($this->itemRules('items.*'));

        $defaults = [
            'contact_name' => $base['contact_name'] ?? null,
            'contact_phone' => $base['contact_phone'] ?? null,
            'contact_email' => $base['contact_email'] ?? null,
        ];

        $created = [];

        try {
            DB::transaction(function () use ($request, $user, $defaults, &$created) {
                foreach ($request->input('items', []) as $index => $row) {
                    $merged = array_merge($defaults, $row);
                    foreach (['contact_name', 'contact_phone', 'contact_email'] as $k) {
                        if (! empty($row[$k])) {
                            $merged[$k] = $row[$k];
                        }
                    }
                    $meta = ['batch' => ['index' => $index, 'total' => count($request->input('items', []))]];
                    try {
                        $created[] = $this->createBookingRecord($user, $merged, $meta);
                    } catch (ValidationException $e) {
                        $prefixed = [];
                        foreach ($e->errors() as $field => $messages) {
                            $prefixed['items.'.$index.'.'.$field] = $messages;
                        }
                        throw ValidationException::withMessages($prefixed);
                    }
                }
            });
        } catch (ValidationException $e) {
            $key = array_key_first($e->errors());

            return response()->json([
                'success' => false,
                'message' => $e->errors()[$key][0] ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        $loaded = PrivateVenueBooking::with(['venue', 'space'])
            ->whereIn('id', collect($created)->pluck('id'))
            ->orderBy('id')
            ->get();

        $total = $loaded->sum(fn ($b) => (float) ($b->estimated_total ?? 0));

        return response()->json([
            'success' => true,
            'message' => count($loaded).' venue booking(s) submitted successfully.',
            'data' => [
                'bookings' => $loaded->map(fn ($b) => $this->transformBooking($b))->values()->all(),
                'count' => $loaded->count(),
                'combined_estimated_total' => $total > 0 ? round($total, 2) : null,
            ],
        ], 201);
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'pending', 'confirmed', 'cancelled'])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $bookings = PrivateVenueBooking::with(['venue', 'space'])
            ->where('user_id', $user->id)
            ->when(isset($validated['status']) && $validated['status'] !== 'all', fn ($q) => $q->where('status', $validated['status']))
            ->orderByDesc('event_date_start')
            ->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 15);

        $bookings->getCollection()->transform(fn ($b) => $this->transformBooking($b));

        return response()->json([
            'success' => true,
            'message' => 'Private venue bookings retrieved successfully.',
            'data' => $bookings->items(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(string $id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $booking = PrivateVenueBooking::with(['venue', 'space'])
            ->where('user_id', $user->id)
            ->find($id);

        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformBooking($booking),
        ]);
    }

    public function cancel(string $id)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $booking = PrivateVenueBooking::where('user_id', $user->id)->find($id);
        if (! $booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found.'], 404);
        }

        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => true,
                'message' => 'Booking already cancelled.',
                'data' => $this->transformBooking($booking->load(['venue', 'space'])),
            ]);
        }

        if ($booking->status === 'confirmed') {
            return response()->json(['success' => false, 'message' => 'Confirmed bookings cannot be cancelled online.'], 400);
        }

        $booking->status = 'cancelled';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully.',
            'data' => $this->transformBooking($booking->load(['venue', 'space'])),
        ]);
    }

    private function authenticateBookingUser()
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Please login first.'], 401);
        }

        if ($user->status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval. You cannot book venues yet.',
            ], 403);
        }

        return $user;
    }

    private function createBookingRecord(User $user, array $validated, ?array $meta = null): PrivateVenueBooking
    {
        $venue = PrivateVenue::where('status', 'active')->find($validated['private_venue_id']);
        if (! $venue) {
            throw ValidationException::withMessages(['private_venue_id' => ['Venue is not available for booking.']]);
        }

        $space = null;
        if (! empty($validated['private_venue_space_id'])) {
            $space = PrivateVenueSpace::where('id', $validated['private_venue_space_id'])
                ->where('private_venue_id', $venue->id)
                ->where('status', 'active')
                ->first();
            if (! $space) {
                throw ValidationException::withMessages(['private_venue_space_id' => ['Selected meeting room is not available.']]);
            }
        }

        $guests = (int) $validated['guests'];
        if ($venue->min_event_size && $guests < $venue->min_event_size) {
            throw ValidationException::withMessages(['guests' => ["Minimum event size for this venue is {$venue->min_event_size}."]]);
        }
        if ($venue->max_event_size && $guests > $venue->max_event_size) {
            throw ValidationException::withMessages(['guests' => ["Maximum event size for this venue is {$venue->max_event_size}."]]);
        }

        if ($space && is_array($space->setup_capacities) && ! empty($validated['setup_style'])) {
            $cap = $space->setup_capacities[$validated['setup_style']] ?? null;
            if ($cap && $guests > (int) $cap) {
                throw ValidationException::withMessages(['guests' => ["Maximum capacity for {$validated['setup_style']} setup in this room is {$cap}."]]);
            }
        }

        $start = Carbon::parse($validated['event_date_start']);
        $end = ! empty($validated['event_date_end'])
            ? Carbon::parse($validated['event_date_end'])
            : $start->copy();

        if ($end->lt($start)) {
            throw ValidationException::withMessages(['event_date_end' => ['End date must be on or after start date.']]);
        }

        $days = max(1, $start->diffInDays($end) + 1);
        $estimated = null;
        if ($venue->starting_daily_rate) {
            $estimated = round((float) $venue->starting_daily_rate * $days, 2);
        }

        return PrivateVenueBooking::create([
            'user_id' => $user->id,
            'private_venue_id' => $venue->id,
            'private_venue_space_id' => $space?->id,
            'event_name' => $validated['event_name'] ?? null,
            'event_type' => $validated['event_type'] ?? null,
            'event_date_start' => $start->toDateString(),
            'event_date_end' => $end->toDateString(),
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'guests' => $guests,
            'setup_style' => $validated['setup_style'] ?? null,
            'estimated_total' => $estimated,
            'currency' => $venue->currency ?? 'EUR',
            'status' => 'pending',
            'special_requests' => $validated['special_requests'] ?? null,
            'contact_name' => $validated['contact_name'] ?? $user->name,
            'contact_phone' => $validated['contact_phone'] ?? $user->phone,
            'contact_email' => $validated['contact_email'] ?? $user->email,
            'metadata' => $meta,
        ]);
    }

    private function transformBooking(PrivateVenueBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'private_venue_id' => $booking->private_venue_id,
            'venue_name' => $booking->venue?->name,
            'venue_city' => $booking->venue?->city,
            'private_venue_space_id' => $booking->private_venue_space_id,
            'space_name' => $booking->space?->name,
            'event_name' => $booking->event_name,
            'event_type' => $booking->event_type,
            'event_type_label' => $booking->event_type
                ? (PrivateVenue::eventTypesList()[$booking->event_type] ?? $booking->event_type)
                : null,
            'event_date_start' => $booking->event_date_start?->toDateString(),
            'event_date_end' => $booking->event_date_end?->toDateString(),
            'start_time' => $booking->start_time?->format('H:i'),
            'end_time' => $booking->end_time?->format('H:i'),
            'guests' => $booking->guests,
            'setup_style' => $booking->setup_style,
            'setup_style_label' => $booking->setup_style
                ? (PrivateVenue::setupTypes()[$booking->setup_style] ?? $booking->setup_style)
                : null,
            'estimated_total' => $booking->estimated_total ? (float) $booking->estimated_total : null,
            'currency' => $booking->currency,
            'status' => $booking->status,
            'special_requests' => $booking->special_requests,
            'contact_name' => $booking->contact_name,
            'contact_phone' => $booking->contact_phone,
            'contact_email' => $booking->contact_email,
            'created_at' => $booking->created_at?->toISOString(),
        ];
    }
}
