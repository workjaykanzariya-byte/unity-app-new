@extends('admin.layouts.app')

@section('title', 'User Details')

@section('content')
@php
    $displayName = $user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? '')) ?: ($user->email ?? 'User');
    $avatarUrl = $user->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=6f42c1&color=fff';
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
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="{{ $avatarUrl }}" class="rounded-circle border" width="72" height="72" alt="User Avatar">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h5 class="mb-0">{{ $displayName }}</h5>
                            @if ($currentRoleId)
                                @php
                                    $currentRole = $roles->firstWhere('id', $currentRoleId);
                                @endphp
                                @if ($currentRole)
                                    <span class="badge bg-primary-subtle text-primary">{{ $currentRole->name }}</span>
                                @endif
                            @endif
                            <span class="badge bg-info-subtle text-info">{{ $user->membership_status ?? 'Free' }}</span>
                        </div>
                        <div class="text-muted">{{ $user->email }}</div>
                        <div class="text-muted">{{ $user->phone ?? '—' }}</div>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-1">Company</div>
                            <div class="fw-semibold">{{ $user->company_name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-1">Business Type</div>
                            <div class="fw-semibold">{{ $user->business_type ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-1">City</div>
                            <div class="fw-semibold">{{ $user->city->name ?? $user->city ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-1">Public Profile</div>
                            <div class="fw-semibold">{{ $user->public_profile_slug ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-1">Last Login</div>
                            <div class="fw-semibold">{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted text-uppercase small mb-1">User ID</div>
                            <div class="fw-semibold text-break">{{ $user->id }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!empty($details))
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Details</h6>
                    <div class="row g-3">
                        @foreach ($details as $detail)
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="text-muted text-uppercase small mb-1">{{ $detail['label'] }}</div>
                                    <div class="fw-semibold text-break">{{ $detail['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($skills))
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-2">Skills</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($skills as $skill)
                            <span class="badge bg-secondary-subtle text-secondary">{{ $skill }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($interests))
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-2">Interests</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($interests as $interest)
                            <span class="badge bg-secondary-subtle text-secondary">{{ $interest }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($socialLinks))
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-2">Social Links</h6>
                    <ul class="list-unstyled mb-0">
                        @foreach ($socialLinks as $link)
                            <li class="mb-2">
                                <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="text-decoration-none">
                                    <i class="bi bi-link-45deg me-1"></i>{{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
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
