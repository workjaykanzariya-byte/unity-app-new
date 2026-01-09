@extends('admin.layouts.app')

@section('title', 'Activities')

@section('content')
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
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Testimonials</th>
                        <th>Referrals</th>
                        <th>Business Deals</th>
                        <th>P2P Meetings</th>
                        <th>Requirements</th>
                    </tr>
                    <tr class="bg-light align-middle">
                        <th>
                            <div class="d-flex flex-column gap-2">
                                <input
                                    type="text"
                                    name="q"
                                    form="activitiesFiltersForm"
                                    class="form-control form-control-sm"
                                    placeholder="Name or email"
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
                                <div class="fw-semibold">{{ $memberName ?: 'Unnamed Member' }}</div>
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
                            <td colspan="6" class="text-center text-muted">No members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $members->links() }}
    </div>
@endsection
