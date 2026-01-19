<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ActivitiesReferralsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $items = $this->baseQuery($filters)
            ->select([
                'activity.id',
                'activity.referral_type',
                'activity.referral_date',
                'activity.referral_of',
                'activity.phone',
                'activity.email',
                'activity.address',
                'activity.hot_value',
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
            ])
            ->orderByDesc('activity.created_at')
            ->paginate(20)
            ->withQueryString();

        $topMembers = $this->topMembers();

        return view('admin.activities.referrals.index', [
            'items' => $items,
            'filters' => $filters,
            'topMembers' => $topMembers,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'referrals_' . now()->format('Ymd_His') . '.csv';

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
                    'Created By Name',
                    'Created By Email',
                    'Related Peer Name',
                    'Related Peer Email',
                    'Referral Type',
                    'Referral Date',
                    'Referral Of',
                    'Phone',
                    'Email',
                    'Address',
                    'Hot Value',
                    'Remarks',
                    'Media Count',
                    'Media URLs',
                    'Media JSON',
                    'Created At',
                ]);

                $this->baseQuery($filters)
                    ->select([
                        'activity.id',
                        'activity.referral_type',
                        'activity.referral_date',
                        'activity.referral_of',
                        'activity.phone',
                        'activity.email',
                        'activity.address',
                        'activity.hot_value',
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
                                $row->referral_type ?? '',
                                $row->referral_date ?? '',
                                $row->referral_of ?? '',
                                $row->phone ?? '',
                                $row->email ?? '',
                                $row->address ?? '',
                                $row->hot_value ?? '',
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
        return [
            'search' => trim((string) $request->query('search', '')),
            'referral_type' => $request->query('referral_type'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('referrals as activity')
            ->leftJoin('users as actor', 'actor.id', '=', 'activity.from_user_id')
            ->leftJoin('users as peer', 'peer.id', '=', 'activity.to_user_id')
            ->whereNull('activity.deleted_at')
            ->where('activity.is_deleted', false);

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('actor.display_name', 'ILIKE', $like)
                    ->orWhere('actor.first_name', 'ILIKE', $like)
                    ->orWhere('actor.last_name', 'ILIKE', $like)
                    ->orWhere('actor.email', 'ILIKE', $like);
            });
        }

        if ($filters['referral_type']) {
            $query->where('activity.referral_type', $filters['referral_type']);
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
        return DB::table('referrals as activity')
            ->join('users as actor', 'actor.id', '=', 'activity.from_user_id')
            ->whereNull('activity.deleted_at')
            ->where('activity.is_deleted', false)
            ->groupBy(
                'activity.from_user_id',
                'actor.display_name',
                'actor.first_name',
                'actor.last_name',
                'actor.email'
            )
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(3)
            ->select([
                'activity.from_user_id as actor_id',
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
