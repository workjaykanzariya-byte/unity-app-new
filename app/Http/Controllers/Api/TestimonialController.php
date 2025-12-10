<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreTestimonialRequest;
use App\Models\File;
use App\Models\Testimonial;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Throwable;

class TestimonialController extends BaseApiController
{
    public function index(Request $request)
    {
        $authUser = $request->user();
        $filter = $request->input('filter', 'given');

        $query = Testimonial::query()
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

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success([
            'items' => collect($paginator->items())
                ->map(fn (Testimonial $testimonial) => $this->formatTestimonial($testimonial))
                ->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreTestimonialRequest $request)
    {
        $authUser = $request->user();

        $media = null;
        if ($request->filled('media_id')) {
            $media = [[
                'id' => $request->input('media_id'),
                'type' => 'image',
            ]];
        }

        try {
            $testimonial = Testimonial::create([
                'from_user_id' => $authUser->id,
                'to_user_id' => $request->input('to_user_id'),
                'content' => $request->input('content'),
                'media' => $media,
                'is_deleted' => false,
            ]);

            $coinsLedger = app(CoinsService::class)->rewardForActivity(
                $authUser,
                'testimonial',
                null,
                'Activity: testimonial',
                $authUser->id
            );

            if ($coinsLedger) {
                $testimonial->setAttribute('coins', [
                    'earned' => $coinsLedger->amount,
                    'balance_after' => $coinsLedger->balance_after,
                ]);
            }

            return $this->success(
                $this->formatTestimonial($testimonial),
                'Testimonial saved successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->error('Something went wrong', 500);
        }
    }

    public function show(Request $request, string $id)
    {
        $authUser = $request->user();

        $testimonial = Testimonial::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($authUser) {
                $q->where('from_user_id', $authUser->id)
                    ->orWhere('to_user_id', $authUser->id);
            })
            ->first();

        if (! $testimonial) {
            return $this->error('Testimonial not found', 404);
        }

        return $this->success($this->formatTestimonial($testimonial));
    }

    protected function enrichMediaWithUrls(?array $media): array
    {
        if (empty($media)) {
            return [];
        }

        $fileIds = collect($media)
            ->pluck('id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($fileIds)) {
            return [];
        }

        $files = File::whereIn('id', $fileIds)->get()->keyBy('id');

        return collect($media)->map(function ($item) use ($files) {
            $id = $item['id'] ?? null;
            $type = $item['type'] ?? 'image';

            $file = $id ? $files->get($id) : null;

            return [
                'id' => $id,
                'type' => $type,
                'url' => $file ? $file->url : null,
            ];
        })->all();
    }

    protected function formatTestimonial(Testimonial $testimonial): array
    {
        $rawMedia = $testimonial->media ?? (
            $testimonial->media_id
                ? [['id' => $testimonial->media_id, 'type' => 'image']]
                : []
        );

        $data = [
            'from_user_id' => $testimonial->from_user_id,
            'to_user_id' => $testimonial->to_user_id,
            'content' => $testimonial->content,
            'media' => $this->enrichMediaWithUrls($rawMedia),
            'id' => $testimonial->id,
            'updated_at' => $testimonial->updated_at,
            'created_at' => $testimonial->created_at,
        ];

        if ($testimonial->getAttribute('coins')) {
            $data['coins'] = $testimonial->getAttribute('coins');
        }

        return $data;
    }
}
