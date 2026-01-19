<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ActivitiesRequirementsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $items = $this->baseQuery($filters)
            ->select([
                'activity.id',
                'activity.subject',
                'activity.description',
                'activity.media',
                'activity.region_filter',
                'activity.category_filter',
                'activity.status',
                'activity.created_at',
                'actor.display_name as actor_display_name',
                'actor.first_name as actor_first_name',
                'actor.last_name as actor_last_name',
                'actor.email as actor_email',
            ])
            ->orderByDesc('activity.created_at')
            ->paginate(20)
            ->withQueryString();

        $topMembers = $this->topMembers();

        return view('admin.activities.requirements.index', [
            'items' => $items,
            'filters' => $filters,
            'topMembers' => $topMembers,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'requirements_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $handle = fopen('php://output', 'w');

            try {
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, [
                    'ID',
                    'Actor Name',
                    'Actor Email',
                    'Subject',
                    'Description',
                    'Region Filter',
                    'Category Filter',
                    'Status',
                    'Media Count',
                    'Created At',
                ]);

                $this->baseQuery($filters)
                    ->select([
                        'activity.id',
                        'activity.subject',
                        'activity.description',
                        'activity.media',
                        'activity.region_filter',
                        'activity.category_filter',
                        'activity.status',
                        'activity.created_at',
                        'actor.display_name as actor_display_name',
                        'actor.first_name as actor_first_name',
                        'actor.last_name as actor_last_name',
                        'actor.email as actor_email',
                    ])
                    ->orderBy('activity.created_at')
                    ->orderBy('activity.id')
                    ->chunk(500, function ($rows) use ($handle) {
                        foreach ($rows as $row) {
                            $actorName = $this->formatUserName(
                                $row->actor_display_name,
                                $row->actor_first_name,
                                $row->actor_last_name
                            );

                            fputcsv($handle, [
                                $row->id,
                                $actorName,
                                $row->actor_email ?? '',
                                $row->subject ?? '',
                                $row->description ?? '',
                                $row->region_filter ?? '',
                                $row->category_filter ?? '',
                                $row->status ?? '',
                                $this->mediaCount($row->media ?? null),
                                $row->created_at ?? '',
                            ]);
                        }
                    });
            } finally {
                fclose($handle);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'status' => $request->query('status'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('requirements as activity')
            ->leftJoin('users as actor', 'actor.id', '=', 'activity.user_id')
            ->whereNull('activity.deleted_at');

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('actor.display_name', 'ILIKE', $like)
                    ->orWhere('actor.first_name', 'ILIKE', $like)
                    ->orWhere('actor.last_name', 'ILIKE', $like)
                    ->orWhere('actor.email', 'ILIKE', $like);
            });
        }

        if ($filters['status']) {
            $query->where('activity.status', $filters['status']);
        }

        if ($filters['from']) {
            $query->whereDate('activity.created_at', '>=', $filters['from']);
        }

        if ($filters['to']) {
            $query->whereDate('activity.created_at', '<=', $filters['to']);
        }

        return $query;
    }

    private function topMembers()
    {
        return DB::table('requirements as activity')
            ->join('users as actor', 'actor.id', '=', 'activity.user_id')
            ->whereNull('activity.deleted_at')
            ->groupBy(
                'activity.user_id',
                'actor.display_name',
                'actor.first_name',
                'actor.last_name',
                'actor.email'
            )
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(3)
            ->select([
                'activity.user_id as actor_id',
                'actor.display_name',
                'actor.first_name',
                'actor.last_name',
                'actor.email',
                DB::raw('count(*) as total_count'),
            ])
            ->get();
    }

    private function formatUserName(?string $displayName, ?string $firstName, ?string $lastName): string
    {
        if ($displayName) {
            return $displayName;
        }

        $name = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

        return $name !== '' ? $name : '—';
    }

    private function mediaCount($media): int
    {
        if (! $media) {
            return 0;
        }

        $decoded = is_string($media) ? json_decode($media, true) : $media;

        if (is_array($decoded)) {
            return count($decoded);
        }

        return 1;
    }
}
