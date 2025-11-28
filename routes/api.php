<?php

use App\Http\Controllers\Api\Auth\OtpController;
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

// Protected API routes (require JWT authentication)
Route::middleware('auth:api')->group(function () {
    // User profile
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

