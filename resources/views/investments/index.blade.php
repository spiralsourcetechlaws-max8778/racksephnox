@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ==================== HEADER ==================== --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold golden-title">Investment Portfolio</h1>
                <p class="text-gold-400 text-sm mt-1">Plan investments · Machine holdings · Live stats</p>
            </div>
            <a href="{{ route('machines.index') }}" class="btn-outline-silver text-sm mt-3 md:mt-0">
                RX Machine Series →
            </a>
        </div>

        {{-- ==================== STATS ==================== --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs uppercase">Total Invested</p>
                <p class="text-2xl font-bold text-gold mt-1">KES {{ number_format($stats['total_invested'], 2) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs uppercase">Total Profit</p>
                <p class="text-2xl font-bold text-green-400 mt-1">KES {{ number_format($stats['total_profit'], 2) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs uppercase">Active</p>
                <p class="text-2xl font-bold text-gold mt-1">{{ $stats['active_count'] }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs uppercase">ROI</p>
                <p class="text-2xl font-bold text-green-400 mt-1">{{ $stats['roi'] }}%</p>
            </div>
        </div>

        {{-- ==================== ACTIVE PLANS ==================== --}}
        @if($plans->count())
            <h2 class="text-xl font-bold text-gold mb-3">Available Plans</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
                @foreach($plans as $plan)
                    <div class="card-golden p-5">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-bold text-gold">{{ $plan->name }}</h3>
                            <span class="text-xs bg-gold/20 text-gold px-2 py-1 rounded-full">
                                {{ $plan->roi_percent }}% ROI
                            </span>
                        </div>
                        <p class="text-sm text-ivory/70 mb-4 min-h-[40px]">{{ $plan->description }}</p>

                        <div class="space-y-2 text-sm mb-4">
                            <div class="flex justify-between">
                                <span class="text-gold-400">Range</span>
                                <span class="text-ivory">{{ $plan->range_label }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gold-400">Daily Rate</span>
                                <span class="text-green-400 font-bold">{{ $plan->daily_interest_rate }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gold-400">Duration</span>
                                <span class="text-ivory">{{ $plan->duration_days }} days</span>
                            </div>
                        </div>

                        @auth
                            <form method="POST" action="{{ route('investments.store') }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                <input type="number" name="amount" min="{{ $plan->min_amount }}"
                                       max="{{ $plan->max_amount }}" step="0.01"
                                       placeholder="Amount (KES)"
                                       class="input-golden w-full" required>
                                <button type="submit" class="btn-golden w-full">Invest Now</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="btn-outline-silver w-full text-center block">
                                Login to Invest
                            </a>
                        @endauth
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ==================== MY INVESTMENTS ==================== --}}
        <h2 class="text-xl font-bold text-gold mb-3">My Holdings</h2>

        @if($investments->count())
            <div class="space-y-3">
                @foreach($investments as $inv)
                    <a href="{{ route('investments.show', $inv['id']) }}"
                       class="card-golden p-4 block hover:scale-[1.01] transition">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br {{ $inv['color'] }} flex items-center justify-center">
                                    <i class="fas {{ $inv['icon'] }} text-white"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-gold">{{ $inv['name'] }}</p>
                                    <p class="text-xs text-gold-400/60">
                                        {{ $inv['source'] === 'machine' ? 'Machine' : 'Plan' }} ·
                                        Started {{ optional($inv['start_date'])->format('M d, Y') ?? '—' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gold">KES {{ number_format($inv['amount'], 2) }}</p>
                                <p class="text-xs text-ivory/60">Invested</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-400">+KES {{ number_format($inv['profit_credited'], 2) }}</p>
                                <p class="text-xs text-ivory/60">Profit</p>
                            </div>
                            <div class="text-right min-w-[110px]">
                                <p class="text-xs uppercase {{ $inv['status'] === 'active' ? 'text-green-400' : 'text-gold' }}">
                                    {{ $inv['status'] }}
                                </p>
                                @if($inv['status'] === 'active')
                                    <p class="text-xs text-ivory/60">{{ $inv['days_remaining'] }}d left</p>
                                    <div class="w-24 bg-gray-700 rounded-full h-1.5 mt-2">
                                        <div class="bg-gold h-1.5 rounded-full" style="width: {{ $inv['progress_percent'] }}%"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="card-golden p-10 text-center">
                <i class="fas fa-chart-line text-4xl text-gold/40 mb-4"></i>
                <p class="text-ivory/60 mb-4">You have no investments yet.</p>
                <a href="{{ route('machines.index') }}" class="btn-golden">Explore Opportunities</a>
            </div>
        @endif

    </div>
</div>
@endsection
