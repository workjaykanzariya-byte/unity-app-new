@extends('admin.layouts.app')

@section('title', 'Referrals')

@section('content')
    @php
        $displayName = function (?string $display, ?string $first, ?string $last): string {
            if ($display) {
                return $display;
            }
            $name = trim(($first ?? '') . ' ' . ($last ?? ''));
            return $name !== '' ? $name : '—';
        };

        $formatDateTime = function ($value): string {
            return $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : '—';
        };

        $formatDate = function ($value): string {
            return $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d') : '—';
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Referrals</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.activities.referrals.export', request()->query()) }}" class="btn btn-outline-primary">Export</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search actor</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name or email">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Referral type</label>
                    <input type="text" name="referral_type" value="{{ $filters['referral_type'] }}" class="form-control" placeholder="Type">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                </div>
                <div class="col-md-1 d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.activities.referrals.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <strong>Top 3 Members</strong>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Member</th>
                        <th>Total Referrals</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topMembers as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div>{{ $displayName($member->display_name ?? null, $member->first_name ?? null, $member->last_name ?? null) }}</div>
                                <div class="text-muted small">{{ $member->email ?? '—' }}</div>
                            </td>
                            <td>{{ $member->total_count ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Actor</th>
                        <th>Peer</th>
                        <th>Type</th>
                        <th>Referral Date</th>
                        <th>Referral Of</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Hot Value</th>
                        <th>Remarks</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $referral)
                        @php
                            $actorName = $displayName($referral->actor_display_name ?? null, $referral->actor_first_name ?? null, $referral->actor_last_name ?? null);
                            $peerName = $displayName($referral->peer_display_name ?? null, $referral->peer_first_name ?? null, $referral->peer_last_name ?? null);
                        @endphp
                        <tr>
                            <td class="font-monospace">{{ $referral->id }}</td>
                            <td>
                                <div>{{ $actorName }}</div>
                                <div class="text-muted small">{{ $referral->actor_email ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $peerName }}</div>
                                <div class="text-muted small">{{ $referral->peer_email ?? '—' }}</div>
                            </td>
                            <td>{{ $referral->referral_type ?? '—' }}</td>
                            <td>{{ $formatDate($referral->referral_date ?? null) }}</td>
                            <td>{{ $referral->referral_of ?? '—' }}</td>
                            <td>{{ $referral->phone ?? '—' }}</td>
                            <td>{{ $referral->email ?? '—' }}</td>
                            <td>{{ $referral->address ?? '—' }}</td>
                            <td>{{ $referral->hot_value ?? '—' }}</td>
                            <td class="text-muted">{{ $referral->remarks ?? '—' }}</td>
                            <td>{{ $formatDateTime($referral->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">No referrals found.</td>
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
