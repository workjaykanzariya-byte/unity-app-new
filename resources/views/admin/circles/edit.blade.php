@extends('admin.layouts.app')

@section('title', 'Edit Circle')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Edit Circle</h5>
        <small class="text-muted">Update circle settings</small>
    </div>
    <a href="{{ route('admin.circles.show', $circle) }}" class="btn btn-outline-secondary btn-sm">Back to Details</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 small">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@php
    $industryTagsValue = old('industry_tags', $circle->industry_tags ? implode(', ', $circle->industry_tags) : '');
    $rankDisplay = $rank['rank_label'] . ' – ' . $rank['circle_title'];
@endphp

<div class="card mb-3">
    <div class="card-header fw-semibold">Circle Image</div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            @if($circle->image_file_id)
                <img src="{{ url('/api/v1/files/'.$circle->image_file_id) }}" alt="Circle image" style="width:100px;height:100px;object-fit:cover" class="rounded border">
            @else
                <div class="rounded border d-flex align-items-center justify-content-center text-muted" style="width:100px;height:100px;">No image</div>
            @endif
            <div><small class="text-muted">Upload JPG/PNG/WEBP up to 5MB.</small></div>
        </div>
        <form action="{{ route('admin.circles.image.update', $circle) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-6"><input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" required></div>
            <div class="col-md-3"><button type="submit" class="btn btn-outline-primary">Update Circle Image</button></div>
        </form>
    </div>
</div>

<form action="{{ route('admin.circles.update', $circle) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-header fw-semibold">Circle Settings</div>
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label">Name</label><input type="text" name="name" class="form-control" value="{{ old('name', $circle->name) }}" required></div>
            <div class="col-md-6"><label class="form-label">Type</label><select name="type" class="form-select" required>@foreach($types as $type)<option value="{{ $type }}" @selected(old('type',$circle->type)===$type)>{{ ucfirst($type) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Stage</label><select name="stage" class="form-select"><option value="">Select stage</option>@foreach($stages as $key => $label)<option value="{{ $key }}" @selected(old('stage',$circle->stage)===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Meeting Mode</label><select name="meeting_mode" class="form-select"><option value="">Select mode</option>@foreach($meetingModes as $mode)<option value="{{ $mode }}" @selected(old('meeting_mode',$circle->meeting_mode)===$mode)>{{ ucfirst($mode) }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Meeting Frequency</label><select name="meeting_frequency" class="form-select"><option value="">Select frequency</option>@foreach($meetingFrequencies as $frequency)<option value="{{ $frequency }}" @selected(old('meeting_frequency',$circle->meeting_frequency)===$frequency)>{{ ucfirst($frequency) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Meeting Repeat</label><input type="text" name="meeting_repeat" class="form-control" value="{{ old('meeting_repeat', $circle->meeting_repeat) }}"></div>
            <div class="col-md-3"><label class="form-label">Launch Date</label><input type="date" name="launch_date" class="form-control" value="{{ old('launch_date', optional($circle->launch_date)->toDateString()) }}"></div>
            <div class="col-md-3"><label class="form-label">Circle Experience Annual Fee</label><input type="number" min="0" name="annual_fee" class="form-control" value="{{ old('annual_fee', $circle->annual_fee) }}"></div>

            <div class="col-md-6"><label class="form-label">Founder</label><select name="founder_user_id" class="form-select" required><option value="">Select user</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('founder_user_id',$circle->founder_user_id)===$u->id)>{{ $u->display_name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? '')) }} @if($u->email)- {{ $u->email }} @endif</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Director</label><select name="director_user_id" class="form-select"><option value="">Select user</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('director_user_id',$circle->director_user_id)===$u->id)>{{ $u->display_name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? '')) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">Industry Director</label><select name="industry_director_user_id" class="form-select"><option value="">Select user</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('industry_director_user_id',$circle->industry_director_user_id)===$u->id)>{{ $u->display_name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? '')) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">DED</label><select name="ded_user_id" class="form-select"><option value="">Select user</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('ded_user_id',$circle->ded_user_id)===$u->id)>{{ $u->display_name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? '')) }}</option>@endforeach</select></div>

            <div class="col-md-4"><label class="form-label">Country</label><select name="country" id="countrySelect" class="form-select">@foreach($countries as $country)<option value="{{ $country }}" @selected(old('country',$selectedCountry)===$country)>{{ $country }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">City</label><select name="city_id" class="form-select" required>@foreach($cities as $city)<option value="{{ $city->id }}" @selected(old('city_id',$circle->city_id)==$city->id)>{{ $city->name }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select" required>@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status',$circle->status)===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>

            <div class="col-md-12"><label class="form-label">Industry Tags</label><input type="text" name="industry_tags" class="form-control" value="{{ $industryTagsValue }}" placeholder="MSME, Real Estate"></div>
            <div class="col-md-6"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $circle->description) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Purpose</label><textarea name="purpose" class="form-control" rows="2">{{ old('purpose', $circle->purpose) }}</textarea></div>
            <div class="col-md-12"><label class="form-label">Announcement</label><textarea name="announcement" class="form-control" rows="2">{{ old('announcement', $circle->announcement) }}</textarea></div>

            <div class="col-md-6"><label class="form-label">Total Active Members</label><input type="text" class="form-control" value="{{ (int) $circle->active_members_count }}" readonly></div>
            <div class="col-md-6"><label class="form-label">Rank Display</label><input type="text" class="form-control" value="{{ $rankDisplay }}" readonly></div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-end gap-2">
        <a href="{{ route('admin.circles.show', $circle) }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.getElementById('countrySelect')?.addEventListener('change', (event) => {
        const url = new URL(window.location.href);
        url.searchParams.set('country', event.target.value);
        window.location.href = url.toString();
    });
</script>
@endpush
