<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ZipArchive;

class VehicleController extends Controller
{
    private array $importableColumns = [
        'name',
        'capacity_seats',
        'description',
        'default_price_per_km',
        'currency',
        'status',
        'sort_order',
    ];

    public function index(Request $request)
    {
        $query = Vehicle::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $vehicles = $query->orderBy('sort_order')->orderBy('name')->paginate(15);

        $allCount = Vehicle::count();
        $activeCount = Vehicle::where('status', 'active')->count();
        $inactiveCount = Vehicle::where('status', 'inactive')->count();
        $pendingCount = Vehicle::where('status', 'pending')->count();

        return view('admin.vehicles.index', compact(
            'vehicles',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    public function create()
    {
        return view('admin.vehicles.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateVehicle($request);
        Vehicle::create($validated);
        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle created successfully.');
    }

    public function show(string $id)
    {
        $vehicle = Vehicle::with('transports')->findOrFail($id);
        return view('admin.vehicles.show', compact('vehicle'));
    }

    public function edit(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return view('admin.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $validated = $this->validateVehicle($request);
        $vehicle->update($validated);
        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(string $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        if ($vehicle->transports()->exists()) {
            return redirect()->route('admin.vehicles.index')
                ->with('error', 'Cannot delete vehicle. It has transport routes. Remove or reassign them first.');
        }
        $vehicle->delete();
        return redirect()->route('admin.vehicles.index')->with('success', 'Vehicle deleted successfully.');
    }

    public function importForm()
    {
        return view('admin.vehicles.import', ['columns' => $this->importableColumns]);
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
            return back()->with('error', 'Could not read the uploaded file.');
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $required = ['name', 'status'];
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
                'name' => 'required|string|max:255',
                'capacity_seats' => 'nullable|integer|min:1|max:100',
                'description' => 'nullable|string',
                'default_price_per_km' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'status' => 'required|in:active,inactive,pending',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $vehicle = Vehicle::updateOrCreate(
                ['name' => $payload['name']],
                $payload
            );

            if ($vehicle->wasRecentlyCreated) {
                $created++;
            } elseif ($vehicle->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";
        $flash = ($created > 0 || $updated > 0)
            ? ['success' => 'Vehicle data imported successfully. ' . $message]
            : ['error' => 'No new data imported. ' . $message];

        return back()->with($flash)->with('import_errors', $errors);
    }

    public function export(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));

        $query = Vehicle::query();
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $vehicles = $query->orderBy('sort_order')->orderBy('name')->get();
        $rows = $this->buildExportRows($vehicles);

        $filename = 'vehicles-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

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
        return view('admin.vehicles.export');
    }

    public function sample(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $rows = $this->sampleRows();
        $filename = 'vehicle-import-sample' . ($format === 'csv' ? '.csv' : '.xls');

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

    private function validateVehicle(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'capacity_seats' => 'nullable|integer|min:1|max:100',
            'description' => 'nullable|string',
            'default_price_per_km' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'sort_order' => 'nullable|integer|min:0',
        ]);
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
            $normalized = strtolower(trim($header));
            $normalized = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $normalized);
            $normalized = str_replace([' ', '-'], '_', $normalized);
            return preg_replace('/_+/', '_', $normalized);
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
        if (empty($payload['status'])) {
            $payload['status'] = 'pending';
        }
        if (empty($payload['currency'])) {
            $payload['currency'] = 'INR';
        }
        if (!isset($payload['sort_order']) || $payload['sort_order'] === null) {
            $payload['sort_order'] = 0;
        }
        return $payload;
    }

    private function normalizeValue(string $column, $value)
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            if ($column === 'status') {
                return 'pending';
            }
            if ($column === 'sort_order') {
                return 0;
            }
            return null;
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($column === 'status') {
            $value = strtolower((string) $value);
            return in_array($value, ['active', 'inactive', 'pending'], true) ? $value : 'pending';
        }
        if (in_array($column, ['default_price_per_km'], true)) {
            return is_numeric($value) ? (float) $value : null;
        }
        if (in_array($column, ['capacity_seats', 'sort_order'], true)) {
            return is_numeric($value) ? (int) $value : 0;
        }
        return $value;
    }

    private function normalizeFormat(?string $format): string
    {
        $format = strtolower($format ?? 'xls');
        return in_array($format, ['csv', 'xls', 'xlsx'], true) ? $format : 'xls';
    }

    private function buildExportRows($vehicles): array
    {
        $rows = [$this->importableColumns];
        foreach ($vehicles as $v) {
            $rows[] = [
                $v->name,
                $v->capacity_seats,
                $v->description,
                $v->default_price_per_km,
                $v->currency ?? 'INR',
                $v->status,
                $v->sort_order,
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
                'Sedan',
                4,
                'AC Sedan for city transfers',
                20.00,
                'INR',
                'active',
                1,
            ],
        ];
    }
}
