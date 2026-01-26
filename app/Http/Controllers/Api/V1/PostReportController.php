<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\ReportPostRequest;
use App\Models\Post;
use App\Models\PostReport;
use Illuminate\Http\JsonResponse;

class PostReportController extends BaseApiController
{
    public function store(ReportPostRequest $request, string $postId): JsonResponse
    {
        $user = $request->user();

        $post = Post::query()
            ->where('id', $postId)
            ->where('is_deleted', false)
            ->whereNull('deleted_at')
            ->first();

        if (! $post) {
            return $this->error('Post not available', 404);
        }

        if ($post->user_id === $user->id) {
            return $this->error('You cannot report your own post', 422);
        }

        $existingReport = PostReport::query()
            ->where('post_id', $post->id)
            ->where('reporter_user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existingReport) {
            return $this->success([
                'id' => $existingReport->id,
                'status' => $existingReport->status,
            ], 'Already reported');
        }

        $data = $request->validated();

        $report = PostReport::create([
            'post_id' => $post->id,
            'reporter_user_id' => $user->id,
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
            'status' => 'open',
        ]);

        return $this->success([
            'id' => $report->id,
            'status' => $report->status,
        ], 'Post reported');
    }
}
