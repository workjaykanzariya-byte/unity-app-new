<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Post\PostLikeUserResource;
use App\Http\Resources\Post\PostResource;
use App\Models\File;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MyPostsController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->input('per_page', 12);
        $perPage = max(1, min($perPage, 50));

        $query = Post::query()
            ->where('user_id', $user->id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->with([
                'author' => function ($query) {
                    $query->select('id', 'display_name', 'first_name', 'last_name', 'profile_photo_file_id');
                },
                'author.profilePhotoFile',
            ])
            ->withCount([
                'likes as likes_count',
                'comments as comments_count',
            ])
            ->addSelect([
                'liked_by_me' => PostLike::selectRaw('1')
                    ->whereColumn('post_likes.post_id', 'posts.id')
                    ->where('post_likes.user_id', $user->id)
                    ->limit(1),
            ])
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);

        $posts = $paginator->getCollection();

        $postIds = $posts->pluck('id');

        $mediaFileIds = $posts
            ->flatMap(function (Post $post) {
                return collect($post->media ?? [])->pluck('id');
            })
            ->filter()
            ->unique()
            ->values();

        $mediaFiles = $mediaFileIds->isNotEmpty()
            ? File::whereIn('id', $mediaFileIds)->get()->keyBy('id')
            : collect();

        $latestCommentIdsQuery = PostComment::query()
            ->select('id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY post_id ORDER BY created_at DESC) as row_number')
            ->whereIn('post_id', $postIds);

        $latestCommentIds = $postIds->isNotEmpty()
            ? DB::query()
                ->fromSub($latestCommentIdsQuery, 'ranked_comments')
                ->where('row_number', '<=', 2)
                ->pluck('id')
            : collect();

        $latestComments = $latestCommentIds instanceof Collection && $latestCommentIds->isNotEmpty()
            ? PostComment::with([
                'user' => function ($query) {
                    $query->select('id', 'display_name', 'first_name', 'last_name', 'profile_photo_file_id');
                },
                'user.profilePhotoFile',
            ])
                ->whereIn('id', $latestCommentIds)
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('post_id')
            : collect();

        $posts = $posts->map(function (Post $post) use ($latestComments) {
            $post->setRelation('latest_comments', $latestComments->get($post->id, collect()));

            return $post;
        });

        $paginator->setCollection($posts);

        $request->attributes->set('post_media_files', $mediaFiles);

        return $this->success([
            'items' => PostResource::collection($posts),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function likes(string $postId, Request $request)
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
            return $this->error('You are not allowed to view likes for this post', 403);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $likesPaginator = PostLike::query()
            ->where('post_id', $post->id)
            ->with([
                'user' => function ($query) {
                    $query->select('id', 'display_name', 'first_name', 'last_name', 'profile_photo_file_id');
                },
                'user.profilePhotoFile',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success([
            'items' => PostLikeUserResource::collection($likesPaginator->getCollection()),
            'meta' => [
                'current_page' => $likesPaginator->currentPage(),
                'last_page' => $likesPaginator->lastPage(),
                'per_page' => $likesPaginator->perPage(),
                'total' => $likesPaginator->total(),
            ],
        ]);
    }
}
