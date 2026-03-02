<div class="table-responsive">
    <table class="table mb-0 align-middle">
        <thead class="table-light">
            <tr>
                <th>Rank</th>
                <th>Peer Name</th>
                <th>{{ $totalLabel }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($rows ?? collect()) as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $row->peer_name ?? '-' }}</div>
                        @if (!empty($row->peer_company))
                            <div class="small text-muted">{{ $row->peer_company }}</div>
                        @endif
                        @if (!empty($row->peer_city))
                            <div class="small text-muted">{{ $row->peer_city }}</div>
                        @endif
                    </td>
                    <td>{{ (int) ($row->total ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">No data found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
