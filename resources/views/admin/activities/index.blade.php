@extends('admin.layouts.app')

@section('title', 'Activities')

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center p-3 gap-2">
            <div class="d-flex align-items-center gap-2">
                <label for="perPage" class="form-label mb-0 small text-muted">Rows per page:</label>
                <select id="perPage" name="per_page" form="activitiesFiltersForm" class="form-select form-select-sm" style="width: 90px;">
                    @foreach ([10, 20, 25, 50, 100] as $size)
                        <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#activitiesExportModal">Export</button>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" class="form-check-input" id="select-all-members">
                        </th>
                        <th>Peer Name</th>
                        <th>Testimonials</th>
                        <th>Referrals</th>
                        <th>Business Deals</th>
                        <th>P2P Meetings</th>
                        <th>Requirements</th>
                    </tr>
                    <tr class="bg-light align-middle">
                        <th></th>
                        <th>
                            <div class="d-flex flex-column gap-2">
                                <input
                                    type="text"
                                    name="q"
                                    form="activitiesFiltersForm"
                                    class="form-control form-control-sm"
                                    placeholder="Name, email, company, or city"
                                    value="{{ request('q', $filters['search']) }}"
                                    oninput="this.form.search.value = this.value"
                                >
                                <select name="membership_status" form="activitiesFiltersForm" class="form-select form-select-sm">
                                    <option value="">Any</option>
                                    @foreach ($membershipStatuses as $status)
                                        <option value="{{ $status }}" @selected($filters['membership_status'] === $status)>{{ ucfirst($status) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" placeholder="—" disabled>
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" placeholder="—" disabled>
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" placeholder="—" disabled>
                        </th>
                        <th>
                            <input type="text" class="form-control form-control-sm" placeholder="—" disabled>
                        </th>
                        <th class="text-end">
                            <form id="activitiesFiltersForm" method="GET" class="d-flex justify-content-end gap-2">
                                <input type="hidden" name="search" value="{{ request('q', $filters['search']) }}">
                                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                                <a href="{{ route('admin.activities.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            </form>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        @php
                            $memberName = $member->display_name ?? trim($member->first_name . ' ' . $member->last_name);
                            $testimonialCount = $counts['testimonials'][$member->id] ?? 0;
                            $referralCount = $counts['referrals'][$member->id] ?? 0;
                            $businessDealCount = $counts['business_deals'][$member->id] ?? 0;
                            $p2pMeetingCount = $counts['p2p_meetings'][$member->id] ?? 0;
                            $requirementCount = $counts['requirements'][$member->id] ?? 0;
                        @endphp
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input member-checkbox" value="{{ $member->id }}">
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $memberName ?: 'Unnamed Peer' }}</div>
                                <div class="text-muted small">{{ $member->email }}</div>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.testimonials', $member) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">{{ $testimonialCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.referrals', $member) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">{{ $referralCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.business-deals', $member) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">{{ $businessDealCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.p2p-meetings', $member) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">{{ $p2pMeetingCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.requirements', $member) }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">{{ $requirementCount }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No peers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="activitiesExportModal" tabindex="-1" aria-labelledby="activitiesExportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activitiesExportModalLabel">Export Activities</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.activities.export') }}" id="activitiesExportForm">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Activity</label>
                            <select name="activity_type" class="form-select" required>
                                <option value="testimonials">Testimonials</option>
                                <option value="referrals">Referrals</option>
                                <option value="business_deals">Business Deals</option>
                                <option value="p2p_meetings">P2P Meetings</option>
                                <option value="requirements">Requirements</option>
                            </select>
                        </div>
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
                        <input type="hidden" name="q" value="{{ request('q', $filters['search']) }}">
                        <input type="hidden" name="membership_status" value="{{ $filters['membership_status'] }}">
                        <div id="selectedMemberIdsContainer"></div>
                        <div class="text-danger small d-none" id="exportSelectionError">Please select at least one member.</div>
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
