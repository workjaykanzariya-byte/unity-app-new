@extends('admin.layout')

@section('title', 'Edit User')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-900">Edit User</h2>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Back to list</a>
    </div>

    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Display Name</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $user->display_name) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700">Membership Status</label>
                <input type="text" name="membership_status" value="{{ old('membership_status', $user->membership_status) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" />
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_sponsored_member" value="1" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500" {{ old('is_sponsored_member', $user->is_sponsored_member) ? 'checked' : '' }} />
                    <span>Sponsored Member</span>
                </label>
            </div>

            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_gdpr_exported" value="1" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500" {{ old('is_gdpr_exported', $user->is_gdpr_exported) ? 'checked' : '' }} />
                    <span>GDPR Exported</span>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Industry Tags (JSON array)</label>
                    <textarea name="industry_tags" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" rows="2">{{ old('industry_tags', json_encode($user->industry_tags ?? [])) }}</textarea>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Target Regions (JSON array)</label>
                    <textarea name="target_regions" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" rows="2">{{ old('target_regions', json_encode($user->target_regions ?? [])) }}</textarea>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Target Business Categories (JSON array)</label>
                    <textarea name="target_business_categories" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" rows="2">{{ old('target_business_categories', json_encode($user->target_business_categories ?? [])) }}</textarea>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Hobbies & Interests (JSON array)</label>
                    <textarea name="hobbies_interests" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" rows="2">{{ old('hobbies_interests', json_encode($user->hobbies_interests ?? [])) }}</textarea>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Leadership Roles (JSON array)</label>
                    <textarea name="leadership_roles" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" rows="2">{{ old('leadership_roles', json_encode($user->leadership_roles ?? [])) }}</textarea>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Special Recognitions (JSON array)</label>
                    <textarea name="special_recognitions" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-200" rows="2">{{ old('special_recognitions', json_encode($user->special_recognitions ?? [])) }}</textarea>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
