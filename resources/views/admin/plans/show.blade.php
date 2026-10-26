@extends('admin.layouts.app')

@section('page-title', $plan->name)
@section('breadcrumbs')
    <a href="{{ route('admin.plans.index') }}" class="text-gold hover:text-yellow-400">Investment Plans</a>
    <span class="mx-1">/</span> {{ $plan->name }}
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">{{ $plan->name }}</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.plans.edit', $plan) }}" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">
                <i class="fas fa-pen mr-1"></i> Edit
            </a>
            <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
        </div>
    </div>

    <div class="admin-card p-6 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-gray-400 uppercase">Min Investment</p>
                <p class="text-xl font-bold text-gold">KES {{ number_format($plan->min_amount ?? 0, 0) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Max Investment</p>
                <p class="text-xl font-bold text-gold">KES {{ number_format($plan->max_amount ?? 0, 0) }}</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Daily Rate</p>
                <p class="text-xl font-bold text-green-400">{{ number_format(($plan->daily_interest_rate ?? 0) * 100, 2) }}%</p>
            </div>
            <div>
                <p class="text-xs text-gray-400 uppercase">Duration</p>
                <p class="text-xl font-bold text-white">{{ $plan->duration_days ?? '—' }} days</p>
            </div>
        </div>
        <div class="pt-4 border-t border-gray-800">
            <p class="text-sm text-gray-400">{{ $plan->description ?? 'No description' }}</p>
        </div>
        <div class="pt-2">
            <span class="px-3 py-1 text-xs rounded {{ $plan->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                {{ $plan->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
    </div>
</div>
@endsection
