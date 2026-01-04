<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class GuideController extends Controller
{
    /**
     * Columns used for import/export.
     */
    private array $importableColumns = [
        'title',
        'description',
        'country',
        'city',
        'language',
        'service_date',
        'start_point',
        'end_point',
        'start_time',
        'end_time',
        'duration_hours',
        'price',
        'status',
        'notes',
    ];

    public function index(Request $request)
    {
        $query = Guide::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('language', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        $guides = $query->orderBy('created_at', 'desc')->paginate(15);

        $cities = Guide::distinct()->whereNotNull('city')->pluck('city')->sort()->values();
        $languages = Guide::distinct()->whereNotNull('language')->pluck('language')->sort()->values();

        $allCount = Guide::count();
        $activeCount = Guide::where('status', 'active')->count();
        $inactiveCount = Guide::where('status', 'inactive')->count();
        $pendingCount = Guide::where('status', 'pending')->count();

        return view('admin.guides.index', compact(
            'guides',
            'cities',
            'languages',
            'allCount',
            'activeCount',
            'inactiveCount',
            'pendingCount'
        ));
    }

    public function create()
    {
        return view('admin.guides.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateGuide($request);

        Guide::create($validated);

        return redirect()->route('admin.guides.index')->with('success', 'Guide created successfully.');
    }

    public function show(string $id)
    {
        $guide = Guide::findOrFail($id);
        return view('admin.guides.show', compact('guide'));
    }

    public function edit(string $id)
    {
        $guide = Guide::findOrFail($id);
        return view('admin.guides.edit', compact('guide'));
    }

    public function update(Request $request, string $id)
    {
        $guide = Guide::findOrFail($id);
        $validated = $this->validateGuide($request);
        $guide->update($validated);

        return redirect()->route('admin.guides.index')->with('success', 'Guide updated successfully.');
    }

    public function destroy(string $id)
    {
        $guide = Guide::findOrFail($id);
        $guide->delete();

        return redirect()->route('admin.guides.index')->with('success', 'Guide deleted successfully.');
    }

    /**
     * Show bulk import form.
     */
    public function importForm()
    {
        return view('admin.guides.import', ['columns' => $this->importableColumns]);
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
                'language' => 'nullable|string|max:100',
                'service_date' => 'nullable|date',
                'start_point' => 'nullable|string|max:255',
                'end_point' => 'nullable|string|max:255',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'duration_hours' => 'nullable|integer|min:1|max:72',
                'price' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,inactive,pending',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $match = array_filter([
                'title' => $payload['title'] ?? null,
                'service_date' => $payload['service_date'] ?? null,
                'city' => $payload['city'] ?? null,
            ]);

            $guide = Guide::updateOrCreate(
                !empty($match) ? $match : ['title' => $payload['title']],
                $payload
            );

            if ($guide->wasRecentlyCreated) {
                $created++;
            } elseif ($guide->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $message = "Import completed. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Unchanged: {$unchanged}.";

        $flash = $created > 0 || $updated > 0
            ? ['success' => 'Guide data imported successfully. ' . $message]
            : ['error' => 'No new data imported. ' . $message];

        return back()
            ->with($flash)
            ->with('import_errors', $errors);
    }

    /**
     * Export guides as CSV or Excel-compatible HTML.
     */
    public function export(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));

        $query = Guide::query();
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }
        if ($request->filled('language')) {
            $query->where('language', $request->language);
        }

        $guides = $query->orderBy('created_at', 'desc')->get();
        $rows = $this->buildExportRows($guides);

        $filename = 'guides-' . now()->format('Ymd-His') . ($format === 'csv' ? '.csv' : '.xls');

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
        return view('admin.guides.export');
    }

    /**
     * Sample download.
     */
    public function sample(Request $request)
    {
        $format = $this->normalizeFormat($request->get('format'));
        $rows = $this->sampleRows();
        $filename = 'guide-import-sample' . ($format === 'csv' ? '.csv' : '.xls');

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
        if (empty($payload['status'])) {
            $payload['status'] = 'pending';
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

        if ($column === 'status') {
            $value = strtolower((string) $value);
            return in_array($value, ['active', 'inactive', 'pending'], true) ? $value : 'pending';
        }

        if ($column === 'duration_hours') {
            return is_numeric($value) ? (int) $value : null;
        }

        if ($column === 'price') {
            return is_numeric($value) ? (float) $value : null;
        }

        if ($column === 'service_date') {
            return $value;
        }

        if (in_array($column, ['start_time', 'end_time'], true)) {
            return $value;
        }

        return $value;
    }

    private function normalizeFormat(?string $format): string
    {
        $format = strtolower($format ?? 'xls');
        return in_array($format, ['csv', 'xls', 'xlsx'], true) ? $format : 'xls';
    }

    private function buildExportRows($guides): array
    {
        $rows = [];
        $rows[] = $this->importableColumns;

        foreach ($guides as $guide) {
            $rows[] = [
                $guide->title,
                $guide->description,
                $guide->country,
                $guide->city,
                $guide->language,
                $guide->service_date ? $guide->service_date->format('Y-m-d') : null,
                $guide->start_point,
                $guide->end_point,
                $guide->start_time ? $guide->start_time->format('H:i') : null,
                $guide->end_time ? $guide->end_time->format('H:i') : null,
                $guide->duration_hours,
                $guide->price,
                $guide->status,
                $guide->notes,
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
                '3 Hour Guide Service',
                'City highlights with local expert',
                'India',
                'Delhi',
                'English',
                '2026-01-10',
                'Connaught Place',
                'India Gate',
                '10:00',
                '13:00',
                3,
                1500,
                'active',
                'Arrive 10 minutes early at meeting point.',
            ],
        ];
    }

    private function validateGuide(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:100',
            'service_date' => 'nullable|date',
            'start_point' => 'nullable|string|max:255',
            'end_point' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'duration_hours' => 'nullable|integer|min:1|max:72',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive,pending',
            'notes' => 'nullable|string',
        ]);
    }
}


