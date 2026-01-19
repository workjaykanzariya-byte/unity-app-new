@extends('admin.layouts.app')

@section('title', 'Access Denied')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="display-6 text-danger mb-3">
                        <i class="bi bi-shield-exclamation"></i>
                    </div>
                    <h1 class="h4 mb-2">Access denied</h1>
                    <p class="text-muted mb-0">You do not have access.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
