<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $stats = [
            'total_restaurants' => $this->safeCount('restaurants'),
            'active_restaurants' => $this->safeCount('restaurants', ['status' => 'active']),
            'inactive_restaurants' => $this->safeCount('restaurants', ['status' => 'inactive']),
            'total_bookings' => $this->safeCount('bookings'),
            'pending_bookings' => $this->safeCount('bookings', ['status' => 'pending']),
            'confirmed_bookings' => $this->safeCount('bookings', ['status' => 'confirmed']),
            'cancelled_bookings' => $this->safeCount('bookings', ['status' => 'cancelled']),
            'total_revenue' => $this->safeSum('bookings', 'estimated_total', ['status' => 'confirmed']),
        ];

        $recentBookings = $this->getRecentBookings(10);
        $notifications = $this->safeGet('notifications', 5);

        return view('admin.dashboard', compact('stats', 'recentBookings', 'notifications'));
    }

    /**
     * Safely count records from a table.
     */
    private function safeCount($table, $conditions = [])
    {
        try {
            $query = DB::table($table);
            foreach ($conditions as $key => $value) {
                $query->where($key, $value);
            }
            return $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Safely sum a column from a table.
     */
    private function safeSum($table, $column, $conditions = [])
    {
        try {
            $query = DB::table($table);
            foreach ($conditions as $key => $value) {
                $query->where($key, $value);
            }
            return $query->sum($column) ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Safely get limited rows.
     */
    private function safeGet($table, $limit = 10)
    {
        try {
            return DB::table($table)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get recent bookings with restaurant info.
     */
    private function getRecentBookings($limit = 10)
    {
        try {
            return DB::table('bookings as b')
                ->leftJoin('restaurants as r', 'r.id', '=', 'b.restaurant_id')
                ->select(
                    'b.id',
                    'b.status',
                    'b.check_in',
                    'b.check_out',
                    'b.guests',
                    'b.rooms',
                    'b.estimated_total',
                    'r.restaurant_name'
                )
                ->orderBy('b.created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }
}
