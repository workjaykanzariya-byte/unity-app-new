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

        $truncate = function ($value, int $limit = 80): string {
            return $value ? \Illuminate\Support\Str::limit($value, $limit) : '—';
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Become A Leader</h1>
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
                    <a href="{{ route('admin.activities.become-a-leader.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
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
                        <th>Applying For</th>
                        <th>Referred Name</th>
                        <th>Referred Mobile</th>
                        <th>Leadership Roles</th>
                        <th>City / Region</th>
                        <th>Primary Domain</th>
                        <th>Why Interested</th>
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
                            <td>{{ $item->applying_for ?? '—' }}</td>
                            <td>{{ $item->referred_name ?? '—' }}</td>
                            <td>{{ $item->referred_mobile ?? '—' }}</td>
                            <td>{{ $formatRoles($item->leadership_roles ?? null) }}</td>
                            <td>{{ $item->contribute_city ?? '—' }}</td>
                            <td>{{ $item->primary_domain ?? '—' }}</td>
                            <td>{{ $truncate($item->why_interested ?? null) }}</td>
                            <td>{{ $formatDateTime($item->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted">No submissions found.</td>
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
