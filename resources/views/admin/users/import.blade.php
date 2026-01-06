@extends('admin.layouts.app')

@section('title', 'Import Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Import Users</h5>
        <small class="text-muted">Upload a CSV to create or update users</small>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Back to Users</a>
</div>

@if (!empty($error))
    <div class="alert alert-danger">{{ $error }}</div>
@endif

@if (!empty($results))
    <div class="alert alert-info">
        <div class="fw-semibold mb-1">Import Summary</div>
        <div>Created: {{ $results['created'] ?? 0 }}</div>
        <div>Updated: {{ $results['updated'] ?? 0 }}</div>
        <div>Failed: {{ count($results['failed'] ?? []) }}</div>
        @if (!empty($results['failed']))
            <div class="mt-2">
                <div class="fw-semibold">Failures</div>
                <ul class="mb-0 small">
                    @foreach ($results['failed'] as $fail)
                        <li>{{ $fail['reason'] ?? 'Unknown error' }} @if(!empty($fail['row'])) — {{ json_encode($fail['row']) }} @endif</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif

<div class="card p-3">
    <form action="{{ route('admin.users.import.submit') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">CSV File</label>
            <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
            <small class="text-muted">CSV only. Supported columns: email, first_name, last_name, display_name, phone, company_name, membership_status, city, coins_balance (id optional).</small>
        </div>
        <button type="submit" class="btn btn-primary">Upload & Import</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div>
@endsection
