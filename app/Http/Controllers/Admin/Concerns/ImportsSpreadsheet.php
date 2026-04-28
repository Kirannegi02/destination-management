<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use SimpleXMLElement;
use Throwable;
use ZipArchive;

trait ImportsSpreadsheet
{
    /**
     * @param  array<string, mixed>  $context
     */
    protected function logSpreadsheetImport(string $message, array $context = []): void
    {
        Log::info('[ImportsSpreadsheet] '.$message, $context);
    }

    protected function parseUploadRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();
        if (! is_string($path) || $path === '') {
            $path = $file->getPathname();
        }

        $ioFactoryAvailable = class_exists(IOFactory::class);

        $this->logSpreadsheetImport('parseUploadRows: start', [
            'original_name' => $file->getClientOriginalName(),
            'extension' => $extension,
            'client_mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'path_readable' => is_string($path) && $path !== '' && is_readable($path),
            'php_spreadsheet_available' => $ioFactoryAvailable,
        ]);

        if (! is_string($path) || $path === '' || ! is_readable($path)) {
            $this->logSpreadsheetImport('parseUploadRows: abort — path missing or not readable', [
                'path' => $path,
            ]);

            return [];
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            $rows = $this->parseCsv($path);
            $this->logSpreadsheetImport('parseUploadRows: csv branch', ['row_count' => count($rows)]);

            return $rows;
        }

        // .xlsx: ZIP/XML parser first; PhpSpreadsheet handles odd exports or non-sheet1 layouts.
        if ($extension === 'xlsx') {
            $xlsxRows = $this->parseXlsx($path);
            $this->logSpreadsheetImport('parseUploadRows: xlsx native attempt', ['row_count' => count($xlsxRows)]);
            if (! empty($xlsxRows)) {
                return $xlsxRows;
            }

            $rows = $this->parseWithPhpSpreadsheet($path, 'xlsx-fallback');
            $this->logSpreadsheetImport('parseUploadRows: xlsx PhpSpreadsheet fallback', ['row_count' => count($rows)]);

            return $rows;
        }

        // .xls: our download is HTML disguised as .xls; Excel "Save As .xls" is binary (BIFF) — needs PhpSpreadsheet.
        if ($extension === 'xls') {
            $htmlRows = $this->parseHtmlTable($path);
            $this->logSpreadsheetImport('parseUploadRows: xls html-table attempt', ['row_count' => count($htmlRows)]);
            if (! empty($htmlRows)) {
                return $htmlRows;
            }

            $rows = $this->parseWithPhpSpreadsheet($path, 'xls-binary');
            $this->logSpreadsheetImport('parseUploadRows: xls PhpSpreadsheet', ['row_count' => count($rows)]);

            return $rows;
        }

        $xlsxRows = $this->parseXlsx($path);
        $this->logSpreadsheetImport('parseUploadRows: generic xlsx zip attempt', ['row_count' => count($xlsxRows)]);
        if (! empty($xlsxRows)) {
            return $xlsxRows;
        }

        $htmlRows = $this->parseHtmlTable($path);
        $this->logSpreadsheetImport('parseUploadRows: generic html attempt', ['row_count' => count($htmlRows)]);
        if (! empty($htmlRows)) {
            return $htmlRows;
        }

        $rows = $this->parseWithPhpSpreadsheet($path, 'generic-fallback');
        $this->logSpreadsheetImport('parseUploadRows: generic PhpSpreadsheet', ['row_count' => count($rows)]);

