@extends('admin.layouts.app')

@section('title', 'Coin Claims')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-2">Coin claims table not available</h5>
            <p class="text-muted mb-0">
                Unable to load coin claim requests because the expected table
                <code>{{ $expected_table }}</code> does not exist in this environment.
            </p>
        </div>
    </div>
@endsection
