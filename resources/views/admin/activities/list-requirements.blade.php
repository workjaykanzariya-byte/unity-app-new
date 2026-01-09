@extends('admin.layouts.app')

@section('title', 'Requirements')

@section('content')
    @php
        $resolveFileUrl = function ($value) {
            if (! $value) {
                return null;
            }

            if (is_string($value) && (str_starts_with($value, 'http://') || str_starts_with($value, 'https://'))) {
                return $value;
            }

            if (is_string($value) && \Illuminate\Support\Str::isUuid($value)) {
                return url('/api/v1/files/' . $value);
            }

            return null;
        };

        $extractMediaUrl = function ($media) use ($resolveFileUrl) {
            if (! $media) {
                return null;
            }

            if (is_array($media)) {
                $first = $media[0] ?? null;
                if (is_array($first)) {
                    $id = $first['id'] ?? null;
                    $url = $first['url'] ?? null;
                    return $resolveFileUrl($url ?: $id);
                }
            }

            return $resolveFileUrl($media);
        };
    @endphp

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.activities.index') }}" class="btn btn-outline-secondary">Back to Activities</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>Region</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Attachment</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $requirement)
                        @php
                            $attachmentUrl = $extractMediaUrl($requirement->media ?? null);
                            $regionLabel = $requirement->region_filter['region_label'] ?? null;
                            $cityName = $requirement->region_filter['city_name'] ?? null;
                            $category = $requirement->category_filter['category'] ?? null;
                        @endphp
                        <tr>
                            <td class="font-monospace">{{ substr($requirement->id, 0, 8) }}</td>
                            <td>{{ $requirement->subject ?? '—' }}</td>
                            <td class="text-muted">{{ $requirement->description ?? '—' }}</td>
                            <td>{{ trim(($regionLabel ?? '') . ($cityName ? ', ' . $cityName : '')) ?: '—' }}</td>
                            <td>{{ $category ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary text-uppercase">{{ $requirement->status ?? 'open' }}</span>
                            </td>
                            <td>
                                @if ($attachmentUrl)
                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ optional($requirement->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
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
