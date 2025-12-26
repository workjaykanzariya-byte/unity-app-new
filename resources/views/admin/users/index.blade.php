@extends('admin.layout')

@section('title', 'Users')

@section('content')
<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <form method="GET" class="flex flex-1 items-center gap-3">
            <div class="flex flex-1 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l3.817 3.817a1 1 0 01-1.414 1.414l-3.817-3.817A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search name or email" class="w-full border-0 bg-transparent text-sm focus:ring-0" />
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-slate-800">Search</button>
            @if($search)
                <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">User</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Email</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Membership</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Sponsored</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="odd:bg-slate-50 hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if($user->adminProfilePhotoUrl())
                                    <img src="{{ $user->adminProfilePhotoUrl() }}" alt="{{ $user->display_name ?? $user->first_name }}" class="h-10 w-10 rounded-full object-cover" />
                                @else
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-sm font-semibold text-slate-600">
                                        {{ strtoupper(mb_substr($user->first_name,0,1).mb_substr($user->last_name ?? '',0,1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-slate-900">{{ $user->display_name ?? ($user->first_name.' '.$user->last_name) }}</div>
                                    <div class="text-xs text-slate-500">ID: {{ $user->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $user->membership_status }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $user->is_sponsored_member ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center rounded-lg bg-white px-3 py-2 text-xs font-semibold text-slate-900 ring-1 ring-slate-300 hover:bg-slate-50">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-2">
        {{ $users->links() }}
    </div>
</div>
@endsection
