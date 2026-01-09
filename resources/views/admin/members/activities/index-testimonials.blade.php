@extends('admin.layouts.app')

@section('title', 'Testimonials')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">Testimonials</h1>
            <p class="text-muted mb-0">Testimonials created by {{ $member->display_name ?? $member->first_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.members.activities.create', [$member, 'type' => 'testimonials']) }}" class="btn btn-primary">Add Testimonial</a>
            <a href="{{ route('admin.members.details', $member) }}" class="btn btn-outline-secondary">Back to Details</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->has('error'))
        <div class="alert alert-danger">{{ $errors->first('error') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Content, member name..." value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 20, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.members.activities.testimonials', $member) }}">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>For Member</th>
                        <th>Content</th>
                        <th>Coins</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $testimonial)
                        <tr>
                            <td class="font-monospace">{{ substr($testimonial->id, 0, 8) }}</td>
                            <td>{{ $testimonial->toUser->display_name ?? trim(($testimonial->toUser->first_name ?? '') . ' ' . ($testimonial->toUser->last_name ?? '')) ?: '—' }}</td>
                            <td class="text-muted">{{ $testimonial->content ?? '—' }}</td>
                            <td>{{ is_numeric($config['coins_reward']) ? $config['coins_reward'] : '—' }}</td>
                            <td>{{ optional($testimonial->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No testimonials found.</td>
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
