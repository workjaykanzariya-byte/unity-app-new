@extends('admin.layouts.app')

@section('title', 'Register A Visitor')

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
        <h1 class="h4 mb-0">Register A Visitor</h1>
        <span class="badge bg-light text-dark border">Total: {{ number_format($items->total()) }}</span>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Submitted At</th>
                        <th>Peer Name</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                        <th>Visitor Name</th>
                        <th>Visitor Mobile</th>
                        <th>Visitor City</th>
                        <th>Visitor Business</th>
                        <th>Status</th>
                        <th>Coins Awarded</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $peer = $item->user;
                            $peerName = $displayName($peer->display_name ?? null, $peer->first_name ?? null, $peer->last_name ?? null);
                            $visitorSearch = $item->visitor_mobile ? ['search' => $item->visitor_mobile] : [];
                        @endphp
                        <tr>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                            <td>{{ $peerName }}</td>
                            <td>{{ $item->event_name ?? '—' }}</td>
                            <td>{{ $formatDate($item->event_date ?? null) }}</td>
                            <td>{{ $item->visitor_full_name ?? '—' }}</td>
                            <td>{{ $item->visitor_mobile ?? '—' }}</td>
                            <td>{{ $item->visitor_city ?? '—' }}</td>
                            <td>{{ $item->visitor_business ?? '—' }}</td>
                            <td>{{ ucfirst($item->status ?? '—') }}</td>
                            <td>{{ $item->coins_awarded ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                @if ($item->visitor_mobile)
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.visitor-registrations.index', $visitorSearch) }}">
                                        View in Visitor Registrations
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">No visitor registrations found.</td>
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
