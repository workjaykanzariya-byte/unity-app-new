@extends('admin.layouts.app')

@section('title', 'Coin Claims')

@section('content')
    @php
        $displayName = function ($user): string {
            if (! $user) return '—';
            if (! empty($user->display_name)) return $user->display_name;
            $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            return $name !== '' ? $name : '—';
        };
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Coin Claims</h1>
        <span class="badge bg-light text-dark border">Total: {{ number_format($claims->total()) }}</span>
    </div>

    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="card shadow-sm mb-3"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted">Search peer</label>
                <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name or phone">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    @foreach (['pending','approved','rejected','all'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary">Apply</button>
                <a href="{{ route('admin.coin-claims.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div></div>

    <div class="card shadow-sm"><div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light"><tr>
                <th>Submitted At</th><th>Peer Name</th><th>Peer Phone</th><th>Activity</th><th>Key Fields</th><th>Status</th><th class="text-end">Actions</th>
            </tr></thead>
            <tbody>
            @forelse ($claims as $claim)
                @php
                    $fields = (array) data_get($claim->payload, 'fields', []);
                    $keyFields = collect($fields)->except(collect($fields)->keys()->filter(fn($k) => str_ends_with($k, '_normalized'))->all())->map(fn($v,$k)=>$k.': '.$v)->take(3)->implode(', ');
                @endphp
                <tr>
                    <td>{{ optional($claim->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ $displayName($claim->user) }}</td>
                    <td>{{ $claim->user->phone ?? '—' }}</td>
                    <td>{{ data_get($registry->get($claim->activity_code), 'label', $claim->activity_code) }}</td>
                    <td>{{ $keyFields ?: '—' }}</td>
                    <td>{{ ucfirst($claim->status) }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.coin-claims.show', $claim->id) }}" class="btn btn-sm btn-outline-primary">Details</a>
                        @if ($claim->status === 'pending')
                            <form method="POST" action="{{ route('admin.coin-claims.approve', $claim->id) }}" class="d-inline">@csrf
                                <button class="btn btn-sm btn-success" onclick="return confirm('Approve this claim?')">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.coin-claims.reject', $claim->id) }}" class="d-inline">@csrf
                                <input type="hidden" name="admin_note" value="Rejected by admin">
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this claim?')">Reject</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No coin claims found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>

    <div class="mt-3">{{ $claims->links() }}</div>
@endsection
