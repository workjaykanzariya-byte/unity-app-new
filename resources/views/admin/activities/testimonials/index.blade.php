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

        $normalizeMedia = function ($media): array {
            if (! $media) {
                return [];
            }

            $decoded = is_string($media) ? json_decode($media, true) : $media;

            return is_array($decoded) ? array_values($decoded) : [$decoded];
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
                    <label class="form-label small text-muted">Search created by</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Name, email, company, or city">
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
            <strong>Top 3 Peers</strong>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Rank</th>
                        <th>Peers</th>
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
                        <th>Created By</th>
                        <th>Related Peer</th>
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
                            $mediaItems = $normalizeMedia($testimonial->media ?? null);
                        @endphp
                        <tr>
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
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-2 js-view-media" data-media='@json($mediaItems)'>View</button>
                                @else
                                    <span class="text-muted">No</span>
                                @endif
                            </td>
                            <td>{{ $formatDateTime($testimonial->created_at ?? null) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No testimonials found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links() }}
    </div>

    <div class="modal fade" id="mediaModal" tabindex="-1" aria-labelledby="mediaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaModalLabel">Media</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="mediaModalBody">
                    <p class="text-muted mb-0">No media available.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.js-view-media');
            if (!button) {
                return;
            }

            let items = [];
            const payload = button.getAttribute('data-media') || '[]';

            try {
                items = JSON.parse(payload);
            } catch (error) {
                items = [];
            }

            const modalElement = document.getElementById('mediaModal');
            const container = document.getElementById('mediaModalBody');
            container.innerHTML = '';

            if (!Array.isArray(items) || items.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">No media available.</p>';
                new bootstrap.Modal(modalElement).show();
                return;
            }

            items.forEach((item, index) => {
                let fileId = null;
                let type = null;
                let thumbnailId = null;

                if (typeof item === 'string') {
                    fileId = item;
                } else if (item && typeof item === 'object') {
                    fileId = item.file_id || item.fileId || item.id || null;
                    type = item.type || item.media_type || item.mime_type || null;
                    thumbnailId = item.thumbnail_file_id || item.thumbnail_id || null;

                    if (!fileId && item.url && typeof item.url === 'string') {
                        const match = item.url.match(/[0-9a-fA-F-]{36}/);
                        fileId = match ? match[0] : null;
                    }
                }

                if (!fileId) {
                    return;
                }

                const url = `/api/v1/files/${fileId}`;
                const wrapper = document.createElement('div');
                wrapper.classList.add('border', 'rounded', 'p-2', 'mb-3');

                const link = document.createElement('a');
                link.href = url;
                link.target = '_blank';
                link.rel = 'noopener';
                link.textContent = `Media ${index + 1}`;
                link.classList.add('d-block', 'mb-2');
                wrapper.appendChild(link);

                const isVideo = type && type.toString().toLowerCase().includes('video');

                if (isVideo) {
                    const video = document.createElement('video');
                    video.src = url;
                    video.controls = true;
                    video.classList.add('w-100', 'mb-3');
                    if (thumbnailId) {
                        video.poster = `/api/v1/files/${thumbnailId}`;
                    }
                    wrapper.appendChild(video);
                } else {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = `Media ${index + 1}`;
                    img.classList.add('img-fluid', 'rounded', 'mb-3');
                    wrapper.appendChild(img);
                }

                container.appendChild(wrapper);
            });

            new bootstrap.Modal(modalElement).show();
        });
    </script>
@endsection
