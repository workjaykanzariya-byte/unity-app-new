@extends('admin.layouts.app')

@section('title', $circle->name . ' Circle')

@section('content')
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0">{{ $circle->name }}</h5>
        <small class="text-muted">Circle details and members</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.circles.edit', $circle) }}" class="btn btn-outline-primary btn-sm">Edit Circle</a>
        <a href="{{ route('admin.circles.index') }}" class="btn btn-outline-secondary btn-sm">Back to Circles</a>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>There were some problems with your input.</strong>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Circle Overview</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge badge-soft-secondary text-uppercase">{{ $circle->status ?? 'pending' }}</span>
                    <span class="badge bg-light text-dark text-uppercase">{{ $circle->type ?? '—' }}</span>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Slug</dt>
                    <dd class="col-sm-8">{{ $circle->slug ?? '—' }}</dd>

                    <dt class="col-sm-4">City</dt>
                    <dd class="col-sm-8">{{ $circle->city?->name ?? '—' }}</dd>

                    <dt class="col-sm-4">Country</dt>
                    <dd class="col-sm-8">{{ $circle->city?->country ?? '—' }}</dd>

                    <dt class="col-sm-4">Founder</dt>
                    <dd class="col-sm-8">{{ $circle->founder?->display_name ?? '—' }}</dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8">{{ optional($circle->created_at)->format('Y-m-d H:i') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header fw-semibold">Narrative & Tags</div>
            <div class="card-body">
                <p class="text-muted mb-2"><strong>Description:</strong> {{ $circle->description ?: '—' }}</p>
                <p class="text-muted mb-2"><strong>Purpose:</strong> {{ $circle->purpose ?: '—' }}</p>
                <p class="text-muted mb-2"><strong>Announcement:</strong> {{ $circle->announcement ?: '—' }}</p>
                <p class="text-muted mb-0"><strong>Industry Tags:</strong> {{ $circle->industry_tags ? implode(', ', $circle->industry_tags) : '—' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header fw-semibold">Members</div>
    <div class="card-body">
        <form action="{{ route('admin.circles.members.store', $circle) }}" method="POST" class="row g-2 align-items-end mb-4">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Search in dropdown</label>
                <input type="text" id="userSelectSearch" class="form-control" placeholder="Type to filter users by name or email">
            </div>
            <div class="col-md-6">
                <label class="form-label">Select User</label>
                <select id="userSelect" name="user_id" class="form-select" required>
                    <option value="">Select user</option>
                    @foreach ($allUsers as $userOption)
                        @php
                            $optionName = $userOption->display_name
                                ?? trim($userOption->first_name . ' ' . ($userOption->last_name ?? ''));
                            $optionLabel = trim($optionName);
                            if ($userOption->email) {
                                $optionLabel = $optionLabel !== '' ? $optionLabel . ' - ' . $userOption->email : $userOption->email;
                            }
                        @endphp
                        <option value="{{ $userOption->id }}">{{ $optionLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100">Add Member</button>
            </div>
        </form>

        <div class="table-responsive" style="overflow-x: auto;">
            <table class="table align-middle" style="white-space: nowrap;">
                <thead class="table-light">
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($circle->members as $membership)
                        @php
                            $member = $membership->user;
                            $memberName = $member?->display_name ?? trim(($member?->first_name ?? '') . ' ' . ($member?->last_name ?? ''));
                        @endphp
                        <tr>
                            <td>{{ $memberName ?: '—' }}</td>
                            <td>{{ $member?->email ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.circles.members.update', [$circle, $membership]) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" class="form-select form-select-sm">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role }}" @selected($membership->role === $role)>{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Update</button>
                                </form>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark text-uppercase">{{ $membership->status ?? 'pending' }}</span>
                            </td>
                            <td>{{ optional($membership->joined_at ?? $membership->created_at)->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.circles.members.destroy', [$circle, $membership]) }}" onsubmit="return confirm('Remove this member from the circle?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No members assigned yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const searchInput = document.getElementById('userSelectSearch');
        const select = document.getElementById('userSelect');
        if (!searchInput || !select) {
            return;
        }

        const options = Array.from(select.options);

        const filterOptions = () => {
            const query = searchInput.value.trim().toLowerCase();
            options.forEach((option, index) => {
                if (index === 0) {
                    option.style.display = '';
                    return;
                }
                const text = option.text.toLowerCase();
                option.style.display = text.includes(query) ? '' : 'none';
            });
        };

        searchInput.addEventListener('keyup', filterOptions);
    })();
</script>
@endpush
