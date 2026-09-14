@extends('layouts.app')

@section('content')
<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold golden-title">My Machine Investments</h1>
            <a href="{{ route('machines.index') }}" class="btn-outline-silver text-sm">Browse Machines →</a>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs">Total Invested</p>
                <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['total_invested'], 2) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs">Profit Earned</p>
                <p class="text-2xl font-bold text-green-400">KES {{ number_format($stats['total_profit'], 2) }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs">Active</p>
                <p class="text-2xl font-bold text-gold">{{ $stats['active_count'] }}</p>
            </div>
            <div class="stat-card p-4 text-center">
                <p class="text-gold-400 text-xs">Projected Profit</p>
                <p class="text-2xl font-bold text-gold">KES {{ number_format($stats['projected_profit'], 2) }}</p>
            </div>
        </div>

        {{-- Investment List --}}
        @if($investments->count())
            <div class="space-y-4">
                @foreach($investments as $inv)
                    <div class="card-golden p-5">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $inv->machine->color ?? 'from-gold-400 to-amber-400' }} flex items-center justify-center">
                                    <i class="fas {{ $inv->machine->icon ?? 'fa-microchip' }} text-white"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gold">{{ $inv->machine->name ?? 'Unknown' }} · VIP {{ $inv->vip_level }}</h3>
                                    <p class="text-xs text-gold-400/60">
                                        {{ $inv->start_date?->format('M d, Y') }} → {{ $inv->end_date?->format('M d, Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gold">KES {{ number_format($inv->amount, 2) }}</p>
                                <p class="text-xs text-ivory/60">Invested</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-green-400">+KES {{ number_format($inv->profit_credited, 2) }}</p>
                                <p class="text-xs text-ivory/60">Profit credited</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase {{ $inv->status === 'active' ? 'text-green-400' : ($inv->status === 'completed' ? 'text-gold' : 'text-red-400') }}">
                                    {{ $inv->status }}
                                </p>
                                @if($inv->status === 'active')
                                    <p class="text-xs text-ivory/60">{{ $inv->days_remaining }}d remaining</p>
                                    <div class="w-24 bg-gray-700 rounded-full h-1.5 mt-2">
                                        <div class="bg-gold h-1.5 rounded-full" style="width: {{ $inv->progress_percent }}%"></div>
                                    </div>
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
                    </div>
                @endforeach
            </div>
        @else
            <div class="card-golden p-10 text-center">
                <i class="fas fa-microchip text-4xl text-gold/40 mb-4"></i>
                <p class="text-ivory/60 mb-4">You have no machine investments yet.</p>
                <a href="{{ route('machines.index') }}" class="btn-golden">Browse RX Machines</a>
            </div>
        @endif

    </div>
</div>
@endsection
