@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Back --}}
        <a href="{{ route('machines.index') }}" class="text-gold-400 hover:text-gold text-sm">
            <i class="fas fa-arrow-left"></i> All Machines
        </a>

        {{-- ==================== MACHINE HEADER ==================== --}}
        <div class="card-golden p-6 mt-4 mb-8">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br {{ $machine->color }} flex items-center justify-center">
                    <i class="fas {{ $machine->icon }} text-white text-3xl"></i>
                </div>
                <div class="flex-1">
                    <h1 class="text-3xl font-bold golden-title">{{ $machine->name }}</h1>
                    <p class="text-gold-400 text-sm mt-1">
                        {{ $machine->code }} • Risk: {{ $machine->risk_profile }} • {{ $machine->duration_days }}-day cycle
                    </p>
                    <p class="text-ivory/70 mt-2">{{ $machine->description }}</p>
                </div>
                <div class="text-right">
                    <p class="text-4xl font-bold text-green-400">{{ round($machine->growth_rate) }}%</p>
                    <p class="text-xs text-gold-400">Target ROI</p>
                </div>
            </div>

            {{-- Constants --}}
            @if($machine->features)
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 text-center">
                    <div class="bg-gold/5 rounded-lg p-3">
                        <p class="text-xs text-gold-400">Φ Phi</p>
                        <p class="text-sm font-mono text-gold">{{ $machine->features['phi'] ?? '—' }}</p>
                    </div>
                    <div class="bg-gold/5 rounded-lg p-3">
                        <p class="text-xs text-gold-400">λ Lambda</p>
                        <p class="text-sm font-mono text-gold">{{ $machine->features['lambda'] ?? '—' }}</p>
                    </div>
                    <div class="bg-gold/5 rounded-lg p-3">
                        <p class="text-xs text-gold-400">Frequency</p>
                        <p class="text-sm font-mono text-gold">{{ $machine->features['frequency_hz'] ?? 888 }} Hz</p>
                    </div>
                    <div class="bg-gold/5 rounded-lg p-3">
                        <p class="text-xs text-gold-400">Cycle</p>
                        <p class="text-sm font-mono text-gold">{{ $machine->features['cycle_days'] ?? 14 }} days</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ==================== VIP TIERS ==================== --}}
        <h2 class="text-2xl font-bold text-gold mb-4">VIP Portals</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            @foreach($vips as $vip)
                <div class="card-golden p-6 hover:scale-[1.02] transition-all">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gold">VIP {{ $vip->level }}</h3>
                        <span class="text-xs bg-gold/20 text-gold px-3 py-1 rounded-full">
                            ×{{ number_format($vip->bonus_multiplier, 2) }} bonus
                        </span>
                    </div>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-xs text-gold-400">Minimum</span>
                            <span class="text-sm font-bold text-ivory">KES {{ number_format($vip->start_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gold-400">Maximum</span>
                            <span class="text-sm font-bold text-ivory">KES {{ number_format($vip->max_amount ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gold-400">Growth Rate</span>
                            <span class="text-sm font-bold text-green-400">{{ round($vip->growth_rate, 2) }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gold-400">Daily Profit</span>
                            <span class="text-sm font-bold text-gold">
                                KES {{ number_format($vip->daily_profit_min, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-xs text-gold-400">Cycle</span>
                            <span class="text-sm font-bold text-ivory">{{ $vip->duration_days }} days</span>
                        </div>
                    </div>

                    @auth
                        <button onclick="openInvestModal({{ $vip->level }}, {{ $vip->start_amount }}, {{ $vip->max_amount ?? 0 }})"
                                class="btn-golden w-full">
                            Invest VIP {{ $vip->level }}
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="btn-outline-silver w-full text-center block">
                            Login to Invest
                        </a>
                    @endauth
                </div>
            @endforeach
        </div>

        {{-- ==================== USER INVESTMENTS ==================== --}}
        @auth
            @if($userInvestments->count())
                <h2 class="text-2xl font-bold text-gold mb-4">Your Investments in {{ $machine->name }}</h2>
                <div class="space-y-3 mb-10">
                    @foreach($userInvestments as $inv)
                        <div class="card-golden p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                            <div>
                                <p class="font-bold text-gold">VIP {{ $inv->vip_level }} · KES {{ number_format($inv->amount, 2) }}</p>
                                <p class="text-xs text-ivory/60">
                                    Started {{ $inv->start_date?->format('Y-m-d') }} ·
                                    Ends {{ $inv->end_date?->format('Y-m-d') }}
                                </p>
                                <p class="text-xs text-green-400">
                                    Profit credited: KES {{ number_format($inv->profit_credited, 2) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gold-400">{{ ucfirst($inv->status) }}</p>
                                <p class="text-xs text-ivory/60">{{ $inv->days_remaining }} days left</p>
                                @if($inv->status === 'active')
                                    <form method="POST" action="{{ route('machines.early-withdraw', $inv) }}" class="mt-2">
                                        @csrf
                                        <button class="text-red-400 text-xs hover:text-red-300"
                                                onclick="return confirm('Early withdrawal incurs a 20% penalty. Continue?')">
                                            Early Withdraw
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endauth

        {{-- ==================== INVEST MODAL ==================== --}}
        @auth
            <div id="investModal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center">
                <div class="bg-cosmic-deep rounded-2xl p-6 max-w-md w-full border border-gold/30">
                    <h3 class="text-xl font-bold text-gold mb-4">Invest in {{ $machine->name }}</h3>
                    <form method="POST" action="{{ route('machines.invest', $machine) }}">
                        @csrf
                        <input type="hidden" name="vip_level" id="modalVipLevel">
                        <div class="mb-4">
                            <label class="text-xs text-gold-400">VIP Level</label>
                            <input type="text" id="modalVipLabel" class="input-golden w-full" readonly>
                        </div>
                        <div class="mb-4">
                            <label class="text-xs text-gold-400">Amount (KES)</label>
                            <input type="number" name="amount" id="modalAmount" class="input-golden w-full" step="0.01" required>
                            <p class="text-xs text-gold-400/60 mt-1" id="modalHint"></p>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" onclick="closeInvestModal()" class="btn-outline-silver flex-1">Cancel</button>
                            <button type="submit" class="btn-golden flex-1">Confirm Investment</button>
                        </div>
                    </form>
                </div>
            </div>
        @endauth

    </div>
</div>

@auth
<script>
    function openInvestModal(level, min, max) {
        document.getElementById('modalVipLevel').value = level;
        document.getElementById('modalVipLabel').value = 'VIP ' + level;
        document.getElementById('modalAmount').value = min;
        document.getElementById('modalAmount').min = min;
        if (max > 0) document.getElementById('modalAmount').max = max;
        document.getElementById('modalHint').textContent =
            'Min: KES ' + min.toLocaleString() + (max ? ' · Max: KES ' + max.toLocaleString() : '');
        const m = document.getElementById('investModal');
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    function closeInvestModal() {
        const m = document.getElementById('investModal');
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
</script>
@endauth

@endsection
