<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Models\GuidePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        'half_day_price',
        'full_day_price',
        'extra_hour_price',
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
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('language', 'like', "%{$search}%")
                    ->orWhere('primary_language', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('language')) {
            $selectedLang = $request->language;
            $query->where(function ($q) use ($selectedLang) {
                $q->where('language', $selectedLang)
                    ->orWhere('primary_language', $selectedLang)
                    ->orWhereJsonContains('other_languages', $selectedLang);
            });
        }

        $guides = $query->orderBy('created_at', 'desc')->paginate(15);

        $cities = Guide::distinct()->whereNotNull('city')->pluck('city')->sort()->values();
        $languages = Guide::query()
            ->select('language', 'primary_language')
            ->get()
            ->flatMap(function ($g) {
                $values = [];
                if (!empty($g->language)) $values[] = $g->language;
                if (!empty($g->primary_language)) $values[] = $g->primary_language;
                return $values;
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $allCount = Guide::count();
        $activeCount = Guide::where('status', 'active')->count();
        $inactiveCount = Guide::where('status', 'inactive')->count();

        return view('admin.guides.index', compact(
            'guides',
            'cities',
            'languages',
            'allCount',
            'activeCount',
            'inactiveCount'
        ));
    }

    public function create()
    {
        return view('admin.guides.create');
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $validated = $this->validateGuide($request);
            $guide = Guide::create($validated);
            $this->syncPackages($guide, $request);
        });

        return redirect()->route('admin.guides.index')->with('success', 'Guide created successfully.');
    }

    public function show(string $id)
    {
        $guide = Guide::with('packages')->findOrFail($id);
        return view('admin.guides.show', compact('guide'));
    }

    public function edit(string $id)
    {
        $guide = Guide::with('packages')->findOrFail($id);
        return view('admin.guides.edit', compact('guide'));
    }

    public function update(Request $request, string $id)
    {
        $guide = Guide::findOrFail($id);
        DB::transaction(function () use ($request, $guide) {
            $validated = $this->validateGuide($request, $guide);
            $guide->update($validated);
            $this->syncPackages($guide, $request);
        });

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
                'half_day_price' => 'nullable|numeric|min:0',
                'full_day_price' => 'nullable|numeric|min:0',
                'extra_hour_price' => 'nullable|numeric|min:0',
                'status' => 'required|in:active,inactive',
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
            $payload['status'] = 'active';
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
            return in_array($value, ['active', 'inactive'], true) ? $value : 'active';
        }

        if ($column === 'duration_hours') {
            return is_numeric($value) ? (int) $value : null;
        }

        if (in_array($column, ['half_day_price', 'full_day_price', 'extra_hour_price'], true)) {
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
                $guide->half_day_price,
                $guide->full_day_price,
                $guide->extra_hour_price,
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
                2800,
                450,
                'active',
                'Arrive 10 minutes early at meeting point.',
            ],
        ];
    }

    private function validateGuide(Request $request, ?Guide $guide = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'profile_photo' => 'nullable|image|max:4096',
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => 'nullable|date|before:today',
            'phone_country_code' => 'nullable|string|max:10',
            'phone_number' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'whatsapp_number' => 'nullable|string|max:30',
            'emergency_contact_number' => 'nullable|string|max:30',
            'nationality' => 'nullable|string|max:80',
            'years_experience' => 'nullable|integer|min:0|max:80',
            'short_bio' => 'nullable|string',
            'description' => 'nullable|string',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:100',
            'primary_language' => 'nullable|string|max:100',
            'other_languages' => 'nullable',
            'language_proficiency' => 'nullable|string|max:50',
            'available_days' => 'nullable',
            'available_from_date' => 'nullable|date',
            'available_to_date' => 'nullable|date|after_or_equal:available_from_date',
            'daily_start_time' => 'nullable|date_format:H:i',
            'daily_end_time' => 'nullable|date_format:H:i',
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => 'nullable|string',
            'max_bookings_per_day' => 'nullable|integer|min:1|max:500',
            'id_proof_type' => 'nullable|string|max:100',
            'id_proof_number' => 'nullable|string|max:120',
            'id_proof_upload' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
            'license_upload' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
            'police_verification' => 'nullable|boolean',
            'verification_status' => ['nullable', Rule::in(['approved', 'rejected'])],
            'experience_indian_customers' => 'nullable|boolean',
            'indian_tours_completed' => 'nullable|integer|min:0',
            'indian_language_support' => 'nullable',
            'indian_special_notes' => 'nullable|string',
            'display_on_website' => 'nullable|boolean',
            'featured_guide' => 'nullable|boolean',
        ];

        $validated = $request->validate($rules);

        $data = array_merge($validated, [
            'other_languages' => $this->normalizeList($request->input('other_languages')),
            'available_days' => $this->normalizeList($request->input('available_days')),
            'indian_language_support' => $this->normalizeList($request->input('indian_language_support')),
            'police_verification' => $request->boolean('police_verification'),
            'experience_indian_customers' => $request->boolean('experience_indian_customers'),
            'display_on_website' => $request->boolean('display_on_website', true),
            'featured_guide' => $request->boolean('featured_guide'),
        ]);

        if (empty($data['language']) && !empty($data['primary_language'])) {
            $data['language'] = $data['primary_language'];
        }

        if (!isset($data['created_by'])) {
            $data['created_by'] = optional(auth('admin')->user())->id;
        }

        $uploads = $this->handleUploads($request, $guide);

        return array_merge($data, $uploads);
    }

    private function normalizeList($value): ?array
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

    private function handleUploads(Request $request, ?Guide $guide = null): array
    {
        $uploads = [];

        if ($request->hasFile('profile_photo')) {
            $uploads['profile_photo'] = $request->file('profile_photo')->store('guides/photos', 'public');
            if ($guide && $guide->profile_photo) {
                Storage::disk('public')->delete($guide->profile_photo);
            }
        }

        if ($request->hasFile('id_proof_upload')) {
            $uploads['id_proof_path'] = $request->file('id_proof_upload')->store('guides/documents', 'public');
            if ($guide && $guide->id_proof_path) {
                Storage::disk('public')->delete($guide->id_proof_path);
            }
        }

        if ($request->hasFile('license_upload')) {
            $uploads['license_path'] = $request->file('license_upload')->store('guides/documents', 'public');
            if ($guide && $guide->license_path) {
                Storage::disk('public')->delete($guide->license_path);
            }
        }

        return $uploads;
    }

    private function syncPackages(Guide $guide, Request $request): void
    {
        $packages = $request->input('packages', []);
        $cleanPackages = collect($packages)
            ->filter(fn($pkg) => !empty($pkg['service_name']) || !empty($pkg['service_type']))
            ->values();

        $existingPackages = $guide->packages()->get()->keyBy('id');
        $keptIds = [];

        foreach ($cleanPackages as $pkg) {
            $payload = $this->buildPackagePayload($pkg);
            $incomingId = isset($pkg['id']) && $pkg['id'] !== '' ? (int) $pkg['id'] : null;

            if ($incomingId && $existingPackages->has($incomingId)) {
                $existingPackages[$incomingId]->update($payload);
                $keptIds[] = $incomingId;
                continue;
            }

            $new = $guide->packages()->create($payload);
            $keptIds[] = $new->id;
        }

        // Remove packages deleted from the form, while preserving updated ones.
        if (!empty($keptIds)) {
            $guide->packages()->whereNotIn('id', $keptIds)->delete();
        } else {
            $guide->packages()->delete();
        }
    }

    private function buildPackagePayload(array $pkg): array
    {
        return [
            'service_type' => $pkg['service_type'] ?? null,
            'service_name' => $pkg['service_name'] ?? null,
            'duration_hours' => isset($pkg['duration_hours']) && $pkg['duration_hours'] !== '' ? (int) $pkg['duration_hours'] : null,
            'standard_price' => isset($pkg['standard_price']) && $pkg['standard_price'] !== '' ? (float) $pkg['standard_price'] : null,
            'extra_hour_price' => isset($pkg['extra_hour_price']) && $pkg['extra_hour_price'] !== '' ? (float) $pkg['extra_hour_price'] : null,
            'default_start_location' => $pkg['default_start_location'] ?? null,
            'default_end_location' => $pkg['default_end_location'] ?? null,
            'start_point' => $pkg['start_point'] ?? null,
            'end_point' => $pkg['end_point'] ?? null,
            'start_time' => $pkg['start_time'] ?? null,
            'end_time' => $pkg['end_time'] ?? null,
            'notes' => $pkg['notes'] ?? null,
            'status' => ($pkg['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            'currency' => $pkg['currency'] ?? 'INR',
        ];
    }
}
