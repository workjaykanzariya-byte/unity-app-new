@extends('admin.layouts.app')

@section('title', 'User Details')

@section('content')
@php
    $displayName = $user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? '')) ?: ($user->email ?? 'User');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">User Details</h5>
        <small class="text-muted">View profile and manage admin role</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back to Users</a>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode($displayName) }}&background=6f42c1&color=fff" class="rounded-circle" width="64" height="64" alt="User Avatar">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0">{{ $displayName }}</h5>
                            @if ($currentRoleId)
                                @php
                                    $currentRole = $roles->firstWhere('id', $currentRoleId);
                                @endphp
                                @if ($currentRole)
                                    <span class="badge bg-primary-subtle text-primary">{{ $currentRole->name }}</span>
                                @endif
                            @endif
                        </div>
                        <div class="text-muted">{{ $user->email }}</div>
                        <div class="text-muted">{{ $user->phone ?? '—' }}</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-2">Membership</div>
                            <div class="fw-semibold">{{ $user->membership_status ?? 'Free' }}</div>
                            <div class="text-muted small">Expires: {{ optional($user->membership_expiry)->format('Y-m-d') ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-2">City</div>
                            <div class="fw-semibold">{{ $user->city->name ?? $user->city ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-2">Coins</div>
                            <div class="fw-semibold">{{ number_format($user->coins_balance ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-2">Joined</div>
                            <div class="fw-semibold">{{ optional($user->created_at)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Assign a Role</h6>
                    @if ($currentRoleId && ($currentRole = $roles->firstWhere('id', $currentRoleId)))
                        <span class="badge bg-primary-subtle text-primary">{{ $currentRole->name }}</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary">No role</span>
                    @endif
                </div>
                @if (!$canAssign)
                    <div class="alert alert-info">Only Global Admin can assign roles.</div>
                @endif
                <form method="POST" action="{{ route('admin.users.updateRole', $user) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Select Role</label>
                        <select name="role_id" class="form-select" @disabled(!$canAssign)>
                            <option value="">Choose role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id', $currentRoleId) == $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button class="btn btn-primary w-100" type="submit" @disabled(!$canAssign)>Save Role</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
