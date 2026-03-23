<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class RestaurantController extends Controller
{
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
        $restaurant = Restaurant::findOrFail($id);
        
        return view('admin.restaurants.edit', compact('restaurant'));
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
            'columns' => $this->importableColumns,
        ]);
    }

    /**
     * Handle import upload (CSV or Excel HTML/XLSX).
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $allowedExt = ['csv', 'txt', 'xls', 'xlsx'];
        if (!in_array($ext, $allowedExt, true)) {
            return back()->with('error', 'Unsupported file type. Please upload CSV, XLS, or XLSX.');
        }

        $rows = $this->parseUploadRows($request->file('file'));
        if (empty($rows)) {
            return back()->with('error', 'Could not read the uploaded file. Please use the provided sample.');
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $required = ['restaurant_name', 'address', 'phone', 'status'];
        foreach ($required as $header) {
            if (!in_array($header, $headers, true)) {
                return back()->with('error', "Missing required column: {$header}");
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $unchanged = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // account for header row
            $assoc = $this->mapRowToAssoc($headers, $row);

            if ($this->rowIsEmpty($assoc)) {
                $skipped++;
                continue;
            }

            $payload = $this->sanitizeImportPayload($assoc);

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
                'star_rating' => 'nullable|integer|min:1|max:5',
                'price' => 'nullable|numeric|min:0',
                'cuisine_type' => 'nullable|string|max:100',
                'seating_capacity' => 'nullable|integer|min:1',
                'status' => 'required|in:active,inactive',
                'tax_number' => 'nullable|string|max:15',
                'license_number' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
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
                'phone' => $this->normalizePhone($payload['phone'] ?? null),
            ]);

            $restaurant = Restaurant::updateOrCreate(!empty($match) ? $match : ['restaurant_name' => $payload['restaurant_name']], $payload);

            if ($restaurant->wasRecentlyCreated) {
                $created++;
            } elseif ($restaurant->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";

        $flash = $created > 0 || $updated > 0
            ? ['success' => 'Restaurant data imported successfully. ' . $message]
            : ['error' => 'No new data imported. ' . $message];

        return back()
            ->with($flash)
            ->with('import_errors', $errors);
    }

    /**
     * Export restaurants as CSV or Excel-compatible HTML.
     */
    public function export(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));

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

        $restaurants = $query->orderBy('created_at', 'desc')->get();
        $rows = $this->buildExportRows($restaurants);

        $filename = 'restaurants-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
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
        return view('admin.restaurants.export');
    }

    /**
     * Download a sample import file.
     */
    public function sample(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $rows = $this->sampleRows();
        $filename = 'restaurant-import-sample' . ($format === 'csv' ? '.csv' : '.xls');

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return response()->streamDownload(function () use ($rows) {
            echo $this->generateHtmlExcel($rows);
        }, $filename, ['Content-Type' => 'application/vnd.ms-excel']);
    }

    /**
     * Parse uploaded CSV/XLS/XLSX into row arrays.
     */
    private function parseUploadRows($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($file->getRealPath());
        }

        // Try XLSX (Zip) first, then fallback to HTML table
        $xlsxRows = $this->parseXlsx($file->getRealPath());
        if (!empty($xlsxRows)) {
            return $xlsxRows;
        }

        return $this->parseHtmlTable($file->getRealPath());
    }

    private function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                // Remove BOM if present
                if (!empty($data)) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                }
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();
        $rows = [];

        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        if (($shared = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($shared);
            libxml_clear_errors();
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string) $si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            return [];
        }

        libxml_use_internal_errors(true);
        $sheet = simplexml_load_string($sheetXml);
        libxml_clear_errors();
        $zip->close();

        if (!$sheet || !isset($sheet->sheetData->row)) {
            return [];
        }

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $value = (string) $c->v;
                if (isset($c['t']) && (string) $c['t'] === 's') {
                    $index = (int) $value;
                    $value = $sharedStrings[$index] ?? '';
                }
                $cells[] = trim($value);
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    private function parseHtmlTable(string $path): array
    {
        $content = file_get_contents($path);
        if (empty($content)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($content);
        libxml_clear_errors();

        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $row = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                $row[] = trim($td->textContent);
            }
            if (!empty($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            // Convert "Restaurant Name" or "restaurant-name" to "restaurant_name"
            $normalized = strtolower(trim($header));
            $normalized = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $normalized);
            $normalized = str_replace([' ', '-'], '_', $normalized);
            // Collapse multiple underscores
            $normalized = preg_replace('/_+/', '_', $normalized);
            return $normalized;
        }, $headers);
    }

    private function mapRowToAssoc(array $headers, array $row): array
    {
        $assoc = [];
        foreach ($headers as $index => $header) {
            $assoc[$header] = $row[$index] ?? null;
        }
        return $assoc;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (!is_null($value) && trim((string) $value) !== '') {
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

        return $payload;
    }

    private function normalizeValue(string $column, $value)
    {
        if ($value === null) {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;
        if ($value === '') {
            return null;
        }

        if (in_array($column, ['parking_available', 'wifi_available', 'accepts_reservations'], true)) {
            return $this->normalizeBoolean($value);
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

        return $value;
    }

    private function normalizePhone($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value);
        return $digits !== '' ? $digits : trim((string) $value);
    }

    private function normalizeBoolean($value): bool
    {
        $trueValues = ['1', 'yes', 'true', 'y', 'on'];
        return in_array(strtolower((string) $value), $trueValues, true);
    }

    private function normalizeFormat(?string $format): string
    {
        $format = strtolower($format ?? 'xls');
        return in_array($format, ['csv', 'xls', 'xlsx'], true) ? $format : 'xls';
    }

    private function buildExportRows($restaurants): array
    {
        $rows = [];
        // Keep headers identical to import template (snake_case)
        $rows[] = $this->importableColumns;

        foreach ($restaurants as $restaurant) {
            $rows[] = [
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
            ];
        }

        return $rows;
    }

    private function generateHtmlExcel(array $rows): string
    {
        $html = '<table border="1"><thead><tr>';
        foreach ($rows[0] as $heading) {
            $html .= '<th>' . htmlspecialchars((string) $heading) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach (array_slice($rows, 1) as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function sampleRows(): array
    {
        return [
            // Use exact importable column names for the sample header
            $this->importableColumns,
            [
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
            ],
        ];
    }
}

