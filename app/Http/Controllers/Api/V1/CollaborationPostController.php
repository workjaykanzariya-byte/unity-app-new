<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CollaborationPost;
use App\Models\CollaborationType;
use Illuminate\Http\Request;

class CollaborationPostController extends Controller
{
    public function store(Request $request)
    {
        // normalize inputs
        $title = is_string($request->title) ? trim($request->title) : $request->title;
        $businessStage = $request->business_stage;

        // Fix common typo from client
        if ($businessStage === 'growing_101_1cr') {
            $businessStage = 'growing_10l_1cr';
        }

        $request->merge([
            'title' => $title,
            'business_stage' => $businessStage,
        ]);

        $request->validate([
            'collaboration_type_id' => ['required','uuid','exists:collaboration_types,id'],
            'title' => ['required','string','max:80'],
            'description' => ['nullable','string'],
            'scope' => ['required','string'],
            'countries_of_interest' => ['nullable','array'],
            'preferred_model' => ['required','string'],
            'industry_id' => ['required','uuid'],
            'business_stage' => ['required','string'],
            'years_in_operation' => ['required','string'],
            'urgency' => ['required','string'],
        ]);

        try {
            $user = $request->user();

            $type = CollaborationType::query()
                ->where('id', $request->collaboration_type_id)
                ->firstOrFail();

            $collaborationTypeValue = trim((string)($type->slug ?: $type->name));
            if ($collaborationTypeValue === '') {
                $collaborationTypeValue = 'unknown';
            }

            $post = CollaborationPost::create([
                'user_id' => $user->id,
                'collaboration_type_id' => $type->id,
                'collaboration_type' => $collaborationTypeValue, // ✅ required by DB
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

        } catch (\Throwable $e) {
            // ONLY for this endpoint to unblock debugging
            return response()->json([
                'status' => false,
                'message' => 'Server error',
                'data' => null,
                'meta' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ], 500);
        }
    }
}
