@extends('admin.layouts.app')

@section('title', 'Member Details')

@section('content')
    @php
        $name = $member->display_name ?? trim($member->first_name . ' ' . $member->last_name);
        $avatar = $member->profile_photo_url ?? ($member->profile_photo_file_id ? url('/api/v1/files/' . $member->profile_photo_file_id) : null);
        $cards = [
            [
                'key' => 'p2p_meetings',
                'label' => 'P2P Meetings',
                'route' => route('admin.members.activities.p2p-meetings', $member),
            ],
            [
                'key' => 'referrals',
                'label' => 'Referrals',
                'route' => route('admin.members.activities.referrals', $member),
            ],
            [
                'key' => 'business_deals',
                'label' => 'Business Deals',
                'route' => route('admin.members.activities.business-deals', $member),
            ],
            [
                'key' => 'requirements',
                'label' => 'Requirements',
                'route' => route('admin.members.activities.requirements', $member),
            ],
            [
                'key' => 'testimonials',
                'label' => 'Testimonials',
                'route' => route('admin.members.activities.testimonials', $member),
            ],
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-1">Member Details</h1>
            <p class="text-muted mb-0">View profile overview and activity summaries.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.edit', $member->id) }}" class="btn btn-outline-secondary">Edit Member</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">Back to Members</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 56px; height: 56px; overflow: hidden;">
                            @if ($avatar)
                                <img src="{{ $avatar }}" alt="{{ $name }}" class="img-fluid w-100 h-100 object-fit-cover">
                            @else
                                <span class="text-muted fs-4">{{ strtoupper(substr($name ?: 'U', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="fw-semibold fs-5">{{ $name ?: 'Unnamed Member' }}</div>
                            <div class="text-muted">{{ $member->email }}</div>
                            <div class="text-muted small">ID: <span class="font-monospace">{{ $member->id }}</span></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Company</div>
                                <div class="fw-semibold">{{ $member->company_name ?? '—' }}</div>
                                <div class="text-muted small mt-2">Designation</div>
                                <div>{{ $member->designation ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Membership Status</div>
                                <div class="fw-semibold text-uppercase">{{ $member->membership_status ?? 'Free' }}</div>
                                <div class="text-muted small mt-2">Coins Balance</div>
                                <div>{{ number_format($member->coins_balance ?? 0) }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Phone</div>
                                <div class="fw-semibold">{{ $member->phone ?? '—' }}</div>
                                <div class="text-muted small mt-2">City</div>
                                <div>{{ $member->city->name ?? $member->city ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="text-muted small">Joined On</div>
                                <div class="fw-semibold">{{ optional($member->created_at)->format('Y-m-d') ?? '—' }}</div>
                                <div class="text-muted small mt-2">Last Login</div>
                                <div>{{ optional($member->last_login_at)->format('Y-m-d H:i') ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">Activities</div>
                            <small class="text-muted">Quick counts per activity</small>
                        </div>
                        <a href="{{ route('admin.members.activities.index', $member) }}" class="btn btn-sm btn-outline-secondary">All Activities</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($cards as $card)
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between border rounded p-3">
                                    <div>
                                        <div class="fw-semibold">{{ $card['label'] }}</div>
                                        <div class="text-muted small">Total records</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary-subtle text-primary fs-6">{{ $activityCounts[$card['key']] ?? 0 }}</span>
                                        <a class="btn btn-sm btn-outline-primary" href="{{ $card['route'] }}" target="_blank" rel="noopener">View</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
