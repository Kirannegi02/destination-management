<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ImportsSpreadsheet;
use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Restaurant;
use App\Services\GlobalMealSyncService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MealController extends Controller
{
    use ImportsSpreadsheet;

    /**
     * Columns for meal bulk import/export (restaurant resolved by name + phone).
     */
    private array $mealBulkColumns = [
        'restaurant_name',
        'phone',
        'meal_type',
        'menu_description',
        'price',
        'supplement_starter',
        'supplement_main_course',
        'status',
        'display_order',
    ];

    /**
     * Display a listing of the meals.
     */
    public function index(Request $request)
    {
        $query = Meal::with('restaurant')->withoutSharedTemplate();

        if ($request->has('restaurant_id') && $request->restaurant_id) {
            $query->where('restaurant_id', $request->restaurant_id);
        }

        if ($request->has('meal_type') && $request->meal_type) {
            $query->where('meal_type', $request->meal_type);
        }

        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('menu_description', 'like', "%{$search}%")
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search) {
                        $restaurantQuery->where('restaurant_name', 'like', "%{$search}%");
                    });
            });
        }

        $meals = $query->orderBy('restaurant_id')->orderBy('display_order')->orderBy('created_at', 'desc')->paginate(15);

        $restaurants = Restaurant::orderBy('restaurant_name')->get();

        $mealTypes = Meal::getMealTypes();

        $allCount = Meal::withoutSharedTemplate()->count();
        $activeCount = Meal::withoutSharedTemplate()->where('status', 'active')->count();
        $inactiveCount = Meal::withoutSharedTemplate()->where('status', 'inactive')->count();

        return view('admin.meals.index', compact(
            'meals',
            'restaurants',
            'mealTypes',
            'status',
            'allCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    public function importForm()
    {
        return view('admin.meals.import', [
            'columns' => $this->mealBulkColumns,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xls', 'xlsx'], true)) {
            return back()->with('error', 'Unsupported file type. Please upload CSV, XLS, or XLSX.');
        }

        $rows = $this->parseUploadRows($request->file('file'));
        if (empty($rows)) {
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        foreach (['restaurant_name', 'phone', 'meal_type'] as $required) {
            if (! in_array($required, $headers, true)) {
                return back()->with('error', "Missing required column: {$required}");
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $sync = app(GlobalMealSyncService::class);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $assoc = $this->mapRowToAssoc($headers, $row);
            if ($this->rowIsEmpty($assoc)) {
                $skipped++;
                continue;
            }

            $name = trim((string) ($assoc['restaurant_name'] ?? ''));
            $phone = $assoc['phone'] ?? null;
            if ($name === '' || $phone === null || trim((string) $phone) === '') {
                $errors[] = "Row {$rowNumber}: restaurant_name and phone are required.";
                $skipped++;
                continue;
            }

            $restaurant = $this->findRestaurantByNameAndPhone($name, $phone);
            if (! $restaurant) {
                $errors[] = "Row {$rowNumber}: no restaurant matches name and phone.";
                $skipped++;
                continue;
            }

            $typeKey = $this->resolveImportedMealType($assoc['meal_type'] ?? null);
            if ($typeKey === null) {
                $errors[] = "Row {$rowNumber}: meal_type is required.";
                $skipped++;
                continue;
            }

            // Accept new column names (price, supplement_starter, supplement_main_course)
            // and fall back to old _eur / _inr suffixed names for backward compatibility.
            $priceEur = $this->nullableNumeric(
                $assoc['price'] ?? $assoc['price_eur'] ?? $assoc['price_inr'] ?? null
            ); // stored as `price` column in DB
            $status = strtolower(trim((string) ($assoc['status'] ?? 'active')));
            $status = in_array($status, ['active', 'inactive'], true) ? $status : 'active';
            $displayOrder = isset($assoc['display_order']) && is_numeric($assoc['display_order'])
                ? (int) $assoc['display_order']
                : 0;

            // Build supplements from flat columns (new names first, then _eur suffixed fallback, then JSON).
            $starterPrice    = $this->nullableNumeric($assoc['supplement_starter'] ?? $assoc['supplement_starter_eur'] ?? null);
            $mainCoursePrice = $this->nullableNumeric($assoc['supplement_main_course'] ?? $assoc['supplement_main_course_eur'] ?? null);
            if ($starterPrice !== null || $mainCoursePrice !== null) {
                $supplements = [
                    'starter'     => ['available' => $starterPrice !== null, 'price' => $starterPrice],
                    'main_course' => ['available' => $mainCoursePrice !== null, 'price' => $mainCoursePrice],
                ];
            } else {
                $supplements = $this->decodeSupplements($assoc['supplements'] ?? null);
            }

            $menuDescription = isset($assoc['menu_description']) ? trim((string) $assoc['menu_description']) : '';

            if (Meal::isSharedTemplateMealType($typeKey)) {
                $existed = Meal::where('restaurant_id', $restaurant->id)
                    ->where('meal_type', $typeKey)
                    ->exists();
                $payload = [];
                if ($priceEur !== null) {
                    $payload['price'] = $priceEur;
                }
                try {
                    $sync->applyTemplateToRestaurantMeal($restaurant, $typeKey, $payload);
                } catch (QueryException $e) {
                    $dbMessage = $e->getMessage();
                    Log::warning('[Meal import] Row skipped: database error while syncing global template', [
                        'row_number' => $rowNumber,
                        'restaurant_id' => $restaurant->id,
                        'meal_type' => $typeKey,
                        'error' => $dbMessage,
                    ]);

                    if (stripos($dbMessage, 'Data truncated for column \'meal_type\'') !== false) {
                        $errors[] = "Row {$rowNumber}: custom meal_type storage is blocked by DB schema (meals.meal_type is ENUM). Run migration 2026_04_18_120000_change_meals_meal_type_to_varchar and retry.";
                    } else {
                        $errors[] = "Row {$rowNumber}: database error while importing meal ({$typeKey}).";
                    }
                    $skipped++;
                    continue;
                }
                if ($existed) {
                    $updated++;
                } else {
                    $created++;
                }
                continue;
            }

            if ($menuDescription === '') {
                $errors[] = "Row {$rowNumber}: menu_description is required for this meal type.";
                $skipped++;
                continue;
            }

            $existingGlobal = Meal::where('restaurant_id', $restaurant->id)
                ->where('meal_type', $typeKey)
                ->where('is_shared_template', true)
                ->first();
            if ($existingGlobal) {
                $errors[] = "Row {$rowNumber}: {$typeKey} is managed as a global menu meal for this restaurant; remove menu_description or import as global (empty description).";
                $skipped++;
                continue;
            }

            $validator = Validator::make([
                'menu_description' => $menuDescription,
                'price' => $priceEur,
                'status' => $status,
                'display_order' => $displayOrder,
            ], [
                'menu_description' => 'required|string',
                'price' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,inactive',
                'display_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: ".$validator->errors()->first();
                $skipped++;
                continue;
            }

            try {
                $meal = Meal::updateOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'meal_type' => $typeKey,
                    ],
                    [
                        'menu_description' => $menuDescription,
                        'price' => $priceEur,
                        'supplements' => $supplements,
                        'status' => $status,
                        'display_order' => $displayOrder,
                        'is_shared_template' => false,
                    ]
                );
            } catch (QueryException $e) {
                $dbMessage = $e->getMessage();
                Log::warning('[Meal import] Row skipped: database error during updateOrCreate', [
                    'row_number' => $rowNumber,
                    'restaurant_id' => $restaurant->id,
                    'meal_type' => $typeKey,
                    'error' => $dbMessage,
                ]);

                if (stripos($dbMessage, 'Data truncated for column \'meal_type\'') !== false) {
                    $errors[] = "Row {$rowNumber}: meal_type '{$typeKey}' cannot be saved because DB column is still ENUM. Run migration 2026_04_18_120000_change_meals_meal_type_to_varchar.";
                } else {
                    $errors[] = "Row {$rowNumber}: database error while importing meal_type '{$typeKey}'.";
                }
                $skipped++;
                continue;
            }

            if ($meal->wasRecentlyCreated) {
                $created++;
            } elseif ($meal->wasChanged()) {
                $updated++;
            }
        }

        $message = "Created: {$created}, updated: {$updated}, skipped: {$skipped}.";
        $flash = ($created > 0 || $updated > 0)
            ? ['success' => 'Meal import completed. '.$message]
            : ['error' => 'No meals imported. '.$message];

        return back()->with($flash)->with('import_errors', $errors);
    }

    public function export(Request $request)
    {
        $format = $this->normalizeImportFormat($request->get('format'));

        $query = Meal::with('restaurant')
            ->withoutSharedTemplate()
            ->orderBy('restaurant_id')
            ->orderBy('display_order')
            ->orderBy('meal_type');

        if ($request->filled('restaurant_id')) {
            $query->where('restaurant_id', $request->restaurant_id);
        }
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $meals = $query->get();
        $rows = $this->buildMealExportRows($meals);

        $filename = 'meals-'.now()->format('Ymd-His').($format === 'csv' ? '.csv' : '.xls');

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

    public function exportPage()
    {
        $restaurants = Restaurant::orderBy('restaurant_name')->get();

        return view('admin.meals.export', compact('restaurants'));
    }

    public function sample(Request $request)
    {
        $format = $this->normalizeImportFormat($request->get('format'));
        $rows = $this->mealSampleRows();
        $filename = 'meal-import-sample'.($format === 'csv' ? '.csv' : '.xls');
        $headers = [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                foreach ($rows as $row) {
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $filename, array_merge($headers, ['Content-Type' => 'text/csv']));
        }

        return response()->streamDownload(function () use ($rows) {
            echo $this->generateHtmlExcel($rows);
        }, $filename, array_merge($headers, ['Content-Type' => 'application/vnd.ms-excel']));
    }

    /**
     * Show the form for creating a new meal.
     */
    public function create()
    {
        $restaurants = Restaurant::orderBy('restaurant_name')->get();
        $mealTypeOptions = Meal::mealTypePickerOptions();

        return view('admin.meals.create', compact('restaurants', 'mealTypeOptions'));
    }

    /**
     * Store a newly created meal in storage.
     */
    public function store(Request $request)
    {
        $normalizedType = Meal::normalizeMealTypeInput((string) $request->input('meal_type', ''));
        if ($normalizedType === '') {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['meal_type' => 'Please enter or select a meal type.']);
        }

        if (Meal::isSharedTemplateMealType($normalizedType)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Standard Lunch, Standard Dinner, and Cocktail (with/without liquor) types are synced from Global menu. Use a custom name for a different offering, or change prices on those rows via Global menu / import.');
        }

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'menu_description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'supplements.starter.price' => 'nullable|numeric|min:0',
            'supplements.main_course.price' => 'nullable|numeric|min:0',
            'supplements.starter.description' => 'nullable|string|max:10000',
            'supplements.main_course.description' => 'nullable|string|max:10000',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $validated['meal_type'] = $normalizedType;
        $validated['supplements'] = $this->normalizedSupplementsFromRequest($request);
        $validated['is_shared_template'] = false;

        $existingMeal = Meal::where('restaurant_id', $validated['restaurant_id'])
            ->where('meal_type', $validated['meal_type'])
            ->first();

        if ($existingMeal) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'This meal type already exists for the selected restaurant. Please edit the existing meal instead.');
        }

        Meal::create($validated);

        return redirect()
            ->route('admin.meals.index')
            ->with('success', 'Meal created successfully.');
    }

    /**
     * Display the specified meal.
     */
    public function show(string $id)
    {
        $meal = Meal::with('restaurant')->findOrFail($id);

        return view('admin.meals.show', compact('meal'));
    }

    /**
     * Show the form for editing the specified meal.
     */
    public function edit(string $id)
    {
        $meal = Meal::findOrFail($id);
        $restaurants = Restaurant::orderBy('restaurant_name')->get();
        $mealTypeOptions = Meal::mealTypePickerOptions();

        return view('admin.meals.edit', compact('meal', 'restaurants', 'mealTypeOptions'));
    }

    /**
     * Update the specified meal in storage.
     */
    public function update(Request $request, string $id)
    {
        $meal = Meal::findOrFail($id);

        if ($meal->is_shared_template) {
            $data = $request->validate([
                'price' => 'nullable|numeric|min:0',
            ]);
            $meal->update($data);

            return redirect()
                ->route('admin.meals.index')
                ->with('success', 'Prices updated. Description is managed under Global menu.');
        }

        $normalizedType = Meal::normalizeMealTypeInput((string) $request->input('meal_type', ''));
        if ($normalizedType === '') {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['meal_type' => 'Please enter or select a meal type.']);
        }

        if (Meal::isSharedTemplateMealType($normalizedType)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Standard Lunch, Dinner, and Cocktail (with/without liquor) types are managed under Global menu. Choose a custom name, or edit prices under Global menu.');
        }

        $validated = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
            'menu_description' => 'required|string',
            'price' => 'nullable|numeric|min:0',
            'supplements.starter.price' => 'nullable|numeric|min:0',
            'supplements.main_course.price' => 'nullable|numeric|min:0',
            'supplements.starter.description' => 'nullable|string|max:10000',
            'supplements.main_course.description' => 'nullable|string|max:10000',
            'status' => 'required|in:active,inactive',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $validated['meal_type'] = $normalizedType;
        $validated['supplements'] = $this->normalizedSupplementsFromRequest($request);

        $existingMeal = Meal::where('restaurant_id', $validated['restaurant_id'])
            ->where('meal_type', $validated['meal_type'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingMeal) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'This meal type already exists for the selected restaurant.');
        }

        $meal->update($validated);

        return redirect()
            ->route('admin.meals.index')
            ->with('success', 'Meal updated successfully.');
    }

    /**
     * Remove the specified meal from storage.
     */
    public function destroy(string $id)
    {
        $meal = Meal::findOrFail($id);

        if ($meal->is_shared_template) {
            return redirect()
                ->route('admin.meals.index')
                ->with('error', 'Cannot delete a global menu meal row. Clear prices via import if needed.');
        }

        $meal->delete();

        return redirect()
            ->route('admin.meals.index')
            ->with('success', 'Meal deleted successfully.');
    }

    private function findRestaurantByNameAndPhone(string $name, $phone): ?Restaurant
    {
        $target = $this->normalizeImportPhone($phone);
        $candidates = Restaurant::where('restaurant_name', $name)->get();

        return $candidates->first(function (Restaurant $r) use ($target) {
            return $this->normalizeImportPhone($r->phone) === $target;
        });
    }

    private function resolveImportedMealType($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $normalized = strtolower(preg_replace('/[\s\-]+/', '_', $value));
        $normalized = preg_replace('/_+/', '_', $normalized);

        $allowed = array_keys(Meal::getMealTypes());
        if (in_array($normalized, $allowed, true)) {
            return $normalized;
        }

        foreach (Meal::getMealTypes() as $key => $label) {
            if (strcasecmp($label, $value) === 0) {
                return $key;
            }
        }

        return $normalized;
    }

    private function nullableNumeric($value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @return array{starter: array{available: bool, price: ?float, description: ?string}, main_course: array{available: bool, price: ?float, description: ?string}}
     */
    private function normalizedSupplementsFromRequest(Request $request): array
    {
        $starterAvail = $request->boolean('supplements.starter.available');
        $mainAvail = $request->boolean('supplements.main_course.available');

        $starterDesc = $request->input('supplements.starter.description');
        $mainDesc = $request->input('supplements.main_course.description');
        $starterDescNorm = ($starterDesc !== null && trim((string) $starterDesc) !== '') ? trim((string) $starterDesc) : null;
        $mainDescNorm = ($mainDesc !== null && trim((string) $mainDesc) !== '') ? trim((string) $mainDesc) : null;

        return [
            'starter' => [
                'available' => $starterAvail,
                'price' => $starterAvail ? $this->nullableNumeric($request->input('supplements.starter.price')) : null,
                'description' => $starterDescNorm,
            ],
            'main_course' => [
                'available' => $mainAvail,
                'price' => $mainAvail ? $this->nullableNumeric($request->input('supplements.main_course.price')) : null,
                'description' => $mainDescNorm,
            ],
        ];
    }

    private function decodeSupplements($raw): ?array
    {
        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }
        $decoded = json_decode((string) $raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
    }

    private function buildMealExportRows($meals): array
    {
        $header = array_merge(['is_shared_template'], $this->mealBulkColumns);
        $rows = [$header];

        foreach ($meals as $meal) {
            $r    = $meal->restaurant;
            $sups = is_array($meal->supplements) ? $meal->supplements : [];
            $rows[] = [
                $meal->is_shared_template ? '1' : '0',
                $r?->restaurant_name,
                $r?->phone,
                $meal->meal_type,
                $meal->menu_description,
                $meal->price !== null ? (float) $meal->price : '',
                $sups['starter']['price'] ?? '',
                $sups['main_course']['price'] ?? '',
                $meal->status,
                $meal->display_order,
            ];
        }

        return $rows;
    }

    private function mealSampleRows(): array
    {
        $header = $this->mealBulkColumns;

        return [
            $header,
            // Custom meal — with supplements (prices in EUR)
            [
                'Example Restaurant',
                '+91-9876543210',
                'cocktail_dinner_with_liquor',
                'Cocktail dinner with liquor — welcome drink, 3-course dinner, open bar',
                2500,  // price (EUR)
                150,   // supplement_starter (EUR) — leave blank if not offered
                200,   // supplement_main_course (EUR) — leave blank if not offered
                'active',
                10,
            ],
            // Custom meal — no supplements
            [
                'Example Restaurant',
                '+91-9876543210',
                'premium_lunch',
                'Premium lunch buffet with live stations and dessert counter',
                1200,  // price (EUR)
                '',    // supplement_starter — blank = not offered
                '',    // supplement_main_course — blank = not offered
                'active',
                20,
            ],
            // Global meal type — description left blank (auto-filled from Global Menu)
            [
                'Example Restaurant',
                '+91-9876543210',
                'standard_buffet_lunch',
                '',    // leave blank for global types — description comes from Global Menu
                899,   // price (EUR)
                '',
                '',
                'active',
                0,
            ],
        ];
    }
}
