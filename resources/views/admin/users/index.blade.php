@extends('admin.layout')

@section('title', 'Users')

@section('content')
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center space-x-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search users" class="w-64 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-500">Search</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Photo</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Email</th>
                        @if($hasMembershipStatus)
                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Membership Status</th>
                        @endif
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Created At</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($users as $user)
                        @php
                            $profilePhotoUrl = $user->profile_photo_url ?? null;
                            if (! $profilePhotoUrl && $user->profile_photo_id) {
                                $profilePhotoUrl = url('/api/v1/files/' . $user->profile_photo_id);
                            }
                            if (! $profilePhotoUrl && $user->profile_photo_file_id) {
                                $profilePhotoUrl = url('/api/v1/files/' . $user->profile_photo_file_id);
                            }
                        @endphp
                        <tr>
                            <td class="px-4 py-2">
                                @if($profilePhotoUrl)
                                    <img src="{{ $profilePhotoUrl }}" alt="Profile photo" class="h-10 w-10 rounded-full object-cover">
                                @else
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-sm">N/A</div>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 font-medium">{{ $user->display_name ?? trim($user->first_name . ' ' . ($user->last_name ?? '')) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $user->email }}</td>
                            @if($hasMembershipStatus)
                                <td class="px-4 py-2 text-sm text-gray-700">{{ $user->membership_status ?? '—' }}</td>
                            @endif
                            <td class="px-4 py-2 text-sm text-gray-700">{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="px-3 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    </div>
@endsection
