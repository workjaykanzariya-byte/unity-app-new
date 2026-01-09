@extends('admin.layouts.app')

@section('title', 'Business Deals')

@section('content')
    @php
        $memberName = $member->display_name ?? trim($member->first_name . ' ' . $member->last_name);
    @endphp

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">Business Deals for {{ $memberName ?: 'Member' }}</h1>
            <p class="text-muted mb-0">All business deals submitted by this member.</p>
        </div>
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Deal With</th>
                        <th>Deal Date</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $deal)
                        <tr>
                            <td class="font-monospace">{{ substr($deal->id, 0, 8) }}</td>
                            <td>
                                <div>{{ $deal->toUser->display_name ?? trim(($deal->toUser->first_name ?? '') . ' ' . ($deal->toUser->last_name ?? '')) ?: '—' }}</div>
                                <div class="text-muted small">{{ $deal->toUser->email ?? '—' }}</div>
                            </td>
                            <td>{{ $deal->deal_date ?? '—' }}</td>
                            <td>{{ $deal->deal_amount !== null ? number_format((float) $deal->deal_amount, 2) : '—' }}</td>
                            <td>{{ $deal->business_type ?? '—' }}</td>
                            <td>{{ optional($deal->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No business deals found.</td>
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
