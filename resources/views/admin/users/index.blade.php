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
    <form class="row g-2 align-items-end" method="GET" id="usersFiltersForm">
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
                    <th>ID</th>
                    <th>
                        <a href="{{ route('admin.users.index', array_merge(request()->query(), ['sort' => 'display_name', 'dir' => $filters['sort'] === 'display_name' && $filters['dir'] === 'asc' ? 'desc' : 'asc'])) }}" class="text-decoration-none text-dark">
                            User
                            @if ($filters['sort'] === 'display_name')
                                <i class="bi bi-arrow-{{ $filters['dir'] === 'asc' ? 'up' : 'down' }}-short"></i>
                            @endif
                        </a>
                    </th>
                    <th>Phone</th>
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
                        <input type="text" class="form-control form-control-sm" placeholder="—" disabled>
                    </th>
                    <th>
                        <input type="text" name="q" form="grid-filters" class="form-control form-control-sm" placeholder="Name or email" value="{{ $filters['search'] }}">
                    </th>
                    <th>
                        <input type="text" name="phone" form="grid-filters" class="form-control form-control-sm" placeholder="Phone" value="{{ $filters['phone'] }}">
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
                        $shortId = substr($user->id, 0, 8);
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input row-checkbox">
                        </td>
                        <td>
                            <span class="font-monospace" data-bs-toggle="tooltip" title="{{ $user->id }}">{{ $shortId }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px; overflow: hidden;">
                                    @if ($avatar)
                                        <img src="{{ $avatar }}" alt="{{ $name }}" class="img-fluid w-100 h-100 object-fit-cover">
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
                        <td>{{ $user->phone ?? '—' }}</td>
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
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-outline-secondary">Edit</a>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $detailsId }}" aria-expanded="false" aria-controls="{{ $detailsId }}">Details</button>
                            </div>
                        </td>
                    </tr>
                    <tr class="collapse-row">
                        <td colspan="11" class="p-0 border-0">
                            <div class="collapse" id="{{ $detailsId }}">
                                <div class="p-3 bg-light border-top">
                                    @php
                                        $fields = [
                                            ['label' => 'ID', 'value' => $user->id],
                                            ['label' => 'Email', 'value' => $user->email],
                                            ['label' => 'Phone', 'value' => $user->phone],
                                            ['label' => 'First Name', 'value' => $user->first_name],
                                            ['label' => 'Last Name', 'value' => $user->last_name],
                                            ['label' => 'Display Name', 'value' => $user->display_name],
                                            ['label' => 'Designation', 'value' => $user->designation],
                                            ['label' => 'Company Name', 'value' => $user->company_name],
                                            ['label' => 'Profile Photo URL', 'value' => $user->profile_photo_url],
                                            ['label' => 'Profile Photo File ID', 'value' => $user->profile_photo_file_id],
                                            ['label' => 'Cover Photo File ID', 'value' => $user->cover_photo_file_id],
                                            ['label' => 'Short Bio', 'value' => $user->short_bio],
                                            ['label' => 'Long Bio (HTML)', 'value' => $user->long_bio_html],
                                            ['label' => 'Industry Tags', 'value' => $user->industry_tags, 'type' => 'json'],
                                            ['label' => 'Business Type', 'value' => $user->business_type],
                                            ['label' => 'Turnover Range', 'value' => $user->turnover_range],
                                            ['label' => 'City ID', 'value' => $user->city_id],
                                            ['label' => 'City (string)', 'value' => $user->city],
                                            ['label' => 'Membership Status', 'value' => $user->membership_status],
                                            ['label' => 'Membership Expiry', 'value' => $user->membership_expiry, 'type' => 'date'],
                                            ['label' => 'Coins Balance', 'value' => $user->coins_balance],
                                            ['label' => 'Introduced By', 'value' => $user->introduced_by],
                                            ['label' => 'Members Introduced Count', 'value' => $user->members_introduced_count],
                                            ['label' => 'Influencer Stars', 'value' => $user->influencer_stars],
                                            ['label' => 'Target Regions', 'value' => $user->target_regions, 'type' => 'json'],
                                            ['label' => 'Target Business Categories', 'value' => $user->target_business_categories, 'type' => 'json'],
                                            ['label' => 'Hobbies / Interests', 'value' => $user->hobbies_interests, 'type' => 'json'],
                                            ['label' => 'Leadership Roles', 'value' => $user->leadership_roles, 'type' => 'json'],
                                            ['label' => 'Is Sponsored Member', 'value' => $user->is_sponsored_member, 'type' => 'bool'],
                                            ['label' => 'Public Profile Slug', 'value' => $user->public_profile_slug],
                                            ['label' => 'Special Recognitions', 'value' => $user->special_recognitions, 'type' => 'json'],
                                            ['label' => 'Gender', 'value' => $user->gender],
                                            ['label' => 'Date of Birth', 'value' => $user->dob, 'type' => 'date'],
                                            ['label' => 'Experience (years)', 'value' => $user->experience_years],
                                            ['label' => 'Experience Summary', 'value' => $user->experience_summary],
                                            ['label' => 'Skills', 'value' => $user->skills, 'type' => 'json'],
                                            ['label' => 'Interests', 'value' => $user->interests, 'type' => 'json'],
                                            ['label' => 'GDPR Deleted At', 'value' => $user->gdpr_deleted_at, 'type' => 'date'],
                                            ['label' => 'Anonymized At', 'value' => $user->anonymized_at, 'type' => 'date'],
                                            ['label' => 'Is GDPR Exported', 'value' => $user->is_gdpr_exported, 'type' => 'bool'],
                                            ['label' => 'Last Login', 'value' => $user->last_login_at, 'type' => 'date'],
                                            ['label' => 'Created At', 'value' => $user->created_at, 'type' => 'date'],
                                            ['label' => 'Updated At', 'value' => $user->updated_at, 'type' => 'date'],
                                            ['label' => 'Deleted At', 'value' => $user->deleted_at, 'type' => 'date'],
                                        ];

                                        $chunks = array_chunk($fields, (int) ceil(count($fields) / 2));
                                        $renderValue = function ($value, $type = 'text') {
                                            if ($type === 'bool') {
                                                $class = $value ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                                                $label = $value ? 'Yes' : 'No';
                                                return '<span class="badge ' . $class . '">' . $label . '</span>';
                                            }

                                            if ($type === 'date') {
                                                if (! $value) {
                                                    return '—';
                                                }

                                                $isDate = $value instanceof \DateTimeInterface;
                                                $formatted = $isDate ? $value->format('Y-m-d H:i') : (string) $value;
                                                $raw = $isDate && method_exists($value, 'toDateTimeString') ? $value->toDateTimeString() : (string) $value;
                                                return e($formatted) . ' <span class="text-muted small">(' . e($raw) . ')</span>';
                                            }

                                            if ($type === 'json') {
                                                if (is_null($value)) {
                                                    return '—';
                                                }

                                                return '<pre class="mb-0 small">' . e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
                                            }

                                            if ($value === null || $value === '') {
                                                return '—';
                                            }

                                            return e((string) $value);
                                        };
                                    @endphp
                                    <div class="row g-3">
                                        @foreach ($chunks as $chunk)
                                            <div class="col-md-6">
                                                <table class="table table-sm mb-0">
                                                    @foreach ($chunk as $field)
                                                        <tr>
                                                            <th class="w-50 text-muted">{{ $field['label'] }}</th>
                                                            <td class="text-break">{!! $renderValue($field['value'], $field['type'] ?? 'text') !!}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        @endforeach
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
        const topFiltersForm = document.getElementById('usersFiltersForm');
        const debounce = (fn, delay = 300) => {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), delay);
            };
        };
        const submitFilters = (form) => {
            const params = new URLSearchParams(window.location.search);
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                params.set(key, value);
            }
            window.location = `${window.location.pathname}?${params.toString()}`;
        };

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
            submitFilters(filterForm);
        });

        const autoSubmit = debounce(() => filterForm && submitFilters(filterForm));
        document.querySelectorAll('#grid-filters input[type=\"text\"]').forEach(input => {
            input.addEventListener('input', autoSubmit);
        });
        document.querySelectorAll('#grid-filters select').forEach(select => {
            select.addEventListener('change', () => submitFilters(filterForm));
        });

        document.querySelectorAll('#usersFiltersForm input, #usersFiltersForm select').forEach(el => {
            if (el.tagName.toLowerCase() === 'select') {
                el.addEventListener('change', () => submitFilters(topFiltersForm));
            } else {
                el.addEventListener('input', debounce(() => submitFilters(topFiltersForm)));
            }
        });

        const tooltipTriggerList = Array.from(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
@endpush
@endsection
