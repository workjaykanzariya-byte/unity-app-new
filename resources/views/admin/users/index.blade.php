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
    <form class="row g-2 align-items-end" method="GET">
        <div class="col-md-4">
            <label class="form-label">Search (name or email)</label>
            <input type="text" name="q" value="{{ $filters['search'] }}" class="form-control" placeholder="Name or email">
        </div>
        <div class="col-md-2">
            <label class="form-label">Membership</label>
            <select name="membership_status" class="form-select">
                <option value="all">All</option>
                @foreach ($membershipStatuses as $status)
                    <option value="{{ $status }}" @selected($filters['membership_status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">City</label>
            <select name="city_id" class="form-select">
                <option value="all">All</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected($filters['city_id'] == $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" value="{{ $filters['phone'] }}" class="form-control" placeholder="Phone">
        </div>
        <div class="col-md-2">
            <label class="form-label">Company</label>
            <input type="text" name="company_name" value="{{ $filters['company_name'] }}" class="form-control" placeholder="Company name">
        </div>
        <div class="col-md-12 d-flex gap-2 mt-2">
            <button class="btn btn-primary">Filter</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <label for="perPage" class="form-label mb-0 small text-muted">Rows per page:</label>
            <select id="perPage" name="per_page" class="form-select form-select-sm" style="width: 90px;">
                @foreach ([10, 20, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="small text-muted">
            @if($users->total() > 0)
                Records {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }}
            @else
                No records found
            @endif
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" class="form-check-input" id="select-all">
                    </th>
                    <th>
                        <a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort' => 'display_name', 'dir' => $filters['sort'] === 'display_name' && $filters['dir'] === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            User
                            @if ($filters['sort'] === 'display_name')
                                <i class="bi bi-arrow-{{ $filters['dir'] === 'asc' ? 'up' : 'down' }}-short"></i>
                            @endif
                        </a>
                    </th>
                    <th>Company</th>
                    <th>Membership</th>
                    <th>City</th>
                    <th>
                        <a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort' => 'coins_balance', 'dir' => $filters['sort'] === 'coins_balance' && $filters['dir'] === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            Coins
                            @if ($filters['sort'] === 'coins_balance')
                                <i class="bi bi-arrow-{{ $filters['dir'] === 'asc' ? 'up' : 'down' }}-short"></i>
                            @endif
                        </a>
                    </th>
                    <th>
                        <a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort' => 'last_login_at', 'dir' => $filters['sort'] === 'last_login_at' && $filters['dir'] === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            Last Login
                            @if ($filters['sort'] === 'last_login_at')
                                <i class="bi bi-arrow-{{ $filters['dir'] === 'asc' ? 'up' : 'down' }}-short"></i>
                            @endif
                        </a>
                    </th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                <tr class="bg-light align-middle">
                    <th></th>
                    <th>
                        <input type="text" name="q" form="grid-filters" class="form-control form-control-sm" placeholder="Name or email" value="{{ $filters['search'] }}">
                    </th>
                    <th>
                        <input type="text" name="company_name" form="grid-filters" class="form-control form-control-sm" placeholder="Company" value="{{ $filters['company_name'] }}">
                    </th>
                    <th>
                        <select name="membership_status" form="grid-filters" class="form-select form-select-sm">
                            <option value="all">All</option>
                            @foreach ($membershipStatuses as $status)
                                <option value="{{ $status }}" @selected($filters['membership_status'] === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th>
                        <select name="city_id" form="grid-filters" class="form-select form-select-sm">
                            <option value="all">All</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected($filters['city_id'] == $city->id)>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th>
                        <input type="text" name="coins_balance" class="form-control form-control-sm" placeholder="—" disabled>
                    </th>
                    <th>
                        <input type="text" name="last_login_at" class="form-control form-control-sm" placeholder="—" disabled>
                    </th>
                    <th>
                        <select class="form-select form-select-sm" disabled><option>Any</option></select>
                    </th>
                    <th class="text-end">
                        <form id="grid-filters" method="GET" class="d-flex gap-2 justify-content-end">
                            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                            <input type="hidden" name="dir" value="{{ $filters['dir'] }}">
                            <button class="btn btn-sm btn-primary">Apply</button>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.index') }}">Reset</a>
                        </form>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $name = $user->display_name ?? trim($user->first_name . ' ' . $user->last_name);
                        $avatar = $user->profile_photo_url ?? ($user->profile_photo_file_id ? url('/api/v1/files/' . $user->profile_photo_file_id) : null);
                        $cityName = $user->city->name ?? $user->city ?? '—';
                        $isActive = $user->deleted_at === null;
                        $detailsId = 'details-' . $user->id;
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input row-checkbox">
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; overflow: hidden;">
                                    @if ($avatar)
                                        <img src="{{ $avatar }}" alt="{{ $name }}" class="img-fluid">
                                    @else
                                        <span class="text-muted">{{ strtoupper(substr($name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $name ?: 'Unnamed User' }}</div>
                                    <div class="text-muted small">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->company_name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary text-uppercase">{{ $user->membership_status ?? 'Free' }}</span>
                        </td>
                        <td>{{ $cityName }}</td>
                        <td>{{ number_format($user->coins_balance ?? 0) }}</td>
                        <td>{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                {{ $isActive ? 'Active' : 'Deleted' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ url('/admin/users/' . $user->id) }}" class="btn btn-outline-secondary">View</a>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $detailsId }}" aria-expanded="false" aria-controls="{{ $detailsId }}">Details</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse-row">
                        <td colspan="9" class="p-0 border-0">
                            <div class="collapse" id="{{ $detailsId }}">
                                <div class="p-3 bg-light border-top">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr><th class="w-50 text-muted">ID</th><td class="text-break">{{ $user->id }}</td></tr>
                                                <tr><th class="text-muted">Phone</th><td>{{ $user->phone ?? '—' }}</td></tr>
                                                <tr><th class="text-muted">Business Type</th><td>{{ $user->business_type ?? '—' }}</td></tr>
                                                <tr><th class="text-muted">Industry Tags</th><td>{{ is_array($user->industry_tags) ? implode(', ', $user->industry_tags) : ($user->industry_tags ?? '—') }}</td></tr>
                                                <tr><th class="text-muted">Membership Expiry</th><td>{{ optional($user->membership_expiry)->format('Y-m-d') ?? '—' }}</td></tr>
                                                <tr><th class="text-muted">City ID</th><td>{{ $user->city_id ?? '—' }}</td></tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <table class="table table-sm">
                                                <tr><th class="w-50 text-muted">Influencer Stars</th><td>{{ $user->influencer_stars ?? '—' }}</td></tr>
                                                <tr><th class="text-muted">Membership Status</th><td>{{ $user->membership_status ?? 'Free' }}</td></tr>
                                                <tr><th class="text-muted">Coins Balance</th><td>{{ number_format($user->coins_balance ?? 0) }}</td></tr>
                                                <tr><th class="text-muted">Last Login</th><td>{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                                                <tr><th class="text-muted">Created At</th><td>{{ optional($user->created_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                                                <tr><th class="text-muted">Updated At</th><td>{{ optional($user->updated_at)->format('Y-m-d H:i') ?? '—' }}</td></tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <div>
            {{ $users->withQueryString()->links() }}
        </div>
        <div class="small text-muted">
            @if($users->total() > 0)
                Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }} records
            @else
                No records
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('select-all');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const perPage = document.getElementById('perPage');
        const filterForm = document.getElementById('grid-filters');

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
            });
        }

        if (perPage) {
            perPage.addEventListener('change', () => {
                const params = new URLSearchParams(window.location.search);
                params.set('per_page', perPage.value);
                window.location = `${window.location.pathname}?${params.toString()}`;
            });
        }

        filterForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(window.location.search);
            for (const [key, value] of formData.entries()) {
                params.set(key, value);
            }
            params.set('per_page', document.getElementById('perPage')?.value || '{{ $filters['per_page'] }}');
            window.location = `${window.location.pathname}?${params.toString()}`;
        });
    });
</script>
@endpush
@endsection
