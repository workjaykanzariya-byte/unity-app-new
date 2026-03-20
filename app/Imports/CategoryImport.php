<?php

namespace App\Imports;

use App\Models\Category;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['category_name'] ?? ''));

            if ($name === '') {
                continue;
            }

            Category::query()->updateOrCreate(
                ['category_name' => $name],
                [
                    'sector' => isset($row['sector']) ? trim((string) $row['sector']) : null,
                    'remarks' => isset($row['remarks']) ? trim((string) $row['remarks']) : null,
                ]
            );
        }
    }
}
