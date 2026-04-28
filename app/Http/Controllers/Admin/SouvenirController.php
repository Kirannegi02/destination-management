<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ImportsSpreadsheet;
use App\Http\Controllers\Controller;
use App\Models\Souvenir;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SouvenirController extends Controller
{
    use ImportsSpreadsheet;

    private array $importableColumns = [
        'name',
        'description',
        'price',
        'currency',
        'min_order_quantity',
        'city',
        'latitude',
        'longitude',
        'stock',
        'country',
        'image_urls',
        'status',
    ];

    public function index(Request $request)
    {
        $query = Souvenir::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $status = $request->get('status', 'all');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }

        $souvenirs = $query->orderBy('created_at', 'desc')->paginate(15);
        $countries = Souvenir::distinct()->whereNotNull('country')->pluck('country')->sort()->values();
        $allCount = Souvenir::count();
        $activeCount = Souvenir::where('status', 'active')->count();
        $inactiveCount = Souvenir::where('status', 'inactive')->count();
        $pendingCount = Souvenir::where('status', 'pending')->count();

        return view('admin.souvenirs.index', compact(
            'souvenirs',
            'status',
            'countries',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    public function create()
    {
        return view('admin.souvenirs.create');
    }

    public function store(Request $request)
    {
        $minPurchase = (int) config('souvenir.min_purchase_quantity', 10);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => ['nullable', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'min_order_quantity' => 'nullable|integer|min:' . $minPurchase,
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'stock' => 'nullable|integer|min:0',
            'country' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $validated = $this->normalizeSouvenirCurrency($validated);
        $validated['min_order_quantity'] = $validated['min_order_quantity'] ?? $minPurchase;
        $validated['stock'] = $validated['stock'] ?? 0;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $imageData = ImageService::upload($image, 'souvenirs', null, 2048);
                    $imagePaths[] = $imageData['path'];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        $validated['images'] = !empty($imagePaths) ? $imagePaths : null;

        Souvenir::create($validated);

        return redirect()
            ->route('admin.souvenirs.index')
            ->with('success', 'Souvenir created successfully.');
    }

    public function edit(string $id)
    {
        $souvenir = Souvenir::findOrFail($id);
        return view('admin.souvenirs.edit', compact('souvenir'));
    }

    public function update(Request $request, string $id)
    {
        $souvenir = Souvenir::findOrFail($id);

        $minPurchase = (int) config('souvenir.min_purchase_quantity', 10);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => ['nullable', 'string', 'regex:/^[A-Za-z]{3}$/'],
            'min_order_quantity' => 'nullable|integer|min:' . $minPurchase,
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'stock' => 'nullable|integer|min:0',
            'country' => 'nullable|string|max:100',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive,pending',
        ]);

        $validated = $this->normalizeSouvenirCurrency($validated);
        $validated['min_order_quantity'] = $validated['min_order_quantity'] ?? $minPurchase;
        $validated['stock'] = $validated['stock'] ?? $souvenir->stock ?? 0;

        $existingImages = $souvenir->images ?? [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                try {
                    $imageData = ImageService::upload($image, 'souvenirs', null, 2048);
                    $existingImages[] = $imageData['path'];
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        if ($request->has('images_to_delete')) {
            foreach ($request->images_to_delete as $imagePath) {
                ImageService::delete($imagePath);
                $existingImages = array_values(array_filter($existingImages, fn($img) => $img !== $imagePath));
            }
        }
        $validated['images'] = !empty($existingImages) ? $existingImages : null;

        $souvenir->update($validated);

        return redirect()
            ->route('admin.souvenirs.index')
            ->with('success', 'Souvenir updated successfully.');
    }

    public function show(string $id)
    {
        $souvenir = Souvenir::findOrFail($id);
        return view('admin.souvenirs.show', compact('souvenir'));
    }

    public function destroy(string $id)
    {
        $souvenir = Souvenir::findOrFail($id);
        if ($souvenir->images) {
            foreach ($souvenir->images as $imagePath) {
                ImageService::delete($imagePath);
            }
        }
        $souvenir->delete();

        return redirect()
            ->route('admin.souvenirs.index')
            ->with('success', 'Souvenir deleted successfully.');
    }

    public function importForm()
    {
        return view('admin.souvenirs.import', ['columns' => $this->importableColumns]);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file']);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt', 'xls', 'xlsx'], true)) {
            return back()->with('error', 'Unsupported file type. Please upload CSV, XLS, or XLSX.');
        }

        $rows = $this->parseUploadRows($request->file('file'));
        if (empty($rows)) {
            return back()->with('error', 'Could not read the uploaded file. Please use the provided sample.');
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $required = ['name', 'price', 'status'];
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
            $rowNumber = $index + 2;
            $assoc = $this->mapRowToAssoc($headers, $row);
            if ($this->rowIsEmpty($assoc)) {
                $skipped++;
                continue;
            }

            $payload = $this->sanitizeImportPayload($assoc);
            $minPurchase = (int) config('souvenir.min_purchase_quantity', 10);
            $validator = Validator::make($payload, [
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'price'             => 'required|numeric|min:0',
                'currency'          => ['nullable', 'string', 'regex:/^[A-Za-z]{3}$/'],
                'min_order_quantity' => 'nullable|integer|min:' . $minPurchase,
                'city'              => 'nullable|string|max:100',
                'latitude'          => 'nullable|numeric|between:-90,90',
                'longitude'         => 'nullable|numeric|between:-180,180',
                'stock'             => 'nullable|integer|min:0',
                'country'           => 'nullable|string|max:100',
                'image_urls'        => 'nullable|string',
                'status'            => 'required|in:active,inactive,pending',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            if (empty($payload['status'])) {
                $payload['status'] = 'pending';
            }
            $payload = $this->normalizeSouvenirCurrency($payload);
            $payload['min_order_quantity'] = $payload['min_order_quantity'] ?? $minPurchase;

            // Parse comma-separated image URLs into the images JSON array
            $rawImageUrls = $payload['image_urls'] ?? null;
            unset($payload['image_urls']);
            if (!empty($rawImageUrls)) {
                $urls = array_values(array_filter(array_map('trim', explode(',', $rawImageUrls))));
                if (!empty($urls)) {
                    $payload['images'] = $urls;
                }
            }

            $match = ['name' => $payload['name']];
            if (!empty($payload['country'])) {
                $match['country'] = $payload['country'];
            }
            $souvenir = Souvenir::updateOrCreate($match, $payload);

            if ($souvenir->wasRecentlyCreated) {
                $created++;
            } elseif ($souvenir->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $flash = ($created > 0 || $updated > 0)
            ? ['success' => 'Souvenir data imported successfully.']
            : ['error' => 'No new data was imported. Please check the file and try again.'];

        return back()->with($flash)->with('import_errors', $errors);
    }

    public function export(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $query = Souvenir::query();
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }
        $souvenirs = $query->orderBy('created_at', 'desc')->get();
        $rows = $this->buildExportRows($souvenirs);
        $filename = 'souvenirs-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

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
        return view('admin.souvenirs.export');
    }

    public function sample(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $rows = [
            $this->importableColumns,
            [
                'Swiss Chocolate Box',                                // name
                'Premium milk chocolate assortment from Zurich.',    // description
                24.99,                                                // price
                'EUR',                                                // currency (EUR/CHF/USD/GBP/INR)
                10,                                                   // min_order_quantity
                'Zurich',                                             // city
                47.3769,                                              // latitude
                8.5417,                                               // longitude
                100,                                                  // stock
                'Switzerland',                                        // country
                'https://example.com/chocolate.jpg',                  // image_urls (comma-separated URLs for multiple images)
                'active',                                             // status (active/inactive/pending)
            ],
        ];
        $filename = 'souvenir-import-sample' . ($format === 'csv' ? '.csv' : '.xls');
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

    private function sanitizeImportPayload(array $row): array
    {
        // Accept common image column aliases
        if (empty($row['image_urls'])) {
            $row['image_urls'] = $row['images'] ?? $row['image_url'] ?? $row['photo_url'] ?? null;
        }

        $payload = [];
        foreach ($this->importableColumns as $column) {
            $value = $row[$column] ?? null;
            $payload[$column] = $this->normalizeValue($column, $value);
        }
        return $payload;
    }

    private function normalizeValue(string $column, $value)
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        $value = is_string($value) ? trim($value) : $value;

        if ($column === 'status') {
            $v = strtolower((string) $value);
            return in_array($v, ['active', 'inactive', 'pending'], true) ? $v : 'pending';
        }
        if ($column === 'min_order_quantity') {
            $minPurchase = (int) config('souvenir.min_purchase_quantity', 10);
            return is_numeric($value) ? max($minPurchase, (int) $value) : $minPurchase;
        }
        if (in_array($column, ['price', 'latitude', 'longitude'], true)) {
            return is_numeric($value) ? (float) $value : null;
        }
        if ($column === 'stock') {
            return is_numeric($value) ? max(0, (int) $value) : 0;
        }
        // image_urls: keep as raw comma-separated string; parsed in import loop
        if ($column === 'image_urls') {
            return (string) $value ?: null;
        }
        return $value;
    }

    /**
     * Normalize ISO 4217 currency code (3 letters) and default to EUR.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSouvenirCurrency(array $data): array
    {
        $currency = $data['currency'] ?? null;
        if ($currency === null || $currency === '') {
            $data['currency'] = 'EUR';
        } else {
            $data['currency'] = strtoupper(trim((string) $currency));
        }

        return $data;
    }

    private function normalizeFormat(?string $format): string
    {
        $format = strtolower($format ?? 'xls');
        return in_array($format, ['csv', 'xls', 'xlsx'], true) ? $format : 'xls';
    }

    private function buildExportRows($souvenirs): array
    {
        $rows = [$this->importableColumns];
        foreach ($souvenirs as $s) {
            $imageUrls = '';
            if (!empty($s->images)) {
                $imageUrls = implode(',', array_map(
                    fn($path) => ImageService::getUrl($path),
                    (array) $s->images
                ));
            }
            $rows[] = [
                $s->name,
                $s->description,
                $s->price,
                $s->currency ?? 'EUR',
                $s->min_order_quantity,
                $s->city,
                $s->latitude,
                $s->longitude,
                $s->stock,
                $s->country,
                $imageUrls,
                $s->status,
            ];
        }
        return $rows;
    }

}
