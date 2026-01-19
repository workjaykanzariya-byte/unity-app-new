<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ActivitiesP2PMeetingsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $items = $this->baseQuery($filters)
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
            ])
            ->orderByDesc('activity.meeting_date')
            ->orderByDesc('activity.created_at')
            ->paginate(20)
            ->withQueryString();

        $topMembers = $this->topMembers();

        return view('admin.activities.p2p_meetings.index', [
            'items' => $items,
            'filters' => $filters,
            'topMembers' => $topMembers,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'p2p_meetings_' . now()->format('Ymd_His') . '.csv';

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
                    'Peer Name',
                    'Peer Email',
                    'Meeting Date',
                    'Meeting Place',
                    'Remarks',
                    'Created At',
                ]);

                $this->baseQuery($filters)
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
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }

    private function baseQuery(array $filters)
    {
        $query = DB::table('p2p_meetings as activity')
            ->leftJoin('users as actor', 'actor.id', '=', 'activity.initiator_user_id')
            ->leftJoin('users as peer', 'peer.id', '=', 'activity.peer_user_id')
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
        return DB::table('p2p_meetings as activity')
            ->join('users as actor', 'actor.id', '=', 'activity.initiator_user_id')
            ->whereNull('activity.deleted_at')
            ->where('activity.is_deleted', false)
            ->groupBy(
                'activity.initiator_user_id',
                'actor.display_name',
                'actor.first_name',
                'actor.last_name',
                'actor.email'
            )
            ->orderByDesc(DB::raw('count(*)'))
            ->limit(3)
            ->select([
                'activity.initiator_user_id as actor_id',
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
}
