<?php $errors = $errors ?? new \Illuminate\Support\ViewErrorBag(); ?>
@extends('admin.layouts.app')

@section('pageTitle', 'Withdrawals')
@section('page-title', 'Withdrawals')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Withdrawal Requests</h2>
            <p class="text-sm text-gray-400">Funds are already held from the user's wallet. Mark as Paid after sending, or Reject to refund.</p>
        </div>
    </div>

    @if(session('success')) <div class="bg-green-500/20 text-green-400 p-3 rounded">{{ session('success') }}</div> @endif
    @if($errors->any()) <div class="bg-red-500/20 text-red-400 p-3 rounded">{{ $errors->first() }}</div> @endif

    <div class="admin-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-800/50 border-b border-gray-800 text-gray-400 text-xs uppercase">
                <tr>
                    <th class="text-left p-3">User</th>
                    <th class="text-right p-3">Amount</th>
                    <th class="text-left p-3 hidden md:table-cell">Destination</th>
                    <th class="text-left p-3 hidden lg:table-cell">Requested</th>
                    <th class="text-right p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($withdrawals as $w)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                        <td class="p-3">
                            <a href="{{ route('admin.users.show', $w->user_id) }}" class="text-white hover:text-gold">
                                {{ $w->user->name ?? 'Unknown' }}
                            </a>
                            <p class="text-xs text-gray-500">{{ $w->user->email ?? '' }}</p>
                        </td>
                        <td class="p-3 text-right font-bold text-orange-400">KES {{ number_format($w->amount, 2) }}</td>
                        <td class="p-3 hidden md:table-cell">
                            @if($w->bankAccount)
                                <p class="text-white text-xs">{{ $w->bankAccount->bank_name }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $w->bankAccount->account_number }}</p>
                            @else
                                <p class="text-xs text-gray-500">{{ $w->phone ?? 'M-Pesa' }}</p>
                            @endif
                        </td>
                        <td class="p-3 hidden lg:table-cell text-xs text-gray-500">{{ $w->created_at->format('M d, H:i') }}</td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <form method="POST" action="{{ route('admin.withdrawals.complete', $w) }}"
                                      onsubmit="return confirm('Confirm KES {{ number_format($w->amount, 2) }} was sent to {{ $w->user->name ?? 'user' }}?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded font-semibold transition">
                                        <i class="fas fa-check mr-1"></i> Mark Paid
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.withdrawals.reject', $w) }}"
                                      onsubmit="return confirm('Reject and refund KES {{ number_format($w->amount, 2) }} to {{ $w->user->name ?? 'user' }}?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition">
                                        <i class="fas fa-undo"></i> Refund
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center">
                            <i class="fas fa-inbox text-4xl text-gray-700 mb-3"></i>
                            <p class="text-gray-500">No pending withdrawals. 🎉</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $withdrawals->links() }}</div>
</div>
@endsection
