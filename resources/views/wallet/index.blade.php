@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-white">My Wallet</h1>
            <p class="text-gray-400 text-sm mt-1">Manage your funds and transactions</p>
        </div>
        <a href="{{ route('lottery.bonus-wheel') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-bold text-sm">
            🎁 Spin Bonus Wheel
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <p class="text-sm text-gray-400 uppercase">Available Balance</p>
            <p class="text-3xl font-bold text-gold mt-1">KES {{ number_format(auth()->user()->wallet?->balance ?? 0, 2) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <p class="text-sm text-gray-400 uppercase">Locked Balance</p>
            <p class="text-3xl font-bold text-orange-400 mt-1">KES {{ number_format(auth()->user()->wallet?->locked_balance ?? 0, 2) }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <p class="text-sm text-gray-400 uppercase">Total Value</p>
            <p class="text-3xl font-bold text-green-400 mt-1">KES {{ number_format((auth()->user()->wallet?->balance ?? 0) + (auth()->user()->wallet?->locked_balance ?? 0), 2) }}</p>
        </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-bold text-white mb-4">Quick Actions</h2>
        <div class="flex gap-4">
            <a href="{{ route('deposit.form') }}" class="btn-golden py-2 px-4">Deposit Funds</a>
            <a href="{{ route('withdrawal.form') }}" class="btn-outline-silver py-2 px-4">Withdraw Funds</a>
        </div>
    </div>
</div>
@endsection
