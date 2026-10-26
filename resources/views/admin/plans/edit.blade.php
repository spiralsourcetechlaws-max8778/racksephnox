@extends('admin.layouts.app')

@section('page-title', 'Edit Plan')
@section('breadcrumbs')
    <a href="{{ route('admin.plans.index') }}" class="text-gold hover:text-yellow-400">Investment Plans</a>
    <span class="mx-1">/</span> {{ $plan->name }}
@endsection

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    @if($errors->any())
        <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-4 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="admin-card p-6 space-y-5">
        @csrf @method('PUT')

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Basic Info</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Plan Name *</label>
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Display Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-gray-400 mb-1">Description</label>
                    <textarea name="description" rows="2"
                              class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">{{ old('description', $plan->description) }}</textarea>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Investment Range</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Minimum (KES) *</label>
                    <input type="number" step="0.01" name="min_amount" value="{{ old('min_amount', $plan->min_amount) }}" required min="0"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Maximum (KES) *</label>
                    <input type="number" step="0.01" name="max_amount" value="{{ old('max_amount', $plan->max_amount) }}" required min="0"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Returns</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Daily Interest Rate (%) *</label>
                    <input type="number" step="0.0001" name="daily_interest_rate" value="{{ old('daily_interest_rate', $plan->daily_interest_rate) }}" required min="0" max="100"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Duration (days) *</label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', $plan->duration_days) }}" required min="1"
                           class="w-full bg-gray-800 border border-gray-700 text-white rounded px-3 py-2">
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-lg font-semibold text-gold mb-3 pb-2 border-b border-gray-800">Status</h2>
            <label class="flex items-center gap-3 p-3 bg-gray-800/50 border border-gray-700 rounded cursor-pointer hover:bg-gray-800">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="w-4 h-4">
                <div>
                    <p class="text-sm font-medium text-white">Active</p>
                    <p class="text-xs text-gray-400">Inactive plans are hidden from users</p>
                </div>
            </label>
        </div>

        <div class="flex gap-3 pt-4 border-t border-gray-800">
            <button type="submit" class="px-6 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded">Save Changes</button>
            <a href="{{ route('admin.plans.index') }}" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded">Cancel</a>
        </div>
    </form>

    <div class="admin-card p-6 border-t-4 border-t-red-600">
        <h3 class="text-lg font-semibold text-red-400 mb-2">Danger Zone</h3>
        <p class="text-sm text-gray-400 mb-4">Deleting a plan does not affect existing investments. The plan will be hidden from new users.</p>
        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
              onsubmit="return confirm('Delete {{ addslashes($plan->name) }} permanently?');">
            @csrf @method('DELETE')
            <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold rounded">
                <i class="fas fa-trash mr-1"></i> Delete Plan
            </button>
        </form>
    </div>
</div>
@endsection
