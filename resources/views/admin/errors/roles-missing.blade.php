@extends('admin.layouts.app')

@section('title', 'Role Configuration Error')

@section('content')
    <div class="alert alert-warning">
        <h5 class="mb-2">Role configuration required</h5>
        <p class="mb-2">
            The admin panel is missing required role keys. Please contact support to configure the roles table.
        </p>
        @if (! empty($missingRoles))
            <div class="small text-muted">
                Missing role keys: {{ implode(', ', $missingRoles) }}
            </div>
        @endif
    </div>
@endsection
