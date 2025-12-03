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
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends BaseApiController
{
    public function feed(Request $request)
    {
        $user = $request->user();

        $query = Post::query()
            ->with([
                'author:id,display_name,first_name,last_name,profile_photo_url',
            ])
            ->withCount(['likes', 'comments'])
            ->orderByDesc('created_at');

        // For now, just show all public posts.
        // Do NOT filter by moderation status so that newly created posts appear immediately.
        $query->where('visibility', 'public');

        $paginator = $query->paginate(20);

        return $this->success([
            'items' => PostResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StorePostRequest $request)
    {
        $user = Auth::user();

        $media = null;

        if ($request->filled('image_id')) {
            $upload = Upload::findOrFail($request->image_id);

            $media = [[
                'id' => $upload->id,
                'type' => 'image',
                'disk' => $upload->disk ?? 'public',
                'path' => $upload->path,
                'url' => $upload->url,
                'mime_type' => $upload->mime_type,
            ]];
        }

        $post = Post::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'circle_id' => $request->input('circle_id'),
            'content_text' => $request->input('content_text'),
            'media' => $media,
            'tags' => $request->input('tags', []),
            'visibility' => $request->input('visibility', 'public'),
            'moderation_status' => $request->input('moderation_status', 'pending'),
            'sponsored' => $request->boolean('sponsored', false),
            'is_deleted' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => [
                'id' => $post->id,
                'user_id' => $post->user_id,
                'circle_id' => $post->circle_id,
                'content_text' => $post->content_text,
                'media' => $post->media,
                'tags' => $post->tags,
                'visibility' => $post->visibility,
                'moderation_status' => $post->moderation_status,
                'sponsored' => $post->sponsored,
                'is_deleted' => $post->is_deleted,
                'created_at' => $post->created_at,
            ],
        ]);
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
        $user = $request->user();

        // User can delete ONLY their own posts
        $post = Post::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $post) {
            return $this->error('Post not found or you are not allowed to delete it', 404);
        }

        $post->delete(); // respects SoftDeletes if used on the model

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
