<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\V1\PostLikeResource;
use App\Http\Resources\V1\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyPostsController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $perPage = (int) $request->input('per_page', 12);
        $perPage = max(1, min($perPage, 50));

        $paginator = Post::query()
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->with([
                'author:id,display_name,profile_photo_file_id',
                'comments' => function ($query) {
                    $query
                        ->with('user:id,display_name,profile_photo_file_id')
                        ->latest('created_at')
                        ->limit(2);
                },
            ])
            ->withCount(['likes', 'comments'])
            ->withExists([
                'likes as liked_by_me' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => PostResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function likes(Request $request, string $postId): JsonResponse
    {
        $user = $request->user();

        $post = Post::query()
            ->where('id', $postId)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            return $this->error('Post not found', 404);
        }

        if ($post->user_id !== $user->id && $post->visibility !== 'public') {
            return $this->error('Forbidden', 403);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 50));

        $paginator = $post->likes()
            ->with('user:id,display_name,profile_photo_file_id')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => PostLikeResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
