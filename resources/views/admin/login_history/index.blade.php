@extends('admin.layouts.app')

@section('title', 'Login History')

@section('content')
<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Peer Name</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>Company</th>
                    <th>Circles</th>
                    <th>Last Login</th>
                </tr>
                <tr class="bg-light align-middle">
                    <th>
                        <form id="loginHistoryFiltersForm" method="GET" action="{{ route('admin.login-history.index') }}" class="d-flex gap-2">
                            <input
                                type="text"
                                name="q"
                                class="form-control form-control-sm"
                                placeholder="Name, email, or phone"
                                value="{{ $filters['q'] }}"
                            >
                        </form>
                    </th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>
                        <div class="d-flex gap-2">
                            <input
                                type="datetime-local"
                                name="from"
                                form="loginHistoryFiltersForm"
                                value="{{ $filters['from'] }}"
                                class="form-control form-control-sm"
                                title="From Time"
                            >
                            <input
                                type="datetime-local"
                                name="to"
                                form="loginHistoryFiltersForm"
                                value="{{ $filters['to'] }}"
                                class="form-control form-control-sm"
                                title="To Time"
                            >
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
                            <div class="fw-semibold">{{ $record->display_name ?: '—' }}</div>
                            <small class="text-muted">{{ $record->email ?: '—' }}</small>
                        </td>
                        <td>{{ $record->phone ?: '—' }}</td>
                        <td>{{ $record->city ?: '—' }}</td>
                        <td>{{ $record->company_name ?: '—' }}</td>
                        <td>
                            <span class="badge bg-light text-dark">{{ (int) $record->circles_count }}</span>
                            @if (! empty($record->circles_names))
                                <span class="ms-1">{{ $record->circles_names }}</span>
                            @endif
                        </td>
                        <td>{{ $record->last_login_at ? \Illuminate\Support\Carbon::parse($record->last_login_at)->format('d M Y, h:i A') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No records found.</td>
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
