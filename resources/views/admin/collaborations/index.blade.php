@extends('admin.layouts.app')

@section('title', 'Find & Build Collaborations')

@section('content')
<div class="card p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <h2 class="h5 mb-0">Find &amp; Build Collaborations</h2>
        </div>
        <div class="d-flex align-items-center gap-3 ms-auto">
            <div class="d-flex align-items-center gap-2">
                <label for="perPage" class="form-label mb-0 small text-muted">Rows per page:</label>
                <select id="perPage" name="per_page" class="form-select form-select-sm" style="width: 90px;">
                    @foreach ([10, 20, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($rowsPerPage === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="small text-muted">
                @if($total > 0)
                    Records {{ $from }} to {{ $to }} of {{ $total }}
                @else
                    No records found
                @endif
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>Peer Name</th>
                    <th>Collaboration Type</th>
                    <th>Title</th>
                    <th>Scope</th>
                    <th>Preferred Mode</th>
                    <th>Business Stage</th>
                    <th>Year in Operation</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                <tr class="bg-light align-middle">
                    <th>
                        <input type="text" name="q" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Search peer, title, scope…" value="{{ $filters['q'] }}">
                    </th>
                    <th>
                        <select name="collaboration_type" form="collaborationFiltersForm" class="form-select form-select-sm">
                            <option value="all">All</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->id }}" @selected($filters['collaboration_type'] === (string) $type->id || $filters['collaboration_type'] === (string) $type->slug)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th>
                        <input type="text" name="title" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Title" value="{{ $filters['title'] }}">
                    </th>
                    <th>
                        <input type="text" name="scope" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Scope" value="{{ $filters['scope'] }}">
                    </th>
                    <th>
                        <input type="text" name="preferred_mode" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Preferred Mode" value="{{ $filters['preferred_mode'] }}">
                    </th>
                    <th>
                        <input type="text" name="business_stage" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Business Stage" value="{{ $filters['business_stage'] }}">
                    </th>
                    <th>
                        <input type="text" name="year_in_operation" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Year in Operation" value="{{ $filters['year_in_operation'] }}">
                    </th>
                    <th>
                        <select name="status" form="collaborationFiltersForm" class="form-select form-select-sm">
                            <option value="all">All</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </th>
                    <th class="text-end">
                        <form id="collaborationFiltersForm" method="GET" class="d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.collaborations.index') }}">Reset</a>
                        </form>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($posts as $post)
                    @php
                        $user = $post->user;
                        $peerName = $user?->name ?? '—';
                        $company = $user?->company_name ?? $user?->company ?? $user?->business_name ?? '—';
                        $city = $user?->city ?? $user?->current_city ?? $user?->location_city ?? '—';
                        $typeName = $post->collaborationType?->name ?? $post->collaboration_type ?? '—';
                        $title = $post->title ?? $post->collaboration_title ?? $post->subject ?? '—';
                        $scope = $post->scope ?? $post->collaboration_scope ?? $post->scope_text ?? '—';
                        $preferredMode = $post->preferred_mode ?? $post->preferred_model ?? $post->meeting_mode ?? $post->mode ?? '—';
                        $businessStage = $post->business_stage ?? $post->stage ?? $post->business_stage_text ?? '—';
                        $yearInOperation = $post->year_in_operation ?? $post->years_in_operation ?? $post->operating_years ?? $post->years ?? '—';
                        $status = $post->status ?? '—';
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px; overflow: hidden;">
                                    <span class="text-muted">{{ strtoupper(substr((string) $peerName, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $peerName ?: '—' }}</div>
                                    <div class="text-muted small">{{ $company ?: '—' }}</div>
                                    <div class="text-muted small">{{ $city ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary">{{ $typeName }}</span></td>
                        <td>{{ $title }}</td>
                        <td>{{ $scope }}</td>
                        <td>{{ $preferredMode }}</td>
                        <td>{{ $businessStage }}</td>
                        <td>{{ $yearInOperation }}</td>
                        <td>
                            <span class="badge {{ strtolower((string) $status) === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ ucfirst((string) $status) }}</span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.collaborations.show', ['id' => $post->id] + request()->query()) }}">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No collaboration posts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
        <div>
            {{ $posts->appends(request()->query())->links() }}
        </div>
        <div class="small text-muted">
            @if($total > 0)
                Showing {{ $from }}-{{ $to }} of {{ $total }} records
            @else
                No records
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const perPage = document.getElementById('perPage');

        if (perPage) {
            perPage.addEventListener('change', () => {
                const params = new URLSearchParams(window.location.search);
                params.set('per_page', perPage.value);
                params.delete('page');
                window.location = `${window.location.pathname}?${params.toString()}`;
            });
        }
    });
</script>
@endpush
@endsection
