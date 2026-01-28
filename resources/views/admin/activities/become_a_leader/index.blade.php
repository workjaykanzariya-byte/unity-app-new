@extends('admin.layouts.app')

@section('title', 'Become A Leader')

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

        $formatRoles = function ($roles): string {
            if (! $roles) {
                return '—';
            }
            $list = is_array($roles) ? $roles : (array) $roles;
            $list = array_filter($list);
            return $list ? implode(', ', $list) : '—';
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Become A Leader</h1>
        <span class="badge bg-light text-dark border">Total: {{ number_format($items->total()) }}</span>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Submitted At</th>
                        <th>Peer Name</th>
                        <th>Applying For</th>
                        <th>Referred Name</th>
                        <th>Referred Mobile</th>
                        <th>Leadership Roles</th>
                        <th>City / Region</th>
                        <th>Primary Domain</th>
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
                            <td>{{ $peerName }}</td>
                            <td>{{ $item->applying_for ?? '—' }}</td>
                            <td>{{ $item->referred_name ?? '—' }}</td>
                            <td>{{ $item->referred_mobile ?? '—' }}</td>
                            <td>{{ $formatRoles($item->leadership_roles ?? null) }}</td>
                            <td>{{ $item->contribute_city ?? '—' }}</td>
                            <td>{{ $item->primary_domain ?? '—' }}</td>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">No submissions found.</td>
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
