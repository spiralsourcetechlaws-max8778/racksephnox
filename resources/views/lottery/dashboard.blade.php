@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold golden-title">🎰 Lottery Dashboard</h1>
            <p class="text-gold-400 mt-2">{{ auth()->user()->name }}'s Sacred Statistics</p>
        </div>

        {{-- STATS GRID --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Total Spins</p>
                <p class="text-2xl font-bold text-gold mt-1">{{ number_format($stats['total_spins']) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Winning Spins</p>
                <p class="text-2xl font-bold text-green-400 mt-1">{{ number_format($stats['total_wins']) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Total Won</p>
                <p class="text-lg font-bold text-gold mt-1">KES {{ number_format($stats['total_won'], 0) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Total Bet</p>
                <p class="text-lg font-bold text-ivory mt-1">KES {{ number_format($stats['total_bet'], 0) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Biggest Win</p>
                <p class="text-lg font-bold text-green-400 mt-1">KES {{ number_format($stats['biggest_win'], 0) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-xs text-gold-400 uppercase">Jackpots</p>
                <p class="text-2xl font-bold text-gold mt-1">{{ $stats['jackpot_wins'] }}</p>
            </div>
        </div>

        {{-- JACKPOT POOLS --}}
        <h2 class="text-xl font-bold text-gold mb-3">💰 Live Jackpot Pools</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach($jackpots as $jp)
                <div class="card-golden p-4 text-center relative">
                    <p class="text-xs uppercase text-gold-400">{{ $jp->label }}</p>
                    <p class="text-xl font-bold text-gold mt-1">KES {{ number_format($jp->current_pool, 0) }}</p>
                    <p class="text-[10px] text-gold-400/60">{{ $jp->frequency_hz }} Hz</p>
                </div>
            @endforeach
        </div>

        {{-- STREAK & MISSIONS --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="card-golden p-5">
                <h3 class="text-lg font-bold text-gold mb-4">🔥 Streak</h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div>
                        <p class="text-xs text-gold-400">Current</p>
                        <p class="text-2xl font-bold text-gold">{{ $streak['current'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gold-400">Longest</p>
                        <p class="text-2xl font-bold text-gold">{{ $streak['longest'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gold-400">Multiplier</p>
                        <p class="text-2xl font-bold text-green-400">×{{ number_format($streak['multiplier'], 1) }}</p>
                    </div>
                </div>
                @if($streak['next_milestone'])
                    <p class="text-xs text-ivory/60 mt-3 text-center">
                        Next: day {{ $streak['next_milestone'] }} → KES {{ number_format($streak['next_reward'], 0) }}
                    </p>
                @endif
                <div class="mt-4 text-center">
                    <a href="{{ route('lottery.index') }}" class="btn-golden text-sm px-6 py-2">
                        {{ $streak['spun_today'] ? '✅ Spun today' : '🎰 Continue Streak' }}
                    </a>
                </div>
            </div>

            <div class="card-golden p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gold">🎯 Today's Missions</h3>
                    <a href="{{ route('lottery.missions') }}" class="text-xs text-gold-400">All →</a>
                </div>
                @forelse(array_slice($missions, 0, 4) as $m)
                    <div class="border-b border-gold/10 py-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-ivory">{{ $m['mission']->name }}</span>
                            <span class="text-gold-400">{{ $m['progress'] }}/{{ $m['target'] }}</span>
                        </div>
                        <div class="w-full bg-gray-700 rounded-full h-1 mt-1">
                            <div class="bg-gold h-1 rounded-full" style="width: {{ $m['progress_percent'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-ivory/50 text-center">No missions today.</p>
                @endforelse
            </div>
        </div>

        {{-- QUICK LINKS --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('lottery.index') }}" class="card-golden p-4 text-center hover:scale-[1.02] transition">
                <i class="fas fa-dice-d6 text-3xl text-gold mb-2"></i>
                <p class="text-sm text-ivory">Play</p>
            </a>
            <a href="{{ route('lottery.tournaments') }}" class="card-golden p-4 text-center hover:scale-[1.02] transition">
                <i class="fas fa-trophy text-3xl text-gold mb-2"></i>
                <p class="text-sm text-ivory">Tournaments</p>
            </a>
            <a href="{{ route('lottery.guilds') }}" class="card-golden p-4 text-center hover:scale-[1.02] transition">
                <i class="fas fa-users text-3xl text-gold mb-2"></i>
                <p class="text-sm text-ivory">Guilds</p>
            </a>
            <a href="{{ route('lottery.fair') }}" class="card-golden p-4 text-center hover:scale-[1.02] transition">
                <i class="fas fa-shield-alt text-3xl text-gold mb-2"></i>
                <p class="text-sm text-ivory">Provably Fair</p>
            </a>
        </div>

    </div>
</div>
@endsection
