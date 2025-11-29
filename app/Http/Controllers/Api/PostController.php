<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Post\StorePostCommentRequest;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostResource;
use App\Models\CircleMember;
use App\Models\Connection;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Http\Request;

class PostController extends BaseApiController
{
    public function feed(Request $request)
    {
        $authUser = $request->user();

        $circleIds = CircleMember::where('user_id', $authUser->id)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->pluck('circle_id')
            ->all();

        $connectionUserIds = Connection::where('is_approved', true)
            ->where(function ($q) use ($authUser) {
                $q->where('requester_id', $authUser->id)
                    ->orWhere('addressee_id', $authUser->id);
            })
            ->get()
            ->flatMap(function ($connection) use ($authUser) {
                return [
                    $connection->requester_id === $authUser->id
                        ? $connection->addressee_id
                        : $connection->requester_id,
                ];
            })
            ->unique()
            ->values()
            ->all();

        $query = Post::query()
            ->with(['user', 'circle'])
            ->withCount(['likes', 'comments'])
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where('moderation_status', 'approved')
            ->where(function ($q) use ($authUser, $circleIds, $connectionUserIds) {
                $q->where('visibility', 'public')
                    ->orWhere(function ($q2) use ($circleIds) {
                        if (! empty($circleIds)) {
                            $q2->where('visibility', 'circle')
                                ->whereIn('circle_id', $circleIds);
                        }
                    })
                    ->orWhere(function ($q3) use ($connectionUserIds) {
                        if (! empty($connectionUserIds)) {
                            $q3->where('visibility', 'connections')
                                ->whereIn('user_id', $connectionUserIds);
                        }
                    })
                    ->orWhere('user_id', $authUser->id);
            });

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 50));

        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = [
            'items' => PostResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function store(StorePostRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $post = new Post();
        $post->user_id = $authUser->id;
        $post->circle_id = $data['circle_id'] ?? null;
        $post->content_text = $data['content_text'] ?? null;
        $post->media = $data['media'] ?? null;
        $post->tags = $data['tags'] ?? null;
        $post->visibility = $data['visibility'];
        $post->sponsored = $data['sponsored'] ?? false;
        $post->save();

        $post->load(['user', 'circle'])->loadCount(['likes', 'comments']);

        return $this->success(new PostResource($post), 'Post created successfully', 201);
    }

    public function show(Request $request, string $id)
    {
        $post = Post::with(['user', 'circle'])
            ->withCount(['likes', 'comments'])
            ->find($id);

        if (! $post || $post->is_deleted || $post->deleted_at) {
            return $this->error('Post not found', 404);
        }

        return $this->success(new PostResource($post));
    }

    public function destroy(Request $request, string $id)
    {
        $authUser = $request->user();

        $post = Post::where('id', $id)
            ->where('user_id', $authUser->id)
            ->first();

        if (! $post) {
            return $this->error('Post not found or you are not allowed to delete it', 404);
        }

        if ($post->is_deleted) {
            return $this->success(null, 'Post already deleted');
        }

        $post->is_deleted = true;
        $post->deleted_at = now();
        $post->save();

        return $this->success(null, 'Post deleted successfully');
    }

    public function like(Request $request, string $id)
    {
        $authUser = $request->user();

        $post = Post::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            return $this->error('Post not found', 404);
        }

        PostLike::firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $authUser->id,
        ]);

        $likeCount = PostLike::where('post_id', $post->id)->count();

        return $this->success(['like_count' => $likeCount], 'Post liked');
    }

    public function unlike(Request $request, string $id)
    {
        $authUser = $request->user();

        $post = Post::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            return $this->error('Post not found', 404);
        }

        PostLike::where('post_id', $post->id)
            ->where('user_id', $authUser->id)
            ->delete();

        $likeCount = PostLike::where('post_id', $post->id)->count();

        return $this->success(['like_count' => $likeCount], 'Post unliked');
    }

    public function storeComment(StorePostCommentRequest $request, string $id)
    {
        $authUser = $request->user();

        $post = Post::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            return $this->error('Post not found', 404);
        }

        $data = $request->validated();

        $comment = new PostComment();
        $comment->post_id = $post->id;
        $comment->user_id = $authUser->id;
        $comment->content = $data['content'];
        $comment->parent_id = $data['parent_id'] ?? null;
        $comment->save();

        $comment->load('user');

        return $this->success(new PostCommentResource($comment), 'Comment added', 201);
    }

    public function listComments(Request $request, string $id)
    {
        $post = Post::where('id', $id)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            return $this->error('Post not found', 404);
        }

        $perPage = (int) $request->input('per_page', 50);
        $perPage = max(1, min($perPage, 100));

        $paginator = PostComment::with('user')
            ->where('post_id', $post->id)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        $data = [
            'items' => PostCommentResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }
}
