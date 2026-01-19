@extends('admin.layouts.app')

@section('title', 'Access Denied')

@section('content')
    <div class="alert alert-danger">
        <h5 class="mb-1">Access denied</h5>
        <p class="mb-0">{{ $message ?? 'You do not have permission to access this section.' }}</p>
    </div>
@endsection
