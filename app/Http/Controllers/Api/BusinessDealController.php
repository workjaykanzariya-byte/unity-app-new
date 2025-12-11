<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreBusinessDealRequest;
use App\Models\Post;
use App\Models\BusinessDeal;
use App\Models\File;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BusinessDealController extends BaseApiController
{
    protected function addUrlsToMedia(?array $media): array
    {
        if (empty($media)) {
            return [];
        }

        return collect($media)->map(function ($item) {
            $id   = $item['id']   ?? null;
            $type = $item['type'] ?? 'image';

            return [
                'id'   => $id,
                'type' => $type,
                'url'  => $id ? url('/api/v1/files/' . $id) : null,
            ];
        })->all();
    }

    protected function buildMediaItemsFromId(?string $mediaId): array
    {
        if (empty($mediaId)) {
            return [];
        }

        $file = File::find($mediaId);

        if (! $file) {
            return [];
        }

        return [[
            'id' => $file->id,
            'type' => 'image',
            'url' => url('/api/v1/files/' . $file->id),
        ]];
    }

    /**
     * Create a feed post for a newly created business deal.
     */
    protected function createPostForBusinessDeal(BusinessDeal $deal, ?string $mediaId = null): void
    {
        try {
            $mediaForPost = $this->buildMediaItemsFromId($mediaId);

            $lines = ['Business Deal:'];

            if (! empty($deal->comment)) {
                $lines[] = 'Comment: ' . $deal->comment;
            }

            if (! empty($deal->deal_amount)) {
                $lines[] = 'Amount: ' . $deal->deal_amount;
            }

            if (! empty($deal->deal_date)) {
                $lines[] = 'Date: ' . $deal->deal_date;
            }

            if (! empty($deal->business_type)) {
                $lines[] = 'Type: ' . $deal->business_type;
            }

            $contentText = implode(PHP_EOL, $lines);

            Post::create([
                'user_id'           => $deal->from_user_id ?? $deal->user_id ?? $deal->created_by ?? Auth::id(),
                'circle_id'         => null,
                'content_text'      => $contentText,
                'media'             => $mediaForPost,
                'tags'              => ['business_deal'],
                'visibility'        => 'public',
                'moderation_status' => 'pending',
                'sponsored'         => false,
                'is_deleted'        => false,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to create post for business deal', [
                'business_deal_id' => $deal->id,
                'error'            => $e->getMessage(),
            ]);
        }
    }

    public function index(Request $request)
    {
        $authUser = $request->user();
        $filter = $request->input('filter', 'given');
        $businessType = $request->input('business_type');

        $query = BusinessDeal::query()
            ->where('is_deleted', false)
            ->whereNull('deleted_at');

        if ($filter === 'received') {
            $query->where('to_user_id', $authUser->id);
        } elseif ($filter === 'all') {
            $query->where(function ($q) use ($authUser) {
                $q->where('from_user_id', $authUser->id)
                    ->orWhere('to_user_id', $authUser->id);
            });
        } else {
            $query->where('from_user_id', $authUser->id);
        }

        if ($businessType) {
            $query->where('business_type', $businessType);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('deal_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreBusinessDealRequest $request)
    {
        $authUser = $request->user();

        try {
            $businessDeal = BusinessDeal::create([
                'from_user_id' => $authUser->id,
                'to_user_id' => $request->input('to_user_id'),
                'deal_date' => $request->input('deal_date'),
                'deal_amount' => $request->input('deal_amount'),
                'business_type' => $request->input('business_type'),
                'comment' => $request->input('comment'),
                'is_deleted' => false,
            ]);

            $coinsLedger = app(CoinsService::class)->rewardForActivity(
                $authUser,
                'business_deal',
                null,
                'Activity: business_deal',
                $authUser->id
            );

            if ($coinsLedger) {
                $businessDeal->setAttribute('coins', [
                    'earned' => $coinsLedger->amount,
                    'balance_after' => $coinsLedger->balance_after,
                ]);
            }

            $this->createPostForBusinessDeal($businessDeal, $request->input('media_id'));

            return $this->success($businessDeal, 'Business deal saved successfully', 201);
        } catch (Throwable $e) {
            return $this->error('Something went wrong', 500);
        }
    }

    public function show(Request $request, string $id)
    {
        $authUser = $request->user();

        $businessDeal = BusinessDeal::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($authUser) {
                $q->where('from_user_id', $authUser->id)
                    ->orWhere('to_user_id', $authUser->id);
            })
            ->first();

        if (! $businessDeal) {
            return $this->error('Business deal not found', 404);
        }

        return $this->success($businessDeal);
    }
}
