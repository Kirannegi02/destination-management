<?php

use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\GuideBookingController;
use App\Http\Controllers\Api\GuideController as ApiGuideController;
use App\Http\Controllers\Api\SightseeingController as ApiSightseeingController;
use App\Http\Controllers\Api\MealController;
use Illuminate\Support\Facades\Route;

// Load JWT stubs if the package is not installed
if (!class_exists('Tymon\JWTAuth\Facades\JWTAuth')) {
    require_once __DIR__ . '/../app/Support/JWTAuthStub.php';
}
if (!class_exists('Tymon\JWTAuth\Exceptions\JWTException')) {
    require_once __DIR__ . '/../app/Support/JWTExceptionStub.php';
}

use Tymon\JWTAuth\Facades\JWTAuth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API routes (no authentication required)
Route::prefix('auth')->group(function () {
    // Send OTP to email or phone
    Route::post('/send-otp', [OtpController::class, 'sendOtp']);
    
    // Verify OTP and login
    Route::post('/verify-otp', [OtpController::class, 'verifyOtp']);
});

// Public image serving route (no authentication required)
Route::get('/images/{path}', [\App\Http\Controllers\Api\ImageController::class, 'serve'])
    ->where('path', '.*')
    ->name('api.images.serve');

// Public transport/vehicle routes (no authentication required)
Route::prefix('vehicles')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\VehicleController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\VehicleController::class, 'show'])->whereNumber('id');
});
Route::post('/transports/quote', [\App\Http\Controllers\Api\TransportQuoteController::class, 'quote']);
Route::post('/transports/quote-request', [\App\Http\Controllers\Api\TransportQuoteController::class, 'store']);
Route::post('/transport-bookings', [\App\Http\Controllers\Api\TransportQuoteController::class, 'store']);
Route::get('/transport-zones', [\App\Http\Controllers\Api\TransportZoneController::class, 'index']);
Route::get('/transport-zones/{id}', [\App\Http\Controllers\Api\TransportZoneController::class, 'show'])->whereNumber('id');

// Public souvenir list (no authentication required) – filter by country
Route::prefix('souvenirs')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\SouvenirController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\SouvenirController::class, 'show'])->whereNumber('id');
});

// Public restaurant routes (no authentication required)
Route::prefix('restaurants')->group(function () {
    // Get list of restaurants with filters (pass include_meals=true to embed meals)
    Route::get('/', [\App\Http\Controllers\Api\RestaurantController::class, 'index']);

    // Get single restaurant by ID — always includes active meals
    Route::get('/{id}', [\App\Http\Controllers\Api\RestaurantController::class, 'show'])
        ->whereNumber('id');
});

// Public meals endpoint — list active meals for a given restaurant
// Query params: restaurant_id (required), status (optional: active|all), global_only (optional: true)
Route::get('/meals', [MealController::class, 'index']);

// Global meal templates — master menu managed by admin (no restaurant-specific pricing)
Route::get('/global-meals', [MealController::class, 'globalMenu']);

// Protected API routes (require JWT authentication)
Route::middleware('auth:api')->group(function () {
    // Restaurant API routes (agents can only see their own restaurants)
    Route::prefix('restaurants')->group(function () {
        // Get filter options
        Route::get('/filter-options', [\App\Http\Controllers\Api\RestaurantController::class, 'filterOptions']);
    });

    // Guides and guide booking APIs
    Route::prefix('guides')->group(function () {
        Route::get('/', [ApiGuideController::class, 'index']);
        Route::get('/{id}', [ApiGuideController::class, 'show']);
    });

    // Sightseeing APIs
    Route::prefix('sightseeings')->group(function () {
        Route::get('/', [ApiSightseeingController::class, 'index']);
        Route::get('/{id}/price-availability', [ApiSightseeingController::class, 'priceAvailability'])->whereNumber('id');
        Route::get('/{id}', [ApiSightseeingController::class, 'show'])->whereNumber('id');
    });

    // Sightseeing booking APIs
    Route::prefix('sightseeing-bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SightseeingBookingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\SightseeingBookingController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\SightseeingBookingController::class, 'show'])->whereNumber('id');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\SightseeingBookingController::class, 'cancel'])->whereNumber('id');
    });
    
    // (meals/index is public — see above)
    Route::prefix('guide-bookings')->group(function () {
        Route::get('/', [GuideBookingController::class, 'index']);
        Route::post('/', [GuideBookingController::class, 'store']);
        Route::post('/{id}/cancel', [GuideBookingController::class, 'cancel']);
    });

    Route::prefix('transport-bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\TransportQuoteController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\TransportQuoteController::class, 'show'])->whereNumber('id');
    });

    // Unified dashboard bookings API (all modules for current user/agent)
    Route::get('/my-bookings', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    
    // Bookings (no payment)
    Route::prefix('bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\BookingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\BookingController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\BookingController::class, 'show'])->whereNumber('id');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\BookingController::class, 'cancel'])->whereNumber('id');
    });

    // Souvenir orders & addresses
    Route::prefix('addresses')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\UserAddressController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\UserAddressController::class, 'store']);
    });
    Route::prefix('souvenir-orders')->group(function () {
        Route::post('/preview', [\App\Http\Controllers\Api\SouvenirOrderController::class, 'preview']);
        Route::get('/', [\App\Http\Controllers\Api\SouvenirOrderController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\SouvenirOrderController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\SouvenirOrderController::class, 'show'])->whereNumber('id');
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\SouvenirOrderController::class, 'cancel'])->whereNumber('id');
    });
    // Souvenir restock requests (low stock / below minimum order quantity)
    Route::post('/souvenirs/{id}/restock-request', [\App\Http\Controllers\Api\SouvenirRestockController::class, 'store'])->whereNumber('id');
    // User profile routes
    Route::prefix('profile')->group(function () {
        // Get profile
        Route::get('/', [\App\Http\Controllers\Api\ProfileController::class, 'getProfile']);
        
        // Create profile (for new users)
        Route::post('/', [\App\Http\Controllers\Api\ProfileController::class, 'createProfile']);
        
        // Update profile
        Route::put('/', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
        Route::patch('/', [\App\Http\Controllers\Api\ProfileController::class, 'updateProfile']);
    });
    
    // User info (simple endpoint)
    Route::get('/user', function () {
        try {
            /** @var \App\Models\User|null $user */
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404); // Not Found
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                    'country' => $user->country,
                    'profile_completed' => !is_null($user->profile_completed_at),
                ]
            ], 200); // OK
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500); // Internal Server Error
        }
    });
    
    // Logout
    Route::post('/logout', function () {
        try {
            // Invalidate the token (add it to blacklist)
            JWTAuth::invalidate(JWTAuth::getToken());
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ], 200); // OK
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
                'error' => config('app.debug') ? $e->getMessage() : 'Logout failed'
            ], 500); // Internal Server Error
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500); // Internal Server Error
        }
    });
});

