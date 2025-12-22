<?php

namespace App\Support\ActivityHistory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class HistoryPaginator
{
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
