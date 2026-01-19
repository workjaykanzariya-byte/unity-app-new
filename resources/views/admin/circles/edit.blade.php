@extends('admin.layouts.app')

@section('title', 'Edit Circle')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Edit Circle</h5>
        <small class="text-muted">Update circle details</small>
    </div>
    <a href="{{ route('admin.circles.show', $circle) }}" class="btn btn-outline-secondary btn-sm">Back to Details</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>There were some problems with your input.</strong>
        <ul class="mb-0 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $industryTagsValue = old('industry_tags', $circle->industry_tags ? implode(', ', $circle->industry_tags) : '');
    if (is_array($industryTagsValue)) {
        $industryTagsValue = implode(', ', $industryTagsValue);
    }
    $founderId = old('founder_user_id', $defaultFounder?->id);
    $founderLabel = old('founder_search', $defaultFounderLabel);
@endphp

<form action="{{ route('admin.circles.update', $circle) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">Circle Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $circle->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Circle Founder</label>
                        <div class="position-relative">
                            <input type="text"
                                   name="founder_search"
                                   id="founderSearch"
                                   class="form-control"
                                   value="{{ $founderLabel }}"
                                   data-default-id="{{ $founderId }}"
                                   data-default-label="{{ $founderLabel }}"
                                   autocomplete="off"
                                   placeholder="Search by name or email">
                            <input type="hidden" name="founder_user_id" id="founderUserId" value="{{ $founderId }}">
                            <div id="founderResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1000;"></div>
                        </div>
                        <div class="form-text">Defaults to the logged-in admin user.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="" disabled>Select type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type', $circle->type) === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $circle->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $circle->description) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <textarea name="purpose" class="form-control" rows="2">{{ old('purpose', $circle->purpose) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Announcement</label>
                        <textarea name="announcement" class="form-control" rows="2">{{ old('announcement', $circle->announcement) }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Industry Tags</label>
                        <input type="text" name="industry_tags" class="form-control" value="{{ $industryTagsValue }}" placeholder="e.g. Finance, SaaS, Retail">
                        <div class="form-text">Separate tags with commas.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">Location</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Country</label>
                        <select name="country" id="countrySelect" class="form-select">
                            @foreach ($countries as $country)
                                <option value="{{ $country }}" @selected(old('country', $selectedCountry) === $country)>{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">City</label>
                        <select name="city_id" class="form-select" required>
                            <option value="" disabled>Select city</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id', $circle->city_id) == $city->id)>
                                    {{ $city->name }}{{ $city->state ? ', ' . $city->state : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Derived Country</label>
                        <input type="text" class="form-control" value="{{ old('country', $selectedCountry) }}" readonly>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.circles.show', $circle) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
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

    (() => {
        const input = document.getElementById('founderSearch');
        const hidden = document.getElementById('founderUserId');
        const results = document.getElementById('founderResults');
        if (!input || !hidden || !results) {
            return;
        }

        const defaultId = input.dataset.defaultId || '';
        const defaultLabel = input.dataset.defaultLabel || '';
        let timer = null;

        const clearResults = () => {
            results.innerHTML = '';
            results.classList.add('d-none');
        };

        const setSelection = (id, label) => {
            hidden.value = id || '';
            input.value = label || '';
        };

        const restoreDefault = () => {
            if (defaultId) {
                setSelection(defaultId, defaultLabel);
            } else {
                setSelection('', '');
            }
        };

        const renderResults = (items) => {
            results.innerHTML = '';
            if (!items.length) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item small text-muted';
                empty.textContent = 'No users found';
                results.appendChild(empty);
            } else {
                items.forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.textContent = item.label;
                    button.addEventListener('click', () => {
                        setSelection(item.id, item.label);
                        clearResults();
                    });
                    results.appendChild(button);
                });
            }
            results.classList.remove('d-none');
        };

        const fetchResults = (query) => {
            fetch(`{{ route('admin.users.search') }}?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' },
            })
                .then((response) => response.json())
                .then((data) => renderResults(Array.isArray(data) ? data : []))
                .catch(() => clearResults());
        };

        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (!query) {
                restoreDefault();
                clearResults();
                return;
            }
            clearTimeout(timer);
            timer = window.setTimeout(() => fetchResults(query), 300);
        });

        document.addEventListener('click', (event) => {
            if (!results.contains(event.target) && event.target !== input) {
                clearResults();
            }
        });
    })();
</script>
@endpush