        return $rows;
    }

    /**
     * Read .xls (binary), .xlsx, ODS, etc. via PhpSpreadsheet when lightweight parsers fail.
     */
    protected function parseWithPhpSpreadsheet(string $path, string $reason = 'unknown'): array
    {
        if (! class_exists(IOFactory::class)) {
            $this->logSpreadsheetImport('PhpSpreadsheet: IOFactory missing (composer install phpoffice/phpspreadsheet?)', [
                'reason' => $reason,
            ]);

            return [];
        }

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $raw = $sheet->toArray(null, true, true, false);
            $rows = [];
            foreach ($raw as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $rows[] = array_map(function ($cell) {
                    if ($cell === null) {
                        return '';
                    }

                    return trim((string) $cell);
                }, $row);
            }

            $this->logSpreadsheetImport('PhpSpreadsheet: success', [
                'reason' => $reason,
                'row_count' => count($rows),
                'sheet_title' => $sheet->getTitle(),
            ]);

            return $rows;
        } catch (Throwable $e) {
            $this->logSpreadsheetImport('PhpSpreadsheet: failed', [
                'reason' => $reason,
                'exception' => $e->getMessage(),
                'exception_class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [];
        }
    }

    protected function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->logSpreadsheetImport('parseCsv: fopen failed', ['path' => $path]);

            return [];
        }
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if (! empty($data)) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
            }
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Read first worksheet only (backward compatible with older callers).
     */
    protected function parseXlsx(string $path): array
    {
        return $this->parseXlsxWorksheet($path, 'xl/worksheets/sheet1.xml');
    }

    /**
     * List worksheet XML entries inside an .xlsx (sheet1.xml, sheet2.xml, …) in order.
     *
     * @return list<string>
     */
    protected function listXlsxWorksheetEntries(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^xl/worksheets/sheet(\d+)\.xml$#', (string) $name, $m)) {
                $entries[(int) $m[1]] = $name;
            }
        }
        $zip->close();
        ksort($entries, SORT_NUMERIC);

        return array_values($entries);
    }

    /**
     * Parse one worksheet from an .xlsx file by zip entry path (e.g. xl/worksheets/sheet2.xml).
     *
     * Uses regex on the raw XML strings — completely bypasses DOM/SimpleXML/XPath so it works on
     * every PHP/libxml build, including Hostinger's, where namespaced OOXML breaks XML traversal.
     */
    protected function parseXlsxWorksheet(string $path, string $worksheetEntry): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            $this->logSpreadsheetImport('parseXlsxWorksheet: ZipArchive::open failed', ['path' => $path]);

            return [];
        }

        // --- Shared strings (regex) ---
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            // Match every <si>…</si> block, collect all <t> text inside each one.
            preg_match_all('/<(?:[^:>\s]+:)?si\b[^>]*>(.*?)<\/(?:[^:>\s]+:)?si>/s', $sharedXml, $siMatches);
            foreach ($siMatches[1] as $siContent) {
                preg_match_all('/<(?:[^:>\s]+:)?t\b[^>]*>(.*?)<\/(?:[^:>\s]+:)?t>/s', $siContent, $tMatches);
                $text = implode('', $tMatches[1] ?? []);
                $sharedStrings[] = html_entity_decode($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            }
        }

        // --- Worksheet XML ---
        $sheetXml = $zip->getFromName($worksheetEntry);
        $zip->close();

        if ($sheetXml === false || $sheetXml === '') {
            $this->logSpreadsheetImport('parseXlsxWorksheet: worksheet missing or empty', [
                'path' => $path,
                'entry' => $worksheetEntry,
            ]);

            return [];
        }

        // Extract the <sheetData> block
        if (! preg_match('/<(?:[^:>\s]+:)?sheetData\b[^>]*>(.*?)<\/(?:[^:>\s]+:)?sheetData>/s', $sheetXml, $sdMatch)) {
            $this->logSpreadsheetImport('parseXlsxWorksheet: no sheetData block', [
                'path' => $path,
                'entry' => $worksheetEntry,
            ]);

            return [];
        }
        $sheetDataContent = $sdMatch[1];

        // Extract all <row> blocks
        if (! preg_match_all('/<(?:[^:>\s]+:)?row\b[^>]*>(.*?)<\/(?:[^:>\s]+:)?row>/s', $sheetDataContent, $rowMatches)) {
            $this->logSpreadsheetImport('parseXlsxWorksheet: no row blocks in sheetData', [
                'path' => $path,
                'entry' => $worksheetEntry,
            ]);

            return [];
        }

        // Excel omits empty cells; use column ref (e.g. "A1") to build dense rows.
        $sparseRows = [];
        $maxCol = 0;

        foreach ($rowMatches[1] as $rowContent) {
            $sparse = [];
            $nextColIndex = 1;

            // Each cell: <c r="A1" t="s"><v>0</v></c> or <c r="A1"><v>1.5</v></c>
            preg_match_all('/<(?:[^:>\s]+:)?c\b([^>]*)>(.*?)<\/(?:[^:>\s]+:)?c>/s', $rowContent, $cellMatches, PREG_SET_ORDER);

            foreach ($cellMatches as $cell) {
                $attrs   = $cell[1];
                $content = $cell[2];

                // Column reference attribute r="A1"
                $ref = '';
                if (preg_match('/\br="([^"]+)"/', $attrs, $rm)) {
                    $ref = $rm[1];
                }

                if ($ref === '') {
                    $colIndex = $nextColIndex;
                    $nextColIndex++;
                } elseif (preg_match('/^([A-Za-z]+)\d+$/', $ref, $cm)) {
                    $colIndex = $this->xlsxColLettersToIndex($cm[1]);
                    $nextColIndex = $colIndex + 1;
                } else {
                    continue;
                }

                $maxCol = max($maxCol, $colIndex);
                $sparse[$colIndex] = $this->xlsxCellValueFromRaw($attrs, $content, $sharedStrings);
            }

            $sparseRows[] = $sparse;
        }

        if ($maxCol === 0) {
            $this->logSpreadsheetImport('parseXlsxWorksheet: maxCol is 0 after regex parse', [
                'path' => $path,
                'entry' => $worksheetEntry,
                'rows_found' => count($sparseRows),
                'sheet_xml_length' => strlen($sheetXml),
                'sheet_data_length' => strlen($sheetDataContent),
            ]);
        }

        $rows = [];
        foreach ($sparseRows as $sparse) {
            $dense = [];
            for ($i = 1; $i <= $maxCol; $i++) {
                $dense[] = $sparse[$i] ?? '';
            }
            $rows[] = $dense;
        }

        return $rows;
    }

    /**
     * Convert column letters (A, B, … Z, AA, AB, …) to a 1-based column index.
     */
    protected function xlsxColLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return $index;
    }

    /**
     * Extract the display value from a raw OOXML <c> element's attributes + inner XML.
     *
     * @param  list<string>  $sharedStrings
     */
    protected function xlsxCellValueFromRaw(string $attrs, string $content, array $sharedStrings): string
    {
        // Cell type from t="" attribute
        $t = '';
        if (preg_match('/\bt="([^"]+)"/', $attrs, $tm)) {
            $t = $tm[1];
        }

        // Inline string: <is><t>text</t></is>
        if ($t === 'inlineStr') {
            preg_match_all('/<(?:[^:>\s]+:)?t\b[^>]*>(.*?)<\/(?:[^:>\s]+:)?t>/s', $content, $tm);
            $text = implode('', $tm[1] ?? []);

            return trim(html_entity_decode($text, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }

        // Raw value from <v>…</v>
        $v = '';
        if (preg_match('/<(?:[^:>\s]+:)?v\b[^>]*>(.*?)<\/(?:[^:>\s]+:)?v>/s', $content, $vm)) {
            $v = $vm[1];
        }

        if ($v === '') {
            return '';
        }

        // Shared string index
        if ($t === 's') {
            return trim($sharedStrings[(int) $v] ?? '');
        }

        // Boolean
        if ($t === 'b') {
            return $v === '1' ? 'TRUE' : 'FALSE';
        }

        return trim(html_entity_decode($v, ENT_XML1 | ENT_QUOTES, 'UTF-8'));
    }

    /**
     * @deprecated  Kept for backward compatibility; use xlsxCellValueFromRaw for new code.
     *
     * @param  list<string>  $sharedStrings
     */
    protected function xlsxCellDisplayString(SimpleXMLElement $c, array $sharedStrings): string
    {
        $t = isset($c['t']) ? (string) $c['t'] : '';
        $vNodes = $c->xpath('./*[local-name()="v"]');
        $vEl = ($vNodes !== false && isset($vNodes[0])) ? $vNodes[0] : null;
        $isNodes = $c->xpath('./*[local-name()="is"]');
        $isEl = ($isNodes !== false && isset($isNodes[0])) ? $isNodes[0] : null;

        if ($t === 'inlineStr' && $isEl !== null) {
            $text = '';
            foreach ($isEl->xpath('.//*[local-name()="t"]') as $tNode) {
                $text .= (string) $tNode;
            }

            return trim($text);
        }

        if ($t === 's') {
            $index = $vEl !== null ? (int) (string) $vEl : 0;

            return trim($sharedStrings[$index] ?? '');
        }

        if ($t === 'b') {
            return ($vEl !== null && (string) $vEl === '1') ? 'TRUE' : 'FALSE';
        }

        if ($vEl === null) {
            return '';
        }

        return trim((string) $vEl);
    }

    protected function parseHtmlTable(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || $content === '') {
            $this->logSpreadsheetImport('parseHtmlTable: empty or unreadable file', [
                'path' => $path,
                'content_false' => $content === false,
            ]);

            return [];
        }

        $snippet = substr($content, 0, 64);
        $this->logSpreadsheetImport('parseHtmlTable: loaded', [
            'byte_length' => strlen($content),
            'looks_like_html' => stripos($content, '<table') !== false || stripos($content, '<html') !== false,
            'snippet_hex' => bin2hex($snippet),
        ]);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($content);
        $xmlErrors = libxml_get_errors();
        libxml_clear_errors();

        if ($xmlErrors !== []) {
            $first = $xmlErrors[0];
            $this->logSpreadsheetImport('parseHtmlTable: libxml warnings', [
                'count' => count($xmlErrors),
                'first' => isset($first->message) ? trim((string) $first->message) : null,
            ]);
        }

        $rows = [];
        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $row = [];
            foreach ($tr->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                $name = $child->nodeName;
                if ($name === 'td' || $name === 'th') {
                    $row[] = trim($child->textContent);
                }
            }
            if (! empty($row)) {
                $rows[] = $row;
            }
        }

        $this->logSpreadsheetImport('parseHtmlTable: parsed <tr> rows', [
            'row_count' => count($rows),
        ]);

        return $rows;
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $header = is_string($header) ? $header : (string) $header;
            // Strip UTF-8 BOM / BOM so first column matches (e.g. "restaurant_name")
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            $header = preg_replace('/^\x{FEFF}/u', '', $header);
            $normalized = strtolower(trim($header));
            // Excel often uses NBSP (U+00A0) between words; str_replace(' ', ...) does not catch it
            $normalized = preg_replace('/\s+/u', ' ', $normalized);

            $stripped = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $normalized);
            if ($stripped === null || ($stripped === '' && $normalized !== '')) {
                // Some hosts mis-handle \p{L}; ASCII fallback keeps restaurant_name, address, etc.
                $stripped = preg_replace('/[^a-z0-9\s_-]/i', '', $normalized) ?? '';
            }
            $normalized = $stripped;
            $normalized = str_replace([' ', '-'], '_', $normalized);
            $normalized = preg_replace('/_+/', '_', $normalized);
            $normalized = trim($normalized, '_');

            return $normalized;
        }, $headers);
    }

    protected function mapRowToAssoc(array $headers, array $row): array
    {
        $assoc = [];
        foreach ($headers as $index => $header) {
            $assoc[$header] = $row[$index] ?? null;
        }

        return $assoc;
    }

    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $key => $value) {
            if ($key === '_row_number') {
                continue;
            }
            if (! is_null($value) && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function normalizeImportPhone($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : trim((string) $value);
    }

    protected function normalizeImportFormat(?string $format): string
    {
        $format = strtolower($format ?? 'xls');

        return in_array($format, ['csv', 'xls', 'xlsx'], true) ? $format : 'xls';
    }

    /**
     * Stream CSV to php://output with UTF-8 BOM (Excel-friendly) and row width aligned to the header row.
     */
    protected function writeCsvRowsForDownload(array $rows): void
    {
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }

        fprintf($out, "\xEF\xBB\xBF");
        $headCount = isset($rows[0]) && is_array($rows[0]) ? count($rows[0]) : 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $row = [$row];
            }
            if ($headCount > 0) {
                $row = array_slice($row, 0, $headCount);
                if (count($row) < $headCount) {
                    $row = array_pad($row, $headCount, '');
                }
            }
            fputcsv($out, $row);
        }

        fclose($out);
    }

    protected function generateHtmlExcel(array $rows): string
    {
        if ($rows === []) {
            return '<table border="1"></table>';
        }

        $headCount = count($rows[0]);

        $html = '<table border="1"><thead><tr>';
        foreach ($rows[0] as $heading) {
            $html .= '<td><strong>'.htmlspecialchars((string) $heading).'</strong></td>';
        }
        $html .= '</tr></thead><tbody>';

        foreach (array_slice($rows, 1) as $row) {
            if (! is_array($row)) {
                $row = [$row];
            }
            $row = array_slice($row, 0, $headCount);
            if (count($row) < $headCount) {
                $row = array_pad($row, $headCount, '');
            }
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>'.htmlspecialchars((string) $cell).'</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }
}
