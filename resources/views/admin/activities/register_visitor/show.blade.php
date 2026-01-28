@extends('admin.layouts.app')

@section('title', 'Register A Visitor - Peer Activity')

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

        $peerName = $displayName($peer->display_name ?? null, $peer->first_name ?? null, $peer->last_name ?? null);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h1 class="h4 mb-1">Register A Visitor</h1>
            <div class="text-muted">Peer: {{ $peerName }} ({{ $peer->email ?? '—' }})</div>
        </div>
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Submitted At</th>
                        <th>Event Type</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                        <th>Visitor Name</th>
                        <th>Visitor Mobile</th>
                        <th>Visitor City</th>
                        <th>Visitor Business</th>
                        <th>Status</th>
                        <th>Coins Awarded</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                            <td>{{ ucfirst($item->event_type ?? '—') }}</td>
                            <td>{{ $item->event_name ?? '—' }}</td>
                            <td>{{ $formatDate($item->event_date ?? null) }}</td>
                            <td>{{ $item->visitor_full_name ?? '—' }}</td>
                            <td>{{ $item->visitor_mobile ?? '—' }}</td>
                            <td>{{ $item->visitor_city ?? '—' }}</td>
                            <td>{{ $item->visitor_business ?? '—' }}</td>
                            <td>{{ ucfirst($item->status ?? '—') }}</td>
                            <td>{{ $item->coins_awarded ? 'Yes' : 'No' }}</td>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">No entries found.</td>
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
