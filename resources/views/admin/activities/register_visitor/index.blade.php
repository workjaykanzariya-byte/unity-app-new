@extends('admin.layouts.app')

@section('title', 'Register A Visitor')

@section('content')
    <style>
        .peer-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; display: block; }
    </style>
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

    <form id="adminactivitiesregister-visitorindexFiltersForm" method="GET" action="{{ route('admin.activities.register-visitor.index') }}">
    @include('admin.components.activity-filter-bar-v2', [
        'actionUrl' => route('admin.activities.register-visitor.index'),
        'resetUrl' => route('admin.activities.register-visitor.index'),
        'filters' => $filters,
        'circles' => $circles ?? collect(),
        'showExport' => false,
        'renderFormTag' => false,
        'formId' => 'adminactivitiesregister-visitorindexFiltersForm',
    ])

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Submitted At</th>
                        <th>Peer Name</th>
                        <th>Peer Phone</th>
                        <th>Event Type</th>
                        <th>Event Name</th>
                        <th>Event Date</th>
                        <th>Visitor Name</th>
                        <th>Visitor Mobile</th>
                        <th>Visitor City</th>
                        <th>Visitor Business</th>
                        <th>Status</th>
                        <th>Coins Awarded</th>
                        <th class="text-end">Actions</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $peerName = $item->peer_name ?? '—';
                            $visitorSearch = $item->visitor_mobile ? ['search' => $item->visitor_mobile] : [];
                        @endphp
                        <tr>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                            <td>
                                @include('admin.components.peer-card', [
                                    'name' => $peerName,
                                    'company' => $item->peer_company ?? '',
                                    'city' => $item->peer_city ?? '',
                                ])
                            </td>
                            <td>{{ $item->peer_phone ?? '—' }}</td>
                            <td>{{ ucfirst($item->event_type ?? '—') }}</td>
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
                                        Open Approval Page
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted">No visitor registrations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    </form>

    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endsection
