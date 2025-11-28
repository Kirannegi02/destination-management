<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Helper function to safely get table count
        $getCount = function($table) {
            try {
                return DB::table($table)->count();
            } catch (\Exception $e) {
                return 0;
            }
        };

        // Helper function to safely get table data
        $getData = function($table, $limit = 10) {
            try {
                return DB::table($table)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        };

        // Get statistics for dashboard
        $stats = [
            'total_bookings' => $getCount('bookings'),
            'pending_bookings' => $this->safeCount('bookings', ['status' => 'pending']),
            'confirmed_bookings' => $this->safeCount('bookings', ['status' => 'confirmed']),
            'cancelled_bookings' => $this->safeCount('bookings', ['status' => 'cancelled']),
            'total_revenue' => $this->safeSum('bookings', 'amount', ['status' => 'confirmed']),
            'total_services' => $getCount('services'),
            'total_destinations' => $getCount('destinations'),
        ];

        // Get recent bookings
        $recentBookings = $getData('bookings', 10);

        // Get notifications
        $notifications = $getData('notifications', 5);

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
}
