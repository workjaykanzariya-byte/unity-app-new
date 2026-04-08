<?php

namespace App\Imports;

use App\Models\CircleCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class CategoriesImport
{
    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    public function importHierarchy(array $nodes, ?int $parentId = null, int $level = 1): array
    {
        $inserted = 0;
        $updated = 0;

        foreach ($nodes as $index => $node) {
            $name = trim((string) ($node['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $attributes = ['name' => $name];
            $payload = [
                'remarks' => $this->nullableTrim($node['remarks'] ?? null),
            ];

            if (Schema::hasColumn('circle_categories', 'parent_id')) {
                $payload['parent_id'] = $parentId;
            }

            if (Schema::hasColumn('circle_categories', 'level')) {
                $payload['level'] = $level;
            }

            if (Schema::hasColumn('circle_categories', 'sort_order')) {
                $payload['sort_order'] = (int) ($node['sort_order'] ?? ($index + 1));
            }

            if (Schema::hasColumn('circle_categories', 'slug')) {
                $payload['slug'] = $this->nextUniqueValue(
                    'circle_categories',
                    'slug',
                    (string) ($node['slug'] ?? Str::slug($name))
                );
            }

            if (Schema::hasColumn('circle_categories', 'is_active')) {
                $payload['is_active'] = (bool) ($node['is_active'] ?? true);
            }

            if (Schema::hasColumn('circle_categories', 'circle_key')) {
                $payload['circle_key'] = $this->nextUniqueValue(
                    'circle_categories',
                    'circle_key',
                    (string) ($node['circle_key'] ?? Str::upper(Str::snake($name))),
                    '_'
                );
            }

            $category = CircleCategory::query()->updateOrCreate($attributes, $payload);

            if ($category->wasRecentlyCreated) {
                $inserted++;
            } else {
                $updated++;
            }

            if (! empty($node['children']) && is_array($node['children'])) {
                $childResult = $this->importHierarchy($node['children'], $category->id, min($level + 1, 4));
                $inserted += $childResult['inserted_count'];
                $updated += $childResult['updated_count'];
            }
        }

        return [
            'inserted_count' => $inserted,
            'updated_count' => $updated,
        ];
    }

    public function import(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->readCsvRows($file),
            'xlsx' => $this->readXlsxRows($file),
            default => throw new \RuntimeException('Unsupported file type. Please upload CSV or XLSX.'),
        };

        if ($rows === []) {
            return [
                'imported_count' => 0,
                'skipped_duplicate_count' => 0,
                'skipped_empty_count' => 0,
            ];
        }

        $existingNormalized = CircleCategory::query()
            ->pluck('name')
            ->mapWithKeys(fn ($name) => [$this->normalizeName((string) $name) => true])
            ->all();

        $seenInFile = [];
        $importedCount = 0;
        $skippedDuplicateCount = 0;
        $skippedEmptyCount = 0;

        foreach ($rows as $row) {
            $categoryNameRaw = $this->firstNonNull($row, ['category', 'category_name']);
            $categoryName = trim((string) ($categoryNameRaw ?? ''));

            if ($categoryName === '') {
                $skippedEmptyCount++;
                continue;
            }

            $normalizedName = $this->normalizeName($categoryName);
            if (isset($existingNormalized[$normalizedName]) || isset($seenInFile[$normalizedName])) {
                $skippedDuplicateCount++;
                continue;
            }

            $remarks = $this->firstNonNull($row, ['remarks']);

            $payload = [
                'name' => $categoryName,
                'remarks' => $this->nullableTrim($remarks),
            ];

            if (Schema::hasColumn('circle_categories', 'parent_id')) {
                $payload['parent_id'] = null;
            }

            if (Schema::hasColumn('circle_categories', 'level')) {
                $payload['level'] = 1;
            }

            if (Schema::hasColumn('circle_categories', 'is_active')) {
                $payload['is_active'] = true;
            }

            if (Schema::hasColumn('circle_categories', 'slug')) {
                $payload['slug'] = $this->nextUniqueValue('circle_categories', 'slug', Str::slug($categoryName));
            }

            if (Schema::hasColumn('circle_categories', 'circle_key')) {
                $payload['circle_key'] = $this->nextUniqueValue('circle_categories', 'circle_key', Str::upper(Str::snake($categoryName)), '_');
            }

            if (Schema::hasColumn('circle_categories', 'sort_order')) {
                $payload['sort_order'] = ((int) CircleCategory::query()->where('level', 1)->max('sort_order')) + 1;
            }

            CircleCategory::query()->create($payload);
            $importedCount++;

            $seenInFile[$normalizedName] = true;
            $existingNormalized[$normalizedName] = true;
        }

        return [
            'imported_count' => $importedCount,
            'skipped_duplicate_count' => $skippedDuplicateCount,
            'skipped_empty_count' => $skippedEmptyCount,
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readCsvRows(UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'r');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open CSV file.');
        }

        $headers = fgetcsv($stream);
        if (! is_array($headers)) {
            fclose($stream);
            return [];
        }

        $normalizedHeaders = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);

        $rows = [];

        while (($data = fgetcsv($stream)) !== false) {
            if ($data === [null] || $data === []) {
                $rows[] = [];
                continue;
            }

            $row = [];
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $data[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readXlsxRows(UploadedFile $file): array
    {
        $zip = new ZipArchive();
        $opened = $zip->open($file->getRealPath());

        if ($opened !== true) {
            throw new \RuntimeException('Unable to open XLSX file.');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new \RuntimeException('Unable to read worksheet from XLSX file.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $shared = @simplexml_load_string($sharedStringsXml);
            if ($shared !== false) {
                foreach ($shared->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                        continue;
                    }

                    $value = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $value .= (string) ($run->t ?? '');
                        }
                    }

                    $sharedStrings[] = $value;
                }
            }
        }

        $sheet = @simplexml_load_string($sheetXml);
        $zip->close();

        if ($sheet === false || ! isset($sheet->sheetData)) {
            return [];
        }

        $matrix = [];

        foreach ($sheet->sheetData->row as $rowNode) {
            $rowValues = [];

            foreach ($rowNode->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');
                $colIndex = $this->columnIndexFromCellRef($ref);

                $type = (string) ($cell['t'] ?? '');
                $value = null;

                if ($type === 's') {
                    $sharedIndex = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$sharedIndex] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($cell->is->t ?? '');
                } else {
                    $value = isset($cell->v) ? (string) $cell->v : '';
                }

                $rowValues[$colIndex] = $value;
            }

            if ($rowValues !== []) {
                ksort($rowValues);
                $matrix[] = $rowValues;
            }
        }

        if ($matrix === []) {
            return [];
        }

        $headerRow = array_shift($matrix);
        $headerMap = [];

        foreach ($headerRow as $index => $header) {
            $normalized = $this->normalizeHeader((string) $header);
            if ($normalized !== '') {
                $headerMap[$index] = $normalized;
            }
        }

        $rows = [];

        foreach ($matrix as $rowValues) {
            $row = [];
            foreach ($headerMap as $index => $header) {
                $row[$header] = $rowValues[$index] ?? null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function normalizeHeader(string $header): string
    {
        $header = str_replace("\xEF\xBB\xBF", '', $header);
        $header = strtolower(trim($header));
        $header = preg_replace('/\s+/', '_', $header) ?? '';

        return match ($header) {
            'category' => 'category',
            'category_name' => 'category_name',
            'sector' => 'sector',
            'remarks' => 'remarks',
            'parent_id' => 'parent_id',
            'level' => 'level',
            'slug' => 'slug',
            'sort_order' => 'sort_order',
            'is_active' => 'is_active',
            'id' => 'id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            default => $header,
        };
    }

    private function normalizeName(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    private function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));
        return $trimmed === '' ? null : $trimmed;
    }

    private function firstNonNull(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return $row[$key];
            }
        }

        return null;
    }

    private function columnIndexFromCellRef(string $ref): int
    {
        if ($ref === '') {
            return 0;
        }

        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref)) ?? 'A';
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function nextUniqueValue(string $table, string $column, string $baseValue, string $separator = '-'): string
    {
        $value = trim($baseValue);
        if ($value === '') {
            $value = 'category';
        }

        $candidate = $value;
        $suffix = 1;

        while (DB::table($table)->where($column, $candidate)->exists()) {
            $candidate = $value . $separator . $suffix;
            $suffix++;
        }

        return $candidate;
    }
}
