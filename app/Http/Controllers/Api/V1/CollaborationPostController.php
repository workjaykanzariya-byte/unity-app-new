<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPost;
use App\Models\CollaborationType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CollaborationPostController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'collaboration_type_id' => ['required', 'uuid', 'exists:collaboration_types,id'],
            'title' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'scope' => ['required', 'string'],
            'countries_of_interest' => ['nullable', 'array'],
            'preferred_model' => ['required', 'string'],
            'industry_id' => ['required', 'uuid'],
            'business_stage' => ['required', 'string'],
            'years_in_operation' => ['required', 'string'],
            'urgency' => ['required', 'string'],
        ]);

        try {
            $user = $request->user();

            $type = CollaborationType::query()
                ->where('id', $request->collaboration_type_id)
                ->firstOrFail();

            $collaborationTypeValue = trim((string) ($type->slug ?: $type->name));

            if ($collaborationTypeValue === '') {
                // absolute safety fallback
                $collaborationTypeValue = 'unknown';
            }

            $post = CollaborationPost::create([
                'user_id' => $user->id,
                'collaboration_type_id' => $type->id,
                'collaboration_type' => $collaborationTypeValue,
                'title' => trim($request->title),
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
            Log::error('Collaboration create DB error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'user_id' => optional($request->user())->id,
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'data' => null,
                'meta' => ['db' => 'constraint_failed'],
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Collaboration create unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
