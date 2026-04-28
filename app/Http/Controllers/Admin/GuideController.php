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
        // ── Identity ──────────────────────────────────────────────
        'title',
        'full_name',
        'gender',
        'date_of_birth',
        'nationality',
        // ── Contact ───────────────────────────────────────────────
        'phone_country_code',
        'phone_number',
        'email',
        'whatsapp_number',
        'emergency_contact_number',
        // ── Location & Language ────────────────────────────────────
        'country',
        'city',
        'primary_language',
        'other_languages',
        'language_proficiency',
        // ── Experience ────────────────────────────────────────────
        'years_experience',
        'short_bio',
        'description',
        // ── Availability ──────────────────────────────────────────
        'available_from_date',
        'available_to_date',
        'available_days',
        'daily_start_time',
        'daily_end_time',
        'max_bookings_per_day',
        // ── Pricing ───────────────────────────────────────────────
        'half_day_price',
        'full_day_price',
        'extra_hour_price',
        // ── ID & Verification ─────────────────────────────────────
        'id_proof_type',
        'id_proof_number',
        'id_proof_path',
        'license_path',
        'police_verification',
        'verification_status',
        // ── Indian Customer Settings ──────────────────────────────
        'experience_indian_customers',
        'indian_tours_completed',
        'indian_language_support',
        'indian_special_notes',
        // ── Display Settings ──────────────────────────────────────
        'display_on_website',
        'featured_guide',
        // ── Media & Misc ──────────────────────────────────────────
        'profile_photo',
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
        try {
            DB::transaction(function () use ($request) {
                $validated = $this->validateGuide($request);
                $guide = Guide::create($validated);
                $this->syncPackages($guide, $request);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Could not save the guide. Please check all fields and try again. (' . $e->getMessage() . ')');
        }

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

        try {
            DB::transaction(function () use ($request, $guide) {
                $validated = $this->validateGuide($request, $guide);
                $guide->update($validated);
                $this->syncPackages($guide, $request);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Could not save the guide. Please check all fields and try again. (' . $e->getMessage() . ')');
        }

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
                'title'                      => 'required|string|max:255',
                'full_name'                  => 'nullable|string|max:255',
                'gender'                     => 'nullable|in:male,female,other',
                'date_of_birth'              => 'nullable|date',
                'nationality'                => 'nullable|string|max:80',
                'phone_country_code'         => 'nullable|string|max:10',
                'phone_number'               => 'nullable|string|max:30',
                'email'                      => 'nullable|email|max:255',
                'whatsapp_number'            => 'nullable|string|max:30',
                'emergency_contact_number'   => 'nullable|string|max:30',
                'country'                    => 'nullable|string|max:100',
                'city'                       => 'nullable|string|max:100',
                'primary_language'           => 'nullable|string|max:100',
                'other_languages'            => 'nullable',
                'language_proficiency'       => 'nullable|string|max:50',
                'years_experience'           => 'nullable|integer|min:0|max:80',
                'short_bio'                  => 'nullable|string',
                'description'               => 'nullable|string',
                'available_from_date'        => 'nullable|date',
                'available_to_date'          => 'nullable|date',
                'available_days'             => 'nullable',
                'daily_start_time'           => 'nullable|date_format:H:i',
                'daily_end_time'             => 'nullable|date_format:H:i',
                'max_bookings_per_day'       => 'nullable|integer|min:1',
                'half_day_price'             => 'nullable|numeric|min:0',
                'full_day_price'             => 'nullable|numeric|min:0',
                'extra_hour_price'           => 'nullable|numeric|min:0',
                'id_proof_type'              => 'nullable|string|max:100',
                'id_proof_number'            => 'nullable|string|max:120',
                'id_proof_path'              => 'nullable|string|max:1000',
                'license_path'               => 'nullable|string|max:1000',
                'police_verification'        => 'nullable|boolean',
                'verification_status'        => 'nullable|in:pending,approved,rejected',
                'experience_indian_customers'=> 'nullable|boolean',
                'indian_tours_completed'     => 'nullable|integer|min:0',
                'indian_language_support'    => 'nullable',
                'indian_special_notes'       => 'nullable|string',
                'display_on_website'         => 'nullable|boolean',
                'featured_guide'             => 'nullable|boolean',
                'profile_photo'              => 'nullable|string|max:1000',
                'status'                     => 'required|in:active,inactive',
                'notes'                      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            // Use email as the unique key; fall back to full_name, then title+city
            if (!empty($payload['email'])) {
                $match = ['email' => $payload['email']];
            } elseif (!empty($payload['full_name'])) {
                $match = ['full_name' => $payload['full_name']];
            } else {
                $match = array_filter([
                    'title' => $payload['title'],
                    'city'  => $payload['city'] ?? null,
                ]);
            }

            $guide = Guide::updateOrCreate($match, $payload);

            if ($guide->wasRecentlyCreated) {
                $created++;
            } elseif ($guide->wasChanged()) {
                $updated++;
            } else {
                $unchanged++;
            }
        }

        $flash = $created > 0 || $updated > 0
            ? ['success' => 'Guide data imported successfully.']
            : ['error' => 'No new data was imported.'];

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
            // Read both <th> (header cells) and <td> (data cells)
            foreach ($tr->childNodes as $node) {
                if (!($node instanceof \DOMElement)) {
                    continue;
                }
                if (in_array(strtolower($node->tagName), ['td', 'th'], true)) {
                    $row[] = trim($node->textContent);
                }
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
        if (!isset($payload['verification_status']) || $payload['verification_status'] === null) {
            $payload['verification_status'] = 'pending';
        }
        if ($payload['police_verification'] === null) {
            $payload['police_verification'] = false;
        }
        if ($payload['experience_indian_customers'] === null) {
            $payload['experience_indian_customers'] = false;
        }
        if ($payload['display_on_website'] === null) {
            $payload['display_on_website'] = true;
        }
        if ($payload['featured_guide'] === null) {
            $payload['featured_guide'] = false;
        }
        if ($payload['indian_tours_completed'] === null) {
            $payload['indian_tours_completed'] = 0;
        }

        // Aliases for profile photo column
        if (empty($payload['profile_photo'])) {
            foreach (['photo', 'photo_url', 'image', 'image_url', 'guide_photo', 'profile_image'] as $alias) {
                if (!empty($row[$alias])) {
                    $payload['profile_photo'] = $this->normalizeValue('profile_photo', $row[$alias]);
                    break;
                }
            }
        }

        // Sync language field from primary_language for backward compatibility
        if (empty($payload['language']) && !empty($payload['primary_language'])) {
            $payload['language'] = $payload['primary_language'];
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

        // ── Enums ────────────────────────────────────────────────
        if ($column === 'status') {
            $v = strtolower((string) $value);
            return in_array($v, ['active', 'inactive'], true) ? $v : 'active';
        }

        if ($column === 'gender') {
            $v = strtolower((string) $value);
            return in_array($v, ['male', 'female', 'other'], true) ? $v : null;
        }

        if ($column === 'verification_status') {
            $v = strtolower((string) $value);
            return in_array($v, ['pending', 'approved', 'rejected'], true) ? $v : 'pending';
        }

        if ($column === 'language_proficiency') {
            $v = strtolower((string) $value);
            $allowed = ['basic', 'conversational', 'fluent', 'native'];
            // Accept flexible casing/spelling
            foreach ($allowed as $a) {
                if (str_starts_with($v, substr($a, 0, 4))) {
                    return ucfirst($a);
                }
            }
            return (string) $value;
        }

        // ── Integers ─────────────────────────────────────────────
        if (in_array($column, ['years_experience', 'max_bookings_per_day', 'indian_tours_completed'], true)) {
            return is_numeric($value) ? (int) $value : null;
        }

        // ── Decimals ─────────────────────────────────────────────
        if (in_array($column, ['half_day_price', 'full_day_price', 'extra_hour_price'], true)) {
            return is_numeric($value) ? (float) $value : null;
        }

        // ── Booleans (accept yes/no/1/0/true/false) ──────────────
        if (in_array($column, [
            'police_verification', 'experience_indian_customers',
            'display_on_website', 'featured_guide',
        ], true)) {
            $v = strtolower(trim((string) $value));
            if (in_array($v, ['1', 'yes', 'true', 'on'], true))  return true;
            if (in_array($v, ['0', 'no', 'false', 'off'], true)) return false;
            return null;
        }

        // ── Array fields (comma/semicolon/pipe separated) ─────────
        if (in_array($column, ['other_languages', 'available_days', 'indian_language_support'], true)) {
            if (is_array($value)) {
                $items = $value;
            } else {
                $items = preg_split('/[,;|]+/', (string) $value) ?: [];
            }
            $items = array_values(array_filter(array_map('trim', $items)));
            return empty($items) ? null : $items;
        }

        // ── Date fields — handle Excel serial numbers ──────────────
        if (in_array($column, ['date_of_birth', 'available_from_date', 'available_to_date'], true)) {
            $str = trim((string) $value);
            if ($str === '') return null;

            // Excel date serial number (days since 1900-01-01, with Lotus bug offset)
            if (is_numeric($str) && (int)$str > 1000 && !str_contains($str, '-') && !str_contains($str, '/')) {
                try {
                    $date = \Carbon\Carbon::create(1899, 12, 30)->addDays((int)$str);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    return null;
                }
            }

            // Already a string date — return as-is for Laravel's date validator
            return $str;
        }

        // ── Time fields (HH:MM) — handle Excel decimal and HH:MM:SS ─
        if (in_array($column, ['daily_start_time', 'daily_end_time'], true)) {
            $str = trim((string) $value);
            if ($str === '') return null;

            // Excel stores time as a decimal fraction of a day (e.g. 09:00 = 0.375)
            if (is_numeric($str) && $str >= 0 && $str < 1) {
                $totalMinutes = (int) round((float) $str * 1440);
                return sprintf('%02d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
            }

            // Strip seconds from HH:MM:SS
            if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
                return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
            }

            return null;
        }

        // ── URL / path fields (photo + documents) ─────────────────
        if (in_array($column, ['profile_photo', 'id_proof_path', 'license_path'], true)) {
            $v = trim((string) $value);
            return $v !== '' ? $v : null;
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
            $join = fn($v) => is_array($v) ? implode(', ', $v) : ($v ?? '');

            $rows[] = [
                $guide->title,
                $guide->full_name,
                $guide->gender,
                $guide->date_of_birth ? $guide->date_of_birth->format('Y-m-d') : null,
                $guide->nationality,
                $guide->phone_country_code,
                $guide->phone_number,
                $guide->email,
                $guide->whatsapp_number,
                $guide->emergency_contact_number,
                $guide->country,
                $guide->city,
                $guide->primary_language,
                $join($guide->other_languages),
                $guide->language_proficiency,
                $guide->years_experience,
                $guide->short_bio,
                $guide->description,
                $guide->available_from_date ? $guide->available_from_date->format('Y-m-d') : null,
                $guide->available_to_date   ? $guide->available_to_date->format('Y-m-d')   : null,
                $join($guide->available_days),
                $guide->daily_start_time ? substr($guide->daily_start_time, 0, 5) : null,
                $guide->daily_end_time   ? substr($guide->daily_end_time,   0, 5) : null,
                $guide->max_bookings_per_day,
                $guide->half_day_price,
                $guide->full_day_price,
                $guide->extra_hour_price,
                $guide->id_proof_type,
                $guide->id_proof_number,
                \App\Services\ImageService::getUrl($guide->id_proof_path),
                \App\Services\ImageService::getUrl($guide->license_path),
                $guide->police_verification ? 'yes' : 'no',
                $guide->verification_status ?? 'pending',
                $guide->experience_indian_customers ? 'yes' : 'no',
                $guide->indian_tours_completed ?? 0,
                $join($guide->indian_language_support),
                $guide->indian_special_notes,
                $guide->display_on_website ? 'yes' : 'no',
                $guide->featured_guide     ? 'yes' : 'no',
                \App\Services\ImageService::getUrl($guide->profile_photo),
                $guide->status,
                $guide->notes,
            ];
        }

        return $rows;
    }

    private function generateHtmlExcel(array $rows): string
    {
        // Use <td> for ALL rows (including header) so parseHtmlTable can read them back.
        // Bold styling is used to visually distinguish the header row.
        $html = '<table border="1"><thead><tr>';
        foreach ($rows[0] as $heading) {
            $html .= '<td><strong>' . htmlspecialchars((string) $heading) . '</strong></td>';
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
        $headers = $this->importableColumns;

        $sample = [
            '3 Hour City Guide Service',          // title
            'Rahul Sharma',                       // full_name
            'male',                               // gender  (male/female/other)
            '1990-05-15',                         // date_of_birth  (YYYY-MM-DD)
            'Indian',                             // nationality
            '+91',                                // phone_country_code
            '9876543210',                         // phone_number
            'rahul@example.com',                  // email  ← used as unique key on re-import
            '9876543210',                         // whatsapp_number
            '9000000001',                         // emergency_contact_number
            'India',                              // country
            'Delhi',                              // city
            'English',                            // primary_language
            'Hindi, French',                      // other_languages  (comma-separated)
            'Fluent',                             // language_proficiency  (Basic/Conversational/Fluent/Native)
            5,                                    // years_experience
            'Expert local guide for Delhi tours.',// short_bio
            'Rahul has guided over 500 groups.', // description
            '2026-01-01',                         // available_from_date  (YYYY-MM-DD)
            '2026-12-31',                         // available_to_date  (YYYY-MM-DD)
            'Monday, Tuesday, Wednesday, Thursday, Friday', // available_days  (comma-separated)
            '09:00',                              // daily_start_time  (HH:MM)
            '18:00',                              // daily_end_time  (HH:MM)
            3,                                    // max_bookings_per_day
            1500,                                 // half_day_price  (EUR)
            2800,                                 // full_day_price  (EUR)
            450,                                  // extra_hour_price  (EUR)
            'Aadhar Card',                        // id_proof_type
            'XXXX-XXXX-1234',                     // id_proof_number
            'https://example.com/id_proof.jpg',   // id_proof_path  (URL)
            'https://example.com/license.jpg',    // license_path  (URL)
            'yes',                                // police_verification  (yes/no)
            'approved',                           // verification_status  (pending/approved/rejected)
            'yes',                                // experience_indian_customers  (yes/no)
            12,                                   // indian_tours_completed
            'Hindi, Tamil',                       // indian_language_support  (comma-separated)
            'Experienced with Indian group dynamics.', // indian_special_notes
            'yes',                                // display_on_website  (yes/no)
            'no',                                 // featured_guide  (yes/no)
            'https://example.com/photo.jpg',      // profile_photo  (URL — paste media library URL here)
            'active',                             // status  (active/inactive)
            'Certified local guide. Arrive 10 min early.', // notes
        ];

        return [$headers, $sample];
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
            'verification_status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'experience_indian_customers' => 'nullable|boolean',
            'indian_tours_completed' => 'nullable|integer|min:0',
            'indian_language_support' => 'nullable',
            'indian_special_notes' => 'nullable|string',
            'display_on_website' => 'nullable|boolean',
            'featured_guide' => 'nullable|boolean',
            'half_day_price' => 'nullable|numeric|min:0',
            'full_day_price' => 'nullable|numeric|min:0',
            'extra_hour_price' => 'nullable|numeric|min:0',
        ];

        $messages = [
            'title.required'                   => 'The guide title is required.',
            'full_name.required'               => 'The guide\'s full name is required.',
            'status.required'                  => 'Please select a status (Active / Inactive).',
            'status.in'                        => 'Status must be Active or Inactive.',
            'email.email'                      => 'Please enter a valid email address.',
            'date_of_birth.before'             => 'Date of birth must be a date before today.',
            'available_to_date.after_or_equal' => 'Available-to date must be on or after the available-from date.',
            'daily_start_time.date_format'     => 'Daily start time must be in HH:MM format.',
            'daily_end_time.date_format'       => 'Daily end time must be in HH:MM format.',
            'years_experience.integer'         => 'Years of experience must be a whole number.',
            'max_bookings_per_day.integer'     => 'Max bookings per day must be a whole number.',
            'profile_photo.image'              => 'Profile photo must be an image file (jpg, png, gif, etc.).',
            'profile_photo.max'                => 'Profile photo must not exceed 4 MB.',
            'id_proof_upload.mimes'            => 'ID proof must be a JPG, PNG or PDF file.',
            'id_proof_upload.max'              => 'ID proof file must not exceed 8 MB.',
            'license_upload.mimes'             => 'License must be a JPG, PNG or PDF file.',
            'license_upload.max'               => 'License file must not exceed 8 MB.',
        ];

        $validated = $request->validate($rules, $messages);

        $data = array_merge($validated, [
            'other_languages'            => $this->normalizeList($request->input('other_languages')),
            'available_days'             => $this->normalizeList($request->input('available_days')),
            'indian_language_support'    => $this->normalizeList($request->input('indian_language_support')),
            'police_verification'        => $request->boolean('police_verification'),
            'experience_indian_customers'=> $request->boolean('experience_indian_customers'),
            'display_on_website'         => $request->boolean('display_on_website', true),
            'featured_guide'             => $request->boolean('featured_guide'),
        ]);

        // Safe defaults for columns that must never be NULL in the database
        if (empty($data['verification_status'])) {
            $data['verification_status'] = 'pending';
        }
        if (!isset($data['indian_tours_completed']) || $data['indian_tours_completed'] === null) {
            $data['indian_tours_completed'] = 0;
        }

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
            // File upload takes priority over URL
            $uploads['profile_photo'] = $request->file('profile_photo')->store('guides/photos', 'public');
            // Delete old file only if it was a storage path (not an external URL)
            if ($guide && $guide->profile_photo && !str_starts_with($guide->profile_photo, 'http')) {
                Storage::disk('public')->delete($guide->profile_photo);
            }
        } elseif ($request->filled('profile_photo_url')) {
            // Plain URL entered in the text field
            $uploads['profile_photo'] = trim($request->input('profile_photo_url'));
        }

        if ($request->hasFile('id_proof_upload')) {
            $uploads['id_proof_path'] = $request->file('id_proof_upload')->store('guides/documents', 'public');
            if ($guide && $guide->id_proof_path && !str_starts_with($guide->id_proof_path, 'http')) {
                Storage::disk('public')->delete($guide->id_proof_path);
            }
        }

        if ($request->hasFile('license_upload')) {
            $uploads['license_path'] = $request->file('license_upload')->store('guides/documents', 'public');
            if ($guide && $guide->license_path && !str_starts_with($guide->license_path, 'http')) {
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
            'currency' => $pkg['currency'] ?? 'EUR',
        ];
    }
}
