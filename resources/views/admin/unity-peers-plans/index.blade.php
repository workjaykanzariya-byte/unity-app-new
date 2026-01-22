@extends('admin.layouts.app')

@section('title', 'Membership Plans')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Membership Plans</h5>
            <div class="d-flex gap-2 align-items-center">
                @if (! $canEdit)
                    <span class="badge bg-secondary">View only</span>
                @endif
                @if ($canEdit)
                    <a href="{{ route('admin.unity-peers-plans.create') }}" class="btn btn-sm btn-primary">Create Plan</a>
                @endif
            </div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Base Price</th>
                        <th>GST %</th>
                        <th>GST Amount</th>
                        <th>Total Amount</th>
                        <th>Duration Days</th>
                        <th>Duration Months</th>
                        <th>Is Free</th>
                        <th>Is Active</th>
                        <th>Sort Order</th>
                        <th>Created At</th>
                        @if ($canEdit)
                            <th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($plans as $plan)
                        @php
                            $price = (float) $plan->price;
                            $gstPercent = (float) $plan->gst_percent;
                            $gstAmount = round($price * ($gstPercent / 100), 2);
                            $totalAmount = round($price + $gstAmount, 2);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $plan->name }}</td>
                            <td>{{ $plan->slug }}</td>
                            <td>₹{{ number_format($price, 2) }}</td>
                            <td>{{ number_format($gstPercent, 2) }}%</td>
                            <td>₹{{ number_format($gstAmount, 2) }}</td>
                            <td>₹{{ number_format($totalAmount, 2) }}</td>
                            <td>{{ $plan->duration_days ?? 0 }}</td>
                            <td>{{ $plan->duration_months ?? '—' }}</td>
                            <td>{{ $plan->is_free ? 'Yes' : 'No' }}</td>
                            <td>
                                @if ($plan->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $plan->sort_order ?? 0 }}</td>
                            <td>{{ $plan->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            @if ($canEdit)
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.unity-peers-plans.edit', $plan) }}">Edit</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 13 : 12 }}" class="text-center text-muted">No plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
