@extends('admin.layouts.app')

@section('title', 'Recommend A Peer')

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
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Recommend A Peer</h1>
        <span class="badge bg-light text-dark border">Total: {{ number_format($items->total()) }}</span>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search created by</label>
                    <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Name, email, company, or city">
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
                    <a href="{{ route('admin.activities.recommend-peer.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Submitted At</th>
                        <th>Peer Name</th>
                        <th>Peer Phone</th>
                        <th>Recommended Peer Name</th>
                        <th>Recommended Peer Mobile</th>
                        <th>How Well Known</th>
                        <th>Is Aware</th>
                        <th>Coins Awarded</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $peer = $item->user;
                            $peerName = $displayName($peer->display_name ?? null, $peer->first_name ?? null, $peer->last_name ?? null);
                        @endphp
                        <tr>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                            <td><div class="fw-semibold text-truncate" style="max-width: 240px;">{{ $peerName }}</div>
                            <div class="text-muted small">{{ $peer->company_name ?? '—' }}</div>
                            <div class="text-muted small">{{ $peer->city ?? 'No City' }}</div></td>
                            <td>{{ $peer->phone ?? '—' }}</td>
                            <td>{{ $item->peer_name ?? '—' }}</td>
                            <td>{{ $item->peer_mobile ?? '—' }}</td>
                            <td>{{ $item->how_well_known ?? '—' }}</td>
                            <td>{{ $item->is_aware ? 'Yes' : 'No' }}</td>
                            <td>{{ $item->coins_awarded ? 'Yes' : 'No' }}</td>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No recommendations found.</td>
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
