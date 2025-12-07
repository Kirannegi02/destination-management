<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the restaurants.
     */
    public function index(Request $request)
    {
        $query = Restaurant::query();

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('restaurant_name', 'like', "%{$search}%")
                  ->orWhere('agency_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by agency name
        if ($request->has('agency_name') && $request->agency_name) {
            $query->where('agency_name', $request->agency_name);
        }

        // Filter by status
        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filter by city
        if ($request->has('city') && $request->city) {
            $query->where('city', $request->city);
        }

        // Filter by cuisine type
        if ($request->has('cuisine_type') && $request->cuisine_type) {
            $query->where('cuisine_type', $request->cuisine_type);
        }

        // Filter by price range
        if ($request->has('price_range') && $request->price_range) {
            $query->where('price_range', $request->price_range);
        }

        $restaurants = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get unique agency names for filter
        $agencyNames = Restaurant::distinct()->pluck('agency_name')->sort()->values();

        // Get unique cities for filter
        $cities = Restaurant::distinct()->whereNotNull('city')->pluck('city')->sort()->values();

        // Get unique cuisine types for filter
        $cuisineTypes = Restaurant::distinct()->whereNotNull('cuisine_type')->pluck('cuisine_type')->sort()->values();

        // Get counts for status tabs
        $allCount = Restaurant::count();
        $activeCount = Restaurant::where('status', 'active')->count();
        $inactiveCount = Restaurant::where('status', 'inactive')->count();
        $pendingCount = Restaurant::where('status', 'pending')->count();

        return view('admin.restaurants.index', compact(
            'restaurants', 
            'status', 
            'agencyNames', 
            'cities',
            'cuisineTypes',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    /**
     * Show the form for creating a new restaurant.
     */
    public function create()
    {
        // Get all unique agency names from users table for dropdown
        $agencyNames = User::whereNotNull('agency_name')
            ->distinct()
            ->pluck('agency_name')
            ->sort()
            ->values();

        return view('admin.restaurants.create', compact('agencyNames'));
    }

    /**
     * Store a newly created restaurant in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'agency_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'alternate_phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'price_range' => 'nullable|in:low,medium,high,premium',
            'cuisine_type' => 'nullable|string|max:100',
            'seating_capacity' => 'nullable|integer|min:1',
            'opening_hours' => 'nullable|array',
            'amenities' => 'nullable|array',
            'gst_number' => 'nullable|string|max:15',
            'license_number' => 'nullable|string|max:100',
            'parking_available' => 'boolean',
            'wifi_available' => 'boolean',
            'accepts_reservations' => 'boolean',
            'payment_methods' => 'nullable|array',
            'social_media_links' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:active,inactive,pending',
        ]);

        // Handle multiple image uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $imageData = ImageService::upload(
                        $image,
                        'restaurants',
                        null,
                        2048
                    );
                    $imagePaths[] = $imageData['path'];
                } catch (\Exception $e) {
                    // Continue with other images if one fails
                    continue;
                }
            }
        }
        $validated['images'] = !empty($imagePaths) ? $imagePaths : null;

        // Handle amenities - convert from comma-separated string or use JSON array
        if ($request->has('amenities_input') && $request->amenities_input) {
            $amenities = array_map('trim', explode(',', $request->amenities_input));
            $amenities = array_filter($amenities);
            $validated['amenities'] = !empty($amenities) ? $amenities : null;
        } elseif ($request->has('amenities') && is_string($request->amenities)) {
            $validated['amenities'] = json_decode($request->amenities, true);
        }

        // Handle payment methods - convert from comma-separated string or use JSON array
        if ($request->has('payment_methods_input') && $request->payment_methods_input) {
            $paymentMethods = array_map('trim', explode(',', $request->payment_methods_input));
            $paymentMethods = array_filter($paymentMethods);
            $validated['payment_methods'] = !empty($paymentMethods) ? $paymentMethods : null;
        } elseif ($request->has('payment_methods') && is_string($request->payment_methods)) {
            $validated['payment_methods'] = json_decode($request->payment_methods, true);
        }

        // Handle opening hours - parse JSON if provided as string
        if ($request->has('opening_hours_input') && $request->opening_hours_input) {
            try {
                $openingHours = json_decode($request->opening_hours_input, true);
                $validated['opening_hours'] = $openingHours ?: null;
            } catch (\Exception $e) {
                $validated['opening_hours'] = null;
            }
        } elseif ($request->has('opening_hours') && is_string($request->opening_hours)) {
            $validated['opening_hours'] = json_decode($request->opening_hours, true);
        }

        // Convert boolean checkboxes
        $validated['parking_available'] = $request->has('parking_available');
        $validated['wifi_available'] = $request->has('wifi_available');
        $validated['accepts_reservations'] = $request->has('accepts_reservations');

        Restaurant::create($validated);

        return redirect()
            ->route('admin.restaurants.index')
            ->with('success', 'Restaurant created successfully.');
    }

    /**
     * Show the form for editing the specified restaurant.
     */
    public function edit(string $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        
        // Get all unique agency names from users table for dropdown
        $agencyNames = User::whereNotNull('agency_name')
            ->distinct()
            ->pluck('agency_name')
            ->sort()
            ->values();

        return view('admin.restaurants.edit', compact('restaurant', 'agencyNames'));
    }

    /**
     * Update the specified restaurant in storage.
     */
    public function update(Request $request, string $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
            'agency_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'required|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'alternate_phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'price_range' => 'nullable|in:low,medium,high,premium',
            'cuisine_type' => 'nullable|string|max:100',
            'seating_capacity' => 'nullable|integer|min:1',
            'opening_hours' => 'nullable|array',
            'amenities' => 'nullable|array',
            'gst_number' => 'nullable|string|max:15',
            'license_number' => 'nullable|string|max:100',
            'parking_available' => 'boolean',
            'wifi_available' => 'boolean',
            'accepts_reservations' => 'boolean',
            'payment_methods' => 'nullable|array',
            'social_media_links' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:active,inactive,pending',
        ]);

        // Handle new image uploads
        $existingImages = $restaurant->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $imageData = ImageService::upload(
                        $image,
                        'restaurants',
                        null,
                        2048
                    );
                    $existingImages[] = $imageData['path'];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Handle image deletions (if images_to_delete is provided)
        if ($request->has('images_to_delete')) {
            $imagesToDelete = $request->images_to_delete;
            foreach ($imagesToDelete as $imagePath) {
                ImageService::delete($imagePath);
                $existingImages = array_filter($existingImages, function($img) use ($imagePath) {
                    return $img !== $imagePath;
                });
            }
            $existingImages = array_values($existingImages); // Re-index array
        }

        $validated['images'] = !empty($existingImages) ? $existingImages : null;

        // Handle amenities - convert from comma-separated string or use JSON array
        if ($request->has('amenities_input') && $request->amenities_input) {
            $amenities = array_map('trim', explode(',', $request->amenities_input));
            $amenities = array_filter($amenities);
            $validated['amenities'] = !empty($amenities) ? $amenities : null;
        } elseif ($request->has('amenities') && is_string($request->amenities)) {
            $validated['amenities'] = json_decode($request->amenities, true);
        }

        // Handle payment methods - convert from comma-separated string or use JSON array
        if ($request->has('payment_methods_input') && $request->payment_methods_input) {
            $paymentMethods = array_map('trim', explode(',', $request->payment_methods_input));
            $paymentMethods = array_filter($paymentMethods);
            $validated['payment_methods'] = !empty($paymentMethods) ? $paymentMethods : null;
        } elseif ($request->has('payment_methods') && is_string($request->payment_methods)) {
            $validated['payment_methods'] = json_decode($request->payment_methods, true);
        }

        // Handle opening hours - parse JSON if provided as string
        if ($request->has('opening_hours_input') && $request->opening_hours_input) {
            try {
                $openingHours = json_decode($request->opening_hours_input, true);
                $validated['opening_hours'] = $openingHours ?: null;
            } catch (\Exception $e) {
                $validated['opening_hours'] = null;
            }
        } elseif ($request->has('opening_hours') && is_string($request->opening_hours)) {
            $validated['opening_hours'] = json_decode($request->opening_hours, true);
        }

        // Convert boolean checkboxes
        $validated['parking_available'] = $request->has('parking_available');
        $validated['wifi_available'] = $request->has('wifi_available');
        $validated['accepts_reservations'] = $request->has('accepts_reservations');

        $restaurant->update($validated);

        return redirect()
            ->route('admin.restaurants.index')
            ->with('success', 'Restaurant updated successfully.');
    }

    /**
     * Display the specified restaurant.
     */
    public function show(string $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        return view('admin.restaurants.show', compact('restaurant'));
    }

    /**
     * Remove the specified restaurant from storage.
     */
    public function destroy(string $id)
    {
        $restaurant = Restaurant::findOrFail($id);
        
        // Delete all images
        if ($restaurant->images) {
            foreach ($restaurant->images as $imagePath) {
                ImageService::delete($imagePath);
            }
        }
        
        $restaurant->delete();

        return redirect()
            ->route('admin.restaurants.index')
            ->with('success', 'Restaurant deleted successfully.');
    }
}

