<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostSave;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostSaveController extends BaseApiController
{
    public function toggle(Request $request, string $postId): JsonResponse
    {
        $user = $request->user();

        $post = Post::query()
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->findOrFail($postId);

        $existingSave = PostSave::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existingSave) {
            $existingSave->delete();
            $isSaved = false;
            $message = 'Post unsaved';
        } else {
            PostSave::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);

            $isSaved = true;
            $message = 'Post saved';
        }

        $savesCount = PostSave::query()
            ->where('post_id', $post->id)
            ->count();

        return $this->success([
            'post_id' => $post->id,
            'is_saved' => $isSaved,
            'saves_count' => $savesCount,
        ], $message);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $posts = Post::query()
            ->select('posts.*')
            ->join('post_saves', 'post_saves.post_id', '=', 'posts.id')
            ->where('post_saves.user_id', $user->id)
            ->where('posts.is_deleted', false)
            ->whereNull('posts.deleted_at')
            ->with([
                'author:id,display_name,first_name,last_name,profile_photo_file_id',
                'circle:id,name',
            ])
            ->withCount(['likes', 'comments', 'saves'])
            ->withExists([
                'likes as is_liked_by_me' => fn ($query) => $query->where('user_id', $user->id),
                'saves as is_saved_by_me' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->orderByDesc('post_saves.created_at')
            ->get();

        return $this->success([
            'items' => PostResource::collection($posts),
        ], 'Saved posts fetched successfully');
    }
}

/*
Quick Postman steps:
- POST /api/v1/posts/{postId}/save (auth:sanctum) to toggle save/unsave.
- GET  /api/v1/posts/saved (auth:sanctum) to fetch saved posts ordered by latest save.
*/
