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
        $stats = [
            // Restaurants
            'total_restaurants' => $this->safeCount('restaurants'),
            'active_restaurants' => $this->safeCount('restaurants', ['status' => 'active']),
            'inactive_restaurants' => $this->safeCount('restaurants', ['status' => 'inactive']),

            // Guides
            'total_guides' => $this->safeCount('guides'),
            'active_guides' => $this->safeCount('guides', ['status' => 'active']),
            'inactive_guides' => $this->safeCount('guides', ['status' => 'inactive']),

            // Sightseeings
            'total_sightseeings' => $this->safeCount('sightseeings'),
            'active_sightseeings' => $this->safeCount('sightseeings', ['status' => 'active']),
            'inactive_sightseeings' => $this->safeCount('sightseeings', ['status' => 'inactive']),

            // Transports
            'total_transports' => $this->safeCount('transports'),
            'active_transports' => $this->safeCount('transports', ['status' => 'active']),
            'inactive_transports' => $this->safeCount('transports', ['status' => 'inactive']),

            // Souvenirs
            'total_souvenirs' => $this->safeCount('souvenirs'),
            'active_souvenirs' => $this->safeCount('souvenirs', ['status' => 'active']),
            'inactive_souvenirs' => $this->safeCount('souvenirs', ['status' => 'inactive']),
        ];

        $recentGuides = $this->getRecentGuides(6);
        $recentRestaurants = $this->getRecentRestaurants(6);
        $recentSightseeings = $this->getRecentSightseeings(6);
        $recentTransports = $this->getRecentTransports(6);
        $recentSouvenirs = $this->getRecentSouvenirs(6);
        $notifications = $this->safeGet('notifications', 5);

        $charts = $this->buildModuleCharts();

        return view('admin.dashboard', compact(
            'stats',
            'recentGuides',
            'recentRestaurants',
            'recentSightseeings',
            'recentTransports',
            'recentSouvenirs',
            'notifications',
            'charts'
        ));
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
     * Build simple monthly charts for modules.
     */
    private function buildModuleCharts(): array
    {
        $year = date('Y');
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $modules = [
            'guides' => 'guides',
            'restaurants' => 'restaurants',
            'sightseeings' => 'sightseeings',
            'transports' => 'transports',
            'souvenirs' => 'souvenirs',
        ];

        $series = [];

        foreach ($modules as $key => $table) {
            try {
                $rows = DB::table($table)
                    ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                    ->whereYear('created_at', $year)
                    ->groupBy('month')
                    ->pluck('total', 'month')
                    ->toArray();
            } catch (\Exception $e) {
                $rows = [];
            }

            $data = [];
            for ($m = 1; $m <= 12; $m++) {
                $data[] = (int)($rows[$m] ?? 0);
            }
            $series[$key] = $data;
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * Get recent guides.
     */
    private function getRecentGuides($limit = 10)
    {
        try {
            return DB::table('guides')
                ->select('id', 'title', 'city', 'country', 'half_day_price', 'full_day_price', 'extra_hour_price', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get recent restaurants.
     */
    private function getRecentRestaurants($limit = 10)
    {
        try {
            return DB::table('restaurants')
                ->select('id', 'restaurant_name', 'city', 'state', 'star_rating', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get recent sightseeings.
     */
    private function getRecentSightseeings($limit = 10)
    {
        try {
            return DB::table('sightseeings')
                ->select('id', 'title', 'city', 'country', 'standard_price', 'currency', 'is_featured', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get recent transports.
     */
    private function getRecentTransports($limit = 10)
    {
        try {
            return DB::table('transports as t')
                ->leftJoin('vehicles as v', 'v.id', '=', 't.vehicle_id')
                ->select(
                    't.id',
                    't.location',
                    't.price_per_km',
                    't.price_per_day',
                    't.status',
                    't.created_at',
                    'v.name as vehicle_name'
                )
                ->orderBy('t.created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Get recent souvenirs.
     */
    private function getRecentSouvenirs($limit = 10)
    {
        try {
            return DB::table('souvenirs')
                ->select('id', 'name', 'city', 'country', 'price', 'currency', 'status', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            return collect([]);
        }
    }
}
