@extends('admin.layout')

@section('title', 'Edit User')

@section('content')
    <div class="bg-white p-6 rounded-lg shadow-sm space-y-6">
        <div class="flex items-center space-x-4">
            @php
                $profilePhotoUrl = $user->profile_photo_url ?? null;
                if (! $profilePhotoUrl && $user->profile_photo_id) {
                    $profilePhotoUrl = url('/api/v1/files/' . $user->profile_photo_id);
                }
                if (! $profilePhotoUrl && $user->profile_photo_file_id) {
                    $profilePhotoUrl = url('/api/v1/files/' . $user->profile_photo_file_id);
                }
            @endphp
            @if($profilePhotoUrl)
                <img src="{{ $profilePhotoUrl }}" alt="Profile photo" class="h-20 w-20 rounded-full object-cover">
            @else
                <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">N/A</div>
            @endif
            <div>
                <div class="text-xl font-semibold text-gray-800">{{ $user->display_name ?? trim($user->first_name . ' ' . ($user->last_name ?? '')) }}</div>
                <div class="text-gray-600">{{ $user->email }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($fields as $field)
                    @php
                        $cast = $casts[$field] ?? null;
                        $value = old($field, $user->{$field});
                        $isArray = $cast === 'array';
                        $isTextarea = str_contains($field, 'summary') || str_contains($field, 'bio') || $isArray;

                        if ($isArray && is_array($value)) {
                            $value = implode(', ', $value);
                        }

                        $inputType = 'text';
                        if ($field === 'email') {
                            $inputType = 'email';
                        } elseif (in_array($field, ['coins_balance', 'members_introduced_count', 'influencer_stars', 'experience_years'], true)) {
                            $inputType = 'number';
                        } elseif (in_array($field, ['dob'], true)) {
                            $inputType = 'date';
                        } elseif (in_array($field, ['membership_expiry', 'last_login_at', 'gdpr_deleted_at', 'anonymized_at'], true)) {
                            $inputType = 'datetime-local';
                            if ($value) {
                                $value = optional($value instanceof \DateTimeInterface ? $value : \Illuminate\Support\Carbon::parse($value))->format('Y-m-d\TH:i');
                            }
                        }
                    @endphp
                    <div class="flex flex-col">
                        <label for="{{ $field }}" class="text-sm font-medium text-gray-700 capitalize">{{ str_replace('_', ' ', $field) }}</label>
                        @if($isTextarea)
                            <textarea name="{{ $field }}" id="{{ $field }}" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3">{{ $value }}</textarea>
                        @else
                            <input type="{{ $inputType }}" name="{{ $field }}" id="{{ $field }}" value="{{ $value }}" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-800">Roles</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($roles as $role)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                {{ in_array($role->id, $userRoleIds, true) ? 'checked' : '' }} {{ $canManageRoles ? '' : 'disabled' }}>
                            <span class="text-sm text-gray-800">{{ $role->name }} <span class="text-gray-500">({{ $role->key }})</span></span>
                        </label>
                    @endforeach
                </div>
                @unless($canManageRoles)
                    <p class="text-sm text-gray-600">Only global admin can manage roles.</p>
                @endunless
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">Save</button>
            </div>
        </form>
    </div>
@endsection
