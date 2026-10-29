@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-3xl font-bold text-white mb-6">Welcome back, {{ auth()->user()->name }}</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <p class="text-sm text-gray-400 uppercase">Wallet Balance</p>
            <p class="text-3xl font-bold text-green-400 mt-2">KES {{ number_format($walletBalance ?? 0, 2) }}</p>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('deposit.form') }}" class="btn-golden text-sm px-3 py-1">Deposit</a>
                <a href="{{ route('withdrawal.form') }}" class="btn-outline-silver text-sm px-3 py-1">Withdraw</a>
            </div>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <p class="text-sm text-gray-400 uppercase">Total Invested</p>
            <p class="text-3xl font-bold text-gold mt-2">KES {{ number_format($totalInvested ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 mt-2">Active: {{ $activeInvestments ?? 0 }}</p>
        </div>
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <p class="text-sm text-gray-400 uppercase">Total Profit</p>
            <p class="text-3xl font-bold text-blue-400 mt-2">KES {{ number_format($totalProfit ?? 0, 2) }}</p>
            <p class="text-sm text-gray-500 mt-2">ROI: {{ $roi ?? 0 }}%</p>
        </div>
    </div>

    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-bold text-white mb-4">Quick Links</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <a href="{{ route('trading.index') }}" class="bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-center transition">
                <i class="fas fa-chart-line text-gold text-2xl mb-2"></i>
                <p class="text-sm text-white">Trading</p>
            </a>
            <a href="{{ route('machines.index') }}" class="bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-center transition">
                <i class="fas fa-microchip text-gold text-2xl mb-2"></i>
                <p class="text-sm text-white">Machines</p>
            </a>
            <a href="{{ route('lottery.index') }}" class="bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-center transition">
                <i class="fas fa-dice text-gold text-2xl mb-2"></i>
                <p class="text-sm text-white">Lottery</p>
            </a>
            <a href="{{ route('loans.index') }}" class="bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-center transition">
                <i class="fas fa-hand-holding-usd text-gold text-2xl mb-2"></i>
                <p class="text-sm text-white">Loans</p>
            </a>
        </div>
    </div>
</div>
@endsection
