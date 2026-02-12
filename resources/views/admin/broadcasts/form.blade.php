@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Broadcast' : 'Create Broadcast')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">{{ $mode === 'edit' ? 'Edit Broadcast' : 'Create Broadcast' }}</h4>
        <a href="{{ route('admin.broadcasts.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
            <form method="POST" action="{{ $mode === 'edit' ? route('admin.broadcasts.update', $broadcast) : route('admin.broadcasts.store') }}" enctype="multipart/form-data">
                @csrf
                @if($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title (optional)</label>
                        <input type="text" name="title" class="form-control" maxlength="150" value="{{ old('title', $broadcast->title) }}">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" required>{{ old('message', $broadcast->message) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Image (optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        @if($broadcast->image_file_id)
                            <div class="mt-2">
                                <img src="{{ url('/api/v1/files/' . $broadcast->image_file_id) }}" alt="Broadcast image" style="max-height: 120px;" class="rounded border">
                            </div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Delivery Type</label>
                        <select id="delivery_type" name="delivery_type" class="form-select" required>
                            <option value="draft" @selected(old('delivery_type', 'draft') === 'draft')>Save as Draft</option>
                            <option value="send_now" @selected(old('delivery_type') === 'send_now')>Send Now</option>
                            <option value="schedule_once" @selected(old('delivery_type') === 'schedule_once')>Schedule One-time</option>
                            <option value="recurring_daily" @selected(old('delivery_type') === 'recurring_daily')>Recurring Daily</option>
                            <option value="recurring_weekly" @selected(old('delivery_type') === 'recurring_weekly')>Recurring Weekly</option>
                            <option value="recurring_monthly" @selected(old('delivery_type') === 'recurring_monthly')>Recurring Monthly</option>
                        </select>
                        <div class="form-text">All times are in Asia/Kolkata.</div>
                    </div>

                    <div class="col-md-6 schedule-field" data-for="schedule_once">
                        <label class="form-label">One-time Date & Time</label>
                        <input type="datetime-local" name="schedule_once_at" class="form-control" value="{{ old('schedule_once_at', $broadcast->send_at?->timezone('Asia/Kolkata')->format('Y-m-d\TH:i')) }}">
                    </div>

                    <div class="col-md-6 schedule-field" data-for="recurring_daily">
                        <label class="form-label">Daily Time</label>
                        <input type="time" name="daily_time" class="form-control" value="{{ old('daily_time', $broadcast->time_of_day ? substr((string) $broadcast->time_of_day, 0, 5) : null) }}">
                    </div>

                    <div class="col-md-3 schedule-field" data-for="recurring_weekly">
                        <label class="form-label">Weekday</label>
                        <select name="weekly_day" class="form-select">
                            @foreach([0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('weekly_day', $broadcast->day_of_week) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 schedule-field" data-for="recurring_weekly">
                        <label class="form-label">Weekly Time</label>
                        <input type="time" name="weekly_time" class="form-control" value="{{ old('weekly_time', $broadcast->time_of_day ? substr((string) $broadcast->time_of_day, 0, 5) : null) }}">
                    </div>

                    <div class="col-md-3 schedule-field" data-for="recurring_monthly">
                        <label class="form-label">Day of Month</label>
                        <input type="number" name="monthly_day" class="form-control" min="1" max="28" value="{{ old('monthly_day', $broadcast->day_of_month) }}">
                    </div>
                    <div class="col-md-3 schedule-field" data-for="recurring_monthly">
                        <label class="form-label">Monthly Time</label>
                        <input type="time" name="monthly_time" class="form-control" value="{{ old('monthly_time', $broadcast->time_of_day ? substr((string) $broadcast->time_of_day, 0, 5) : null) }}">
                    </div>
                </div>

                @if(!empty($nextRunPreview))
                    <div class="alert alert-info mt-3 mb-0">
                        Next run preview: <strong>{{ $nextRunPreview->timezone('Asia/Kolkata')->format('Y-m-d H:i:s') }} IST</strong>
                    </div>
                @endif

                <div class="mt-4 d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="delivery_type" value="draft" class="btn btn-outline-secondary">Save as Draft</button>
                    <button type="submit" class="btn btn-primary">Schedule</button>
                    @if($mode === 'edit')
                        <button type="submit" name="delivery_type" value="send_now" class="btn btn-success">Send Now</button>
                    @else
                        <button type="submit" name="delivery_type" value="send_now" class="btn btn-success">Send Now</button>
                    @endif
                </div>
            </form>

            @if($mode === 'edit' && $broadcast->status === 'scheduled')
                <form method="POST" action="{{ route('admin.broadcasts.cancel', $broadcast) }}" class="mt-2 text-end">
                    @csrf
                    <button class="btn btn-sm btn-outline-danger" type="submit">Cancel Broadcast</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selector = document.getElementById('delivery_type');
        const fields = document.querySelectorAll('.schedule-field');

        const syncVisibility = () => {
            const value = selector.value;
            fields.forEach((field) => {
                const show = field.dataset.for === value;
                field.style.display = show ? '' : 'none';
            });
        };

        selector.addEventListener('change', syncVisibility);
        syncVisibility();
    });
</script>
@endpush
