<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Restaurant;
use Illuminate\Http\Request;

class MealController extends Controller
{
    /**
     * List meals for a selected restaurant.
     *
     * Query params:
     * - restaurant_id (required): Restaurant ID to filter meals
     * - status (optional): active|inactive|all (default: active)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $restaurantId = $request->get('restaurant_id');
            if (!$restaurantId) {
                return response()->json([
                    'success' => false,
                    'message' => 'restaurant_id is required.',
                    'error' => 'Missing restaurant_id',
                ], 400);
            }

            $restaurant = Restaurant::find($restaurantId);
            if (!$restaurant) {
                return response()->json([
                    'success' => true,
                    'message' => 'No restaurant found',
                    'data' => [],
                ], 200);
            }

            $status = $request->get('status', 'active');

            $query = Meal::with('restaurant')
                ->where('restaurant_id', $restaurantId);

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $meals = $query
                ->orderBy('display_order')
                ->orderBy('meal_type')
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $meals->map(function (Meal $meal) {
                return [
                    'id' => $meal->id,
                    'restaurant_id' => $meal->restaurant_id,
                    'restaurant_name' => optional($meal->restaurant)->restaurant_name,
                    'meal_type' => $meal->meal_type,
                    'meal_type_label' => $meal->meal_type_label,
                    'menu_description' => $meal->menu_description,
                    'price_inr' => $meal->price_inr !== null ? (float) $meal->price_inr : null,
                    'price_inr_formatted' => $meal->price_inr_formatted,
                    'local_currency' => $meal->local_currency,
                    'local_price' => $meal->local_price !== null ? (float) $meal->local_price : null,
                    'local_price_formatted' => $meal->local_price_formatted,
                    'supplements' => $meal->supplements,
                    'status' => $meal->status,
                    'display_order' => $meal->display_order,
                    'created_at' => $meal->created_at ? $meal->created_at->toISOString() : null,
                    'updated_at' => $meal->updated_at ? $meal->updated_at->toISOString() : null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => $data->count() ? 'Meals retrieved successfully' : 'No meals found',
                'data' => $data,
                'filters_applied' => [
                    'restaurant_id' => $restaurantId,
                    'status' => $status,
                ],
            ], 200);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Authentication token is required.',
                'error' => 'Authentication required',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please login again.',
                'error' => 'Token expired',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
                'error' => 'Token invalid',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please login again.',
                'error' => 'Token error',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve meals',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}



