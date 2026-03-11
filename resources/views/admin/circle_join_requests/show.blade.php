@extends('admin.layouts.app')
@section('title', 'Circle Joining Request Detail')
@section('content')
<div class="container-fluid">
    <div class="card"><div class="card-body">
        <h5>Peer: {{ $record->user?->adminDisplayName() }}</h5>
        <p>Email: {{ $record->user?->email }} | Phone: {{ $record->user?->phone }}</p>
        <p>Company: {{ $record->user?->adminCompanyLabel() }} | City: {{ $record->user?->adminCityLabel() }}</p>
        <p>Circle: {{ $record->circle?->name }}</p>
        <p>Reason: {{ $record->reason_for_joining }}</p>
        <p>Status: <span class="badge text-bg-secondary">{{ $record->status }}</span></p>
        <hr>
        <p>CD Approved At: {{ optional($record->cd_approved_at)->format('d M Y H:i') }}</p>
        <p>CD Rejected At: {{ optional($record->cd_rejected_at)->format('d M Y H:i') }} ({{ $record->cd_rejection_reason }})</p>
        <p>ID Approved At: {{ optional($record->id_approved_at)->format('d M Y H:i') }}</p>
        <p>ID Rejected At: {{ optional($record->id_rejected_at)->format('d M Y H:i') }} ({{ $record->id_rejection_reason }})</p>
    </div></div>
</div>
@endsection
