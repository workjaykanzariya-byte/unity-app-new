<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\ReportPostRequest;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\PostReportReason;
use Illuminate\Http\JsonResponse;

class PostReportController extends BaseApiController
{
    public function store(ReportPostRequest $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if ($post->is_deleted || $post->deleted_at) {
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
        $reason = PostReportReason::query()->find($data['reason_id']);

        $report = PostReport::create([
            'post_id' => $post->id,
            'reporter_user_id' => $user->id,
            'reason_id' => $data['reason_id'],
            'reason' => $reason?->title,
            'status' => 'open',
        ]);

        return $this->success([
            'id' => $report->id,
            'status' => $report->status,
        ], 'Post reported');
    }
}
