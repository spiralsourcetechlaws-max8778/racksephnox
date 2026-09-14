@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">

        <a href="{{ route('lottery.index') }}" class="text-gold-400 text-sm">
            <i class="fas fa-arrow-left"></i> Back to Lottery
        </a>

        <h1 class="text-3xl font-bold golden-title mt-4 mb-2 text-center">💎 Jackpot Pools</h1>
        <p class="text-gold-400 text-center text-sm mb-8">Four sacred tiers · Abundance-aligned · Auto-dropping</p>

        <div class="space-y-6">
            @foreach($jackpots as $jp)
                <div class="card-golden p-6 relative overflow-hidden">
                    @if($jp->must_drop_soon)
                        <div class="absolute top-3 right-3 bg-red-500/30 text-red-300 px-3 py-1 rounded-full text-xs animate-pulse">
                            MUST DROP SOON
                        </div>
                    @endif

                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gold">{{ $jp->label }}</h2>
                            <p class="text-xs text-gold-400/70">{{ $jp->frequency_hz }} Hz frequency</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gold">KES {{ number_format($jp->current_pool, 0) }}</p>
                            <p class="text-xs text-gold-400">Current Pool</p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex justify-between text-xs text-gold-400 mb-1">
                            <span>Progress to ceiling</span>
                            <span>{{ $jp->progress_percent }}%</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-3">
                            <div class="bg-gradient-to-r from-gold-400 via-green-400 to-gold-500 h-3 rounded-full"
                                 style="width: {{ $jp->progress_percent }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3 text-xs">
                        <div class="bg-gold/5 rounded p-2 text-center">
                            <p class="text-gold-400">Seed</p>
                            <p class="text-ivory font-bold">KES {{ number_format($jp->seed_amount, 0) }}</p>
                        </div>
                        <div class="bg-gold/5 rounded p-2 text-center">
                            <p class="text-gold-400">Ceiling</p>
                            <p class="text-ivory font-bold">KES {{ number_format($jp->ceiling_amount ?? 0, 0) }}</p>
                        </div>
                        <div class="bg-gold/5 rounded p-2 text-center">
                            <p class="text-gold-400">Wins</p>
                            <p class="text-ivory font-bold">{{ $jp->wins_count }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if(session('jackpot_wins') || isset($recentWins))
            <h2 class="text-xl font-bold text-gold mt-10 mb-3">Recent Jackpot Wins</h2>
            <div class="space-y-2">
                @foreach($recentWins ?? [] as $w)
                    <div class="card-golden p-3 flex justify-between text-sm">
                        <span>{{ $w['user'] }} · {{ ucfirst($w['tier']) }}</span>
                        <span class="text-green-400 font-bold">KES {{ number_format($w['amount'], 0) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
