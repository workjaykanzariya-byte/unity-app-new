@extends('admin.layouts.app')

@section('title', 'Coins Ledger')

@section('content')
    @php
        $memberName = $member->display_name ?? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));
        $heading = $memberName ? $memberName . ' Coins Ledger' : 'Coins Ledger';
        $labelForType = function (?string $type): ?string {
            return $type ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $type)) : null;
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">{{ $heading }}</h5>
            <small class="text-muted">{{ $member->email }}</small>
        </div>
        <a href="{{ route('admin.coins.index') }}" class="btn btn-outline-secondary">Back to Coins</a>
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
                    <a href="{{ $activeType ? route('admin.coins.ledger.type', [$member, $activeType]) : route('admin.coins.ledger', $member) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
                @if ($activeType)
                    <span class="badge bg-light text-dark border ms-auto">Type: {{ $labelForType($activeType) }}</span>
                @endif
            </form>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Coins</th>
                        <th>Balance After</th>
                        <th>Why</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $reference = $item->reference;
                            $activityType = $activeType ?? ($activityTypes[$item->activity_id] ?? null);
                            $activityLabel = $labelForType($activityType);
                            $reason = $reference ?: ($activityLabel ? 'Activity: ' . $activityLabel : '—');
                            $activityShort = $item->activity_id ? substr($item->activity_id, 0, 8) : null;
                            if (! $reference && $activityLabel && $activityShort) {
                                $reason .= ' (' . $activityShort . ')';
                            }
                            $createdBy = $item->created_by ? ($createdByUsers[$item->created_by] ?? null) : null;
                            $createdByName = $createdBy
                                ? ($createdBy->display_name ?? trim(($createdBy->first_name ?? '') . ' ' . ($createdBy->last_name ?? '')) ?: '—')
                                : null;
                        @endphp
                        <tr>
                            <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>{{ $item->amount }}</td>
                            <td>{{ $item->balance_after }}</td>
                            <td class="text-muted">{{ $reason }}</td>
                            <td>
                                @if ($createdBy)
                                    <div>{{ $createdByName }}</div>
                                    <div class="text-muted small">{{ $createdBy->email ?? '—' }}</div>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No ledger entries found.</td>
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
