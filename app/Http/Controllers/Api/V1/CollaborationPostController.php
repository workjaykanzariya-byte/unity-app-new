<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCollaborationPostRequest;
use App\Http\Resources\CollaborationPostResource;
use App\Services\Collaboration\CollaborationPostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CollaborationPostController extends Controller
{
    public function __construct(private readonly CollaborationPostService $collaborationPostService)
    {
    }

    public function store(StoreCollaborationPostRequest $request): JsonResponse
    {
        try {
            $post = $this->collaborationPostService->createForUser($request->user(), $request->validated());
        } catch (ValidationException $exception) {
            $errors = $exception->errors();

            if (isset($errors['industry_id']) && in_array('Please select a leaf industry.', $errors['industry_id'], true)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please select a leaf industry.',
                    'data' => null,
                    'errors' => $errors,
                ], 422);
            }

            if (isset($errors['collaborations'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Free members can have maximum 2 active collaboration posts. Please upgrade to post more.',
                    'data' => null,
                    'errors' => $errors,
                ], 422);
            }

            throw $exception;
        }

        $post->load([
            'user:id,first_name,last_name,display_name,city,membership_status,profile_photo_file_id',
            'industry:id,name,parent_id',
            'collaborationType:id,name,slug',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Collaboration post created successfully.',
            'data' => new CollaborationPostResource($post),
        ], 201);
    }
}
