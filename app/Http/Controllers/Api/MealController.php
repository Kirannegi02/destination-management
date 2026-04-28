<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\MealTemplate;
use App\Models\Restaurant;
use App\Support\Currency;
use Illuminate\Http\Request;

class MealController extends Controller
{
    /**
     * List active meals for a restaurant.
     *
     * GET /api/meals?restaurant_id=1
     *
     * Query params:
     *   restaurant_id  (required)
     *   status         (optional) active | inactive | all   (default: active)
     */
    public function index(Request $request)
    {
        try {
            $restaurantId = $request->get('restaurant_id');
            if (!$restaurantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'restaurant_id is required.',
                    'error'   => 'Missing restaurant_id',
                ], 400);
            }

            $restaurant = Restaurant::find($restaurantId);
            if (!$restaurant) {
                return response()->json([
                    'success' => true,
                    'message' => 'No restaurant found',
                    'data'    => [],
                ], 200);
            }

            $status     = $request->get('status', 'active');
            $globalOnly = filter_var($request->get('global_only', false), FILTER_VALIDATE_BOOLEAN);

            $query = Meal::where('restaurant_id', $restaurantId);
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            if ($globalOnly) {
                $query->where('is_shared_template', true);
            }

            $meals = $query
                ->orderBy('display_order')
                ->orderBy('meal_type')
                ->get();

            return response()->json([
                'success' => true,
                'message' => $meals->count() ? 'Meals retrieved successfully' : 'No meals found',
                'data'    => $meals->map(fn (Meal $m) => self::transformMeal($m))->values(),
                'filters_applied' => [
                    'restaurant_id' => $restaurantId,
                    'status'        => $status,
                    'global_only'   => $globalOnly,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve meals',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Return the global meal templates (admin-managed shared menu).
     *
     * GET /api/global-meals
     *
     * These are the master meal definitions (description + supplements) that
     * exist independently of any restaurant. Prices are NOT included here
     * because each restaurant can have its own price for the same global meal.
     * Use GET /api/meals?restaurant_id={id} to get the meals with
     * restaurant-specific pricing.
     */
    public function globalMenu(Request $request)
    {
        try {
            $status = $request->get('status', 'active');

            $query = MealTemplate::orderBy('display_order')->orderBy('meal_type');
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $templates = $query->get();

            return response()->json([
                'success' => true,
                'message' => $templates->count() ? 'Global meals retrieved successfully' : 'No global meals found',
                'data'    => $templates->map(fn (MealTemplate $t) => $this->transformTemplate($t))->values(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve global meals',
                'error'   => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Return global meals for a specific restaurant — i.e. only the meals
     * that are synced from the global menu, with this restaurant's pricing.
     *
     * GET /api/meals?restaurant_id={id}&global_only=true
     * (handled inside index() via the global_only flag)
     */

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Shared meal transformer — consistent shape for every meal endpoint.
     */
    public static function transformMeal(Meal $meal): array
    {
        $sups = is_array($meal->supplements) ? $meal->supplements : [];
        $isGlobal = (bool) $meal->is_shared_template;

        return [
            'id'               => $meal->id,
            'meal_type'        => $meal->meal_type,
            'meal_type_label'  => $meal->meal_type_label,
            'is_global'        => $isGlobal,
            'menu_description' => $meal->menu_description,
            'price'            => $meal->price !== null ? (float) $meal->price : null,
            'price_formatted'  => $meal->price_eur_formatted,
            'currency'         => 'EUR',
            'supplements'      => [
                'starter'     => self::transformSupplement($sups['starter'] ?? null),
                'main_course' => self::transformSupplement($sups['main_course'] ?? null),
            ],
            'display_order'    => (int) $meal->display_order,
            'status'           => $meal->status,
        ];
    }

    /**
     * Normalise a single supplement block.
     * Returns null when the supplement is not available.
     */
    private static function transformSupplement(?array $sup): ?array
    {
        if (empty($sup)) {
            return null;
        }

        $available = (bool) ($sup['available'] ?? false);

        if (!$available) {
            return null;
        }

        $price = (isset($sup['price']) && $sup['price'] !== null && $sup['price'] !== '')
            ? (float) $sup['price']
            : null;

        return [
            'available'        => true,
            'price'            => $price,
            'price_formatted'  => $price !== null ? Currency::format($price) : null,
            'description'      => $sup['description'] ?? null,
        ];
    }

    /**
     * Transform a MealTemplate (master global record — no restaurant pricing).
     */
    private function transformTemplate(MealTemplate $template): array
    {
        $sups = is_array($template->supplements) ? $template->supplements : [];

        return [
            'id'               => $template->id,
            'meal_type'        => $template->meal_type,
            'meal_type_label'  => Meal::labelForMealTypeKey((string) $template->meal_type),
            'menu_description' => $template->menu_description,
            'supplements'      => [
                'starter'     => self::transformSupplement($sups['starter'] ?? null),
                'main_course' => self::transformSupplement($sups['main_course'] ?? null),
            ],
            'display_order'    => (int) $template->display_order,
            'status'           => $template->status,
            'note'             => 'Prices are restaurant-specific. Use GET /api/meals?restaurant_id={id} to get pricing.',
        ];
    }
}
