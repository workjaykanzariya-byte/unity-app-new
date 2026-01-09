<?php

namespace App\Http\Controllers\Admin;

use App\Events\ActivityCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemberActivityRequest;
use App\Models\BusinessDeal;
use App\Models\P2pMeeting;
use App\Models\Referral;
use App\Models\Requirement;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\Post;
use App\Services\Coins\CoinsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class MemberActivityController extends Controller
{
    private const TYPE_CONFIG = [
        'p2p-meetings' => [
            'label' => 'P2P Meetings',
            'singular' => 'P2P Meeting',
            'activity_type' => 'p2p_meeting',
            'route' => 'admin.members.activities.p2p-meetings',
            'searchable' => ['meeting_place', 'remarks'],
        ],
        'referrals' => [
            'label' => 'Referrals',
            'singular' => 'Referral',
            'activity_type' => 'referral',
            'route' => 'admin.members.activities.referrals',
            'searchable' => ['referral_of', 'remarks', 'email', 'phone', 'address'],
        ],
        'business-deals' => [
            'label' => 'Business Deals',
            'singular' => 'Business Deal',
            'activity_type' => 'business_deal',
            'route' => 'admin.members.activities.business-deals',
            'searchable' => ['business_type', 'comment'],
        ],
        'requirements' => [
            'label' => 'Requirements',
            'singular' => 'Requirement',
            'activity_type' => 'requirement',
            'route' => 'admin.members.activities.requirements',
            'searchable' => ['subject', 'description'],
        ],
        'testimonials' => [
            'label' => 'Testimonials',
            'singular' => 'Testimonial',
            'activity_type' => 'testimonial',
            'route' => 'admin.members.activities.testimonials',
            'searchable' => ['content'],
        ],
    ];

    public function index(User $member): RedirectResponse
    {
        return redirect()->route('admin.members.details', $member);
    }

    public function p2pMeetingsIndex(User $member, Request $request): View
    {
        $config = $this->activityConfig('p2p-meetings');
        $filters = $this->extractFilters($request);

        $query = P2pMeeting::query()
            ->with(['peer'])
            ->where('initiator_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        $this->applyDateFilter($query, $filters, 'meeting_date');
        $this->applySearchFilter($query, $filters, $config['searchable'], function ($q, $search) {
            $q->orWhereHas('peer', function ($peerQuery) use ($search) {
                $like = "%{$search}%";
                $peerQuery->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like);
            });
        });

        $meetings = $query
            ->orderByDesc('meeting_date')
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.members.activities.index-p2p-meetings', [
            'member' => $member,
            'items' => $meetings,
            'filters' => $filters,
            'config' => $config,
        ]);
    }

    public function referralsIndex(User $member, Request $request): View
    {
        $config = $this->activityConfig('referrals');
        $filters = $this->extractFilters($request);

        $query = Referral::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        $this->applyDateFilter($query, $filters, 'referral_date');
        $this->applySearchFilter($query, $filters, $config['searchable'], function ($q, $search) {
            $q->orWhereHas('toUser', function ($toUserQuery) use ($search) {
                $like = "%{$search}%";
                $toUserQuery->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like);
            });
        });

        $referrals = $query
            ->orderByDesc('referral_date')
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.members.activities.index-referrals', [
            'member' => $member,
            'items' => $referrals,
            'filters' => $filters,
            'config' => $config,
        ]);
    }

    public function businessDealsIndex(User $member, Request $request): View
    {
        $config = $this->activityConfig('business-deals');
        $filters = $this->extractFilters($request);

        $query = BusinessDeal::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        $this->applyDateFilter($query, $filters, 'deal_date');
        $this->applySearchFilter($query, $filters, $config['searchable'], function ($q, $search) {
            $q->orWhereHas('toUser', function ($toUserQuery) use ($search) {
                $like = "%{$search}%";
                $toUserQuery->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like);
            });
        });

        $deals = $query
            ->orderByDesc('deal_date')
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.members.activities.index-business-deals', [
            'member' => $member,
            'items' => $deals,
            'filters' => $filters,
            'config' => $config,
        ]);
    }

    public function requirementsIndex(User $member, Request $request): View
    {
        $config = $this->activityConfig('requirements');
        $filters = $this->extractFilters($request);

        $query = Requirement::query()
            ->where('user_id', $member->id)
            ->whereNull('deleted_at');

        $this->applyDateFilter($query, $filters, 'created_at');
        $this->applySearchFilter($query, $filters, $config['searchable']);

        if ($filters['status']) {
            $query->where('status', $filters['status']);
        }

        $requirements = $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.members.activities.index-requirements', [
            'member' => $member,
            'items' => $requirements,
            'filters' => $filters,
            'config' => $config,
            'statusOptions' => ['open', 'in_progress', 'closed'],
        ]);
    }

    public function testimonialsIndex(User $member, Request $request): View
    {
        $config = $this->activityConfig('testimonials');
        $filters = $this->extractFilters($request);

        $query = Testimonial::query()
            ->with(['toUser'])
            ->where('from_user_id', $member->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        $this->applyDateFilter($query, $filters, 'created_at');
        $this->applySearchFilter($query, $filters, $config['searchable'], function ($q, $search) {
            $q->orWhereHas('toUser', function ($toUserQuery) use ($search) {
                $like = "%{$search}%";
                $toUserQuery->where('display_name', 'ILIKE', $like)
                    ->orWhere('first_name', 'ILIKE', $like)
                    ->orWhere('last_name', 'ILIKE', $like)
                    ->orWhere('email', 'ILIKE', $like);
            });
        });

        $testimonials = $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.members.activities.index-testimonials', [
            'member' => $member,
            'items' => $testimonials,
            'filters' => $filters,
            'config' => $config,
        ]);
    }

    public function create(User $member, string $type): View
    {
        $config = $this->activityConfig($type);

        return view('admin.members.activities.create', [
            'member' => $member,
            'type' => $type,
            'config' => $config,
            'referralTypes' => $this->referralTypes(),
            'businessTypes' => ['new', 'repeat'],
            'statusOptions' => ['open', 'in_progress', 'closed'],
        ]);
    }

    public function store(StoreMemberActivityRequest $request, User $member, string $type): RedirectResponse
    {
        $config = $this->activityConfig($type);
        $validated = Arr::except($request->validated(), ['member_id']);

        try {
            switch ($type) {
                case 'p2p-meetings':
                    $meeting = P2pMeeting::create([
                        'initiator_user_id' => $member->id,
                        'peer_user_id' => $validated['peer_user_id'],
                        'meeting_date' => $validated['meeting_date'],
                        'meeting_place' => $validated['meeting_place'],
                        'remarks' => $validated['remarks'],
                        'is_deleted' => false,
                    ]);

                    $this->rewardCoins($member, 'p2p_meeting');

                    event(new ActivityCreated(
                        'P2P Meeting',
                        $meeting,
                        (string) $member->id,
                        $meeting->peer_user_id ? (string) $meeting->peer_user_id : null
                    ));

                    break;

                case 'referrals':
                    $referral = Referral::create([
                        'from_user_id' => $member->id,
                        'to_user_id' => $validated['to_user_id'],
                        'referral_type' => $validated['referral_type'],
                        'referral_date' => $validated['referral_date'],
                        'referral_of' => $validated['referral_of'],
                        'phone' => $validated['phone'],
                        'email' => $validated['email'],
                        'address' => $validated['address'],
                        'hot_value' => $validated['hot_value'],
                        'remarks' => $validated['remarks'],
                        'is_deleted' => false,
                    ]);

                    $this->rewardCoins($member, 'referral');

                    event(new ActivityCreated(
                        'Referral',
                        $referral,
                        (string) $member->id,
                        $referral->to_user_id ? (string) $referral->to_user_id : null
                    ));

                    break;

                case 'business-deals':
                    $deal = BusinessDeal::create([
                        'from_user_id' => $member->id,
                        'to_user_id' => $validated['to_user_id'],
                        'deal_date' => $validated['deal_date'],
                        'deal_amount' => $validated['deal_amount'],
                        'business_type' => $validated['business_type'],
                        'comment' => $validated['comment'] ?? null,
                        'is_deleted' => false,
                    ]);

                    $this->rewardCoins($member, 'business_deal');
                    $this->createPostForBusinessDeal($deal);

                    event(new ActivityCreated(
                        'Business Deal',
                        $deal,
                        (string) $member->id,
                        $deal->to_user_id ? (string) $deal->to_user_id : null
                    ));

                    break;

                case 'requirements':
                    $media = null;
                    if (! empty($validated['media_id'])) {
                        $media = [[
                            'id' => $validated['media_id'],
                            'type' => 'image',
                        ]];
                    }

                    $requirement = Requirement::create([
                        'user_id' => $member->id,
                        'subject' => $validated['subject'],
                        'description' => $validated['description'],
                        'media' => $media,
                        'region_filter' => [
                            'region_label' => $validated['region_label'],
                            'city_name' => $validated['city_name'],
                        ],
                        'category_filter' => [
                            'category' => $validated['category'],
                        ],
                        'status' => $validated['status'] ?? 'open',
                    ]);

                    $this->rewardCoins($member, 'requirement');
                    $this->createPostForRequirement($requirement);

                    event(new ActivityCreated(
                        'Requirement',
                        $requirement,
                        (string) $member->id,
                        null
                    ));

                    break;

                case 'testimonials':
                    $media = null;
                    if (! empty($validated['media_id'])) {
                        $media = [[
                            'id' => $validated['media_id'],
                            'type' => 'image',
                        ]];
                    }

                    $testimonial = Testimonial::create([
                        'from_user_id' => $member->id,
                        'to_user_id' => $validated['to_user_id'],
                        'content' => $validated['content'],
                        'media' => $media,
                        'is_deleted' => false,
                    ]);

                    $this->rewardCoins($member, 'testimonial');
                    $this->createPostForTestimonial($testimonial);

                    event(new ActivityCreated(
                        'Testimonial',
                        $testimonial,
                        (string) $member->id,
                        $testimonial->to_user_id ? (string) $testimonial->to_user_id : null
                    ));

                    break;

                default:
                    abort(404);
            }
        } catch (Throwable $e) {
            return back()
                ->withErrors(['error' => 'Failed to add ' . $config['singular'] . '.'])
                ->withInput();
        }

        return redirect()
            ->route($config['route'], $member)
            ->with('success', $config['singular'] . ' added successfully.');
    }

    private function activityConfig(string $type): array
    {
        if (! array_key_exists($type, self::TYPE_CONFIG)) {
            abort(404);
        }

        $config = self::TYPE_CONFIG[$type];
        $config['coins_reward'] = config('coins.activity_rewards')[$config['activity_type']] ?? null;

        return $config;
    }

    private function extractFilters(Request $request): array
    {
        $perPage = $request->integer('per_page') ?: 20;
        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;

        return [
            'search' => trim((string) $request->query('q', $request->input('search', ''))),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'status' => $request->input('status'),
            'per_page' => $perPage,
        ];
    }

    private function applyDateFilter($query, array $filters, string $column): void
    {
        if (! empty($filters['start_date'])) {
            $query->whereDate($column, '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate($column, '<=', $filters['end_date']);
        }
    }

    private function applySearchFilter($query, array $filters, array $columns, ?callable $relationCallback = null): void
    {
        if ($filters['search'] === '') {
            return;
        }

        $search = $filters['search'];
        $like = "%{$search}%";

        $query->where(function ($subQuery) use ($columns, $like, $relationCallback, $search) {
            foreach ($columns as $column) {
                $subQuery->orWhere($column, 'ILIKE', $like);
            }

            if ($relationCallback) {
                $relationCallback($subQuery, $search);
            }
        });
    }

    private function rewardCoins(User $member, string $activityType): void
    {
        app(CoinsService::class)->rewardForActivity(
            $member,
            $activityType,
            null,
            'Activity: ' . $activityType,
            $member->id
        );
    }

    private function referralTypes(): array
    {
        return [
            'customer_referral',
            'b2b_referral',
            'b2g_referral',
            'collaborative_projects',
            'referral_partnerships',
            'vendor_referrals',
            'others',
        ];
    }

    private function addUrlsToMedia(?array $media): array
    {
        if (empty($media)) {
            return [];
        }

        return collect($media)->map(function ($item) {
            $id = $item['id'] ?? null;
            $type = $item['type'] ?? 'image';

            return [
                'id' => $id,
                'type' => $type,
                'url' => $id ? url('/api/v1/files/' . $id) : null,
            ];
        })->all();
    }

    private function createPostForRequirement(Requirement $requirement): void
    {
        try {
            $mediaForPost = $this->addUrlsToMedia($requirement->media ?? []);

            Post::create([
                'user_id' => $requirement->user_id,
                'circle_id' => null,
                'content_text' => trim(($requirement->subject ?? '') . ' - ' . ($requirement->description ?? '')),
                'media' => $mediaForPost,
                'tags' => $requirement->tags ?? [],
                'visibility' => $requirement->visibility ?? 'public',
                'moderation_status' => 'pending',
                'sponsored' => false,
                'is_deleted' => false,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create post for requirement (admin)', [
                'requirement_id' => $requirement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createPostForTestimonial(Testimonial $testimonial): void
    {
        try {
            $mediaForPost = $this->addUrlsToMedia($testimonial->media ?? []);

            $toUser = User::find($testimonial->to_user_id);
            $contentText = 'Gave a testimonial';
            if ($toUser) {
                $displayName = trim($toUser->first_name . ' ' . $toUser->last_name);
                $contentText = 'Gave a testimonial for ' . $displayName . ': ' . ($testimonial->content ?? '');
            } else {
                $contentText = $testimonial->content ?? $contentText;
            }

            Post::create([
                'user_id' => $testimonial->from_user_id,
                'circle_id' => null,
                'content_text' => $contentText,
                'media' => $mediaForPost,
                'tags' => ['testimonial'],
                'visibility' => 'public',
                'moderation_status' => 'pending',
                'sponsored' => false,
                'is_deleted' => false,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create post for testimonial (admin)', [
                'testimonial_id' => $testimonial->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createPostForBusinessDeal(BusinessDeal $deal): void
    {
        try {
            $mediaForPost = [];
            $toUser = User::find($deal->to_user_id);

            $contentText = 'Closed a business deal';
            if ($toUser) {
                $displayName = trim($toUser->first_name . ' ' . $toUser->last_name);
                $contentText = 'Closed a ' . ($deal->business_type ?? 'business') . ' deal with ' . $displayName
                    . ' for amount ' . ($deal->deal_amount ?? 0)
                    . '. ' . ($deal->comment ?? '');
            } else {
                $contentText = ($deal->comment ?: $contentText);
            }

            Post::create([
                'user_id' => $deal->from_user_id ?? $deal->user_id ?? $deal->created_by ?? $deal->to_user_id,
                'circle_id' => null,
                'content_text' => $contentText,
                'media' => $mediaForPost,
                'tags' => ['business_deal'],
                'visibility' => 'public',
                'moderation_status' => 'pending',
                'sponsored' => false,
                'is_deleted' => false,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create post for business deal (admin)', [
                'business_deal_id' => $deal->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
