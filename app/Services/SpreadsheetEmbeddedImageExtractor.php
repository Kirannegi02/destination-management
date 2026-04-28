<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;
use ZipArchive;

/**
 * Extracts pictures embedded in Excel cells (XLSX / XLS) for bulk import.
 * CSV cannot carry embedded images; use .xlsx and place images in the images column.
 *
 * Resolves the "images" column from the workbook's first row (reliable column letters).
 * Excel 365 "picture in cell" images are stored in-cell; also reads zip media when
 * file_get_contents('zip://...') fails on Windows.
 */
class SpreadsheetEmbeddedImageExtractor
{
    private const IMAGE_HEADER_KEYS = ['images', 'image', 'image_url', 'image_urls', 'photo', 'photos'];

    /**
     * @param  list<string>|null  $normalizedHeaders  Fallback only if row 1 cannot be read; prefer null and resolve from file
     * @return array<int, list<string>> Map: 1-based Excel row number => list of temp disk paths (caller must unlink)
     */
    public function extractImagesByRow(string $filePath, ?array $normalizedHeaders = null): array
    {
        if (! class_exists(IOFactory::class) || ! is_readable($filePath)) {
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (Throwable) {
            return [];
        }

        $sheet = $spreadsheet->getActiveSheet();

        $columnIndexes = $this->resolveImageColumnIndexesFromWorksheet($sheet);
        if ($columnIndexes === [] && $normalizedHeaders !== null) {
            $columnIndexes = $this->imageColumnIndexesFromHeaderArray($normalizedHeaders);
        }
        if ($columnIndexes === []) {
            return [];
        }

        $seenHashes = [];
        $byRow = [];

        $collect = function (BaseDrawing $drawing) use (&$byRow, &$seenHashes, $columnIndexes): void {
            $coords = $drawing->getCoordinates();
            if ($coords === '') {
                return;
            }
            try {
                [$colIndex, $row] = Coordinate::indexesFromString($coords);
            } catch (Throwable) {
                return;
            }
            if (! in_array($colIndex, $columnIndexes, true)) {
                return;
            }
            $hash = $drawing->getHashCode();
            if (isset($seenHashes[$hash])) {
                return;
            }
            $seenHashes[$hash] = true;
            $tmp = $this->drawingToTempFile($drawing, $filePath);
            if ($tmp === null) {
                return;
            }
            $byRow[$row][] = $tmp;
        };

        foreach ($sheet->getDrawingCollection() as $d) {
            if ($d instanceof BaseDrawing) {
                $collect($d);
            }
        }
        foreach ($sheet->getInCellDrawingCollection() as $d) {
            if ($d instanceof BaseDrawing) {
                $collect($d);
            }
        }

        $this->collectDrawingsFromImageCells($sheet, $columnIndexes, $byRow, $seenHashes, $filePath);

        return $byRow;
    }

    /**
     * @return list<int> 1-based column indexes (A=1)
     */
    private function resolveImageColumnIndexesFromWorksheet(Worksheet $sheet): array
    {
        $indexes = [];
        try {
            $highestColumn = $sheet->getHighestColumn(1);
            $maxCol = Coordinate::columnIndexFromString($highestColumn);
        } catch (Throwable) {
            return [];
        }

        for ($col = 1; $col <= $maxCol; $col++) {
            $addr = Coordinate::stringFromColumnIndex($col).'1';
            $cell = $sheet->getCell($addr);
            $raw = $cell->getValue();
            if ($raw === null || $raw === '') {
                continue;
            }
            if (! is_string($raw) && ! is_numeric($raw)) {
                continue;
            }
            $normalized = $this->normalizeHeaderToken((string) $raw);
            if (in_array($normalized, self::IMAGE_HEADER_KEYS, true)) {
                $indexes[] = $col;
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * Must match {@see \App\Http\Controllers\Admin\Concerns\ImportsSpreadsheet::normalizeHeaders()} per cell.
     */
    private function normalizeHeaderToken(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $normalized);
        $normalized = str_replace([' ', '-'], '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized);

        return $normalized;
    }

    /**
     * @param  list<string>  $normalizedHeaders
     * @return list<int>
     */
    private function imageColumnIndexesFromHeaderArray(array $normalizedHeaders): array
    {
        $indexes = [];
        foreach ($normalizedHeaders as $i => $h) {
            if (in_array($h, self::IMAGE_HEADER_KEYS, true)) {
                $indexes[] = $i + 1;
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * Pictures placed "in cell" (Excel 365) may be stored as the cell value only.
     *
     * @param  array<int, list<string>>  $byRow
     * @param  array<string, bool>  $seenHashes
     */
    private function collectDrawingsFromImageCells(
        Worksheet $sheet,
        array $columnIndexes,
        array &$byRow,
        array &$seenHashes,
        string $sourceFilePath
    ): void {
        $maxRow = (int) $sheet->getHighestRow();
        if ($maxRow < 2) {
            return;
        }

        for ($row = 2; $row <= $maxRow; $row++) {
            foreach ($columnIndexes as $colIndex) {
                $addr = Coordinate::stringFromColumnIndex($colIndex).$row;
                try {
                    $cell = $sheet->getCell($addr);
                } catch (Throwable) {
                    continue;
                }
                if ($cell->getDataType() !== DataType::TYPE_DRAWING_IN_CELL) {
                    continue;
                }
                $value = $cell->getValue();
                if (! $value instanceof BaseDrawing) {
                    continue;
                }
                $hash = $value->getHashCode();
                if (isset($seenHashes[$hash])) {
                    continue;
                }
                $seenHashes[$hash] = true;
                $tmp = $this->drawingToTempFile($value, $sourceFilePath);
                if ($tmp === null) {
                    continue;
                }
                $byRow[$row][] = $tmp;
            }
        }
    }

    private function drawingToTempFile(BaseDrawing $drawing, string $sourceFilePath): ?string
    {
        if ($drawing instanceof MemoryDrawing) {
            return $this->memoryDrawingToTempFile($drawing);
        }
        if ($drawing instanceof Drawing) {
            return $this->fileDrawingToTempFile($drawing, $sourceFilePath);
        }

        return null;
    }

    private function memoryDrawingToTempFile(MemoryDrawing $drawing): ?string
    {
        $resource = $drawing->getImageResource();
        if ($resource === null) {
            return null;
        }

        $ext = match ($drawing->getMimeType()) {
            MemoryDrawing::MIMETYPE_JPEG => 'jpg',
            MemoryDrawing::MIMETYPE_GIF => 'gif',
            MemoryDrawing::MIMETYPE_PNG => 'png',
            default => 'png',
        };

        $path = tempnam(sys_get_temp_dir(), 'embimg_');
        if ($path === false) {
            return null;
        }
        unlink($path);
        $path .= '.'.$ext;

        $callback = $drawing->getRenderingFunction();
        $ok = @$callback($resource, $path);
        if ($ok === false && ! is_file($path)) {
            return null;
        }

        return is_file($path) && filesize($path) > 0 ? $path : null;
    }

    private function fileDrawingToTempFile(Drawing $drawing, string $sourceFilePath): ?string
    {
        $src = $drawing->getPath();
        if ($src === '' || str_starts_with($src, 'data:')) {
            return null;
        }

        $contents = $this->readDrawingBinary($src, $sourceFilePath);
        if ($contents === false || $contents === '') {
            return null;
        }

        $ext = $drawing->getExtension();
        if ($ext === '' || strlen($ext) > 12) {
            $ext = 'png';
        }

        $path = tempnam(sys_get_temp_dir(), 'embimg_');
        if ($path === false) {
            return null;
        }
        unlink($path);
        $path .= '.'.$ext;

        if (file_put_contents($path, $contents) === false) {
            return null;
        }

        return is_file($path) && filesize($path) > 0 ? $path : null;
    }

    /**
     * @return string|false
     */
    private function readDrawingBinary(string $src, string $sourceXlsxPath)
    {
        $data = @file_get_contents($src);
        if ($data !== false && $data !== '') {
            return $data;
        }

        if (str_starts_with($src, 'zip://')) {
            $parsed = $this->parseZipStreamUrl($src);
            if ($parsed !== null) {
                [$zipDiskPath, $internalPath] = $parsed;
                $zip = new ZipArchive;
                if ($zip->open($zipDiskPath) === true) {
                    $data = $zip->getFromName($internalPath);
                    $zip->close();
                    if ($data !== false && $data !== '') {
                        return $data;
                    }
                }
            }
        }

        $zipDisk = $sourceXlsxPath;
        if (is_readable($zipDisk) && str_contains($src, 'xl/media/')) {
            if (preg_match('#xl/media/[^#\'"]+#', $src, $m)) {
                $internal = $m[0];
                $zip = new ZipArchive;
                if ($zip->open($zipDisk) === true) {
                    $data = $zip->getFromName($internal);
                    $zip->close();
                    if ($data !== false && $data !== '') {
                        return $data;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return array{0: string, 1: string}|null  [ disk path, path inside zip ]
     */
    private function parseZipStreamUrl(string $src): ?array
    {
        if (! str_starts_with($src, 'zip://')) {
            return null;
        }
        $rest = substr($src, 6);
        $pos = strpos($rest, '#');
        if ($pos === false) {
            return null;
        }
        $diskPath = substr($rest, 0, $pos);
        $internalPath = substr($rest, $pos + 1);

        return [$diskPath, $internalPath];
    }
}
