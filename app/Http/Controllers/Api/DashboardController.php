<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\GuideBooking;
use App\Models\SightseeingBooking;
use App\Models\SouvenirOrder;
use App\Models\TransportBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Unified dashboard endpoint for all user/agent bookings.
     *
     * Query params:
     * - module: all|restaurant|guide|sightseeing|transport|souvenir (default all)
     * - status: all|pending|confirmed|cancelled|... (default all)
     * - page: integer >= 1
     * - per_page: integer 1..100 (default 15)
     */
    public function index(Request $request)
    {
        Log::info('Dashboard API hit', [
            'path' => $request->path(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'auth_header_present' => $request->hasHeader('Authorization'),
            'auth_guard_check' => auth('api')->check(),
        ]);

        $user = auth('api')->user();
        if (!$user) {
            Log::warning('Dashboard API unauthorized access', [
                'ip' => $request->ip(),
                'auth_header_present' => $request->hasHeader('Authorization'),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please login first.',
            ], 401);
        }

        $validated = $request->validate([
            'module' => 'nullable|in:all,restaurant,guide,sightseeing,transport,souvenir',
            'status' => 'nullable|string|max:50',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $module = $validated['module'] ?? 'all';
        $status = $validated['status'] ?? 'all';
        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 15);

        Log::info('Dashboard API request authenticated', [
            'user_id' => $user->id,
            'user_status' => $user->status ?? null,
            'module' => $module,
            'status_filter' => $status,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        $items = collect();

        if ($this->moduleAllowed($module, 'restaurant')) {
            $restaurantBookings = Booking::with(['restaurant', 'meal'])
                ->where('user_id', $user->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->get()
                ->map(function (Booking $b) {
                    return [
                        'module' => 'restaurant',
                        'module_label' => 'Restaurant Booking',
                        'booking_id' => $b->id,
                        'title' => $b->restaurant?->restaurant_name,
                        'subtitle' => $b->meal_type ?? $b->meal?->meal_type_label,
                        'booking_date' => $b->booking_date?->format('Y-m-d'),
                        'booking_time' => $b->booking_time,
                        'status' => $b->status,
                        'amount' => $b->estimated_total !== null ? (float) $b->estimated_total : null,
                        'currency' => 'EUR',
                        'quantity' => $b->guests,
                        'created_at' => $b->created_at?->toISOString(),
                        'details' => [
                            'restaurant_id' => $b->restaurant_id,
                            'meal_id' => $b->meal_id,
                            'guest_name' => $b->guest_name,
                            'guest_phone' => $b->guest_phone,
                        ],
                    ];
                });

            $items = $items->concat($restaurantBookings);
        }

        if ($this->moduleAllowed($module, 'guide')) {
            $guideBookings = GuideBooking::with(['guide', 'package'])
                ->where('user_id', $user->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->get()
                ->map(function (GuideBooking $b) {
                    return [
                        'module' => 'guide',
                        'module_label' => 'Guide Booking',
                        'booking_id' => $b->id,
                        'title' => $b->guide?->full_name ?? $b->guide?->title,
                        'subtitle' => $b->package?->service_name,
                        'booking_date' => $b->service_date?->format('Y-m-d'),
                        'booking_time' => $b->start_time?->format('H:i'),
                        'status' => $b->status,
                        'amount' => $b->estimated_total !== null
                            ? (float) $b->estimated_total
                            : ($b->price !== null ? (float) $b->price : null),
                        'currency' => $b->currency,
                        'quantity' => $b->guests,
                        'created_at' => $b->created_at?->toISOString(),
                        'details' => [
                            'guide_id' => $b->guide_id,
                            'guide_package_id' => $b->guide_package_id,
                            'end_time' => $b->calculated_end_time?->format('H:i'),
                            'duration_hours' => $b->duration_hours,
                        ],
                    ];
                });

            $items = $items->concat($guideBookings);
        }

        if ($this->moduleAllowed($module, 'sightseeing')) {
            $sightseeingBookings = SightseeingBooking::with(['sightseeing', 'sightseeingOption'])
                ->where('user_id', $user->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->get()
                ->map(function (SightseeingBooking $b) {
                    return [
                        'module' => 'sightseeing',
                        'module_label' => 'Sightseeing Booking',
                        'booking_id' => $b->id,
                        'title' => $b->sightseeing?->title,
                        'subtitle' => $b->sightseeingOption?->name,
                        'booking_date' => $b->booking_date?->format('Y-m-d'),
                        'booking_time' => null,
                        'status' => $b->status,
                        'amount' => $b->price !== null ? (float) $b->price : null,
                        'currency' => $b->currency,
                        'quantity' => $b->pax_count,
                        'created_at' => $b->created_at?->toISOString(),
                        'details' => [
                            'sightseeing_id' => $b->sightseeing_id,
                            'sightseeing_option_id' => $b->sightseeing_option_id,
                            'guest_name' => $b->guest_name,
                            'guest_phone' => $b->guest_phone,
                        ],
                    ];
                });

            $items = $items->concat($sightseeingBookings);
        }

        if ($this->moduleAllowed($module, 'transport')) {
            $transportBookings = TransportBooking::with('vehicle')
                ->where('user_id', $user->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->get()
                ->map(function (TransportBooking $b) {
                    return [
                        'module' => 'transport',
                        'module_label' => 'Transport Booking',
                        'booking_id' => $b->id,
                        'title' => $b->vehicle?->name,
                        'subtitle' => $b->trip_type,
                        'booking_date' => null,
                        'booking_time' => null,
                        'status' => $b->status,
                        'amount' => $b->total_amount !== null ? (float) $b->total_amount : null,
                        'currency' => $b->currency,
                        'quantity' => $b->passengers,
                        'created_at' => $b->created_at?->toISOString(),
                        'details' => [
                            'trip_type' => $b->trip_type,
                            'cities' => $b->cities,
                            'vehicle_id' => $b->vehicle_id,
                        ],
                    ];
                });

            $items = $items->concat($transportBookings);
        }

        if ($this->moduleAllowed($module, 'souvenir')) {
            $souvenirOrders = SouvenirOrder::with('items')
                ->where('user_id', $user->id)
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->get()
                ->map(function (SouvenirOrder $o) {
                    return [
                        'module' => 'souvenir',
                        'module_label' => 'Souvenir Order',
                        'booking_id' => $o->id,
                        'title' => 'Souvenir Order #' . $o->id,
                        'subtitle' => $o->requested_delivery_date?->format('Y-m-d'),
                        'booking_date' => $o->requested_delivery_date?->format('Y-m-d'),
                        'booking_time' => null,
                        'status' => $o->status,
                        'amount' => $o->total !== null ? (float) $o->total : null,
                        'currency' => $o->currency,
                        'quantity' => $o->items->sum('quantity'),
                        'created_at' => $o->created_at?->toISOString(),
                        'details' => [
                            'items_count' => $o->items->count(),
                            'delivery_too_close' => (bool) $o->delivery_too_close,
                            'pending_restock' => (bool) $o->pending_restock,
                        ],
                    ];
                });

            $items = $items->concat($souvenirOrders);
        }

        $sorted = $items
            ->sortByDesc(function (array $row) {
                return $row['created_at'] ?? '';
            })
            ->values();

        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min($page, $lastPage);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = $sorted->slice($offset, $perPage)->values()->all();

        $moduleCounts = $sorted
            ->groupBy('module')
            ->map(fn ($rows) => $rows->count())
            ->all();

        Log::info('Dashboard API response prepared', [
            'user_id' => $user->id,
            'total_items' => $total,
            'module_counts' => $moduleCounts,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard bookings retrieved successfully.',
            'data' => $paginatedItems,
            'meta' => [
                'filters' => [
                    'module' => $module,
                    'status' => $status,
                ],
                'counts' => [
                    'total' => $total,
                    'by_module' => $moduleCounts,
                ],
                'pagination' => [
                    'current_page' => $currentPage,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
            ],
        ], 200);
    }

    private function moduleAllowed(string $requested, string $current): bool
    {
        return $requested === 'all' || $requested === $current;
    }
}

