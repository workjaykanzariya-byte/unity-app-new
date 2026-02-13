@extends('admin.layouts.app')

@section('title', 'Create Circle')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">Create Circle</h5>
        <small class="text-muted">Add a new community circle</small>
    </div>
    <a href="{{ route('admin.circles.index') }}" class="btn btn-outline-secondary btn-sm">Back to Circles</a>
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
    $industryTagsValue = old('industry_tags');
    if (is_array($industryTagsValue)) {
        $industryTagsValue = implode(', ', $industryTagsValue);
    }
    $founderId = old('founder_user_id', $defaultFounder?->id);
    $founderLabel = old('founder_search', $defaultFounderLabel);
    $meetingRepeatValue = old('meeting_repeat');
    if (is_array($meetingRepeatValue)) {
        $meetingRepeatValue = json_encode($meetingRepeatValue, JSON_PRETTY_PRINT);
    }

    $calendar = old('calendar', []);
    $calendarFrequency = data_get($calendar, 'frequency', '');
    $calendarDay = data_get($calendar, 'default_meet_day', '');
    $calendarTime = data_get($calendar, 'default_meet_time', '');
    $calendarMonthlyRule = data_get($calendar, 'monthly_rule', '');
    $calendarTimezone = data_get($calendar, 'timezone', 'Asia/Kolkata');
@endphp

<form action="{{ route('admin.circles.store') }}" method="POST">
    @csrf

    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">Circle Details</div>
                <div class="card-body row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
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
                            <option value="" disabled @selected(old('type') === null)>Select type</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Pending (default)</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <textarea name="purpose" class="form-control" rows="2">{{ old('purpose') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Announcement</label>
                        <textarea name="announcement" class="form-control" rows="2">{{ old('announcement') }}</textarea>
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
                <div class="card-header fw-semibold">Circle Settings</div>
                <div class="card-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Meeting Mode</label>
                        <select name="meeting_mode" class="form-select">
                            <option value="">Select mode</option>
                            @foreach ($meetingModes as $mode)
                                <option value="{{ $mode }}" @selected(old('meeting_mode') === $mode)>{{ ucfirst($mode) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Meeting Frequency</label>
                        <select name="meeting_frequency" class="form-select">
                            <option value="">Select frequency</option>
                            @foreach ($meetingFrequencies as $frequency)
                                <option value="{{ $frequency }}" @selected(old('meeting_frequency') === $frequency)>{{ ucfirst($frequency) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Launch Date</label>
                        <input type="date" name="launch_date" class="form-control" value="{{ old('launch_date') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Meeting Repeat (JSON)</label>
                        <textarea name="meeting_repeat" class="form-control" rows="3" placeholder='{"repeat_every":1,"unit":"month"}'>{{ $meetingRepeatValue }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Circle Director</label>
                        <select name="director_user_id" class="form-select">
                            <option value="">Select director</option>
                            @foreach ($allUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('director_user_id') === $user->id)>{{ $user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Industry Director</label>
                        <select name="industry_director_user_id" class="form-select">
                            <option value="">Select industry director</option>
                            @foreach ($allUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('industry_director_user_id') === $user->id)>{{ $user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">DED</label>
                        <select name="ded_user_id" class="form-select">
                            <option value="">Select DED</option>
                            @foreach ($allUsers as $user)
                                <option value="{{ $user->id }}" @selected(old('ded_user_id') === $user->id)>{{ $user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Cover Image</label>
                        <input type="hidden" name="cover_file_id" id="coverFileId" value="{{ old('cover_file_id') }}">
                        <input type="file" class="form-control" id="coverFileInput" accept="image/*">
                        <div class="form-text" id="coverUploadStatus">Upload up to 10MB.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header fw-semibold">Meeting Schedule (Calendar)</div>
                <div class="card-body row g-3" id="calendarScheduleSection">
                    <div class="col-md-3">
                        <label class="form-label">Frequency</label>
                        <select name="calendar[frequency]" id="calendarFrequency" class="form-select">
                            <option value="">Select frequency</option>
                            <option value="weekly" @selected($calendarFrequency === 'weekly')>Weekly</option>
                            <option value="monthly" @selected($calendarFrequency === 'monthly')>Monthly</option>
                            <option value="quarterly" @selected($calendarFrequency === 'quarterly')>Quarterly</option>
                        </select>
                        <div class="form-text">Weekly: choose day + time.</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Default Meet Time</label>
                        <input type="time" name="calendar[default_meet_time]" id="calendarMeetTime" class="form-control" value="{{ $calendarTime }}">
                    </div>
                    <div class="col-md-3" id="calendarDayWrap">
                        <label class="form-label">Day of Week</label>
                        <select name="calendar[default_meet_day]" id="calendarMeetDay" class="form-select">
                            <option value="">Select day</option>
                            @foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                                <option value="{{ $day }}" @selected($calendarDay === $day)>{{ ucfirst($day) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3" id="calendarMonthlyRuleWrap">
                        <label class="form-label">Week of Month</label>
                        <select name="calendar[monthly_rule]" id="calendarMonthlyRule" class="form-select">
                            <option value="">Select week rule</option>
                            @foreach (['first','second','third','fourth','last'] as $rule)
                                <option value="{{ $rule }}" @selected($calendarMonthlyRule === $rule)>{{ ucfirst($rule) }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Monthly/Quarterly: choose week rule + day + time (e.g. first monday).</div>
                    </div>
                    <input type="hidden" name="calendar[timezone]" id="calendarTimezone" value="{{ $calendarTimezone ?: 'Asia/Kolkata' }}">
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
                            <option value="" disabled @selected(old('city_id') === null)>Select city</option>
                            @foreach ($cities as $city)
                                <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>
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
            <a href="{{ route('admin.circles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Circle</button>
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

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const uploadUrl = '{{ route('admin.files.upload') }}';

    document.getElementById('coverFileInput')?.addEventListener('change', async (event) => {
        const file = event.target.files?.[0];
        if (!file) return;

        const statusEl = document.getElementById('coverUploadStatus');
        statusEl.textContent = 'Uploading...';

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {},
            });

            if (!response.ok) {
                statusEl.textContent = 'Upload failed. Please try again.';
                return;
            }

            const payload = await response.json();
            const fileId = payload?.data?.id ?? payload?.data?.[0]?.id;
            if (!fileId) {
                statusEl.textContent = 'Upload failed. Missing file id.';
                return;
            }

            document.getElementById('coverFileId').value = fileId;
            statusEl.textContent = 'Upload successful.';
        } catch (error) {
            statusEl.textContent = 'Upload failed. Please try again.';
        }
    });


    const calendarFrequency = document.getElementById('calendarFrequency');
    const calendarDayWrap = document.getElementById('calendarDayWrap');
    const calendarMonthlyRuleWrap = document.getElementById('calendarMonthlyRuleWrap');
    const calendarTimezone = document.getElementById('calendarTimezone');

    const toggleCalendarFields = () => {
        const frequency = (calendarFrequency?.value || '').toLowerCase();

        if (calendarTimezone && !calendarTimezone.value) {
            calendarTimezone.value = 'Asia/Kolkata';
        }

        if (!calendarDayWrap || !calendarMonthlyRuleWrap) {
            return;
        }

        calendarDayWrap.classList.toggle('d-none', frequency === '');
        calendarMonthlyRuleWrap.classList.toggle('d-none', !['monthly', 'quarterly'].includes(frequency));
    };

    calendarFrequency?.addEventListener('change', toggleCalendarFields);
    toggleCalendarFields();

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
