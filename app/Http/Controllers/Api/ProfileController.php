<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Validate GST number format (Indian GST format)
     * GST number format: 15 characters, alphanumeric
     * Format: 2 digits (state code) + 10 characters (PAN: 5 letters + 4 digits + 1 letter) + 1 alphanumeric (entity number) + Z + 1 alphanumeric (check digit)
     * Example: 27AABCU9603R1ZX
     * Pattern breakdown: 27 (state) + AABCU9603R (PAN) + 1 (entity) + Z + X (check)
     */
    private function validateGstNumber($gstNumber)
    {
        // Remove spaces and convert to uppercase
        $gstNumber = strtoupper(trim($gstNumber));
        
        // Check length (should be 15 characters)
        if (strlen($gstNumber) !== 15) {
            return false;
        }
        
        // Check format: 2 digits (state) + 5 letters + 4 digits + 1 letter (PAN) + 1 alphanumeric (entity) + Z + 1 alphanumeric (check)
        // Pattern: ^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[0-9A-Z]{1}Z[0-9A-Z]{1}$
        $pattern = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[0-9A-Z]{1}Z[0-9A-Z]{1}$/';
        
        if (!preg_match($pattern, $gstNumber)) {
            return false;
        }
        
        // Additional validation: State code should be between 01-38 (Indian states/UTs)
        $stateCode = (int) substr($gstNumber, 0, 2);
        if ($stateCode < 1 || $stateCode > 38) {
            return false;
        }
        
        // Check that position 13 is 'Z'
        if (substr($gstNumber, 13, 1) !== 'Z') {
            return false;
        }
        
        return true;
    }

    /**
     * Create agent profile (POST)
     * This should be called after a new user is created via OTP verification
     */
    public function createProfile(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Check if profile already exists
            if ($user->profile_completed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile already exists. Use update profile API instead.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'agency_name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
                'mobile' => 'required|string|max:20',
                'alternate_phone' => 'nullable|string|max:20',
                'gst_number' => 'required|string|max:15|unique:users,gst_number,' . $user->id,
                'address' => 'required|string',
                'country' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'city' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate GST number format
            $gstNumber = strtoupper(trim($request->gst_number));
            if (!$this->validateGstNumber($gstNumber)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid GST number format. Please provide a valid 15-character GST number.',
                    'errors' => [
                        'gst_number' => ['GST number must be 15 characters in format: 2 digits (state code) + 10 alphanumeric (PAN) + 1 digit + Z + 1 digit']
                    ]
                ], 422);
            }

            // Handle image upload using ImageService
            $imagePath = null;
            if ($request->hasFile('image')) {
                try {
                    $imageData = ImageService::upload(
                        $request->file('image'),
                        'agents',
                        (string) $user->id,
                        2048 // 2MB max size
                    );
                    $imagePath = $imageData['path'];
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image upload failed: ' . $e->getMessage()
                    ], 422);
                }
            }

            // Update user profile
            $user->update([
                'name' => $request->name,
                'image' => $imagePath,
                'agency_name' => $request->agency_name,
                'email' => $request->email,
                'phone' => $request->mobile,
                'alternate_phone' => $request->alternate_phone,
                'gst_number' => $gstNumber,
                'address' => $request->address,
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city ?? null,
                'pincode' => $request->pincode ?? null,
                'status' => 'pending', // New profiles start as pending
                'profile_completed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile created successfully',
                'data' => [
                    'user' => $this->formatUserResponse($user)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update agent profile (PUT/PATCH)
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'agency_name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
                'mobile' => 'sometimes|required|string|max:20',
                'alternate_phone' => 'nullable|string|max:20',
                'gst_number' => 'sometimes|required|string|max:15|unique:users,gst_number,' . $user->id,
                'address' => 'sometimes|required|string',
                'country' => 'sometimes|required|string|max:100',
                'state' => 'sometimes|required|string|max:100',
                'city' => 'nullable|string|max:100',
                'pincode' => 'nullable|string|max:10',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate GST number if provided
            if ($request->has('gst_number')) {
                $gstNumber = strtoupper(trim($request->gst_number));
                if (!$this->validateGstNumber($gstNumber)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid GST number format. Please provide a valid 15-character GST number.',
                        'errors' => [
                            'gst_number' => ['GST number must be 15 characters in format: 2 digits (state code) + 10 alphanumeric (PAN) + 1 digit + Z + 1 digit']
                        ]
                    ], 422);
                }
            }

            // Handle image upload using ImageService
            $updateData = [];
            
            if ($request->hasFile('image')) {
                try {
                    $imageData = ImageService::update(
                        $request->file('image'),
                        $user->image, // Old image path to delete
                        'agents',
                        (string) $user->id,
                        2048 // 2MB max size
                    );
                    $updateData['image'] = $imageData['path'];
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Image upload failed: ' . $e->getMessage()
                    ], 422);
                }
            }

            // Build update data
            if ($request->has('name')) $updateData['name'] = $request->name;
            if ($request->has('agency_name')) $updateData['agency_name'] = $request->agency_name;
            if ($request->has('email')) $updateData['email'] = $request->email;
            if ($request->has('mobile')) $updateData['phone'] = $request->mobile;
            if ($request->has('alternate_phone')) $updateData['alternate_phone'] = $request->alternate_phone;
            if ($request->has('gst_number')) $updateData['gst_number'] = strtoupper(trim($request->gst_number));
            if ($request->has('address')) $updateData['address'] = $request->address;
            if ($request->has('country')) $updateData['country'] = $request->country;
            if ($request->has('state')) $updateData['state'] = $request->state;
            if ($request->has('city')) $updateData['city'] = $request->city;
            if ($request->has('pincode')) $updateData['pincode'] = $request->pincode;

            // Update user profile
            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => $this->formatUserResponse($user)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get agent profile (GET)
     */
    public function getProfile(Request $request)
    {
        try {
            $user = auth('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $this->formatUserResponse($user)
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Format user response with full profile data
     */
    private function formatUserResponse($user)
    {
        // Use ImageService to get accessible URL
        $imageUrl = ImageService::getUrl($user->image);
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'image' => $imageUrl,
            'agency_name' => $user->agency_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'alternate_phone' => $user->alternate_phone,
            'country_code' => $user->country_code,
            'country' => $user->country,
            'state' => $user->state,
            'city' => $user->city,
            'pincode' => $user->pincode,
            'address' => $user->address,
            'gst_number' => $user->gst_number,
            'status' => $user->status,
            'profile_completed_at' => $user->profile_completed_at?->toISOString(),
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ];
    }
}
