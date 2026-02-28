@extends('admin.layouts.app')

@section('title', 'Activities')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">Top by P2P Meetings Completed</h5>
                    <ul class="list-group list-group-flush">
                        @forelse ($topP2pPeers as $rank => $peer)
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <div class="fw-semibold">#{{ $rank + 1 }} {{ $peer->peer_name }}</div>
                                    <div class="small text-muted">{{ $peer->company_name ?: '—' }} · {{ $peer->city_name ?: '—' }}</div>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ (int) $peer->p2p_completed_count }}</span>
                            </li>
                        @empty
                            <li class="list-group-item px-0 text-muted">No peers found.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">My Rank</h5>
                    @if ($myRank)
                        <div class="display-6 fw-bold">#{{ $myRank['rank'] }}</div>
                        <div class="fw-semibold">{{ $myRank['peer_name'] }}</div>
                        <div class="small text-muted">P2P Meetings Completed: {{ $myRank['p2p_completed_count'] }}</div>
                    @else
                        <div class="text-muted small">No linked peer account found for this admin.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form id="activitiesFiltersForm" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Name, email, company, city" value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Company</label>
                    <input type="text" name="company" class="form-control form-control-sm" value="{{ $filters['company'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">City</label>
                    <input type="text" name="city" class="form-control form-control-sm" value="{{ $filters['city'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Circle</label>
                    <select name="circle_id" class="form-select form-select-sm">
                        <option value="any">Any</option>
                        @foreach ($circles as $circle)
                            <option value="{{ $circle->id }}" @selected($filters['circle_id'] === $circle->id)>{{ $circle->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted">Rows</label>
                    <select id="perPage" name="per_page" class="form-select form-select-sm">
                        @foreach ([10, 20, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
                </div>
            </form>
            <div class="mt-2">
                <a href="{{ route('admin.activities.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#activitiesExportModal">Export</button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;"><input type="checkbox" class="form-check-input" id="select-all-members"></th>
                        <th>Peer</th>
                        <th>Testimonials</th>
                        <th>Referrals</th>
                        <th>Business Deals</th>
                        <th>P2P Meetings</th>
                        <th>Requirements</th>
                        <th>Become A Leader</th>
                        <th>Recommend A Peer</th>
                        <th>Register A Visitor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td><input type="checkbox" class="form-check-input member-checkbox" value="{{ $member->id }}"></td>
                            <td>
                                <div class="fw-bold">{{ $member->peer_name }}</div>
                                <div class="small text-muted">{{ $member->company_name ?: 'No Company' }}</div>
                                <div class="small text-muted">{{ $member->city_name ?: 'No City' }}</div>
                                <div class="small text-muted">{{ $member->circle_name ?: 'No Circle' }}</div>
                                <div class="small text-muted">{{ $member->email }}</div>
                            </td>
                            <td>@if ($member->testimonials_count > 0)<a href="{{ route('admin.activities.testimonials', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->testimonials_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->referrals_count > 0)<a href="{{ route('admin.activities.referrals', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->referrals_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->business_deals_count > 0)<a href="{{ route('admin.activities.business-deals', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->business_deals_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->p2p_completed_count > 0)<a href="{{ route('admin.activities.p2p-meetings', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->p2p_completed_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->requirements_count > 0)<a href="{{ route('admin.activities.requirements', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->requirements_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->become_leader_count > 0)<a href="{{ route('admin.activities.become-a-leader.show', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->become_leader_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->recommend_peer_count > 0)<a href="{{ route('admin.activities.recommend-peer.show', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->recommend_peer_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                            <td>@if ($member->register_visitor_count > 0)<a href="{{ route('admin.activities.register-visitor.show', $member->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">{{ $member->register_visitor_count }}</a>@else<span class="text-muted">0</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No peers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="activitiesExportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Activities Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.activities.export') }}" id="activitiesExportForm">
                    @csrf
                    <input type="hidden" name="activity_type" value="summary">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Scope</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scopeSelected" value="selected" checked>
                                <label class="form-check-label" for="scopeSelected">Selected peers only</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scopeAll" value="all">
                                <label class="form-check-label" for="scopeAll">All peers (current filters)</label>
                            </div>
                        </div>
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="company" value="{{ $filters['company'] }}">
                        <input type="hidden" name="city" value="{{ $filters['city'] }}">
                        <input type="hidden" name="circle_id" value="{{ $filters['circle_id'] }}">
                        <div id="selectedMemberIdsContainer"></div>
                        <div class="text-danger small d-none" id="exportSelectionError">Please select at least one peer.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Export CSV</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $members->links() }}
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const selectAll = document.getElementById('select-all-members');
        const checkboxes = document.querySelectorAll('.member-checkbox');
        const exportForm = document.getElementById('activitiesExportForm');
        const selectedContainer = document.getElementById('selectedMemberIdsContainer');
        const selectionError = document.getElementById('exportSelectionError');
        const scopeSelected = document.getElementById('scopeSelected');

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });
        }

        exportForm.addEventListener('submit', (event) => {
            selectionError.classList.add('d-none');
            selectedContainer.innerHTML = '';
            const selectedIds = Array.from(checkboxes)
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);

            if (scopeSelected.checked && selectedIds.length === 0) {
                event.preventDefault();
                selectionError.classList.remove('d-none');
                return;
            }

            selectedIds.forEach((id) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_member_ids[]';
                input.value = id;
                selectedContainer.appendChild(input);
            });
        });
    });
</script>
@endpush
