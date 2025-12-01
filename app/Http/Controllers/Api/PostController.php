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
        $request->user(); // Ensure authentication but no filtering yet

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min($perPage, 50));

        $paginator = Post::query()
            ->with(['user', 'circle'])
            ->withCount(['likes', 'comments'])
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->where('moderation_status', 'approved')
            ->orderByDesc('created_at')
            ->paginate($perPage);

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
        $user = $request->user();

        // Validation is handled by StorePostRequest
        $data = $request->validated();

        // Attach author
        $data['user_id'] = $user->id;

        // Enforce: at least content_text or media must be present
        if (empty($data['content_text']) && empty($data['media'])) {
            return $this->error('Either content_text or media is required.', 422);
        }

        // Normalize media array (for JSONB)
        if (! empty($data['media']) && is_array($data['media'])) {
            $data['media'] = array_values($data['media']);
        }

        // Create the post
        $post = Post::create($data);

        // Reload the post with relations and counts using a fresh query.
        // IMPORTANT: use with() + withCount() instead of loadCount() on the model
        $post = Post::query()
            ->with([
                'user:id,first_name,last_name,display_name,profile_photo_url,public_profile_slug',
                'circle:id,name,slug',
            ])
            ->withCount([
                'likes',
                'comments',
            ])
            ->findOrFail($post->id);

        return $this->success(
            new PostResource($post),
            'Post created successfully',
            201
        );
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
