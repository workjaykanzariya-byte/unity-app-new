@extends('admin.layouts.app')

@section('title', 'Add ' . $config['singular'])

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">Add {{ $config['singular'] }}</h1>
            <p class="text-muted mb-0">Create a new {{ strtolower($config['singular']) }} for {{ $member->display_name ?? $member->first_name }}</p>
        </div>
        <a href="{{ route($config['route'], $member) }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold">Please fix the errors below.</div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.members.activities.store', [$member, 'type' => $type]) }}" class="row g-3">
                @csrf

                <div class="col-12">
                    <label class="form-label">Member</label>
                    <input type="text" class="form-control" value="{{ $member->display_name ?? $member->first_name }} ({{ $member->id }})" disabled>
                </div>

                @switch($type)
                    @case('p2p-meetings')
                        <div class="col-md-6">
                            <label class="form-label">Peer Member ID</label>
                            <input type="text" name="peer_user_id" class="form-control" value="{{ old('peer_user_id') }}" required>
                            @error('peer_user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meeting Date</label>
                            <input type="date" name="meeting_date" class="form-control" value="{{ old('meeting_date') }}" required>
                            @error('meeting_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Meeting Place</label>
                            <input type="text" name="meeting_place" class="form-control" value="{{ old('meeting_place') }}" required>
                            @error('meeting_place')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" required>{{ old('remarks') }}</textarea>
                            @error('remarks')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        @break

                    @case('referrals')
                        <div class="col-md-6">
                            <label class="form-label">Referred Member ID</label>
                            <input type="text" name="to_user_id" class="form-control" value="{{ old('to_user_id') }}" required>
                            @error('to_user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referral Type</label>
                            <select name="referral_type" class="form-select" required>
                                <option value="">Select type</option>
                                @foreach ($referralTypes as $referralType)
                                    <option value="{{ $referralType }}" @selected(old('referral_type') === $referralType)>{{ ucwords(str_replace('_', ' ', $referralType)) }}</option>
                                @endforeach
                            </select>
                            @error('referral_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referral Date</label>
                            <input type="date" name="referral_date" class="form-control" value="{{ old('referral_date') }}" required>
                            @error('referral_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Referral Of</label>
                            <input type="text" name="referral_of" class="form-control" value="{{ old('referral_of') }}" required>
                            @error('referral_of')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                            @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Hot Value (1-5)</label>
                            <input type="number" name="hot_value" class="form-control" value="{{ old('hot_value', 3) }}" min="1" max="5" required>
                            @error('hot_value')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2" required>{{ old('address') }}</textarea>
                            @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3" required>{{ old('remarks') }}</textarea>
                            @error('remarks')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        @break

                    @case('business-deals')
                        <div class="col-md-6">
                            <label class="form-label">Deal With Member ID</label>
                            <input type="text" name="to_user_id" class="form-control" value="{{ old('to_user_id') }}" required>
                            @error('to_user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deal Date</label>
                            <input type="date" name="deal_date" class="form-control" value="{{ old('deal_date') }}" required>
                            @error('deal_date')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deal Amount</label>
                            <input type="number" step="0.01" name="deal_amount" class="form-control" value="{{ old('deal_amount') }}" required>
                            @error('deal_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Business Type</label>
                            <select name="business_type" class="form-select" required>
                                <option value="">Select type</option>
                                @foreach ($businessTypes as $businessType)
                                    <option value="{{ $businessType }}" @selected(old('business_type') === $businessType)>{{ ucfirst($businessType) }}</option>
                                @endforeach
                            </select>
                            @error('business_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Comment</label>
                            <textarea name="comment" class="form-control" rows="3">{{ old('comment') }}</textarea>
                            @error('comment')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        @break

                    @case('requirements')
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" required>
                            @error('subject')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Open (default)</option>
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}" @selected(old('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Region Label</label>
                            <input type="text" name="region_label" class="form-control" value="{{ old('region_label') }}" required>
                            @error('region_label')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City Name</label>
                            <input type="text" name="city_name" class="form-control" value="{{ old('city_name') }}" required>
                            @error('city_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category') }}" required>
                            @error('category')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Media File ID (optional)</label>
                            <input type="text" name="media_id" class="form-control" value="{{ old('media_id') }}">
                            @error('media_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        @break

                    @case('testimonials')
                        <div class="col-md-6">
                            <label class="form-label">Testimonial For Member ID</label>
                            <input type="text" name="to_user_id" class="form-control" value="{{ old('to_user_id') }}" required>
                            @error('to_user_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Content</label>
                            <textarea name="content" class="form-control" rows="3" required>{{ old('content') }}</textarea>
                            @error('content')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Media File ID (optional)</label>
                            <input type="text" name="media_id" class="form-control" value="{{ old('media_id') }}">
                            @error('media_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        @break
                @endswitch

                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Save {{ $config['singular'] }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
