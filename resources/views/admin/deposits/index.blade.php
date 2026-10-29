@extends('admin.layouts.app')
@section('page-title', 'Deposits')
@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold text-white">Deposit Requests</h1>
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 text-gray-400 text-xs uppercase"><tr><th class="p-3 text-left">User</th><th class="p-3 text-right">Amount</th><th class="p-3 text-left">Status</th><th class="p-3 text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($deposits as $dep)
                <tr class="border-t border-gray-800">
                    <td class="p-3 text-white">{{ $dep->user?->name ?? 'Unknown' }}</td>
                    <td class="p-3 text-right text-gold font-mono">KES {{ number_format($dep->amount, 2) }}</td>
                    <td class="p-3"><span class="px-2 py-0.5 text-xs rounded {{ $dep->status === 'approved' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">{{ ucfirst($dep->status) }}</span></td>
                    <td class="p-3 text-right">
                        @if($dep->status === 'pending')
                        <form method="POST" action="{{ route('admin.deposits.verify', $dep) }}" class="inline">@csrf<button class="px-2 py-1 bg-green-600 text-white text-xs rounded">Verify</button></form>
                        <form method="POST" action="{{ route('admin.deposits.reject', $dep) }}" class="inline">@csrf<button class="px-2 py-1 bg-red-600 text-white text-xs rounded">Reject</button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="p-6 text-center text-gray-500">No deposits.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $deposits->links() }}
</div>
@endsection
