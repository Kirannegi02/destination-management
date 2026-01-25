<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sightseeing;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ZipArchive;

class SightseeingController extends Controller
{
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
        'requires_date',
        'requires_pax',
        'is_featured',
        'status',
        'display_order',
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

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        $sightseeings = $query->orderBy('display_order', 'asc')
            ->orderBy('created_at', 'desc')
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
        $defaultNote = 'Please select date and pax count for exact pricing for your group';
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
            'defaultNote' => $defaultNote,
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

            if (empty($data['standard_price_note'])) {
                $data['standard_price_note'] = 'Please select date and pax count for exact pricing for your group';
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
        $defaultNote = 'Please select date and pax count for exact pricing for your group';
        return view('admin.sightseeings.edit', compact('sightseeing', 'defaultNote'));
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

            if (empty($data['standard_price_note'])) {
                $data['standard_price_note'] = 'Please select date and pax count for exact pricing for your group';
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
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'country' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'start_location' => 'nullable|string|max:255',
                'end_location' => 'nullable|string|max:255',
                'standard_price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:8',
                'default_pax' => 'nullable|integer|min:1|max:500',
                'standard_price_note' => 'nullable|string|max:255',
                'requires_date' => 'nullable|boolean',
                'requires_pax' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
                'status' => 'required|in:active,inactive,pending',
                'display_order' => 'nullable|integer|min:0|max:9999',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            if (!isset($payload['status']) || $payload['status'] === null) {
                $payload['status'] = 'pending';
            }

            $match = array_filter([
                'title' => $payload['title'] ?? null,
                'city' => $payload['city'] ?? null,
                'country' => $payload['country'] ?? null,
            ]);

            $sightseeing = Sightseeing::updateOrCreate(
                !empty($match) ? $match : ['title' => $payload['title']],
                $payload
            );

            if ($sightseeing->wasRecentlyCreated) {
                $created++;
            } elseif ($sightseeing->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";

        $flash = $created > 0 || $updated > 0
            ? ['success' => 'Sightseeing data imported successfully. ' . $message]
            : ['error' => 'No new data imported. ' . $message];

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
            'standard_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'default_pax' => 'nullable|integer|min:1|max:500',
            'standard_price_note' => 'nullable|string|max:255',
            'requires_date' => 'nullable|boolean',
            'requires_pax' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'display_order' => 'nullable|integer|min:0|max:9999',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ];

        $validated = $request->validate($rules);

        $validated['requires_date'] = $request->boolean('requires_date', true);
        $validated['requires_pax'] = $request->boolean('requires_pax', true);
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
            'default_pax' => isset($option['default_pax']) && $option['default_pax'] !== ''
                ? (int) $option['default_pax'] : $sightseeing->default_pax,
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

    private function parseUploadRows($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($file->getRealPath());
        }

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
        $loaded = $dom->loadHTML($content);
        libxml_clear_errors();
        if (!$loaded) {
            return [];
        }

        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            if (!$tr instanceof \DOMElement) {
                continue;
            }
            $row = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                if (!$td instanceof \DOMElement) {
                    continue;
                }
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
            $normalized = strtolower(trim($header));
            $normalized = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $normalized);
            $normalized = str_replace([' ', '-'], '_', $normalized);
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

        if (in_array($column, ['requires_date', 'requires_pax', 'is_featured'], true)) {
            return $this->normalizeBoolean($value);
        }

        if ($column === 'status') {
            $value = strtolower((string) $value);
            return in_array($value, ['active', 'inactive', 'pending'], true) ? $value : 'pending';
        }

        if ($column === 'standard_price') {
            return is_numeric($value) ? (float) $value : null;
        }

        if ($column === 'default_pax' || $column === 'display_order') {
            return is_numeric($value) ? (int) $value : null;
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
            $rows[] = [
                $sightseeing->title,
                $sightseeing->description,
                $sightseeing->country,
                $sightseeing->city,
                $sightseeing->start_location,
                $sightseeing->end_location,
                $sightseeing->standard_price,
                $sightseeing->currency,
                $sightseeing->default_pax,
                $sightseeing->standard_price_note,
                $sightseeing->requires_date ? 'Yes' : 'No',
                $sightseeing->requires_pax ? 'Yes' : 'No',
                $sightseeing->is_featured ? 'Yes' : 'No',
                $sightseeing->status,
                $sightseeing->display_order,
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
            $this->importableColumns,
            [
                'Mt. Titlis',
                'Base sightseeing without add-ons',
                'Switzerland',
                'Engelberg',
                'Cable car base',
                'Summit',
                null,
                'CHF',
                10,
                'Please select date and pax count for exact pricing for your group',
                'Yes',
                'Yes',
                'No',
                'active',
                1,
            ],
        ];
    }
}

