@extends('admin.layouts.app')

@section('title', 'Circles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Circles</h5>
        <small class="text-muted">Community circles overview</small>
    </div>
    <a href="{{ route('admin.circles.create') }}" class="btn btn-primary btn-sm">Create Circle</a>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card p-3 mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="q" value="{{ $filters['search'] }}" class="form-control" placeholder="Circle name">
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">City</label>
            <select name="city_id" class="form-select">
                <option value="">All</option>
                @foreach ($cities as $city)
                    <option value="{{ $city->id }}" @selected($filters['city_id'] == $city->id)>{{ $city->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="type" class="form-select">
                <option value="">All</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a class="btn btn-outline-secondary w-100" href="{{ route('admin.circles.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive" style="overflow-x: auto;">
        <table class="table align-middle" style="white-space: nowrap;">
            <thead>
                <tr>
                    <th>Circle</th>
                    <th>Founder</th>
                    <th>City</th>
                    <th>Country</th>
                    <th>Type</th>
                    <th>Industry Tags</th>
                    <th>Meeting Mode</th>
                    <th>Meeting Frequency</th>
                    <th>Launch Date</th>
                    <th>Cover</th>
                    <th>Director</th>
                    <th>Industry Director</th>
                    <th>DED</th>
                    <th>Peers</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($circles as $circle)
                    <tr>
                        <td class="fw-semibold">{{ $circle->name }}</td>
                        <td>{{ $circle->founder?->display_name ?? '—' }}</td>
                        <td>{{ $circle->city?->name ?? '—' }}</td>
                        <td>{{ $circle->city?->country ?? '—' }}</td>
                        <td><span class="badge bg-light text-dark text-uppercase">{{ $circle->type ?? '—' }}</span></td>
                        <td>{{ $circle->industry_tags ? implode(', ', $circle->industry_tags) : '—' }}</td>
                        <td>{{ $circle->meeting_mode ?? '—' }}</td>
                        <td>{{ $circle->meeting_frequency ?? '—' }}</td>
                        <td>{{ optional($circle->launch_date)->format('Y-m-d') ?? '—' }}</td>
                        <td>@if($circle->cover_file_id)<img src="{{ url('/api/v1/files/' . $circle->cover_file_id) }}" style="width:36px;height:36px;object-fit:cover;border-radius:6px;">@else — @endif</td>
                        <td>{{ $circle->director?->display_name ?? '—' }}</td>
                        <td>{{ $circle->industryDirector?->display_name ?? '—' }}</td>
                        <td>{{ $circle->ded?->display_name ?? '—' }}</td>
                        <td>{{ $circle->members_count ?? 0 }}</td>
                        <td><span class="badge badge-soft-secondary text-uppercase">{{ $circle->status ?? 'pending' }}</span></td>
                        <td>{{ optional($circle->created_at)->format('Y-m-d') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-light" href="{{ route('admin.circles.show', $circle) }}">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="18" class="text-center text-muted py-4">No circles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">
        {{ $circles->links() }}
    </div>
</div>
@endsection
