<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreTestimonialRequest;
use App\Models\Testimonial;
use App\Services\Coins\CoinsService;
use Illuminate\Support\Facades\DB;
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
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreTestimonialRequest $request, CoinsService $coinsService)
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
            [$testimonial, $newBalance] = DB::transaction(function () use ($request, $authUser, $media, $coinsService) {
                $createdTestimonial = Testimonial::create([
                    'from_user_id' => $authUser->id,
                    'to_user_id' => $request->input('to_user_id'),
                    'content' => $request->input('content'),
                    'media' => $media,
                    'is_deleted' => false,
                ]);

                $balance = $coinsService->awardForActivity($authUser, 'testimonials', (string) $createdTestimonial->id);

                return [$createdTestimonial, $balance];
            });

            return $this->success(
                array_merge($testimonial->toArray(), ['coins_balance' => $newBalance]),
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

        return $this->success($testimonial);
    }
}
