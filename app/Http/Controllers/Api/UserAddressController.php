<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserAddressController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $addresses = UserAddress::where('user_id', $user->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = $addresses->map(function ($a) {
            return [
                'id' => $a->id,
                'label' => $a->label,
                'address_line1' => $a->address_line1,
                'address_line2' => $a->address_line2,
                'city' => $a->city,
                'state' => $a->state,
                'country' => $a->country,
                'pincode' => $a->pincode,
                'latitude' => $a->latitude,
                'longitude' => $a->longitude,
                'is_default' => (bool) $a->is_default,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Addresses retrieved successfully.',
            'data' => $data,
        ], 200);
    }

    public function store(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            Log::warning('UserAddressController@store unauthorized access attempt');
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        Log::info('UserAddressController@store received request', [
            'user_id' => $user->id,
            // Log full input so we can see actual field names being sent
            'payload' => $request->all(),
        ]);

        $validated = $request->validate([
            'label' => 'nullable|string|max:100',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:100',
            'pincode' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_default' => 'boolean',
        ]);

        $validated['user_id'] = $user->id;
        if (!empty($validated['is_default'])) {
            UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
        } else {
            $validated['is_default'] = UserAddress::where('user_id', $user->id)->exists() ? false : true;
        }

        $address = UserAddress::create($validated);

        Log::info('UserAddressController@store created address', [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'city' => $address->city,
            'country' => $address->country,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_default' => $address->is_default,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address added successfully.',
            'data' => [
                'id' => $address->id,
                'label' => $address->label,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'city' => $address->city,
                'state' => $address->state,
                'country' => $address->country,
                'pincode' => $address->pincode,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'is_default' => (bool) $address->is_default,
            ],
        ], 201);
    }
}
