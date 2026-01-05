@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-container">
    <div class="topbar">
        <div>
            <p class="eyebrow">Peers Global Unity</p>
            <h2>Admin Dashboard</h2>
            <p class="muted">Secure administrative control panel.</p>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn secondary">Logout</button>
        </form>
    </div>

    <div class="grid">
        <div class="surface">
            <h3>Welcome back</h3>
            <p class="muted">You are signed in as {{ optional(auth()->guard('admin')->user())->name ?? 'Admin' }}.</p>
        </div>
    </div>
</div>
@endsection
