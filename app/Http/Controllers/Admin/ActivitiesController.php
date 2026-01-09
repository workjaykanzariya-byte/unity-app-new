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
use Illuminate\Support\Facades\Schema;
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

        $query = User::query()->select([
            'id',
            'email',
            'first_name',
            'last_name',
            'display_name',
            'membership_status',
        ]);

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

        $testimonialCounts = $this->countByUser(Testimonial::query()
            ->whereIn('from_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'from_user_id');

        $referralCounts = $this->countByUser(Referral::query()
            ->whereIn('from_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'from_user_id');

        $businessDealCounts = $this->countByUser(BusinessDeal::query()
            ->whereIn('from_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'from_user_id');

        $p2pMeetingCounts = $this->countByUser(P2pMeeting::query()
            ->whereIn('initiator_user_id', $memberIds)
            ->where('is_deleted', false)
            ->whereNull('deleted_at'),
            'initiator_user_id');

        $requirementCounts = $this->countByUser(Requirement::query()
            ->whereIn('user_id', $memberIds)
            ->whereNull('deleted_at'),
            'user_id');

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

    public function testimonials(User $member): View
    {
        $items = Testimonial::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-testimonials', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function referrals(User $member): View
    {
        $items = Referral::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-referrals', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function businessDeals(User $member): View
    {
        $items = BusinessDeal::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-business-deals', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function p2pMeetings(User $member): View
    {
        $items = P2pMeeting::query()
            ->with(['peer'])
            ->where('initiator_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->orderByDesc('meeting_date')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-p2p-meetings', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function requirements(User $member): View
    {
        $items = Requirement::query()
            ->with(['user'])
            ->where('user_id', $member->id)
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.activities.list-requirements', [
            'member' => $member,
            'items' => $items,
        ]);
    }

    public function export(ActivitiesExportRequest $request): StreamedResponse
    {
        $activityType = $request->string('activity_type')->toString();
        $scope = $request->string('scope')->toString();
        $search = trim((string) $request->input('q', ''));
        $membership = $request->input('membership_status');

        $activityMap = [
            'testimonials' => Testimonial::class,
            'referrals' => Referral::class,
            'business_deals' => BusinessDeal::class,
            'p2p_meetings' => P2pMeeting::class,
            'requirements' => Requirement::class,
        ];

        $modelClass = $activityMap[$activityType] ?? null;
        abort_if(! $modelClass, 404);

        $table = (new $modelClass())->getTable();
        $columns = Schema::getColumnListing($table);
        $memberKey = $this->resolveMemberKey($columns);

        $query = DB::table($table . ' as activity')
            ->leftJoin('users', 'users.id', '=', 'activity.' . $memberKey)
            ->leftJoin('cities', 'cities.id', '=', 'users.city_id')
            ->select([
                'activity.*',
                DB::raw('users.id as member_id'),
                DB::raw('COALESCE(users.display_name, CONCAT(users.first_name, \' \', users.last_name)) as member_display_name'),
                DB::raw('users.email as member_email'),
                DB::raw('users.phone as member_phone'),
                DB::raw('users.company_name as member_company_name'),
                DB::raw('COALESCE(cities.name, users.city) as member_city_name'),
                DB::raw('users.membership_status as member_membership_status'),
            ]);

        if ($scope === 'selected') {
            $memberIds = $request->input('selected_member_ids', []);
            $query->whereIn('users.id', $memberIds);
        } else {
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $like = "%{$search}%";
                    $q->where('users.display_name', 'ILIKE', $like)
                        ->orWhere('users.first_name', 'ILIKE', $like)
                        ->orWhere('users.last_name', 'ILIKE', $like)
                        ->orWhere('users.email', 'ILIKE', $like);
                });
            }

            if ($membership && $membership !== 'all') {
                $query->where('users.membership_status', $membership);
            }
        }

        $headers = array_merge([
            'member_id',
            'member_display_name',
            'member_email',
            'member_phone',
            'member_company_name',
            'member_city_name',
            'member_membership_status',
        ], $columns, ['attachment_url']);

        $filename = 'activity_' . $activityType . '_' . now()->format('Ymd_His') . '.csv';

        $response = response()->streamDownload(function () use ($query, $columns) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, array_merge([
                'member_id',
                'member_display_name',
                'member_email',
                'member_phone',
                'member_company_name',
                'member_city_name',
                'member_membership_status',
            ], $columns, ['attachment_url']));

            $chunkCallback = function ($rows) use ($handle, $columns) {
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $attachmentUrl = $this->resolveAttachmentUrl($rowArray, $columns);

                    $data = [
                        $rowArray['member_id'] ?? null,
                        $rowArray['member_display_name'] ?? null,
                        $rowArray['member_email'] ?? null,
                        $rowArray['member_phone'] ?? null,
                        $rowArray['member_company_name'] ?? null,
                        $rowArray['member_city_name'] ?? null,
                        $rowArray['member_membership_status'] ?? null,
                    ];

                    foreach ($columns as $column) {
                        $data[] = $rowArray[$column] ?? null;
                    }

                    $data[] = $attachmentUrl;
                    fputcsv($handle, $data);
                }
            };

            if (in_array('id', $columns, true)) {
                $query->orderBy('activity.id')->chunkById(500, $chunkCallback, 'activity.id');
            } else {
                $query->orderBy('activity.created_at')->chunk(500, $chunkCallback);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);

        return $response;
    }

    private function countByUser($query, string $column): array
    {
        return $query
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)
            ->pluck('total', $column)
            ->all();
    }

    private function resolveMemberKey(array $columns): string
    {
        foreach (['member_id', 'user_id', 'created_by', 'from_user_id', 'initiator_user_id'] as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return 'user_id';
    }

    private function resolveAttachmentUrl(array $row, array $columns): ?string
    {
        foreach (['file_id', 'image_id', 'attachment_file_id', 'media_file_id', 'photo_file_id'] as $column) {
            if (in_array($column, $columns, true)) {
                return $this->formatAttachmentUrl($row[$column] ?? null);
            }
        }

        foreach (['attachments', 'media'] as $column) {
            if (! in_array($column, $columns, true)) {
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
