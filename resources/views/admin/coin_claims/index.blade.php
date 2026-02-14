@extends('admin.layouts.app')

@section('title', 'Coin Claims')

@section('content')
    @php
        $displayName = function (?string $display, ?string $first, ?string $last): string {
            if ($display) {
                return $display;
            }
            $name = trim(($first ?? '') . ' ' . ($last ?? ''));
            return $name !== '' ? $name : '—';
        };
        $formatDateTime = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : '—';
        $status = $filters['status'] ?? 'pending';
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Coin Claims</h1>
        <span class="badge bg-light text-dark border">Total: {{ number_format($claims->total()) }}</span>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search Peer</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name or phone">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending" @selected($status === 'pending')>Pending</option>
                        <option value="approved" @selected($status === 'approved')>Approved</option>
                        <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                        <option value="all" @selected($status === 'all')>All</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.coin-claims.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Activity</th>
                        <th>Key Fields</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($claims as $claim)
                        @php
                            $member = $claim->user;
                            $memberName = $displayName($member->display_name ?? null, $member->first_name ?? null, $member->last_name ?? null);
                            $payload = is_array($claim->payload) ? $claim->payload : [];
                            $activityLabel = $activityLabels[$claim->activity_code] ?? $claim->activity_code;
                        @endphp
                        <tr>
                            <td>{{ $formatDateTime($claim->created_at) }}</td>
                            <td>{{ $memberName }}</td>
                            <td>{{ $member->phone ?? '—' }}</td>
                            <td>{{ $activityLabel }}</td>
                            <td>
                                @forelse (collect($payload)->take(3) as $key => $value)
                                    <div><strong>{{ $key }}:</strong> {{ is_scalar($value) ? $value : json_encode($value) }}</div>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td>{{ ucfirst($claim->status) }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#claimModal-{{ $claim->id }}">Details</button>
                                @if ($claim->status === 'pending')
                                    <form method="POST" action="{{ route('admin.coin-claims.approve', $claim->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this coin claim?')">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.coin-claims.reject', $claim->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this coin claim?')">Reject</button>
                                    </form>
                                @endif
                            </td>
                        </tr>

                        <div class="modal fade" id="claimModal-{{ $claim->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Coin Claim Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Activity:</strong> {{ $activityLabel }}</p>
                                        <p><strong>Status:</strong> {{ ucfirst($claim->status) }}</p>
                                        <hr>
                                        @foreach ($payload as $key => $value)
                                            <div class="mb-2">
                                                <strong>{{ $key }}:</strong>
                                                @if (is_string($value) && str_ends_with($key, '_file_id'))
                                                    <a href="{{ url('/api/v1/files/'.$value) }}" target="_blank">Download File</a>
                                                @else
                                                    <span>{{ is_scalar($value) ? $value : json_encode($value) }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No coin claims found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $claims->links() }}</div>
@endsection
