<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ImageService;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * Get list of restaurants with filters
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Get authenticated user
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.'
                ], 401);
            }

            // Check if user has agency_name
            if (!$user->agency_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agency name not found. Please complete your profile first.'
                ], 400);
            }

            $query = Restaurant::query();

            // Filter by logged-in user's agency_name (agents can only see their own restaurants)
            $query->where('agency_name', $user->agency_name);

            // Filter by status (default to active only)
            $status = $request->get('status', 'active');
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            // Note: agency_name filter is removed from request filters since it's automatically applied

            // Filter by city
            if ($request->has('city') && $request->city) {
                $query->where('city', 'like', '%' . $request->city . '%');
            }

            // Filter by state
            if ($request->has('state') && $request->state) {
                $query->where('state', 'like', '%' . $request->state . '%');
            }

            // Filter by country
            if ($request->has('country') && $request->country) {
                $query->where('country', 'like', '%' . $request->country . '%');
            }

            // Filter by cuisine_type
            if ($request->has('cuisine_type') && $request->cuisine_type) {
                $query->where('cuisine_type', 'like', '%' . $request->cuisine_type . '%');
            }

            // Filter by price_range
            if ($request->has('price_range') && $request->price_range) {
                $query->where('price_range', $request->price_range);
            }

            // Filter by star_rating
            if ($request->has('star_rating') && $request->star_rating) {
                $query->where('star_rating', $request->star_rating);
            }

            // Filter by minimum star rating
            if ($request->has('min_rating') && $request->min_rating) {
                $query->where('star_rating', '>=', $request->min_rating);
            }

            // Filter by seating capacity (minimum)
            if ($request->has('min_capacity') && $request->min_capacity) {
                $query->where('seating_capacity', '>=', $request->min_capacity);
            }

            // Filter by seating capacity (maximum)
            if ($request->has('max_capacity') && $request->max_capacity) {
                $query->where('seating_capacity', '<=', $request->max_capacity);
            }

            // Filter by features
            if ($request->has('parking_available')) {
                $query->where('parking_available', filter_var($request->parking_available, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('wifi_available')) {
                $query->where('wifi_available', filter_var($request->wifi_available, FILTER_VALIDATE_BOOLEAN));
            }

            if ($request->has('accepts_reservations')) {
                $query->where('accepts_reservations', filter_var($request->accepts_reservations, FILTER_VALIDATE_BOOLEAN));
            }

            // Search by restaurant name, description, or address
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('restaurant_name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%");
                });
            }

            // Filter by amenities (if restaurant has any of the specified amenities)
            if ($request->has('amenities') && $request->amenities) {
                $amenities = is_array($request->amenities) ? $request->amenities : explode(',', $request->amenities);
                $amenities = array_map('trim', $amenities);
                
                $query->where(function($q) use ($amenities) {
                    foreach ($amenities as $amenity) {
                        // Try JSON contains first, fallback to LIKE for compatibility
                        try {
                            $q->orWhereJsonContains('amenities', $amenity);
                        } catch (\Exception $e) {
                            // Fallback: use JSON search with LIKE
                            $q->orWhere('amenities', 'like', '%"' . $amenity . '"%');
                        }
                    }
                });
            }

            // Filter by payment methods (if restaurant accepts any of the specified methods)
            if ($request->has('payment_methods') && $request->payment_methods) {
                $paymentMethods = is_array($request->payment_methods) ? $request->payment_methods : explode(',', $request->payment_methods);
                $paymentMethods = array_map('trim', $paymentMethods);
                
                $query->where(function($q) use ($paymentMethods) {
                    foreach ($paymentMethods as $method) {
                        // Try JSON contains first, fallback to LIKE for compatibility
                        try {
                            $q->orWhereJsonContains('payment_methods', $method);
                        } catch (\Exception $e) {
                            // Fallback: use JSON search with LIKE
                            $q->orWhere('payment_methods', 'like', '%"' . $method . '"%');
                        }
                    }
                });
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at'); // Default sort by created_at
            $sortOrder = $request->get('sort_order', 'desc'); // Default descending
            
            $allowedSortFields = ['created_at', 'restaurant_name', 'star_rating', 'seating_capacity', 'price_range'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy($sortBy, $sortOrder);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Pagination
            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $restaurants = $query->paginate($perPage);

            // Transform restaurant data to include image URLs
            $restaurants->getCollection()->transform(function ($restaurant) {
                return $this->transformRestaurant($restaurant);
            });

            // Always return 200 with empty array if no restaurants found
            return response()->json([
                'success' => true,
                'message' => $restaurants->total() > 0 
                    ? 'Restaurants retrieved successfully' 
                    : 'No restaurants found',
                'data' => $restaurants->items(), // Will be empty array if no results
                'pagination' => [
                    'current_page' => $restaurants->currentPage(),
                    'last_page' => $restaurants->lastPage(),
                    'per_page' => $restaurants->perPage(),
                    'total' => $restaurants->total(),
                    'from' => $restaurants->firstItem(),
                    'to' => $restaurants->lastItem(),
                ],
                'filters_applied' => $this->getAppliedFilters($request, $user),
            ], 200);

        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Authentication token is required.',
                'error' => 'Authentication required'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please login again.',
                'error' => 'Token expired'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
                'error' => 'Token invalid'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please login again.',
                'error' => 'Token error'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve restaurants',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get single restaurant by ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Get authenticated user - this will be null if token is missing or invalid
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Authentication token is required.',
                    'error' => 'Token missing or invalid'
                ], 401);
            }

            // Check if user has agency_name
            if (!$user->agency_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agency name not found. Please complete your profile first.',
                    'error' => 'Profile incomplete'
                ], 400);
            }

            // Validate restaurant ID
            if (!is_numeric($id) || $id <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid restaurant ID.',
                    'error' => 'Invalid parameter'
                ], 400);
            }

            $restaurant = Restaurant::where('id', $id)
                ->where('agency_name', $user->agency_name)
                ->first();

            if (!$restaurant) {
                // Return 200 with null data instead of 404
                return response()->json([
                    'success' => true,
                    'message' => 'No restaurant found',
                    'data' => null
                ], 200);
            }

            // Only return active restaurants unless explicitly requested
            if ($restaurant->status !== 'active') {
                // Return 200 with null data instead of 404
                return response()->json([
                    'success' => true,
                    'message' => 'Restaurant is not available',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Restaurant retrieved successfully',
                'data' => $this->transformRestaurant($restaurant)
            ], 200);

        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Authentication token is required.',
                'error' => 'Authentication required'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please login again.',
                'error' => 'Token expired'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
                'error' => 'Token invalid'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please login again.',
                'error' => 'Token error'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve restaurant',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Transform restaurant data for API response
     * 
     * @param Restaurant $restaurant
     * @return array
     */
    private function transformRestaurant($restaurant)
    {
        // Transform images to full URLs
        $images = [];
        if ($restaurant->images && is_array($restaurant->images)) {
            foreach ($restaurant->images as $imagePath) {
                $images[] = ImageService::getUrl($imagePath);
            }
        }

        return [
            'id' => $restaurant->id,
            'restaurant_name' => $restaurant->restaurant_name,
            'agency_name' => $restaurant->agency_name,
            'description' => $restaurant->description,
            'address' => $restaurant->address,
            'city' => $restaurant->city,
            'state' => $restaurant->state,
            'country' => $restaurant->country,
            'pincode' => $restaurant->pincode,
            'phone' => $restaurant->phone,
            'email' => $restaurant->email,
            'alternate_phone' => $restaurant->alternate_phone,
            'website' => $restaurant->website,
            'images' => $images,
            'star_rating' => $restaurant->star_rating,
            'price_range' => $restaurant->price_range,
            'price_range_label' => $restaurant->price_range_label,
            'cuisine_type' => $restaurant->cuisine_type,
            'seating_capacity' => $restaurant->seating_capacity,
            'opening_hours' => $restaurant->opening_hours,
            'amenities' => $restaurant->amenities,
            'gst_number' => $restaurant->gst_number,
            'license_number' => $restaurant->license_number,
            'parking_available' => (bool) $restaurant->parking_available,
            'wifi_available' => (bool) $restaurant->wifi_available,
            'accepts_reservations' => (bool) $restaurant->accepts_reservations,
            'payment_methods' => $restaurant->payment_methods,
            'social_media_links' => $restaurant->social_media_links,
            'latitude' => $restaurant->latitude ? (float) $restaurant->latitude : null,
            'longitude' => $restaurant->longitude ? (float) $restaurant->longitude : null,
            'status' => $restaurant->status,
            'created_at' => $restaurant->created_at ? $restaurant->created_at->toISOString() : null,
            'updated_at' => $restaurant->updated_at ? $restaurant->updated_at->toISOString() : null,
        ];
    }

    /**
     * Get list of applied filters for response
     * 
     * @param Request $request
     * @param \App\Models\User $user
     * @return array
     */
    private function getAppliedFilters($request, $user)
    {
        $filters = [
            'agency_name' => $user->agency_name, // Always show the user's agency
        ];
        
        $filterFields = [
            'status', 'city', 'state', 'country', 
            'cuisine_type', 'price_range', 'star_rating', 'min_rating',
            'min_capacity', 'max_capacity', 'parking_available', 
            'wifi_available', 'accepts_reservations', 'search',
            'amenities', 'payment_methods', 'sort_by', 'sort_order', 'per_page'
        ];

        foreach ($filterFields as $field) {
            if ($request->has($field) && $request->$field !== null && $request->$field !== '') {
                $filters[$field] = $request->$field;
            }
        }

        return $filters;
    }

    /**
     * Get available filter options (for dropdowns/filters in frontend)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function filterOptions()
    {
        try {
            // Get authenticated user - this will be null if token is missing or invalid
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Authentication token is required.',
                    'error' => 'Token missing or invalid'
                ], 401);
            }

            // Check if user has agency_name
            if (!$user->agency_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agency name not found. Please complete your profile first.',
                    'error' => 'Profile incomplete'
                ], 400);
            }

            // Filter restaurants by logged-in user's agency_name
            $restaurantQuery = Restaurant::where('agency_name', $user->agency_name);

            $options = [
                'agency_name' => $user->agency_name, // Show the user's agency name
                'cities' => (clone $restaurantQuery)->distinct()
                    ->whereNotNull('city')
                    ->pluck('city')
                    ->sort()
                    ->values()
                    ->toArray(),
                'states' => (clone $restaurantQuery)->distinct()
                    ->whereNotNull('state')
                    ->pluck('state')
                    ->sort()
                    ->values()
                    ->toArray(),
                'countries' => (clone $restaurantQuery)->distinct()
                    ->whereNotNull('country')
                    ->pluck('country')
                    ->sort()
                    ->values()
                    ->toArray(),
                'cuisine_types' => (clone $restaurantQuery)->distinct()
                    ->whereNotNull('cuisine_type')
                    ->pluck('cuisine_type')
                    ->sort()
                    ->values()
                    ->toArray(),
                'price_ranges' => ['low', 'medium', 'high', 'premium'],
                'star_ratings' => [1, 2, 3, 4, 5],
                'statuses' => ['active', 'inactive', 'pending'],
            ];

            // Get all unique amenities from user's restaurants only
            $allAmenities = [];
            (clone $restaurantQuery)->whereNotNull('amenities')->get()->each(function ($restaurant) use (&$allAmenities) {
                if ($restaurant->amenities && is_array($restaurant->amenities)) {
                    $allAmenities = array_merge($allAmenities, $restaurant->amenities);
                }
            });
            $options['amenities'] = array_values(array_unique($allAmenities));

            // Get all unique payment methods from user's restaurants only
            $allPaymentMethods = [];
            (clone $restaurantQuery)->whereNotNull('payment_methods')->get()->each(function ($restaurant) use (&$allPaymentMethods) {
                if ($restaurant->payment_methods && is_array($restaurant->payment_methods)) {
                    $allPaymentMethods = array_merge($allPaymentMethods, $restaurant->payment_methods);
                }
            });
            $options['payment_methods'] = array_values(array_unique($allPaymentMethods));

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => $options
            ], 200);

        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Authentication token is required.',
                'error' => 'Authentication required'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token has expired. Please login again.',
                'error' => 'Token expired'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
                'error' => 'Token invalid'
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication error. Please login again.',
                'error' => 'Token error'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve filter options',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}

