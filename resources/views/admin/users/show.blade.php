@extends('admin.layouts.app')

@section('page-title', $user->name)
@section('content')

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $user->name }}</h1>
            <p class="text-sm text-gray-400">{{ $user->email }} @if($user->phone) · {{ $user->phone }} @endif</p>
            <div class="flex gap-2 mt-2">
                <span class="px-2 py-0.5 bg-gray-800 text-gold text-xs rounded">{{ $user->role_label }}</span>
                <span class="px-2 py-0.5 bg-{{ in_array($user->kyc_status, ['verified','approved']) ? 'green' : 'yellow' }}-500/20 text-{{ in_array($user->kyc_status, ['verified','approved']) ? 'green' : 'yellow' }}-400 text-xs rounded">KYC: {{ ucfirst($user->kyc_status ?? 'pending') }}</span>
                <span class="px-2 py-0.5 bg-gray-800 text-gray-400 text-xs rounded">Ref: {{ $user->referral_code ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            @if(auth()->user()->hasRole('super_admin'))
                <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-gold hover:bg-yellow-500 text-black font-bold rounded text-sm">Edit</a>
            @endif
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm">Back</a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Wallet</p><p class="text-2xl font-bold text-green-400">KES {{ number_format($stats['wallet_balance'], 2) }}</p></div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Deposited</p><p class="text-2xl font-bold text-gold">KES {{ number_format($stats['total_deposited'], 2) }}</p></div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Invested</p><p class="text-2xl font-bold text-white">KES {{ number_format($stats['total_invested'], 2) }}</p></div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-4"><p class="text-xs text-gray-400">Loans Out</p><p class="text-2xl font-bold text-orange-400">KES {{ number_format($stats['total_loans_principal'], 2) }}</p></div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-gold mb-3">Recent Transactions ({{ $stats['transaction_count'] }})</h3>
        @forelse($user->transactions as $tx)
            <div class="flex justify-between items-center py-2 border-b border-gray-800/50 last:border-0">
                <div><p class="text-sm text-white">{{ ucfirst($tx->type) }}</p><p class="text-xs text-gray-500">{{ $tx->description }}</p></div>
                <p class="text-sm font-medium text-gold">KES {{ number_format($tx->amount, 2) }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-500">No transactions.</p>
        @endforelse
    </div>
</div>
@endsection
