@extends('admin.layouts.app')

@section('title', 'Collaboration Details')

@section('content')
@php
    $user = $post->user;
    $name = $user?->name ?: $user?->display_name ?: trim(($user?->first_name ?? '') . ' ' . ($user?->last_name ?? ''));
    $name = $name !== '' ? $name : 'Unnamed Peer';
    $userCompany = $user?->company_name ?? $user?->company ?? $user?->business_name ?? null;
    $userCity = $user?->city ?? $user?->current_city ?? null;
    $postCity = $post->city ?? null;
    $displayCity = $postCity ?: $userCity;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h2 class="h4 mb-1">Collaboration Post Details</h2>
        <div class="text-muted small">Post ID: {{ $post->id }}</div>
    </div>
    <div>
        <a href="{{ $backUrl }}" class="btn btn-sm btn-outline-secondary">Back to Collaborations</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Peer Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">{{ $name }}</dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">{{ $user?->email ?? '—' }}</dd>
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8">{{ $user?->phone ?? '—' }}</dd>
                    <dt class="col-sm-4">Company</dt>
                    <dd class="col-sm-8">{{ $userCompany ?? '—' }}</dd>
                    <dt class="col-sm-4">City</dt>
                    <dd class="col-sm-8">{{ $userCity ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Post Info</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8">{{ $post->collaborationType?->name ?? $post->collaboration_type ?? '—' }}</dd>
                    <dt class="col-sm-4">Title</dt>
                    <dd class="col-sm-8">{{ $post->title ?? '—' }}</dd>
                    <dt class="col-sm-4">Description</dt>
                    <dd class="col-sm-8">{{ $post->description ?? '—' }}</dd>
                    <dt class="col-sm-4">City</dt>
                    <dd class="col-sm-8">{{ $displayCity ?? '—' }}</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">{{ ucfirst((string) ($post->status ?? '—')) }}</dd>
                    <dt class="col-sm-4">Created At</dt>
                    <dd class="col-sm-8">{{ $post->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
