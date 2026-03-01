<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ActivitiesP2PMeetingsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $baseQuery = $this->baseQuery($request, $filters);
        $total = (clone $baseQuery)->count();

        $items = $baseQuery
            ->select([
                'activity.id',
                'activity.meeting_date',
                'activity.meeting_place',
                'activity.remarks',
                'activity.created_at',
                'actor.display_name as actor_display_name',
                'actor.first_name as actor_first_name',
                'actor.last_name as actor_last_name',
                'actor.email as actor_email',
                DB::raw("coalesce(nullif(trim(concat_ws(' ', actor.first_name, actor.last_name)), ''), actor.display_name, '—') as from_user_name"),
                DB::raw("coalesce(actor.company_name, '') as from_company"),
                DB::raw("coalesce(actor.city, '') as from_city"),
                'peer.display_name as peer_display_name',
                'peer.first_name as peer_first_name',
                'peer.last_name as peer_last_name',
                'peer.email as peer_email',
                DB::raw("coalesce(nullif(trim(concat_ws(' ', peer.first_name, peer.last_name)), ''), peer.display_name, '—') as to_user_name"),
                DB::raw("coalesce(peer.company_name, '') as to_company"),
                DB::raw("coalesce(peer.city, '') as to_city"),
            ])
            ->orderByDesc('activity.meeting_date')
            ->orderByDesc('activity.created_at')
            ->paginate(20)
            ->withQueryString();

        $topMembers = $this->topMembers($request);

        return view('admin.activities.p2p_meetings.index', [
            'items' => $items,
            'filters' => $filters,
            'topMembers' => $topMembers,
            'total' => $total,
            'circles' => $this->circleOptions(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'p2p_meetings_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($request, $filters) {
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
                    'Created By Name',
                    'Created By Email',
                    'Connected With Name',
                    'Connected With Email',
                    'Meeting Date',
                    'Meeting Place',
                    'Remarks',
                    'Media Count',
                    'Media URLs',
                    'Media JSON',
                    'Created At',
                ]);

                $this->baseQuery($request, $filters)
                    ->select([
                        'activity.id',
                        'activity.meeting_date',
                        'activity.meeting_place',
                        'activity.remarks',
                        'activity.created_at',
                        'actor.display_name as actor_display_name',
                        'actor.first_name as actor_first_name',
                        'actor.last_name as actor_last_name',
                        'actor.email as actor_email',
                        'peer.display_name as peer_display_name',
                        'peer.first_name as peer_first_name',
                        'peer.last_name as peer_last_name',
                        'peer.email as peer_email',
                        DB::raw('NULL as media'),
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
                            $peerName = $this->formatUserName(
                                $row->peer_display_name,
                                $row->peer_first_name,
                                $row->peer_last_name
                            );

                            fputcsv($handle, [
                                $row->id,
                                $actorName,
                                $row->actor_email ?? '',
                                $peerName,
                                $row->peer_email ?? '',
                                $row->meeting_date ?? '',
                                $row->meeting_place ?? '',
                                $row->remarks ?? '',
                                $this->mediaCount($row->media ?? null),
                                $this->mediaUrls($row->media ?? null),
                                $this->mediaJson($row->media ?? null),
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
        $from = $request->query('from');
        $to = $request->query('to');

        return [
            'q' => trim((string) $request->query('q', $request->query('search', ''))),
            'from' => $from,
            'to' => $to,
            'from_at' => $this->parseDayBoundary($from, false),
            'to_at' => $this->parseDayBoundary($to, true),
            'circle_id' => $request->query('circle_id'),
        ];
    }

    private function baseQuery(Request $request, array $filters)
    {
        $query = DB::table('p2p_meetings as activity')
            ->leftJoin('users as actor', 'actor.id', '=', 'activity.initiator_user_id')
            ->leftJoin('users as peer', 'peer.id', '=', 'activity.peer_user_id')
            ->whereNull('activity.deleted_at')
            ->where('activity.is_deleted', false);

        if ($filters['q'] !== '') {
            $query->leftJoin('cities as actor_city', 'actor_city.id', '=', 'actor.city_id');
            $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
            $query->where(function ($q) use ($like) {
                $q->where('actor.display_name', 'ILIKE', $like)
                    ->orWhere('actor.first_name', 'ILIKE', $like)
                    ->orWhere('actor.last_name', 'ILIKE', $like)
                    ->orWhere('actor.company_name', 'ILIKE', $like)
                    ->orWhere('actor.city', 'ILIKE', $like)
                    ->orWhere('actor_city.name', 'ILIKE', $like)
                    ->orWhereExists(function ($sub) use ($like) {
                        $sub->selectRaw('1')
                            ->from('circle_members as cm_search')
                            ->join('circles as c_search', 'c_search.id', '=', 'cm_search.circle_id')
                            ->whereColumn('cm_search.user_id', 'actor.id')
                            ->where('c_search.name', 'ILIKE', $like);
                    });
            });
        }

        if ($filters['from_at']) {
            $query->where('activity.created_at', '>=', $filters['from_at']);
        }

        if ($filters['to_at']) {
            $query->where('activity.created_at', '<=', $filters['to_at']);
        }

        if (! empty($filters['circle_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('circle_members as cm_filter')
                    ->whereColumn('cm_filter.user_id', 'actor.id')
                    ->where('cm_filter.circle_id', $filters['circle_id']);
            });
        }

        $this->applyScopeToActivityQuery($query, 'activity.initiator_user_id', 'activity.peer_user_id');

        return $query;
    }

    private function topMembers(Request $request)
    {
        $filters = $this->filters($request);

        $query = DB::table('p2p_meetings as activity')
            ->join('users as actor', 'actor.id', '=', 'activity.initiator_user_id')
            ->whereNull('activity.deleted_at')
            ->where('activity.is_deleted', false);

        if (! empty($filters['circle_id'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->selectRaw('1')
                    ->from('circle_members as cm_filter')
                    ->whereColumn('cm_filter.user_id', 'actor.id')
                    ->where('cm_filter.circle_id', $filters['circle_id']);
            });
        }

        $this->applyScopeToActivityQuery($query, 'activity.initiator_user_id', 'activity.peer_user_id');

        return $query
            ->groupBy(
                'activity.initiator_user_id',
                'actor.display_name',
                'actor.first_name',
                'actor.last_name',
                'actor.email',
                'actor.company_name',
                'actor.city'
            )
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(5)
            ->select([
                'activity.initiator_user_id as actor_id',
                'actor.display_name',
                'actor.first_name',
                'actor.last_name',
                'actor.email',
                'actor.company_name',
                'actor.city',
                DB::raw("coalesce(nullif(trim(concat_ws(' ', actor.first_name, actor.last_name)), ''), actor.display_name, '—') as peer_name"),
                DB::raw("coalesce(actor.company_name, '') as peer_company"),
                DB::raw("coalesce(actor.city, '') as peer_city"),
                DB::raw('count(*) as total_count'),
            ])
            ->get();
    }

    private function circleOptions()
    {
        return DB::table('circles')
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    private function parseDayBoundary($value, bool $endOfDay): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            $parsed = Carbon::parse($value);

            return $endOfDay ? $parsed->endOfDay() : $parsed->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function applyScopeToActivityQuery($query, string $primaryColumn, ?string $peerColumn): void
    {
        AdminCircleScope::applyToActivityQuery($query, auth('admin')->user(), $primaryColumn, $peerColumn);
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
        return count($this->normalizeMedia($media));
    }

    private function mediaUrls($media): string
    {
        $urls = [];

        foreach ($this->normalizeMedia($media) as $item) {
            $url = $this->resolveMediaUrl($item);
            if ($url) {
                $urls[] = $url;
            }
        }

        return implode(',', $urls);
    }

    private function mediaJson($media): string
    {
        $normalized = $this->normalizeMedia($media);

        return $normalized ? json_encode($normalized) : '';
    }

    private function normalizeMedia($media): array
    {
        if (! $media) {
            return [];
        }

        if (is_string($media)) {
            $decoded = json_decode($media, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return [$media];
        }

        if (is_array($media)) {
            return $media;
        }

        return [$media];
    }

    private function resolveMediaUrl($item): ?string
    {
        if (is_array($item)) {
            $url = $item['url'] ?? null;
            $id = $item['id'] ?? null;

            if ($url) {
                return $url;
            }

            if ($id && Str::isUuid($id)) {
                return url('/api/v1/files/' . $id);
            }

            return $id ?: null;
        }

        if (is_string($item)) {
            if (str_starts_with($item, 'http://') || str_starts_with($item, 'https://')) {
                return $item;
            }

            if (Str::isUuid($item)) {
                return url('/api/v1/files/' . $item);
            }

            return $item;
        }

        return null;
    }
}
