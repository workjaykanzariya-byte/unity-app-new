@extends('admin.layouts.app')

@section('title', 'Testimonials')

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
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h1 class="h4 mb-0">Testimonials</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.activities.testimonials.export', request()->query()) }}" class="btn btn-outline-primary">Export</a>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search actor</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name or email">
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
                    <a href="{{ route('admin.activities.testimonials.index') }}" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
            <strong>Top 3 Members</strong>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Member</th>
                        <th>Total Testimonials</th>
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
                        <th>ID</th>
                        <th>Actor</th>
                        <th>Peer</th>
                        <th>Content</th>
                        <th>Media</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $testimonial)
                        @php
                            $actorName = $displayName($testimonial->actor_display_name ?? null, $testimonial->actor_first_name ?? null, $testimonial->actor_last_name ?? null);
                            $peerName = $displayName($testimonial->peer_display_name ?? null, $testimonial->peer_first_name ?? null, $testimonial->peer_last_name ?? null);
                            $mediaInfo = $mediaSummary($testimonial->media ?? null);
                        @endphp
                        <tr>
                            <td class="font-monospace">{{ $testimonial->id }}</td>
                            <td>
                                <div>{{ $actorName }}</div>
                                <div class="text-muted small">{{ $testimonial->actor_email ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $peerName }}</div>
                                <div class="text-muted small">{{ $testimonial->peer_email ?? '—' }}</div>
                            </td>
                            <td class="text-muted">{{ $testimonial->content ?? '—' }}</td>
                            <td>
                                @if ($mediaInfo['has'])
                                    <span class="badge bg-success">Yes ({{ $mediaInfo['count'] }})</span>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>{{ $formatDateTime($testimonial->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No testimonials found.</td>
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
