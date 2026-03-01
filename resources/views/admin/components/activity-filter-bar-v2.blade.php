@php
    $label = $titleLabel ?? 'Search created by';
    $filters = $filters ?? [];
    $q = $filters['q'] ?? request('q', '');
    $from = $filters['from'] ?? request('from', '');
    $to = $filters['to'] ?? request('to', '');
    $circleId = (string) ($filters['circle_id'] ?? request('circle_id', ''));
@endphp

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="{{ $actionUrl }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="activityFilterQuery" class="form-label small text-muted">{{ $label }}</label>
                <input id="activityFilterQuery" type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Name, company, city, or circle">
            </div>
            <div class="col-md-2">
                <label for="activityFilterFrom" class="form-label small text-muted">From</label>
                <input id="activityFilterFrom" type="date" name="from" value="{{ $from }}" class="form-control" placeholder="dd-mm-yyyy">
            </div>
            <div class="col-md-2">
                <label for="activityFilterTo" class="form-label small text-muted">To</label>
                <input id="activityFilterTo" type="date" name="to" value="{{ $to }}" class="form-control" placeholder="dd-mm-yyyy">
            </div>
            <div class="col-md-2">
                <label for="activityFilterCircle" class="form-label small text-muted">Circle</label>
                <select id="activityFilterCircle" name="circle_id" class="form-select">
                    <option value="">All Circles</option>
                    @foreach (($circles ?? collect()) as $circle)
                        <option value="{{ $circle->id }}" @selected($circleId !== '' && $circleId === (string) $circle->id)>{{ $circle->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Apply</button>
                <a href="{{ $resetUrl }}" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
        @if (!empty($showExport) && !empty($exportUrl))
            <div class="mt-2 d-flex justify-content-end">
                <a href="{{ $exportUrl }}" class="btn btn-outline-primary">Export</a>
            </div>
        @endif
    </div>
</div>
