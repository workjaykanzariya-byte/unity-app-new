@extends('admin.layouts.app')

@section('title', 'Login History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Login History</h5>
        <small class="text-muted">Latest peer logins by user</small>
    </div>
</div>

<div class="card p-3 mb-3">
    <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.login-history.index') }}">
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Name, email, or phone">
        </div>
        <div class="col-md-3">
            <label class="form-label">From Time</label>
            <input type="datetime-local" name="from" value="{{ $filters['from'] }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">To Time</label>
            <input type="datetime-local" name="to" value="{{ $filters['to'] }}" class="form-control">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a class="btn btn-outline-secondary w-100" href="{{ route('admin.login-history.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table align-middle" style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>User</th>
                    <th>City</th>
                    <th>Company</th>
                    <th>Circles</th>
                    <th>Last Login</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $record->display_name ?: '—' }}</div>
                            <small class="text-muted">{{ $record->email ?: $record->phone ?: '—' }}</small>
                        </td>
                        <td>{{ $record->city ?: '—' }}</td>
                        <td>{{ $record->company_name ?: '—' }}</td>
                        <td>
                            {{ (int) $record->circles_count }}
                            @if (!empty($record->circles_names))
                                - {{ $record->circles_names }}
                            @endif
                        </td>
                        <td>{{ optional($record->last_login_at)->format('Y-m-d H:i:s') ?: '—' }}</td>
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
