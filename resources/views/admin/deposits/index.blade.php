<?php $errors = $errors ?? new \Illuminate\Support\ViewErrorBag(); ?>
@extends('admin.layouts.app')

@section('page-title', 'Deposits')
@section('content')
<div class="space-y-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-white">Deposit Requests</h2>
            <p class="text-sm text-gray-400">Verify M-Pesa and bank deposits. Approved deposits credit the user's wallet automatically.</p>
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
                    <th class="text-left p-3 hidden md:table-cell">Method</th>
                    <th class="text-left p-3 hidden md:table-cell">Reference</th>
                    <th class="text-left p-3 hidden lg:table-cell">Date</th>
                    <th class="text-right p-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deposits as $deposit)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 transition">
                        <td class="p-3">
                            <a href="{{ route('admin.users.show', $deposit->user_id) }}" class="text-white hover:text-gold">
                                {{ $deposit->user->name ?? 'Unknown' }}
                            </a>
                            <p class="text-xs text-gray-500">{{ $deposit->user->email ?? '' }}</p>
                        </td>
                        <td class="p-3 text-right font-bold text-green-400">KES {{ number_format($deposit->amount, 2) }}</td>
                        <td class="p-3 hidden md:table-cell">
                            <span class="px-2 py-0.5 bg-gray-700 text-gray-300 text-xs rounded uppercase">
                                {{ $deposit->payment_method ?? 'M-Pesa' }}
                            </span>
                        </td>
                        <td class="p-3 hidden md:table-cell font-mono text-xs text-gray-400">{{ $deposit->reference ?? '—' }}</td>
                        <td class="p-3 hidden lg:table-cell text-xs text-gray-500">{{ $deposit->created_at->format('M d, H:i') }}</td>
                        <td class="p-3">
                            <div class="flex justify-end gap-1">
                                <form method="POST" action="{{ route('admin.deposits.verify', $deposit) }}"
                                      onsubmit="return confirm('Verify KES {{ number_format($deposit->amount, 2) }} and credit {{ $deposit->user->name ?? 'user' }}?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs rounded font-semibold transition">
                                        <i class="fas fa-check mr-1"></i> Approve
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.deposits.reject', $deposit) }}"
                                      onsubmit="return confirm('Reject this deposit?');">
                                    @csrf
                                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center">
                            <i class="fas fa-inbox text-4xl text-gray-700 mb-3"></i>
                            <p class="text-gray-500">No pending deposits. 🎉</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $deposits->links() }}</div>
</div>
@endsection
