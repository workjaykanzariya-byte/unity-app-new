@extends('admin.layout')

@section('title', 'Users')

@section('content')
    <div class="bg-white p-6 rounded-lg shadow-sm space-y-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search users by name or email" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <div class="flex items-center gap-2">
                @if($search !== '')
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-800">Clear</a>
                @endif
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">Search</button>
            </div>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700">Photo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700">Email</th>
                        @if($hasMembershipStatus)
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700">Membership Status</th>
                        @endif
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700">Created At</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                        @php
                            $profilePhotoUrl = $user->adminProfilePhotoUrl();
                            $initials = strtoupper(trim(($user->first_name[0] ?? '') . ($user->last_name[0] ?? '')));
                            if ($initials === '') {
                                $initials = 'NA';
                            }
                        @endphp
                        <tr class="{{ $loop->even ? 'bg-slate-50' : 'bg-white' }} hover:bg-slate-100 transition">
                            <td class="px-4 py-3">
                                <div class="relative h-12 w-12">
                                    @if($profilePhotoUrl)
                                        <img src="{{ $profilePhotoUrl }}" alt="Profile photo" class="h-12 w-12 rounded-full object-cover border border-gray-200" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                    @endif
                                    <div class="absolute inset-0 {{ $profilePhotoUrl ? 'hidden' : '' }} rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-semibold">
                                        {{ $initials }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ $user->display_name ?? trim($user->first_name . ' ' . ($user->last_name ?? '')) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $user->email }}</td>
                            @if($hasMembershipStatus)
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $user->membership_status ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md bg-indigo-600 text-white hover:bg-indigo-500">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $hasMembershipStatus ? 6 : 5 }}" class="px-4 py-6 text-center text-gray-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
@endsection
