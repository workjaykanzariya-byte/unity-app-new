@extends('admin.layouts.app')

@section('title', 'P2P Meetings')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="border-bottom p-3">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label small text-muted mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    <a href="{{ route('admin.activities.p2p-meetings', $member) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Peer</th>
                        <th>Meeting Date</th>
                        <th>Meeting Place</th>
                        <th>Remarks</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $meeting)
                        <tr>
                            <td>
                                <div>{{ $meeting->peer->display_name ?? trim(($meeting->peer->first_name ?? '') . ' ' . ($meeting->peer->last_name ?? '')) ?: '—' }}</div>
                                <div class="text-muted small">{{ $meeting->peer->email ?? '—' }}</div>
                            </td>
                            <td>{{ $meeting->meeting_date ?? '—' }}</td>
                            <td>{{ $meeting->meeting_place ?? '—' }}</td>
                            <td class="text-muted">{{ $meeting->remarks ?? '—' }}</td>
                            <td>{{ optional($meeting->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No meetings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endsection
