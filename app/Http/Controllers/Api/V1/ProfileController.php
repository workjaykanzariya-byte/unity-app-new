<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Profile fetched successfully',
            'data' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $data['skills'] = $data['skills'] ?? [];
        $data['interests'] = $data['interests'] ?? [];
        $data['social_links'] = $data['social_links'] ?? [];

        if (array_key_exists('about', $data)) {
            $data['short_bio'] = $data['about'];
            unset($data['about']);
        }

        if (array_key_exists('profile_photo_id', $data)) {
            $data['profile_photo_file_id'] = $data['profile_photo_id'];
            unset($data['profile_photo_id']);
        }

        if (array_key_exists('cover_photo_id', $data)) {
            $data['cover_photo_file_id'] = $data['cover_photo_id'];
            unset($data['cover_photo_id']);
        }

        $user->fill($data);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),
        ]);
    }
}
