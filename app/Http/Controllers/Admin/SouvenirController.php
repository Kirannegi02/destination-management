<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Souvenir;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class SouvenirController extends Controller
{
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
                'status' => 'required|in:active,inactive,pending',
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

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";
        $flash = ($created > 0 || $updated > 0)
            ? ['success' => 'Souvenir data imported successfully. ' . $message]
            : ['error' => 'No new data imported. ' . $message];

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
            // name, description, price, currency, min_order_quantity, city, latitude, longitude, stock, country, status
            ['Swiss Chocolate Box', 'Premium chocolate assortment', '24.99', 'CHF', 2, 'Zurich', 47.3769, 8.5417, 50, 'Switzerland', 'active'],
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

    private function parseUploadRows($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, ['csv', 'txt'])) {
            return $this->parseCsv($file->getRealPath());
        }
        $xlsxRows = $this->parseXlsx($file->getRealPath());
        return !empty($xlsxRows) ? $xlsxRows : $this->parseHtmlTable($file->getRealPath());
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
                    $text = isset($si->t) ? (string) $si->t : '';
                    if ($text === '' && isset($si->r)) {
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
                    $value = $sharedStrings[(int) $value] ?? '';
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
            $n = strtolower(trim($header));
            $n = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $n);
            $n = str_replace([' ', '-'], '_', $n);
            return preg_replace('/_+/', '_', $n);
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
            if ($value !== null && trim((string) $value) !== '') {
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
        if ($column === 'price') {
            return is_numeric($value) ? (float) $value : null;
        }
        if ($column === 'latitude') {
            return is_numeric($value) ? (float) $value : null;
        }
        if ($column === 'longitude') {
            return is_numeric($value) ? (float) $value : null;
        }
        if ($column === 'stock') {
            return is_numeric($value) ? max(0, (int) $value) : 0;
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
            $rows[] = [
                $s->name,
                $s->description,
                $s->price,
                $s->currency,
                $s->min_order_quantity,
                $s->city,
                $s->latitude,
                $s->longitude,
                $s->stock,
                $s->country,
                $s->status,
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
}
