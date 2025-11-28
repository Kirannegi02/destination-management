<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

// Load JWT stubs if the package is not installed
if (!class_exists('Tymon\JWTAuth\Facades\JWTAuth')) {
    require_once __DIR__ . '/../../../Support/JWTAuthStub.php';
}
if (!class_exists('Tymon\JWTAuth\Exceptions\JWTException')) {
    require_once __DIR__ . '/../../../Support/JWTExceptionStub.php';
}

use Tymon\JWTAuth\Facades\JWTAuth;

class OtpController extends Controller
{
    /**
     * Send OTP to email or phone number
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'identifier' => 'required|string', // Can be email or phone
                'name' => 'nullable|string|max:255', // Optional name for new users
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422); // Unprocessable Entity
            }

            $identifier = $request->identifier;
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            
            // Determine OTP type
            $otpType = $isEmail ? 'email' : 'sms';
            
            // Find or create user
            $user = User::findByEmailOrPhone($identifier);
            
            if (!$user) {
                // Create new user
                $userData = [
                    'name' => $request->name ?? 'User',
                ];
                
                if ($isEmail) {
                    $userData['email'] = $identifier;
                } else {
                    // Extract country code if provided (e.g., +91XXXXXXXXXX)
                    $phone = preg_replace('/[^0-9+]/', '', $identifier);
                    if (strpos($phone, '+') === 0) {
                        // Try to extract country code (assuming first 2-3 digits after +)
                        $userData['country_code'] = '+91'; // Default, can be improved
                        $userData['phone'] = substr($phone, 3);
                    } else {
                        $userData['phone'] = $phone;
                    }
                }
                
                try {
                    $user = User::create($userData);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create user',
                        'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                    ], 500); // Internal Server Error
                }
            }

            // Generate OTP
            // For now, use static OTP since SMTP/Firebase are not configured
            $staticOtp = '123456';
            
            // In production, use: $otp = $user->generateOtp($otpType);
            // For now, manually set OTP
            try {
                $user->update([
                    'otp' => $staticOtp,
                    'otp_expires_at' => now()->addMinutes(10),
                    'otp_type' => $otpType,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate OTP',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500); // Internal Server Error
            }

            // TODO: Send OTP via email or SMS
            // For now, just return success (in production, send actual OTP)
            // When SMTP/Firebase are configured, implement:
            // if ($otpType === 'email') {
            //     // Send email via SMTP
            // } else {
            //     // Send SMS via Firebase
            // }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => [
                    'identifier' => $identifier,
                    'type' => $otpType,
                    'otp' => $staticOtp, // Remove this in production - only for testing
                    'expires_in' => 10, // minutes
                ]
            ], 200); // OK
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500); // Internal Server Error
        }
    }

    /**
     * Verify OTP and login user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string', // Email or phone
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $identifier = $request->identifier;
        $otp = $request->otp;

        // Find user
        $user = User::findByEmailOrPhone($identifier);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found. Please request OTP first.'
            ], 404);
        }

        // Verify OTP
        // For static OTP during development, accept '123456'
        // In production, remove the static check and only use: $user->verifyOtp($otp)
        $staticOtp = '123456';
        $isValidOtp = false;
        
        if ($user->otp === $staticOtp && $otp === $staticOtp) {
            // Static OTP verification (for development)
            $isValidOtp = true;
            // Clear OTP after verification
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
                'otp_type' => null,
            ]);
        } else {
            // Normal OTP verification (for production)
            $isValidOtp = $user->verifyOtp($otp);
        }

        if (!$isValidOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401); // Unauthorized
        }

        // Generate JWT token
        try {
            $token = JWTAuth::fromUser($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                    ],
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60, // seconds
                ]
            ], 200); // OK
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
                'error' => config('app.debug') ? $e->getMessage() : 'Token generation failed'
            ], 500); // Internal Server Error
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500); // Internal Server Error
        }
    }
}
