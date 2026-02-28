<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollaborationStoreRequest;
use App\Models\CollaborationPost;
use App\Models\CollaborationType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CollaborationPostController extends Controller
{
    public function store(CollaborationStoreRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            $type = CollaborationType::query()
                ->where('id', $request->collaboration_type_id)
                ->firstOrFail();

            $collaborationTypeSlug = $type->slug ?: $type->name;

            $post = CollaborationPost::create([
                'user_id' => $user->id,
                'collaboration_type_id' => $type->id,
                'collaboration_type' => $collaborationTypeSlug,
                'title' => $request->title,
                'description' => $request->description,
                'scope' => $request->scope,
                'countries_of_interest' => $request->countries_of_interest ?? [],
                'preferred_model' => $request->preferred_model,
                'industry_id' => $request->industry_id,
                'business_stage' => $request->business_stage,
                'years_in_operation' => $request->years_in_operation,
                'urgency' => $request->urgency,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Collaboration created',
                'data' => $post,
                'meta' => null,
            ], 201);
        } catch (QueryException $e) {
            Log::error('Collaboration store DB error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'user_id' => optional($request->user())->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => null,
                'meta' => [
                    'db_error' => 'DB constraint failed',
                ],
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Collaboration store unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
                'user_id' => optional($request->user())->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'data' => null,
                'meta' => null,
            ], 500);
        }
    }
}
