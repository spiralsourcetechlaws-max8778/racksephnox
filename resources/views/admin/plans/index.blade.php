@extends('admin.layouts.app')

@section('page-title', 'Investment Plans')
@section('breadcrumbs')
    <a href="{{ route('admin.dashboard') }}" class="text-gold hover:text-yellow-400">Dashboard</a>
    <span class="mx-1">/</span> Investment Plans
@endsection

@section('content')
<div class="space-y-4">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h2 class="text-xl font-bold text-white">Investment Plans</h2>
            <p class="text-sm text-gray-400">{{ $plans->total() ?? count($plans ?? []) }} total plans</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">
                <i class="fas fa-plus mr-1"></i> New Plan
            </a>
            <form method="POST" action="{{ route('admin.plans.export') }}" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">
                    <i class="fas fa-download mr-1"></i> Export
                </button>
            </form>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    <div class="admin-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 border-b border-gray-800 text-gray-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-3">Plan</th>
                    <th class="text-right p-3 hidden md:table-cell">Min Investment</th>
                    <th class="text-right p-3 hidden md:table-cell">Max Investment</th>
                    <th class="text-right p-3">Daily Rate</th>
                    <th class="text-right p-3 hidden lg:table-cell">Duration</th>
                    <th class="text-left p-3">Status</th>
                    <th class="text-right p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                        <td class="p-3">
                            <p class="text-white font-medium">{{ $plan->name }}</p>
                            <p class="text-xs text-gray-500">{{ Str::limit($plan->description ?? '', 60) }}</p>
                        </td>
                        <td class="p-3 text-right text-gold font-mono hidden md:table-cell">
                            KES {{ number_format($plan->min_amount ?? 0, 0) }}
                        </td>
                        <td class="p-3 text-right text-gold font-mono hidden md:table-cell">
                            KES {{ number_format($plan->max_amount ?? 0, 0) }}
                        </td>
                        <td class="p-3 text-right text-green-400 font-bold">
                            {{ number_format(($plan->daily_interest_rate ?? 0) * 100, 2) }}%
                        </td>
                        <td class="p-3 text-right text-gray-400 hidden lg:table-cell">
                            {{ $plan->duration_days ?? '—' }} days
                        </td>
                        <td class="p-3">
                            <span class="px-2 py-0.5 text-xs rounded {{ $plan->is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="p-3 text-right">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.plans.show', $plan) }}"
                                   class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.plans.edit', $plan) }}"
                                   class="px-2 py-1 bg-gray-600 hover:bg-gray-500 text-white text-xs rounded" title="Edit">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}"
                                      onsubmit="return confirm('Delete plan {{ addslashes($plan->name) }}? Active investments in this plan will not be affected.');"
                                      class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center">
                            <i class="fas fa-chart-line text-4xl text-gray-700 mb-3"></i>
                            <p class="text-gray-500 mb-3">No investment plans yet.</p>
                            <a href="{{ route('admin.plans.create') }}" class="text-gold hover:text-yellow-400 text-sm">Create your first plan →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($plans, 'links'))
        <div class="mt-4">{{ $plans->links() }}</div>
    @endif
</div>
@endsection
