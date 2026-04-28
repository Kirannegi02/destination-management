<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ImportsSpreadsheet;
use App\Http\Controllers\Controller;
use App\Models\Sightseeing;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SightseeingController extends Controller
{
    use ImportsSpreadsheet;
    /**
     * Columns used for import/export.
     */
    private array $importableColumns = [
        'title',
        'description',
        'country',
        'city',
        'start_location',
        'end_location',
        'standard_price',
        'currency',
        'default_pax',
        'standard_price_note',
        'availability_notes',
        'booking_conditions',
        'detail_page_note',
        'requires_date',
        'requires_pax',
        'is_featured',
        'display_order',
        'image',
        'options',
        'status',
    ];

    public function index(Request $request)
    {
        $query = Sightseeing::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->boolean('featured')) {
            $query->where('is_featured', 1);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $sightseeings = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        $cities = Sightseeing::distinct()->whereNotNull('city')->pluck('city')->sort()->values();
        $countries = Sightseeing::distinct()->whereNotNull('country')->pluck('country')->sort()->values();

        $allCount = Sightseeing::count();
        $activeCount = Sightseeing::where('status', 'active')->count();
        $inactiveCount = Sightseeing::where('status', 'inactive')->count();
        $pendingCount = Sightseeing::where('status', 'pending')->count();

        return view('admin.sightseeings.index', compact(
            'sightseeings',
            'cities',
            'countries',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    public function create()
    {
        $sampleOptions = [
            ['name' => 'Mt. Titlis', 'duration_minutes' => 0, 'base_price' => null, 'includes_lunch' => false],
            ['name' => 'Mt. Titlis with Ice Flyer', 'duration_minutes' => 0, 'base_price' => null, 'includes_lunch' => false],
            ['name' => 'Mt. Titlis with Ice Flyer and Indian Lunch', 'duration_minutes' => 0, 'base_price' => null, 'includes_lunch' => true],
            ['name' => 'Mt. Jungfrau', 'duration_minutes' => 0, 'base_price' => null, 'includes_lunch' => false],
            ['name' => 'Mt. Jungfrau with Indian Lunch', 'duration_minutes' => 0, 'base_price' => null, 'includes_lunch' => true],
            ['name' => 'Lake Lucerne Cruise', 'duration_minutes' => null, 'base_price' => null, 'includes_lunch' => false],
            ['name' => 'Rhine Falls Boat Ride - 15 Min', 'duration_minutes' => 15, 'base_price' => null, 'includes_lunch' => false],
            ['name' => 'Rhine Falls Boat Ride - 30 Min', 'duration_minutes' => 30, 'base_price' => null, 'includes_lunch' => false],
            ['name' => 'Lindt Chocolate Museum Visit', 'duration_minutes' => null, 'base_price' => null, 'includes_lunch' => false],
        ];

        return view('admin.sightseeings.create', [
            'sampleOptions' => $sampleOptions,
        ]);
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $data = $this->validateSightseeing($request);

            if ($request->hasFile('image')) {
                $upload = ImageService::upload($request->file('image'), 'sightseeings', null, 4096);
                $data['image'] = $upload['path'];
            }

            $data['created_by'] = optional(auth('admin')->user())->id;

            $sightseeing = Sightseeing::create($data);

            $this->syncOptions($sightseeing, $request);
        });

        return redirect()->route('admin.sightseeings.index')->with('success', 'Sightseeing created successfully.');
    }

    public function show(string $id)
    {
        $sightseeing = Sightseeing::with('options')->findOrFail($id);
        return view('admin.sightseeings.show', compact('sightseeing'));
    }

    public function edit(string $id)
    {
        $sightseeing = Sightseeing::with('options')->findOrFail($id);
        return view('admin.sightseeings.edit', compact('sightseeing'));
    }

    public function update(Request $request, string $id)
    {
        $sightseeing = Sightseeing::findOrFail($id);

        DB::transaction(function () use ($request, $sightseeing) {
            $data = $this->validateSightseeing($request, $sightseeing);

            if ($request->hasFile('image')) {
                $upload = ImageService::update($request->file('image'), $sightseeing->image, 'sightseeings', null, 4096);
                $data['image'] = $upload['path'];
            }

            $sightseeing->update($data);

            $this->syncOptions($sightseeing, $request);
        });

        return redirect()->route('admin.sightseeings.index')->with('success', 'Sightseeing updated successfully.');
    }

    public function destroy(string $id)
    {
        $sightseeing = Sightseeing::findOrFail($id);

        if ($sightseeing->image) {
            ImageService::delete($sightseeing->image);
        }

        $sightseeing->delete();

        return redirect()->route('admin.sightseeings.index')->with('success', 'Sightseeing deleted successfully.');
    }

    /**
     * Show bulk import form.
     */
    public function importForm()
    {
        return view('admin.sightseeings.import', ['columns' => $this->importableColumns]);
    }

    /**
     * Handle bulk import (CSV/XLS/XLSX).
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
        $required = ['title', 'status'];
        foreach ($required as $header) {
            if (!in_array($header, $headers, true)) {
                return back()->with('error', "Missing required column: {$header}");
            }
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $unchanged = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $assoc = $this->mapRowToAssoc($headers, $row);

            if ($this->rowIsEmpty($assoc)) {
                $skipped++;
                continue;
            }

            $payload = $this->sanitizeImportPayload($assoc);

            $validator = Validator::make($payload, [
                'title'               => 'required|string|max:255',
                'description'         => 'nullable|string',
                'country'             => 'nullable|string|max:100',
                'city'                => 'nullable|string|max:100',
                'start_location'      => 'nullable|string|max:255',
                'end_location'        => 'nullable|string|max:255',
                'standard_price'      => 'nullable|numeric|min:0',
                'currency'            => 'nullable|string|max:8',
                'default_pax'         => 'nullable|integer|min:1',
                'standard_price_note' => 'nullable|string|max:255',
                'availability_notes'  => 'nullable|string',
                'booking_conditions'  => 'nullable|string',
                'detail_page_note'    => 'nullable|string',
                'requires_date'       => 'nullable|boolean',
                'requires_pax'        => 'nullable|boolean',
                'is_featured'         => 'nullable|boolean',
                'display_order'       => 'nullable|integer',
                'image'               => 'nullable|string|max:1000',
                'options'             => 'nullable|string',
                'status'              => 'required|in:active,inactive,pending',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            if (!isset($payload['status']) || $payload['status'] === null) {
                $payload['status'] = 'pending';
            }

            // Extract options JSON before saving — it's a relation, not a column
            $optionsJson = $payload['options'] ?? null;
            unset($payload['options']);

            $match = array_filter([
                'title'   => $payload['title']   ?? null,
                'city'    => $payload['city']     ?? null,
                'country' => $payload['country']  ?? null,
            ]);

            $sightseeing = Sightseeing::updateOrCreate(
                !empty($match) ? $match : ['title' => $payload['title']],
                $payload
            );

            // Sync options if provided in the sheet
            if (!empty($optionsJson)) {
                $parsedOptions = $this->parseImportOptions($optionsJson);
                if (!empty($parsedOptions)) {
                    $sightseeing->options()->delete();
                    foreach ($parsedOptions as $optionData) {
                        $sightseeing->options()->create($optionData);
                    }
                }
            }

            if ($sightseeing->wasRecentlyCreated) {
                $created++;
            } elseif ($sightseeing->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $flash = $created > 0 || $updated > 0
            ? ['success' => 'Sightseeing data imported successfully.']
            : ['error' => 'No new data was imported. Please check the file and try again.'];

        return back()
            ->with($flash)
            ->with('import_errors', $errors);
    }

    /**
     * Export sightseeings as CSV or Excel-compatible HTML.
     */
    public function export(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));

        $query = Sightseeing::query();
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $sightseeings = $query->orderBy('created_at', 'desc')->get();
        $rows = $this->buildExportRows($sightseeings);

        $filename = 'sightseeings-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

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
     * Export page.
     */
    public function exportPage()
    {
        return view('admin.sightseeings.export');
    }

    /**
     * Sample download.
     */
    public function sample(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $rows = $this->sampleRows();
        $filename = 'sightseeing-import-sample' . ($format === 'csv' ? '.csv' : '.xls');

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

    private function validateSightseeing(Request $request, ?Sightseeing $sightseeing = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'start_location' => 'nullable|string|max:255',
            'end_location' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ];

        $validated = $request->validate($rules);

        $validated['is_featured'] = $request->boolean('is_featured');

        return $validated;
    }

    private function syncOptions(Sightseeing $sightseeing, Request $request): void
    {
        $options = collect($request->input('options', []))
            ->filter(fn($opt) => !empty($opt['name']))
            ->map(fn($opt) => $this->buildOptionPayload($opt, $sightseeing))
            ->values();

        $sightseeing->options()->delete();

        foreach ($options as $payload) {
            $sightseeing->options()->create($payload);
        }
    }

    private function buildOptionPayload(array $option, Sightseeing $sightseeing): array
    {
        $tags = $this->normalizeTags($option['tags'] ?? null);

        return [
            'name' => $option['name'],
            'description' => $option['description'] ?? null,
            'duration_minutes' => isset($option['duration_minutes']) && $option['duration_minutes'] !== ''
                ? (int) $option['duration_minutes'] : null,
            'base_price' => isset($option['base_price']) && $option['base_price'] !== ''
                ? (float) $option['base_price'] : null,
            'currency' => $option['currency'] ?? $sightseeing->currency ?? 'CHF',
            'includes_lunch' => !empty($option['includes_lunch']),
            'includes_transport' => !empty($option['includes_transport']),
            'availability_note' => $option['availability_note'] ?? null,
            'tags' => $tags,
            'is_active' => array_key_exists('is_active', $option) ? (bool) $option['is_active'] : true,
        ];
    }

    private function normalizeTags($value): ?array
    {
        if (is_array($value)) {
            $clean = array_values(array_filter(array_map('trim', $value)));
            return empty($clean) ? null : $clean;
        }

        if (is_string($value)) {
            $parts = array_map('trim', explode(',', $value));
            $clean = array_values(array_filter($parts));
            return empty($clean) ? null : $clean;
        }

        return null;
    }

    private function sanitizeImportPayload(array $row): array
    {
        // Accept common image column aliases
        if (empty($row['image'])) {
            $row['image'] = $row['image_url'] ?? $row['photo'] ?? $row['photo_url'] ?? null;
        }

        $payload = [];
        foreach ($this->importableColumns as $column) {
            $value = $row[$column] ?? null;
            $payload[$column] = $this->normalizeValue($column, $value);
        }

        // Defaults
        if (empty($payload['status'])) {
            $payload['status'] = 'pending';
        }
        if (empty($payload['currency'])) {
            $payload['currency'] = 'EUR';
        }
        if (!isset($payload['requires_date'])) {
            $payload['requires_date'] = true;
        }
        if (!isset($payload['requires_pax'])) {
            $payload['requires_pax'] = true;
        }
        if (!isset($payload['is_featured'])) {
            $payload['is_featured'] = false;
        }

        return $payload;
    }

    /**
     * Parse an options JSON string from the import sheet into structured option payloads.
     * Accepts the compact format: [{"name":"...","price":120,"lunch":"yes",...}, ...]
     */
    private function parseImportOptions(string $optionsJson): array
    {
        $decoded = json_decode($optionsJson, true);
        if (!is_array($decoded)) {
            return [];
        }

        $result = [];
        foreach ($decoded as $item) {
            if (empty($item['name'])) {
                continue;
            }
            $tags = null;
            if (!empty($item['tags'])) {
                $tags = array_values(array_filter(array_map('trim', explode(',', (string) $item['tags']))));
            }
            $result[] = [
                'name'               => (string) $item['name'],
                'description'        => $item['description'] ?? null,
                'duration_minutes'   => isset($item['duration']) ? (int) $item['duration'] : null,
                'base_price'         => isset($item['price']) ? (float) $item['price'] : null,
                'currency'           => $item['currency'] ?? 'EUR',
                'includes_lunch'     => $this->normalizeBoolean($item['lunch']     ?? 'no'),
                'includes_transport' => $this->normalizeBoolean($item['transport'] ?? 'no'),
                'availability_note'  => $item['note'] ?? null,
                'tags'               => $tags,
                'is_active'          => $this->normalizeBoolean($item['active']    ?? 'yes'),
            ];
        }

        return $result;
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

        // Boolean fields
        if (in_array($column, ['is_featured', 'requires_date', 'requires_pax'], true)) {
            return $this->normalizeBoolean($value);
        }

        // Numeric fields
        if (in_array($column, ['standard_price'], true)) {
            return is_numeric($value) ? (float) $value : null;
        }
        if (in_array($column, ['default_pax', 'display_order'], true)) {
            return is_numeric($value) ? (int) $value : null;
        }

        // Options: keep raw JSON string; parsed later during import sync
        if ($column === 'options') {
            return (string) $value ?: null;
        }

        // Image: accept any URL or storage path as-is
        if ($column === 'image') {
            $str = (string) $value;
            // If it looks like a URL, store it directly
            if (preg_match('/^https?:\/\//i', $str)) {
                return $str;
            }
            return $str ?: null;
        }

        if ($column === 'status') {
            $value = strtolower((string) $value);
            return in_array($value, ['active', 'inactive', 'pending'], true) ? $value : 'pending';
        }

        return $value;
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

    private function buildExportRows($sightseeings): array
    {
        $rows = [];
        $rows[] = $this->importableColumns;

        foreach ($sightseeings as $sightseeing) {
            $sightseeing->loadMissing('options');
            $optionsJson = '';
            if ($sightseeing->options->isNotEmpty()) {
                $optionsJson = json_encode(
                    $sightseeing->options->map(fn($o) => array_filter([
                        'name'      => $o->name,
                        'price'     => $o->base_price,
                        'currency'  => $o->currency,
                        'duration'  => $o->duration_minutes,
                        'lunch'     => $o->includes_lunch     ? 'yes' : 'no',
                        'transport' => $o->includes_transport ? 'yes' : 'no',
                        'note'      => $o->availability_note,
                        'tags'      => is_array($o->tags) ? implode(',', $o->tags) : $o->tags,
                        'active'    => ($o->is_active ?? true) ? 'yes' : 'no',
                        'description' => $o->description,
                    ], fn($v) => $v !== null && $v !== ''))->values()->all(),
                    JSON_UNESCAPED_UNICODE
                );
            }

            $rows[] = [
                $sightseeing->title,
                $sightseeing->description,
                $sightseeing->country,
                $sightseeing->city,
                $sightseeing->start_location,
                $sightseeing->end_location,
                $sightseeing->standard_price,
                $sightseeing->currency ?? 'EUR',
                $sightseeing->default_pax,
                $sightseeing->standard_price_note,
                $sightseeing->availability_notes,
                $sightseeing->booking_conditions,
                $sightseeing->detail_page_note,
                $sightseeing->requires_date ? 'yes' : 'no',
                $sightseeing->requires_pax  ? 'yes' : 'no',
                $sightseeing->is_featured   ? 'yes' : 'no',
                $sightseeing->display_order,
                \App\Services\ImageService::getUrl($sightseeing->image),
                $optionsJson,
                $sightseeing->status,
            ];
        }

        return $rows;
    }

    private function sampleRows(): array
    {
        $sampleOptions = json_encode([
            [
                'name'        => 'Mt. Titlis Basic',
                'price'       => 120,
                'currency'    => 'EUR',
                'duration'    => 480,
                'lunch'       => 'no',
                'transport'   => 'no',
                'tags'        => 'mountain,snow,cable-car',
                'active'      => 'yes',
                'description' => 'Cable car ride to the summit without add-ons.',
            ],
            [
                'name'        => 'Mt. Titlis with Ice Flyer',
                'price'       => 150,
                'currency'    => 'EUR',
                'duration'    => 540,
                'lunch'       => 'no',
                'transport'   => 'no',
                'tags'        => 'mountain,snow,ice-flyer',
                'active'      => 'yes',
                'description' => 'Includes the thrilling Ice Flyer chair lift.',
            ],
            [
                'name'        => 'Mt. Titlis with Indian Lunch',
                'price'       => 175,
                'currency'    => 'EUR',
                'duration'    => 600,
                'lunch'       => 'yes',
                'transport'   => 'no',
                'tags'        => 'mountain,snow,lunch',
                'active'      => 'yes',
                'description' => 'Full experience including Indian lunch at the summit.',
            ],
        ], JSON_UNESCAPED_UNICODE);

        return [
            $this->importableColumns,
            [
                'Mt. Titlis',                                    // title
                'Iconic cable car ride to snowy summit.',        // description
                'Switzerland',                                   // country
                'Engelberg',                                     // city
                'Cable car base station',                        // start_location
                'Summit (3238 m)',                               // end_location
                120.00,                                          // standard_price (EUR) — base/default price
                'EUR',                                           // currency
                2,                                               // default_pax
                'Per person, min 2 pax',                        // standard_price_note
                'Available daily, weather permitting.',          // availability_notes
                'No refund within 24 hours of booking.',        // booking_conditions
                'Warm clothing recommended.',                    // detail_page_note
                'yes',                                           // requires_date  (yes/no)
                'yes',                                           // requires_pax   (yes/no)
                'yes',                                           // is_featured    (yes/no)
                1,                                               // display_order
                'https://example.com/titlis.jpg',                // image (URL — paste media library URL here)
                $sampleOptions,                                  // options — JSON array of packages (see format above)
                'active',                                        // status (active/inactive/pending)
            ],
        ];
    }
}

