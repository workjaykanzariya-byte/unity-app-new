@extends('admin.layouts.app')

@section('title', 'Membership Plans')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Membership Plans</h5>
            @if (! $canEdit)
                <span class="badge bg-secondary">View only</span>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Price</th>
                        <th>GST %</th>
                        <th>Total Amount</th>
                        <th>Duration (Days)</th>
                        <th>Duration (Months)</th>
                        <th>Active</th>
                        <th>Sort</th>
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
                            <td>
                                <div class="fw-semibold">{{ $plan->name }}</div>
                                @if ($plan->is_free)
                                    <span class="badge bg-info">Free</span>
                                @endif
                            </td>
                            <td>{{ $plan->slug }}</td>
                            <td>₹{{ number_format($price, 2) }}</td>
                            <td>{{ number_format($gstPercent, 2) }}%</td>
                            <td>₹{{ number_format($totalAmount, 2) }}</td>
                            <td>{{ $plan->duration_days ?? 0 }}</td>
                            <td>{{ $plan->duration_months ?? '—' }}</td>
                            <td>
                                @if ($plan->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $plan->sort_order ?? 0 }}</td>
                            @if ($canEdit)
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.memberships.plans.edit', $plan) }}">Edit</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canEdit ? 10 : 9 }}" class="text-center text-muted">No membership plans found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
