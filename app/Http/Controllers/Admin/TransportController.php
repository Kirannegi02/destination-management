<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transport;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use ZipArchive;

class TransportController extends Controller
{
    private array $importableColumns = [
        'from_location',
        'to_location',
        'vehicle_id',
        'price_per_km',
        'min_charge',
        'currency',
        'notes',
        'status',
    ];

    public function index(Request $request)
    {
        $query = Transport::query()->with('vehicle');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('from_location', 'like', "%{$search}%")
                    ->orWhere('to_location', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $transports = $query->orderBy('created_at', 'desc')->paginate(15);
        $vehicles = Vehicle::orderBy('sort_order')->orderBy('name')->get();

        $allCount = Transport::count();
        $activeCount = Transport::where('status', 'active')->count();
        $inactiveCount = Transport::where('status', 'inactive')->count();
        $pendingCount = Transport::where('status', 'pending')->count();

        return view('admin.transports.index', compact(
            'transports',
            'vehicles',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    public function create()
    {
        $vehicles = Vehicle::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.transports.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateTransport($request);
        Transport::create($validated);
        return redirect()->route('admin.transports.index')->with('success', 'Transport created successfully.');
    }

    public function show(string $id)
    {
        $transport = Transport::with('vehicle')->findOrFail($id);
        return view('admin.transports.show', compact('transport'));
    }

    public function edit(string $id)
    {
        $transport = Transport::findOrFail($id);
        $vehicles = Vehicle::where('status', 'active')->orderBy('sort_order')->orderBy('name')->get();
        return view('admin.transports.edit', compact('transport', 'vehicles'));
    }

    public function update(Request $request, string $id)
    {
        $transport = Transport::findOrFail($id);
        $validated = $this->validateTransport($request);
        $transport->update($validated);
        return redirect()->route('admin.transports.index')->with('success', 'Transport updated successfully.');
    }

    public function destroy(string $id)
    {
        $transport = Transport::findOrFail($id);
        $transport->delete();
        return redirect()->route('admin.transports.index')->with('success', 'Transport deleted successfully.');
    }

    public function importForm()
    {
        $vehicles = Vehicle::orderBy('name')->get();
        return view('admin.transports.import', [
            'columns' => $this->importableColumns,
            'vehicles' => $vehicles,
        ]);
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
        $required = ['vehicle_id', 'price_per_km', 'status'];
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
                'from_location' => 'nullable|string|max:255',
                'to_location' => 'nullable|string|max:255',
                'vehicle_id' => 'required|exists:vehicles,id',
                'price_per_km' => 'required|numeric|min:0',
                'min_charge' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'notes' => 'nullable|string',
                'status' => 'required|in:active,inactive,pending',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $match = [
                'from_location' => $payload['from_location'] ?? null,
                'to_location' => $payload['to_location'] ?? null,
                'vehicle_id' => $payload['vehicle_id'],
            ];
            $transport = Transport::updateOrCreate($match, $payload);

            if ($transport->wasRecentlyCreated) {
                $created++;
            } elseif ($transport->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";
        $flash = ($created > 0 || $updated > 0)
            ? ['success' => 'Transport data imported successfully. ' . $message]
            : ['error' => 'No new data imported. ' . $message];

        return back()->with($flash)->with('import_errors', $errors);
    }

    public function export(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));

        $query = Transport::with('vehicle');
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $transports = $query->orderBy('created_at', 'desc')->get();
        $rows = $this->buildExportRows($transports);

        $filename = 'transports-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

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
        $vehicles = Vehicle::orderBy('name')->get();
        return view('admin.transports.export', compact('vehicles'));
    }

    public function sample(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $rows = $this->sampleRows();
        $filename = 'transport-import-sample' . ($format === 'csv' ? '.csv' : '.xls');

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

    private function validateTransport(Request $request): array
    {
        return $request->validate([
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'vehicle_id' => 'required|exists:vehicles,id',
            'price_per_km' => 'required|numeric|min:0',
            'min_charge' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
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
        return $payload;
    }

    private function normalizeValue(string $column, $value)
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $column === 'status' ? 'pending' : null;
        }
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($column === 'status') {
            $value = strtolower((string) $value);
            return in_array($value, ['active', 'inactive', 'pending'], true) ? $value : 'pending';
        }
        if (in_array($column, ['price_per_km', 'min_charge'], true)) {
            return is_numeric($value) ? (float) $value : null;
        }
        if ($column === 'vehicle_id') {
            return is_numeric($value) ? (int) $value : null;
        }
        return $value;
    }

    private function normalizeFormat(?string $format): string
    {
        $format = strtolower($format ?? 'xls');
        return in_array($format, ['csv', 'xls', 'xlsx'], true) ? $format : 'xls';
    }

    private function buildExportRows($transports): array
    {
        $rows = [['from_location', 'to_location', 'vehicle_id', 'vehicle_name', 'price_per_km', 'min_charge', 'currency', 'notes', 'status']];
        foreach ($transports as $t) {
            $rows[] = [
                $t->from_location,
                $t->to_location,
                $t->vehicle_id,
                $t->vehicle ? $t->vehicle->name : '',
                $t->price_per_km,
                $t->min_charge,
                $t->currency ?? 'INR',
                $t->notes,
                $t->status,
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
        $vehicleId = Vehicle::orderBy('id')->value('id') ?? 1;
        return [
            $this->importableColumns,
            [
                'Delhi',
                'Agra',
                $vehicleId,
                25.00,
                500,
                'INR',
                'One-way rate',
                'active',
            ],
        ];
    }
}
