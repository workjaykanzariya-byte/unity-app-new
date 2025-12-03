<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/profile
     * Return the logged-in user's full profile.
     */
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'profilePhotoFile',
            'coverPhotoFile',
            'cityRelation',
        ]);

        return response()->json([
            'success' => true,
            'message' => null,
            'data'    => new UserProfileResource($user),
        ]);
    }

    /**
     * PUT/PATCH /api/v1/profile
     * Update the logged-in user's profile.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Basic text fields
        if (array_key_exists('first_name', $data)) {
            $user->first_name = $data['first_name'];
        }
        if (array_key_exists('last_name', $data)) {
            $user->last_name = $data['last_name'];
        }
        if (array_key_exists('company_name', $data)) {
            $user->company_name = $data['company_name'];
        }
        if (array_key_exists('designation', $data)) {
            $user->designation = $data['designation'];
        }

        // Derive display_name if names changed
        if (isset($data['first_name']) || isset($data['last_name'])) {
            $first = $user->first_name ?? '';
            $last  = $user->last_name ?? '';
            $user->display_name = trim($first . ' ' . $last) ?: $user->email;
        }

        // about -> short_bio
        if (array_key_exists('about', $data)) {
            $user->short_bio = $data['about'];
        }

        // Extra profile fields
        if (array_key_exists('gender', $data)) {
            $user->gender = $data['gender'];
        }
        if (array_key_exists('dob', $data)) {
            $user->dob = $data['dob'];
        }
        if (array_key_exists('experience_years', $data)) {
            $user->experience_years = $data['experience_years'];
        }
        if (array_key_exists('experience_summary', $data)) {
            $user->experience_summary = $data['experience_summary'];
        }

        // city text + city_id
        if (array_key_exists('city', $data)) {
            $user->city = $data['city'];
        }
        if (array_key_exists('city_id', $data)) {
            $user->city_id = $data['city_id'];
        }

        // JSON fields
        if (array_key_exists('skills', $data)) {
            $user->skills = $data['skills'] ?? [];
        }
        if (array_key_exists('interests', $data)) {
            $user->interests = $data['interests'] ?? [];
        }
        if (array_key_exists('social_links', $data)) {
            $user->social_links = $data['social_links'] ?? [];
        }

        // File IDs
        if (array_key_exists('profile_photo_id', $data)) {
            $user->profile_photo_file_id = $data['profile_photo_id'];
        }
        if (array_key_exists('cover_photo_id', $data)) {
            $user->cover_photo_file_id = $data['cover_photo_id'];
        }

        $user->save();

        $user->refresh()->load([
            'profilePhotoFile',
            'coverPhotoFile',
            'cityRelation',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => new UserProfileResource($user),
        ]);
    }
}
