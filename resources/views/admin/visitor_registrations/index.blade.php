@extends('admin.layouts.app')

@section('title', 'Visitor Registrations')

@section('content')
    @php
        $displayName = function (?string $display, ?string $first, ?string $last): string {
            if ($display) {
                return $display;
            }
            $name = trim(($first ?? '') . ' ' . ($last ?? ''));
            return $name !== '' ? $name : '—';
        };

        $formatDate = function ($value): string {
            return $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '—';
        };

        $formatDateTime = function ($value): string {
            return $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : '—';
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Visitor Registrations</h1>
        <span class="badge bg-light text-dark border">Total: {{ number_format($registrations->total()) }}</span>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Visitor name/mobile or peer name/phone">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending" @selected($filters['status'] === 'pending')>Pending</option>
                        <option value="approved" @selected($filters['status'] === 'approved')>Approved</option>
                        <option value="rejected" @selected($filters['status'] === 'rejected')>Rejected</option>
                        <option value="all" @selected($filters['status'] === 'all')>All</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.visitor-registrations.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Submitted At</th>
                        <th>Peer Name</th>
                        <th>Peer Phone</th>
                        <th>Event Type</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                        <th>Visitor Name</th>
                        <th>Visitor Mobile</th>
                        <th>Visitor City</th>
                        <th>Visitor Business</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registrations as $registration)
                        @php
                            $member = $registration->user;
                            $memberName = $displayName($member->display_name ?? null, $member->first_name ?? null, $member->last_name ?? null);
                        @endphp
                        <tr>
                            <td>{{ $formatDateTime($registration->created_at ?? null) }}</td>
                            <td>{{ $memberName }}</td>
                            <td>{{ $member->phone ?? '—' }}</td>
                            <td>{{ ucfirst($registration->event_type ?? '—') }}</td>
                            <td>{{ $registration->event_name ?? '—' }}</td>
                            <td>{{ $formatDate($registration->event_date ?? null) }}</td>
                            <td>{{ $registration->visitor_full_name ?? '—' }}</td>
                            <td>{{ $registration->visitor_mobile ?? '—' }}</td>
                            <td>{{ $registration->visitor_city ?? '—' }}</td>
                            <td>{{ $registration->visitor_business ?? '—' }}</td>
                            <td>{{ ucfirst($registration->status ?? '—') }}</td>
                            <td class="text-end">
                                @if ($registration->status === 'pending')
                                    <form method="POST" action="{{ route('admin.visitor-registrations.approve', $registration->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this visitor registration?')">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.visitor-registrations.reject', $registration->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this visitor registration?')">Reject</button>
                                    </form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">No visitor registrations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $registrations->links() }}
    </div>
@endsection
