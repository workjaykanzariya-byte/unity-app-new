@extends('admin.layouts.app')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="text-2xl font-semibold text-slate-900">Dashboard</div>
            <p class="text-sm text-slate-500 mt-1">Overview of current platform health.</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Admin Session Active</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-sm text-slate-500">Total Users</div>
            <div class="text-3xl font-semibold text-slate-900 mt-2">{{ $stats['total_users'] }}</div>
            <div class="text-xs text-emerald-600 mt-1">+3.2% vs last week</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-sm text-slate-500">Pending Activities</div>
            <div class="text-3xl font-semibold text-slate-900 mt-2">{{ $stats['pending_activities'] }}</div>
            <div class="text-xs text-amber-600 mt-1">Action required</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
            <div class="text-sm text-slate-500">Coins Issued</div>
            <div class="text-3xl font-semibold text-slate-900 mt-2">{{ $stats['coins_issued'] }}</div>
            <div class="text-xs text-slate-500 mt-1">Cumulative</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold text-slate-900">Approval Queue</div>
                <span class="text-xs text-slate-500">Coming soon</span>
            </div>
            <div class="border border-dashed border-slate-200 rounded-lg p-6 text-center text-sm text-slate-500">
                Placeholder for upcoming approval workflows and moderation actions.
            </div>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold text-slate-900">Admin Activity Feed</div>
                <span class="text-xs text-slate-500">Coming soon</span>
            </div>
            <div class="border border-dashed border-slate-200 rounded-lg p-6 text-center text-sm text-slate-500">
                Placeholder for audit logs and admin actions visibility.
            </div>
        </div>
    </div>
@endsection
