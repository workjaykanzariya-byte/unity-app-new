@extends('admin.layouts.app')

@section('title', 'Testimonials')

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
                        <th>To Member</th>
                        <th>Content</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $testimonial)
                        <tr>
                            <td class="font-monospace">{{ substr($testimonial->id, 0, 8) }}</td>
                            <td>
                                <div>{{ $testimonial->toUser->display_name ?? trim(($testimonial->toUser->first_name ?? '') . ' ' . ($testimonial->toUser->last_name ?? '')) ?: '—' }}</div>
                                <div class="text-muted small">{{ $testimonial->toUser->email ?? '—' }}</div>
                            </td>
                            <td class="text-muted">{{ $testimonial->content ?? '—' }}</td>
                            <td>{{ optional($testimonial->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No testimonials found.</td>
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
