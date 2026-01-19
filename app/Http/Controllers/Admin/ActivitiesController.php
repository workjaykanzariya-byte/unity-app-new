<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivitiesExportRequest;
use App\Models\BusinessDeal;
use App\Models\P2pMeeting;
use App\Models\Referral;
use App\Models\Requirement;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class ActivitiesController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $membership = $request->query('membership_status');
        $perPage = $request->integer('per_page') ?: 20;
        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;
        $allowedCircleIds = $request->attributes->get('allowed_circle_ids');
        $isCircleScoped = (bool) $request->attributes->get('is_circle_scoped');

        $query = User::query()->select([
            'id',
            'email',
            'first_name',
            'last_name',
            'display_name',
            'membership_status',
        ]);

        $this->applyCircleScopeToUsersQuery($query, $request);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";
                $q->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like);
            });
        }

        if ($membership && $membership !== 'all') {
            $query->where('membership_status', $membership);
        }

        $members = $query->orderBy('display_name')->paginate($perPage)->withQueryString();

        $memberIds = $members->pluck('id')->all();

        if ($memberIds === []) {
            $testimonialCounts = [];
            $referralCounts = [];
            $businessDealCounts = [];
            $p2pMeetingCounts = [];
            $requirementCounts = [];
        } else {
            $testimonialQuery = Testimonial::query()
                ->whereIn('from_user_id', $memberIds)
                ->where('is_deleted', false)
                ->whereNull('deleted_at');
            $this->applyCircleScopeToActivityQuery($testimonialQuery, 'from_user_id', $allowedCircleIds, $isCircleScoped);
            $testimonialCounts = $this->countByUser($testimonialQuery, 'from_user_id');

            $referralQuery = Referral::query()
                ->whereIn('from_user_id', $memberIds)
                ->where('is_deleted', false)
                ->whereNull('deleted_at');
            $this->applyCircleScopeToActivityQuery($referralQuery, 'from_user_id', $allowedCircleIds, $isCircleScoped);
            $referralCounts = $this->countByUser($referralQuery, 'from_user_id');

            $businessDealQuery = BusinessDeal::query()
                ->whereIn('from_user_id', $memberIds)
                ->where('is_deleted', false)
                ->whereNull('deleted_at');
            $this->applyCircleScopeToActivityQuery($businessDealQuery, 'from_user_id', $allowedCircleIds, $isCircleScoped);
            $businessDealCounts = $this->countByUser($businessDealQuery, 'from_user_id');

            $p2pMeetingQuery = P2pMeeting::query()
                ->whereIn('initiator_user_id', $memberIds)
                ->where('is_deleted', false)
                ->whereNull('deleted_at');
            $this->applyCircleScopeToActivityQuery($p2pMeetingQuery, 'initiator_user_id', $allowedCircleIds, $isCircleScoped);
            $p2pMeetingCounts = $this->countByUser($p2pMeetingQuery, 'initiator_user_id');

            $requirementQuery = Requirement::query()
                ->whereIn('user_id', $memberIds)
                ->whereNull('deleted_at');
            $this->applyCircleScopeToActivityQuery($requirementQuery, 'user_id', $allowedCircleIds, $isCircleScoped);
            $requirementCounts = $this->countByUser($requirementQuery, 'user_id');
        }

        $membershipStatuses = User::query()
            ->whereNotNull('membership_status')
            ->distinct()
            ->pluck('membership_status')
            ->sort()
            ->values();

        return view('admin.activities.index', [
            'members' => $members,
            'filters' => [
                'search' => $search,
                'membership_status' => $membership,
                'per_page' => $perPage,
            ],
            'membershipStatuses' => $membershipStatuses,
            'counts' => [
                'testimonials' => $testimonialCounts,
                'referrals' => $referralCounts,
                'business_deals' => $businessDealCounts,
                'p2p_meetings' => $p2pMeetingCounts,
                'requirements' => $requirementCounts,
            ],
        ]);
    }

    public function testimonials(User $member, Request $request): View
    {
        $this->ensureMemberInScope($member, $request);
        $filters = $this->dateFilters($request);

        $items = Testimonial::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->when($filters['from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.list-testimonials', [
            'member' => $member,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function referrals(User $member, Request $request): View
    {
        $this->ensureMemberInScope($member, $request);
        $filters = $this->dateFilters($request);

        $items = Referral::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->when($filters['from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.list-referrals', [
            'member' => $member,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function businessDeals(User $member, Request $request): View
    {
        $this->ensureMemberInScope($member, $request);
        $filters = $this->dateFilters($request);

        $items = BusinessDeal::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->when($filters['from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.list-business-deals', [
            'member' => $member,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function p2pMeetings(User $member, Request $request): View
    {
        $this->ensureMemberInScope($member, $request);
        $filters = $this->dateFilters($request);

        $items = P2pMeeting::query()
            ->with(['peer'])
            ->where('initiator_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->when($filters['from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('meeting_date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.list-p2p-meetings', [
            'member' => $member,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function requirements(User $member, Request $request): View
    {
        $this->ensureMemberInScope($member, $request);
        $filters = $this->dateFilters($request);

        $items = Requirement::query()
            ->with(['user'])
            ->where('user_id', $member->id)
            ->whereNull('deleted_at')
            ->when($filters['from'], fn ($query) => $query->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn ($query) => $query->whereDate('created_at', '<=', $filters['to']))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.list-requirements', [
            'member' => $member,
            'items' => $items,
            'filters' => $filters,
        ]);
    }

    public function export(ActivitiesExportRequest $request): StreamedResponse
    {
        $validated = $request->validated();
        $activityType = $validated['activity_type'];
        $filename = 'activity_' . $activityType . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($request, $activityType) {
            @ini_set('zlib.output_compression', '0');
            @ini_set('output_buffering', '0');
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $handle = fopen('php://output', 'w');

            try {
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, $this->buildHeaderRow($activityType));
                $this->streamRowsAsCsv($handle, $activityType, $request);
            } catch (\Throwable $e) {
                logger()->error('Activities export failed', [
                    'activity_type' => $activityType,
                    'error' => $e->getMessage(),
                ]);
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

    private function countByUser($query, string $column): array
    {
        return $query
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->pluck('total', $column)
            ->all();
    }

    private function dateFilters(Request $request): array
    {
        return [
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }

    private function buildHeaderRow(string $activityType): array
    {
        return collect($this->exportColumns($activityType))
            ->pluck('label')
            ->all();
    }

    private function streamRowsAsCsv($handle, string $activityType, ActivitiesExportRequest $request): void
    {
        $query = $this->buildExportQuery($activityType, $request, $request->validated());
        $columns = $this->exportColumns($activityType);

        $chunkCallback = function ($rows) use ($handle, $columns) {
            $memberMap = $this->buildMemberMap($rows);

            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $data = [];

                foreach ($columns as $column) {
                    $data[] = $this->resolveExportValue($column['key'], $rowArray, $memberMap);
                }

                fputcsv($handle, $data);
            }
        };

        $query->orderByDesc('activity.created_at')->chunk(500, $chunkCallback);
    }

    private function buildExportQuery(string $activityType, ActivitiesExportRequest $request, array $filters)
    {
        $memberKey = $this->resolveMemberKey($activityType);

        $query = DB::table($this->activityTable($activityType) . ' as activity')
            ->leftJoin('users as member_user', 'member_user.id', '=', 'activity.' . $memberKey)
            ->leftJoin('users as related_user', 'related_user.id', '=', $this->relatedUserJoinColumn($activityType))
            ->select($this->exportSelectColumns($activityType));

        $allowedCircleIds = $request->attributes->get('allowed_circle_ids');
        $isCircleScoped = (bool) $request->attributes->get('is_circle_scoped');

        if ($isCircleScoped && is_array($allowedCircleIds)) {
            if ($allowedCircleIds === []) {
                $query->whereRaw('1=0');
            } else {
                $query->whereExists(function ($subQuery) use ($allowedCircleIds) {
                    $subQuery->selectRaw(1)
                        ->from('circle_members as cm')
                        ->whereColumn('cm.user_id', 'member_user.id')
                        ->where('cm.status', 'approved')
                        ->whereNull('cm.deleted_at')
                        ->whereIn('cm.circle_id', $allowedCircleIds);
                });
            }
        }

        if (($filters['scope'] ?? null) === 'selected') {
            $memberIds = $filters['selected_member_ids'] ?? [];
            $query->whereIn('member_user.id', $memberIds);
        } else {
            $search = trim((string) ($filters['q'] ?? ''));
            $membership = $filters['membership_status'] ?? null;

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $like = "%{$search}%";
                    $q->where('member_user.display_name', 'ILIKE', $like)
                        ->orWhere('member_user.first_name', 'ILIKE', $like)
                        ->orWhere('member_user.last_name', 'ILIKE', $like)
                        ->orWhere('member_user.email', 'ILIKE', $like);
                });
            }

            if ($membership && $membership !== 'all') {
                $query->where('member_user.membership_status', $membership);
            }
        }

        return $query;
    }

    private function applyCircleScopeToUsersQuery($query, Request $request): void
    {
        $allowedCircleIds = $request->attributes->get('allowed_circle_ids');
        $isCircleScoped = (bool) $request->attributes->get('is_circle_scoped');

        if (! $isCircleScoped || ! is_array($allowedCircleIds)) {
            return;
        }

        if ($allowedCircleIds === []) {
            $query->whereRaw('1=0');
            return;
        }

        $query->whereExists(function ($subQuery) use ($allowedCircleIds) {
            $subQuery->selectRaw(1)
                ->from('circle_members as cm')
                ->whereColumn('cm.user_id', 'users.id')
                ->where('cm.status', 'approved')
                ->whereNull('cm.deleted_at')
                ->whereIn('cm.circle_id', $allowedCircleIds);
        });
    }

    private function ensureMemberInScope(User $member, Request $request): void
    {
        $allowedCircleIds = $request->attributes->get('allowed_circle_ids');
        $isCircleScoped = (bool) $request->attributes->get('is_circle_scoped');

        if (! $isCircleScoped || ! is_array($allowedCircleIds)) {
            return;
        }

        if ($allowedCircleIds === []) {
            abort(403);
        }

        $isMemberInScope = DB::table('circle_members')
            ->where('user_id', $member->id)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->whereIn('circle_id', $allowedCircleIds)
            ->exists();

        if (! $isMemberInScope) {
            abort(403);
        }
    }

    private function applyCircleScopeToActivityQuery($query, string $userColumn, $allowedCircleIds, bool $isCircleScoped): void
    {
        if (! $isCircleScoped || ! is_array($allowedCircleIds)) {
            return;
        }

        if ($allowedCircleIds === []) {
            $query->whereRaw('1=0');
            return;
        }

        $qualifiedColumn = $query->getModel()->getTable() . '.' . $userColumn;

        $query->whereExists(function ($subQuery) use ($qualifiedColumn, $allowedCircleIds) {
            $subQuery->selectRaw(1)
                ->from('circle_members as cm')
                ->whereColumn('cm.user_id', $qualifiedColumn)
                ->where('cm.status', 'approved')
                ->whereNull('cm.deleted_at')
                ->whereIn('cm.circle_id', $allowedCircleIds);
        });
    }

    private function exportColumns(string $activityType): array
    {
        return match ($activityType) {
            'requirements' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'member_name', 'label' => 'User Name'],
                ['key' => 'member_email', 'label' => 'User Email'],
                ['key' => 'subject', 'label' => 'Subject'],
                ['key' => 'description', 'label' => 'Description'],
                ['key' => 'region', 'label' => 'Region'],
                ['key' => 'category', 'label' => 'Category'],
                ['key' => 'status', 'label' => 'Status'],
                ['key' => 'attachment_url', 'label' => 'Attachment URL'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ],
            'testimonials' => [
                ['key' => 'member_name', 'label' => 'From Member Name'],
                ['key' => 'member_email', 'label' => 'From Member Email'],
                ['key' => 'related_name', 'label' => 'To Member Name'],
                ['key' => 'related_email', 'label' => 'To Member Email'],
                ['key' => 'content', 'label' => 'Content'],
                ['key' => 'attachment_url', 'label' => 'Attachment URL'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ],
            'referrals' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'related_name', 'label' => 'Referred Member Name'],
                ['key' => 'related_email', 'label' => 'Referred Member Email'],
                ['key' => 'referral_date', 'label' => 'Referral Date'],
                ['key' => 'referral_of', 'label' => 'Referral Of'],
                ['key' => 'referral_type', 'label' => 'Type'],
                ['key' => 'phone', 'label' => 'Phone'],
                ['key' => 'email', 'label' => 'Email'],
                ['key' => 'address', 'label' => 'Address'],
                ['key' => 'hot_value', 'label' => 'Hot Value'],
                ['key' => 'remarks', 'label' => 'Remarks'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ],
            'business_deals' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'related_name', 'label' => 'Deal With Name'],
                ['key' => 'related_email', 'label' => 'Deal With Email'],
                ['key' => 'deal_date', 'label' => 'Deal Date'],
                ['key' => 'deal_amount', 'label' => 'Amount'],
                ['key' => 'business_type', 'label' => 'Type'],
                ['key' => 'comment', 'label' => 'Comment'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ],
            'p2p_meetings' => [
                ['key' => 'id', 'label' => 'ID'],
                ['key' => 'related_name', 'label' => 'Peer Name'],
                ['key' => 'related_email', 'label' => 'Peer Email'],
                ['key' => 'meeting_date', 'label' => 'Meeting Date'],
                ['key' => 'meeting_place', 'label' => 'Meeting Place'],
                ['key' => 'remarks', 'label' => 'Remarks'],
                ['key' => 'created_at', 'label' => 'Created At'],
            ],
            default => [],
        };
    }

    private function resolveExportValue(string $key, array $row, array $memberMap)
    {
        return match ($key) {
            'member_name' => $this->resolveMemberName($row['member_id'] ?? null, $memberMap),
            'member_email' => $this->resolveMemberEmail($row['member_id'] ?? null, $memberMap),
            'related_name' => $this->resolveMemberName($row['related_member_id'] ?? null, $memberMap),
            'related_email' => $this->resolveMemberEmail($row['related_member_id'] ?? null, $memberMap),
            'region' => $this->resolveRegion($row['region_filter'] ?? null),
            'category' => $this->resolveCategory($row['category_filter'] ?? null),
            'attachment_url' => $this->resolveAttachmentUrl($row),
            default => $row[$key] ?? null,
        };
    }

    private function resolveRegion($value): ?string
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($decoded)) {
            return null;
        }

        $regionLabel = $decoded['region_label'] ?? null;
        $cityName = $decoded['city_name'] ?? null;
        $region = trim(($regionLabel ?? '') . ($cityName ? ', ' . $cityName : ''));

        return $region !== '' ? $region : null;
    }

    private function resolveCategory($value): ?string
    {
        $decoded = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($decoded)) {
            return null;
        }

        return $decoded['category'] ?? null;
    }

    private function resolveMemberKey(string $activityType): string
    {
        return match ($activityType) {
            'testimonials' => 'from_user_id',
            'referrals' => 'from_user_id',
            'business_deals' => 'from_user_id',
            'p2p_meetings' => 'initiator_user_id',
            'requirements' => 'user_id',
            default => 'user_id',
        };
    }

    private function activityTable(string $activityType): string
    {
        $modelClass = match ($activityType) {
            'testimonials' => Testimonial::class,
            'referrals' => Referral::class,
            'business_deals' => BusinessDeal::class,
            'p2p_meetings' => P2pMeeting::class,
            'requirements' => Requirement::class,
            default => Requirement::class,
        };

        return (new $modelClass())->getTable();
    }

    private function relatedUserJoinColumn(string $activityType): string
    {
        return match ($activityType) {
            'testimonials' => 'activity.to_user_id',
            'referrals' => 'activity.to_user_id',
            'business_deals' => 'activity.to_user_id',
            'p2p_meetings' => 'activity.peer_user_id',
            default => 'activity.to_user_id',
        };
    }

    private function exportSelectColumns(string $activityType): array
    {
        return match ($activityType) {
            'requirements' => [
                'activity.id',
                'activity.subject',
                'activity.description',
                'activity.region_filter',
                'activity.category_filter',
                'activity.status',
                'activity.media',
                'activity.created_at',
                DB::raw('member_user.id as member_id'),
            ],
            'testimonials' => [
                'activity.content',
                'activity.media',
                'activity.created_at',
                DB::raw('member_user.id as member_id'),
                DB::raw('related_user.id as related_member_id'),
            ],
            'referrals' => [
                'activity.id',
                'activity.referral_date',
                'activity.referral_of',
                'activity.referral_type',
                'activity.phone',
                'activity.email',
                'activity.address',
                'activity.hot_value',
                'activity.remarks',
                'activity.created_at',
                DB::raw('related_user.id as related_member_id'),
            ],
            'business_deals' => [
                'activity.id',
                'activity.deal_date',
                'activity.deal_amount',
                'activity.business_type',
                'activity.comment',
                'activity.created_at',
                DB::raw('related_user.id as related_member_id'),
            ],
            'p2p_meetings' => [
                'activity.id',
                'activity.meeting_date',
                'activity.meeting_place',
                'activity.remarks',
                'activity.created_at',
                DB::raw('related_user.id as related_member_id'),
            ],
            default => [],
        };
    }

    private function buildMemberMap(iterable $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $rowArray = (array) $row;
            if (! empty($rowArray['member_id'])) {
                $ids[] = $rowArray['member_id'];
            }
            if (! empty($rowArray['related_member_id'])) {
                $ids[] = $rowArray['related_member_id'];
            }
        }

        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            return [];
        }

        return DB::table('users')
            ->whereIn('id', $ids)
            ->get(['id', 'display_name', 'first_name', 'last_name', 'email'])
            ->mapWithKeys(function ($user) {
                $name = $user->display_name;
                if (! $name) {
                    $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                }

                $name = $name ?: $user->email;

                return [
                    $user->id => [
                        'name' => $name,
                        'email' => $user->email,
                    ],
                ];
            })
            ->all();
    }

    private function resolveMemberName(?string $memberId, array $memberMap): ?string
    {
        if (! $memberId) {
            return null;
        }

        return $memberMap[$memberId]['name'] ?? null;
    }

    private function resolveMemberEmail(?string $memberId, array $memberMap): ?string
    {
        if (! $memberId) {
            return null;
        }

        return $memberMap[$memberId]['email'] ?? null;
    }

    private function resolveAttachmentUrl(array $row): ?string
    {
        foreach (['file_id', 'image_id', 'attachment_file_id', 'media_file_id', 'photo_file_id'] as $column) {
            if (array_key_exists($column, $row)) {
                return $this->formatAttachmentUrl($row[$column] ?? null);
            }
        }

        foreach (['attachments', 'media'] as $column) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            $media = $row[$column] ?? null;
            if (! $media) {
                continue;
            }

            $decoded = is_string($media) ? json_decode($media, true) : $media;
            if (is_array($decoded)) {
                $first = $decoded[0] ?? null;
                if (is_array($first)) {
                    return $this->formatAttachmentUrl($first['url'] ?? $first['id'] ?? null);
                }
            }

            return $this->formatAttachmentUrl($decoded);
        }

        return null;
    }

    private function formatAttachmentUrl($value): ?string
    {
        if (! $value) {
            return null;
        }

        if (is_string($value) && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'))) {
            return $value;
        }

        if (is_string($value) && Str::isUuid($value)) {
            return url('/api/v1/files/' . $value);
        }

        return null;
    }
}
