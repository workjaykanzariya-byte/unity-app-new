<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Activity\StoreTestimonialRequest;
use App\Models\Testimonial;
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

            // TODO: award coins for this activity

            return $this->success($testimonial, 'Testimonial saved successfully', 201);
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
