@extends('admin.layouts.app')

@section('title', 'Edit Membership Plan')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Membership Plan</h4>
        <a href="{{ route('admin.memberships.plans.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.memberships.plans.update', $plan) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" value="{{ $plan->name }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input type="text" class="form-control" value="{{ $plan->slug }}" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Price (Base)</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="price"
                            class="form-control"
                            value="{{ old('price', $plan->price) }}"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">GST %</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="gst_percent"
                            class="form-control"
                            value="{{ old('gst_percent', $plan->gst_percent) }}"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Amount (Preview)</label>
                        @php
                            $price = (float) old('price', $plan->price);
                            $gstPercent = (float) old('gst_percent', $plan->gst_percent);
                            $gstAmount = round($price * ($gstPercent / 100), 2);
                            $totalAmount = round($price + $gstAmount, 2);
                        @endphp
                        <input type="text" class="form-control" value="₹{{ number_format($totalAmount, 2) }}" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Duration (Days)</label>
                        <input
                            type="number"
                            min="0"
                            name="duration_days"
                            class="form-control"
                            value="{{ old('duration_days', $plan->duration_days ?? 0) }}"
                            required
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Duration (Months)</label>
                        <input
                            type="number"
                            min="0"
                            name="duration_months"
                            class="form-control"
                            value="{{ old('duration_months', $plan->duration_months) }}"
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Sort Order</label>
                        <input
                            type="number"
                            min="0"
                            name="sort_order"
                            class="form-control"
                            value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Active</label>
                        <select name="is_active" class="form-select" required>
                            <option value="1" @selected(old('is_active', $plan->is_active) == 1)>Yes</option>
                            <option value="0" @selected(old('is_active', $plan->is_active) == 0)>No</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.memberships.plans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
