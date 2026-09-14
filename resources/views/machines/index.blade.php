@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ==================== HEADER ==================== --}}
        <div class="text-center mb-10">
            <h1 class="text-4xl md:text-5xl font-bold golden-title">RX Machine Series</h1>
            <p class="text-gold-400 mt-2">
                7 Sacred Portals • VIP 1‑3 • Golden Ratio Φ • 88% ROI in 14 days
            </p>
        </div>

        {{-- ==================== STATS GRID ==================== --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-10">
            <div class="stat-card p-5 text-center">
                <p class="text-gold-400 text-xs uppercase tracking-widest">RX Machines</p>
                <p class="text-4xl font-bold text-gold mt-2">{{ $stats['machine_count'] }}</p>
            </div>
            <div class="stat-card p-5 text-center">
                <p class="text-gold-400 text-xs uppercase tracking-widest">VIP Levels</p>
                <p class="text-4xl font-bold text-gold mt-2">{{ $stats['vip_levels'] }}</p>
            </div>
            <div class="stat-card p-5 text-center">
                <p class="text-gold-400 text-xs uppercase tracking-widest">Total ROI</p>
                <p class="text-4xl font-bold text-green-400 mt-2">{{ $stats['total_roi'] }}%</p>
            </div>
            <div class="stat-card p-5 text-center">
                <p class="text-gold-400 text-xs uppercase tracking-widest">Days Cycle</p>
                <p class="text-4xl font-bold text-gold mt-2">{{ $stats['cycle_days'] }}</p>
            </div>
        </div>

        {{-- ==================== CONSTANTS ==================== --}}
        <div class="text-center text-xs text-gold-400/60 mb-10 font-mono">
            Φ = {{ $stats['phi'] }}  •  λ = {{ $stats['lambda'] }}  •  π = {{ $stats['pi'] }}  •  e = {{ $stats['e'] }}
        </div>

        {{-- ==================== USER STATS (if logged in) ==================== --}}
        @auth
            <div class="card-golden p-5 mb-10">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div>
                        <p class="text-gold-400 text-xs">Total Invested</p>
                        <p class="text-xl font-bold text-gold">KES {{ number_format($userStats['total_invested'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gold-400 text-xs">Total Profit</p>
                        <p class="text-xl font-bold text-green-400">KES {{ number_format($userStats['total_profit'], 2) }}</p>
                    </div>
                    <div>
                        <p class="text-gold-400 text-xs">Active</p>
                        <p class="text-xl font-bold text-gold">{{ $userStats['active_count'] }}</p>
                    </div>
                    <div>
                        <p class="text-gold-400 text-xs">Projected Profit</p>
                        <p class="text-xl font-bold text-gold">KES {{ number_format($userStats['projected_profit'], 2) }}</p>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a href="{{ route('machines.my-investments') }}" class="btn-outline-silver text-sm">View My Investments →</a>
                </div>
            </div>
        @endauth

        {{-- ==================== MACHINE GRID ==================== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($machines as $machine)
                @php
                    $features = $machine->features ?? [];
                    $roi = $machine->growth_rate;
                @endphp
                <div class="card-golden p-6 group hover:scale-[1.02] transition-all">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $machine->color }} flex items-center justify-center">
                                <i class="fas {{ $machine->icon }} text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gold">{{ $machine->name }}</h3>
                                <p class="text-xs text-gold-400/70">{{ $machine->code }} • {{ $machine->risk_profile }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-green-400">{{ round($roi) }}%</p>
                            <p class="text-xs text-gold-400">ROI</p>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="text-sm text-ivory/70 mb-4 min-h-[40px]">{{ $machine->description }}</p>

                    {{-- VIP amounts --}}
                    <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                        <div class="bg-gold/5 rounded-lg p-2">
                            <p class="text-xs text-gold-400">VIP 1</p>
                            <p class="text-sm font-bold text-ivory">KES {{ number_format($machine->vip1_start_amount, 0) }}</p>
                        </div>
                        <div class="bg-gold/5 rounded-lg p-2">
                            <p class="text-xs text-gold-400">VIP 2</p>
                            <p class="text-sm font-bold text-ivory">KES {{ number_format($machine->vip2_start_amount, 0) }}</p>
                        </div>
                        <div class="bg-gold/5 rounded-lg p-2">
                            <p class="text-xs text-gold-400">VIP 3</p>
                            <p class="text-sm font-bold text-ivory">KES {{ number_format($machine->vip3_start_amount, 0) }}</p>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-between items-center text-xs text-gold-400/60 mb-4">
                        <span><i class="fas fa-clock"></i> {{ $machine->duration_days }} days</span>
                        <span><i class="fas fa-users"></i> {{ $machine->investor_count }}</span>
                        <span><i class="fas fa-coins"></i> KES {{ number_format($machine->total_invested, 0) }}</span>
                    </div>

                    {{-- CTA --}}
                    <a href="{{ route('machines.show', $machine->code) }}"
                       class="btn-golden w-full text-center block">
                        Explore Portals →
                    </a>
                </div>
            @endforeach
        </div>

        {{-- ==================== FOOTER ==================== --}}
        <div class="text-center mt-12 pt-6 border-t border-gold/20">
            <p class="text-xs text-gold-400/60 italic">
                I Am The Source | Divine Golden Phi | Infinite Spiral of Creation | 888 Hz
            </p>
            <p class="text-xs text-gold-500/40 mt-1">
                Guardian and Protector | Law of Information | Racksephnox
            </p>
            <div class="flex justify-center gap-4 mt-2 text-xs">
                <a href="{{ route('legal.terms') }}" class="text-gold-400 hover:text-gold">Terms</a>
                <a href="{{ route('legal.privacy') }}" class="text-gold-400 hover:text-gold">Privacy</a>
                <a href="{{ route('guide') }}" class="text-gold-400 hover:text-gold">Guide</a>
            </div>
        </div>

    </div>
</div>
@endsection
