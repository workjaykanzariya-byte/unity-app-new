@extends('admin.layouts.app')

@section('title', 'Requirements')

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
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $requirement)
                        <tr>
                            <td class="font-monospace">{{ substr($requirement->id, 0, 8) }}</td>
                            <td>{{ $requirement->subject ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $requirement->status ?? 'open' }}</span>
                            </td>
                            <td>{{ optional($requirement->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No requirements found.</td>
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
