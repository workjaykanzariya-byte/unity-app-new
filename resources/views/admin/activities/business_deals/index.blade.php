@extends('admin.layouts.app')

@section('title', 'Business Deals')

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
        <h1 class="h4 mb-0">Business Deals</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.activities.business-deals.export', request()->query()) }}" class="btn btn-outline-primary">Export</a>
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
                    <label class="form-label small text-muted">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Apply</button>
                    <a href="{{ route('admin.activities.business-deals.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
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
                        <th>Total Deals</th>
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
                        <th>Deal Date</th>
                        <th>Deal Amount</th>
                        <th>Business Type</th>
                        <th>Comment</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $deal)
                        @php
                            $actorName = $displayName($deal->actor_display_name ?? null, $deal->actor_first_name ?? null, $deal->actor_last_name ?? null);
                            $peerName = $displayName($deal->peer_display_name ?? null, $deal->peer_first_name ?? null, $deal->peer_last_name ?? null);
                        @endphp
                        <tr>
                            <td class="font-monospace">{{ $deal->id }}</td>
                            <td>
                                <div>{{ $actorName }}</div>
                                <div class="text-muted small">{{ $deal->actor_email ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $peerName }}</div>
                                <div class="text-muted small">{{ $deal->peer_email ?? '—' }}</div>
                            </td>
                            <td>{{ $formatDate($deal->deal_date ?? null) }}</td>
                            <td>{{ $deal->deal_amount ?? '—' }}</td>
                            <td>{{ $deal->business_type ?? '—' }}</td>
                            <td class="text-muted">{{ $deal->comment ?? '—' }}</td>
                            <td>{{ $formatDateTime($deal->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No business deals found.</td>
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
