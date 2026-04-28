<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transport;
use App\Models\TransportZone;
use App\Models\Vehicle;
use App\Services\NominatimGeocoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class TransportController extends Controller
{
    private array $importableColumns = [
        'location',
        'vehicle_id',
        'price_per_km',
        'min_charge',
        'notes',
        'status',
    ];

    public function index(Request $request)
    {
        $query = Transport::query()->with(['vehicle', 'zone']);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('location', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('zone', function ($zq) use ($search) {
                        $zq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $transports = $query->orderBy('created_at', 'desc')->paginate(15);
        $vehicles = Vehicle::orderBy('name')->get();

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

    public function create(\Illuminate\Http\Request $request)
    {
        $vehicles = Vehicle::where('status', 'active')->orderBy('name')->get();

        return view('admin.transports.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateZoneBundleRequest($request, null);
        DB::transaction(function () use ($validated) {
            $this->persistZoneBundle($validated);
        });

        return redirect()->route('admin.transports.index')->with('success', 'Zone and vehicle pricing saved.');
    }

    /**
     * Reverse geocode (OpenStreetMap Nominatim) — suggest city/area text from map coordinates for the cities field.
     */
    public function reverseGeocode(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        try {
            $res = Http::withHeaders([
                'User-Agent' => (config('app.name') ?: 'DMS') . ' AdminTransport/1.0',
            ])->timeout(12)->get('https://nominatim.openstreetmap.org/reverse', [
                'format' => 'json',
                'lat' => $request->input('lat'),
                'lon' => $request->input('lng'),
                'accept-language' => 'en',
            ]);

            if (!$res->successful()) {
                return response()->json(['success' => false, 'message' => 'Geocoder error.'], 502);
            }

            $j = $res->json();
            $addr = $j['address'] ?? [];
            $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['municipality'] ?? null;
            $state = $addr['state'] ?? null;
            $line = $city ? ($state ? $city . ', ' . $state : $city) : (string) ($j['display_name'] ?? '');

            return response()->json([
                'success' => true,
                'city' => $city,
                'state' => $state,
                'line' => $line,
                'display_name' => $j['display_name'] ?? null,
            ]);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Could not reach geocoder.'], 502);
        }
    }

    /**
     * Forward geocode (Nominatim search) — map search box in zone editor.
     */
    public function forwardGeocode(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:200',
        ]);

        try {
            $results = NominatimGeocoder::searchPlaces($request->input('q'), 6);

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Search failed.'], 502);
        }
    }

    /**
     * Reverse-geocode sample points inside the drawn polygon to build a cities list (no manual typing).
     */
    public function suggestCitiesFromPolygon(Request $request)
    {
        $request->validate([
            'polygon_json' => 'required|string',
        ]);

        $polygon = $this->parsePolygonJson($request->input('polygon_json'));
        if (!$polygon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid polygon.',
            ], 422);
        }

        try {
            $cities = NominatimGeocoder::suggestedLocalityNamesFromPolygon($polygon, 8);

            return response()->json([
                'success' => true,
                'cities' => $cities,
            ]);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Could not resolve place names.'], 502);
        }
    }

    public function show(string $id)
    {
        $transport = Transport::with(['vehicle', 'zone'])->findOrFail($id);
        return view('admin.transports.show', compact('transport'));
    }

    public function edit(string $id)
    {
        $transport = Transport::with(['zone', 'vehicle'])->findOrFail($id);
        if (!$transport->transport_zone_id || !$transport->zone) {
            return redirect()->route('admin.transports.index')
                ->with('error', 'This record has no zone. Add a new zone package from Add Transport.');
        }

        $zone = $transport->zone;
        $vehicles = Vehicle::orderBy('name')->get();
        $zoneTransports = Transport::where('transport_zone_id', $zone->id)
            ->with('vehicle')
            ->orderBy('vehicle_id')
            ->get();

        if ($zoneTransports->isEmpty()) {
            return redirect()->route('admin.transports.create')
                ->with('error', 'This zone has no vehicle rows. Add pricing with the form below.');
        }

        return view('admin.transports.edit', compact('zone', 'zoneTransports', 'vehicles'));
    }

    public function update(Request $request, string $id)
    {
        $transport = Transport::findOrFail($id);
        if (!$transport->transport_zone_id) {
            return redirect()->route('admin.transports.index')
                ->with('error', 'This record has no zone.');
        }

        $zone = TransportZone::findOrFail($transport->transport_zone_id);
        $validated = $this->validateZoneBundleRequest($request, $zone);
        DB::transaction(function () use ($zone, $validated) {
            $this->persistZoneBundleUpdate($zone, $validated);
        });

        return redirect()->route('admin.transports.index')->with('success', 'Zone and pricing updated.');
    }

    public function destroy(string $id)
    {
        $transport = Transport::findOrFail($id);
        $zoneId = $transport->transport_zone_id;
        $transport->delete();

        if ($zoneId && Transport::where('transport_zone_id', $zoneId)->count() === 0) {
            TransportZone::query()->whereKey($zoneId)->delete();
        }

        return redirect()->route('admin.transports.index')->with('success', 'Pricing row removed.');
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
                'location' => 'nullable|string|max:255',
                'vehicle_id' => 'required|exists:vehicles,id',
                'price_per_km' => 'required|numeric|min:0',
                'min_charge' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'status' => 'required|in:active,inactive,pending',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            $match = [
                'location' => $payload['location'] ?? null,
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

        $query = Transport::with(['vehicle', 'zone']);
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

    /**
     * @return array{zone: array<string, mixed>, vehicles: list<array<string, mixed>>}
     */
    private function validateZoneBundleRequest(Request $request, ?TransportZone $zone): array
    {
        $transportIdRules = ['nullable', 'integer'];
        if ($zone) {
            $transportIdRules[] = Rule::exists('transports', 'id')->where('transport_zone_id', $zone->id);
        }

        $validated = $request->validate([
            'zone.name' => 'required|string|max:255',
            'zone.cities_text' => 'nullable|string',
            'zone.polygon_json' => 'nullable|string',
            'zone.price_per_day' => 'required|numeric|min:0',
            'zone.currency' => 'nullable|string|max:10',
            'zone.status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'zone.notes' => 'nullable|string',
            'zone.default_map_lat' => 'nullable|numeric|between:-90,90',
            'zone.default_map_lng' => 'nullable|numeric|between:-180,180',
            'vehicles' => 'required|array|min:1',
            'vehicles.*.vehicle_id' => 'required|exists:vehicles,id',
            'vehicles.*.price_per_km' => 'required|numeric|min:0',
            'vehicles.*.min_charge' => 'nullable|numeric|min:0',
            'vehicles.*.status' => ['nullable', Rule::in(['active', 'inactive', 'pending'])],
            'vehicles.*.notes' => 'nullable|string|max:500',
            'vehicles.*.id' => $transportIdRules,
        ]);

        $vehicleIds = array_map(fn ($r) => (int) ($r['vehicle_id'] ?? 0), $validated['vehicles']);
        if (count($vehicleIds) !== count(array_unique($vehicleIds))) {
            throw ValidationException::withMessages(['vehicles' => 'Each vehicle can only appear once.']);
        }

        foreach ($validated['vehicles'] as $k => $r) {
            if (array_key_exists('id', $r) && ($r['id'] === '' || $r['id'] === null)) {
                unset($validated['vehicles'][$k]['id']);
            }
        }
        $validated['vehicles'] = array_values($validated['vehicles']);

        $polygon = $this->parsePolygonJson($validated['zone']['polygon_json'] ?? null);
        $cities = $this->parseCitiesFromText($validated['zone']['cities_text'] ?? '');
        if ($cities === [] && $polygon) {
            $cities = NominatimGeocoder::suggestedLocalityNamesFromPolygon($polygon, 5);
        }
        if ($cities === []) {
            throw ValidationException::withMessages([
                'zone.cities_text' => 'Draw a zone on the map and use “Fill cities from zone”, or type at least one place name.',
            ]);
        }

        $validated['zone']['cities'] = $cities;
        $validated['zone']['polygon'] = $polygon;
        unset($validated['zone']['cities_text'], $validated['zone']['polygon_json']);

        return $validated;
    }

    /**
     * @param  array{zone: array<string, mixed>, vehicles: list<array<string, mixed>>}  $validated
     */
    private function persistZoneBundle(array $validated): void
    {
        $z = $validated['zone'];
        $zone = TransportZone::create([
            'name' => $z['name'],
            'cities' => $z['cities'],
            'polygon' => $z['polygon'],
            'default_map_lat' => $z['default_map_lat'] ?? null,
            'default_map_lng' => $z['default_map_lng'] ?? null,
            'currency' => $z['currency'] ?? 'EUR',
            'price_per_day' => $z['price_per_day'],
            'notes' => $z['notes'] ?? null,
            'status' => $z['status'],
        ]);

        foreach ($validated['vehicles'] as $row) {
            $vehicle = Vehicle::find((int) $row['vehicle_id']);
            Transport::create([
                'transport_zone_id' => $zone->id,
                'location' => $this->transportRowLabel($zone, $vehicle),
                'vehicle_id' => (int) $row['vehicle_id'],
                'price_per_km' => $row['price_per_km'],
                'min_charge' => $row['min_charge'] ?? null,
                'price_per_day' => null,
                'currency' => $z['currency'] ?? 'EUR',
                'notes' => $row['notes'] ?? null,
                'status' => $row['status'] ?? 'active',
            ]);
        }
    }

    /**
     * @param  array{zone: array<string, mixed>, vehicles: list<array<string, mixed>>}  $validated
     */
    private function persistZoneBundleUpdate(TransportZone $zone, array $validated): void
    {
        $z = $validated['zone'];
        $zone->update([
            'name' => $z['name'],
            'cities' => $z['cities'],
            'polygon' => $z['polygon'],
            'default_map_lat' => $z['default_map_lat'] ?? null,
            'default_map_lng' => $z['default_map_lng'] ?? null,
            'currency' => $z['currency'] ?? 'EUR',
            'price_per_day' => $z['price_per_day'],
            'notes' => $z['notes'] ?? null,
            'status' => $z['status'],
        ]);

        $keptIds = [];
        foreach ($validated['vehicles'] as $row) {
            $vehicle = Vehicle::find((int) $row['vehicle_id']);
            $payload = [
                'transport_zone_id' => $zone->id,
                'location' => $this->transportRowLabel($zone, $vehicle),
                'vehicle_id' => (int) $row['vehicle_id'],
                'price_per_km' => $row['price_per_km'],
                'min_charge' => $row['min_charge'] ?? null,
                'price_per_day' => null,
                'currency' => $z['currency'] ?? 'EUR',
                'notes' => $row['notes'] ?? null,
                'status' => $row['status'] ?? 'active',
            ];

            if (!empty($row['id'])) {
                $t = Transport::query()->where('transport_zone_id', $zone->id)->whereKey($row['id'])->first();
                if ($t) {
                    $t->update($payload);
                    $keptIds[] = $t->id;
                }
            } else {
                $t = Transport::create($payload);
                $keptIds[] = $t->id;
            }
        }

        Transport::query()->where('transport_zone_id', $zone->id)->whereNotIn('id', $keptIds)->delete();
    }

    private function transportRowLabel(TransportZone $zone, ?Vehicle $vehicle): string
    {
        $name = $vehicle ? $vehicle->name : '';

        return mb_substr($zone->name . ($name !== '' ? ' — ' . $name : ''), 0, 255);
    }

    /**
     * @return list<string>
     */
    private function parseCitiesFromText(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }
        $parts = preg_split('/[\r\n,]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($parts as $p) {
            $t = trim($p);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    private function parsePolygonJson(?string $json): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded) || empty($decoded['type'])) {
            return null;
        }
        if (!in_array($decoded['type'], ['Polygon', 'MultiPolygon'], true)) {
            return null;
        }

        return $decoded;
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
        $rows = [['transport_zone_id', 'zone_name', 'zone_price_per_day', 'location', 'vehicle_id', 'vehicle_name', 'price_per_km', 'min_charge', 'notes', 'status']];
        foreach ($transports as $t) {
            $rows[] = [
                $t->transport_zone_id,
                $t->zone ? $t->zone->name : '',
                $t->zone?->price_per_day,
                $t->location,
                $t->vehicle_id,
                $t->vehicle ? $t->vehicle->name : '',
                $t->price_per_km,
                $t->min_charge,
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
                'Delhi, India',
                $vehicleId,
                25.00,
                500,
                'One-way rate',
                'active',
            ],
        ];
    }
}
