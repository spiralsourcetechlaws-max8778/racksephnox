@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">

        <a href="{{ route('investments.index') }}" class="text-gold-400 hover:text-gold text-sm">
            <i class="fas fa-arrow-left"></i> All Investments
        </a>

        <div class="card-golden p-6 mt-4">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br {{ $investment['color'] }} flex items-center justify-center">
                    <i class="fas {{ $investment['icon'] }} text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold golden-title">{{ $investment['name'] }}</h1>
                    <p class="text-gold-400 text-xs">
                        {{ $investment['source'] === 'machine' ? 'RX Machine Investment' : 'Plan Investment' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gold/5 rounded-lg p-3">
                    <p class="text-xs text-gold-400">Invested</p>
                    <p class="text-lg font-bold text-gold">KES {{ number_format($investment['amount'], 2) }}</p>
                </div>
                <div class="bg-gold/5 rounded-lg p-3">
                    <p class="text-xs text-gold-400">Daily Profit</p>
                    <p class="text-lg font-bold text-green-400">KES {{ number_format($investment['daily_profit'], 2) }}</p>
                </div>
                <div class="bg-gold/5 rounded-lg p-3">
                    <p class="text-xs text-gold-400">Profit Credited</p>
                    <p class="text-lg font-bold text-green-400">KES {{ number_format($investment['profit_credited'], 2) }}</p>
                </div>
                <div class="bg-gold/5 rounded-lg p-3">
                    <p class="text-xs text-gold-400">Projected Profit</p>
                    <p class="text-lg font-bold text-gold">KES {{ number_format($investment['projected_profit'], 2) }}</p>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gold-400">Status</span>
                    <span class="uppercase {{ $investment['status'] === 'active' ? 'text-green-400' : 'text-gold' }}">
                        {{ $investment['status'] }}
                    </span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gold-400">Started</span>
                    <span class="text-ivory">{{ optional($investment['start_date'])->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gold-400">Ends</span>
                    <span class="text-ivory">{{ optional($investment['end_date'])->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between border-b border-gold/10 pb-2">
                    <span class="text-gold-400">Days Remaining</span>
                    <span class="text-ivory">{{ $investment['days_remaining'] }}</span>
                </div>
            </div>

            @if($investment['status'] === 'active')
                <div class="mt-6">
                    <p class="text-xs text-gold-400 mb-1">Progress · {{ $investment['progress_percent'] }}%</p>
                    <div class="w-full bg-gray-700 rounded-full h-2">
                        <div class="bg-gradient-to-r from-gold-400 to-green-400 h-2 rounded-full"
                             style="width: {{ $investment['progress_percent'] }}%"></div>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
