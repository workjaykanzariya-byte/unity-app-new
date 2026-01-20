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

        $normalizeMedia = function ($media): array {
            if (! $media) {
                return [];
            }

            $decoded = is_string($media) ? json_decode($media, true) : $media;

            return is_array($decoded) ? array_values($decoded) : [$decoded];
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
                            $mediaItems = $normalizeMedia($requirement->media ?? null);
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
                                @if ($mediaInfo['has'])
                                    <span class="badge bg-success">Yes ({{ $mediaInfo['count'] }})</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary ms-2 js-view-media" data-media='@json($mediaItems)'>View</button>
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
