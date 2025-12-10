<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\File;
use App\Models\Testimonial;
use App\Services\Coins\CoinsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        $items = collect($paginator->items())
            ->map(fn (Testimonial $testimonial) => $this->transformTestimonial($testimonial))
            ->values();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $authUser = $request->user();
        $data = $this->validateTestimonial($request);

        $mediaItems = $this->buildMediaItems($data);
        $firstMediaId = $mediaItems[0]['id'] ?? ($data['media_id'] ?? null);

        try {
            $testimonial = Testimonial::create([
                'from_user_id' => $authUser->id,
                'to_user_id' => $data['to_user_id'],
                'content' => $data['content'],
                'media_id' => $firstMediaId,
                'media' => $mediaItems ?: [],
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
                $this->transformTestimonial($testimonial),
                'Testimonial saved successfully',
                201
            );
        } catch (Throwable $e) {
            Log::error('Failed to create testimonial', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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

        return $this->success($this->transformTestimonial($testimonial));
    }

    private function validateTestimonial(Request $request): array
    {
        return $request->validate([
            'to_user_id' => ['required', 'uuid', 'exists:users,id'],
            'content' => ['required', 'string', 'max:2000'],
            'media' => ['nullable', 'array'],
            'media.*.id' => ['required_with:media', 'uuid', 'exists:files,id'],
            'media.*.type' => ['required_with:media', 'string', 'max:50'],
            'media_id' => ['nullable', 'uuid', 'exists:files,id'],
        ]);
    }

    private function buildMediaItems(array $data): array
    {
        $mediaItems = [];

        if (! empty($data['media'])) {
            $fileIds = collect($data['media'])->pluck('id')->all();

            $files = File::whereIn('id', $fileIds)->get()->keyBy('id');

            foreach ($data['media'] as $item) {
                $file = $files->get($item['id']);
                if (! $file) {
                    continue;
                }

                $mediaItems[] = [
                    'id' => $file->id,
                    'type' => $item['type'],
                    'url' => $file->url,
                ];
            }
        } elseif (! empty($data['media_id'])) {
            $file = File::find($data['media_id']);
            if ($file) {
                $mediaItems[] = [
                    'id' => $file->id,
                    'type' => 'image',
                    'url' => $file->url,
                ];
            }
        }

        return $mediaItems;
    }

    private function transformTestimonial(Testimonial $testimonial): array
    {
        return [
            'id' => $testimonial->id,
            'from_user_id' => $testimonial->from_user_id,
            'to_user_id' => $testimonial->to_user_id,
            'content' => $testimonial->content,
            'media_id' => $testimonial->media_id,
            'media' => $testimonial->media ?? [],
            'created_at' => $testimonial->created_at,
            'updated_at' => $testimonial->updated_at,
        ];
    }
}
