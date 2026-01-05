@extends('admin.layouts.app')

@section('title', 'Circles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Circles</h5>
        <small class="text-muted">Community circles overview</small>
    </div>
</div>

<div class="card p-3 mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Circle name">
        </div>
        <div class="col-md-3">
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
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100">Filter</button>
            <a class="btn btn-outline-secondary w-100" href="{{ route('admin.circles.index') }}">Reset</a>
        </div>
    </form>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Circle</th>
                    <th>Founder</th>
                    <th>City</th>
                    <th>Industry</th>
                    <th>Members</th>
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
                        <td>{{ $circle->business_type ?? '—' }}</td>
                        <td>{{ $circle->members()->count() }}</td>
                        <td><span class="badge badge-soft-secondary text-uppercase">{{ $circle->status ?? 'pending' }}</span></td>
                        <td>{{ optional($circle->created_at)->format('Y-m-d') }}</td>
                        <td class="text-end"><button class="btn btn-sm btn-light">View</button></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No circles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2">
        {{ $circles->links() }}
    </div>
</div>
@endsection
