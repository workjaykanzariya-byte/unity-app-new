@extends('admin.layouts.app')

@section('title', 'P2P Meetings')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Peer Member</th>
                        <th>Meeting Date</th>
                        <th>Meeting Place</th>
                        <th>Remarks</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $meeting)
                        <tr>
                            <td class="font-monospace">{{ substr($meeting->id, 0, 8) }}</td>
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
                            <td colspan="6" class="text-center text-muted">No meetings found.</td>
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
