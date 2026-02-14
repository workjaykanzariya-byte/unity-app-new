@extends('admin.layouts.app')

@section('title', 'Login History')

@section('content')
<div class="card p-3">
    <form id="loginHistoryFiltersForm" method="GET" action="{{ route('admin.login-history.index') }}"></form>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <label for="perPage" class="form-label mb-0 small text-muted">Rows per page:</label>
            <select id="perPage" name="per_page" form="loginHistoryFiltersForm" class="form-select form-select-sm" style="width: 90px;">
                @foreach ([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 20) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <input
                type="datetime-local"
                name="from"
                form="loginHistoryFiltersForm"
                value="{{ $filters['from'] ?? '' }}"
                class="form-control form-control-sm"
                style="min-width: 180px;"
                title="From Time"
            >
            <input
                type="datetime-local"
                name="to"
                form="loginHistoryFiltersForm"
                value="{{ $filters['to'] ?? '' }}"
                class="form-control form-control-sm"
                style="min-width: 180px;"
                title="To Time"
            >
        </div>

        <div class="small text-muted">
            @if($records->total() > 0)
                Records {{ $records->firstItem() }} to {{ $records->lastItem() }} of {{ $records->total() }}
            @else
                No records found
            @endif
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Peer Name</th>
                    <th>City</th>
                    <th>Company</th>
                    <th>Circles</th>
                    <th>Last Login</th>
                </tr>
                <tr class="bg-light align-middle">
                    <th>
                        <input
                            type="text"
                            name="name"
                            form="loginHistoryFiltersForm"
                            class="form-control form-control-sm"
                            placeholder="Name or email"
                            value="{{ $filters['name'] ?? '' }}"
                        >
                    </th>
                    <th>
                        <input
                            type="text"
                            name="city"
                            form="loginHistoryFiltersForm"
                            class="form-control form-control-sm"
                            placeholder="City"
                            value="{{ $filters['city'] ?? '' }}"
                        >
                    </th>
                    <th>
                        <input
                            type="text"
                            name="company"
                            form="loginHistoryFiltersForm"
                            class="form-control form-control-sm"
                            placeholder="Company"
                            value="{{ $filters['company'] ?? '' }}"
                        >
                    </th>
                    <th>
                        <div class="d-flex gap-2 align-items-center">
                            <input
                                type="text"
                                name="circle"
                                form="loginHistoryFiltersForm"
                                class="form-control form-control-sm"
                                placeholder="Circle name"
                                value="{{ $filters['circle'] ?? '' }}"
                            >
                            <select name="joined" form="loginHistoryFiltersForm" class="form-select form-select-sm" style="min-width: 180px;">
                                <option value="all" @selected(($filters['joined'] ?? 'all') === 'all')>All</option>
                                <option value="yes" @selected(($filters['joined'] ?? 'all') === 'yes')>Only Joined (Yes)</option>
                                <option value="no" @selected(($filters['joined'] ?? 'all') === 'no')>Not Joined (No)</option>
                            </select>
                        </div>
                    </th>
                    <th>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="submit" form="loginHistoryFiltersForm" class="btn btn-primary btn-sm">Apply</button>
                            <a href="{{ route('admin.login-history.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $record->peer_name ?: '—' }}</div>
                            <small class="text-muted">{{ $record->email ?: '—' }}</small>
                        </td>
                        <td>{{ $record->city ?: '—' }}</td>
                        <td>{{ $record->company ?: '—' }}</td>
                        <td>
                            <span class="badge bg-light text-dark">{{ (int) $record->circles_count }}</span>
                            @if ((int) $record->circles_count === 0)
                                <span class="ms-1 text-muted">Not Joined</span>
                            @elseif (! empty($record->circles_names))
                                <span class="ms-1">{{ $record->circles_names }}</span>
                            @endif
                        </td>
                        <td>{{ $record->last_login_at ? \Illuminate\Support\Carbon::parse($record->last_login_at)->format('d-m-Y h:i A') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-2">
        {{ $records->links() }}
    </div>
</div>
@endsection
