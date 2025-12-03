<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Post\PostRequest;
use App\Http\Requests\Post\StorePostCommentRequest;
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
        $query = Post::query()
            ->with([
                'author:id,display_name,first_name,last_name,profile_photo_url',
                'media',
            ])
            ->withCount(['likes', 'comments'])
            ->orderByDesc('created_at');

        // For now, just show all public posts.
        // Do NOT filter by moderation status so that newly created posts appear immediately.
        $query->where('visibility', 'public');

        $paginator = $query->paginate(20);

        return $this->success(null, [
            'items' => PostResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(PostRequest $request)
    {
        $user = $request->user();

        $post = $user->posts()->create([
            'content' => $request->content,
            'visibility' => 'public',
        ]);

        if ($request->media_ids) {
            $post->media()->sync($request->media_ids);
        }

        return $this->success(
            'Post created successfully',
            new PostResource($post->load('media')),
            201
        );
    }

    public function show(Request $request, string $id)
    {
        $post = Post::with(['user', 'circle'])
            ->withCount(['likes', 'comments'])
            ->with(['media'])
            ->find($id);

        if (! $post || $post->is_deleted || $post->deleted_at) {
            return $this->error('Post not found', 404);
        }

        return $this->success(null, new PostResource($post));
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();

        // User can delete ONLY their own posts
        $post = Post::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $post) {
            return $this->error('Post not found or you are not allowed to delete it', 404);
        }

        $post->delete(); // respects SoftDeletes if used on the model

        return $this->success('Post deleted successfully');
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

        return $this->success('Post liked', ['like_count' => $likeCount]);
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

        return $this->success('Post unliked', ['like_count' => $likeCount]);
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

        return $this->success('Comment added', new PostCommentResource($comment), 201);
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

        return $this->success(null, $data);
    }
}
