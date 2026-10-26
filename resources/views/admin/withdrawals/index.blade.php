@extends('admin.layouts.app')
@section('content')
<h2 class="text-2xl font-bold text-white mb-6">Pending Withdrawals</h2>
@if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded mb-4">{{ session('success') }}</div> @endif

<div class="bg-gray-900 rounded-lg shadow overflow-hidden border border-gray-800">
    <table class="w-full text-left text-gray-300">
        <thead class="bg-gray-800 text-gray-400 text-sm">
            <tr><th class="p-4">User</th><th class="p-4">Amount</th><th class="p-4">Destination</th><th class="p-4">Date</th><th class="p-4 text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-800">
            @forelse($withdrawals as $w)
            <tr>
                <td class="p-4">{{ $w->user->name ?? 'Unknown' }}</td>
                <td class="p-4 font-bold text-orange-400">KES {{ number_format($w->amount, 2) }}</td>
                <td class="p-4">{{ $w->bankAccount->bank_name ?? 'M-Pesa' }}<br><span class="text-xs text-gray-500">{{ $w->bankAccount->account_number ?? '' }}</span></td>
                <td class="p-4">{{ $w->created_at->format('M d, H:i') }}</td>
                <td class="p-4 text-right flex gap-2 justify-end">
                    <form method="POST" action="{{ route('admin.withdrawals.complete', $w) }}">@csrf <button class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">Mark Paid</button></form>
                    <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}">@csrf <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">Reject & Refund</button></form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="p-6 text-center text-gray-500">No pending withdrawals.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $withdrawals->links() }}</div>
@endsection
