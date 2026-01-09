@extends('admin.layouts.app')

@section('title', 'Referrals')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="border-bottom p-3">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label small text-muted mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label small text-muted mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    <a href="{{ route('admin.activities.referrals', $member) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Referred Member</th>
                        <th>Referral Date</th>
                        <th>Referral Of</th>
                        <th>Type</th>
                        <th>Contact</th>
                        <th>Hot Value</th>
                        <th>Remarks</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $referral)
                        <tr>
                            <td class="font-monospace">{{ substr($referral->id, 0, 8) }}</td>
                            <td>
                                <div>{{ $referral->toUser->display_name ?? trim(($referral->toUser->first_name ?? '') . ' ' . ($referral->toUser->last_name ?? '')) ?: '—' }}</div>
                                <div class="text-muted small">{{ $referral->toUser->email ?? '—' }}</div>
                            </td>
                            <td>{{ $referral->referral_date ?? '—' }}</td>
                            <td>{{ $referral->referral_of ?? '—' }}</td>
                            <td>{{ $referral->referral_type ?? '—' }}</td>
                            <td class="text-muted">
                                <div>{{ $referral->phone ?? '—' }}</div>
                                <div>{{ $referral->email ?? '—' }}</div>
                                <div>{{ $referral->address ?? '—' }}</div>
                            </td>
                            <td>{{ $referral->hot_value ?? '—' }}</td>
                            <td class="text-muted">{{ $referral->remarks ?? '—' }}</td>
                            <td>{{ optional($referral->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No referrals found.</td>
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
