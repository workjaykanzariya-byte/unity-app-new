@extends('admin.layouts.app')

@section('title', 'Activities')

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Activities</h1>
            <p class="text-muted mb-0">Summary of activity counts per member.</p>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email" value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Membership Status</label>
                    <select name="membership_status" class="form-select">
                        <option value="">Any</option>
                        @foreach ($membershipStatuses as $status)
                            <option value="{{ $status }}" @selected($filters['membership_status'] === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        @foreach ([10, 20, 25, 50, 100] as $size)
                            <option value="{{ $size }}" @selected($filters['per_page'] === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
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
                                <a href="{{ route('admin.activities.testimonials', $member) }}" class="btn btn-sm btn-outline-primary">{{ $testimonialCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.referrals', $member) }}" class="btn btn-sm btn-outline-primary">{{ $referralCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.business-deals', $member) }}" class="btn btn-sm btn-outline-primary">{{ $businessDealCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.p2p-meetings', $member) }}" class="btn btn-sm btn-outline-primary">{{ $p2pMeetingCount }}</a>
                            </td>
                            <td>
                                <a href="{{ route('admin.activities.requirements', $member) }}" class="btn btn-sm btn-outline-primary">{{ $requirementCount }}</a>
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
