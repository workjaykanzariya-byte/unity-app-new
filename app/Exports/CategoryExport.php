<?php

namespace App\Exports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CategoryExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Category::query()
            ->select(['category_name', 'sector', 'remarks'])
            ->orderBy('category_name')
            ->get();
    }

    public function headings(): array
    {
        return ['category_name', 'sector', 'remarks'];
    }
}
