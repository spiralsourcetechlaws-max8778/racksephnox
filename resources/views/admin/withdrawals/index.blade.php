@extends('admin.layouts.app')
@section('page-title', 'Withdrawals')
@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold text-white">Withdrawal Requests</h1>
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 text-gray-400 text-xs uppercase"><tr><th class="p-3 text-left">User</th><th class="p-3 text-right">Amount</th><th class="p-3 text-left">Status</th><th class="p-3 text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($withdrawals as $w)
                <tr class="border-t border-gray-800">
                    <td class="p-3 text-white">{{ $w->user?->name ?? 'Unknown' }}</td>
                    <td class="p-3 text-right text-orange-400 font-mono">KES {{ number_format($w->amount, 2) }}</td>
                    <td class="p-3"><span class="px-2 py-0.5 text-xs rounded {{ $w->status === 'completed' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400' }}">{{ ucfirst($w->status) }}</span></td>
                    <td class="p-3 text-right">
                        @if($w->status === 'pending')
                        <form method="POST" action="{{ route('admin.withdrawals.complete', $w) }}" class="inline">@csrf<button class="px-2 py-1 bg-green-600 text-white text-xs rounded">Complete</button></form>
                        <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}" class="inline">@csrf<button class="px-2 py-1 bg-red-600 text-white text-xs rounded">Reject</button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="p-6 text-center text-gray-500">No withdrawals.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $withdrawals->links() }}
</div>
@endsection
