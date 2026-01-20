@extends('admin.layouts.app')

@section('title', 'Business Deals')

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
                    <a href="{{ route('admin.activities.business-deals', $member) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Deal With</th>
                        <th>Deal Date</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Comment</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $deal)
                        <tr>
                            <td>
                                <div>{{ $deal->toUser->display_name ?? trim(($deal->toUser->first_name ?? '') . ' ' . ($deal->toUser->last_name ?? '')) ?: '—' }}</div>
                                <div class="text-muted small">{{ $deal->toUser->email ?? '—' }}</div>
                            </td>
                            <td>{{ $deal->deal_date ?? '—' }}</td>
                            <td>{{ $deal->deal_amount !== null ? number_format((float) $deal->deal_amount, 2) : '—' }}</td>
                            <td>{{ $deal->business_type ?? '—' }}</td>
                            <td class="text-muted">{{ $deal->comment ?? '—' }}</td>
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
