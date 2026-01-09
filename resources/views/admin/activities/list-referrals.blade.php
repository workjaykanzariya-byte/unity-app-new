@extends('admin.layouts.app')

@section('title', 'Referrals')

@section('content')
    @php
        $memberName = $member->display_name ?? trim($member->first_name . ' ' . $member->last_name);
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">Referrals for {{ $memberName ?: 'Member' }}</h1>
            <p class="text-muted mb-0">All referrals submitted by this member.</p>
        </div>
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Referred Member</th>
                        <th>Referral Of</th>
                        <th>Type</th>
                        <th>Hot Value</th>
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
                            <td>{{ $referral->referral_of ?? '—' }}</td>
                            <td>{{ $referral->referral_type ?? '—' }}</td>
                            <td>{{ $referral->hot_value ?? '—' }}</td>
                            <td>{{ optional($referral->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No referrals found.</td>
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
