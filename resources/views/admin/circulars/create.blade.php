@extends('admin.layouts.app')

@section('title', 'Create Circular')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Create Circular</h5>
        <small class="text-muted">Create and target circular content</small>
    </div>
    <a href="{{ route('admin.circulars.index') }}" class="btn btn-outline-secondary btn-sm">Back to Circulars</a>
</div>

<form action="{{ route('admin.circulars.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @include('admin.circulars._form')
</form>
@endsection
