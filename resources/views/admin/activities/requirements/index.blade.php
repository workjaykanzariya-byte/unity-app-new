@extends('admin.layouts.app')

@section('title', 'Requirements')

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

        $mediaSummary = function ($media): array {
            if (! $media) {
                return ['has' => false, 'count' => 0];
            }

            $decoded = is_string($media) ? json_decode($media, true) : $media;

            if (is_array($decoded)) {
                $count = count($decoded);
                return ['has' => $count > 0, 'count' => $count];
            }

            return ['has' => true, 'count' => 1];
        };

        $firstMediaId = function ($media): ?string {
            if (! $media) {
                return null;
            }

            $decoded = is_string($media) ? json_decode($media, true) : $media;
            $items = is_array($decoded) ? array_values($decoded) : [$decoded];
            $first = $items[0] ?? null;

            if (is_string($first)) {
                return $first;
            }

            if (is_array($first)) {
                return $first['file_id'] ?? $first['fileId'] ?? $first['id'] ?? null;
            }

            if (is_object($first)) {
                return $first->file_id ?? $first->fileId ?? $first->id ?? null;
            }

            return null;
        };

        $decodeFilter = function ($value): array {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            }

            return [];
        };
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Requirements</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.activities.requirements.export', request()->query()) }}" class="btn btn-outline-primary">Export</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search created by</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name, email, company, or city">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <input type="text" name="status" value="{{ $filters['status'] }}" class="form-control" placeholder="Status">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                </div>
                <div class="col-md-1 d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.activities.requirements.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <strong>Top 3 Peers</strong>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Peers</th>
                        <th>Total Requirements</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topMembers as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div>{{ $displayName($member->display_name ?? null, $member->first_name ?? null, $member->last_name ?? null) }}</div>
                                <div class="text-muted small">{{ $member->email ?? '—' }}</div>
                            </td>
                            <td>{{ $member->total_count ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Created By</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>Region</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Media</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $requirement)
                        @php
                            $actorName = $displayName($requirement->actor_display_name ?? null, $requirement->actor_first_name ?? null, $requirement->actor_last_name ?? null);
                            $mediaInfo = $mediaSummary($requirement->media ?? null);
                            $mediaId = $firstMediaId($requirement->media ?? null);
                            $regionFilter = $decodeFilter($requirement->region_filter ?? null);
                            $categoryFilter = $decodeFilter($requirement->category_filter ?? null);
                            $regionLabel = $regionFilter['region_label'] ?? $regionFilter['region_name'] ?? $regionFilter['city_name'] ?? null;
                            $categoryLabel = $categoryFilter['category'] ?? null;
                        @endphp
                        <tr>
                            <td>
                                <div>{{ $actorName }}</div>
                                <div class="text-muted small">{{ $requirement->actor_email ?? '—' }}</div>
                            </td>
                            <td>{{ $requirement->subject ?? '—' }}</td>
                            <td class="text-muted">{{ $requirement->description ?? '—' }}</td>
                            <td>{{ $regionLabel ?: '—' }}</td>
                            <td>{{ $categoryLabel ?: '—' }}</td>
                            <td>{{ $requirement->status ?? '—' }}</td>
                            <td>
                                @if ($mediaInfo['has'] && $mediaId)
                                    <span class="badge bg-success">Yes ({{ $mediaInfo['count'] }})</span>
                                    <a href="{{ url('/api/v1/files/' . $mediaId) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary ms-2">View</a>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>{{ $formatDateTime($requirement->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted">No requirements found.</td>
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
