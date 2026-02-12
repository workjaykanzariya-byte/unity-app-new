@extends('admin.layouts.app')

@section('title', 'Broadcasts')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Broadcasts</h5>
            <a href="{{ route('admin.broadcasts.create') }}" class="btn btn-sm btn-primary">Create Broadcast</a>
        </div>

        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Title / Message</th>
                        <th>Status</th>
                        <th>Send Time</th>
                        <th>Recurrence</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($broadcasts as $broadcast)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $broadcast->title ?: \Illuminate\Support\Str::limit($broadcast->message, 50) }}</div>
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($broadcast->message, 90) }}</div>
                            </td>
                            <td><span class="badge bg-{{ $broadcast->status === 'sent' ? 'success' : ($broadcast->status === 'scheduled' ? 'info' : ($broadcast->status === 'sending' ? 'warning text-dark' : 'secondary')) }}">{{ ucfirst($broadcast->status) }}</span></td>
                            <td>{{ $broadcast->next_run_at?->timezone('Asia/Kolkata')->format('Y-m-d H:i') ?? $broadcast->send_at?->timezone('Asia/Kolkata')->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ ucfirst($broadcast->recurrence) }}</td>
                            <td>{{ $broadcast->created_by_admin_id }}</td>
                            <td>{{ $broadcast->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="text-end d-flex gap-2 justify-content-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.broadcasts.edit', $broadcast) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.broadcasts.send-now', $broadcast) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success" type="submit">Send Now</button>
                                </form>
                                @if($broadcast->status === 'scheduled')
                                    <form method="POST" action="{{ route('admin.broadcasts.cancel', $broadcast) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No broadcasts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $broadcasts->links() }}
        </div>
    </div>
@endsection
