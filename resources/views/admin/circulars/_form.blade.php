@php
    $isEdit = $circular->exists;
    $publishDate = old('publish_date', optional($circular->publish_date)->format('Y-m-d\TH:i'));
    $expiryDate = old('expiry_date', optional($circular->expiry_date)->format('Y-m-d\TH:i'));
@endphp

<div class="row g-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">Basic Information</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Circular Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $circular->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Circular Category <span class="text-danger">*</span></label>
                    <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                        <option value="">Select category</option>
                        @foreach ($categoryOptions as $category)
                            <option value="{{ $category }}" @selected(old('category', $circular->category) === $category)>{{ ucfirst($category) }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Short Summary <span class="text-danger">*</span></label>
                    <textarea name="summary" rows="3" class="form-control @error('summary') is-invalid @enderror" required>{{ old('summary', $circular->summary) }}</textarea>
                    <div class="form-text">Keep this between 150–200 characters for best results.</div>
                    @error('summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority Level <span class="text-danger">*</span></label>
                    <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                        <option value="">Select priority</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected(old('priority', $circular->priority) === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Publish Date <span class="text-danger">*</span></label>
                    <input type="datetime-local" name="publish_date" class="form-control @error('publish_date') is-invalid @enderror" value="{{ $publishDate }}" required>
                    @error('publish_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Expiry Date</label>
                    <input type="datetime-local" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" value="{{ $expiryDate }}">
                    @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="">Select status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected(old('status', $circular->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Pin Circular</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="is_pinned" value="1" @checked(old('is_pinned', $circular->is_pinned))>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">Content</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Featured Image</label>
                    <input type="file" name="featured_image" class="form-control @error('featured_image') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp,image/*">
                    @if(old('featured_image_url', $circular->featured_image_url))
                        <img src="{{ old('featured_image_url', $circular->featured_image_url) }}" alt="Featured Image" class="img-thumbnail mt-2" style="max-height: 120px;">
                    @endif
                    @error('featured_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Attachment</label>
                    <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                    @if(old('attachment_url', $circular->attachment_url))
                        <a class="d-block mt-2" href="{{ old('attachment_url', $circular->attachment_url) }}" target="_blank" rel="noopener">View current file</a>
                    @endif
                    @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Detailed Description <span class="text-danger">*</span></label>
                    <textarea name="content" rows="8" class="form-control @error('content') is-invalid @enderror" required>{{ old('content', $circular->content) }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Video Link</label>
                    <input type="url" name="video_url" class="form-control @error('video_url') is-invalid @enderror" value="{{ old('video_url', $circular->video_url) }}">
                    @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">CTA Label</label>
                    <input type="text" name="cta_label" class="form-control @error('cta_label') is-invalid @enderror" value="{{ old('cta_label', $circular->cta_label) }}">
                    @error('cta_label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">CTA URL</label>
                    <input type="url" name="cta_url" class="form-control @error('cta_url') is-invalid @enderror" value="{{ old('cta_url', $circular->cta_url) }}">
                    @error('cta_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-header fw-semibold">Target Audience</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Audience Type <span class="text-danger">*</span></label>
                    <select name="audience_type" class="form-select @error('audience_type') is-invalid @enderror" required>
                        <option value="">Select audience</option>
                        @foreach ($audienceOptions as $audience)
                            <option value="{{ $audience }}" @selected(old('audience_type', $circular->audience_type) === $audience)>{{ ucfirst(str_replace('_', ' ', $audience)) }}</option>
                        @endforeach
                    </select>
                    @error('audience_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Chapter / City</label>
                    <select name="city_id" class="form-select @error('city_id') is-invalid @enderror">
                        <option value="">All Cities</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) old('city_id', $circular->city_id) === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Circle</label>
                    <select name="circle_id" class="form-select @error('circle_id') is-invalid @enderror">
                        <option value="">All Circles</option>
                        @foreach ($circles as $circle)
                            <option value="{{ $circle->id }}" @selected((string) old('circle_id', $circular->circle_id) === (string) $circle->id)>{{ $circle->name }}</option>
                        @endforeach
                    </select>
                    @error('circle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Send Push Notification</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="send_push_notification" value="1" @checked(old('send_push_notification', $circular->send_push_notification))>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Allow Comments</label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" role="switch" name="allow_comments" value="1" @checked(old('allow_comments', $circular->allow_comments))>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">{{ $isEdit ? 'Update Circular' : 'Create Circular' }}</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.circulars.index') }}">Cancel</a>
</div>
