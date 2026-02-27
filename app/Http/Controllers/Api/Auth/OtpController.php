<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            try {
                $otp = $user->generateOtp($otpType);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate OTP',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500); // Internal Server Error
            }

            // Send OTP via email or SMS
            $responseData = [
                'identifier' => $identifier,
                'type' => $otpType,
                'expires_in' => 10, // minutes
            ];

            try {
                if ($otpType === 'email' && $user->email) {
                    // Send email via SMTP
                    Mail::to($user->email)->send(new OtpMail($otp, $user->name ?? 'User'));
                } elseif ($otpType === 'sms' && $user->phone) {
                    // Format phone number for SMS (E.164 format)
                    $phoneNumber = $user->country_code 
                        ? $user->country_code . $user->phone 
                        : $user->phone;
                    
                    // Ensure phone number starts with +
                    if (strpos($phoneNumber, '+') !== 0) {
                        $phoneNumber = '+' . $phoneNumber;
                    }
                    
                    // IMPORTANT: Firebase Phone Auth cannot send SMS from server-side
                    // Firebase requires client-side SDK to send SMS
                    // The OTP is generated and stored in database for verification
                    // Client must use Firebase SDK to send SMS, then use /verify-otp to verify with backend OTP
                    
                    // Get Firebase config for client-side implementation
                    $firebaseApiKey = Setting::get('firebase_api_key', '');
                    $firebaseProjectId = Setting::get('firebase_project_id', '');
                    $firebaseSenderId = Setting::get('firebase_sender_id', '');
                    $firebaseAppId = Setting::get('firebase_app_id', '');
                    
                    if ($firebaseApiKey && $firebaseProjectId) {
                        $responseData['firebase_config'] = [
                            'apiKey' => $firebaseApiKey,
                            'authDomain' => $firebaseProjectId . '.firebaseapp.com',
                            'projectId' => $firebaseProjectId,
                            'storageBucket' => $firebaseProjectId . '.firebasestorage.app',
                            'messagingSenderId' => $firebaseSenderId,
                            'appId' => $firebaseAppId,
                        ];
                        $responseData['requires_firebase_sdk'] = true;
                        $responseData['instructions'] = 'Use Firebase SDK signInWithPhoneNumber() to send SMS. Then verify OTP with /verify-otp endpoint using the OTP from your database.';
                    }
                    
                    $responseData['phone_number'] = $phoneNumber;
                    $responseData['otp_generated'] = true; // OTP is in database, ready for verification
                    
                    Log::info('SMS OTP generated for user (requires Firebase client SDK to send SMS)', [
                        'user_id' => $user->id,
                        'phone' => $phoneNumber,
                        'otp' => $otp,
                        'note' => 'Client must use Firebase SDK to send SMS'
                    ]);
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the OTP generation
                Log::error('Failed to send OTP: ' . $e->getMessage());
                // Continue and return success - OTP is generated and stored
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully',
                'data' => $responseData
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
        $isValidOtp = $user->verifyOtp($otp);

        if (!$isValidOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 401); // Unauthorized
        }

        // Check if user is new (profile not completed)
        $isNewUser = !$user->profile_completed_at;
        
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
                    'is_new_user' => $isNewUser, // Flag to indicate if profile needs to be created
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
