@extends('admin.layouts.app')
@section('page-title', 'Investment Plans')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">Investment Plans</h1>
        <a href="{{ route('admin.plans.create') }}" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">Create Plan</a>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 text-gray-400 text-xs uppercase"><tr><th class="p-3 text-left">Name</th><th class="p-3 text-right">Min-Max</th><th class="p-3 text-right">Rate</th><th class="p-3 text-right">Actions</th></tr></thead>
            <tbody>
                @foreach($plans as $plan)
                <tr class="border-t border-gray-800">
                    <td class="p-3 text-white">{{ $plan->name }}</td>
                    <td class="p-3 text-right text-gray-300">KES {{ number_format($plan->min_amount, 0) }} - {{ number_format($plan->max_amount, 0) }}</td>
                    <td class="p-3 text-right text-gold">{{ $plan->daily_interest_rate }}%</td>
                    <td class="p-3 text-right">
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="px-2 py-1 bg-gray-600 text-white text-xs rounded">Edit</a>
                        <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button class="px-2 py-1 bg-red-600 text-white text-xs rounded">Delete</button></form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $plans->links() }}
</div>
@endsection
