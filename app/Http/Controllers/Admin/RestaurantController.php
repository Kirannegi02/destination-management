<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ImportsSpreadsheet;
use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Services\GlobalMealSyncService;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RestaurantController extends Controller
{
    use ImportsSpreadsheet;

    /**
     * Columns we allow for import/export.
     */
    private array $importableColumns = [
        'restaurant_name',
        'description',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'phone',
        'email',
        'alternate_phone',
        'website',
        'images',
        'video',
        'star_rating',
        'price',
        'cuisine_type',
        'seating_capacity',
        'status',
        'parking_available',
        'wifi_available',
        'accepts_reservations',
        'tax_number',
        'license_number',
    ];

    /**
     * Optional columns: meal_price_* plus starter/main supplement (EUR) per global meal type.
     */
    private function globalMealImportExportTailColumns(): array
    {
        $cols = [];
        foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $t) {
            $cols[] = 'meal_price_'.$t;
            $cols[] = GlobalMealSyncService::supplementStarterColumn($t);
            $cols[] = GlobalMealSyncService::supplementMainCourseColumn($t);
        }

        return $cols;
    }

    /**
     * Full header row for restaurant import/export sample and export when including meal prices.
     */
    private function restaurantSheetColumns(): array
    {
        return array_merge($this->importableColumns, $this->globalMealImportExportTailColumns());
    }

    /**
     * Global meal tail columns for import/export docs (same order as the spreadsheet tail).
     *
     * @return list<array{type: string, label: string, price_column: string, supplement_starter_column: string, supplement_main_course_column: string}>
     */
    private function globalMealSheetColumnGroups(): array
    {
        $labels = Meal::getMealTypes();
        $groups = [];
        foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $type) {
            $groups[] = [
                'type' => $type,
                'label' => $labels[$type] ?? $type,
                'price_column' => 'meal_price_'.$type,
                'supplement_starter_column' => GlobalMealSyncService::supplementStarterColumn($type),
                'supplement_main_course_column' => GlobalMealSyncService::supplementMainCourseColumn($type),
            ];
        }

        return $groups;
    }

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
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
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

        // Filter by price (accepts legacy price_range param for compatibility)
        if ($request->filled('price')) {
            $query->where('price', $request->price);
        } elseif ($request->filled('price_range')) {
            $query->where('price', $request->price_range);
        }

        $restaurants = $query->orderBy('created_at', 'desc')->paginate(15);

        // Get unique cities for filter
        $cities = Restaurant::distinct()->whereNotNull('city')->pluck('city')->sort()->values();

        // Get unique cuisine types for filter
        $cuisineTypes = Restaurant::distinct()->whereNotNull('cuisine_type')->pluck('cuisine_type')->sort()->values();

        // Get counts for status tabs
        $allCount = Restaurant::count();
        $activeCount = Restaurant::where('status', 'active')->count();
        $inactiveCount = Restaurant::where('status', 'inactive')->count();

        return view('admin.restaurants.index', compact(
            'restaurants', 
            'status', 
            'cities',
            'cuisineTypes',
            'allCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    /**
     * Show the form for creating a new restaurant.
     */
    public function create()
    {
        return view('admin.restaurants.create');
    }

    /**
     * Store a newly created restaurant in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
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
            'price' => 'nullable|numeric|min:0',
            'cuisine_type' => 'nullable|string|max:100',
            'seating_capacity' => 'nullable|integer|min:1',
            'opening_hours' => 'nullable|array',
            'amenities' => 'nullable|array',
            'tax_number' => 'nullable|string|max:15',
            'license_number' => 'nullable|string|max:100',
            'parking_available' => 'boolean',
            'wifi_available' => 'boolean',
            'accepts_reservations' => 'boolean',
            'payment_methods' => 'nullable|array',
            'social_media_links' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:active,inactive',
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

        // Handle video: file upload or URL
        $validated['video'] = $this->resolveRestaurantVideo($request, null);

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
        $restaurant = Restaurant::with(['meals' => function ($q) {
            $q->whereIn('meal_type', GlobalMealSyncService::sharedTemplateMealTypes());
        }])->findOrFail($id);

        $mealByType = $restaurant->meals->keyBy('meal_type');
        $globalMealFormData = [];
        foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $type) {
            $m = $mealByType->get($type);
            $sup = $m && is_array($m->supplements) ? $m->supplements : [];
            $st = is_array($sup['starter'] ?? null) ? $sup['starter'] : [];
            $mc = is_array($sup['main_course'] ?? null) ? $sup['main_course'] : [];
            $globalMealFormData[$type] = [
                'price' => $m && $m->price !== null ? $m->price : '',
                'supplement_starter' => isset($st['price']) && is_numeric($st['price']) ? $st['price'] : '',
                'supplement_main_course' => isset($mc['price']) && is_numeric($mc['price']) ? $mc['price'] : '',
            ];
        }

        return view('admin.restaurants.edit', compact('restaurant', 'globalMealFormData'));
    }

    /**
     * Update the specified restaurant in storage.
     */
    public function update(Request $request, string $id)
    {
        $restaurant = Restaurant::findOrFail($id);

        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:255',
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
            'video' => 'nullable|file|mimes:mp4,mov,webm|max:51200',
            'video_url' => 'nullable|string|max:1000',
            'star_rating' => 'nullable|integer|min:1|max:5',
            'price' => 'nullable|numeric|min:0',
            'cuisine_type' => 'nullable|string|max:100',
            'seating_capacity' => 'nullable|integer|min:1',
            'opening_hours' => 'nullable|array',
            'amenities' => 'nullable|array',
            'tax_number' => 'nullable|string|max:15',
            'license_number' => 'nullable|string|max:100',
            'parking_available' => 'boolean',
            'wifi_available' => 'boolean',
            'accepts_reservations' => 'boolean',
            'payment_methods' => 'nullable|array',
            'social_media_links' => 'nullable|array',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'required|in:active,inactive',
        ]);

        $globalMealRules = [];
        foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $t) {
            $globalMealRules["global_meals.{$t}.price"] = 'nullable|numeric|min:0';
            $globalMealRules["global_meals.{$t}.supplement_starter"] = 'nullable|numeric|min:0';
            $globalMealRules["global_meals.{$t}.supplement_main_course"] = 'nullable|numeric|min:0';
        }
        $request->validate($globalMealRules);

        // Handle video (new upload, URL, or remove)
        $validated['video'] = $this->resolveRestaurantVideo($request, $restaurant);

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

        $sync = app(GlobalMealSyncService::class);
        foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $mealType) {
            $priceRaw = $request->input("global_meals.{$mealType}.price");
            $stRaw = $request->input("global_meals.{$mealType}.supplement_starter");
            $mcRaw = $request->input("global_meals.{$mealType}.supplement_main_course");

            $overrides = [];
            if ($priceRaw !== null && trim((string) $priceRaw) !== '') {
                $overrides['price'] = (float) $priceRaw;
            }

            $supImport = [];
            if ($stRaw !== null && trim((string) $stRaw) !== '') {
                $supImport['starter'] = ['price' => (float) $stRaw];
            }
            if ($mcRaw !== null && trim((string) $mcRaw) !== '') {
                $supImport['main_course'] = ['price' => (float) $mcRaw];
            }
            if ($supImport !== []) {
                $overrides['supplements'] = $supImport;
            }

            if ($overrides !== []) {
                $sync->applyTemplateToRestaurantMeal($restaurant, $mealType, $overrides);
            }
        }

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

        // Delete video file if stored path (not external URL)
        if ($restaurant->video && !str_starts_with($restaurant->video, 'http')) {
            $path = str_replace(['public/', 'storage/'], '', $restaurant->video);
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $restaurant->delete();

        return redirect()
            ->route('admin.restaurants.index')
            ->with('success', 'Restaurant deleted successfully.');
    }

    /**
     * Resolve restaurant video from request: file upload, URL, or keep/remove existing.
     *
     * @param Request $request
     * @param Restaurant|null $existing Existing restaurant (for update) or null (create)
     * @return string|null
     */
    private function resolveRestaurantVideo(Request $request, ?Restaurant $existing): ?string
    {
        // Explicit remove (edit form)
        if ($request->has('video_remove') && $request->boolean('video_remove')) {
            $this->deleteRestaurantVideoFile($existing);
            return null;
        }

        // New file upload
        if ($request->hasFile('video') && $request->file('video')->isValid()) {
            $this->deleteRestaurantVideoFile($existing);
            $file = $request->file('video');
            $dir = 'restaurants/videos';
            if (!Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir);
            }
            $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $path = $file->storeAs($dir, $name, 'public');
            return $path;
        }

        // Video URL (paste link)
        $url = $request->input('video_url');
        if (is_string($url) && trim($url) !== '') {
            $this->deleteRestaurantVideoFile($existing);
            return trim($url);
        }

        // Update: keep existing
        if ($existing && $existing->video) {
            return $existing->video;
        }

        return null;
    }

    /**
     * Delete stored video file (not external URL) for a restaurant.
     */
    private function deleteRestaurantVideoFile(?Restaurant $restaurant): void
    {
        if (!$restaurant || !$restaurant->video) {
            return;
        }
        if (str_starts_with($restaurant->video, 'http')) {
            return;
        }
        $path = str_replace(['public/', 'storage/'], '', $restaurant->video);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Show bulk import form.
     */
    public function importForm()
    {
        return view('admin.restaurants.import', [
            'columns' => $this->restaurantSheetColumns(),
            'globalMealColumnGroups' => $this->globalMealSheetColumnGroups(),
        ]);
    }

    /**
     * Handle import upload (CSV or XLSX only). Images: URL text in the images column only (no embedded pictures).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $allowedExt = ['csv', 'xlsx'];
        if (! in_array($ext, $allowedExt, true)) {
            return back()->with('error', 'Unsupported file type. Please upload a .csv or .xlsx file only.');
        }

        $uploaded = $request->file('file');
        $parsed = $this->parseRestaurantImportRows($uploaded);

        if ($parsed === null) {
            Log::warning('[Restaurant import] Parse failed or no valid header row', [
                'original_name' => $uploaded->getClientOriginalName(),
                'extension' => $ext,
                'size' => $uploaded->getSize(),
                'php_spreadsheet' => $this->isPhpSpreadsheetAvailable(),
                'vendor_autoload' => is_readable(base_path('vendor/autoload.php')),
                'phpspreadsheet_package_present' => is_file(base_path('vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/IOFactory.php')),
                'zip_extension' => extension_loaded('zip'),
                'temp_path' => $uploaded->getRealPath() ?: $uploaded->getPathname(),
            ]);

            return back()->with(
                'error',
                'Could not read the file or find a header row with columns: restaurant_name, address, phone, status. '
                .'Use the sample download, row 1 (or the first row of your table) must include those exact names.'
            );
        }

        $headers = $parsed['headers'];
        $rows = $parsed['data_rows'];
        Log::info('[Restaurant import] Parsed sheet', [
            'file' => $uploaded->getClientOriginalName(),
            'header_row_number' => $parsed['header_row_number'],
            'headers' => $headers,
            'data_row_count' => count($rows),
        ]);

        $required = ['restaurant_name', 'address', 'phone', 'status'];
        foreach ($required as $header) {
            if (! in_array($header, $headers, true)) {
                $preview = implode(', ', array_slice(array_filter($headers, fn ($h) => $h !== ''), 0, 12));

                Log::warning('[Restaurant import] Missing required column', [
                    'missing' => $header,
                    'headers_first_20' => array_slice($headers, 0, 20),
                ]);

                return back()->with(
                    'error',
                    "Missing required column: {$header}. "
                    .($preview !== '' ? "Found (first columns): {$preview}. " : '')
                    .'Use exact headers: restaurant_name, address, phone, status (see sample download).'
                );
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $unchanged = 0;
        $errors = [];

        $headerRowNumber = $parsed['header_row_number'];

        foreach ($rows as $index => $row) {
            $rowNumber = $headerRowNumber + 1 + $index;
            $assoc = $this->mapRowToAssoc($headers, $row);

            if ($this->rowIsEmpty($assoc)) {
                Log::info('[Restaurant import] Row skipped: empty row', [
                    'row_number' => $rowNumber,
                ]);
                $errors[] = "Row {$rowNumber}: empty row.";
                $skipped++;
                continue;
            }

            $payload = $this->sanitizeImportPayload($assoc);
            Log::info('[Restaurant import] Row payload prepared', [
                'row_number' => $rowNumber,
                'restaurant_name' => $payload['restaurant_name'] ?? null,
                'phone_raw' => $payload['phone'] ?? null,
                'phone_normalized' => $this->normalizeImportPhone($payload['phone'] ?? null),
                'status' => $payload['status'] ?? null,
            ]);

            $validator = Validator::make($payload, [
                'restaurant_name' => 'required|string|max:255',
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
                'video' => 'nullable|string|max:1000',
                'star_rating' => 'nullable|integer|min:1|max:5',
                'price' => 'nullable|numeric|min:0',
                'cuisine_type' => 'nullable|string|max:100',
                'seating_capacity' => 'nullable|integer|min:1',
                'status' => 'required|in:active,inactive',
                'tax_number' => 'nullable|string|max:15',
                'license_number' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                Log::warning('[Restaurant import] Row skipped: validation failed', [
                    'row_number' => $rowNumber,
                    'errors' => $validator->errors()->toArray(),
                    'restaurant_name' => $payload['restaurant_name'] ?? null,
                    'phone' => $payload['phone'] ?? null,
                ]);
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            // Ensure status default if missing after normalization
            if (empty($payload['status'])) {
                $payload['status'] = 'active';
            }

            $match = array_filter([
                'restaurant_name' => $payload['restaurant_name'] ?? null,
                'phone' => $this->normalizeImportPhone($payload['phone'] ?? null),
            ]);
            Log::info('[Restaurant import] Match key computed', [
                'row_number' => $rowNumber,
                'match' => $match,
            ]);

            $restaurant = Restaurant::updateOrCreate(!empty($match) ? $match : ['restaurant_name' => $payload['restaurant_name']], $payload);

            if ($restaurant->wasRecentlyCreated) {
                Log::info('[Restaurant import] Row result: created', [
                    'row_number' => $rowNumber,
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->restaurant_name,
                    'phone' => $restaurant->phone,
                ]);
                $created++;
            } elseif ($restaurant->wasChanged()) {
                Log::info('[Restaurant import] Row result: updated', [
                    'row_number' => $rowNumber,
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->restaurant_name,
                    'phone' => $restaurant->phone,
                ]);
                $updated++;
            } else {
                Log::info('[Restaurant import] Row result: unchanged (already exists with same values)', [
                    'row_number' => $rowNumber,
                    'restaurant_id' => $restaurant->id,
                    'restaurant_name' => $restaurant->restaurant_name,
                    'phone' => $restaurant->phone,
                ]);
                $unchanged++;
            }

            $sync = app(GlobalMealSyncService::class);
            foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $mealType) {
                $priceCol = 'meal_price_'.$mealType;
                $stCol = GlobalMealSyncService::supplementStarterColumn($mealType);
                $mcCol = GlobalMealSyncService::supplementMainCourseColumn($mealType);

                $headerPresent = in_array($priceCol, $headers, true)
                    || in_array($stCol, $headers, true)
                    || in_array($mcCol, $headers, true);
                if (! $headerPresent) {
                    continue;
                }

                $overrides = [];

                if (in_array($priceCol, $headers, true)) {
                    $raw = $assoc[$priceCol] ?? null;
                    if ($raw !== null && trim((string) $raw) !== '') {
                        if (! is_numeric($raw)) {
                            Log::warning('[Restaurant import] Global meal override ignored: invalid meal price', [
                                'row_number' => $rowNumber,
                                'meal_type' => $mealType,
                                'column' => $priceCol,
                                'value' => $raw,
                            ]);
                            $errors[] = "Row {$rowNumber}: {$priceCol} must be numeric.";
                            continue;
                        }
                        $overrides['price'] = (float) $raw;
                    }
                }

                $supImport = [];
                $supplementRowInvalid = false;
                foreach (['starter' => $stCol, 'main_course' => $mcCol] as $supKey => $col) {
                    if (! in_array($col, $headers, true)) {
                        continue;
                    }
                    $raw = $assoc[$col] ?? null;
                    if ($raw === null || trim((string) $raw) === '') {
                        continue;
                    }
                    if (! is_numeric($raw)) {
                        Log::warning('[Restaurant import] Global meal override ignored: invalid supplement price', [
                            'row_number' => $rowNumber,
                            'meal_type' => $mealType,
                            'column' => $col,
                            'value' => $raw,
                        ]);
                        $errors[] = "Row {$rowNumber}: {$col} must be numeric.";
                        $supplementRowInvalid = true;
                        break;
                    }
                    $supImport[$supKey] = ['price' => (float) $raw];
                }
                if ($supplementRowInvalid) {
                    continue;
                }
                if (! empty($supImport)) {
                    $overrides['supplements'] = $supImport;
                }

                if (empty($overrides)) {
                    continue;
                }

                Log::info('[Restaurant import] Applying global meal overrides', [
                    'row_number' => $rowNumber,
                    'restaurant_id' => $restaurant->id,
                    'meal_type' => $mealType,
                    'overrides' => $overrides,
                ]);
                $sync->applyTemplateToRestaurantMeal($restaurant, $mealType, $overrides);
            }
        }

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";
        Log::info('[Restaurant import] Completed', [
            'file' => $uploaded->getClientOriginalName(),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'unchanged' => $unchanged,
            'errors' => $errors,
        ]);

        $flash = $created > 0 || $updated > 0
            ? ['success' => 'Restaurant data imported successfully.']
            : ['error' => 'No new data imported. ' . $message];

        return back()->with($flash)->with('import_errors', $errors);
    }

    /**
     * Export restaurants as CSV or Excel-compatible HTML.
     */
    public function export(Request $request)
    {
        $format = $this->normalizeImportFormat($request->get('format'));

        $query = Restaurant::query();
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('cuisine_type')) {
            $query->where('cuisine_type', $request->cuisine_type);
        }

        $restaurants = $query->with(['meals' => function ($q) {
            $q->whereIn('meal_type', GlobalMealSyncService::sharedTemplateMealTypes());
        }])->orderBy('created_at', 'desc')->get();
        $rows = $this->buildExportRows($restaurants);

        $filename = 'restaurants-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $this->writeCsvRowsForDownload($rows);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->streamDownload(function () use ($rows) {
            echo $this->generateHtmlExcel($rows);
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel']);
    }

    /**
     * Export page with instructions.
     */
    public function exportPage()
    {
        return view('admin.restaurants.export', [
            'globalMealColumnGroups' => $this->globalMealSheetColumnGroups(),
        ]);
    }

    /**
     * Download a sample import file (CSV or real .xlsx when PhpSpreadsheet is available).
     */
    public function sample(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'xlsx'));
        if (! in_array($format, ['csv', 'xlsx'], true)) {
            $format = 'xlsx';
        }
        $rows = $this->sampleRows();
        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $this->writeCsvRowsForDownload($rows);
            }, 'restaurant-import-sample.csv', array_merge($headers, ['Content-Type' => 'text/csv; charset=UTF-8']));
        }

        if (! $this->isPhpSpreadsheetAvailable()) {
            return response()->streamDownload(function () use ($rows) {
                $this->writeCsvRowsForDownload($rows);
            }, 'restaurant-import-sample.csv', array_merge($headers, ['Content-Type' => 'text/csv; charset=UTF-8']));
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'restaurant-import-sample.xlsx', array_merge($headers, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]));
    }

    /**
     * Parse CSV or XLSX. Prefer PhpSpreadsheet when autoloaded; otherwise fall back to native ZIP/XML reader.
     *
     * @return array{headers: list<string>, data_rows: list<array>, header_row_number: positive-int}|null
     */
    private function parseRestaurantImportRows(UploadedFile $uploaded): ?array
    {
        $this->ensureComposerAutoload();

        $path = $uploaded->getRealPath() ?: $uploaded->getPathname();
        $ext = strtolower($uploaded->getClientOriginalExtension());

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            return null;
        }

        if ($ext === 'csv') {
            $rows = $this->parseCsv($path);

            return $this->resolveRestaurantTableFromRows($rows);
        }

        if ($ext === 'xlsx') {
            if ($this->isPhpSpreadsheetAvailable()) {
                try {
                    $spreadsheet = IOFactory::load($path);
                    foreach ($spreadsheet->getAllSheets() as $sheet) {
                        $raw = $sheet->toArray(null, true, true, false);
                        if ($raw === []) {
                            continue;
                        }
                        $resolved = $this->resolveRestaurantTableFromRows($raw);
                        if ($resolved !== null) {
                            return $resolved;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('[Restaurant import] PhpSpreadsheet xlsx load failed, trying native reader', [
                        'message' => $e->getMessage(),
                        'exception' => $e::class,
                    ]);
                }
            }

            // Native reader: try every worksheet (Sheet1 is not always where the table lives).
            foreach ($this->listXlsxWorksheetEntries($path) as $entry) {
                $nativeRows = $this->parseXlsxWorksheet($path, $entry);
                if ($nativeRows === []) {
                    continue;
                }
                $resolved = $this->resolveRestaurantTableFromRows($nativeRows);
                if ($resolved !== null) {
                    return $resolved;
                }
            }

            return null;
        }

        return null;
    }

    /**
     * Some deployments omit loading vendor/autoload.php before controllers; ensure it is loaded once.
     */
    private function ensureComposerAutoload(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $autoload = base_path('vendor/autoload.php');
        if (is_readable($autoload)) {
            require_once $autoload;
        }
        $done = true;
    }

    private function isPhpSpreadsheetAvailable(): bool
    {
        $this->ensureComposerAutoload();

        // Do not require_once single files: that loads IOFactory but not Shared\File, Reader\*, etc.
        // (Composer autoload must resolve the whole package.)
        return class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class, true)
            && class_exists(\PhpOffice\PhpSpreadsheet\Shared\File::class, true);
    }

    /**
     * Find first row (within first 10) that contains required columns; build header + data rows.
     *
     * @param  list<array>  $rows
     * @return array{headers: list<string>, data_rows: list<array>, header_row_number: positive-int}|null
     */
    private function resolveRestaurantTableFromRows(array $rows): ?array
    {
        if ($rows === []) {
            return null;
        }

        $maxScan = min(10, count($rows));
        for ($i = 0; $i < $maxScan; $i++) {
            $rowCells = $rows[$i];
            if (! is_array($rowCells)) {
                continue;
            }
            $headers = $this->canonicalizeRestaurantImportHeaders(
                $this->normalizeHeaders(array_map(function ($cell) {
                    if ($cell === null) {
                        return '';
                    }

                    return trim((string) $cell);
                }, $rowCells))
            );
            if (! $this->headersHaveRestaurantImportRequired($headers)) {
                continue;
            }

            $dataRows = [];
            foreach (array_slice($rows, $i + 1) as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $dataRows[] = array_map(function ($cell) {
                    if ($cell === null) {
                        return '';
                    }

                    return trim((string) $cell);
                }, $row);
            }

            return [
                'headers' => $headers,
                'data_rows' => $dataRows,
                'header_row_number' => $i + 1,
            ];
        }

        return null;
    }

    /**
     * Map common spreadsheet header variants to import column names.
     *
     * @param  list<string>  $headers
     * @return list<string>
     */
    private function canonicalizeRestaurantImportHeaders(array $headers): array
    {
        $map = [
            'restaurantname' => 'restaurant_name',
            'restaurant' => 'restaurant_name',
            'resto_name' => 'restaurant_name',
            'business_name' => 'restaurant_name',
            'restaurant_title' => 'restaurant_name',
            'name_of_restaurant' => 'restaurant_name',
            'nameofrestaurant' => 'restaurant_name',
            'restaurant_names' => 'restaurant_name',
            'mobile' => 'phone',
            'mobile_phone' => 'phone',
            'phone_number' => 'phone',
            'telephone' => 'phone',
            'tel' => 'phone',
            'contact_number' => 'phone',
            'street' => 'address',
            'street_address' => 'address',
            'full_address' => 'address',
            'location' => 'address',
            'addr' => 'address',
            'restaurant_status' => 'status',
            'active_status' => 'status',
            // video aliases
            'video_url' => 'video',
            'video_link' => 'video',
            'videourl' => 'video',
            'videolink' => 'video',
            'restaurant_video' => 'video',
        ];

        return array_map(function (string $h) use ($map): string {
            if (isset($map[$h])) {
                return $map[$h];
            }
            $compact = str_replace(['_', '-', ' '], '', $h);
            if ($compact === 'restaurantname') {
                return 'restaurant_name';
            }

            return $h;
        }, $headers);
    }

    /**
     * @param  list<string>  $headers
     */
    private function headersHaveRestaurantImportRequired(array $headers): bool
    {
        foreach (['restaurant_name', 'address', 'phone', 'status'] as $col) {
            if (! in_array($col, $headers, true)) {
                return false;
            }
        }

        return true;
    }

    private function sanitizeImportPayload(array $row): array
    {
        $payload = [];
        foreach ($this->importableColumns as $column) {
            $value = $row[$column] ?? null;
            $payload[$column] = $this->normalizeValue($column, $value);
        }

        // Backward-compatible aliases for image columns in import sheets.
        if (empty($payload['images'])) {
            $imageAliasKeys = ['image', 'image_url', 'image_urls', 'photo', 'photos'];
            foreach ($imageAliasKeys as $alias) {
                if (isset($row[$alias])) {
                    $payload['images'] = $this->normalizeValue('images', $row[$alias]);
                    if (!empty($payload['images'])) {
                        break;
                    }
                }
            }
        }

        return $payload;
    }

    private function normalizeValue(string $column, $value)
    {
        if (in_array($column, ['parking_available', 'wifi_available', 'accepts_reservations'], true)) {
            if ($value === null) {
                return false;
            }
            $value = is_string($value) ? trim($value) : $value;
            if ($value === '') {
                return false;
            }
            return $this->normalizeBoolean($value);
        }

        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;
        if ($value === '') {
            return null;
        }

        if ($column === 'status') {
            $value = strtolower((string) $value);
            return in_array($value, ['active', 'inactive'], true) ? $value : 'active';
        }

        if ($column === 'star_rating') {
            return is_numeric($value) ? (int) $value : null;
        }

        if ($column === 'price') {
            return is_numeric($value) ? (float) $value : null;
        }

        if ($column === 'seating_capacity') {
            return is_numeric($value) ? (int) $value : null;
        }

        if ($column === 'images') {
            return $this->normalizeImportedImages($value);
        }

        if ($column === 'video') {
            // Accept a URL or a storage path; strip surrounding whitespace
            $v = is_string($value) ? trim($value) : null;
            return ($v !== null && $v !== '') ? $v : null;
        }

        return $value;
    }

    /**
     * Normalize imported images from Excel/CSV.
     * Accepts a single URL/path or multiple values split by comma, semicolon, pipe, or newline.
     */
    private function normalizeImportedImages($value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $items = $value;
        } else {
            $stringValue = trim((string) $value);
            if ($stringValue === '') {
                return null;
            }

            $items = preg_split('/[,\n;|]+/', $stringValue) ?: [];
        }

        $items = array_values(array_filter(array_map(function ($item) {
            return trim((string) $item);
        }, $items), function ($item) {
            return $item !== '';
        }));

        if (empty($items)) {
            return null;
        }

        return array_values(array_unique($items));
    }

    private function normalizeBoolean($value): bool
    {
        $trueValues = ['1', 'yes', 'true', 'y', 'on'];
        return in_array(strtolower((string) $value), $trueValues, true);
    }

    private function buildExportRows($restaurants): array
    {
        $rows = [];
        $rows[] = $this->restaurantSheetColumns();

        foreach ($restaurants as $restaurant) {
            $byType = $restaurant->meals->keyBy('meal_type');
            $priceCells = [];
            foreach (GlobalMealSyncService::sharedTemplateMealTypes() as $type) {
                $m = $byType->get($type);
                $priceCells[] = $m && $m->price !== null ? $m->price : '';
                $sup = $m && is_array($m->supplements) ? $m->supplements : [];
                $st = $sup['starter'] ?? [];
                $mc = $sup['main_course'] ?? [];
                $priceCells[] = isset($st['price']) && is_numeric($st['price']) ? $st['price'] : '';
                $priceCells[] = isset($mc['price']) && is_numeric($mc['price']) ? $mc['price'] : '';
            }
            $rows[] = array_merge([
                $restaurant->restaurant_name,
                $restaurant->description,
                $restaurant->address,
                $restaurant->city,
                $restaurant->state,
                $restaurant->country,
                $restaurant->pincode,
                $restaurant->phone,
                $restaurant->email,
                $restaurant->alternate_phone,
                $restaurant->website,
                $this->formatImagesForExport($restaurant->images),
                $restaurant->video_url ?? $restaurant->video,  // export full URL if stored path
                $restaurant->star_rating,
                $restaurant->price,
                $restaurant->cuisine_type,
                $restaurant->seating_capacity,
                $restaurant->status,
                $restaurant->parking_available ? 'Yes' : 'No',
                $restaurant->wifi_available ? 'Yes' : 'No',
                $restaurant->accepts_reservations ? 'Yes' : 'No',
                $restaurant->tax_number,
                $restaurant->license_number,
            ], $priceCells);
        }

        return $rows;
    }

    /**
     * Convert images field to export-friendly URL list.
     */
    private function formatImagesForExport($images): ?string
    {
        if ($images === null || $images === '') {
            return null;
        }

        $list = $images;
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $list = $decoded;
            } else {
                $list = preg_split('/[,\n;|]+/', $images) ?: [];
            }
        }

        if (!is_array($list)) {
            $list = [(string) $list];
        }

        $list = array_values(array_filter(array_map(function ($item) {
            $value = trim((string) $item);
            if ($value === '') {
                return null;
            }

            return ImageService::getUrl($value);
        }, $list)));

        if (empty($list)) {
            return null;
        }

        return implode(' | ', array_values(array_unique($list)));
    }

    /**
     * Sample spreadsheet rows: header + examples (full, minimal, prices-only, mixed supplements).
     * Meal tail order per type: meal_price_{type}, meal_supplement_starter_{type}, meal_supplement_main_course_{type}.
     */
    private function sampleRows(): array
    {
        $header = $this->restaurantSheetColumns();
        $globalTypes = GlobalMealSyncService::sharedTemplateMealTypes();
        $priceColumnSummary = implode(', ', array_map(static fn (string $t) => 'meal_price_'.$t, $globalTypes));

        $baseFull = [
            'The Spice Route',
            'Cozy family dining with regional specials',
            '123 Market Street',
            'Delhi',
            'Delhi',
            'India',
            '110001',
            '+91-9876543210',
            'hello@spiceroute.in',
            '+91-9876543211',
            'https://spiceroute.in',
            'https://example.com/restaurant/front.jpg | https://example.com/restaurant/inside.jpg',
            'https://example.com/storage/media-library/videos/restaurant-tour.mp4',
            4,
            1200,
            'North Indian',
            80,
            'active',
            'Yes',
            'Yes',
            'Yes',
            '29ABCDE1234F2Z5',
            'LIC-2024-123',
        ];

        $baseMinimal = [
            'Minimal Cafe',
            '',
            '1 Short Street',
            '',
            '',
            '',
            '',
            '+91-9111111111',
            '',
            '',
            '',
            '',
            '',   // video — leave blank if none
            '',
            '',
            '',
            '',
            'active',
            '',
            '',
            '',
            '',
            '',
        ];

        $basePricesOnly = [
            'Main Price Bistro',
            'Example: set EUR prices using tail columns '.$priceColumnSummary.'; supplement columns can stay empty. Menu text comes from Global menu.',
            '45 High Street',
            'Mumbai',
            'Maharashtra',
            'India',
            '400001',
            '+91-9222222222',
            'chef@mainprice.example',
            '',
            'https://mainprice.example',
            '',
            '',   // video
            3,
            800,
            'Multi-cuisine',
            50,
            'active',
            'No',
            'Yes',
            'No',
            '',
            '',
        ];

        $baseMixed = [
            'Mixed Supplements Kitchen',
            'Example: fill only some supplement columns; blanks are left unchanged for that field.',
            '88 Food Court Lane',
            'Bengaluru',
            'Karnataka',
            'India',
            '560001',
            '+91-9333333333',
            '',
            '',
            '',
            '',
            5,
            2000,
            'South Indian',
            120,
            'active',
            'Yes',
            'Yes',
            'Yes',
            '',
            '',
        ];

        $types = $globalTypes;
        $mealFull = [];
        $demoMealPrices = [899, 1099, 2499, 3199];
        foreach ($types as $i => $_) {
            $mealFull[] = $demoMealPrices[$i] ?? 1000;
            $mealFull[] = 50 + ($i * 10);
            $mealFull[] = 75 + ($i * 10);
        }

        $mealEmpty = array_fill(0, count($types) * 3, '');

        $mealPricesOnly = [];
        foreach ($types as $i => $_) {
            $mealPricesOnly[] = $demoMealPrices[$i] ?? 1000;
            $mealPricesOnly[] = '';
            $mealPricesOnly[] = '';
        }

        $mealMixed = [];
        foreach ($types as $type) {
            if ($type === 'standard_buffet_lunch') {
                $mealMixed[] = 950;
                $mealMixed[] = 55;
                $mealMixed[] = '';
            } elseif ($type === 'standard_buffet_dinner') {
                $mealMixed[] = '';
                $mealMixed[] = '';
                $mealMixed[] = '';
            } elseif ($type === 'cocktail_dinner_without_liquor') {
                $mealMixed[] = 1599;
                $mealMixed[] = '';
                $mealMixed[] = 99;
            } else {
                $mealMixed[] = '';
                $mealMixed[] = 120;
                $mealMixed[] = '';
            }
        }

        return [
            $header,
            array_merge($baseFull, $mealFull),
            array_merge($baseMinimal, $mealEmpty),
            array_merge($basePricesOnly, $mealPricesOnly),
            array_merge($baseMixed, $mealMixed),
        ];
    }
}

