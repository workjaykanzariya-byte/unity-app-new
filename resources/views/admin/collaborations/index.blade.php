@extends('admin.layouts.app')

@section('title', 'Find & Build Collaborations')

@section('content')
@php use App\Support\CollaborationFormatter; @endphp
<div class="card p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="d-flex align-items-center gap-2">
            <h2 class="h5 mb-0">Find &amp; Build Collaborations</h2>
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto flex-wrap justify-content-end">
            <input type="date" name="created_from" form="collaborationFiltersForm" class="form-control form-control-sm" style="width: 150px;" value="{{ $filters['created_from'] }}" title="Created from">
            <input type="date" name="created_to" form="collaborationFiltersForm" class="form-control form-control-sm" style="width: 150px;" value="{{ $filters['created_to'] }}" title="Created to">
            <span class="small text-muted" id="selectedCount">(Selected: 0)</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="exportCsvBtn">Export CSV</button>
            <div class="d-flex align-items-center gap-2 ms-2">
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
                    <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
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
                    <th></th>
                    <th>
                        <input type="text" name="q" form="collaborationFiltersForm" class="form-control form-control-sm" placeholder="Search peer, title, scope…" value="{{ $filters['q'] }}">
                    </th>
                    <th>
                        <select name="collaboration_type" form="collaborationFiltersForm" class="form-select form-select-sm">
                            <option value="all">All</option>
                            @foreach ($types as $type)
                                <option value="{{ $type->slug }}" @selected($filters['collaboration_type'] === (string) $type->slug)>{{ $type->name }}</option>
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
                        $u = $post->user;

                        $peerName = $u?->name
                            ?? $u?->display_name
                            ?? $post->peer_name
                            ?? $post->person_name
                            ?? $post->name
                            ?? '—';

                        $company = ($u?->company_name ?? $u?->company ?? $u?->business_name ?? null)
                            ?? $post->company
                            ?? $post->company_name
                            ?? $post->business_name
                            ?? '—';

                        $city = ($u?->city ?? $u?->current_city ?? $u?->location_city ?? null)
                            ?? $post->city
                            ?? $post->user_city
                            ?? '—';

                        $initial = mb_strtoupper(mb_substr($peerName !== '—' ? $peerName : 'U', 0, 1));
                        $typeName = $post->collaborationType?->name ?? CollaborationFormatter::humanize($post->collaboration_type);
                        $title = $post->title ?? $post->collaboration_title ?? $post->subject ?? '—';
                        $scope = CollaborationFormatter::humanize($post->scope ?? $post->collaboration_scope ?? $post->scope_text);
                        $preferredMode = CollaborationFormatter::humanize($post->preferred_mode ?? $post->preferred_model ?? $post->meeting_mode ?? $post->mode);
                        $businessStage = CollaborationFormatter::humanize($post->business_stage ?? $post->stage ?? $post->business_stage_text);
                        $yearInOperation = CollaborationFormatter::humanize($post->year_in_operation ?? $post->years_in_operation ?? $post->operating_years ?? $post->years);
                        $status = $post->status ?? '—';
                    @endphp
                    <tr>
                        <td><input type="checkbox" class="form-check-input row-checkbox" value="{{ $post->id }}"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-sm rounded-circle border d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                    <span class="fw-semibold">{{ $initial }}</span>
                                </div>

                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ $peerName }}</div>
                                    <div class="text-muted small text-truncate">{{ $company }}</div>
                                    <div class="text-muted small text-truncate">{{ $city }}</div>
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
                            <span class="badge {{ strtolower((string) $status) === 'active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ CollaborationFormatter::humanize((string) $status) }}</span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.collaborations.show', ['id' => $post->id] + request()->query()) }}">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No collaboration posts found.</td>
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
        const exportBtn = document.getElementById('exportCsvBtn');
        const selectAll = document.getElementById('selectAll');
        const rowCheckboxes = () => Array.from(document.querySelectorAll('.row-checkbox'));
        const selectedCount = document.getElementById('selectedCount');
        const exportRoute = @json(route('admin.collaborations.export'));

        const updateSelectedCount = () => {
            const selected = rowCheckboxes().filter(cb => cb.checked).length;
            if (selectedCount) {
                selectedCount.textContent = `(Selected: ${selected})`;
            }
        };

        selectAll?.addEventListener('change', () => {
            rowCheckboxes().forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });

        rowCheckboxes().forEach(cb => {
            cb.addEventListener('change', () => {
                const all = rowCheckboxes();
                if (selectAll) {
                    selectAll.checked = all.length > 0 && all.every(item => item.checked);
                }
                updateSelectedCount();
            });
        });

        if (perPage) {
            perPage.addEventListener('change', () => {
                const params = new URLSearchParams(window.location.search);
                params.set('per_page', perPage.value);
                params.delete('page');
                window.location = `${window.location.pathname}?${params.toString()}`;
            });
        }

        exportBtn?.addEventListener('click', () => {
            const params = new URLSearchParams(window.location.search);
            rowCheckboxes().filter(cb => cb.checked).forEach(cb => {
                params.append('selected_ids[]', cb.value);
            });
            window.location.href = `${exportRoute}?${params.toString()}`;
        });

        updateSelectedCount();
    });
</script>
@endpush
@endsection
