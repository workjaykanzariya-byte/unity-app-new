@extends('admin.layouts.app')

@section('title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Users</h5>
        <small class="text-muted">Manage platform users</small>
    </div>
</div>

<div class="card p-3 mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name or email">
        </div>
        <div class="col-md-3">
            <label class="form-label">Membership</label>
            <select name="membership_status" class="form-select">
                <option value="">All</option>
                @foreach ($membershipStatuses as $status)
                    <option value="{{ $status }}" @selected($filters['membership_status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">City</label>
            <select name="city_id" class="form-select">
                <option value="">All</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected($filters['city_id'] == $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a class="btn btn-outline-secondary w-100" href="{{ route('admin.users.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Membership</th>
                    <th>City</th>
                    <th>Coins</th>
                    <th>Last Login</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $user->display_name ?? ($user->first_name . ' ' . $user->last_name) }}</div>
                            <small class="text-muted">{{ $user->email }}</small>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $user->membership_status ?? 'Free' }}</span></td>
                        <td>{{ $user->city->name ?? $user->city ?? '—' }}</td>
                        <td>{{ number_format($user->coins_balance ?? 0) }}</td>
                        <td>{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><span class="badge bg-success-subtle text-success">Active</span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-light">View</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">
        {{ $users->links() }}
    </div>
</div>
@endsection
