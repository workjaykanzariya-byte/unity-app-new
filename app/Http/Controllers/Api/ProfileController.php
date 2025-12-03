<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Profile\StoreUserLinkRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UpdateUserLinkRequest;
use App\Http\Resources\UserLinkResource;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;

class ProfileController extends BaseApiController
{
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'profilePhotoFile',
            'coverPhotoFile',
            'userLinks',
        ]);

        return $this->success(new UserProfileResource($user));
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

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

        if (isset($data['first_name']) || isset($data['last_name'])) {
            $user->display_name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email;
        }

        if (array_key_exists('about', $data)) {
            $user->short_bio = $data['about'];
        }

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

        if (array_key_exists('city', $data)) {
            $user->city = $data['city'];
        }

        if (array_key_exists('skills', $data)) {
            $user->skills = $data['skills'] ?? [];
        }

        if (array_key_exists('interests', $data)) {
            $user->interests = $data['interests'] ?? [];
        }

        if (array_key_exists('social_links', $data)) {
            $user->social_links = $data['social_links'] ?? [];
        }

        if (array_key_exists('profile_photo_id', $data)) {
            $user->profile_photo_file_id = $data['profile_photo_id'];
        }

        if (array_key_exists('cover_photo_id', $data)) {
            $user->cover_photo_file_id = $data['cover_photo_id'];
        }

        $user->save();

        $user->load(['profilePhotoFile', 'coverPhotoFile', 'userLinks']);

        return $this->success(new UserProfileResource($user), 'Profile updated successfully');
    }

    public function links(Request $request)
    {
        $user = $request->user();
        $links = $user->userLinks()->orderByDesc('created_at')->get();

        return $this->success(UserLinkResource::collection($links));
    }

    public function storeLink(StoreUserLinkRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $link = $user->userLinks()->create($data);

        return $this->success(new UserLinkResource($link), 'Link created successfully', 201);
    }

    public function updateLink(UpdateUserLinkRequest $request, string $id)
    {
        $user = $request->user();
        $data = $request->validated();

        $link = $user->userLinks()->where('id', $id)->first();

        if (! $link) {
            return $this->error('Link not found', 404);
        }

        $link->fill($data);
        $link->save();

        return $this->success(new UserLinkResource($link), 'Link updated successfully');
    }

    public function destroyLink(Request $request, string $id)
    {
        $user = $request->user();
        $link = $user->userLinks()->where('id', $id)->first();

        if (! $link) {
            return $this->error('Link not found', 404);
        }

        $link->delete();

        return $this->success(null, 'Link deleted successfully');
    }
}
