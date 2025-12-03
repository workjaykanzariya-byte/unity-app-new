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

        $user->forceFill([
            'first_name'            => $data['first_name'] ?? $user->first_name,
            'last_name'             => $data['last_name'] ?? $user->last_name,
            'company_name'          => $data['company_name'] ?? $user->company_name,
            'designation'           => $data['designation'] ?? $user->designation,
            'short_bio'             => array_key_exists('about', $data) ? $data['about'] : $user->short_bio,
            'gender'                => $data['gender'] ?? $user->gender,
            'dob'                   => $data['dob'] ?? $user->dob,
            'experience_years'      => $data['experience_years'] ?? $user->experience_years,
            'experience_summary'    => $data['experience_summary'] ?? $user->experience_summary,
            'city_id'               => array_key_exists('city_id', $data) ? $data['city_id'] : $user->city_id,
            'city'                  => $data['city'] ?? $user->city,
            'skills'                => $data['skills'] ?? $user->skills ?? [],
            'interests'             => $data['interests'] ?? $user->interests ?? [],
            'social_links'          => $data['social_links'] ?? $user->social_links ?? [],
            'profile_photo_file_id' => array_key_exists('profile_photo_id', $data) ? $data['profile_photo_id'] : $user->profile_photo_file_id,
            'cover_photo_file_id'   => array_key_exists('cover_photo_id', $data) ? $data['cover_photo_id'] : $user->cover_photo_file_id,
        ]);

        if (array_key_exists('first_name', $data) || array_key_exists('last_name', $data)) {
            $user->display_name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: $user->email;
        }

        $user->saveOrFail();

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
